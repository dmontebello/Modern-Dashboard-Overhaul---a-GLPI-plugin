<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use ProjectTask;
use Session;

/**
 * A Kanban stage is a UI-level abstraction: standalone.status or the native
 * ProjectTask percent_done. Never overwrite projectstates_id with a fake state.
 */
final class TaskStageService
{
    public static function stage(array $task): string
    {
        if (($task['source'] ?? 'project') === 'standalone') {
            return [1 => 'todo', 2 => 'doing', 3 => 'done'][(int) ($task['state_id'] ?? 1)] ?? 'todo';
        }
        $done = (int) ($task['percent_done'] ?? 0);
        return $done >= 100 ? 'done' : ($done <= 0 ? 'todo' : 'doing');
    }

    public static function labels(): array
    {
        return ['todo' => 'To do', 'doing' => 'Doing', 'done' => 'Done'];
    }

    public static function item(string $source, int $id): ProjectTask|StandaloneTask|null
    {
        if ($id < 1 || !in_array($source, ['project', 'standalone'], true)) {
            return null;
        }
        $item = $source === 'project' ? new ProjectTask() : new StandaloneTask();
        if (($source === 'project' && !ProjectTask::canView())
            || ($source === 'standalone' && !StandaloneTask::canView())
            || !$item->getFromDB($id)
            || !$item->can($id, READ)) {
            return null;
        }
        return $item;
    }

    /**
     * @return array{status:int,ok:bool,error?:string,stage?:string,version?:string}
     */
    public static function move(string $source, int $id, string $stage, string $expected): array
    {
        if ((int) Session::getLoginUserID() < 1 || $id < 1
            || (!isset(self::labels()[$stage]) && !preg_match('/^state-[1-9][0-9]{0,9}$/D', $stage))
            || !in_array($source, ['project', 'standalone'], true)
            || ($source === 'standalone' && str_starts_with($stage, 'state-'))
            || strlen($expected) > 64) {
            return ['status' => 400, 'ok' => false, 'error' => 'invalid'];
        }

        $item = self::item($source, $id);
        if ($item === null || !empty($item->fields['is_deleted'])) {
            return ['status' => 404, 'ok' => false, 'error' => 'not_found'];
        }
        if (!$item->can($id, UPDATE)) {
            return ['status' => 403, 'ok' => false, 'error' => 'forbidden'];
        }
        $modified = (string) ($item->fields['date_mod'] ?? '');
        // Require the version from the rendered card, even when it is empty
        // for a legacy row without a native date_mod value.
        if ($modified !== $expected) {
            return ['status' => 409, 'ok' => false, 'error' => 'stale'];
        }

        if (str_starts_with($stage, 'state-')) {
            if ($source !== 'project') { return ['status'=>400,'ok'=>false,'error'=>'invalid']; }
            $stateId = (int)substr($stage, 6);
            if (!isset(TaskBoard::projectStates()[$stateId])) { return ['status'=>400,'ok'=>false,'error'=>'unknown_state']; }
            $changed = (int)($item->fields['projectstates_id'] ?? 0) !== $stateId;
            if ($changed && !$item->update(['id'=>$id,'projectstates_id'=>$stateId,'auto_projectstates'=>0])) {
                return ['status'=>409,'ok'=>false,'error'=>'update_failed'];
            }
            $item->getFromDB($id);
            if ((int)($item->fields['projectstates_id'] ?? 0) !== $stateId) {
                return ['status'=>409,'ok'=>false,'error'=>'native_state_mismatch'];
            }
            return ['status'=>200,'ok'=>true,'stage'=>$stage,
                'version'=>(string)($item->fields['date_mod'] ?? ''),
                'percent_done'=>(int)($item->fields['percent_done'] ?? 0)];
        }
        if ($source === 'standalone') {
            $wanted = array_search($stage, [1 => 'todo', 2 => 'doing', 3 => 'done'], true);
            $current = (int) ($item->fields['status'] ?? 0);
            $update = ['id' => $id, 'status' => $wanted];
        } else {
            // Native automatic progress cannot be manually overwritten.
            if (!empty($item->fields['auto_percent_done'])) {
                return ['status' => 409, 'ok' => false, 'error' => 'automatic_progress'];
            }
            $current = (int) ($item->fields['percent_done'] ?? 0);
            $wanted = match ($stage) {
                'todo' => 0,
                'doing' => $current > 0 && $current < 100 ? $current : 50,
                default => 100,
            };
            $update = ['id' => $id, 'percent_done' => $wanted];
        }
        if ($current !== (int) $wanted && !$item->update($update)) {
            return ['status' => 409, 'ok' => false, 'error' => 'update_failed'];
        }

        // A client must refresh its model after a successful native update.
        $item->getFromDB($id);
        $stageNow = $source === 'standalone'
            ? ([1 => 'todo', 2 => 'doing', 3 => 'done'][(int) ($item->fields['status'] ?? 0)] ?? '')
            : self::stage(['percent_done' => (int) ($item->fields['percent_done'] ?? 0)]);
        if ($stageNow !== $stage) {
            return ['status' => 409, 'ok' => false, 'error' => 'native_state_mismatch'];
        }
        return ['status' => 200, 'ok' => true, 'stage' => $stageNow,
            'version' => (string) ($item->fields['date_mod'] ?? ''),
            'percent_done' => $source === 'project' ? (int)($item->fields['percent_done'] ?? 0) : null];
    }
}
