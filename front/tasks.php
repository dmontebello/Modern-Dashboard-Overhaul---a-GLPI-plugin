<?php

declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskMenu;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskMenu.php';
require_once __DIR__ . '/../src/TaskBoard.php';

Session::checkCentralAccess();
if (!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace']) {
    Html::displayNotFoundError();
    return;
}
if (!ProjectTask::canView()) {
    Session::checkRight('projecttask', ProjectTask::READMY);
    return;
}

$isIt = str_starts_with(strtolower((string) (Session::getLanguage() ?? 'en')), 'it');
$tr = static fn (string $it, string $en): string => $isIt ? $it : $en;
$esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scalar = static fn (string $key, string $fallback = ''): string =>
    isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : $fallback;

$view = $scalar('view') === 'board' ? 'board' : 'list';
$group = $scalar('group') === 'state' ? 'state' : 'progress';
$filter = $scalar('filter', 'open');
$filter = in_array($filter, ['open', 'all', 'done', 'overdue'], true) ? $filter : 'open';
$q = trim(substr($scalar('q'), 0, 120));
$projectId = filter_var($scalar('project', '0'), FILTER_VALIDATE_INT);
$projectId = $projectId === false ? 0 : max(0, $projectId);
$stateId = filter_var($scalar('state', '-1'), FILTER_VALIDATE_INT);
$stateId = $stateId === false ? -1 : max(-1, $stateId);
$taskForm = static fn (int $id): string => ProjectTask::getFormURL() . '?id=' . $id;
$projectForm = static fn (int $id): string => Project::getFormURL() . '?id=' . $id;
global $CFG_GLPI;
$base = rtrim((string) $CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/tasks.php';
$boardUrl = static fn (array $args = []): string => $base . '?' . http_build_query(array_replace([
    'view' => 'board', 'group' => $group, 'filter' => $filter, 'q' => $q,
    'project' => $projectId, 'state' => $stateId,
], $args), '', '&', PHP_QUERY_RFC3986);

$creating = $scalar('new') === '1' && ProjectTask::canCreate();
$creation = ['projects' => [], 'truncated' => false];
$creationError = false;
if ($creating) {
    try {
        $creation = TaskBoard::creationProjects();
    } catch (Throwable $error) {
        Toolbox::logInFile('php-errors', '[mdoverhaul Tasks] ' . $error->getMessage() . "\n");
        $creationError = true;
    }
}
if ($creating && $projectId > 0 && isset($creation['projects'][$projectId])) {
    Html::redirect(ProjectTask::getFormURL() . '?projects_id=' . $projectId);
    return;
}

Html::header('Tasks', '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl">';
echo '<header class="mdo-tasks-head"><div><p class="mdo-tasks-eyebrow">MODERN DASHBOARD OVERHAUL</p>';
echo '<h1>' . $esc($tr('Attività', 'Tasks')) . '</h1>';
echo '<p class="text-secondary">' . $esc($tr(
    'Attività di progetto GLPI, separate dai ticket e dalle richieste.',
    'Native GLPI project tasks, separate from tickets and requests.'
)) . '</p></div><div class="mdo-tasks-actions">';
if (Project::canView()) {
    echo '<a class="btn btn-outline-secondary" href="' . $esc(Project::getSearchURL()) . '"><i class="ti ti-briefcase me-1"></i>'
        . $esc($tr('Progetti', 'Projects')) . '</a>';
}
if (ProjectTask::canCreate()) {
    echo '<a class="btn btn-outline-primary" href="' . $esc($base . '?new=1') . '" title="'
        . $esc($tr('Per aggiungere una task seleziona prima un progetto.', 'Select a project before creating a task.')) . '">'
        . '<i class="ti ti-plus me-1"></i>' . $esc($tr('Nuova task', 'New task')) . '</a>';
}
echo '</div></header>';
if ($creating) {
    echo '<h2>' . $esc($tr('Scegli il progetto', 'Choose a project')) . '</h2>';
    echo '<p>' . $esc($tr('La nuova attività si apre nel form nativo GLPI del progetto scelto.', 'The new task opens in the native GLPI form for the selected project.')) . '</p>';
    if ($creationError) {
        echo '<p role="alert">' . $esc($tr('Impossibile caricare i progetti. Riprova o consulta i log GLPI.', 'Unable to load projects. Try again or check the GLPI logs.')) . '</p>';
    } elseif ($projectId > 0) {
        echo '<p role="alert">' . $esc($tr('Progetto non disponibile per la creazione.', 'Project unavailable for task creation.')) . '</p>';
    }
    if ($creation['projects'] !== []) {
        echo '<form method="get" action="' . $esc($base) . '"><input type="hidden" name="new" value="1">';
        Dropdown::showFromArray('project', $creation['projects'], [
            'display_emptychoice' => true, 'value' => 0,
            'aria_label' => $tr('Progetto obbligatorio', 'Required project'),
        ]);
        echo '<button class="btn btn-primary" type="submit">' . $esc($tr('Continua', 'Continue')) . '</button></form>';
    } else {
        echo '<p role="status">' . $esc($tr('Nessun progetto disponibile per creare attività.', 'No projects available for task creation.')) . '</p>';
    }
    if ($creation['truncated']) {
        echo '<p>' . $esc($tr('Scelta parziale: per gli altri progetti usa la ricerca nativa e la scheda Attività del progetto.', 'Partial selection: use native project search and the project Tasks tab for other projects.')) . '</p>';
    }
    echo '<a class="btn btn-outline-secondary" href="' . $esc($base) . '">' . $esc($tr('Torna alle attività', 'Back to tasks')) . '</a></div>';
    Html::footer();
    return;
}
echo '<div class="mdo-tasks-view-tabs" role="navigation" aria-label="' . $esc($tr('Viste delle attività', 'Task views')) . '">';
echo '<a class="btn ' . ($view === 'list' ? 'btn-primary' : 'btn-outline-secondary') . '" href="' . $esc($base) . '">'
    . '<i class="ti ti-list me-1"></i>' . $esc($tr('Elenco', 'List')) . '</a>';
echo '<a class="btn ' . ($view === 'board' ? 'btn-primary' : 'btn-outline-secondary') . '" href="' . $esc($boardUrl()) . '">'
    . '<i class="ti ti-layout-kanban me-1"></i>Kanban</a></div>';

if ($view === 'list') {
    echo '<p class="mdo-tasks-hint">' . $esc($tr(
        'Ricerca, ordinamento, colonne e operazioni restano quelli nativi di GLPI, con i relativi permessi.',
        'Search, sorting, columns and actions are provided by GLPI with native permissions.'
    )) . '</p>';
    echo '</div>';
    // GLPI has no front/projecttask.php. Keep all native search actions here.
    $params = Search::manageParams(ProjectTask::class, $_GET);
    $params['target'] = $base;
    $params['display_type'] = Search::HTML_OUTPUT;
    echo '<div class="search_page row"><div class="col search-container" data-glpi-search-container>';
    Search::showGenericSearch(ProjectTask::class, $params);
    Search::showList(ProjectTask::class, $params);
    echo '</div></div>';
    Html::footer();
    return;
}

try {
    $loaded = TaskBoard::load();
} catch (Throwable $error) {
    Toolbox::logInFile('php-errors', '[mdoverhaul Tasks] ' . $error->getMessage() . "\n");
    echo '<div class="alert alert-danger" role="alert">' . $esc($tr(
        'Impossibile caricare le attività. Riprova o consulta i log GLPI.',
        'Unable to load tasks. Try again or check the GLPI logs.'
    )) . '</div></div>';
    Html::footer();
    return;
}
$all = $loaded['tasks'];
$stats = ['all' => count($all), 'open' => 0, 'done' => 0, 'overdue' => 0];
$projects = [];
$states = [];
foreach ($all as $task) {
    $stats[$task['percent_done'] >= 100 ? 'done' : 'open']++;
    $stats['overdue'] += (int) TaskBoard::isOverdue($task);
    if ($task['project_readable']) {
        $projects[$task['project_id']] = $task['project'];
    }
    $states[$task['state_id']] = $task['state'];
}
asort($projects, SORT_NATURAL | SORT_FLAG_CASE);
asort($states, SORT_NATURAL | SORT_FLAG_CASE);
$filtered = TaskBoard::filter($all, $q, $filter, $stateId, $projectId);
$dueDate = static function (string $due): string {
    return TaskBoard::dateTimestamp($due) !== null ? Html::convDateTime($due) : '—';
};
echo '<div class="mdo-tasks-summary">';
foreach ([
    ['all', $tr('Visibili', 'Visible'), 'ti-list-check'],
    ['open', $tr('Da completare', 'Incomplete'), 'ti-progress'],
    ['done', $tr('Completate', 'Completed'), 'ti-circle-check'],
    ['overdue', $tr('Scadute', 'Overdue'), 'ti-alert-triangle'],
] as [$key, $label, $icon]) {
    echo '<a class="mdo-tasks-stat" href="' . $esc($boardUrl(['filter' => $key])) . '">'
        . '<span><i class="ti ' . $icon . '"></i> ' . $esc($label) . '</span><strong>' . (int) $stats[$key] . '</strong></a>';
}
echo '</div>';
if ($loaded['truncated']) {
    echo '<div class="alert alert-warning">' . $esc($tr(
        'Vista parziale: vengono verificate al massimo 1000 attività recenti nelle entità attive, prima dei permessi individuali. Tutti i conteggi e i filtri si riferiscono alle attività visibili in questo campione. Usa l’elenco nativo per la ricerca completa.',
        'Partial view: up to 1000 recent tasks in active entities are checked before individual permissions. All counts and filters refer to visible tasks in this sample. Use native list search for complete results.'
    )) . '</div>';
}
echo '<form class="mdo-tasks-filters" method="get" action="' . $esc($base) . '"><input type="hidden" name="view" value="board">';
echo '<label>' . $esc($tr('Cerca', 'Search')) . '<input class="form-control" name="q" maxlength="120" value="' . $esc($q) . '" placeholder="Task, ID..."></label>';
echo '<label>' . $esc($tr('Mostra', 'Show')) . '<select class="form-select" name="filter">';
foreach (['open' => $tr('Da completare', 'Incomplete'), 'all' => $tr('Tutte', 'All'), 'done' => $tr('Completate', 'Completed'), 'overdue' => $tr('Scadute', 'Overdue')] as $key => $label) {
    echo '<option value="' . $key . '"' . ($filter === $key ? ' selected' : '') . '>' . $esc($label) . '</option>';
}
echo '</select></label>';
echo '<label>' . $esc($tr('Progetto', 'Project')) . '<select class="form-select" name="project"><option value="0">' . $esc($tr('Tutti', 'All')) . '</option>';
foreach ($projects as $id => $name) {
    echo '<option value="' . (int) $id . '"' . ($projectId === (int) $id ? ' selected' : '') . '>' . $esc($name) . '</option>';
}
echo '</select></label>';
echo '<label>' . $esc($tr('Stato', 'State')) . '<select class="form-select" name="state"><option value="-1">' . $esc($tr('Tutti', 'All')) . '</option>';
foreach ($states as $id => $name) {
    echo '<option value="' . (int) $id . '"' . ($stateId === (int) $id ? ' selected' : '') . '>' . $esc($name) . '</option>';
}
echo '</select></label>';
echo '<label>' . $esc($tr('Colonne', 'Group by')) . '<select class="form-select" name="group">';
foreach (['progress' => $tr('Avanzamento', 'Progress'), 'state' => $tr('Stato GLPI', 'GLPI state')] as $key => $label) {
    echo '<option value="' . $key . '"' . ($group === $key ? ' selected' : '') . '>' . $esc($label) . '</option>';
}
echo '</select></label><button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i>'
    . $esc($tr('Filtra', 'Apply')) . '</button></form>';
echo '<p class="mdo-tasks-hint">' . $esc($tr(
    'Kanban in sola lettura: per modificare una task apri la sua scheda GLPI.',
    'Read-only Kanban: open the native GLPI task form to make changes.'
)) . '</p>';

$columns = $group === 'progress' ? [
    'todo' => ['name' => $tr('Da iniziare · 0%', 'To do · 0%'), 'items' => []],
    'doing' => ['name' => $tr('In corso · 1–99%', 'In progress · 1–99%'), 'items' => []],
    'done' => ['name' => $tr('Completate · 100%', 'Done · 100%'), 'items' => []],
] : [];
foreach ($filtered as $task) {
    $column = $group === 'state' ? 'state-' . $task['state_id'] :
        ($task['percent_done'] === 0 ? 'todo' : ($task['percent_done'] >= 100 ? 'done' : 'doing'));
    if (!isset($columns[$column])) {
        $columns[$column] = ['name' => $task['state'], 'items' => []];
    }
    $columns[$column]['items'][] = $task;
}
echo '<div class="mdo-tasks-board" role="region" aria-label="Kanban" tabindex="0">';
foreach ($columns as $column) {
    echo '<section class="mdo-tasks-column"><header><h2>' . $esc($column['name']) . '</h2><span>' . count($column['items']) . '</span></header>';
    echo '<div class="mdo-tasks-column-body">';
    foreach ($column['items'] as $task) {
        echo '<article class="mdo-tasks-card"><a class="mdo-tasks-card-title" href="' . $esc($taskForm($task['id'])) . '">'
            . $esc($task['name'] ?: '—') . '</a>';
        if ($task['project_readable']) {
            echo '<a class="mdo-tasks-project" href="' . $esc($projectForm($task['project_id'])) . '">'
                . $esc($task['project']) . '</a>';
        } else {
            echo '<span class="mdo-tasks-project">' . $esc($tr('Progetto non accessibile', 'Restricted project')) . '</span>';
        }
        if ($task['description'] !== '') {
            echo '<p>' . $esc($task['description']) . '</p>';
        }
        echo '<div class="mdo-tasks-card-meta"><span>' . $esc($task['state']) . '</span><span>'
            . $esc($dueDate($task['due'])) . '</span></div>';
        echo '<div class="mdo-tasks-progress" role="progressbar" aria-label="Avanzamento" aria-valuenow="'
            . (int) $task['percent_done'] . '" aria-valuemin="0" aria-valuemax="100"><span style="width:'
            . (int) $task['percent_done'] . '%"></span></div><small>' . (int) $task['percent_done'] . '%</small>';
        echo '</article>';
    }
    echo '</div></section>';
}
echo '</div></div>';
Html::footer();
