<?php

// No plugin-owned tables. GLPI itself records the plugin installation state.
// Keep RSS feeds and dashboard items intact for compatibility and rollback.
function plugin_mdoverhaul_install(): bool
{
    return true;
}

function plugin_mdoverhaul_uninstall(): bool
{
    return true;
}
