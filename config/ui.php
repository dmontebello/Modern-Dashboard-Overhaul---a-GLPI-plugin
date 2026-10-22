<?php
/**
 * Legacy 0.5 deployment defaults for Modern Dashboard Overhaul.
 * In 0.7.0 manage settings from GLPI -> Setup -> Plugins -> Configure.
 * Native saved values in glpi_configs (plugin:mdoverhaul) take precedence.
 * This file and optional ui.local.php are fallbacks for older installs only.
 * Do not edit distributed files: SHA-512 checks require them to stay unchanged.
 */
return [
    'enabled' => true,
    'rss_widgets' => true,
    'compact_empty_filters' => true,
    'dashboard_tabs' => true,
    'chart_text' => true,
];
