<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;
// Legacy type kept as a read-inaccessible marker for data recovery.
// No registration, search, form, create, update or delete in MDO.
final class StandaloneTask {
    public static function enabled(): bool {return false;}
    public static function canView(): bool {return false;}
    public static function canCreate(): bool {return false;}
    public static function getTypeName($nb=0): string {return 'Retired legacy independent tasks';}
}
