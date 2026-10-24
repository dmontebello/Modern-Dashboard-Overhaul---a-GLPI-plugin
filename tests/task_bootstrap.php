<?php
/** Isolated doubles for Tasks tests. These are not a live GLPI ACL implementation. */
declare(strict_types=1);

const READ = 1;
const UPDATE = 2;
const CREATE = 4;
const DELETE = 8;
const ERROR = 3;

class CommonGLPI {}
require_once __DIR__ . '/common_db_double.php';
final class Config
{
    public static array $values = [];
    public static function getConfigurationValues(string $context): array { return self::$values[$context] ?? []; }
    public static function setConfigurationValues(string $context, array $values): void { self::$values[$context] = array_replace(self::$values[$context] ?? [], $values); }
}
final class Session
{
    public static int $user = 1;
    public static array $entities = [1];
    public static bool $projectRead = true;
    public static bool $projectAll = true;
    public static bool $taskRead = true;
    public static bool $create = true;
    public static bool $central = true;
    public static bool $config = true;
    public static int $standaloneRights = 0;
    public static array $tokens = [];
    public static string $language = 'it_IT';
    public static ?string $now = null;
    public static function getLanguage(): string { return self::$language; }
    public static function getCurrentTime(): ?string { return self::$now; }
    public static function checkCentralAccess(): void { if (!self::$central) { throw new RuntimeException('central denied'); } }
    public static function checkRight(string $key, int $right): void { if ($key === 'config' && self::$config) { return; } throw new RuntimeException('read denied'); }
    public static function getNewCSRFToken(): string { self::$tokens['isolated-test-token']=true; return 'isolated-test-token'; }
    public static function getLoginUserID(): int { return self::$user; }
    public static function haveRight($name,$right): bool { return $name==='plugin_mdoverhaul_standalonetask' && (self::$standaloneRights & $right)===$right; }
    public static function haveRightsOr($name,array $rights): bool { foreach($rights as $right) { if(self::haveRight($name,$right)) { return true; } } return false; }
    public static function addMessageAfterRedirect(...$args): void {}
    public static function checkCSRF(array $input): void
    {
        $token=$input['_glpi_csrf_token']??'';
        if (!is_string($token) || !isset(self::$tokens[$token])) { throw new RuntimeException('CSRF denied by middleware double'); }
        unset(self::$tokens[$token]);
    }
    public static function haveAccessToEntity(int $id, bool $recursive = false): bool { return in_array($id, self::$entities, true); }
}
final class Project
{
    public static array $rows = [];
    public array $fields = [];
    private array $team = [];
    public static function canView(): bool { return Session::$projectRead; }
    public static function getFormURL(bool $full = true): string { return ($full ? '/glpi' : '') . '/front/project.form.php'; }
    public static function getSearchURL(bool $full = true): string { return ($full ? '/glpi' : '') . '/front/project.php'; }
    public function getFromDB(int $id): bool { $this->fields = self::$rows[$id] ?? []; $this->post_getFromDB(); return $this->fields !== []; }
    public function getFromResultSet(array $row): void { $this->fields = $row; }
    public function post_getFromDB(): void { $this->team = $this->fields['team_users'] ?? []; }
    public function getEntityID(): int { return (int) $this->fields['entities_id']; }
    public function isRecursive(): bool { return !empty($this->fields['is_recursive']); }
    public function can(int $id, int $right): bool
    {
        if ($this->fields === [] && !$this->getFromDB($id)) { return false; }
        if ($right === UPDATE) {
            return Session::$create && Session::haveAccessToEntity($this->getEntityID())
                && !empty($this->fields['writable']) && empty($this->fields['hook_denied']);
        }
        $visible = self::canView() && Session::haveAccessToEntity($this->getEntityID())
            && (Session::$projectAll || ($this->fields['users_id'] ?? 0) === Session::$user || in_array(Session::$user, $this->team, true))
            && empty($this->fields['hook_denied']);
        return $visible && ($right === READ || (Session::$create && !empty($this->fields['writable'])));
    }
}
final class ProjectTask
{
    public const READMY = 1;
    public static array $rows = [];
    public array $fields = [];
    private array $team = [];
    public function getFromResultSet(array $row): void { $this->fields = $row; }
    public function post_getFromDB(): void { $this->team = $this->fields['team_users'] ?? []; }
    public static function canView(): bool { return Session::$projectRead || Session::$taskRead; }
    public static function canCreate(): bool { return Session::$create; }
    public static function getFormURL(bool $full = true): string { return ($full ? '/glpi' : '') . '/front/projecttask.form.php'; }
    public function can(int $id, int $right, ?array &$input = null): bool
    {
        if ($id < 0) {
            $project = new Project();
            return self::canCreate() && $project->getFromDB((int) ($input['projects_id'] ?? 0))
                && $project->can((int) $input['projects_id'], UPDATE);
        }
        if ($this->fields === []) { $this->fields = self::$rows[$id] ?? []; }
        $project = new Project();
        return self::canView() && $this->fields !== []
            && Session::haveAccessToEntity((int) $this->fields['entities_id'])
            && empty($this->fields['hook_denied'])
            && $project->getFromDB((int) $this->fields['projects_id'])
            && ($project->can((int) $this->fields['projects_id'], READ)
                || (Session::$taskRead && (($this->fields['users_id'] ?? 0) === Session::$user || in_array(Session::$user, $this->team, true))));
    }
}
final class Dropdown
{
    public static array $tables = [];
    public static function getDropdownName(string $table, int $id): string
    {
        self::$tables[] = $table;
        if ($table !== 'glpi_projectstates') { throw new RuntimeException('Invalid native state table'); }
        return [1 => 'Attivo', 2 => 'Bloccato', 3 => 'In Review'][$id] ?? '';
    }
    public static function show(string $type, array $options = []): bool
    {
        if ($type !== Project::class) { return false; }
        echo '<select name="project" aria-label="Seleziona il progetto per creare la task"><option value="0">—</option>';
        foreach (Project::$rows as $id => $row) {
            $project = new Project();
            if ($project->getFromDB((int) $id) && $project->can((int) $id, READ)
                && empty($row['is_deleted']) && empty($row['is_template'])) {
                echo '<option value="' . (int) $id . '">' . htmlspecialchars($row['name'], ENT_QUOTES) . '</option>';
            }
        }
        echo '</select>';
        return true;
    }
    public static function showFromArray(string $name, array $elements, array $options): void
    {
        echo '<select name="' . htmlspecialchars($name) . '" aria-label="' . htmlspecialchars($options['aria_label']) . '"><option value="0">—</option>';
        foreach ($elements as $id => $label) { echo '<option value="' . (int) $id . '">' . htmlspecialchars($label, ENT_QUOTES) . '</option>'; }
        echo '</select>';
    }
}
final class Plugin
{
    public static bool $active = true;
    public static function isPluginActive(string $name): bool { return self::$active; }
}
final class Html
{
    public static ?string $redirect = null;
    public static function displayNotFoundError(): void { echo 'NOT_FOUND'; }
    public static function header(...$args): void {}
    public static function footer(): void {}
    public static function redirect(string $url): void { self::$redirect = $url; }
    public static function convDateTime(string $date): string { return $date; }
}
final class Toolbox
{
    public static array $errors = [];
    public static function logInFile(string $file, string $text): void { self::$errors[] = $text; }
}
final class Search
{
    public const HTML_OUTPUT = 0;
    public static array $calls = [];
    public static function manageParams(string $type, array $params): array { return $params; }
    public static function showGenericSearch(string $type, array $params): void { self::$calls['form'] = [$type, $params]; }
    public static function showList(string $type, array $params): void { self::$calls['list'] = [$type, $params]; }
}
function getEntitiesRestrictCriteria(string $table, ...$args): array { return [$table . '.entities_id' => Session::$entities]; }
final class TaskFakeDB
{
    public array $queries = [];
    public bool $fail = false;
    public bool $hasTaskTable = true;
    public array $schemaFields = [];
    public array $schemaKeys = [];
    public array $profileRights = [];
    public int $creates = 0;
    public int $alterations = 0;
    public function tableExists($table): bool { return $this->hasTaskTable; }
    public function fieldExists($table,$field): bool { return isset($this->schemaFields[$field]); }
    public function doQuery($sql): bool
    {
        if (!str_starts_with($sql,'CREATE TABLE `glpi_plugin_mdoverhaul_tasks`')) { throw new RuntimeException('Unexpected schema SQL'); }
        $this->hasTaskTable=true; $this->creates++;
        preg_match_all('/`([a-z_]+)` (?:int|varchar|mediumtext|timestamp|tinyint)/',$sql,$matches);
        $this->schemaFields=array_fill_keys($matches[1],true);
        return true;
    }
    public function request(array $query): array
    {
        $this->queries[] = $query;
        if ($this->fail) { throw new RuntimeException('private DB failure'); }
        $table = $query['FROM'];
        $rows = match($table) { 'glpi_projecttasks'=>ProjectTask::$rows,'glpi_projects'=>Project::$rows,default=>CommonDBTM::$storage[$table]??[] };
        $rows = array_filter($rows, static function (array $row) use ($query, $table): bool {
            return self::matches($row,$query['WHERE'],$table);
        });
        krsort($rows);
        return array_slice(array_values($rows), 0, $query['LIMIT']);
    }
    public static function matches(array $row,array $where,string $table): bool
    {
            foreach ($where as $field => $value) {
                if (is_int($field)) { if (!self::matches($row,$value,$table)) { return false; } continue; }
                if ($field==='AND') { foreach($value as $condition) { if(!self::matches($row,$condition,$table)) { return false; } } continue; }
                if ($field==='OR') {
                    $one=false; foreach($value as $key=>$condition) { if(self::matches($row,is_int($key)?$condition:[$key=>$condition],$table)) { $one=true; } }
                    if (!$one) { return false; } continue;
                }
                $key = substr($field, strlen($table) + 1);
                if (is_array($value) ? !in_array($row[$key] ?? 0, $value, true) : ($row[$key] ?? 0) != $value) { return false; }
            }
            return true;
    }
}
require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/BuildInfo.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskMenu.php';

