<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\TaskStageService;

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/TaskStageService.php';

Session::checkCentralAccess();
$ajax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
if ($ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || !Plugin::isPluginActive('mdoverhaul')
    || !Settings::get()['task_workspace']) {
    http_response_code(404);
    if ($ajax) echo json_encode(['ok'=>false,'error'=>'not_found']);
    else Html::displayNotFoundError();
    return;
}
// The GLPI 11 middleware consumes the CSRF token before this controller runs.
$source = $_POST['source'] ?? null;
$id = $_POST['id'] ?? null;
$confirmed = $_POST['confirm_delete'] ?? null;
$result = ['ok'=>false,'error'=>'invalid'];
$status = 400;
if (is_string($source) && $source === 'project'
    && (is_string($id) || is_int($id))
    && preg_match('/^[1-9][0-9]{0,9}$/D', (string)$id)
    && $confirmed === '1') {
    try {
        $item = TaskStageService::item($source, (int)$id);
        if ($item === null || !empty($item->fields['is_deleted'])) {
            $status = 404; $result['error'] = 'not_found';
        } elseif (!$item->can((int)$id, DELETE)) {
            $status = 403; $result['error'] = 'forbidden';
        } elseif (!$item->maybeDeleted()) {
            // Never silently purge a ProjectTask that cannot be moved to trash.
            $status = 409; $result['error'] = 'trash_unavailable';
        } elseif ($item->delete(['id'=>(int)$id], false)) {
            $status = 200; $result = ['ok'=>true];
        } else {
            $status = 409; $result['error'] = 'delete_failed';
        }
    } catch (Throwable $error) {
        Toolbox::logInFile('php-errors', '[mdoverhaul task delete] '.get_class($error)."\n");
        $status = 500; $result['error'] = 'server_error';
    }
}
if ($ajax) {
    http_response_code($status);
    $result['token'] = Session::getNewCSRFToken(true);
    echo json_encode($result, JSON_THROW_ON_ERROR);
    return;
}
if (!$result['ok']) {
    Session::addMessageAfterRedirect(
        'Task not deleted. Check permissions or try again.', false, ERROR
    );
}
global $CFG_GLPI;
Html::redirect(rtrim((string)$CFG_GLPI['root_doc'],'/').'/plugins/mdoverhaul/front/tasks.php?view=list');
