<?php

namespace GlpiPlugin\Mdoverhaul;

use RSSFeed;
use Session;
use Throwable;

/** Read-only, dynamic cards. Existing card/type IDs remain valid after upgrade. */
final class Dashboard
{
    // Persisted keys are not plugin folder names. Keep the existing cards working
    // without rewriting dashboards or creating duplicate catalogue entries.
    private const WIDGET_TYPE = 'arensecurity_rss';
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 20;

    public static function getTypes(): array
    {
        // Presets use the native Widget type selector; GLPI stores the selection
        // in its existing dashboard configuration. No per-user plugin table.
        return [
            self::WIDGET_TYPE => self::type('RSS - 10 articoli (predefinito)', 'renderFeed'),
            self::WIDGET_TYPE . '_5' => self::type('RSS - 5 articoli', 'renderFive'),
            self::WIDGET_TYPE . '_15' => self::type('RSS - 15 articoli', 'renderFifteen'),
            self::WIDGET_TYPE . '_20' => self::type('RSS - 20 articoli', 'renderTwenty'),
        ];
    }

    private static function type(string $label, string $method): array
    {
        return ['label' => $label, 'function' => self::class . '::' . $method, 'image' => ''];
    }

    public static function getCards(): array
    {
        $cards = [];
        $types = array_keys(self::getTypes());
        foreach (self::getVisibleFeeds() as $id => $feed) {
            $cards['arensecurity_feed_' . $id] = [
                'widgettype' => $types,
                'label' => (string) $feed['name'],
                'group' => 'Modern Dashboard Overhaul - RSS',
                'provider' => self::class . '::provideFeed',
                'args' => [(int) $id],
                'cache' => false,
            ];
        }
        return $cards;
    }

    public static function provideFeed(int $feedId, array $params = []): array
    {
        $data = ['feed_id' => $feedId, 'title' => '', 'items' => [], 'message' => '',
            'timezone' => self::timezone()];
        $visible = self::getVisibleFeeds();
        if (!isset($visible[$feedId])) {
            $data['message'] = 'Feed non disponibile o accesso non autorizzato.';
            return $data;
        }
        $entry = $visible[$feedId];
        $data['title'] = (string) $entry['name'];
        try {
            // Same native parser/cache as the original tested 0.1.0 pilot.
            $rss = RSSFeed::getRSSFeed((string) $entry['url'], (int) $entry['refresh_rate']);
            if ($rss === false) {
                $data['message'] = 'RSS non raggiungibile. Verificare il feed in Strumenti.';
                return $data;
            }
            $data['items'] = array_slice(FeedFormatter::normalize(
                $rss->get_items(0, FeedFormatter::MAX_SCAN), $data['timezone']
            ), 0, self::MAX_LIMIT);
        } catch (Throwable) {
            $data['message'] = 'Impossibile leggere il feed. Verificarne contenuto e configurazione in Strumenti.';
            return $data;
        }
        if ($data['items'] === []) {
            $data['message'] = 'Nessun articolo disponibile in questo feed.';
        }
        return $data;
    }

    public static function renderFeed(array $params = []): string
    {
        return self::render($params, self::DEFAULT_LIMIT);
    }
    public static function renderFive(array $params = []): string
    {
        return self::render($params, 5);
    }
    public static function renderFifteen(array $params = []): string
    {
        return self::render($params, 15);
    }
    public static function renderTwenty(array $params = []): string
    {
        return self::render($params, 20);
    }

