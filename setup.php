<?php

use Glpi\Plugin\Hooks;
use GlpiPlugin\Mdoverhaul\Dashboard;
use GlpiPlugin\Mdoverhaul\Settings;

require_once __DIR__ . '/src/Settings.php';
require_once __DIR__ . '/src/FeedFormatter.php';
require_once __DIR__ . '/src/Dashboard.php';

define('PLUGIN_MDOVERHAUL_VERSION', '0.7.0');

function plugin_init_mdoverhaul(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['mdoverhaul'] = true;
    // The settings page must remain available even when both modules are off.
    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['mdoverhaul'] = 'front/config.php';
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['mdoverhaul'] = ['css/settings.css'];

    // Do not register conflicting legacy RSS cards from two simultaneous plugins.
    if (Plugin::isPluginActive('arensecurity')) {
        return;
    }

    $settings = Settings::get();
    $restyle = $settings['dashboard_restyling'];
    $rss = $settings['rss_widgets'];
    $styles = [];

    if ($rss) {
        $PLUGIN_HOOKS[Hooks::DASHBOARD_TYPES]['mdoverhaul'] = [Dashboard::class, 'getTypes'];
        $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['mdoverhaul'] = [Dashboard::class, 'getCards'];
        $styles[] = 'css/security-watch.css';
    }

    if ($restyle) {
        $styles[] = 'css/dashboard-theme.css';
        $styles[] = 'css/dashboard-overhaul.css';
        if ($settings['compact_empty_filters']) {
            $styles[] = 'css/compact-filters.css';
        }
        if ($settings['dashboard_tabs']) {
            $styles[] = 'css/dashboard-tabs.css';
        }
        if ($settings['chart_text']) {
            $styles[] = 'css/chart-text.css';
        }
        // JS is needed for native-theme/counter/tab/chart integration only.
        // RSS-only mode does not alter native dashboard behavior.
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mdoverhaul'] = ['js/dashboard-overhaul.js'];
    }

    if ($styles !== []) {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['mdoverhaul'] = array_merge(
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['mdoverhaul'],
            $styles
        );
    }
}

function plugin_version_mdoverhaul(): array
{
    return [
        'name' => 'Modern Dashboard Overhaul',
        'version' => PLUGIN_MDOVERHAUL_VERSION,
        'author' => 'Davide Montebello',
        'license' => 'MIT',
        'homepage' => '', // Internal development: not on GitHub or Marketplace.
        'requirements' => ['glpi' => ['min' => '11.0.0', 'max' => '11.1.0']],
    ];
}

function plugin_mdoverhaul_check_prerequisites(): bool
{
    if (Plugin::isPluginActive('arensecurity')) {
        echo 'Disabilitare arensecurity prima di attivare mdoverhaul.';
        return false;
    }
    return version_compare(GLPI_VERSION, '11.0.0', '>=')
        && version_compare(GLPI_VERSION, '11.1.0', '<');
}

function plugin_mdoverhaul_check_config(bool $verbose = false): bool
{
    if (Plugin::isPluginActive('arensecurity')) {
        if ($verbose) {
            echo 'Disabilitare arensecurity: non attivare entrambi i plugin.';
        }
        return false;
    }
    return true;
}
