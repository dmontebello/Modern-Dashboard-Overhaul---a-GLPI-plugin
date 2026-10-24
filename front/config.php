<?php

declare(strict_types=1);

use GlpiPlugin\Mdoverhaul\Settings;
use GlpiPlugin\Mdoverhaul\BuildInfo;

// In GLPI 11 /front/ plugin scripts are initialized by the central web router.
require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/BuildInfo.php';

Session::checkRight('config', UPDATE);
if (!Plugin::isPluginActive('mdoverhaul')) {
    Html::displayNotFoundError();
    return;
}

$isItalian = str_starts_with(strtolower((string) (Session::getLanguage() ?? 'en')), 'it');
$i18n = static function (string $it, string $en) use ($isItalian): string {
    return $isItalian ? $it : $en;
};
$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

global $CFG_GLPI;
$url = rtrim((string) $CFG_GLPI['root_doc'], '/') . '/plugins/mdoverhaul/front/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!isset($_POST['save']) || $_POST['save'] !== '1') {
            throw new InvalidArgumentException('Operazione non valida.');
        }
        Settings::save($_POST);
        Session::addMessageAfterRedirect($i18n('Impostazioni salvate.', 'Settings saved.'), true, INFO);
    } catch (InvalidArgumentException) {
        Session::addMessageAfterRedirect($i18n('Valori non validi: nessuna modifica salvata.', 'Invalid values: no changes saved.'), true, ERROR);
    } catch (Throwable) {
        // Do not disclose database/internal details through the web UI.
        Session::addMessageAfterRedirect($i18n('Salvataggio non riuscito. Consultare i log GLPI.', 'Save failed. Check the GLPI logs.'), true, ERROR);
    }
    Html::redirect($url); // POST/Redirect/GET, prevents accidental resubmission.
    return;
}

$settings = Settings::get();
Html::header('Modern Dashboard Overhaul', $url, 'config', 'plugin');

$fields = [
    'dashboard_restyling' => [
        $i18n('Dashboard restyling', 'Dashboard restyling'),
        $i18n('Colori e proporzioni dei widget nativi, tema chiaro/scuro e layout responsive.', 'Native widget colors and proportions, light/dark themes and responsive layout.'),
        false,
    ],
    'compact_empty_filters' => [
        $i18n('Compatta i filtri vuoti', 'Compact empty filters'),
        $i18n('Nasconde lo spazio dei filtri soltanto quando non contiene filtri e non stai modificando la dashboard.', 'Hides an empty filter area outside edit mode.'),
        true,
    ],
    'dashboard_tabs' => [
        $i18n('Dashboard affiancate', 'Horizontal dashboard tabs'),
        $i18n('Sostituisce visivamente il menu a discesa con schede scorrevoli, conservando il pulsante + nativo.', 'Shows scrollable tabs instead of the dropdown while keeping the native + button.'),
        true,
    ],
    'chart_text' => [
        $i18n('Testi dei grafici', 'Chart typography'),
        $i18n('Adatta contrasto e dimensioni dei testi dei grafici supportati, senza cambiare dati e serie.', 'Adapts supported chart labels without changing series or values.'),
        true,
    ],
    'task_workspace' => [
        $i18n('Tasks Workspace', 'Tasks Workspace'),
        $i18n('Abilita Assistance → Tasks: ricerca nativa GLPI e Kanban di ProjectTask in sola lettura. Non crea ticket.', 'Enable Assistance → Tasks: native GLPI search and a read-only ProjectTask Kanban. No tickets are created.'),
        false,
    ],
    'kanban_restyling' => [
        $i18n('Kanban restyling', 'Kanban restyling'),
        $i18n('Migliora le colonne e le card del Kanban nativo GLPI senza cambiare trascinamento e dati.', 'Improves native GLPI Kanban columns and cards without changing drag-and-drop or data.'),
        false,
    ],
    'rss_widgets' => [
        $i18n('Widget RSS', 'RSS widgets'),
        $i18n('Crea widget per i feed RSS GLPI attivi e visibili. Disattivandolo, i widget RSS salvati potrebbero non essere visualizzati; i dati non vengono eliminati.', 'Shows widgets for available GLPI RSS feeds. Disabling may hide existing RSS cards but does not delete any data.'),
        false,
    ],
];

echo '<div class="mdo-settings container-xl">';
echo '<div class="card mdo-settings-card"><div class="card-body">';
echo '<div class="d-flex align-items-center gap-3 mb-3"><i class="ti ti-layout-dashboard fs-1 text-primary" aria-hidden="true"></i><div>';
echo '<h2 class="m-0">Modern Dashboard Overhaul</h2>';
echo '<p class="mdo-settings-version">' . $escape(BuildInfo::version()) . ' · Dev</p>';
echo '</div></div>';
$build = BuildInfo::get();
echo '<dl class="mdo-build-info">';
foreach (['source_commit' => $i18n('Commit sorgente', 'Source commit'), 'build_id' => 'GitHub Actions build', 'built_at' => $i18n('Data UTC', 'UTC date')] as $key => $label) {
    echo '<div><dt>' . $escape($label) . '</dt><dd>' . $escape((string) ($build[$key] ?? $i18n('Build non generata', 'Unbuilt checkout'))) . '</dd></div>';
}
echo '</dl>';
echo '<p class="mdo-settings-intro">' . $escape($i18n(
    'Le opzioni sono salvate nella configurazione nativa di GLPI. Nessuna nuova tabella e nessuna modifica ai widget o alle dashboard esistenti.',
    'Options are stored in GLPI native configuration. No extra tables and no changes to your dashboard data.'
)) . '</p>';
echo '<form method="post" action="' . $escape($url) . '">';
echo '<input type="hidden" name="_glpi_csrf_token" value="' . $escape(Session::getNewCSRFToken()) . '">';
echo '<input type="hidden" name="save" value="1">';

foreach ($fields as $key => [$title, $desc, $dependent]) {
    $id = 'mdo-setting-' . $key;
    $checked = $settings[$key] ? ' checked' : '';
    $class = $dependent ? ' mdo-setting-child' : ' mdo-setting-module';
    echo '<div class="mdo-setting-row' . $class . '">';
    echo '<div class="mdo-setting-description">';
    if (!$dependent) {
        echo '<span class="mdo-module-label">' . $escape($i18n('Modulo', 'Module')) . '</span>';
    }
    echo '<label for="' . $escape($id) . '" class="mdo-setting-title">' . $escape($title) . '</label>';
    echo '<p class="mdo-setting-detail">' . $escape($desc) . '</p>';
    echo '</div><div class="form-check form-switch mdo-setting-control">';
    echo '<input type="hidden" name="' . $escape($key) . '" value="0">';
    echo '<input class="form-check-input" type="checkbox" role="switch" id="' . $escape($id)
        . '" name="' . $escape($key) . '" value="1" aria-label="' . $escape($title) . '"' . $checked . '>';
    echo '</div></div>';
}

echo '<div class="mdo-settings-actions">';
echo '<button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-2" aria-hidden="true"></i>'
    . $escape($i18n('Salva impostazioni', 'Save settings')) . '</button>';
echo '<span class="text-secondary small">' . $escape($i18n(
    'Le sotto-opzioni Restyling si applicano soltanto quando il modulo è attivo. Ricarica la dashboard dopo il salvataggio.',
    'Restyling sub-options take effect only when the module is enabled. Reload your dashboard after saving.'
)) . '</span></div>';
echo '</form></div></div></div>';

Html::footer();
