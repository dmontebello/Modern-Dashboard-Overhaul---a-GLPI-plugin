<?php

namespace GlpiPlugin\Mdoverhaul;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/** Bounded, read-only formatting of SimplePie items. No HTTP or storage here. */
final class FeedFormatter
{
    public const MAX_SCAN = 100;

    public static function normalize(iterable $items, string $timezone): array
    {
        $rows = [];
        $position = 0;
        foreach ($items as $item) {
            if ($position >= self::MAX_SCAN) {
                break;
            }
            $index = $position++;
            try {
                $title = self::plainText((string) $item->get_title());
                $url = trim((string) $item->get_permalink());
                $url = self::safeLink($url) ? $url : '';
                $title = $title !== '' ? $title : 'Articolo senza titolo';
                $dates = self::dates($item, $timezone);

                // CVE badges remain available for old dashboards, but a CVE is
                // NOT an article ID: several independent reports may cite it.
                preg_match_all('/\bCVE-(?:19|20)\d{2}-\d{4,}\b/i', $title, $matches);
                $cves = array_values(array_unique(array_map('strtoupper', $matches[0])));
                if ($cves === [] && $url !== '') {
                    preg_match_all('/\bCVE-(?:19|20)\d{2}-\d{4,}\b/i', $url, $matches);
                    $cves = array_values(array_unique(array_map('strtoupper', $matches[0])));
                }
                $guid = method_exists($item, 'get_id') ? trim((string) $item->get_id()) : '';
                $identity = $guid !== '' ? 'guid:' . substr($guid, 0, 512)
                    : ($url !== '' ? 'url:' . $url : 'title:' . strtolower($title));
                $sort = 0;
                foreach ($dates as $date) {
                    $sort = max($sort, $date['timestamp']);
                }
                $rows[] = [
                    'identity' => $identity,
                    'index' => $index,
                    'title' => $title,
                    'link' => $url,
                    'cves' => $cves,
                    'dates' => $dates,
                    'timestamp' => $sort,
                ];
            } catch (Throwable) {
                // One malformed entry must not break all dashboard cards.
                continue;
            }
        }
        usort($rows, static fn (array $a, array $b): int =>
            ($b['timestamp'] <=> $a['timestamp']) ?: ($a['index'] <=> $b['index']));
        $seen = [];
        $output = [];
        foreach ($rows as $row) {
            if (isset($seen[$row['identity']])) {
                continue;
            }
            $seen[$row['identity']] = true;
            unset($row['identity'], $row['index']);
            $output[] = $row;
        }
        return $output;
    }

    public static function safeLink(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\x7f]/', $url)
            || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && !isset($parts['user']) && !isset($parts['pass']);
    }

    private static function plainText(string $text): string
    {
        $text = substr($text, 0, 16384);
        $plain = trim(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if (strlen($plain) <= 1024) {
            return $plain;
        }
        // UTF-8-aware bound without requiring an additional PHP extension.
        return preg_match('/^.{0,1024}/us', $plain, $match) === 1
            ? $match[0] : substr($plain, 0, 1024);
    }

    private static function tag(object $item, array $keys): string
    {
        if (!method_exists($item, 'get_item_tags')) {
            return '';
        }
        foreach ($keys as [$ns, $key]) {
            $tags = $item->get_item_tags($ns, $key);
            $raw = is_array($tags) ? ($tags[0]['data'] ?? '') : '';
            if (is_string($raw) && trim($raw) !== '') {
                return trim($raw);
            }
        }
        return '';
    }

    /** Never substitute the refresh time for an article timestamp. */
    private static function dates(object $item, string $timezone): array
    {
        $published = self::tag($item, [
            ['http://www.w3.org/2005/Atom', 'published'],
            ['http://purl.org/atom/ns#', 'issued'],
            ['', 'pubDate'],
            ['http://purl.org/dc/elements/1.1/', 'date'],
        ]);
        $updated = self::tag($item, [
            ['http://www.w3.org/2005/Atom', 'updated'],
            ['http://purl.org/atom/ns#', 'modified'],
        ]);
        $out = [];
        foreach ([['Pub.', $published], ['Agg.', $updated]] as [$label, $raw]) {
            $date = self::parseDate($raw, $label, $timezone);
            if ($date !== null) {
                // When both dates are identical, publication is sufficient.
                if ($out !== [] && $date['timestamp'] === $out[0]['timestamp']) {
                    continue;
                }
                $out[] = $date;
            }
        }
        if ($out === [] && method_exists($item, 'get_date')) {
            $timestamp = $item->get_date('U');
            if (is_numeric($timestamp) && (int) $timestamp > 0) {
                // Parser fallback: original precision/semantics are unknown.
                // Display the date, not a fabricated 00:00 publication time.
                $dt = (new DateTimeImmutable('@' . (int) $timestamp))->setTimezone(new DateTimeZone($timezone));
                $out[] = [
                    'label' => 'Data feed',
                    'display' => $dt->format('d/m/Y'),
                    'iso' => $dt->format('Y-m-d'),
                    'timestamp' => (int) $timestamp,
                    'note' => 'Data interpretata dal lettore RSS; ora e tipo di data non verificati.',
                ];
            }
        }
        return $out;
    }

    private static function parseDate(string $raw, string $label, string $timezone): ?array
    {
        if ($raw === '' || strlen($raw) > 160) {
            return null;
        }
        $parsed = date_parse($raw);
        if (($parsed['error_count'] ?? 1) > 0 || ($parsed['warning_count'] ?? 0) > 0
            || ($parsed['year'] ?? false) === false || ($parsed['month'] ?? false) === false
            || ($parsed['day'] ?? false) === false) {
            return null;
        }
        try {
            $hasTime = ($parsed['hour'] ?? false) !== false && ($parsed['minute'] ?? false) !== false;
            $hasZone = !empty($parsed['is_localtime']);
            $dt = new DateTimeImmutable($raw, new DateTimeZone('UTC'));
            $stamp = $dt->getTimestamp();
            $note = 'Valore della fonte: ' . $raw;
            if ($hasTime && $hasZone) {
                $dt = $dt->setTimezone(new DateTimeZone($timezone));
                $display = $dt->format('d/m/Y H:i');
                $iso = $dt->format(DATE_ATOM);
                $note .= '; visualizzato in ' . $timezone . ' (' . $dt->format('T, P') . ').';
            } elseif ($hasTime) {
                $display = $dt->format('d/m/Y H:i') . ' (fuso n/d)';
                $iso = ''; // Do not attach a made-up timezone to the HTML time.
                $note .= '; la fonte non specifica il fuso: nessuna conversione.';
            } else {
                $display = $dt->format('d/m/Y');
                $iso = $dt->format('Y-m-d');
                $note .= '; la fonte non specifica un orario.';
            }
            return ['label' => $label, 'display' => $display, 'iso' => $iso,
                'timestamp' => $stamp, 'note' => $note];
        } catch (Throwable) {
            return null;
        }
    }
}
