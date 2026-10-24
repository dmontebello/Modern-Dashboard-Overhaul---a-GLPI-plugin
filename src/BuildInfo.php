<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

/** Public provenance only. Never runs Git or reads credentials at runtime. */
final class BuildInfo
{
    public static function version(): string
    {
        if (defined('PLUGIN_MDOVERHAUL_VERSION')) {
            return (string) PLUGIN_MDOVERHAUL_VERSION;
        }
        // Standalone tools/tests can read the single source without bootstrapping GLPI.
        preg_match("/define\('PLUGIN_MDOVERHAUL_VERSION', '([^']+)'\)/", (string) file_get_contents(__DIR__ . '/../setup.php'), $match);
        return $match[1] ?? 'unknown';
    }

    public static function get(): array
    {
        $base = ['version' => self::version(), 'channel' => 'Dev', 'source_commit' => null,
            'build_id' => null, 'built_at' => null, 'source_state' => 'unbuilt'];
        $path = __DIR__ . '/../build-info.json';
        if (!is_file($path)) {
            return $base;
        }
        $info = json_decode((string) file_get_contents($path), true);
        if (!is_array($info) || ($info['version'] ?? '') !== $base['version']) {
            return $base;
        }
        foreach (['source_commit' => '/^[a-f0-9]{40}$/D', 'build_id' => '/^(?:[0-9]+(?:\.[0-9]+)?|local)$/D',
            'built_at' => '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/D', 'source_state' => '/^(?:clean|dirty)$/D'] as $key => $pattern) {
            if (is_string($info[$key] ?? null) && preg_match($pattern, $info[$key])) {
                $base[$key] = $info[$key];
            }
        }
        return $base;
    }
}
