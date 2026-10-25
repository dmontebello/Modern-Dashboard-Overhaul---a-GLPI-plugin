<?php
declare(strict_types=1);
require_once __DIR__ . '/task_bootstrap.php';
require_once __DIR__ . '/../src/TaskCreationService.php';

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskCreationService;

$tests=0;
function check7(bool $ok, string $label): void {
    global $tests;
    $tests++;
    if (!$ok) { throw new RuntimeException("FAILED $label"); }
    echo "[OK] $tests $label\n";
}
function page7(array $get=[], array $post=[]): string {
    $_GET=$get;
    $_POST=$post;
    $_SERVER['REQUEST_METHOD']=$post===[]?'GET':'POST';
    if($post!==[]) Session::checkCSRF($post);
    ob_start();
    try { include __DIR__.'/../front/task.form.php'; return (string)ob_get_clean(); }
    catch(Throwable $err) { ob_end_clean(); throw $err; }
}
taskFixture();
$flags=array_fill_keys(array_keys(Settings::defaults()),'0');
$flags['task_workspace']=$flags['tasks_standalone']='1';
Settings::save($flags);
Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE;
$html=page7();
check7(str_contains($html,'name="projects_id"') && str_contains($html,'name="name"')
    && str_contains($html,'name="content"') && str_contains($html,'name="due_date"'),
    'single form offers shared fields and optional native project');
check7(str_contains($html,'name="entities_id"') && str_contains($html,'name="users_id_assign"'),
    'standalone-only fields remain separate');
check7(!str_contains($html,'Tipo di attività'), 'no preliminary type selection');
$values=['name'=>'Task comune','content'=>'Aggiornamento','due_date'=>'2026-10-25T12:30'];
$result=TaskCreationService::create($values+['projects_id'=>'1']);
check7($result['ok'] && $result['source']==='project','native ProjectTask created with project');
check7(ProjectTask::$rows[$result['id']]['plan_end_date']==='2026-10-25 12:30:00',
    'shared due date mapped to native ProjectTask field');
check7(!isset(ProjectTask::$rows[$result['id']]['users_id_assign']),
    'standalone assignee not written into native ProjectTask');
check7(TaskCreationService::create($values+['projects_id'=>'2'])['ok']===false,
    'cross-entity project rejected');
check7(TaskCreationService::create($values+['projects_id'=>'99999'])['ok']===false,
    'non-existent project rejected');
check7(TaskCreationService::create($values+['projects_id'=>'-1'])['ok']===false,
    'negative id rejected instead of falling back to independent');
check7(TaskCreationService::create($values+['projects_id'=>['1']])['ok']===false,
    'array-shaped project denied');
$stand=TaskCreationService::create($values+['projects_id'=>'','entities_id'=>'1','status'=>'2']);
check7($stand['ok'] && $stand['source']==='standalone','blank project creates independent task');
$stored=CommonDBTM::$storage[StandaloneTask::getTable()][$stand['id']];
check7($stored['due_date']==='2026-10-25 12:30:00' && $stored['status']===2
    && !isset($stored['projects_id']), 'standalone uses own schema, not fake project');
check7(TaskCreationService::create($values+['projects_id'=>'0'])['source']==='standalone',
    'numeric zero project creates independent task');
Session::$standaloneRights=0;
$previousIndependentCount = count(CommonDBTM::$storage[StandaloneTask::getTable()] ?? []);
check7(TaskCreationService::create($values+['projects_id'=>0])['error']==='forbidden',
    'no standalone CREATE right denies independent creation');
check7(count(CommonDBTM::$storage[StandaloneTask::getTable()] ?? []) === $previousIndependentCount,
    'unauthorised standalone attempt writes no independent row');
check7(TaskCreationService::create($values+['projects_id'=>1])['ok'],
    'project creation still works without standalone grants');
Session::$create=false;
check7(TaskCreationService::create($values+['projects_id'=>1])['error']==='forbidden',
    'native ProjectTask permissions cannot be bypassed');
Session::$create=true;
Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE;
foreach(['','2026-02-30T10:30','2026-10-25T99:01'] as $bad) {
    $badValues=$values; $badValues['name']=$bad;
    if($bad===''){check7(!TaskCreationService::create($badValues)['ok'],'blank name rejected');break;}
}
check7(TaskCreationService::create($values+['extra'=>'ignored','projects_id'=>0])['ok'],
    'unknown crafted fields ignored by allowlist');
$badDue=$values; $badDue['due_date']='2026-02-30T12:00';
check7(TaskCreationService::create($badDue+['projects_id'=>1])['error']==='invalid',
    'calendar-invalid due date refused');
$huge=$values; $huge['content']=str_repeat('a',100001);
check7(TaskCreationService::create($huge)['error']==='invalid','oversized description rejected');
try { page7([],['create'=>'1','name'=>'X']); check7(false,'missing CSRF'); }
catch(RuntimeException $e) { check7(str_contains($e->getMessage(),'CSRF'),'CSRF middleware rejects missing token'); }
$token=Session::getNewCSRFToken();
page7([],['_glpi_csrf_token'=>$token,'create'=>'1','name'=>'Browser project','projects_id'=>'1']);
check7(str_contains((string)Html::$redirect,'projecttask.form.php?id='),'POST project redirects to native GLPI form');
$token=Session::getNewCSRFToken();
page7([],['_glpi_csrf_token'=>$token,'create'=>'1','name'=>'Browser independent','projects_id'=>'0']);
check7(str_contains((string)Html::$redirect,'standalonetask.form.php?id='),'POST independent redirects to own form');
$previous=Html::$redirect;
$token=Session::getNewCSRFToken();
$error=page7([],['_glpi_csrf_token'=>$token,'create'=>'1','name'=>'Must fail','projects_id'=>'2']);
check7(str_contains($error,'role="alert"') && str_contains($error,'value="Must fail"')
    && Html::$redirect===$previous,'invalid selection retains typed values and does not redirect');
taskFixture();
check7(str_contains(taskPage(['new'=>'1']),'') && Html::$redirect==='/glpi/plugins/mdoverhaul/front/task.form.php',
    'old generic new-task entry forwards to unified form');
echo "$tests unified creation tests PASSED (isolated doubles, NON COLLAUDATO LIVE)\n";
