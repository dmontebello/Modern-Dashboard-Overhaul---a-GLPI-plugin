<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { return; }
require_once __DIR__ . '/task_bootstrap.php';
taskFixture();
$flags = array_fill_keys(array_keys(\GlpiPlugin\Mdoverhaul\Settings::defaults()), '0');
$flags['task_workspace'] = $flags['tasks_standalone'] = '1';
\GlpiPlugin\Mdoverhaul\Settings::save($flags);
Session::$standaloneRights = \GlpiPlugin\Mdoverhaul\StandaloneTask::READMY | CREATE | UPDATE;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = $_POST = [];
ob_start();
include __DIR__ . '/../front/task.form.php';
echo (string) ob_get_clean();
