<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\StandaloneTask;
use GlpiPlugin\Mdoverhaul\TaskBoard;
use GlpiPlugin\Mdoverhaul\TaskCreationService;
use GlpiPlugin\Mdoverhaul\TaskMenu;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskCreationService.php';
require_once __DIR__ . '/../src/TaskMenu.php';

Session::checkCentralAccess();
if (!Plugin::isPluginActive('mdoverhaul') || !Settings::get()['task_workspace']
    || (!ProjectTask::canCreate() && !StandaloneTask::canCreate())) {
    Html::displayNotFoundError(); return;
}
$translate = [StandaloneTask::class, 'text'];
$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
global $CFG_GLPI;
$base = rtrim((string)$CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/task.form.php';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$error = '';
$data = $post ? $_POST : [];
if ($post) {
    // GLPI 11 CheckCsrfListener validates POST before dispatch. Never bypass it.
    if (($_POST['create'] ?? null) !== '1') {
        Html::displayNotFoundError(); return;
    }
    $result = TaskCreationService::create($_POST);
    if ($result['ok']) {
        $target = $result['source'] === 'project' ? ProjectTask::getFormURL() : StandaloneTask::getFormURL();
        Html::redirect($target . '?id=' . $result['id']); // Outside service try/catch.
        return;
    }
    $error = match ($result['error']) {
        'forbidden' => $translate('Non hai i permessi per creare questa attività nel progetto selezionato.', 'You cannot create this task in the selected project.'),
        'save_failed' => $translate('Impossibile salvare la task. Controlla i log GLPI.', 'Unable to save task. Check GLPI logs.'),
        default => $translate('Controlla i campi inseriti e riprova.', 'Check the supplied fields and retry.'),
    };
}

Html::header($translate('Nuova task', 'New task'), '', 'helpdesk', strtolower(TaskMenu::class));
echo '<div class="mdo-tasks container-xl"><div class="mdo-tasks-create mdo-unified-create">';
echo '<h1>' . $esc($translate('Nuova task', 'New task')) . '</h1>';
echo '<p class="mdo-tasks-hint">' . $esc($translate(
    'Progetto facoltativo: con progetto viene creata una task GLPI nativa; senza progetto una task indipendente.',
    'Project is optional. With a project a native GLPI task is created; without one an independent task is created.'
)) . '</p>';
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . $esc($error) . '</div>'; }
echo '<form method="post" action="' . $esc($base) . '" class="mdo-unified-create-form">';
echo '<input type="hidden" name="_glpi_csrf_token" value="' . $esc(Session::getNewCSRFToken()) . '">';
echo '<input type="hidden" name="create" value="1">';
echo '<label for="mdo-new-name">' . $esc(__('Name')) . '</label>';
echo '<input class="form-control" id="mdo-new-name" name="name" maxlength="255" required value="' .
    $esc(is_string($data['name'] ?? null) ? $data['name'] : '') . '">';
echo '<label for="mdo-new-content">' . $esc(__('Description')) . '</label>';
echo '<textarea class="form-control" id="mdo-new-content" name="content" maxlength="100000" rows="5">' .
    $esc(is_string($data['content'] ?? null) ? $data['content'] : '') . '</textarea>';
echo '<label for="mdo-new-due">' . $esc($translate('Scadenza', 'Due date')) . '</label>';
$due = is_string($data['due_date'] ?? null) ? $data['due_date'] : '';
echo '<input class="form-control" id="mdo-new-due" type="datetime-local" name="due_date" value="' . $esc($due) . '">';
if (ProjectTask::canCreate()) {
    echo '<label for="mdo-new-project">' . $esc($translate('Progetto (facoltativo)', 'Project (optional)')) . '</label>';
    echo '<div class="mdo-tasks-project-picker">';
    $ready = false;
    $depth = ob_get_level();
    try {
        ob_start();
        $rendered = Dropdown::show(Project::class, [
            'name' => 'projects_id', 'value' => is_scalar($data['projects_id'] ?? 0) ? (int)$data['projects_id'] : 0,
            'display_emptychoice' => true, 'required' => false, 'aria_label' => $translate('Progetto facoltativo', 'Optional project'),
            'condition' => ['is_deleted' => 0, 'is_template' => 0], 'entity' => -1,
            'width' => '100%', 'addicon' => false,
        ]);
        $markup = (string)ob_get_clean();
        if ($rendered !== false && $markup !== '') { echo $markup; $ready = true; }
    } catch (Throwable $exception) {
        while (ob_get_level() > $depth) { ob_end_clean(); }
        Toolbox::logInFile('php-errors', '[mdoverhaul unified dropdown] ' . get_class($exception) . "\n");
    }
    if (!$ready) {
        echo '<p role="status">' . $esc($translate('Selezione progetto non disponibile. Usa la ricerca nativa GLPI per creare task di progetto.', 'Project selector unavailable. Use native GLPI project search to create project tasks.')) . '</p>';
    }
    echo '</div>';
}
if (StandaloneTask::canCreate()) {
    echo '<div data-mdo-standalone-fields>';
    echo '<label for="mdo-new-entity">' . $esc(__('Entity')) . '</label>';
    Entity::dropdown(['name'=>'entities_id','value'=>(int)($data['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
        'entity'=>$_SESSION['glpiactiveentities'] ?? [], 'display_emptychoice'=>false,'aria_label'=>__('Entity')]);
    echo '<label for="mdo-new-status">' . $esc(__('Status')) . '</label>';
    Dropdown::showFromArray('status', StandaloneTask::statuses(), ['value'=>(int)($data['status'] ?? 1),'aria_label'=>__('Status')]);
    echo '<label for="mdo-new-assignee">' . $esc(__('Assigned to')) . '</label>';
    User::dropdown(['name'=>'users_id_assign','value'=>(int)($data['users_id_assign'] ?? 0),'entity'=>(int)($_SESSION['glpiactive_entity'] ?? 0),'right'=>'all','aria_label'=>__('Assigned to')]);
    echo '<label for="mdo-new-group">' . $esc(__('Group')) . '</label>';
    Group::dropdown(['name'=>'groups_id_assign','value'=>(int)($data['groups_id_assign'] ?? 0),'entity'=>(int)($_SESSION['glpiactive_entity'] ?? 0),'aria_label'=>__('Group')]);
    echo '</div>';
}
echo '<p class="mdo-tasks-hint">' . $esc($translate(
    'Le opzioni specifiche delle task di progetto (team, parent, pianificazione avanzata) restano nel form nativo dopo il salvataggio. Diario e allegati sono disponibili dopo la creazione.',
    'Project-specific options (team, parent, advanced planning) remain available in the native form after saving. Activity and attachments are available after creation.'
)) . '</p>';
echo '<div class="mdo-tasks-create-actions"><button type="submit" class="btn btn-primary">' .
    $esc(__('Save')) . '</button><a class="btn btn-outline-secondary" href="' .
    $esc(rtrim((string)$CFG_GLPI['root_doc'],'/') . '/plugins/mdoverhaul/front/tasks.php') . '">' .
    $esc($translate('Torna alle attività','Back to tasks')) . '</a></div></form></div></div>';
Html::footer();
