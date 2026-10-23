<?php
/** GLPI-independent test doubles. No SQL/network and no GLPI bootstrap. */
declare(strict_types=1);
namespace Glpi\Plugin {
    final class Hooks {
        public const CONFIG_PAGE = 'config_page';
        public const ADD_CSS = 'add_css';
        public const ADD_JAVASCRIPT = 'add_javascript';
        public const DASHBOARD_TYPES = 'dashboard_types';
        public const DASHBOARD_CARDS = 'dashboard_cards';
        public const MENU_TOADD = 'menu_toadd';
    }
}
namespace {
    use GlpiPlugin\Mdoverhaul\Dashboard;
    use GlpiPlugin\Mdoverhaul\FeedFormatter;
    use GlpiPlugin\Mdoverhaul\Settings;

    const GLPI_VERSION = '11.0.9';
    const READ = 1;
    final class Config {
        public static array $values = [];
        public static int $reads = 0;
        public static function getConfigurationValues(string $scope): array { self::$reads++; return self::$values[$scope] ?? []; }
        public static function setConfigurationValues(string $scope, array $values): void { self::$values[$scope] = array_replace(self::$values[$scope] ?? [], $values); }
    }
    class CommonGLPI {}
    final class ProjectTask { public static function canView(): bool { return true; } }
    final class Plugin {
        public static bool $legacyActive = false;
        public static function isPluginActive(string $name): bool { return $name === 'arensecurity' && self::$legacyActive; }
    }
    final class Session {
        public static int $userId = 1;
        public static bool $global = true;
        public static bool $public = true;
        public static function getLoginUserID(): int { return self::$userId; }
        public static function haveRight(string $key, int $access): bool {return $key === 'rssfeed_public' && self::$public;}
    }
    final class FakeItem {
        public function __construct(public string $title, public string $link, public string $guid='', public string $published='2026-10-21T14:30:00+02:00'){}
        public function get_title(): string {return $this->title;}
        public function get_permalink(): string {return $this->link;}
        public function get_id(): string {return $this->guid;}
        public function get_item_tags(string $namespace, string $tag): array {return $tag === 'published' ? [['data'=>$this->published]] : [];}
        public function get_date(string $format): string {return '1792585800';}
    }
    final class FakeRSSParser {
        public function __construct(private array $items){}
        public function get_items(int $offset, int $limit): array { return array_slice($this->items,$offset,$limit); }
    }
    final class RSSFeed {
        public static array $data = [];
        public static array $parsers = [];
        public array $fields = [];
        public static int $queries=0;
        public static function canView(): bool {return Session::$global;}
        public static function getListCriteria(bool $arg): array {return ['WHERE'=>['base'=>true]];}
        public function getFromDB(int $id): bool { if (!isset(self::$data[$id])) return false; $this->fields = self::$data[$id]; return true; }
        public function haveVisibilityAccess(): bool { return (bool)($this->fields['public']??false); }
        public static function getRSSFeed(string $url, int $seconds): FakeRSSParser|false {self::$queries++;return self::$parsers[$url] ?? false;}
    }
    final class FakeDB {
        public function request(array $query): array {
            if (($query['WHERE']['glpi_rssfeeds.is_active']??null)!==1)throw new \RuntimeException('active filter missing');
            return array_map(static fn($id)=>['id'=>$id],array_keys(RSSFeed::$data));
        }
    }
    $DB = new FakeDB();
    $_SESSION['glpiactiveprofile']=['id'=>1];
    $_SESSION['glpiactiveentities']=[0];
    $_SESSION['glpitimezone']='Europe/Rome';
    require_once __DIR__.'/../src/Settings.php';
    require_once __DIR__.'/../src/FeedFormatter.php';
    require_once __DIR__.'/../src/Dashboard.php';
    require_once __DIR__.'/../setup.php';
    $tests=0;
    function check(bool $valid, string $message): void { global $tests; $tests++; if (!$valid)throw new \RuntimeException('FAILED test '.$tests.': '.$message);echo "[OK] $tests $message\n"; }
    function invalid(callable $action, string $message): void {try {$action();} catch (\InvalidArgumentException){check(true,$message);return;}check(false,$message);}

