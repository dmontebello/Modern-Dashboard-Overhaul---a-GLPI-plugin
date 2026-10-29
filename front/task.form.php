<?php
declare(strict_types=1);
use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskCreationService;
use GlpiPlugin\Mdoverhaul\TaskMenu;
require_once __DIR__.'/../src/Settings.php';
require_once __DIR__.'/../src/TaskBoard.php';
require_once __DIR__.'/../src/TaskCreationService.php';
require_once __DIR__.'/../src/TaskMenu.php';
Session::checkCentralAccess();
if(!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace'] || !ProjectTask::canCreate()){
    Html::displayNotFoundError(); return;
}
$it=str_starts_with(strtolower((string)Session::getLanguage()),'it');
$t=static fn(string $a,string $b):string=>$it?$a:$b;
$e=static fn(string $s):string=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
global $CFG_GLPI;
$base=rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/task.form.php';
$post=($_SERVER['REQUEST_METHOD']??'GET')==='POST';$error='';$data=$post?$_POST:[];
if($post){
    if(($_POST['create']??null)!=='1'){Html::displayNotFoundError();return;}
    $result=TaskCreationService::create($_POST);
    if($result['ok']){Html::redirect(ProjectTask::getFormURL().'?id='.$result['id']);return;}
    $error=$result['error']==='forbidden'?$t('Non autorizzato per questo progetto.','Not authorized for this project.'):
        $t('Seleziona un progetto valido e controlla i campi.','Select an authorized project and check fields.');
}
Html::header($t('Nuova task','New task'),'','helpdesk',strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl"><div class="mdo-tasks-create mdo-unified-create"><h1>'.$e($t('Nuova task di progetto','New project task')).'</h1>';
echo '<p>'.$e($t('Un progetto GLPI è obbligatorio.','A GLPI project is required.')).'</p>';
if($error!=='')echo '<p class="alert alert-danger" role="alert">'.$e($error).'</p>';
echo '<form method="post" action="'.$e($base).'" class="mdo-unified-create-form">';
echo '<input type="hidden" name="_glpi_csrf_token" value="'.$e(Session::getNewCSRFToken()).'"><input type="hidden" name="create" value="1">';
echo '<label for="mdo-new-name">'.$e(__('Name')).'</label><input class="form-control" id="mdo-new-name" name="name" maxlength="255" required value="'.$e(is_string($data['name']??null)?$data['name']:'').'">';
echo '<label for="mdo-new-content">'.$e(__('Description')).'</label><textarea class="form-control" id="mdo-new-content" name="content" maxlength="100000" rows="5">'.$e(is_string($data['content']??null)?$data['content']:'').'</textarea>';
echo '<label for="mdo-new-due">'.$e($t('Scadenza','Due date')).'</label><input class="form-control" type="datetime-local" id="mdo-new-due" name="due_date" value="'.$e(is_string($data['due_date']??null)?$data['due_date']:'').'">';
echo '<label>'.$e($t('Progetto obbligatorio','Required project')).'</label><div class="mdo-tasks-project-picker">';
$ready=false;$depth=ob_get_level();
try{ob_start();$shown=Dropdown::show(Project::class,['name'=>'projects_id','value'=>(int)($data['projects_id']??0),'display_emptychoice'=>true,'required'=>true,'aria_label'=>$t('Progetto obbligatorio','Required project'),'condition'=>['is_deleted'=>0,'is_template'=>0],'entity'=>-1,'width'=>'100%','addicon'=>false]);$html=(string)ob_get_clean();if($shown!==false&&$html!==''){echo $html;$ready=true;}}
catch(Throwable $err){while(ob_get_level()>$depth)ob_end_clean();Toolbox::logInFile('php-errors','[mdoverhaul project dropdown] '.get_class($err)."\n");}
echo '</div><div class="mdo-tasks-create-actions"><button class="btn btn-primary" type="submit"'.($ready?'':' disabled').'>'.$e($t('Crea attività','Create task')).'</button><a class="btn btn-outline-secondary" href="'.$e(rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/tasks.php').'">'.$e($t('Annulla','Cancel')).'</a></div></form></div></div>';
Html::footer();
