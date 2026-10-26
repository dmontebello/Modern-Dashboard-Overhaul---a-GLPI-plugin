<?php

declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskMenu;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskStageService;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskMenu.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskStageService.php';

Session::checkCentralAccess();
if (!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace']) {
    Html::displayNotFoundError();
    return;
}
$isIt = str_starts_with(strtolower((string) (Session::getLanguage() ?? 'en')), 'it');
$tr = static fn (string $it, string $en): string => $isIt ? $it : $en;
$esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scalar = static fn (string $key, string $fallback = ''): string =>
    isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : $fallback;

if ($scalar('source') === 'standalone' && !StandaloneTask::enabled()) { Html::displayNotFoundError(); return; }
if (!ProjectTask::canView() && !StandaloneTask::canView()) {
    Session::checkRight('projecttask', ProjectTask::READMY); return;
}

$source = $scalar('source', 'all');
if (!in_array($source, ['all', 'project', 'standalone'], true)) { $source = 'all'; }
if ($source === 'all' && !ProjectTask::canView()) { $source = 'standalone'; }
if ($source === 'all' && !StandaloneTask::canView()) { $source = 'project'; }
$standalone = $source === 'standalone';
$unified = $source === 'all';
if ($standalone && !StandaloneTask::enabled()) { Html::displayNotFoundError(); return; }
if ($standalone && !StandaloneTask::canView()) { Session::checkRight(StandaloneTask::$rightname, StandaloneTask::READMY); return; }
if (!$standalone && !ProjectTask::canView()) { Session::checkRight('projecttask', ProjectTask::READMY); return; }
$itemtype = $standalone ? StandaloneTask::class : ProjectTask::class;

