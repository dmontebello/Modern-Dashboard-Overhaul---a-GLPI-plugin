<?php
declare(strict_types=1);
require_once __DIR__ . '/task_bootstrap.php';

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskMenu;

$tests = 0;
function verify(bool $valid, string $message): void
{
    global $tests;
    $tests++;
    if (!$valid) { throw new RuntimeException('FAILED ' . $message); }
    echo "[OK] $tests $message\n";
}
set_error_handler(static function (int $severity, string $message): never { throw new ErrorException($message, 0, $severity); });
taskFixture();
$nativeStates = TaskBoard::projectStates();
verify(count($nativeStates) === 4 && $nativeStates[4] === 'Deferred', 'native states include empty state catalogue');
$htmlStates = taskPage(['view'=>'board','source'=>'project','group'=>'state']);
verify(str_contains($htmlStates, 'data-mdo-column="state-4"') && str_contains($htmlStates, 'Deferred'), 'empty ProjectState column visible');
$flags = array_fill_keys(array_keys(\GlpiPlugin\Mdoverhaul\Settings::defaults()), '0');
$flags['task_workspace'] = $flags['tasks_standalone'] = '1';
\GlpiPlugin\Mdoverhaul\Settings::save($flags);
Session::$standaloneRights = \GlpiPlugin\Mdoverhaul\StandaloneTask::READMY | CREATE | UPDATE;
$htmlMixed = taskPage(['view'=>'board','source'=>'all','group'=>'state']);
verify(str_contains($htmlMixed, 'data-mdo-column="todo"') && str_contains($htmlMixed, 'data-mdo-column="doing"') && str_contains($htmlMixed, 'data-mdo-column="done"'), 'mixed All board uses common stages even with group state param');
taskFixture();
$board = TaskBoard::load();
verify(count($board['tasks']) === 3, 'active entity isolation before per-item ACL');
verify($board['tasks'][0]['state_id'] === 2 && $board['tasks'][0]['state'] === 'Bloccato', 'native ProjectState key and table');
verify($board['scanned'] === 3 && !$board['truncated'], 'no cross-entity candidates or counts');
verify(count($DB->queries) === 1 && $DB->queries[0]['SELECT'] === ['glpi_projecttasks.*'], 'single bounded candidate hydration query');
verify(($DB->queries[0]['ORDER'] ?? null) === ['glpi_projecttasks.id DESC'],
    'GLPI 11 SQL iterator needs ordered string expression, not field=>DESC map');
Session::$projectAll = false;
$board = TaskBoard::load();
verify(array_column($board['tasks'], 'id') === [4, 1], 'actor profile excludes another user task');
verify($board['tasks'][0]['project'] === '' && !$board['tasks'][0]['project_readable'], 'task actor cannot discover restricted project title');
$html = taskPage(['view' => 'board']);
verify(!str_contains($html, 'SEGRETO') && !str_contains($html, 'project.form.php?id=3'), 'restricted project absent from links, choices and markup');
Session::$user = 2;
$board = TaskBoard::load();
verify(array_column($board['tasks'], 'id') === [4, 3], 'second actor has independent visibility');
Session::$entities = [2];
verify(array_column(TaskBoard::load()['tasks'], 'id') === [2], 'second active entity switches visible task set');
taskFixture();
Session::$projectRead = Session::$projectAll = false;
ProjectTask::$rows[3]['team_users'] = [1];
verify(array_column(TaskBoard::load()['tasks'], 'id') === [4, 3, 1], 'native post-load task team hydration preserves team-only access');
taskFixture();
Session::$projectAll = false;
Project::$rows[3]['team_users'] = [1];
verify(array_keys(TaskBoard::creationProjects()['projects']) === [1, 3], 'native project team hydration preserves creation choices');
verify((end($DB->queries)['ORDER'] ?? null) === ['glpi_projects.id DESC'],
    'project chooser query uses GLPI 11 valid ORDER expression');
