<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use ProjectTask;
use Session;

/**
 * Project-only native state transitions; never synthesize statuses from percent_done.
 */
final class TaskStageService
{
    public static function stage(array $task, bool $preferNativeState = false): string
    {
        if (($task['source'] ?? 'project') === 'standalone') {
            return [1 => 'todo', 2 => 'doing', 3 => 'done'][(int) ($task['state_id'] ?? 1)] ?? 'todo';
        }
        if ($preferNativeState && (int) ($task['state_id'] ?? 0) > 0) {
            $stateId = (int) $task['state_id'];
            foreach (self::projectStageTargets() as $stage => $id) {
                if ($id === $stateId) { return $stage; }
            }
        }
        $done = (int) ($task['percent_done'] ?? 0);
        return $done >= 100 ? 'done' : ($done <= 0 ? 'todo' : 'doing');
    }

    public static function labels(): array
    {
        return ['todo' => 'To do', 'doing' => 'Doing', 'done' => 'Done'];
    }

    /**
     * Match shared Kanban stages to *real* native GLPI ProjectStates.
     * Prefer the three mappings explicitly configured under GLPI core; fall
     * back to unique, conventional native state names only when unambiguous.
     * Do not invent arbitrary IDs or write directly to core tables.
     *
     * @return array<string,int> stage => existing ProjectState ID
     */
    public static function projectStageTargets(): array
    {
        $states = TaskBoard::projectStates();
        $core = \Config::getConfigurationValues('core');
        $keys = [
            'todo' => 'projecttask_unstarted_states_id',
            'doing' => 'projecttask_inprogress_states_id',
            'done' => 'projecttask_completed_states_id',
        ];
        $aliases = [
            'todo' => ['new', 'to do', 'todo', 'not started', 'da fare', 'non iniziata', 'non iniziato', 'nuova', 'nuovo'],
            'doing' => ['processing', 'in progress', 'doing', 'working', 'in corso', 'in lavorazione'],
            'done' => ['done', 'completed', 'complete', 'finished', 'completata', 'completato', 'terminata', 'terminato'],
        ];
        $targets = [];
        foreach ($keys as $stage => $setting) {
            $id = (int) ($core[$setting] ?? 0);
            if ($id > 0 && isset($states[$id]) && !in_array($id, $targets, true)) {
                $targets[$stage] = $id;
            }
        }
        foreach ($aliases as $stage => $names) {
            if (isset($targets[$stage])) { continue; }
            $matches = [];
            foreach ($states as $id => $name) {
                $normalized = function_exists('mb_strtolower')
                    ? mb_strtolower(trim($name), 'UTF-8') : strtolower(trim($name));
                if (in_array($normalized, $names, true) && !in_array((int)$id, $targets, true)) {
                    $matches[] = (int)$id;
                }
            }
            if (count($matches) === 1) {
                $targets[$stage] = $matches[0];
            }
        }
        return $targets;
    }

    /**
     * A project using automatic percentage may still change its native state
     * where all three real ProjectState targets are unambiguous.
     */
    public static function canMoveMixedProject(array $fields): bool
    {
        // Shared All stages are safe only when each has an existing native ID.
        // Other installations use separate ProjectState columns instead.
        return count(self::projectStageTargets()) === 3;
    }

    public static function item(string $source,int $id): ?ProjectTask
    {
        if ($source !== 'project' || $id < 1 || !ProjectTask::canView()) return null;
        $item=new ProjectTask();
        return $item->getFromDB($id) && $item->can($id,READ) ? $item : null;
    }

    /**
     * @return array{status:int,ok:bool,error?:string,stage?:string,version?:string}
     */
    public static function move(string $source, int $id, string $stage, string $expected, string $mode = 'progress'): array
    {
        if ((int)Session::getLoginUserID()<1 || $source!=='project' || $id<1
            || !preg_match('/^state-[1-9][0-9]{0,9}$/D',$stage)
            || strlen($expected)>64 || $mode!=='state') {
            return ['status'=>400,'ok'=>false,'error'=>'invalid'];
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
                'percent_done'=>(int)($item->fields['percent_done'] ?? 0),
                'state_label'=>(string)(TaskBoard::projectStates()[$stateId] ?? '')];
        }
        return ['status'=>400,'ok'=>false,'error'=>'invalid'];
    }
}
