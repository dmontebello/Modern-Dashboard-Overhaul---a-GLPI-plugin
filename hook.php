<?php

require_once __DIR__ . '/src/Settings.php';
require_once __DIR__ . '/src/TaskBoard.php';
require_once __DIR__ . '/src/TaskSchema.php';
require_once __DIR__ . '/src/BuildInfo.php';
require_once __DIR__ . '/src/StandaloneTask.php';
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
    // Deliberately retain task data, profiles and config; no destructive DROP.
    return true;
}

/** GLPI's public search hook, in DB iterator format; no raw ACL SQL. */
function plugin_mdoverhaul_addDefaultWhere($itemtype): array
{
    return $itemtype === \GlpiPlugin\Mdoverhaul\StandaloneTask::class
        ? \GlpiPlugin\Mdoverhaul\StandaloneTask::getVisibilityCriteria()['WHERE'] : [];
}
