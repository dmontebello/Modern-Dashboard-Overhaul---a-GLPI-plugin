<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use CommonGLPI;
use Profile;
use Session;

/** Native Profile rights matrix and native Profile POST/CSRF handling. */
final class TaskProfile extends CommonGLPI
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        return $item instanceof Profile && Session::haveRight('profile', READ) ? 'MDO · ' . StandaloneTask::getTypeName(2) : '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (!$item instanceof Profile || !Session::haveRight('profile', READ)) { return false; }
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (!TaskSchema::isReady()) { echo '<p>' . $esc(StandaloneTask::text('Aggiornare lo schema del plugin.', 'Upgrade the plugin schema.')) . '</p>'; return true; }
        echo '<form method="post" action="' . $esc(Profile::getFormURL()) . '">';
        echo '<input type="hidden" name="id" value="' . (int)$item->getID() . '">';
        echo '<input type="hidden" name="_glpi_csrf_token" value="' . $esc(Session::getNewCSRFToken()) . '">';
        $item->displayRightsChoiceMatrix([['itemtype'=>StandaloneTask::class,'label'=>StandaloneTask::getTypeName(2),'field'=>StandaloneTask::$rightname]],
            ['canedit'=>Session::haveRight('profile', UPDATE),'title'=>'Modern Dashboard Overhaul']);
        if (Session::haveRight('profile', UPDATE)) { echo '<button class="btn btn-primary" name="update" value="1">' . $esc(__('Save')) . '</button>'; }
        echo '</form>';
        return true;
    }
}
