<?php
/** Actual controllers, persistent state supplied only by the isolated test server. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { return; }
require_once __DIR__ . '/task_bootstrap.php';
taskFixture();
$options=json_decode($argv[1]??'{}',true,flags:JSON_THROW_ON_ERROR);
$flags=array_fill_keys(array_keys(\GlpiPlugin\Mdoverhaul\Settings::defaults()),'0');
$flags['task_workspace']='1'; $flags['tasks_standalone']=empty($options['disabled'])?'1':'0';
\GlpiPlugin\Mdoverhaul\Settings::save($flags);
Session::$standaloneRights=(int)($options['rights']??(1024|CREATE|UPDATE|DELETE));
Session::$projectRead=Session::$taskRead=Session::$create=false;
Session::$user=(int)($options['user']??1);
Session::$entities=$options['entities']??[1];
$rows=$options['rows']??null;
if ($rows===null) {
    $rows=[];
    for($id=1;$id<=(int)($options['count']??100);$id++) {
        $rows[$id]=['id'=>$id,'name'=>$id===1?str_repeat('TitoloLungo',12):'Independent '.$id,'content'=>'Descrizione indipendente',
            'entities_id'=>1,'users_id_owner'=>1,'users_id_assign'=>0,'groups_id_assign'=>0,'status'=>($id%3)+1,
            'due_date'=>$id%2?'2000-01-01 12:00:00':null,'is_deleted'=>0];
    }
}
CommonDBTM::$storage[\GlpiPlugin\Mdoverhaul\TaskSchema::TABLE]=$rows;
$_GET=$options['query']??[]; $_POST=$options['post']??[];
$_SERVER['REQUEST_METHOD']=$_POST===[]?'GET':'POST';
$status=200;
ob_start();
try {
    if ($_POST!==[]) { Session::getNewCSRFToken(); Session::checkCSRF($_POST); }
    if (($options['mode']??'workspace')==='form') { include __DIR__.'/../front/standalonetask.form.php'; }
    else { echo taskPage(['source'=>'standalone']+$_GET); }
} catch (RuntimeException) { $status=403; }
$body=(string)ob_get_clean();
if ($body==='NOT_FOUND') { $status=404; }
echo json_encode(['body'=>$body,'status'=>$status,'redirect'=>Html::$redirect,
    'rows'=>CommonDBTM::$storage[\GlpiPlugin\Mdoverhaul\TaskSchema::TABLE],
    'projects'=>count(Project::$rows),'projecttasks'=>count(ProjectTask::$rows)],JSON_THROW_ON_ERROR);
