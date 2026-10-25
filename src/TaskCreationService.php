<?php
declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use ProjectTask;
use Session;
use Throwable;

/**
 * One create operation, two GLPI itemtypes. Never insert into core tables directly.
 * ProjectTask's project is mandatory; StandaloneTask never receives projects_id.
 */
final class TaskCreationService
{
    /** @return array{ok:bool,source?:string,id?:int,error?:string} */
    public static function create(array $data): array
    {
        if ((int) Session::getLoginUserID() < 1 || !Settings::get()['task_workspace']) {
            return self::error('forbidden');
        }
        $input = self::validate($data);
        if ($input === null) {
            return self::error('invalid');
        }

        $projectId = $input['projects_id'];
        if ($projectId > 0) {
            // No read-right shortcut: GLPI's native parent/update/create checks
            // decide whether this specific ProjectTask may be created.
            if (!TaskBoard::canCreateInProject($projectId)) {
                return self::error('forbidden');
            }
            $item = new ProjectTask();
            $values = [
                'projects_id' => $projectId,
                'name' => $input['name'],
                'content' => $input['content'],
            ];
            if ($input['due_date'] !== null) {
                $values['plan_end_date'] = $input['due_date'];
            }
            $source = 'project';
        } else {
            if (!StandaloneTask::canCreate()) {
                return self::error('forbidden');
            }
            $item = new StandaloneTask();
            $values = [
                'entities_id' => $input['entities_id'],
                'name' => $input['name'],
                'content' => $input['content'],
                'status' => $input['status'],
                'users_id_assign' => $input['users_id_assign'],
                'groups_id_assign' => $input['groups_id_assign'],
                'due_date' => $input['due_date'],
            ];
            $source = 'standalone';
        }

        try {
            // An item-specific, native GLPI permission check before add().
            // If it rejects, never fall back to the other itemtype.
            $item->check(-1, CREATE, $values);
            $created = $item->add($values);
            if (!is_int($created) && !is_string($created)) {
                return self::error('save_failed');
            }
            $id = filter_var($created, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                return self::error('save_failed');
            }
            return ['ok' => true, 'source' => $source, 'id' => $id];
        } catch (Throwable $error) {
            // Redirects must happen outside this try block (GLPI 11 RedirectException).
            \Toolbox::logInFile('php-errors', '[mdoverhaul unified create] ' . get_class($error) . "\n");
            return self::error('save_failed');
        }
    }

    /**
     * Narrow mapping: do not treat standalone status as projectstates_id, or
     * users_id_assign/groups_id_assign as ProjectTaskTeam fields.
     *
     * @return array{projects_id:int,name:string,content:string,due_date:?string,entities_id:int,status:int,users_id_assign:int,groups_id_assign:int}|null
     */
    public static function validate(array $raw): ?array
    {
        $name = $raw['name'] ?? null;
        $content = $raw['content'] ?? '';
        $rawDue = $raw['due_date'] ?? '';
        if (!is_string($name) || trim($name) === ''
            || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 255
            || !is_string($content) || strlen($content) > 100000 || !is_string($rawDue)) {
            return null;
        }
        $projectId = self::unsignedId($raw['projects_id'] ?? '');
        $entity = self::unsignedId($raw['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0));
        $status = self::unsignedId($raw['status'] ?? 1);
        $userId = self::unsignedId($raw['users_id_assign'] ?? 0);
        $groupId = self::unsignedId($raw['groups_id_assign'] ?? 0);
        if ($projectId === null || $entity === null || $status === null
            || $userId === null || $groupId === null || !isset(StandaloneTask::statuses()[$status])) {
            return null;
        }

        $date = null;
        if ($rawDue !== '') {
            if (strlen($rawDue) > 19
                || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}(?::[0-9]{2})?$/D', $rawDue)) {
                return null;
            }
            $date = str_replace('T', ' ', $rawDue);
            if (strlen($date) === 16) {
                $date .= ':00';
            }
            // Use GLPI/PHP's request timezone, not an invented UTC conversion.
            if (TaskBoard::dateTimestamp($date) === null) {
                return null;
            }
        }
        return [
            'projects_id' => $projectId,
            'name' => trim($name),
            'content' => $content,
            'due_date' => $date,
            'entities_id' => $entity,
            'status' => $status,
            'users_id_assign' => $userId,
            'groups_id_assign' => $groupId,
        ];
    }

    private static function unsignedId(mixed $value): ?int
    {
        if ($value === '') {
            return 0;
        }
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $text = (string) $value;
        if ($text === '' || strlen($text) > 10 || !ctype_digit($text)) {
            return null;
        }
        $parsed = filter_var($text, FILTER_VALIDATE_INT);
        return $parsed === false || $parsed < 0 ? null : $parsed;
    }

    /** @return array{ok:false,error:string} */
    private static function error(string $reason): array
    {
        return ['ok' => false, 'error' => $reason];
    }
}