taskFixture();
ProjectTask::$rows[1]['hook_denied'] = true;
ProjectTask::$rows[3]['is_template'] = 1;
ProjectTask::$rows[4]['is_deleted'] = 1;
verify(TaskBoard::load()['tasks'] === [], 'item hook denial, templates and deleted tasks excluded');
taskFixture();
Project::$rows[3]['is_deleted'] = 1;
verify(array_column(TaskBoard::load()['tasks'], 'id') === [1], 'tasks of deleted projects excluded');
unset(Project::$rows[1]);
verify(TaskBoard::load()['tasks'] === [], 'missing project tolerated');
taskFixture();
ProjectTask::$rows[1]['projectstates_id'] = 0;
ProjectTask::$rows[1]['percent_done'] = 200;
ProjectTask::$rows[1]['plan_end_date'] = null;
$rows = TaskBoard::load()['tasks'];
verify($rows[2]['state'] === '' && $rows[2]['percent_done'] === 100 && !TaskBoard::isOverdue($rows[2]), 'missing state/date and invalid percent handled');
foreach (['', '0000-00-00 00:00:00', '2026-02-30 12:00:00', 'not a date', '<script>'] as $date) {
    verify(TaskBoard::dateTimestamp($date) === null, 'invalid date rejected: ' . $date);
}
verify(TaskBoard::isOverdue(['due' => '2000-01-01', 'percent_done' => 0]), 'unfinished past task overdue');
verify(!TaskBoard::isOverdue(['due' => '2000-01-01', 'percent_done' => 100]), 'completed task never overdue');
taskFixture();
$rows = TaskBoard::load()['tasks'];
verify(count(TaskBoard::filter($rows, 'Descrizione', 'open', 2, 1)) === 1, 'query/project/state/completion filter intersection');
verify(TaskBoard::filter($rows, '<img onerror=alert(1)>', 'all', -1, 0) === [], 'hostile search is plain text');
Session::$projectAll = false;
$choices = TaskBoard::creationProjects();
verify(array_keys($choices['projects']) === [1], 'creation choices require project READ and UPDATE and native task CREATE');
taskPage(['new' => '1', 'kind'=>'project', 'project' => '1']);
verify(Html::$redirect === '/glpi/front/projecttask.form.php?projects_id=1', 'explicit project redirects to native form with root path');
taskFixture();
Html::$throwOnRedirect = true;
try {
    taskPage(['new'=>'1', 'kind'=>'project', 'continue'=>'1', 'project'=>'1']);
    verify(false, 'GLPI redirect must throw out of Tasks controller');
} catch (SimulatedGLPIRedirect $e) {
    verify($e->getMessage() === '/glpi/front/projecttask.form.php?projects_id=1'
        && Html::$redirect === $e->getMessage(),
        'GLPI 11 RedirectException propagates from native ProjectTask redirect');
} finally {
    Html::$throwOnRedirect = false;
}
verify(Toolbox::$errors === [], 'successful GLPI redirect is not logged as a validation failure');

taskFixture();
Session::$projectRead = false;
taskPage(['new' => '1', 'kind'=>'project', 'project' => '1']);
verify(Html::$redirect === '/glpi/front/projecttask.form.php?projects_id=1', 'native creation does not require an extra Project READ permission');
taskFixture();
verify(str_contains(taskPage(['new'=>'1','kind'=>'project','continue'=>'1','project'=>'0']), 'role="alert"'), 'empty selection is an explicit error, never a silent reload');
Session::$projectAll = false;
Html::$redirect = null;
$html = taskPage(['new' => '1', 'kind'=>'project', 'project' => '2']);
verify(Html::$redirect === null && !str_contains($html, 'SEGRETO'), 'manipulated creation project rejected without disclosure');
for ($id = 4; $id < 1010; $id++) { Project::$rows[$id] = array_replace(Project::$rows[1], ['id' => $id]); }
verify(TaskBoard::creationProjects()['truncated'] && !isset(TaskBoard::creationProjects()['projects'][1]), 'project choices bounded with explicit truncation');
taskPage(['new' => '1', 'kind'=>'project', 'project' => '1']);
verify(Html::$redirect === '/glpi/front/projecttask.form.php?projects_id=1', 'known project outside chooser sample checked directly');
taskFixture();
Session::$create = false;
verify(TaskBoard::creationProjects()['projects'] === [] && !str_contains(taskPage([]), 'Nuova task'), 'reader has no creation choices or CTA');
taskFixture();
taskPage(['target' => 'https://evil.invalid', 'criteria' => [['field' => 12, 'value' => 2]], 'start' => 20]);
verify(Search::$calls['list'][0] === ProjectTask::class && parse_url(Search::$calls['list'][1]['target'], PHP_URL_PATH) === '/glpi/plugins/mdoverhaul/front/tasks.php'
    && !str_contains(Search::$calls['list'][1]['target'], 'evil.invalid'), 'public native Search facade keeps search/sort/pagination in workspace');
