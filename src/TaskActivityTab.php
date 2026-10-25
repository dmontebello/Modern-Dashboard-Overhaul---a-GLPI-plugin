<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use CommonGLPI;
use ProjectTask;

final class TaskActivityTab extends CommonGLPI
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        return $item instanceof ProjectTask && TaskActivity::canRead('project', (int)$item->getID())
            ? StandaloneTask::text('Diario MDO', 'MDO activity') : '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0): bool
    {
        if (!$item instanceof ProjectTask || !TaskActivity::canRead('project',(int)$item->getID())) { return false; }
        global $CFG_GLPI;
        $url=rtrim((string)$CFG_GLPI['root_doc'],'/').
            '/plugins/mdoverhaul/front/task-activity.php?source=project&id='.(int)$item->getID();
        echo '<a class="btn btn-primary" href="'.htmlspecialchars($url,ENT_QUOTES).'">'.
            htmlspecialchars(StandaloneTask::text('Apri diario e allegati','Open updates and attachments'),ENT_QUOTES).'</a>';
        return true;
    }
}
