<?php

declare(strict_types=1);

namespace GlpiPlugin\Mdoverhaul;

use CommonGLPI;
use ProjectTask;

/**
 * Project task entry in Assistance. This is not a TicketTask, nor a new GLPI item type.
 * The menu only exists while the Tasks Workspace module is enabled.
 */
final class TaskMenu extends CommonGLPI
{
    public static function getTypeName($nb = 0): string
    {
        return 'Tasks';
    }

    public static function getIcon(): string
    {
        return 'ti ti-list-check';
    }

    public static function canView(): bool
    {
        return Settings::get()['task_workspace'] && (ProjectTask::canView() || StandaloneTask::canView());
    }

    public static function getMenuContent(): array|false
    {
        if (!Settings::get()['task_workspace'] || !self::canView()) {
            return false;
        }
        return [
            'title' => 'Tasks',
            'page'  => '/plugins/mdoverhaul/front/tasks.php',
            'icon'  => self::getIcon(),
            'links' => [
                'search' => '/plugins/mdoverhaul/front/tasks.php',
            ],
        ];
    }
}
