<?php

declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskMenu;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskMenu.php';
require_once __DIR__ . '/../src/TaskBoard.php';

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
$taskForm = static fn (int $id): string => ProjectTask::getFormURL(false) . '?id=' . $id;
$projectForm = static fn (int $id): string => Project::getFormURL(false) . '?id=' . $id;
$base = '/plugins/mdoverhaul/front/tasks.php';
$boardUrl = static fn (array $args = []): string => $base . '?' . http_build_query(array_replace([
    'view' => 'board', 'group' => $group, 'filter' => $filter, 'q' => $q,
    'project' => $projectId, 'state' => $stateId,
], $args), '', '&', PHP_QUERY_RFC3986);

Html::header('Tasks', '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl">';
echo '<header class="mdo-tasks-head"><div><p class="mdo-tasks-eyebrow">MODERN DASHBOARD OVERHAUL</p>';
echo '<h1>' . $esc($tr('Attività', 'Tasks')) . '</h1>';
echo '<p class="text-secondary">' . $esc($tr(
    'Attività di progetto GLPI, separate dai ticket e dalle richieste.',
    'Native GLPI project tasks, separate from tickets and requests.'
)) . '</p></div><div class="mdo-tasks-actions">';
echo '<a class="btn btn-outline-secondary" href="' . $esc(Project::getSearchURL(false)) . '"><i class="ti ti-briefcase me-1"></i>'
    . $esc($tr('Progetti', 'Projects')) . '</a>';
if (ProjectTask::canCreate()) {
    echo '<a class="btn btn-outline-primary" href="' . $esc(Project::getSearchURL(false)) . '" title="'
        . $esc($tr('Per aggiungere una task seleziona prima un progetto.', 'Select a project before creating a task.')) . '">'
        . '<i class="ti ti-plus me-1"></i>' . $esc($tr('Nuova task', 'New task')) . '</a>';
}
echo '</div></header>';
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
    Search::show(ProjectTask::class);
    Html::footer();
    return;
}

$loaded = TaskBoard::load();
$all = $loaded['tasks'];
$stats = ['all' => count($all), 'open' => 0, 'done' => 0, 'overdue' => 0];
$projects = [];
$states = [];
foreach ($all as $task) {
    $stats[$task['percent_done'] >= 100 ? 'done' : 'open']++;
    $stats['overdue'] += (int) TaskBoard::isOverdue($task);
    $projects[$task['project_id']] = $task['project'];
    $states[$task['state_id']] = $task['state'];
}
asort($projects, SORT_NATURAL | SORT_FLAG_CASE);
asort($states, SORT_NATURAL | SORT_FLAG_CASE);
$filtered = TaskBoard::filter($all, $q, $filter, $stateId, $projectId);
$dueDate = static function (string $due): string {
    $ts = $due !== '' ? strtotime($due) : false;
    return $ts !== false ? date('d/m/Y', $ts) : '—';
};
echo '<div class="mdo-tasks-summary">';
foreach ([
    ['all', $tr('Totali', 'Total'), 'ti-list-check'],
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
        'Vista Kanban limitata alle 1000 attività più recenti. I conteggi possono essere parziali; l’elenco nativo non ha questo limite.',
        'Kanban is limited to the newest 1000 tasks. Counts may be partial; native search does not have this limit.'
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
        echo '<a class="mdo-tasks-project" href="' . $esc($projectForm($task['project_id'])) . '">'
            . $esc($task['project']) . '</a>';
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
