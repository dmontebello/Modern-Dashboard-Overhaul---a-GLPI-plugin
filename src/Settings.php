<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use Config;
use InvalidArgumentException;

/**
 * Native GLPI configuration, with zero plugin-owned SQL tables.
 * No filesystem writes: compatible with a Docker read-only bind mount.
 */
final class Settings
{
    public const CONTEXT = 'plugin:mdoverhaul';
    /** Memoize within one PHP request; never cache settings across users/requests. */
    private static ?array $cached = null;

    /** Configuration key -> legacy 0.5 file fallback key. */
    private const KEYS = [
        'dashboard_restyling' => 'enabled',
        'rss_widgets' => 'rss_widgets',
        'compact_empty_filters' => 'compact_empty_filters',
        'dashboard_tabs' => 'dashboard_tabs',
        'chart_text' => 'chart_text',
        'task_workspace' => 'task_workspace',
        'kanban_restyling' => 'kanban_restyling',
    ];

    public static function defaults(): array
    {
        $defaults = array_fill_keys(array_keys(self::KEYS), true);
        $defaults['task_workspace'] = false;
        $defaults['kanban_restyling'] = false;
        $path = __DIR__ . '/../config/ui.php';
        $old = is_file($path) ? require $path : [];
        if (!is_array($old)) {
            $old = [];
        }
        $local = __DIR__ . '/../config/ui.local.php';
        if (is_file($local)) {
            $override = require $local;
            if (is_array($override)) {
                $old = array_replace($old, array_intersect_key($override, $old));
            }
        }
        foreach (self::KEYS as $key => $legacy) {
            if (isset($old[$legacy]) && is_bool($old[$legacy])) {
                $defaults[$key] = $old[$legacy];
            }
        }
        return $defaults;
    }

    /** @return array<string, bool> Only the five supported switches. */
    public static function get(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $values = self::defaults();
        $stored = Config::getConfigurationValues(self::CONTEXT);
        if (!is_array($stored)) {
            return self::$cached = $values;
        }
        foreach ($values as $key => $default) {
            if (!array_key_exists($key, $stored)) {
                continue;
            }
            $parsed = self::parseBoolean($stored[$key]);
            if ($parsed !== null) {
                $values[$key] = $parsed;
            }
        }
        return self::$cached = $values;
    }

    /**
     * Strict allowlist validation; invalid forms are rejected without a partial write.
     * The legacy CSRF protection is enforced by GLPI 11's request middleware.
     */
    public static function save(array $input): void
    {
        $values = [];
        foreach (self::KEYS as $key => $_legacy) {
            // Values are emitted as the strings 0/1 by the settings form.
            if (!isset($input[$key]) || !is_string($input[$key])
                || !in_array($input[$key], ['0', '1'], true)) {
                throw new InvalidArgumentException('Impostazioni incomplete o non valide.');
            }
            $values[$key] = $input[$key];
        }
        Config::setConfigurationValues(self::CONTEXT, $values);
        self::$cached = null;
    }

    private static function parseBoolean(mixed $value): ?bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return false;
        }
        return null;
    }
}
