<?php
declare(strict_types=1);
// Project-only fixture: real GLPI integration must still be tested on staging.
require_once __DIR__.'/task_bootstrap.php';
require_once __DIR__.'/../src/TaskStageService.php';
require_once __DIR__.'/../src/TaskCreationService.php';
use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskStageService;
use GlpiPlugin\Mdoverhaul\TaskCreationService;
use GlpiPlugin\Mdoverhaul\TaskSchema;
function projectCheck(bool $yes,string $label): void {if(!$yes)throw new RuntimeException('FAIL: '.$label);echo "[OK] ".$label."\n";}
taskFixture();
projectCheck(!array_key_exists('tasks_standalone',Settings::defaults()),'no independent configuration');
projectCheck(!str_contains(taskPage(['view'=>'board']),'data-source="standalone"'),'board contains no independent tasks');
projectCheck(!str_contains(taskPage(['view'=>'board']),'data-mdo-column="todo"'),'board always uses native states');
projectCheck(str_contains(taskPage(['view'=>'board','source'=>'all','group'=>'progress']),'data-mdo-column="state-4"'),'old All progress link resolves to native Project states');
projectCheck(str_contains(taskPage(['view'=>'board']),'data-mdo-column="state-4"'),'empty native status shown');
projectCheck(!str_contains(taskPage(['view'=>'board']),'mdo-tasks-source-tabs'),'source tabs removed');
projectCheck(taskPage(['source'=>'standalone'])==='NOT_FOUND','retired independent route forbidden');
projectCheck(TaskStageService::item('standalone',1)===null,'retired source cannot fetch task');
projectCheck(TaskStageService::move('standalone',1,'state-2','','state')['status']===400,'retired source cannot write task');
projectCheck(TaskStageService::move('project',1,'done','','state')['status']===400,'percent stages cannot modify native task');
projectCheck(TaskStageService::move('project',1,'state-3','','state')['ok'],'native ProjectState writes via native API');
projectCheck(ProjectTask::$rows[1]['projectstates_id']===3,'native state persisted');
projectCheck(TaskCreationService::validate(['name'=>'Task','projects_id'=>'0'])===null,'project mandatory during creation');
projectCheck(TaskCreationService::validate(['name'=>'Task','projects_id'=>'1'])!==null,'valid project accepted');
projectCheck(!TaskCreationService::create(['name'=>'Task','projects_id'=>'0'])['ok'],'blank project cannot create a task');
projectCheck(!TaskCreationService::create(['name'=>'Task','projects_id'=>'-1'])['ok'],'invalid project cannot create a task');
projectCheck(TaskSchema::VERSION==='2','legacy schema remains compatible');
echo "Project-only PHP fixture tests PASSED (NOT LIVE)\n";
