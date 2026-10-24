<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { return; }
require_once __DIR__ . '/task_bootstrap.php';
taskFixture();
$_SERVER['REQUEST_METHOD'] = 'GET';
include __DIR__ . '/../front/config.php';