verify(Search::$calls['form'][1]['criteria'][0]['value'] === 2 && Search::$calls['list'][1]['start'] === 20, 'native criteria and pagination delegated unchanged');
ProjectTask::$rows[1]['name'] = '<img src=x onerror=alert(1)>';
$html = taskPage(['view' => 'board', 'q' => ['hostile'], 'project' => ['2'], 'state' => 'not-int']);
verify(!str_contains($html, '<img src=x') && str_contains($html, '&lt;img'), 'HTML escaped and array-shaped parameters ignored');
verify(str_contains($html, 'data-endpoint='), 'workspace board exposes a stage endpoint');
taskFixture();
for ($id = 5; $id <= 1005; $id++) { ProjectTask::$rows[$id] = array_replace(ProjectTask::$rows[1], ['id' => $id]); }
$board = TaskBoard::load();
verify($board['truncated'] && $board['scanned'] === 1000 && count($board['tasks']) === 1000, '1000 candidate cap with overflow detection');
verify(str_contains(taskPage(['view' => 'board']), 'Vista parziale'), 'partial source disclosed visibly');
$DB->fail = true;
$html = taskPage(['view' => 'board']);
verify(str_contains($html, 'role="alert"') && !str_contains($html, 'private DB failure') && Toolbox::$errors !== [], 'DB failure rendered safely and logged');
$html = taskPage(['new' => '1', 'kind'=>'project']);
verify(str_contains($html, 'name="project"') && !str_contains($html, 'private DB failure'), 'native project dropdown independent of failing optional SQL scan');

taskFixture();
$html = taskPage(['new' => '1', 'kind'=>'project']);
verify(str_contains($html, 'name="project"') && str_contains($html, 'Progetto consentito')
    && !str_contains($html, 'SEGRETO altra entità'), 'native project selector shows permitted projects only');
$html = taskPage([]);
verify(str_contains($html, 'mdo-tasks-native-search') && str_contains($html, 'search-no-forced-height')
    && str_contains($html, 'non crea automaticamente attività'), 'native search embedded directly below Tasks tabs with first-task hint');
taskFixture();
$rows = TaskBoard::load()['tasks'];
$rows[0]['due'] = (new DateTimeImmutable('today'))->format('Y-m-d H:i:s');
$rows[1]['due'] = (new DateTimeImmutable('today +6 days'))->format('Y-m-d H:i:s');
$rows[2]['due'] = '';
verify(count(TaskBoard::filter($rows, '', 'all', -1, 0, 'today')) === 1, 'today filter uses calendar boundaries');
verify(count(TaskBoard::filter($rows, '', 'all', -1, 0, 'week')) === 2, 'next seven calendar days filter');
verify(count(TaskBoard::filter($rows, '', 'all', -1, 0, 'none')) === 1, 'missing due date filter');
verify(count(TaskBoard::filter($rows, '', 'open', 2, 3, 'week')) === 2, 'due filter combines with completion/state/project');
$previousZone = date_default_timezone_get();
date_default_timezone_set('Europe/Rome');
Session::$now = '2026-10-24 00:10:00';
verify(!TaskBoard::isOverdue(['due' => '2026-10-24 00:15:00', 'percent_done' => 0]), 'overdue follows native GLPI request clock');
$calendarRows = array_replace($rows, [0 => array_replace($rows[0], ['due' => '2026-10-30 23:59:59']),
    1 => array_replace($rows[1], ['due' => '2026-10-31 00:00:00']), 2 => array_replace($rows[2], ['due' => '2026-10-24 00:15:00'])]);
