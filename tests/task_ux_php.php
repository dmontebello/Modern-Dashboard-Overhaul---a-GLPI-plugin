<?php
declare(strict_types=1);
require_once __DIR__ . '/task_bootstrap.php';
require_once __DIR__ . '/../src/TaskStageService.php';
use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\StandaloneTask;

$tests = 0;
function uxCheck(bool $condition, string $name): void {
    global $tests;
    $tests++;
    if (!$condition) throw new RuntimeException('FAILED ' . $name);
    echo "[OK] $tests $name\n";
}
function deleteRequest(string $source, int $id, bool $confirm=true): array {
    $post=['source'=>$source,'id'=>(string)$id,'confirm_delete'=>$confirm?'1':'0',
        '_glpi_csrf_token'=>Session::getNewCSRFToken()];
    Session::checkCSRF($post);
    $_POST=$post; $_SERVER['REQUEST_METHOD']='POST';
    $_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
    ob_start();
    include __DIR__.'/../front/task-delete.php';
    $body=(string)ob_get_clean();
    return [http_response_code(),json_decode($body,true,flags:JSON_THROW_ON_ERROR)];
}
taskFixture();
$flags=array_fill_keys(array_keys(Settings::defaults()),'0');
$flags['task_workspace']=$flags['tasks_standalone']='1'; Settings::save($flags);
Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE|DELETE;
$task=new StandaloneTask();
$id=$task->add(['name'=>'Deletable','entities_id'=>1]);
uxCheck(is_int($id) && $id>0,'test standalone persisted');
$html=taskPage(['source'=>'all','view'=>'list']);
uxCheck(str_contains($html, 'mdo-task-delete-form') && str_contains($html, 'confirm_delete'),
    'combined list renders permission-gated trash action');
[$status,$result]=deleteRequest('standalone',$id);
uxCheck($status===200 && $result['ok'] && !empty($result['token']),'deletion returns renewed token');
uxCheck(!empty(CommonDBTM::$storage[StandaloneTask::getTable()][$id]['is_deleted']),
    'standalone soft-deleted, no purge');
[$status,$result]=deleteRequest('standalone',$id);
uxCheck($status===404 && !$result['ok'],'already deleted row rejected');
$second=$task->add(['name'=>'Still visible','entities_id'=>1]);
Session::$standaloneRights=StandaloneTask::READMY|CREATE;
[$status,$result]=deleteRequest('standalone',$second);
uxCheck($status===403 && !$result['ok'],'read-only actor cannot delete');
uxCheck(empty(CommonDBTM::$storage[StandaloneTask::getTable()][$second]['is_deleted']),'denied DELETE cannot mutate');
Session::$standaloneRights=StandaloneTask::READMY|CREATE|DELETE;
[$status,$result]=deleteRequest('standalone',$second,false);
uxCheck($status===400 && !$result['ok'],'unconfirmed action rejected');
[$status,$result]=deleteRequest('project',1);
uxCheck($status===200 && $result['ok'] && !empty(ProjectTask::$rows[1]['is_deleted']),
    'native project task soft-delete only');
Session::$entities=[2];
[$status,$result]=deleteRequest('project',4);
uxCheck($status===404 && !$result['ok'],'cross-entity ProjectTask deletion refused');
echo "$tests task UX delete tests PASSED (GLPI doubles, NOT LIVE)\n";
