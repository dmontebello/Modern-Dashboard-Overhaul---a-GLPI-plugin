<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskSchema;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskMenu;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskMenu.php';

Session::checkCentralAccess();
if (!Plugin::isPluginActive('mdoverhaul') || !StandaloneTask::enabled()) { Html::displayNotFoundError(); return; }
$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = [StandaloneTask::class, 'text'];
$task = new StandaloneTask();
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$rawId = $post ? ($_POST['id'] ?? '0') : ($_GET['id'] ?? '0');
$id = is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false;
if ($id === false || $id < 0) { Html::displayNotFoundError(); return; }

if ($post) {
    // GLPI 11 CheckCsrfListener has already consumed/validated the form token.
    // Do not validate it twice or bypass the native middleware.
    $actions = array_intersect(['add','update','delete','restore'], array_keys($_POST));
    if (count($actions) !== 1 || $_POST[array_values($actions)[0]] !== '1') { Html::displayNotFoundError(); return; }
    $action = array_values($actions)[0];
    $input = $_POST;
    $input['id'] = $id;
    if ($action === 'add' && $id !== 0) { Html::displayNotFoundError(); return; }
    if ($action !== 'add' && $id === 0) { Html::displayNotFoundError(); return; }
    $task->check($action === 'add' ? -1 : $id, match($action) { 'add'=>CREATE, 'update'=>UPDATE, default=>DELETE }, $input);
    try {
        $result = match($action) {
            'add' => $task->add($input), 'update' => $task->update($input),
            'delete' => $task->delete(['id'=>$id]), 'restore' => $task->restore(['id'=>$id]),
        };
        if (!$result) {
            Session::addMessageAfterRedirect($text('Valori non validi o operazione non consentita.', 'Invalid values or operation denied.'), false, ERROR);
        }
        if ($action === 'add' && $result) { $id = (int) $result; }
    } catch (Throwable $error) {
        Toolbox::logInFile('php-errors', '[mdoverhaul standalone] ' . get_class($error) . "\n");
        Session::addMessageAfterRedirect($text('Operazione non riuscita. Consultare i log GLPI.', 'Operation failed. Check GLPI logs.'), false, ERROR);
    }
    Html::redirect($action === 'delete' ? StandaloneTask::getSearchURL() : StandaloneTask::getFormURL() . ($id > 0 ? '?id='.$id : ''));
    return;
}

$input = ['entities_id'=>(int)($_SESSION['glpiactive_entity'] ?? 0)];
$task->check($id > 0 ? $id : -1, $id > 0 ? READ : CREATE, $input);
$fields = $task->fields;
$entity = (int)($fields['entities_id'] ?? $input['entities_id']);
$canEdit = $id === 0 ? StandaloneTask::canCreate() : $task->can($id, UPDATE);
$deleted = !empty($fields['is_deleted']);
Html::header(StandaloneTask::getTypeName(), '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl"><div class="mdo-tasks-create">';
echo '<h1>' . $esc(StandaloneTask::getTypeName()) . ($id > 0 ? ' #'.$id : '') . '</h1>';
echo '<p>' . $esc($text('Questa attività non appartiene a un progetto.', 'This task has no project.')) . '</p>';
if ($deleted) { echo '<p role="status">' . $esc($text('Attività nel cestino.', 'Task in trash.')) . '</p>'; }
echo '<form method="post" action="' . $esc(StandaloneTask::getFormURL()) . '" class="mdo-standalone-form">';
echo '<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="_glpi_csrf_token" value="' . $esc(Session::getNewCSRFToken()) . '">';
echo '<fieldset' . (!$canEdit || $deleted ? ' disabled' : '') . '>';
echo '<label for="mdo-task-name">' . $esc(__('Name')) . '</label><input class="form-control" id="mdo-task-name" name="name" required maxlength="255" value="' . $esc((string)($fields['name'] ?? '')) . '">';
echo '<label for="mdo-task-content">' . $esc(__('Description')) . '</label><textarea class="form-control" id="mdo-task-content" name="content" maxlength="100000" rows="5">' . $esc((string)($fields['content'] ?? '')) . '</textarea>';
echo '<div><span>' . $esc(__('Status')) . '</span>';
Dropdown::showFromArray('status', StandaloneTask::statuses(), ['value'=>(int)($fields['status'] ?? 1),'aria_label'=>__('Status')]);
echo '</div>';
$due = TaskBoard::dateTimestamp((string)($fields['due_date'] ?? '')) !== null ? str_replace(' ', 'T', substr((string)$fields['due_date'],0,16)) : '';
echo '<label for="mdo-task-due">' . $esc($text('Scadenza', 'Due date')) . '</label><input class="form-control" id="mdo-task-due" type="datetime-local" name="due_date" value="' . $esc($due) . '">';
if ($id === 0) {
    echo '<div><span>' . $esc(__('Entity')) . '</span>';
    Entity::dropdown(['name'=>'entities_id','value'=>$entity,'entity'=>$_SESSION['glpiactiveentities'] ?? [$entity],'display_emptychoice'=>false,'aria_label'=>__('Entity')]);
    echo '</div>';
}
echo '<div><span>' . $esc(__('Assigned to')) . '</span>';
User::dropdown(['name'=>'users_id_assign','value'=>(int)($fields['users_id_assign'] ?? 0),'entity'=>$entity,'right'=>'all','aria_label'=>__('Assigned to')]);
echo '</div><div><span>' . $esc(__('Group')) . '</span>';
Group::dropdown(['name'=>'groups_id_assign','value'=>(int)($fields['groups_id_assign'] ?? 0),'entity'=>$entity,'aria_label'=>__('Group')]);
echo '</div></fieldset><div class="mdo-tasks-create-actions">';
if ($canEdit && !$deleted) { echo '<button class="btn btn-primary" name="' . ($id > 0 ? 'update' : 'add') . '" value="1">' . $esc(__('Save')) . '</button>'; }
if ($id > 0 && $task->can($id, DELETE)) {
    echo '<button class="btn btn-outline-secondary" name="' . ($deleted ? 'restore' : 'delete') . '" value="1">' . $esc($deleted ? __('Restore') : __('Delete')) . '</button>';
}
echo '<a class="btn btn-outline-secondary" href="' . $esc(StandaloneTask::getSearchURL()) . '">' . $esc($text('Torna alle attività', 'Back to tasks')) . '</a>';
echo '</div></form></div></div>';
Html::footer();
