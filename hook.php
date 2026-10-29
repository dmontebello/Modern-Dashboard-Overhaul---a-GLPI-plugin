<?php

require_once __DIR__ . '/src/Settings.php';
require_once __DIR__ . '/src/TaskBoard.php';
require_once __DIR__ . '/src/TaskSchema.php';
require_once __DIR__ . '/src/BuildInfo.php';
// Called by GLPI's administrative install/upgrade, never by workspace GET.
function plugin_mdoverhaul_install(): bool
{
    try { return \GlpiPlugin\Mdoverhaul\TaskSchema::install(); }
    catch (Throwable $error) {
        Toolbox::logInFile('php-errors', '[mdoverhaul schema] ' . $error->getMessage() . "\n");
        return false;
    }
}

function plugin_mdoverhaul_uninstall(): bool
{
    // Retire the legacy independent model without any destructive DROP. Historic
    // data, notes/files and config remain available for audited export/rollback.
    return true;
}

