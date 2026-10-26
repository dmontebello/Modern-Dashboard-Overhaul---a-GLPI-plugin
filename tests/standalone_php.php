<?php
declare(strict_types=1);
require_once __DIR__ . '/task_bootstrap.php';
require_once __DIR__ . '/../hook.php';

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskSchema;
use GlpiPlugin\Mdoverhaul\TaskMenu;

$tests=0;
function expect(bool $value,string $message): void
{
    global $tests; $tests++;
    if (!$value) { throw new RuntimeException('FAILED '.$message); }
    echo "[OK] $tests $message\n";
}
set_error_handler(static function(int $level,string $message): never { throw new ErrorException($message,0,$level); });
function standaloneFixture(): void
{
    taskFixture();
    $flags=array_fill_keys(array_keys(Settings::defaults()),'0');
    $flags['task_workspace']=$flags['tasks_standalone']='1';
    Settings::save($flags);
    Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE|DELETE;
}
function formRequest(array $get=[],array $post=[]): string
{
    global $DB,$CFG_GLPI;
    $_GET=$get; $_POST=$post;
    $_SERVER['REQUEST_METHOD']=$post===[]?'GET':'POST';
    // Simulates GLPI's CheckCsrfListener BEFORE the plugin controller.
    if ($post!==[]) { Session::checkCSRF($post); }
    ob_start();
    try { include __DIR__.'/../front/standalonetask.form.php'; return (string)ob_get_clean(); }
    catch(Throwable $error) { ob_end_clean(); throw $error; }
}
function token(): array { return ['_glpi_csrf_token'=>Session::getNewCSRFToken()]; }

standaloneFixture();
$projectBefore=Project::$rows; $projectTasksBefore=ProjectTask::$rows;
$item=new StandaloneTask();
$id=$item->add(['name'=>'Independent alpha','content'=>'<script>hostile()</script>','entities_id'=>1,'status'=>'2','due_date'=>'2026-10-25T09:30','users_id_owner'=>99,'projects_id'=>123]);
expect($id===1,'independent task created without project');
expect($item->fields['users_id_owner']===1 && !isset($item->fields['projects_id']),'owner fixed to authenticated actor; project field never persisted');
expect($item->fields['due_date']==='2026-10-25 09:30:00','native datetime-local normalised and validated');
expect(Project::$rows===$projectBefore && ProjectTask::$rows===$projectTasksBefore,'no Project or ProjectTask insert or mutation');
expect($item->can(1,READ) && $item->can(1,UPDATE),'owner READMY plus UPDATE sees and edits own task');
Session::$user=2;
expect(!$item->can(1,READ) && !$item->can(1,UPDATE) && !$item->can(1,DELETE),'another actor READMY cannot read/write/delete');
Session::$standaloneRights=READ|UPDATE;
expect($item->can(1,READ) && $item->can(1,UPDATE),'READALL plus UPDATE works within active entity');
Session::$entities=[2];
expect(!$item->can(1,READ),'READALL never bypasses entity isolation');
Session::$entities=[1]; Session::$user=1; Session::$standaloneRights=StandaloneTask::READMY|UPDATE|CREATE|DELETE;
expect(!$item->add(['name'=>'Cross entity','entities_id'=>2]),'cross-entity creation refused');
expect(!$item->add(['name'=>'','entities_id'=>1]),'empty title refused');
expect(!$item->add(['name'=>'Invalid state','entities_id'=>1,'status'=>999]),'unknown status refused');
expect(!$item->add(['name'=>'Invalid due','entities_id'=>1,'due_date'=>'2026-02-30 12:00:00']),'invalid calendar date refused');
expect(!$item->add(['name'=>['array'],'entities_id'=>1]),'array input refused without warning');
expect(!$item->add(['name'=>'Cross assignee','entities_id'=>1,'users_id_assign'=>3]),'assignee outside task entity refused');
expect(!$item->add(['name'=>'Cross group','entities_id'=>1,'groups_id_assign'=>6]),'group outside task entity refused');
expect($item->update(['id'=>1,'name'=>'Updated','users_id_assign'=>2,'groups_id_assign'=>5,'entities_id'=>2,'users_id_owner'=>99]),'authorised update with native actor references');
expect($item->fields['entities_id']===1 && $item->fields['users_id_owner']===1,'update cannot transfer entity or ownership');
User::$rows[2]['denied']=true;
expect($item->update(['id'=>1,'name'=>'Updated with existing actor']),'unchanged assignee does not impose a new unrelated user READ requirement');
expect(!$item->update(['id'=>1,'users_id_assign'=>3]),'new cross-entity assignee still rejected');
unset(User::$rows[2]['denied']);
Session::$user=2; Session::$standaloneRights=StandaloneTask::READMY;
expect($item->can(1,READ) && !$item->can(1,UPDATE),'assigned reader sees task but cannot write without UPDATE');
Session::$user=4; $_SESSION['glpigroups']=[5];
expect($item->can(1,READ),'assigned group member has READMY visibility');
$_SESSION['glpigroups']=[];
expect(!$item->can(1,READ),'removing group membership removes visibility');
$options=$item->rawSearchOptions();
ob_start();
$statusSelect=StandaloneTask::getSpecificValueToSelect('status', 'mdo_status_filter', 2);
$unexpectedOutput=(string) ob_get_clean();
expect($unexpectedOutput==='' && is_string($statusSelect)
    && str_contains($statusSelect, 'name="mdo_status_filter"')
    && str_contains($statusSelect, 'value="2" selected'),
    'native Search status selector returns HTML without echo and preserves selected value');
