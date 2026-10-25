<?php
declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use Session;

/** Append-only task journal, separate from the task's stable description. */
final class TaskActivity
{
    public const PAGE_SIZE = 20;

    private static function checked(string $source, int $id, int $right): ?object
    {
        if (!TaskSchema::isReady() || $id < 1) { return null; }
        $item = TaskStageService::item($source, $id);
        if ($item === null || !empty($item->fields['is_deleted'])) { return null; }
        return $item->can($id, $right) ? $item : null;
    }

    public static function canRead(string $source, int $id): bool
    {
        return self::checked($source, $id, READ) !== null;
    }

    public static function canWrite(string $source, int $id): bool
    {
        return self::checked($source, $id, UPDATE) !== null;
    }

    /** @return array{items:array,more:bool} */
    public static function page(string $source, int $id, int $start = 0): array
    {
        global $DB;
        if (!self::canRead($source, $id)) { return ['items'=>[], 'more'=>false]; }
        $table = TaskSchema::NOTES;
        $rows = $DB->request([
            'SELECT'=>[$table.'.*'], 'FROM'=>$table,
            'WHERE'=>['source'=>$source,'items_id'=>$id,'is_deleted'=>0],
            'ORDER'=>[$table.'.id DESC'],
            'START'=>min(100000, max(0,$start)),
            'LIMIT'=>self::PAGE_SIZE+1,
        ]);
        $items = [];
        foreach ($rows as $row) {
            if (count($items) > self::PAGE_SIZE) { break; }
            $items[] = $row;
        }
        return ['items'=>array_slice($items,0,self::PAGE_SIZE),
            'more'=>count($items)>self::PAGE_SIZE];
    }

    public static function add(string $source, int $id, string $content): bool
    {
        global $DB;
        if (!self::canWrite($source, $id)) { return false; }
        $content = trim($content);
        if ($content === '' || strlen($content)>10000) { return false; }
        return (bool) $DB->insert(TaskSchema::NOTES, [
            'source'=>$source, 'items_id'=>$id,
            'users_id'=>(int)Session::getLoginUserID(),
            'content'=>$content,
            'date_creation'=>Session::getCurrentTime() ?: date('Y-m-d H:i:s'),
            'is_deleted'=>0,
        ]);
    }
}