    private static function render(array $params, int $limit): string
    {
        $name = (string) ($params['title'] ?? 'RSS');
        $title = self::escape($name);
        $timezone = self::escape((string) ($params['timezone'] ?? self::timezone()));
        $all = is_array($params['items'] ?? null) ? $params['items'] : [];
        $items = array_slice(array_values(array_filter($all, 'is_array')), 0, $limit);
        $shown = count($items);
        $color = (string) ($params['color'] ?? '');
        $style = preg_match('/^#[0-9a-fA-F]{6}$/D', $color)
            ? ' style="--asw-accent:' . $color . '"' : '';
        // Global GLPI theme/palette is handled by CSS variables. These optional
        // native parameters also allow a dashboard-specific dark renderer.
        $dark = in_array($params['dark'] ?? false, [true, 1, '1'], true)
            || ($params['theme'] ?? '') === 'dark';
        $darkAttr = $dark ? ' data-asw-dark="1"' : '';
        $html = '<div class="card asw-feed" role="region" aria-label="' . $title . '"'
            . $darkAttr . $style . '>';
        $html .= '<div class="asw-header"><div class="asw-heading"><span class="asw-kicker">RSS FEED</span>'
            . '<h3 class="asw-name">' . $title . '</h3></div>';
        $html .= '<span class="asw-count" title="Articoli mostrati / limite selezionato">'
            . $shown . '/' . $limit . '</span></div>';
        $html .= '<div class="asw-body" tabindex="0" aria-label="Articoli del feed; area scorrevole">';
        if ($items === []) {
            $message = (string) ($params['message'] ?? 'Nessuna notizia disponibile.');
            $html .= '<div class="asw-empty" role="status"><i class="ti ti-rss-off" aria-hidden="true"></i>'
                . '<p class="asw-message">' . self::escape($message) . '</p></div>';
        } else {
            $html .= '<ol class="asw-list">';
            foreach ($items as $i => $item) {
                $label = self::escape((string) ($item['title'] ?? 'Articolo senza titolo'));
                $link = (string) ($item['link'] ?? '');
                $html .= '<li class="asw-item"><span class="asw-index" aria-hidden="true">'
                    . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '</span><div class="asw-content">';
                if (FeedFormatter::safeLink($link)) {
                    $html .= '<a class="asw-title" href="' . self::escape($link)
                        . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
                } else {
                    $html .= '<span class="asw-title">' . $label . '</span>';
                }
                $dates = is_array($item['dates'] ?? null) ? $item['dates'] : [];
                $html .= '<div class="asw-dates">';
                if ($dates === []) {
                    $html .= '<span>Data non indicata dalla fonte</span>';
                } else {
                    foreach ($dates as $date) {
                        if (!is_array($date)) {
                            continue;
                        }
                        $iso = (string) ($date['iso'] ?? '');
                        $attr = $iso !== '' ? ' datetime="' . self::escape($iso) . '"' : '';
                        $html .= '<span class="asw-date" title="' . self::escape((string) ($date['note'] ?? ''))
                            . '"><span class="asw-date-label">' . self::escape((string) ($date['label'] ?? 'Data'))
                            . '</span> <time' . $attr . '>' . self::escape((string) ($date['display'] ?? ''))
                            . '</time></span>';
                    }
                }
                $html .= '</div>';
                $cves = is_array($item['cves'] ?? null) ? $item['cves'] : [];
                // Do not repeat an ID in a badge when the entire title is that ID.
                if ($cves !== [] && !(count($cves) === 1 && strtoupper(trim((string) ($item['title'] ?? ''))) === $cves[0])) {
                    $html .= '<div class="asw-tags">';
                    foreach (array_slice($cves, 0, 3) as $cve) {
                        $html .= '<span class="asw-tag">' . self::escape((string) $cve) . '</span>';
                    }
                    if (count($cves) > 3) {
                        $html .= '<span class="asw-tag">+' . (count($cves) - 3) . '</span>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div></li>';
            }
            $html .= '</ol>';
        }
        $html .= '</div><div class="asw-footer"><span>Orari con fuso noto: ' . $timezone . '.</span>';
        if ($shown > 0 && $shown < $limit) {
            $html .= '<span>Il feed contiene ' . $shown . ' articoli distinti disponibili, su ' . $limit . ' richiesti.</span>';
        }
        $html .= '</div></div>';
        return $html;
    }

    private static function timezone(): string
    {
        // Respect user's GLPI timezone; otherwise use GLPI's PHP timezone (never hard-code an installation city).
        $value = $_SESSION['glpitimezone'] ?? date_default_timezone_get();
        return is_string($value) && in_array($value, timezone_identifiers_list(), true)
            ? $value : 'UTC';
    }

    private static function getVisibleFeeds(): array
    {
        global $DB;
        static $cache = [];
        $userId = Session::getLoginUserID();
        if (!$userId || !RSSFeed::canView()) {
            return [];
        }
        $key = (string) $userId . ':' . hash('sha256', serialize([
            $_SESSION['glpiactiveprofile'] ?? [], $_SESSION['glpiactiveentities'] ?? [],
        ]));
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $visible = [];
        $criteria = RSSFeed::getListCriteria(false);
        $criteria['WHERE']['glpi_rssfeeds.is_active'] = 1;
        $rss = new RSSFeed();
        foreach ($DB->request($criteria) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1 || !$rss->getFromDB($id)) {
                continue;
            }
            $fields = $rss->fields;
            if ((int) ($fields['is_active'] ?? 0) !== 1) {
                continue;
            }
            $isOwner = (int) ($fields['users_id'] ?? 0) === (int) $userId;
            $mayView = Session::haveRight('rssfeed_public', READ) && $rss->haveVisibilityAccess();
            if (!$isOwner && !$mayView) {
                continue;
            }
            $visible[$id] = [
                'name' => (string) ($fields['name'] ?? ('RSS #' . $id)),
                'url' => (string) ($fields['url'] ?? ''),
                'refresh_rate' => max(300, (int) ($fields['refresh_rate'] ?? 3600)),
            ];
        }
        $cache[$key] = $visible;
        return $visible;
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