expect(StandaloneTask::getSpecificValueToSelect('other', 'unused')==='',
    'unsupported native specific field returns empty markup');
expect($options[1]['itemtype']===StandaloneTask::class && StandaloneTask::getTable()===TaskSchema::TABLE,'native Search explicitly identifies the custom-table itemtype');
$criteria=plugin_mdoverhaul_addDefaultWhere(StandaloneTask::class);
expect($criteria===StandaloneTask::getVisibilityCriteria()['WHERE'] && plugin_mdoverhaul_addDefaultWhere(ProjectTask::class)===[],'public native Search hook applies own ACL only to standalone type');
$DB->queries=[];
expect(StandaloneTask::loadBoard()['tasks']===[],'board counts and rows exclude unauthorised actors');
expect(($DB->queries[0]['ORDER'] ?? null) === [TaskSchema::TABLE . '.id DESC'],
    'standalone board uses valid GLPI ORDER expression rather than associative map');
Session::$user=2;
expect(count(StandaloneTask::loadBoard()['tasks'])===1,'assigned actor board uses same visibility criteria');
expect($DB->creates===0,'workspace and CRUD never run install/upgrade DDL');
Session::$projectRead=Session::$taskRead=false;
expect(TaskMenu::canView(),'standalone-only profile gets Assistance Tasks without ProjectTask rights');
taskPage(['source'=>'standalone','new'=>'1','kind'=>'project','project'=>'1']);
expect(Html::$redirect==='/glpi/front/projecttask.form.php?projects_id=1','project creation from independent subview keeps selected ID separate from board project filter');
Html::$redirect=null;
$html=taskPage(['source'=>'standalone','view'=>'board']);
expect(str_contains($html,'data-task-type="standalone"') && !str_contains($html,'<script>hostile()'),'standalone board type/ID and escaping');
expect(!str_contains($html,'role="progressbar"') && !str_contains($html,'Progetto non accessibile'),'no fake progress percentage or restricted-project placeholder for independent tasks');
taskPage(['source'=>'standalone','view'=>'list']);
expect(Search::$calls['list'][0]===StandaloneTask::class,'independent list uses native Search for its own itemtype');
Session::$user=1; Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE|DELETE;
$html=taskPage(['source'=>'standalone','new'=>'1']);
expect(Html::$redirect === '/glpi/plugins/mdoverhaul/front/task.form.php', 'standalone new-task entry forwards to optional-project form');
$html=formRequest(['id'=>'1']);
expect(!str_contains($html,'<script>hostile()') && str_contains($html,'name="status"'),'GLPI-integrated edit form escapes content and renders allowed fields');
try { formRequest([],['add'=>'1','name'=>'CSRF missing','entities_id'=>1]); expect(false,'CSRF'); }
catch(RuntimeException $e) { expect(str_contains($e->getMessage(),'CSRF'),'missing CSRF blocked by simulated native middleware'); }
$post=token()+['id'=>'0','add'=>'1','name'=>'Created from form','entities_id'=>'1','status'=>'1'];
formRequest([],$post);
expect(str_contains((string)Html::$redirect,'standalonetask.form.php?id=2'),'POST/create redirects to created independent form');
try { formRequest([],$post); expect(false,'replayed CSRF'); }
catch(RuntimeException $e) { expect(str_contains($e->getMessage(),'CSRF'),'form token replay blocked by simulated middleware'); }
expect(formRequest([],token()+['id'=>['hostile'],'update'=>'1'])==='NOT_FOUND','array ID refused by server');
expect(formRequest([],token()+['id'=>'0','update'=>'1'])==='NOT_FOUND','update without existing ID refused');
Session::$user=9; Session::$standaloneRights=StandaloneTask::READMY|UPDATE|DELETE;
try { formRequest([],token()+['id'=>'1','update'=>'1','name'=>'Forbidden']); expect(false,'cross-owner POST'); }
catch(RuntimeException $e) { expect($e->getMessage()==='native item right denied','cross-owner POST rejected before update'); }
Session::$user=1; Session::$standaloneRights=StandaloneTask::READMY|DELETE;
expect($item->delete(['id'=>1]),'DELETE soft-deletes without requiring UPDATE');
expect(array_column(StandaloneTask::loadBoard()['tasks'],'id')===[2],'deleted task absent from board');
expect(!$item->delete(['id'=>1],true),'purge refused even through direct model call');
expect(!$item->delete(['id'=>1,'purge'=>0],true),'crafted purge flag cannot bypass data retention');
expect($item->restore(['id'=>1]),'authorised restore uses native model');
$flags=array_map(static fn(bool $v): string=>$v?'1':'0',Settings::get());
$flags['tasks_standalone']='0'; Settings::save($flags);
expect(!StandaloneTask::canView() && !StandaloneTask::canCreate() && taskPage(['source'=>'standalone'])==='NOT_FOUND','submodule OFF denies direct page and CRUD');
expect(count(CommonDBTM::$storage[TaskSchema::TABLE])===2,'disabling preserves all independent data');
expect(plugin_mdoverhaul_uninstall() && count(CommonDBTM::$storage[TaskSchema::TABLE])===2,'uninstall retains tasks for rollback/export');

