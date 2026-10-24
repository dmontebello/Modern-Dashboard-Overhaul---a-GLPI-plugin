<?php
declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\BuildInfo;

require_once __DIR__ . '/../src/BuildInfo.php';
Session::checkLoginUser();
if (!Plugin::isPluginActive('mdoverhaul')) {
    http_response_code(404);
    return;
}
header('Content-Type: application/javascript; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
echo 'window.MdoBuild = Object.freeze(' . json_encode(BuildInfo::get(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . ');';
