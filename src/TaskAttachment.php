<?php
declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use RuntimeException;
use Session;

/** Private GLPI storage, never expose direct paths or redirect to public files. */
final class TaskAttachment
{
    public const MAX_BYTES = 15728640;
    private const EXTENSIONS = ['pdf','png','jpg','jpeg','webp','txt','csv','docx','xlsx','pptx','odt','ods','zip'];

    private static function directory(): string
    {
        if (!defined('GLPI_DOC_DIR')) { throw new RuntimeException('GLPI private storage unavailable'); }
        $root = rtrim(GLPI_DOC_DIR, '/') . '/mdoverhaul';
        if (is_link($root)) { throw new RuntimeException('Invalid task storage'); }
        if (!is_dir($root) && !mkdir($root,0700,true) && !is_dir($root)) {
            throw new RuntimeException('Task storage not writable');
        }
        return $root;
    }

    /** Native upload only after a parent UPDATE check. */
    public static function add(string $source, int $id, array $upload): bool
    {
        global $DB;
        if (!TaskActivity::canWrite($source,$id)
            || ($upload['error'] ?? -1) !== UPLOAD_ERR_OK
            || !is_string($upload['tmp_name'] ?? null)
            || !is_string($upload['name'] ?? null)) { return false; }
        $size = (int) ($upload['size'] ?? 0);
        $path = $upload['tmp_name'];
        if ($size < 1 || $size > self::MAX_BYTES || !is_uploaded_file($path)) { return false; }
        $name = basename(str_replace('\\', '/', $upload['name']));
        $ext = strtolower((string)pathinfo($name,PATHINFO_EXTENSION));
        if ($name === '' || strlen($name)>255 || !in_array($ext,self::EXTENSIONS,true)
            || preg_match('/[\x00-\x1F\x7F]/', $name)) { return false; }
        $stored = bin2hex(random_bytes(32));
        $location = self::directory() . '/' . $stored;
        if (!move_uploaded_file($path,$location)) { return false; }
        chmod($location,0600);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($location);
        try {
            $ok = $DB->insert(TaskSchema::FILES, [
                'source'=>$source,'items_id'=>$id,
                'users_id'=>(int)Session::getLoginUserID(),
                'name'=>$name,'stored'=>$stored,
                'mime'=>is_string($mime)?substr($mime,0,128):'application/octet-stream',
                'filesize'=>$size,'date_creation'=>Session::getCurrentTime() ?: date('Y-m-d H:i:s'),
                'is_deleted'=>0,
            ]);
        } catch (\Throwable $e) {
            unlink($location);
            throw $e;
        }
        if (!$ok) { unlink($location); }
        return (bool)$ok;
    }

    public static function listing(string $source, int $id): array
    {
        global $DB;
        if (!TaskActivity::canRead($source,$id)) { return []; }
        $table = TaskSchema::FILES;
        $rows = $DB->request(['SELECT'=>[$table.'.id',$table.'.name',$table.'.filesize',$table.'.date_creation'],
            'FROM'=>$table,'WHERE'=>['source'=>$source,'items_id'=>$id,'is_deleted'=>0],
            'ORDER'=>[$table.'.id DESC'],'LIMIT'=>100]);
        $items=[];
        foreach ($rows as $row) { $items[]=$row; }
        return $items;
    }

    public static function get(int $fileId): ?array
    {
        global $DB;
        if ($fileId<1 || !TaskSchema::isReady()) { return null; }
        $rows=$DB->request(['FROM'=>TaskSchema::FILES,'WHERE'=>['id'=>$fileId,'is_deleted'=>0],'LIMIT'=>1]);
        foreach ($rows as $row) {
            if (!TaskActivity::canRead((string)$row['source'],(int)$row['items_id'])) { return null; }
            return $row;
        }
        return null;
    }

    public static function download(int $fileId): bool
    {
        $record=self::get($fileId);
        if ($record===null) { return false; }
        $stored=(string)($record['stored']??'');
        if (!preg_match('/^[a-f0-9]{64}$/D',$stored)) { return false; }
        $dir=self::directory();
        $path=$dir.'/'.$stored;
        if (is_link($path) || !is_file($path) || realpath(dirname($path))!==realpath($dir)) { return false; }
        $name=(string)$record['name'];
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="attachment"; filename*=UTF-8\'\''.rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Length: '.filesize($path));
        readfile($path);
        return true;
    }
}
