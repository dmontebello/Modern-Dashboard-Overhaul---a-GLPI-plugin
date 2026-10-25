<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use Config;
use DBConnection;
use Migration;
use RuntimeException;

/** Only GLPI's administrative install/upgrade invokes this. Never from a page. */
final class TaskSchema
{
    public const TABLE = 'glpi_plugin_mdoverhaul_tasks';
    public const VERSION = '2';
    public const NOTES = 'glpi_plugin_mdoverhaul_tasknotes';
    public const FILES = 'glpi_plugin_mdoverhaul_taskfiles';
    private static ?bool $ready = null;
    private const FIELDS = [
        'entities_id' => ['fkey', 0], 'name' => ['string', ''], 'content' => ['mediumtext', null],
        'users_id_owner' => ['fkey', 0], 'users_id_assign' => ['fkey', 0],
        'groups_id_assign' => ['fkey', 0], 'status' => ['integer', 1],
        'due_date' => ['datetime', null], 'date_creation' => ['datetime', null],
        'date_mod' => ['datetime', null], 'is_deleted' => ['bool', 0],
    ];

    public static function isReady(): bool
    {
        global $DB;
        if (self::$ready !== null) { return self::$ready; }
        if (($DB ?? null) === null || !$DB->tableExists(self::TABLE)
            || (string) (Config::getConfigurationValues(Settings::CONTEXT)['task_schema_version'] ?? '') !== self::VERSION) {
            return self::$ready = false;
        }
        foreach (array_merge(['id'], array_keys(self::FIELDS)) as $field) {
            if (!$DB->fieldExists(self::TABLE, $field)) { return self::$ready = false; }
        }
        foreach ([self::NOTES, self::FILES] as $linkedTable) {
            if (!$DB->tableExists($linkedTable)) { return self::$ready = false; }
        }
        return self::$ready = true;
    }

    public static function install(): bool
    {
        global $DB, $GLPI_CACHE;
        self::$ready = null;
        $table = self::TABLE;
        if (!$DB->tableExists($table)) {
            $sign = DBConnection::getDefaultPrimaryKeySignOption();
            $charset = DBConnection::getDefaultCharset();
            $collation = DBConnection::getDefaultCollation();
            $sql = "CREATE TABLE `$table` (
                `id` int $sign NOT NULL AUTO_INCREMENT,
                `entities_id` int $sign NOT NULL DEFAULT 0,
                `name` varchar(255) NOT NULL DEFAULT '', `content` mediumtext DEFAULT NULL,
                `users_id_owner` int $sign NOT NULL DEFAULT 0,
                `users_id_assign` int $sign NOT NULL DEFAULT 0,
                `groups_id_assign` int $sign NOT NULL DEFAULT 0,
                `status` int NOT NULL DEFAULT 1, `due_date` timestamp NULL DEFAULT NULL,
                `date_creation` timestamp NULL DEFAULT NULL, `date_mod` timestamp NULL DEFAULT NULL,
                `is_deleted` tinyint NOT NULL DEFAULT 0, PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation";
            if (!$DB->doQuery($sql)) { throw new RuntimeException('Unable to create standalone task schema'); }
        }
        if (!$DB->fieldExists($table, 'id')) {
            throw new RuntimeException('Existing task table has no primary ID; review schema before upgrade');
        }
        $migration = new Migration(BuildInfo::version());
        foreach (self::FIELDS as $field => [$type, $default]) {
            $migration->addField($table, $field, $type, ['value' => $default]);
        }
        foreach (['entities_id', 'users_id_owner', 'users_id_assign', 'groups_id_assign', 'status', 'due_date', 'is_deleted'] as $field) {
            $migration->addKey($table, $field);
        }
        // New rights start at zero for every profile; existing grants are preserved.
        $migration->addRight(StandaloneTask::$rightname, 0, []);
        require_once __DIR__ . '/TaskActivitySchema.php';
        TaskActivitySchema::install();
        $migration->executeMigration();
        if (isset($GLPI_CACHE)) { $GLPI_CACHE->set('all_possible_rights', []); }
        Config::setConfigurationValues(Settings::CONTEXT, ['task_schema_version' => self::VERSION]);
        self::$ready = null;
        if (!self::isReady()) { throw new RuntimeException('Standalone task upgrade incomplete'); }
        return true;
    }
}