$view = $scalar('view', 'board') === 'list' ? 'list' : 'board';
// A legacy ?group=progress must never turn Project statuses into percentage columns.
// All uses shared stages only when the three native GLPI mappings are available.
$group = $unified ? 'progress' : 'state';
$filter = $scalar('filter', 'all');
$filter = in_array($filter, ['open', 'all', 'done', 'overdue'], true) ? $filter : 'all';
$dueFilter = $scalar('due', 'all');
$dueFilter = in_array($dueFilter, ['all', 'overdue', 'today', 'week', 'none'], true) ? $dueFilter : 'all';
$sort = $scalar('board_sort', 'due');
$sort = in_array($sort, ['due', 'name', 'project', 'state', 'progress'], true) ? $sort : 'due';
if ($standalone && in_array($sort, ['project', 'progress'], true)) { $sort = 'due'; }
$q = trim(substr($scalar('q'), 0, 120));
$projectId = filter_var($scalar('project', '0'), FILTER_VALIDATE_INT);
$projectId = $projectId === false ? 0 : max(0, $projectId);
$creationProjectId = $projectId;
if ($standalone) { $projectId = 0; }
$stateId = filter_var($scalar('state', '-1'), FILTER_VALIDATE_INT);
$stateId = $stateId === false ? -1 : max(-1, $stateId);
$taskForm = static fn (int $id): string => $itemtype::getFormURL() . '?id=' . $id;
$projectForm = static fn (int $id): string => Project::getFormURL() . '?id=' . $id;
global $CFG_GLPI;
$base = rtrim((string) $CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/tasks.php';
$boardUrl = static fn (array $args = []): string => $base . '?' . http_build_query(array_replace([
    'view' => 'board', 'group' => $group, 'filter' => $filter, 'q' => $q,
    'project' => $projectId, 'state' => $stateId, 'due' => $dueFilter, 'board_sort' => $sort,
    'source' => $source,
], $args), '', '&', PHP_QUERY_RFC3986);

$creating = $scalar('new') === '1' && (ProjectTask::canCreate() || StandaloneTask::canCreate());
if ($creating && $scalar('kind') === '') {
    Html::redirect(rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task.form.php');
    return;
}
$kind = $scalar('kind');
if ($creating && $kind === 'standalone' && StandaloneTask::canCreate()) {
    Html::redirect(StandaloneTask::getFormURL()); return;
}
$creationError = false;
if ($creating && $kind === 'project' && ProjectTask::canCreate() && $creationProjectId > 0) {
    // GLPI 11 Html::redirect() intentionally throws RedirectException.
    // Catch only errors from project validation, never the redirect itself.
    $canCreateInProject = false;
    try {
        $canCreateInProject = TaskBoard::canCreateInProject($creationProjectId);
    } catch (Throwable $error) {
        Toolbox::logInFile('php-errors', '[mdoverhaul Tasks project validation] ' . get_class($error) . "\n");
        $creationError = true;
    }
    if ($canCreateInProject) {
        Html::redirect(ProjectTask::getFormURL() . '?projects_id=' . $creationProjectId);
    }
}
Html::header('Tasks', '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl">';
echo '<header class="mdo-tasks-head"><div><p class="mdo-tasks-eyebrow">MODERN DASHBOARD OVERHAUL</p>';
echo '<h1>' . $esc($tr('Attività', 'Tasks')) . '</h1>';
echo '<p class="text-secondary">' . $esc($unified
    ? $tr('Tutte le attività accessibili: di progetto e indipendenti.', 'All accessible project and independent tasks.')
    : ($standalone
        ? $tr('Attività indipendenti, senza progetto obbligatorio.', 'Independent tasks, with no required project.')
        : $tr('Attività di progetto GLPI, separate dai ticket e dalle richieste.', 'Native GLPI project tasks, separate from tickets and requests.'))
) . '</p></div><div class="mdo-tasks-actions">';
if (Project::canView()) {
    echo '<a class="btn btn-outline-secondary" href="' . $esc(Project::getSearchURL()) . '"><i class="ti ti-briefcase me-1"></i>'
        . $esc($tr('Progetti', 'Projects')) . '</a>';
}
if (ProjectTask::canCreate() || StandaloneTask::canCreate()) {
    echo '<a class="btn btn-outline-primary" href="' . $esc(rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task.form.php') . '">'
        . '<i class="ti ti-plus me-1"></i>' . $esc($tr('Nuova task', 'New task')) . '</a>';
}
echo '</div></header>';
if ($creating) {
    if ($kind !== 'project' || !ProjectTask::canCreate()) {
        echo '<div class="mdo-tasks-create"><h2>' . $esc($tr('Tipo di attività', 'Task type')) . '</h2><div class="mdo-tasks-create-actions">';
        if (StandaloneTask::canCreate()) {
            echo '<a class="btn btn-primary" href="' . $esc(StandaloneTask::getFormURL()) . '">' . $esc($tr('Task indipendente', 'Independent task')) . '</a>';
        }
        if (ProjectTask::canCreate()) {
            echo '<a class="btn btn-outline-primary" href="' . $esc($base . '?new=1&kind=project&source=' . ($standalone ? 'standalone' : 'project')) . '">' . $esc($tr('Task di progetto', 'Project task')) . '</a>';
        }
        echo '</div></div></div>'; Html::footer(); return;
    }
    echo '<div class="mdo-tasks-create"><h2>' . $esc($tr('Scegli il progetto', 'Choose a project')) . '</h2>';
    echo '<p>' . $esc($tr('La nuova attività si apre nel form nativo GLPI del progetto scelto.', 'The new task opens in the native GLPI form for the selected project.')) . '</p>';
    if ($creationError || $creationProjectId > 0 || $scalar('continue') === '1') {
        echo '<p role="alert">' . $esc($tr(
            'Il progetto selezionato non è disponibile per creare task. Controlla i diritti di progetto e riprova.',
            'The selected project is not available for creating tasks. Check project rights and retry.'
        )) . '</p>';
    }
    // Native-first: GLPI owns project search, entity/visibility filtering and AJAX.
    // Avoid constructing our own project catalogue via a plugin SQL query.
    $dropdownReady = false;
    if (Project::canView()) {
        echo '<form id="mdo-project-create" method="get" action="' . $esc($base) . '" class="mdo-tasks-create-form">';
        echo '<input type="hidden" name="continue" value="1">';
        echo '<input type="hidden" name="kind" value="project"><input type="hidden" name="source" value="' . $esc($source) . '">';
        echo '<input type="hidden" name="new" value="1"><div class="mdo-tasks-project-picker">';
        echo '<span class="mdo-tasks-project-label">' . $esc($tr('Progetto GLPI', 'GLPI project')) . '</span>';
        $level = ob_get_level();
        try {
            ob_start();
            $rendered = Dropdown::show(Project::class, [
                'name' => 'project',
                'value' => 0,
                'display_emptychoice' => true,
                'required' => true,
                'aria_label' => $tr('Seleziona il progetto per creare la task', 'Select the project to create the task'),
                'condition' => ['is_deleted' => 0, 'is_template' => 0],
                'entity' => -1,
                'width' => '100%',
                'addicon' => false,
            ]);
            $html = (string) ob_get_clean();
            if ($rendered !== false && $html !== '') {
                echo $html;
                $dropdownReady = true;
            }
        } catch (Throwable $error) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            Toolbox::logInFile('php-errors', '[mdoverhaul Tasks project dropdown] ' . $error->getMessage() . "\n");
        }
        echo '</div>';
        echo '</form>';
    }
    if (!$dropdownReady) {
        echo '<p role="status">' . $esc($tr(
            'Se il selettore non è disponibile, apri il progetto dalla ricerca nativa GLPI e usa la scheda Attività.',
            'If the selector is unavailable, open a project from GLPI native search and use its Tasks tab.'
        )) . '</p>';
    }
    echo '<div class="mdo-tasks-create-actions">';
    if ($dropdownReady) {
        echo '<button class="btn btn-primary" type="submit" form="mdo-project-create">' . $esc($tr('Continua', 'Continue')) . '</button>';
    }
    if (Project::canView()) {
        echo '<a class="btn btn-outline-secondary" href="' . $esc(Project::getSearchURL()) . '">'
            . $esc($tr('Apri elenco progetti GLPI', 'Open GLPI project list')) . '</a> ';
    }
    echo '<a class="btn btn-outline-secondary" href="' . $esc($base) . '">' . $esc($tr('Torna alle attività', 'Back to tasks')) . '</a></div></div></div>';
    Html::footer();
    return;
}
// GLPI profile ACL remains mandatory. Admin-only guidance when independent
// tasks are enabled but the current profile has no read rights; do not reveal
// task rows and do not weaken the native 403 for direct standalone URLs.
if (!$standalone && StandaloneTask::enabled() && !StandaloneTask::canView()
    && Session::haveRight('config', UPDATE)) {
    echo '<div class="alert alert-warning mdo-tasks-permission-hint" role="status">'
        . $esc($tr(
            'Attività indipendenti attive, ma il profilo non ha READMY / READALL. '
            . 'Apri Amministrazione → Profili → profilo corrente → MDO · Attività indipendenti, '
            . 'assegna lettura e creazione, salva e accedi nuovamente.',
            'Independent tasks are enabled, but this profile lacks READMY / READALL. '
            . 'Open Administration → Profiles → current profile → MDO · Independent tasks, '
            . 'grant read and create, save, then sign in again.'
        )) . '</div>';
}
echo '<nav class="mdo-tasks-view-tabs mdo-tasks-source-tabs" aria-label="' . $esc($tr('Origine attività', 'Task source')) . '">';
foreach (['all' => [ProjectTask::canView() && StandaloneTask::canView(), $tr('Tutte', 'All')], 'project' => [ProjectTask::canView(), $tr('Progetto', 'Project')], 'standalone' => [StandaloneTask::canView(), $tr('Indipendenti', 'Independent')]] as $key => [$visible, $label]) {
    if ($visible) { echo '<a class="btn ' . ($source === $key ? 'btn-primary' : 'btn-outline-secondary') . '"' . ($source === $key ? ' aria-current="page"' : '') . ' href="' . $esc($boardUrl(['source'=>$key,'view'=>$view,'group'=>'state','project'=>0,'state'=>-1])) . '">' . $esc($label) . '</a>'; }
}
echo '</nav>';
echo '<nav class="mdo-tasks-view-tabs" aria-label="' . $esc($tr('Viste delle attività', 'Task views')) . '">';
echo '<a class="btn ' . ($view === 'list' ? 'btn-primary' : 'btn-outline-secondary') . '"' . ($view === 'list' ? ' aria-current="page"' : '') . ' href="' . $esc($boardUrl(['view' => 'list'])) . '">'
    . '<i class="ti ti-list me-1"></i>' . $esc($tr('Elenco', 'List')) . '</a>';
echo '<a class="btn ' . ($view === 'board' ? 'btn-primary' : 'btn-outline-secondary') . '"' . ($view === 'board' ? ' aria-current="page"' : '') . ' href="' . $esc($boardUrl()) . '">'
    . '<i class="ti ti-layout-kanban me-1"></i>Kanban</a></nav>';
if ($view === 'list' && !$unified) {
    echo '<p class="mdo-tasks-hint mdo-tasks-list-hint">' . $esc(
        !$standalone && ProjectTask::canCreate()
            ? $tr(
                'La creazione di un progetto non crea automaticamente attività. Usa Nuova task per aggiungere la prima; ricerca e permessi restano quelli nativi GLPI.',
                'Creating a project does not automatically create tasks. Use New task to add the first one; search and permissions remain native GLPI.'
            )
            : $tr(
                'Questa vista mostra le attività accessibili con ricerca e permessi nativi GLPI.',
                'This view shows accessible tasks with native GLPI search and permissions.'
            )
    ) . '</p>';
    // GLPI has no front/projecttask.php. Keep native search, pagination and export
    // inside the workspace rather than creating a second page below the header.
    $params = Search::manageParams($itemtype, $_GET);
    $params['target'] = $boardUrl(['view' => 'list']);
    $params['display_type'] = Search::HTML_OUTPUT;
    echo '<section class="mdo-tasks-native-search" aria-label="' . $esc($tr('Elenco attività', 'Task list')) . '">';
    echo '<div class="search_page row search-no-forced-height"><div class="col search-container disable-overflow-y" data-glpi-search-container>';
    Search::showGenericSearch($itemtype, $params);
    Search::showList($itemtype, $params);
    echo '</div></div></section></div>';
    Html::footer();
    return;
}
echo '<p class="mdo-tasks-hint">' . $esc($tr(
    'Elenco e Kanban conservano filtri separati. Nell’elenco puoi usare anche le ricerche salvate GLPI.',
    'List and Kanban use separate filters. Native saved searches are also available in the list.'
)) . '</p>';

try {
    $loaded = $standalone ? StandaloneTask::loadBoard() : ($unified ? (function (): array {
        $a = TaskBoard::load(); $b = StandaloneTask::loadBoard();
        return ['tasks' => array_merge($a['tasks'], $b['tasks']), 'truncated' => $a['truncated'] || $b['truncated'], 'scanned' => $a['scanned'] + $b['scanned']];
    })() : TaskBoard::load());
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
foreach ($all as &$record) {
    $record['source'] = $record['source'] ?? 'project';
    $record['stage'] = TaskStageService::stage($record, $unified);
}
unset($record);
$scope = TaskBoard::filter($all, $q, 'all', $stateId, $projectId, $dueFilter);
$stats = ['all' => count($scope), 'open' => 0, 'done' => 0, 'overdue' => 0];
foreach ($scope as $task) {
    $stats[$task['percent_done'] >= 100 ? 'done' : 'open']++;
    $stats['overdue'] += (int) TaskBoard::isOverdue($task);
}
$projects = [];
$states = [];
foreach ($all as $task) {
    if ($task['project_readable']) {
        $projects[$task['project_id']] = $task['project'];
    }
    $states[$task['state_id']] = $task['state'] !== '' ? $task['state'] : ($task['state_id'] > 0
        ? $tr('Stato #', 'State #') . $task['state_id'] : $tr('Senza stato', 'No state'));
}
asort($projects, SORT_NATURAL | SORT_FLAG_CASE);
asort($states, SORT_NATURAL | SORT_FLAG_CASE);
$filtered = TaskBoard::sort(TaskBoard::filter($all, $q, $filter, $stateId, $projectId, $dueFilter), $sort);
$dueDate = static function (string $due): string {
    return TaskBoard::dateTimestamp($due) !== null ? Html::convDateTime($due) : '—';
};
if ($loaded['truncated']) {
    echo '<p class="mdo-tasks-partial" role="status">' . $esc($tr('Vista e conteggi parziali', 'Partial view and counts')) . '</p>';
}
echo '<div class="mdo-tasks-summary">';
foreach ([
    ['all', $tr('Visibili', 'Visible'), 'ti-list-check'],
    ['open', $tr('Da completare', 'Incomplete'), 'ti-progress'],
    ['done', $tr('Completate', 'Completed'), 'ti-circle-check'],
    ['overdue', $tr('Scadute', 'Overdue'), 'ti-alert-triangle'],
] as [$key, $label, $icon]) {
    echo '<a class="mdo-tasks-stat' . ($filter === $key ? ' is-active' : '') . '" href="' . $esc($boardUrl(['filter' => $key])) . '">'
        . '<span><i class="ti ' . $icon . '"></i> ' . $esc($label) . '</span><strong>' . (int) $stats[$key] . '</strong></a>';
}
echo '</div>';
if ($loaded['truncated']) {
    echo '<div class="alert alert-warning">' . $esc($tr(
        'Vista parziale: vengono verificate al massimo 1000 attività recenti nelle entità attive, prima dei permessi individuali. Tutti i conteggi e i filtri si riferiscono alle attività visibili in questo campione. Usa l’elenco nativo per la ricerca completa.',
        'Partial view: up to 1000 recent tasks in active entities are checked before individual permissions. All counts and filters refer to visible tasks in this sample. Use native list search for complete results.'
    )) . '</div>';
}
$activeFilters = (int) ($q !== '') + (int) ($projectId > 0) + (int) ($stateId >= 0)
    + (int) ($filter !== 'all') + (int) ($dueFilter !== 'all');
echo '<details class="mdo-tasks-filter-panel"' . ($activeFilters > 0 ? ' open' : '') . '><summary>'
    . $esc($tr('Filtri e ordinamento', 'Filters and sorting'))
    . ($activeFilters > 0 ? ' · ' . $activeFilters . ' ' . $esc($tr('attivi', 'active')) : '') . '</summary>';
echo '<form class="mdo-tasks-filters" method="get" action="' . $esc($base) . '"><input type="hidden" name="view" value="board"><input type="hidden" name="source" value="' . $esc($source) . '">';
echo '<label>' . $esc($tr('Cerca', 'Search')) . '<input class="form-control" name="q" maxlength="120" value="' . $esc($q) . '" placeholder="Task, ID..."></label>';
echo '<label>' . $esc($tr('Mostra', 'Show')) . '<select class="form-select" name="filter">';
foreach (['open' => $tr('Da completare', 'Incomplete'), 'all' => $tr('Tutte', 'All'), 'done' => $tr('Completate', 'Completed'), 'overdue' => $tr('Scadute', 'Overdue')] as $key => $label) {
    echo '<option value="' . $key . '"' . ($filter === $key ? ' selected' : '') . '>' . $esc($label) . '</option>';
}
echo '</select></label>';
echo '<label>' . $esc($tr('Scadenza', 'Due date')) . '<select class="form-select" name="due">';
foreach (['all' => $tr('Qualsiasi', 'Any'), 'overdue' => $tr('Scadute', 'Overdue'), 'today' => $tr('Oggi', 'Today'), 'week' => $tr('Prossimi 7 giorni', 'Next 7 days'), 'none' => $tr('Senza scadenza', 'No due date')] as $key => $label) {
    echo '<option value="' . $key . '"' . ($dueFilter === $key ? ' selected' : '') . '>' . $esc($label) . '</option>';
}
echo '</select></label>';
if (!$standalone) {
echo '<label>' . $esc($tr('Progetto', 'Project')) . '<select class="form-select" name="project"><option value="0">' . $esc($tr('Tutti', 'All')) . '</option>';
foreach ($projects as $id => $name) {
    echo '<option value="' . (int) $id . '"' . ($projectId === (int) $id ? ' selected' : '') . '>' . $esc($name) . '</option>';
}
echo '</select></label>';
}
echo '<label>' . $esc($tr('Stato', 'State')) . '<select class="form-select" name="state"><option value="-1">' . $esc($tr('Tutti', 'All')) . '</option>';
foreach ($states as $id => $name) {
    echo '<option value="' . (int) $id . '"' . ($stateId === (int) $id ? ' selected' : '') . '>' . $esc($name) . '</option>';
}
echo '</select></label>';
echo '<label>' . $esc($tr('Colonne', 'Group by')) . '<select class="form-select" name="group">';
echo '<option value="' . $esc($group) . '" selected>' . $esc($unified ? $tr('Stato (Project + Indipendenti)', 'Status (Project + Independent)') : ($standalone ? $tr('Stato', 'Status') : $tr('Stato GLPI', 'GLPI status'))) . '</option>';
echo '</select></label>';
echo '<label>' . $esc($tr('Ordina per', 'Sort by')) . '<select class="form-select" name="board_sort">';
foreach (['due' => $tr('Scadenza', 'Due date'), 'name' => $tr('Titolo', 'Title'), 'project' => $tr('Progetto', 'Project'), 'state' => $tr('Stato', 'State'), 'progress' => $tr('Avanzamento', 'Progress')] as $key => $label) {
    if ($standalone && in_array($key, ['project','progress'], true)) { continue; }
    echo '<option value="' . $key . '"' . ($sort === $key ? ' selected' : '') . '>' . $esc($label) . '</option>';
}
echo '</select></label><div class="mdo-tasks-filter-actions"><button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i>'
    . $esc($tr('Filtra', 'Apply')) . '</button><a class="btn btn-outline-secondary" href="' . $esc($boardUrl(['q'=>'','filter'=>'all','due'=>'all','project'=>0,'state'=>-1,'board_sort'=>'due'])) . '">'
    . $esc($tr('Reimposta', 'Reset')) . '</a></div></form></details>';
if ($projectId > 0 && isset($projects[$projectId])) {
    echo '<a class="btn btn-outline-secondary" href="' . $esc($projectForm($projectId) . '&forcetab=Project%243') . '">'
        . $esc($tr('Apri il Kanban nativo del progetto', 'Open native project Kanban')) . '</a>';
}
echo '<p class="mdo-tasks-hint">' . $esc($tr(
    'Trascina le card modificabili tra gli stati oppure usa Sposta in. Le altre attività si aprono nel modulo GLPI.',
    'Drag editable cards between statuses or use Move to. Other tasks open in their GLPI forms.'
)) . '</p>';
echo '<p class="mdo-tasks-results" role="status">' . count($filtered) . ' ' . $esc($tr('attività mostrate', 'tasks shown'))
    . ($loaded['truncated'] ? ' · ' . $esc($tr('campione parziale', 'partial sample')) : '') . '</p>';
if ($filtered === [] && $view !== 'board') {
    echo '<div class="mdo-tasks-empty" role="status"><h2>' . $esc($all === []
        ? $tr('Nessuna attività visibile', 'No visible tasks') : $tr('Nessun risultato', 'No results')) . '</h2><p>'
        . $esc($all === [] ? ($standalone ? $tr('Le attività indipendenti accessibili appariranno qui.', 'Accessible independent tasks will appear here.') : $tr('Le attività dei progetti accessibili appariranno qui.', 'Tasks from accessible projects will appear here.'))
            : $tr('Modifica i filtri o reimpostali per vedere le altre attività.', 'Change or reset filters to see other tasks.')) . '</p></div></div>';
    Html::footer();
    return;
}

if ($unified && $view === 'list') {
    $start = max(0, min(10000, (int)($scalar('start', '0'))));
    $page = array_slice($filtered, $start, 30);
    echo '<div class="mdo-tasks-native-search"><table class="table table-vcenter"><thead><tr><th>' .
        $esc($tr('Attività', 'Task')) . '</th><th>' . $esc($tr('Tipo', 'Type')) .
        '</th><th>' . $esc($tr('Fase', 'Stage')) . '</th><th>' . $esc($tr('Progetto', 'Project')) .
        '</th><th class="text-end">' . $esc($tr('Azioni', 'Actions')) . '</th></tr></thead><tbody>';
    foreach ($page as $record) {
        $url = ($record['source'] === 'standalone' ? StandaloneTask::getFormURL() : ProjectTask::getFormURL())
            . '?id=' . (int)$record['id'];
        $taskItem = TaskStageService::item($record['source'], (int)$record['id']);
        $canDelete = $taskItem !== null && $taskItem->can((int)$record['id'], DELETE)
            && $taskItem->maybeDeleted() && empty($taskItem->fields['is_deleted']);
        echo '<tr data-mdo-list-row><td><a href="' . $esc($url) . '">' . $esc($record['name']) .
            '</a></td><td><span class="mdo-task-type-badge">' . $esc($record['source'] === 'standalone' ? $tr('Indipendente', 'Independent') :
                $tr('Progetto', 'Project')) . '</span></td><td><span class="mdo-task-stage-badge mdo-task-stage-' . $esc($record['stage']) . '">' . $esc(TaskStageService::labels()[$record['stage']]) .
            '</span></td><td>' . $esc($record['project_readable'] ? $record['project'] : '') . '</td><td class="text-end">';
        if ($canDelete) {
            echo '<form class="mdo-task-delete-form" method="post" action="' . $esc(rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task-delete.php') . '">'
                . '<input type="hidden" name="_glpi_csrf_token" value="' . $esc(Session::getNewCSRFToken()) . '">'
                . '<input type="hidden" name="source" value="' . $esc($record['source']) . '">'
                . '<input type="hidden" name="id" value="' . (int)$record['id'] . '">'
                . '<input type="hidden" name="confirm_delete" value="0">'
                . '<button type="submit" class="btn btn-outline-danger btn-sm" aria-label="' . $esc($tr('Elimina task', 'Delete task') . ': ' . $record['name']) . '" data-mdo-task-delete data-confirm="' . $esc($tr('Spostare questa task nel cestino?', 'Move this task to trash?')) . '">'
                . '<i class="ti ti-trash" aria-hidden="true"></i> ' . $esc($tr('Elimina', 'Delete')) . '</button></form>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div><nav class="mdo-tasks-pages" aria-label="' . $esc($tr('Pagine', 'Pages')) . '">';
    if ($start > 0) {
        echo '<a class="btn btn-outline-secondary" href="' . $esc($boardUrl(['view'=>'list','start'=>max(0,$start-30)])) . '">' . $esc($tr('Precedenti', 'Previous')) . '</a>';
    }
    if ($start+30 < count($filtered)) {
        echo '<a class="btn btn-outline-secondary" href="' . $esc($boardUrl(['view'=>'list','start'=>$start+30])) . '">' . $esc($tr('Successive', 'Next')) . '</a>';
    }
    echo '</nav><p class="mdo-tasks-hint">' . $esc($tr(
        'Vista combinata limitata alle candidate accessibili; usa le viste Progetto e Indipendenti per la ricerca GLPI completa.',
        'Combined view is limited to the visible candidate sample; use the native Project and Independent lists for complete GLPI search.'
    )) . '</p></div>';
    Html::footer();
    return;
}
// Always show native ProjectState statuses, including empty columns. When an
// installation lacks an unambiguous three-way native mapping, the All board
// keeps independent stages and Project states in separate, type-safe lanes.
// Never silently use percent_done as a substitute for a Project status.
$mixedNativeProject = $unified && count(TaskStageService::projectStageTargets()) !== 3;
$nativeStateColumns = !$standalone ? TaskBoard::projectStates() : [];
$projectChoices = [];
foreach ($nativeStateColumns as $id => $name) {
    $projectChoices['state-' . $id] = $name;
}
$columns = [];
if ($unified || $standalone) {
    foreach (TaskStageService::labels() as $stage => $name) {
        $columns[$stage] = ['name' => $name, 'items' => [],
            'accept' => $mixedNativeProject ? 'standalone' : 'all', 'drop' => true];
    }
}
if (!$standalone && (!$unified || $mixedNativeProject)) {
    foreach ($projectChoices as $key => $name) {
        $columns[$key] = ['name' => $mixedNativeProject ? $tr('Progetto: ', 'Project: ') . $name : $name,
            'items' => [], 'accept' => 'project', 'drop' => true];
    }
}
foreach ($filtered as $task) {
    $native = $task['source'] === 'project' && (!$unified || $mixedNativeProject);
    $column = $native ? 'state-' . $task['state_id'] : $task['stage'];
    if (!isset($columns[$column])) {
        $columns[$column] = [
            'name' => $native ? ($states[$task['state_id']] ?? $tr('Senza stato', 'No status'))
                : (TaskStageService::labels()[$column] ?? $column),
            'items' => [],
            'accept' => $native ? 'project' : ($mixedNativeProject ? 'standalone' : 'all'),
            'drop' => !$native || isset($projectChoices[$column]),
        ];
    }
    $columns[$column]['items'][] = $task;
}
if ($mixedNativeProject) {
    echo '<p class="mdo-tasks-hint" role="status">' . $esc($tr(
        'Le fasi comuni non sono associate in modo univoco agli stati GLPI: in Tutte le attività di progetto usano gli stati nativi, quelle indipendenti le proprie fasi. Nessuna percentuale viene alterata.',
        'Common stages are not mapped unambiguously to GLPI statuses: All shows native Project statuses alongside Independent stages. Percent complete is not overwritten.'
    )) . '</p>';
}
if ($mixedNativeProject && !$projectChoices) {
    echo '<p class="alert alert-warning" role="status">' . $esc($tr(
        'Nessuno stato di progetto GLPI configurato: configura gli stati prima di spostare le attività di progetto.',
        'No GLPI Project statuses configured: configure them before moving Project tasks.'
    )) . '</p>';
}
echo '<p class="mdo-tasks-hint" id="mdo-board-scroll">' . $esc($tr('Scorri orizzontalmente per vedere le colonne.', 'Scroll horizontally to see columns.')) . '</p>';
echo '<div class="mdo-tasks-board" data-mdo-board data-endpoint="' . $esc(rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task-stage.php') . '" data-token="' . $esc(Session::getNewCSRFToken()) . '" data-mode="' . $esc($unified ? 'all' : $group) . '" role="region" aria-label="Kanban" aria-describedby="mdo-board-scroll" tabindex="0">';
foreach ($columns as $columnKey => $column) {
    echo '<section class="mdo-tasks-column" data-mdo-column="' . $esc((string)$columnKey) . '" data-accept-source="' . $esc($column['accept']) . '" data-drop-enabled="' . ($column['drop'] ? '1' : '0') . '"><header><h2>' . $esc($column['name']) . '</h2><span>' . count($column['items']) . '</span></header>';
    echo '<div class="mdo-tasks-column-body">';
    if ($column['items'] === []) {
        echo '<p class="mdo-tasks-hint">' . $esc($tr('Nessuna attività', 'No tasks')) . '</p>';
    }
    foreach ($column['items'] as $task) {
        $item = TaskStageService::item($task['source'], (int) $task['id']);
        $canMove = $item !== null && $item->can((int) $task['id'], UPDATE)
            && ($task['source'] !== 'project' || !$unified || !$mixedNativeProject
                || $projectChoices !== []);
        echo '<article class="mdo-tasks-card" data-task-type="' . $esc($task['source']) . '" data-source="' . $esc($task['source']) . '" data-id="' . (int)$task['id'] . '" data-version="' . $esc((string)($item->fields['date_mod'] ?? '')) . '" data-can-move="' . ($canMove ? '1' : '0') . '" data-complete="' . ($task['percent_done'] >= 100 ? '1' : '0') . '" data-stage="' . $esc($columnKey) . '" data-group="' . $esc($group) . '"' . ($canMove ? ' draggable="true"' : '') . '><small>' . $esc($task['source'] === 'standalone' ? StandaloneTask::getTypeName() : $tr('Task di progetto', 'Project task')) . ' #' . (int)$task['id'] . '</small><a class="mdo-tasks-card-title" href="' . $esc(($task['source'] === 'standalone' ? StandaloneTask::getFormURL() : ProjectTask::getFormURL()) . '?id=' . (int)$task['id']) . '">' 
            . $esc($task['name'] ?: '—') . '</a>';
        if ($task['project_readable']) {
            echo '<a class="mdo-tasks-project" href="' . $esc($projectForm($task['project_id'])) . '">'
                . $esc($task['project']) . '</a>';
        } elseif ($task['source'] === 'project') {
            echo '<span class="mdo-tasks-project">' . $esc($tr('Progetto non accessibile', 'Restricted project')) . '</span>';
        }
        if ($task['description'] !== '') {
            echo '<p class="mdo-tasks-card-description">' . $esc($task['description']) . '</p>';
        }
        echo '<a class="mdo-tasks-journal" href="' . $esc(rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task-activity.php?source=' . rawurlencode($task['source']) . '&id=' . (int)$task['id']) . '">' . $esc($tr('Diario e allegati', 'Activity and files')) . '</a>';
        echo '<div class="mdo-tasks-card-meta"><span class="mdo-tasks-state">' . $esc((string)$task['state']) . '</span>';
        if (TaskBoard::isOverdue($task)) {
            echo '<span class="mdo-tasks-overdue">' . $esc($tr('Scaduta', 'Overdue')) . '</span>';
        }
        echo '</div><p class="mdo-tasks-date">' . $esc($tr('Scadenza prevista', 'Planned end')) . ': '
            . $esc($dueDate($task['due'])) . '</p>';
        if (TaskBoard::dateTimestamp($task['real_end']) !== null) {
            echo '<p class="mdo-tasks-date">' . $esc($tr('Fine effettiva', 'Actual end')) . ': ' . $esc($dueDate($task['real_end'])) . '</p>';
        }
        if ($task['source'] === 'project') {
        echo '<div class="mdo-tasks-progress" role="progressbar" aria-label="' . $esc($tr('Avanzamento', 'Progress')) . '" aria-valuenow="'
            . (int) $task['percent_done'] . '" aria-valuemin="0" aria-valuemax="100"><span style="width:'
            . (int) $task['percent_done'] . '%"></span></div><small>' . (int) $task['percent_done'] . '%</small>';
        }
        if ($canMove) {
            echo '<label class="mdo-stage-control">' . $esc($tr('Sposta in', 'Move to')) . ' <select data-stage-choice aria-label="' . $esc($tr('Sposta attività', 'Move task')) . '">';
            $nativeChoices = $task['source'] === 'project' && (!$unified || $mixedNativeProject);
            $choices = $nativeChoices ? $projectChoices : TaskStageService::labels();
            if (!isset($choices[$columnKey])) {
                echo '<option value="' . $esc((string)$columnKey) . '" selected disabled>' . $esc($column['name']) . '</option>';
            }
            foreach ($choices as $stageKey => $stageName) {
                echo '<option value="' . $esc((string)$stageKey) . '"' . ($columnKey === $stageKey ? ' selected' : '') . '>' . $esc($stageName) . '</option>';
            }
            echo '</select></label>';
        }
        echo '</article>';
    }
    echo '</div></section>';
}
echo '</div><p data-mdo-live class="visually-hidden" role="status" aria-live="polite"></p></div>';
Html::footer();
