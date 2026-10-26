<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\TaskStageService;

require_once __DIR__ . '/../src/TaskStageService.php';
require_once __DIR__ . '/../src/StandaloneTask.php';
require_once __DIR__ . '/../src/TaskSchema.php';
require_once __DIR__ . '/../src/TaskBoard.php';
require_once __DIR__ . '/../src/Settings.php';

Session::checkCentralAccess();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || !Plugin::isPluginActive('mdoverhaul')
    || !GlpiPlugin\Mdoverhaul\Settings::get()['task_workspace']) {
    http_response_code(404);
    echo json_encode(['ok'=>false, 'error'=>'not_found']);
    return;
}
// GLPI 11's request middleware checks CSRF for POST on this plugin route.
$source = $_POST['source'] ?? '';
$stage = $_POST['stage'] ?? '';
$id = $_POST['id'] ?? null;
$version = $_POST['version'] ?? '';
if (!is_string($source) || !is_string($stage) || !is_string($version)
    || !is_scalar($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid']);
    return;
}
try {
    $result = TaskStageService::move($source, (int) $id, $stage, $version);
} catch (Throwable $error) {
    Toolbox::logInFile('php-errors', '[mdoverhaul stage] ' . get_class($error) . "\n");
    $result = ['status'=>500,'ok'=>false,'error'=>'server_error'];
}
http_response_code($result['status']);
unset($result['status']);
// GLPI consumes the CSRF token on every accepted POST, including ACL/conflict
// failures. Rotate it even on a 4xx response so the next action can be retried.
$result['token'] = Session::getNewCSRFToken();
echo json_encode($result, JSON_THROW_ON_ERROR);
