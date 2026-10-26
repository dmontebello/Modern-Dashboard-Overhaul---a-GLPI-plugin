<?php
declare(strict_types=1);
require_once __DIR__ . '/task_bootstrap.php';
require_once __DIR__ . '/../src/TaskStageService.php';
require_once __DIR__ . '/../src/TaskActivity.php';
require_once __DIR__ . '/../src/TaskAttachment.php';

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskActivity;
use GlpiPlugin\Mdoverhaul\TaskAttachment;
use GlpiPlugin\Mdoverhaul\TaskSchema;

final class ActivityTestDB
{
    public function __construct(private TaskFakeDB $base) {}
    public function __call(string $method, array $args) { return $this->base->$method(...$args); }
    public function insert(string $table, array $values): bool
    {
        $id=count(CommonDBTM::$storage[$table]??[])+1;
        CommonDBTM::$storage[$table][$id]=['id'=>$id]+$values;
        return true;
    }
    public function request(array $query): array
    {
        $table=$query['FROM'];
        if (!in_array($table,[TaskSchema::NOTES,TaskSchema::FILES],true)) {
            return $this->base->request($query);
        }
        $rows=array_filter(CommonDBTM::$storage[$table]??[],
            static function(array $row) use ($query): bool {
                foreach ($query['WHERE']??[] as $key=>$value) {
                    if (($row[$key]??null)!=$value) return false;
                }
                return true;
            });
        krsort($rows);
        return array_slice(array_values($rows),(int)($query['START']??0),(int)($query['LIMIT']??1000));
    }
}
$n=0;
function t(bool $ok, string $label): void
{
    global $n;
    $n++;
    if (!$ok) throw new RuntimeException('FAILED '.$label);
    echo "[OK] $n $label\n";
}
taskFixture();
$flags=array_fill_keys(array_keys(Settings::defaults()),'0');
$flags['task_workspace']=$flags['tasks_standalone']='1';
Settings::save($flags);
Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE;
$task=new StandaloneTask();
$id=$task->add(['name'=>'Journal test','entities_id'=>1]);
t($id===1,'standalone created');
global $DB;
$DB=new ActivityTestDB($DB);
t(TaskActivity::canWrite('standalone',1),'owner can write journal');
for($i=1;$i<=25;$i++) t(TaskActivity::add('standalone',1,'Progress note '.$i),'append note '.$i);
$a=TaskActivity::page('standalone',1);
t(count($a['items'])===20 && $a['more'],'first journal page has twenty entries');
t(str_contains($a['items'][0]['content'],'25'),'latest entry first');
$b=TaskActivity::page('standalone',1,20);
t(count($b['items'])===5 && !$b['more'],'all older journal entries remain accessible');
t($b['items'][4]['content']==='Progress note 1','oldest entry preserved');
t(!TaskActivity::add('standalone',1,''),'blank note refused');
Session::$user=2;
t(!TaskActivity::canRead('standalone',1),'another READMY user cannot see journal');
t(TaskActivity::page('standalone',1)['items']===[],'unauthorised journal page empty');
t(!TaskActivity::add('standalone',1,'denied'),'unauthorised append blocked');
Session::$user=1;
Session::$standaloneRights=StandaloneTask::READMY;
t(TaskActivity::canRead('standalone',1) && !TaskActivity::canWrite('standalone',1),'read-only user cannot append');
t(!TaskAttachment::add('standalone',1,[]),'missing upload denied');
Session::$standaloneRights=StandaloneTask::READMY|UPDATE|CREATE;
$DB->insert(TaskSchema::FILES,['source'=>'standalone','items_id'=>1,'users_id'=>1,'name'=>'evidence.pdf',
    'stored'=>str_repeat('a',64),'mime'=>'application/pdf','filesize'=>100,'date_creation'=>'2026-10-25 10:00:00','is_deleted'=>0]);
t(count(TaskAttachment::listing('standalone',1))===1,'authorised file metadata visible');
Session::$user=2;
t(TaskAttachment::get(1)===null && TaskAttachment::listing('standalone',1)===[],'attachment parent ACL enforced');
Session::$user=1;
Session::$standaloneRights=StandaloneTask::READMY|CREATE|UPDATE;
$task->getFromDB(1);
$version=(string)($task->fields['date_mod']??'');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('standalone',1,'doing','stale')['status']===409,
    'stale Kanban drag rejected');
$result=\GlpiPlugin\Mdoverhaul\TaskStageService::move('standalone',1,'doing',$version);
t($result['ok'] && $result['stage']==='doing','independent stage persisted');
$movedProject=\GlpiPlugin\Mdoverhaul\TaskStageService::move('project',1,'done','');
t($movedProject['ok'] && $movedProject['stage']==='done','native ProjectTask stage updated');
t(ProjectTask::$rows[1]['percent_done']===100 && ProjectTask::$rows[1]['projectstates_id']===2,
    'project Stage does not override GLPI status');
ProjectTask::$rows[1]['auto_percent_done']=1;
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('project',1,'todo','')['status']===409,
    'automatic ProjectTask percent cannot be overridden');
$native=\GlpiPlugin\Mdoverhaul\TaskStageService::move('project',1,'state-4','');
t($native['ok'] && $native['stage']==='state-4','native ProjectState stage moved');
t((int)ProjectTask::$rows[1]['projectstates_id']===4 && (int)ProjectTask::$rows[1]['auto_projectstates']===0,
  'native state saved without automatic overwrite');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('project',1,'state-999','')['error']==='unknown_state',
  'unknown native state denied');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('standalone',1,'state-4','')['error']==='invalid',
  'independent task cannot accept native ProjectState ID');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::stage(['source'=>'project','state_id'=>2,'percent_done'=>0], true)==='doing', 'All maps GLPI native Processing to Doing even with 0 percent');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('project',1,'doing','', 'all')['ok'], 'All moves ProjectTask via mapped native status');
t(ProjectTask::$rows[1]['projectstates_id']===2, 'All retains configured native ProjectState');
// Incomplete GLPI mapping must never rewrite ProjectTask.percent_done.
Config::$values['core'] = [];
ProjectTask::$rows[1]['auto_percent_done'] = 0;
$beforeUnmapped = ProjectTask::$rows[1];
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('project', 1, 'done', '', 'all')['error'] === 'stage_mapping_unavailable',
  'unmapped Project shared stage rejected');
t(ProjectTask::$rows[1] === $beforeUnmapped, 'unmapped shared stage leaves ProjectTask unchanged');
t(\GlpiPlugin\Mdoverhaul\TaskStageService::move('project', 1, 'state-3', '', 'all')['ok'],
  'unmapped mixed board still permits native ProjectState update');
t(ProjectTask::$rows[1]['percent_done'] === $beforeUnmapped['percent_done'],
  'native state update retains ProjectTask percent_done');
echo "$n task activity tests PASSED (isolated doubles, NON COLLAUDATO LIVE)\n";