verify(count(TaskBoard::filter($calendarRows, '', 'all', -1, 0, 'week')) === 2, 'calendar week boundary survives daylight-saving transition');
verify(count(TaskBoard::filter($calendarRows, '', 'all', -1, 0, 'today')) === 1, 'today follows GLPI request date');
Session::$now = null;
date_default_timezone_set($previousZone);
verify(array_column(TaskBoard::sort($rows, 'due'), 'id') === [4, 3, 1], 'due sorting places missing dates last');
verify(array_column(TaskBoard::sort($rows, 'name'), 'id') === [1, 3, 4], 'natural title sorting');
$html = taskPage(['view' => 'board', 'project' => '1', 'due' => 'week', 'board_sort' => 'name']);
verify(str_contains($html, 'forcetab=Project%243') && str_contains($html, 'aria-current="page"'), 'native project Kanban link and active navigation');
taskPage(['project' => '1', 'due' => 'week', 'board_sort' => 'name']);
parse_str((string) parse_url(Search::$calls['list'][1]['target'], PHP_URL_QUERY), $retained);
verify($retained['due'] === 'week' && $retained['board_sort'] === 'name' && $retained['project'] === '1', 'native list target preserves board preferences without translating native criteria');
$html = taskPage(['view' => 'board', 'group' => 'progress']);
verify(str_contains($html, 'Raggruppamento per avanzamento') && !str_contains($html, 'Da iniziare ·'), 'percent grouping never presented as manual Stage');
ProjectTask::$rows[1]['percent_done'] = 100;
ProjectTask::$rows[1]['real_end_date'] = '2025-03-10 09:00:00';
$html = taskPage(['view' => 'board', 'project' => '1']);
verify(str_contains($html, 'Fine effettiva') && !str_contains($html, 'mdo-tasks-overdue'), 'actual end displayed separately and completed task has no overdue badge');
verify(str_contains(taskPage(['view' => 'board', 'q' => 'nonexistent']), 'data-mdo-column="todo"'), 'project board empty state');
ProjectTask::$rows = [];
verify(str_contains(taskPage(['view' => 'board']), 'data-mdo-column="done"'), 'project board has empty-dataset state');
// Missing standalone rights must not expose the tab or task data; only an admin
// with config:UPDATE receives a clear profile-setup explanation.
taskFixture();
$flags = array_map(static fn(bool $v): string => $v ? '1' : '0', Settings::get());
$flags['tasks_standalone'] = '1';
Settings::save($flags);
$html = taskPage(['view'=>'list']);
verify(str_contains($html, 'READMY / READALL')
    && !preg_match('~<a[^>]*>Indipendenti</a>~', $html),
    'admin sees setup instructions but not unauthorized independent tab');
Session::$config = false;
verify(!str_contains(taskPage(['view'=>'list']), 'READMY / READALL'),
    'non-admin does not see administrative profile setup instructions');
taskFixture();
ProjectTask::$rows = [];
Session::$language = 'en_GB';
verify(str_contains(taskPage(['view' => 'board']), 'No tasks'), 'empty project board follows GLPI language');
taskFixture();
$flags = array_fill_keys(array_keys(Settings::defaults()), '0');
Settings::save($flags);
verify(taskPage([]) === 'NOT_FOUND' && !TaskMenu::canView() && TaskMenu::getMenuContent() === false, 'workspace OFF rejects direct URL and menu');
taskFixture();
Plugin::$active = false;
verify(taskPage([]) === 'NOT_FOUND', 'inactive plugin rejects direct URL');
taskFixture();
Session::$projectRead = Session::$taskRead = false;
verify(!TaskMenu::canView() && TaskMenu::getMenuContent() === false && TaskBoard::load()['tasks'] === [], 'unauthorized profile gets no menu or board data');
try { taskPage([]); verify(false, 'direct access denied'); } catch (RuntimeException $e) { verify($e->getMessage() === 'read denied', 'direct page requires native read rights'); }
taskFixture();
Session::$central = false;
try { taskPage([]); verify(false, 'central access denied'); } catch (RuntimeException $e) { verify($e->getMessage() === 'central denied', 'self-service interface denied by native central check'); }
echo "\n$tests Tasks tests PASSED (isolated GLPI doubles).\n";
