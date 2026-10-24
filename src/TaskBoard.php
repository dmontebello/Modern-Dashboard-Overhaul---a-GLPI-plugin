<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use Dropdown;
use Project;
use ProjectTask;
use Session;

/**
 * Read-only adapter for the optional Kanban/summary.
 * Search/list mode uses GLPI's public Search facade with a workspace target.
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
            'SELECT' => ['glpi_projecttasks.*'],
            'FROM' => 'glpi_projecttasks',
            'WHERE' => [
                'glpi_projecttasks.is_deleted' => 0,
                'glpi_projecttasks.is_template' => 0,
            ] + getEntitiesRestrictCriteria('glpi_projecttasks', '', '', 'auto'),
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
            // Hydrate the complete native row once; can() also honours ITEM_CAN hooks.
            $task->getFromResultSet($row);
            $task->post_getFromDB(); // Loads the native task team used by canViewItem().
            if (!$task->can($id, READ)) {
                continue;
            }
            $fields = $task->fields;
            $projectId = (int) ($fields['projects_id'] ?? 0);
            if ($projectId < 1) {
                continue;
            }
            if (!array_key_exists($projectId, $projects)) {
                $project = new Project();
                $valid = $project->getFromDB($projectId)
                    && empty($project->fields['is_deleted'])
                    && empty($project->fields['is_template'])
                    && Session::haveAccessToEntity($project->getEntityID(), $project->isRecursive());
                $readable = $valid && $project->can($projectId, READ);
                $projects[$projectId] = [
                    'valid' => $valid,
                    'readable' => $readable,
                    'name' => $readable ? (string) ($project->fields['name'] ?? '') : '',
                ];
            }
            if (!$projects[$projectId]['valid']) {
                continue;
            }
            $stateId = (int) ($fields['projectstates_id'] ?? 0);
            if (!array_key_exists($stateId, $states)) {
                $states[$stateId] = $stateId > 0
                    ? (string) Dropdown::getDropdownName('glpi_projectstates', $stateId)
                    : '';
            }
            $result['tasks'][] = [
                'id' => $id,
                'name' => (string) ($fields['name'] ?? ''),
                'project_id' => $projectId,
                'project' => $projects[$projectId]['name'],
                'project_readable' => $projects[$projectId]['readable'],
                'state_id' => $stateId,
                'state' => $states[$stateId],
                'percent_done' => max(0, min(100, (int) ($fields['percent_done'] ?? 0))),
                'due' => (string) ($fields['plan_end_date'] ?? ''),
                'real_end' => (string) ($fields['real_end_date'] ?? ''),
                'description' => self::description((string) ($fields['content'] ?? '')),
            ];
        }
        return $result;
    }

    /** Bounded choices for the native creation form; never create a project here. */
    public static function creationProjects(): array
    {
        global $DB;
        $result = ['projects' => [], 'truncated' => false];
        if (!ProjectTask::canCreate() || !Project::canView()) {
            return $result;
        }
        $rows = $DB->request([
            'SELECT' => ['glpi_projects.*'],
            'FROM' => 'glpi_projects',
            'WHERE' => ['glpi_projects.is_deleted' => 0, 'glpi_projects.is_template' => 0]
                + getEntitiesRestrictCriteria('glpi_projects', '', '', 'auto'),
            'ORDER' => ['glpi_projects.id' => 'DESC'],
            'LIMIT' => self::MAX_CANDIDATES + 1,
        ]);
        $scanned = 0;
        foreach ($rows as $row) {
            if ($scanned++ >= self::MAX_CANDIDATES) {
                $result['truncated'] = true;
                break;
            }
            $project = new Project();
            $project->getFromResultSet($row);
            $project->post_getFromDB();
            $id = (int) ($row['id'] ?? 0);
            $input = ['projects_id' => $id];
            $task = new ProjectTask();
            if ($id > 0 && $project->can($id, READ) && $project->can($id, UPDATE)
                && $task->can(-1, CREATE, $input)) {
                $result['projects'][$id] = (string) ($row['name'] ?? '');
            }
        }
        asort($result['projects'], SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    /** A known project is checked directly, even if absent from the bounded chooser. */
    public static function canCreateInProject(int $projectId): bool
    {
        if ($projectId < 1 || !ProjectTask::canCreate()) {
            return false;
        }
        $project = new Project();
        $task = new ProjectTask();
        $input = ['projects_id' => $projectId];
        return $project->getFromDB($projectId)
            && empty($project->fields['is_deleted']) && empty($project->fields['is_template'])
            // Native CommonDBChild checks the parent's update right and entity.
            // Requiring Project READ as well incorrectly rejects native creation.
            && $task->can(-1, CREATE, $input);
    }

    public static function filter(array $tasks, string $query, string $filter, int $stateId, int $projectId, string $dueFilter = 'all'): array
    {
        $query = trim($query);
        // Use the same request clock and PHP timezone as GLPI's native dates.
        $today = (new \DateTimeImmutable(Session::getCurrentTime() ?? 'now'))->setTime(0, 0);
        return array_values(array_filter($tasks, static function (array $task) use ($query, $filter, $stateId, $projectId, $dueFilter, $today): bool {
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
            $due = self::dateTimestamp((string) ($task['due'] ?? ''));
            if (($dueFilter === 'overdue' && !self::isOverdue($task))
                || ($dueFilter === 'none' && $due !== null)
                || ($dueFilter === 'today' && ($due === null || $due < $today->getTimestamp() || $due >= $today->modify('+1 day')->getTimestamp()))
                || ($dueFilter === 'week' && ($due === null || $due < $today->getTimestamp() || $due >= $today->modify('+7 days')->getTimestamp()))) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $text = implode(' ', [$task['name'], $task['project'], $task['state'], $task['description'], (string) $task['id']]);
            return (function_exists('mb_stripos') ? mb_stripos($text, $query) : stripos($text, $query)) !== false;
        }));
    }

    /** Sort only the authorised in-memory sample; missing deadlines come last. */
    public static function sort(array $tasks, string $sort): array
    {
        usort($tasks, static function (array $a, array $b) use ($sort): int {
            $order = match ($sort) {
                'name', 'project', 'state' => strnatcasecmp((string) $a[$sort], (string) $b[$sort]),
                'progress' => $a['percent_done'] <=> $b['percent_done'],
                default => (self::dateTimestamp($a['due']) ?? PHP_INT_MAX) <=> (self::dateTimestamp($b['due']) ?? PHP_INT_MAX),
            };
            return $order ?: (strnatcasecmp($a['name'], $b['name']) ?: ($a['id'] <=> $b['id']));
        });
        return $tasks;
    }

    public static function isOverdue(array $task): bool
    {
        if (($task['percent_done'] ?? 0) >= 100) {
            return false;
        }
        $timestamp = self::dateTimestamp((string) ($task['due'] ?? ''));
        $now = self::dateTimestamp((string) Session::getCurrentTime()) ?? time();
        return $timestamp !== null && $timestamp < $now;
    }

    public static function dateTimestamp(string $date): ?int
    {
        if (!preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}(?: [0-9]{2}:[0-9]{2}:[0-9]{2})?$/D', $date)) {
            return null;
        }
        $format = strlen($date) === 10 ? '!Y-m-d' : '!Y-m-d H:i:s';
        $parsed = \DateTimeImmutable::createFromFormat($format, $date);
        $errors = \DateTimeImmutable::getLastErrors();
        return $parsed !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            ? $parsed->getTimestamp() : null;
    }

    public static function description(string $richText): string
    {
        $withoutScripts = preg_replace('~<(?:script|style)\b[^>]*>.*?</(?:script|style)>~is', '', $richText) ?? '';
        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($withoutScripts), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        return function_exists('mb_substr') ? mb_substr($plain, 0, 240) : substr($plain, 0, 240);
    }
}
