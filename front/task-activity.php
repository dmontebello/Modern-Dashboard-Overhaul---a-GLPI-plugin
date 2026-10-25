<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskActivity;
use GlpiPlugin\Mdoverhaul\TaskAttachment;
use GlpiPlugin\Mdoverhaul\TaskMenu;
use GlpiPlugin\Mdoverhaul\TaskStageService;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskSchema;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskStageService.php';
require_once __DIR__ . '/../src/TaskActivity.php';
require_once __DIR__ . '/../src/TaskAttachment.php';
require_once __DIR__ . '/../src/TaskMenu.php';

Session::checkCentralAccess();
$esc=static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$trans=[StandaloneTask::class,'text'];
if (!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace'] || !TaskSchema::isReady()) {
    Html::displayNotFoundError(); return;
}
$post=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$input=$post?$_POST:$_GET;
$source=$input['source']??'';
$rawId=$input['id']??0;
$id=is_scalar($rawId)?filter_var($rawId,FILTER_VALIDATE_INT):false;
if (!is_string($source) || !in_array($source,['project','standalone'],true) || $id===false || $id<1) {
    Html::displayNotFoundError(); return;
}
$item=TaskStageService::item($source,$id);
if ($item===null || !TaskActivity::canRead($source,$id)) {
    Html::displayNotFoundError(); return;
}
if (!$post && isset($_GET['download'])) {
    $file=filter_var($_GET['download'],FILTER_VALIDATE_INT);
    if ($file!==false && $file>0 && TaskAttachment::download($file)) { return; }
    Html::displayNotFoundError(); return;
}

global $CFG_GLPI;
$base=rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/task-activity.php';
$url=static fn(array $args=[]): string => $base.'?'.http_build_query(array_replace([
    'source'=>$source,'id'=>$id,
],$args),'','&',PHP_QUERY_RFC3986);

if ($post) {
    // GLPI 11 CheckCsrfListener must validate the form token before reaching this route.
    $actions=array_intersect(['add_note','add_file'],array_keys($_POST));
    if (count($actions)!==1 || !TaskActivity::canWrite($source,$id)) {
        Html::displayNotFoundError(); return;
    }
    $action=array_values($actions)[0];
    $success=false;
    try {
        if ($action==='add_note' && is_string($_POST['content']??null)) {
            $success=TaskActivity::add($source,$id,$_POST['content']);
        } elseif ($action==='add_file' && isset($_FILES['attachment']) && is_array($_FILES['attachment'])) {
            $success=TaskAttachment::add($source,$id,$_FILES['attachment']);
        }
    } catch (Throwable $error) {
        Toolbox::logInFile('php-errors','[mdoverhaul activity] '.get_class($error)."\n");
    }
    Session::addMessageAfterRedirect(
        $success?$trans('Aggiornamento salvato.','Update saved.'):
                 $trans('Operazione non riuscita o non autorizzata.','Operation failed or denied.'),
        false, $success ? INFO : ERROR
    );
    Html::redirect($url());
    return;
}

$start=filter_var($_GET['start']??0,FILTER_VALIDATE_INT);
$start=($start===false || $start<0)?0:min(100000,$start);
$notes=TaskActivity::page($source,$id,$start);
$noteItems=static function(array $rows) use ($esc,$trans): void {
    foreach ($rows as $row) {
        $user=(int)($row['users_id']??0);
        $author=function_exists('getUserName')?(string)getUserName($user):('User #'.$user);
        $date=(string)($row['date_creation']??'');
        echo '<article class="mdo-activity-entry"><div class="mdo-activity-meta"><strong>'.
            $esc($author).'</strong><time>'.$esc($date).'</time></div><p>'.
            nl2br($esc((string)$row['content'])).'</p></article>';
    }
};
if (($_GET['partial']??'')==='notes') {
    $noteItems($notes['items']);
    if ($notes['more']) {
        echo '<a data-mdo-more class="btn btn-outline-secondary" href="'.
            $esc($url(['start'=>$start+TaskActivity::PAGE_SIZE,'partial'=>'notes'])).'">'.
            $esc($trans('Mostra precedenti','Show previous')).'</a>';
    }
    return;
}
Html::header($trans('Diario attività','Task activity'), '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl"><div class="mdo-tasks-create mdo-activity">';
echo '<h1>'.$esc($trans('Diario e allegati','Activity and attachments')).'</h1>';
echo '<h2>'.$esc((string)($item->fields['name']??'')).'</h2>';
echo '<p><a class="btn btn-outline-secondary" href="'.$esc(($source==='standalone'?
    StandaloneTask::getFormURL():ProjectTask::getFormURL()).'?id='.$id).'">'.
    $esc($trans('Torna alla task','Back to task')).'</a></p>';
echo '<section aria-label="'.$esc($trans('Aggiornamenti','Updates')).'"><h3>'.
    $esc($trans('Aggiornamenti','Updates')).'</h3>';
echo '<div data-mdo-activity-notes>';
$noteItems($notes['items']);
echo '</div>';
if ($notes['more']) {
    echo '<a data-mdo-more class="btn btn-outline-secondary" href="'.
        $esc($url(['start'=>$start+TaskActivity::PAGE_SIZE,'partial'=>'notes'])).'">'.
        $esc($trans('Mostra precedenti','Show previous')).'</a>';
}
if (TaskActivity::canWrite($source,$id)) {
    echo '<form method="post" action="'.$esc($base).'" class="mdo-activity-form">'.
        '<input type="hidden" name="source" value="'.$esc($source).'">'.
        '<input type="hidden" name="id" value="'.$id.'">'.
        '<input type="hidden" name="_glpi_csrf_token" value="'.$esc(Session::getNewCSRFToken()).'">'.
        '<label for="mdo-note">'.$esc($trans('Cosa è successo?','What happened?')).'</label>'.
        '<textarea id="mdo-note" class="form-control" name="content" maxlength="10000" rows="4" required></textarea>'.
        '<button class="btn btn-primary" name="add_note" value="1">'.$esc($trans('Aggiungi aggiornamento','Add update')).'</button></form>';
}
echo '</section><section aria-label="'.$esc($trans('Allegati','Attachments')).'"><h3>'.
    $esc($trans('Allegati','Attachments')).'</h3><ul class="mdo-activity-files">';
foreach (TaskAttachment::listing($source,$id) as $attachment) {
    echo '<li><a href="'.$esc($url(['download'=>(int)$attachment['id']])).'">'.
        $esc((string)$attachment['name']).'</a> (' . (int)$attachment['filesize'] . ' bytes)</li>';
}
echo '</ul>';
if (TaskActivity::canWrite($source,$id)) {
    echo '<form method="post" enctype="multipart/form-data" action="'.$esc($base).'" class="mdo-activity-form">'.
        '<input type="hidden" name="source" value="'.$esc($source).'">'.
        '<input type="hidden" name="id" value="'.$id.'">'.
        '<input type="hidden" name="_glpi_csrf_token" value="'.$esc(Session::getNewCSRFToken()).'">'.
        '<label for="mdo-file">'.$esc($trans('Allega un file (massimo 15 MB)','Attach a file (maximum 15 MB)')).'</label>'.
        '<input class="form-control" id="mdo-file" type="file" name="attachment" required>'.
        '<button class="btn btn-primary" name="add_file" value="1">'.$esc($trans('Carica allegato','Upload attachment')).'</button></form>';
}
echo '</section></div></div>';
Html::footer();