    check(PLUGIN_MDOVERHAUL_VERSION==='0.8.0','semantic version is 0.8.0');
    check(plugin_mdoverhaul_check_prerequisites(),'GLPI 11.0.9 requirements');
    check(plugin_mdoverhaul_check_config(),'active with no legacy plugin');
    Plugin::$legacyActive=true;
    check(!plugin_mdoverhaul_check_config(),'dual-active legacy blocked');
    Plugin::$legacyActive=false;
    check(array_keys(Settings::defaults())===['dashboard_restyling','rss_widgets','compact_empty_filters','dashboard_tabs','chart_text','task_workspace','kanban_restyling'],'exactly seven settings');
    $start=Config::$reads;
    Settings::get();Settings::get();Settings::get();
    check(Config::$reads-$start===1,'settings fetched only once per request');
    invalid(static fn()=>Settings::save(['dashboard_restyling'=>'1']),'incomplete POST is rejected');
    $inputs=array_fill_keys(array_keys(Settings::defaults()),'1');
    $inputs['rss_widgets']='0';
    Settings::save($inputs);
    check(!Settings::get()['rss_widgets'],'RSS mode saves independently');
    check(Config::$reads-$start===2,'saving invalidates cached settings');
    $inputs['rss_widgets']='1';
    Settings::save($inputs);
    check(Settings::get()['dashboard_restyling'] && Settings::get()['rss_widgets'],'two modules active');
    $GLOBALS['PLUGIN_HOOKS']=[];
    plugin_init_mdoverhaul();
    check(isset($GLOBALS['PLUGIN_HOOKS']['dashboard_types']['mdoverhaul']),'RSS widget type registration');
    check(isset($GLOBALS['PLUGIN_HOOKS']['add_javascript']['mdoverhaul']),'restyling JS loaded');
    check(!isset($GLOBALS['PLUGIN_HOOKS']['menu_toadd']['mdoverhaul']),'Tasks off by default');
    $inputs['task_workspace']='1';
    Settings::save($inputs);
    $GLOBALS['PLUGIN_HOOKS']=[];
    plugin_init_mdoverhaul();
    check(isset($GLOBALS['PLUGIN_HOOKS']['menu_toadd']['mdoverhaul']['helpdesk']),'Tasks menu enabled by setting');
    check(in_array('css/ticket-mini-dashboard.css', $GLOBALS['PLUGIN_HOOKS']['add_css']['mdoverhaul'], true), 'mini-ticket CSS loaded');
    $inputs['kanban_restyling']='1';
    Settings::save($inputs);
    $GLOBALS['PLUGIN_HOOKS']=[];
    plugin_init_mdoverhaul();
    check(in_array('css/task-kanban.css', $GLOBALS['PLUGIN_HOOKS']['add_css']['mdoverhaul'], true), 'native Kanban CSS toggled on');
    check(FeedFormatter::safeLink('https://example.org/advisory?a=1'),'HTTPS URLs allowed');
    check(FeedFormatter::safeLink('http://example.org/path'),'HTTP URLs allowed');
    foreach (['javascript:alert(1)','data:text/html,abc','http://user:pass@example.org',"https://example.org/ok\nno", 'file:///etc/passwd'] as $bad) {
        check(!FeedFormatter::safeLink($bad),'unsafe URL blocked: '.substr($bad,0,25));
    }
    $items=[
        new FakeItem('CVE-2026-1234 distinto articolo A','https://example.org/a','id-a'),
        new FakeItem('CVE-2026-1234 distinto articolo B','https://example.org/b','id-b'),
        new FakeItem('CVE-2026-1234 stesso GUID','https://example.org/b-alt','id-b'),
        new FakeItem('<script>alert(1)</script> Notizia <b>sicura</b>','https://example.org/x','id-x'),
        new FakeItem('Link malevolo','javascript:alert(1)','id-z'),
    ];
    $rows=FeedFormatter::normalize($items,'Europe/Rome');
    check(count($rows)===4,'multiple articles citing same CVE retained; repeated GUID deduplicated');
    check($rows[0]['title']==='CVE-2026-1234 distinto articolo A','original title preserved');
    check(strpos($rows[2]['title'],'<script>')===false,'HTML scripts stripped from feed title');
    check($rows[3]['link']==='','unsafe link not included');
    check(($rows[0]['dates'][0]['display']??'')==='21/10/2026 14:30','publication date parsed in source time zone');
    $veryLong=FeedFormatter::normalize([new FakeItem(str_repeat('A',5000),'https://example.org/long')],'UTC');
    check(strlen($veryLong[0]['title'])===1024,'malformed giant title bounded to 1024 chars');
    $tooMany=array_fill(0,160,new FakeItem('News','https://example.org/repeat'));
    check(count(FeedFormatter::normalize($tooMany,'UTC'))===1,'large feed bounded+deduplicated');

