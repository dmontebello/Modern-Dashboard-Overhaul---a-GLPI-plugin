<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use Dropdown;
use Project;
use ProjectTask;

/**
 * Read-only adapter for the optional Kanban/summary.
 * Search/list mode uses GLPI's native Search::show(ProjectTask::class).
 * Check item permissions, not only entity visibility, for every returned row.
 */
final class TaskBoard
{
    public const MAX_CANDIDATES = 1000;

    public static function load(): array
    {
        global $DB;
        $result = ['tasks' => [], 'scanned' => 0, 'truncated' => false];
        if (!ProjectTask::canView()) {
            return $result;
        }

        $projects = [];
        $states = [];
        // Bounded candidate scan: avoids disclosing unauthorized project counts.
        $candidates = $DB->request([
            'SELECT' => ['glpi_projecttasks.id'],
            'FROM' => 'glpi_projecttasks',
            'WHERE' => ['glpi_projecttasks.is_deleted' => 0],
            'ORDER' => ['glpi_projecttasks.id' => 'DESC'],
            'LIMIT' => self::MAX_CANDIDATES + 1,
        ]);

        foreach ($candidates as $row) {
            if ($result['scanned'] >= self::MAX_CANDIDATES) {
                $result['truncated'] = true;
                break;
            }
            $result['scanned']++;
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $task = new ProjectTask();
            if (!$task->getFromDB($id) || !$task->canViewItem()) {
                continue;
            }
            $fields = $task->fields;
            $projectId = (int) ($fields['projects_id'] ?? 0);
            if ($projectId < 1) {
                continue;
            }
            if (!array_key_exists($projectId, $projects)) {
                $project = new Project();
                $projects[$projectId] = $project->getFromDB($projectId)
                    ? (string) ($project->fields['name'] ?? '') : '';
            }
            $stateId = (int) ($fields['projecttaskstates_id'] ?? 0);
            if (!array_key_exists($stateId, $states)) {
                $states[$stateId] = $stateId > 0
                    ? (string) Dropdown::getDropdownName('glpi_projecttaskstates', $stateId)
                    : 'Senza stato';
                if ($states[$stateId] === '') {
                    $states[$stateId] = 'Stato #' . $stateId;
                }
            }
            $result['tasks'][] = [
                'id' => $id,
                'name' => (string) ($fields['name'] ?? ''),
                'project_id' => $projectId,
                'project' => $projects[$projectId],
                'state_id' => $stateId,
                'state' => $states[$stateId],
                'percent_done' => max(0, min(100, (int) ($fields['percent_done'] ?? 0))),
                'due' => (string) ($fields['plan_end_date'] ?? ''),
                'description' => self::description((string) ($fields['content'] ?? '')),
            ];
        }
        return $result;
    }

    public static function filter(array $tasks, string $query, string $filter, int $stateId, int $projectId): array
    {
        $query = trim($query);
        return array_values(array_filter($tasks, static function (array $task) use ($query, $filter, $stateId, $projectId): bool {
            if ($stateId >= 0 && $task['state_id'] !== $stateId) {
                return false;
            }
            if ($projectId > 0 && $task['project_id'] !== $projectId) {
                return false;
            }
            if ($filter === 'open' && $task['percent_done'] >= 100) {
                return false;
            }
            if ($filter === 'done' && $task['percent_done'] < 100) {
                return false;
            }
            if ($filter === 'overdue' && !self::isOverdue($task)) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $text = implode(' ', [$task['name'], $task['project'], $task['state'], $task['description'], (string) $task['id']]);
            return (function_exists('mb_stripos') ? mb_stripos($text, $query) : stripos($text, $query)) !== false;
        }));
    }

    public static function isOverdue(array $task): bool
    {
        if (($task['percent_done'] ?? 0) >= 100) {
            return false;
        }
        $due = (string) ($task['due'] ?? '');
        $timestamp = ($due !== '' && $due !== '0000-00-00 00:00:00') ? strtotime($due) : false;
        return $timestamp !== false && $timestamp < time();
    }

    public static function description(string $richText): string
    {
        $withoutScripts = preg_replace('~<(?:script|style)\b[^>]*>.*?</(?:script|style)>~is', '', $richText) ?? '';
        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($withoutScripts), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        return function_exists('mb_substr') ? mb_substr($plain, 0, 240) : substr($plain, 0, 240);
    }
}