taskFixture(); $DB->hasTaskTable=false; $DB->schemaFields=[]; $DB->extraTables=[];
(new ReflectionProperty(TaskSchema::class,'ready'))->setValue(null,null);
expect(TaskSchema::install(),'fresh schema install');
$DB->profileRights[1][StandaloneTask::$rightname]=READ|CREATE;
$rowsBefore=CommonDBTM::$storage;
expect(TaskSchema::install() && $DB->creates===3 && $DB->alterations===0,'repeat install is idempotent and does not recreate table');
expect($DB->profileRights[1][StandaloneTask::$rightname]===(READ|CREATE) && $DB->profileRights[2][StandaloneTask::$rightname]===0,'existing grants retained; new profiles start denied');
expect(count($DB->schemaKeys)===7 && CommonDBTM::$storage===$rowsBefore,'indices present; migration does not change task or core data');
$DB->hasTaskTable=false; (new ReflectionProperty(TaskSchema::class,'ready'))->setValue(null,null);
$flags=array_map(static fn(bool $v): string=>$v?'1':'0',Settings::get()); $flags['tasks_standalone']='1';
try { Settings::save($flags); expect(false,'premature opt-in'); }
catch(InvalidArgumentException) { expect(true,'standalone cannot be enabled before completed upgrade'); }

standaloneFixture();
$item=new StandaloneTask();
$item->add(['name'=>'Owner scope','entities_id'=>1]);
Session::$user=0; Session::$standaloneRights=READ|CREATE|UPDATE|DELETE;
expect(!StandaloneTask::canView() && !StandaloneTask::canCreate(),'unauthenticated actor cannot use model despite supplied rights');
Session::$user=1; Plugin::$active=false;
expect(!StandaloneTask::canView() && !$item->add(['name'=>'Disabled plugin','entities_id'=>1]),'inactive plugin denies model access');
Plugin::$active=true;
Session::$entities=[1,11]; Group::$rows[5]['is_recursive']=1;
$child=$item->add(['name'=>'Subentity group','entities_id'=>11,'groups_id_assign'=>5]);
expect($child===2,'recursive group may be assigned within its native descendant entity');
Session::$user=4; Session::$standaloneRights=StandaloneTask::READMY; $_SESSION['glpigroups']=[5]; Session::$entities=[11];
expect(array_column(StandaloneTask::loadBoard()['tasks'],'id')===[2],'group READMY and active descendant entity intersect');
Session::$entities=[1];
expect(StandaloneTask::loadBoard()['tasks']===[],'group membership does not override active entity scope');
$flags=array_map(static fn(bool $v): string=>$v?'1':'0',Settings::get()); $flags['task_workspace']='0'; Settings::save($flags);
expect(!StandaloneTask::canView() && count(CommonDBTM::$storage[TaskSchema::TABLE])===2,'parent module OFF preserves child data and denies reads');
echo "\n$tests Standalone PHP tests PASSED (GLPI doubles, NON COLLAUDATO LIVE).\n";