    RSSFeed::$data = [
        1=>['id'=>1,'is_active'=>1,'users_id'=>1,'name'=>'Owned','url'=>'https://feed.example/owned','refresh_rate'=>900,'public'=>false],
        2=>['id'=>2,'is_active'=>1,'users_id'=>2,'name'=>'Public','url'=>'https://feed.example/public','refresh_rate'=>300,'public'=>true],
        3=>['id'=>3,'is_active'=>1,'users_id'=>2,'name'=>'Denied','url'=>'https://feed.example/private','refresh_rate'=>300,'public'=>false],
        4=>['id'=>4,'is_active'=>0,'users_id'=>1,'name'=>'Inactive','url'=>'https://feed.example/inactive','refresh_rate'=>300,'public'=>true],
    ];
    RSSFeed::$parsers=['https://feed.example/owned'=>new FakeRSSParser($items),'https://feed.example/public'=>new FakeRSSParser($items)];
    $cards=Dashboard::getCards();
    check(array_keys($cards)===['arensecurity_feed_1','arensecurity_feed_2'],'per-user RSS access respects GLPI visibility');
    check(count(Dashboard::getTypes())===4,'four legacy widget types preserved');
    check(isset(Dashboard::getTypes()['arensecurity_rss_20']),'20 article preset still available');
    $hidden=Dashboard::provideFeed(3);
    check($hidden['items']===[] && str_contains($hidden['message'],'autorizzato'),'provider refuses non-visible feed');
    $owned=Dashboard::provideFeed(1);
    check(count($owned['items'])===4,'authorized feed receives parser results');
    $html=Dashboard::renderFive($owned);
    check(str_contains($html,'asw-feed') && !str_contains($html,'<script>'),'RSS HTML rendering is escaped');
    check(str_contains($html,'CVE-2026-1234'),'CVE legacy label retained');
    check(!str_contains($html,'href="javascript:'),'no unsafe href output');
    check(str_contains($html,'target="_blank" rel="noopener noreferrer"'),'outbound links isolated');
    $empty=Dashboard::renderTwenty(['title'=>'Empty','items'=>[],'message'=>'No items']);
    check(str_contains($empty,'asw-empty'),'empty feed state renders safely');
    $_SESSION['glpiactiveprofile']=['id'=>3];
    Session::$userId=2;Session::$public=false;
    $cards=Dashboard::getCards();
    check(array_keys($cards)===['arensecurity_feed_2','arensecurity_feed_3'],'second user cannot read first user private feed');
    Session::$global=false;
    check(Dashboard::getCards()===[],'missing RSS rights refuse all feed catalogue entries');

    echo "\n$tests PHP tests PASSED (isolated GLPI mocks).\n";
}
