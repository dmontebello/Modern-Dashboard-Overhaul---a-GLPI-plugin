<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use Config;
use RuntimeException;

/** Only GLPI's administrative install/upgrade invokes this. Never from a page. */
final class TaskSchema
{
    public const TABLE = 'glpi_plugin_mdoverhaul_tasks';
    public const VERSION = '2';
    public const NOTES = 'glpi_plugin_mdoverhaul_tasknotes';
    public const FILES = 'glpi_plugin_mdoverhaul_taskfiles';
    private static ?bool $ready = null;
    public static function isReady(): bool
    {
        global $DB;
        if (self::$ready !== null) { return self::$ready; }
        if (($DB ?? null) === null
            || (string)(Config::getConfigurationValues(Settings::CONTEXT)['task_schema_version'] ?? '') !== self::VERSION) {
            return self::$ready = false;
        }
        foreach ([self::NOTES, self::FILES] as $linkedTable) {
            if (!$DB->tableExists($linkedTable)) { return self::$ready = false; }
            foreach (['id','source','items_id','users_id','date_creation','is_deleted'] as $required) {
                if (!$DB->fieldExists($linkedTable,$required)) { return self::$ready = false; }
            }
        }
        return self::$ready = true;
    }

    public static function install(): bool
    {
        global $DB, $GLPI_CACHE;
        self::$ready = null;
        // Never recreate or modify the retired independent-task table.
        // Existing data and profile grants are retained for external export.
        require_once __DIR__ . '/TaskActivitySchema.php';
        TaskActivitySchema::install();
        if (isset($GLPI_CACHE)) { $GLPI_CACHE->set('all_possible_rights', []); }
        Config::setConfigurationValues(Settings::CONTEXT, ['task_schema_version' => self::VERSION]);
        self::$ready = null;
        if (!self::isReady()) { throw new RuntimeException('Task activity schema upgrade incomplete'); }
        return true;
    }
}
