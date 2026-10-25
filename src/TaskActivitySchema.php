<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use DBConnection;
use RuntimeException;

/** Schema for task journal and private attachment metadata. */
final class TaskActivitySchema
{
    public static function install(): void
    {
        global $DB;
        $sign = DBConnection::getDefaultPrimaryKeySignOption();
        $charset = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $definitions = [
            TaskSchema::NOTES => [
                "source varchar(16) NOT NULL",
                "items_id int $sign NOT NULL",
                "users_id int $sign NOT NULL",
                "content mediumtext NOT NULL",
                "date_creation datetime NOT NULL",
                "is_deleted tinyint NOT NULL DEFAULT 0",
            ],
            TaskSchema::FILES => [
                "source varchar(16) NOT NULL",
                "items_id int $sign NOT NULL",
                "users_id int $sign NOT NULL",
                "name varchar(255) NOT NULL",
                "stored char(64) NOT NULL",
                "mime varchar(128) NOT NULL",
                "filesize bigint NOT NULL DEFAULT 0",
                "date_creation datetime NOT NULL",
                "is_deleted tinyint NOT NULL DEFAULT 0",
            ],
        ];
        foreach ($definitions as $table => $columns) {
            if (!$DB->tableExists($table)) {
                $sql = "CREATE TABLE `$table` (\n `id` int $sign NOT NULL AUTO_INCREMENT,\n";
                foreach ($columns as $column) {
                    $parts = explode(' ', $column, 2);
                    $sql .= " `{$parts[0]}` {$parts[1]},\n";
                }
                $sql .= " PRIMARY KEY (`id`), INDEX `parent` (`source`,`items_id`,`id`)"
                     . ") ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation";
                if (!$DB->doQuery($sql)) {
                    throw new RuntimeException('MDO activity schema upgrade failed');
                }
            }
            if (!$DB->fieldExists($table, 'source') || !$DB->fieldExists($table, 'items_id')
                || !$DB->fieldExists($table, 'id')) {
                throw new RuntimeException('MDO activity schema is incomplete');
            }
        }
    }
}
