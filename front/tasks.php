<?php
declare(strict_types=1);
use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskMenu;
use GlpiPlugin\Mdoverhaul\TaskStageService;
require_once __DIR__.'/../src/Settings.php';
require_once __DIR__.'/../src/TaskMenu.php';
require_once __DIR__.'/../src/TaskBoard.php';
require_once __DIR__.'/../src/TaskStageService.php';
Session::checkCentralAccess();
if (!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace']) {
    Html::displayNotFoundError(); return;
}
if (!ProjectTask::canView()) { Session::checkRight('projecttask', ProjectTask::READMY); return; }
if (isset($_GET['source']) && !in_array($_GET['source'], ['project','all'], true)) {
    Html::displayNotFoundError(); return;
}
$it=str_starts_with(strtolower((string)Session::getLanguage()),'it');
$t=static fn(string $a,string $b): string=>$it?$a:$b;
$e=static fn(string $v): string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$get=static fn(string $key,string $default=''): string=>is_string($_GET[$key]??null)?$_GET[$key]:$default;
$view=$get('view','board')==='list'?'list':'board';
$q=trim(substr($get('q'),0,120));
$filter=in_array($get('filter'),['all','open','done','overdue'],true)?$get('filter'):'all';
$due=in_array($get('due'),['all','overdue','today','week','none'],true)?$get('due'):'all';
$sort=in_array($get('board_sort'),['due','name','project','state','progress'],true)?$get('board_sort'):'due';
$project=filter_var($get('project','0'),FILTER_VALIDATE_INT);
$project=$project===false?0:max(0,$project);
$state=filter_var($get('state','-1'),FILTER_VALIDATE_INT);
$state=$state===false?-1:max(-1,$state);
global $CFG_GLPI;
$base=rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/tasks.php';
$url=static fn(array $args=[]): string=>$base.'?'.http_build_query(array_replace([
    'view'=>$view,'q'=>$q,'filter'=>$filter,'due'=>$due,'board_sort'=>$sort,
    'project'=>$project,'state'=>$state
],$args),'','&',PHP_QUERY_RFC3986);
Html::header('Tasks','','helpdesk',strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl"><header class="mdo-tasks-head"><div>';
echo '<p class="mdo-tasks-eyebrow">MODERN DASHBOARD OVERHAUL</p><h1>'.$e($t('Attività dei progetti','Project tasks')).'</h1>';
echo '<p class="text-secondary">'.$e($t('Solo attività native associate a progetti GLPI.','Only native tasks belonging to GLPI projects.')).'</p></div><div class="mdo-tasks-actions">';
if (Project::canView()) echo '<a class="btn btn-outline-secondary" href="'.$e(Project::getSearchURL()).'">'.$e($t('Progetti','Projects')).'</a>';
if (ProjectTask::canCreate()) echo '<a class="btn btn-outline-primary" href="'.$e(rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/task.form.php').'">'.$e($t('Nuova task','New task')).'</a>';
echo '</div></header><nav class="mdo-tasks-view-tabs" aria-label="Task views">';
foreach (['list'=>$t('Elenco','List'),'board'=>'Kanban'] as $key=>$label) {
    echo '<a class="btn '.($view===$key?'btn-primary':'btn-outline-secondary').'" '.($view===$key?'aria-current="page"':'').' href="'.$e($url(['view'=>$key])).'">'.$e($label).'</a>';
}
echo '</nav>';
if ($view==='list') {
    echo '<p class="mdo-tasks-hint">'.$e($t('Ricerca e permessi nativi GLPI.','Native GLPI search and permissions.')).'</p>';
    $params=Search::manageParams(ProjectTask::class,$_GET);
    $params['target']=$url(['view'=>'list']);$params['display_type']=Search::HTML_OUTPUT;
    echo '<section class="mdo-tasks-native-search"><div class="search_page row search-no-forced-height"><div class="col search-container disable-overflow-y" data-glpi-search-container>';
    Search::showGenericSearch(ProjectTask::class,$params);Search::showList(ProjectTask::class,$params);
    echo '</div></div></section></div>';Html::footer();return;
}
try {$loaded=TaskBoard::load();} catch (Throwable $err) {
    Toolbox::logInFile('php-errors','[mdoverhaul Tasks] '.get_class($err)."\n");
    echo '<div class="alert alert-danger" role="alert">'.$e($t('Impossibile caricare le attività.','Unable to load tasks.')).'</div></div>';
    Html::footer();return;
}
$all=$loaded['tasks'];
$projects=[];foreach($all as $task) if($task['project_readable'])$projects[$task['project_id']]=$task['project'];
asort($projects,SORT_NATURAL|SORT_FLAG_CASE);
$native=TaskBoard::projectStates();
$filtered=TaskBoard::sort(TaskBoard::filter($all,$q,$filter,$state,$project,$due),$sort);
$stats=['all'=>0,'open'=>0,'done'=>0,'overdue'=>0];
foreach(TaskBoard::filter($all,$q,'all',$state,$project,$due) as $task) {
    $stats['all']++;$stats[$task['percent_done']>=100?'done':'open']++;
    $stats['overdue']+=(int)TaskBoard::isOverdue($task);
}
echo '<div class="mdo-tasks-summary">';
foreach(['all'=>$t('Visibili','Visible'),'open'=>$t('Da completare','Incomplete'),
         'done'=>$t('Completate','Completed'),'overdue'=>$t('Scadute','Overdue')] as $k=>$label)
    echo '<a class="mdo-tasks-stat'.($filter===$k?' is-active':'').'" href="'.$e($url(['filter'=>$k])).'"><span>'.$e($label).'</span><strong>'.(int)$stats[$k].'</strong></a>';
echo '</div>';
if($loaded['truncated'])echo '<div class="alert alert-warning">'.$e($t('Vista parziale: usa Elenco per la ricerca completa.','Partial sample: use List for complete search.')).'</div>';
echo '<details class="mdo-tasks-filter-panel"><summary>'.$e($t('Filtri e ordinamento','Filters and sorting')).'</summary>';
echo '<form class="mdo-tasks-filters" method="get" action="'.$e($base).'"><input type="hidden" name="view" value="board">';
echo '<label>'.$e($t('Cerca','Search')).'<input class="form-control" name="q" maxlength="120" value="'.$e($q).'"></label>';
foreach(['filter'=>['all'=>$t('Tutte','All'),'open'=>$t('Incomplete','Incomplete'),'done'=>$t('Completate','Completed'),'overdue'=>$t('Scadute','Overdue')],
'due'=>['all'=>$t('Qualsiasi','Any'),'overdue'=>$t('Scadute','Overdue'),'today'=>$t('Oggi','Today'),'week'=>$t('7 giorni','7 days'),'none'=>$t('Nessuna','None')],
'board_sort'=>['due'=>$t('Scadenza','Due date'),'name'=>$t('Nome','Name'),'project'=>$t('Progetto','Project'),'state'=>$t('Stato','State'),'progress'=>$t('Avanzamento','Progress')]] as $key=>$opts) {
    $value=[$filter,$due,$sort][array_search($key,['filter','due','board_sort'],true)];
    echo '<label>'.$e(ucfirst($key)).'<select class="form-select" name="'.$key.'">';
    foreach($opts as $k=>$label)echo '<option value="'.$k.'"'.($value===$k?' selected':'').'>'.$e($label).'</option>';
    echo '</select></label>';
}
echo '<label>'.$e($t('Progetto','Project')).'<select class="form-select" name="project"><option value="0">'.$e($t('Tutti','All')).'</option>';
foreach($projects as $id=>$name)echo '<option value="'.(int)$id.'"'.($project===(int)$id?' selected':'').'>'.$e($name).'</option>';
echo '</select></label><label>'.$e($t('Stato','Status')).'<select class="form-select" name="state"><option value="-1">'.$e($t('Tutti','All')).'</option>';
foreach($native as $id=>$name)echo '<option value="'.(int)$id.'"'.($state===(int)$id?' selected':'').'>'.$e($name).'</option>';
echo '</select></label><div class="mdo-tasks-filter-actions"><button class="btn btn-primary" type="submit">'.$e($t('Filtra','Apply')).'</button><a class="btn btn-outline-secondary" href="'.$e($base.'?view=board').'">'.$e($t('Reimposta','Reset')).'</a></div></form></details>';
$columns=[];
foreach($native as $id=>$name)$columns['state-'.$id]=['name'=>$name,'items'=>[]];
foreach($filtered as $task) {
    $key='state-'.$task['state_id'];
    if(!isset($columns[$key]))$columns[$key]=['name'=>$t('Senza stato','No status'),'items'=>[]];
    $columns[$key]['items'][]=$task;
}
echo '<p class="mdo-tasks-hint">'.$e($t('Sposta le task tra gli stati GLPI.','Move tasks between native GLPI statuses.')).'</p>';
echo '<p class="mdo-tasks-results" role="status">'.count($filtered).' '.$e($t('attività mostrate','tasks shown')).'</p>';
echo '<p class="mdo-tasks-hint" id="mdo-board-scroll">'.$e($t('Scorri orizzontalmente.','Scroll horizontally.')).'</p>';
echo '<div class="mdo-tasks-board" data-mdo-board data-endpoint="'.$e(rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/task-stage.php').'" data-token="'.$e(Session::getNewCSRFToken()).'" data-mode="state" role="region" tabindex="0" aria-label="Kanban" aria-describedby="mdo-board-scroll">';
foreach($columns as $key=>$column) {
    $enabled=array_key_exists($key,array_fill_keys(array_map(static fn($id)=>'state-'.$id,array_keys($native)),true));
    echo '<section class="mdo-tasks-column" data-mdo-column="'.$e($key).'" data-accept-source="project" data-drop-enabled="'.($enabled?'1':'0').'"><header><h2>'.$e($column['name']).'</h2><span>'.count($column['items']).'</span></header><div class="mdo-tasks-column-body">';
    if(!$column['items'])echo '<p class="mdo-tasks-hint">'.$e($t('Nessuna attività','No tasks')).'</p>';
    foreach($column['items'] as $task) {
        $item=TaskStageService::item('project',(int)$task['id']);
        $canMove=$item!==null && $item->can((int)$task['id'],UPDATE) && count($native)>0;
        echo '<article class="mdo-tasks-card" data-source="project" data-id="'.(int)$task['id'].'" data-version="'.$e((string)($item->fields['date_mod']??'')).'" data-can-move="'.($canMove?'1':'0').'" data-complete="'.($task['percent_done']>=100?'1':'0').'"'.($canMove?' draggable="true"':'').'><small>Project task #'.(int)$task['id'].'</small>';
        echo '<a class="mdo-tasks-card-title" href="'.$e(ProjectTask::getFormURL().'?id='.(int)$task['id']).'">'.$e($task['name']).'</a>';
        if($task['project_readable'])echo '<a class="mdo-tasks-project" href="'.$e(Project::getFormURL().'?id='.(int)$task['project_id']).'">'.$e($task['project']).'</a>';
        echo '<a class="mdo-tasks-journal" href="'.$e(rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/task-activity.php?source=project&id='.(int)$task['id']).'">'.$e($t('Diario e allegati','Activity and files')).'</a>';
        echo '<div class="mdo-tasks-card-meta"><span class="mdo-tasks-state">'.$e($task['state']).'</span></div>';
        if($canMove) {
            echo '<label class="mdo-stage-control">'.$e($t('Sposta in','Move to')).' <select data-stage-choice aria-label="'.$e($t('Sposta attività','Move task')).'">';
            if(!isset($columns[$key])||!$enabled)echo '<option value="'.$e($key).'" selected disabled>'.$e($t('Senza stato','No status')).'</option>';
            foreach($native as $id=>$name)echo '<option value="state-'.(int)$id.'"'.($key==='state-'.$id?' selected':'').'>'.$e($name).'</option>';
            echo '</select></label>';
        }
        echo '</article>';
    }
    echo '</div></section>';
}
echo '</div><p data-mdo-live class="visually-hidden" role="status" aria-live="polite"></p></div>';Html::footer();