function taskFixture(): void
{
    global $DB, $CFG_GLPI;
    $DB = new TaskFakeDB();
    $DB->schemaFields=array_fill_keys(['id','entities_id','name','content','users_id_owner','users_id_assign','groups_id_assign','status','due_date','date_creation','date_mod','is_deleted'],true);
    (new ReflectionProperty(\GlpiPlugin\Mdoverhaul\TaskSchema::class,'ready'))->setValue(null,null);
    Config::setConfigurationValues(\GlpiPlugin\Mdoverhaul\Settings::CONTEXT,['task_schema_version'=>'1']);
    CommonDBTM::$storage=[];
    $CFG_GLPI = ['root_doc' => '/glpi'];
    Session::$user = 1;
    Session::$standaloneRights=0;
    Session::$tokens=[];
    $_SESSION['glpigroups']=[];
    $_SESSION['glpiactive_entity']=1;
    $_SESSION['glpiactiveentities']=[1];
    Session::$entities = [1];
    Session::$projectRead = Session::$projectAll = Session::$taskRead = Session::$create = Session::$central = true;
    Session::$language = 'it_IT';
    Session::$now = null;
    Plugin::$active = true;
    Html::$redirect = null;
    Search::$calls = [];
    Toolbox::$errors = [];
    Project::$rows = [
        1 => ['id' => 1, 'name' => 'Progetto consentito', 'entities_id' => 1, 'users_id' => 1, 'writable' => true],
        2 => ['id' => 2, 'name' => 'SEGRETO altra entità', 'entities_id' => 2, 'users_id' => 2, 'writable' => true],
        3 => ['id' => 3, 'name' => 'SEGRETO altro manager', 'entities_id' => 1, 'users_id' => 2, 'writable' => true],
    ];
    ProjectTask::$rows = [];
    foreach ([1 => [1, 1, 1], 2 => [2, 2, 2], 3 => [3, 1, 2], 4 => [3, 1, 1]] as $id => [$project, $entity, $user]) {
        ProjectTask::$rows[$id] = ['id' => $id, 'name' => 'Attività ' . $id, 'projects_id' => $project,
            'entities_id' => $entity, 'users_id' => $user, 'projectstates_id' => 2, 'percent_done' => 30,
            'plan_end_date' => '2000-01-01 12:00:00', 'real_end_date' => '', 'content' => '<b>Descrizione</b>'];
    }
    $flags = array_fill_keys(array_keys(\GlpiPlugin\Mdoverhaul\Settings::defaults()), '0');
    $flags['task_workspace'] = '1';
    \GlpiPlugin\Mdoverhaul\Settings::save($flags);
}
function taskPage(array $get): string
{
    global $DB, $CFG_GLPI;
    $_GET = $get;
    ob_start();
    try { include __DIR__ . '/../front/tasks.php'; return (string) ob_get_clean(); }
    catch (Throwable $e) { ob_end_clean(); throw $e; }
}
