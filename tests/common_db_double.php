<?php
/** Minimal GLPI persistence/rights doubles. No real SQL, session middleware or GLPI. */
declare(strict_types=1);

interface ExtraVisibilityCriteria { public static function getVisibilityCriteria(bool $forceall = false): array; }
class CommonDBTM extends CommonGLPI
{
    public static array $storage = [];
    public array $fields = [];
    public array $input = [];
    public static function getTable($classname = null) { return 'unused'; }
    public function getID(): int { return (int)($this->fields['id'] ?? 0); }
    public function getEntityID(): int { return (int)($this->fields['entities_id'] ?? 0); }
    public function isRecursive(): bool { return false; }
    public function getFromResultSet(array $row): void { $this->fields=$row; }
    public function post_getFromDB(): void {}
    public function getFromDB($id): bool { $this->fields=self::$storage[static::getTable()][(int)$id] ?? []; return $this->fields!==[]; }
    public static function getFormURL($full=true): string { return ($full?'/glpi':'') . '/plugins/mdoverhaul/front/standalonetask.form.php'; }
    public static function getSearchURL($full=true): string { return ($full?'/glpi':'') . '/plugins/mdoverhaul/front/standalonetask.php'; }
    public function can($id, int $right, ?array &$input=null): bool
    {
        if ($id <= 0) {
            $this->fields=['id'=>0,'entities_id'=>(int)($_SESSION['glpiactive_entity']??0)] + ($input??[]);
            if ($input!==null) { $this->fields=array_replace($this->fields,$input); }
            return static::canCreate() && $this->canCreateItem();
        }
        if (($this->fields['id']??0)!=$id && !$this->getFromDB($id)) { return false; }
        if (!empty($this->fields['hook_denied'])) { return false; }
        return match($right) {
            READ=>static::canView() && $this->canViewItem(), UPDATE=>static::canUpdate() && $this->canUpdateItem(),
            DELETE=>static::canDelete() && $this->canDeleteItem(), default=>false,
        };
    }
    public function check($id, int $right, ?array &$input=null): void
    {
        if (!$this->can($id,$right,$input)) { throw new RuntimeException('native item right denied'); }
    }
    public function add(array $input)
    {
        $input=$this->prepareInputForAdd($input);
        if ($input===false) { return false; }
        $id=count(self::$storage[static::getTable()]??[])+1;
        $this->fields=['id'=>$id,'is_deleted'=>0,'date_creation'=>'2026-10-24 12:00:00','date_mod'=>'2026-10-24 12:00:00']+$input;
        self::$storage[static::getTable()][$id]=$this->fields;
        return $id;
    }
    public function update(array $input, $history=true, $options=[])
    {
        $input=$this->prepareInputForUpdate($input);
        if ($input===false) { return false; }
        $this->fields=array_replace($this->fields,$input);
        self::$storage[static::getTable()][$this->getID()]=$this->fields;
        return true;
    }
    public function delete(array $input, $force=false, $history=true)
    {
        if (!$this->getFromDB($input['id'])) { return false; }
        $this->input=$input+['_purge'=>(int)$force];
        if (!$this->pre_deleteItem()) { return false; }
        self::$storage[static::getTable()][$this->getID()]['is_deleted']=1;
        return true;
    }
    public function restore(array $input, $history=true)
    {
        if (!$this->getFromDB($input['id'])) { return false; }
        self::$storage[static::getTable()][$this->getID()]['is_deleted']=0;
        return true;
    }
}
function __($text, ...$args): string { return $text; }

class User
{
    public static array $rows=[1=>['id'=>1,'entities'=>[1]],2=>['id'=>2,'entities'=>[1]],3=>['id'=>3,'entities'=>[2]]];
    public array $fields=[];
    public function getFromDB($id): bool { $this->fields=static::$rows[$id]??[]; return $this->fields!==[]; }
    public function can($id,$right): bool { return empty($this->fields['denied']); }
    public static function dropdown($options=[]): void
    {
        echo '<select name="'.htmlspecialchars($options['name']).'" aria-label="'.htmlspecialchars($options['aria_label']??$options['name']).'"><option value="0">—</option>';
        foreach (static::$rows as $id=>$row) { echo '<option value="'.$id.'"'.((int)($options['value']??0)===$id?' selected':'').'>Actor '.$id.'</option>'; }
        echo '</select>';
    }
}
final class Profile_User { public static function getUserEntities($id,$recursive=true): array { return User::$rows[$id]['entities']??[]; } }
class Group extends User
{
    public static array $rows=[5=>['id'=>5,'entities_id'=>1,'is_recursive'=>0],6=>['id'=>6,'entities_id'=>2,'is_recursive'=>0]];
    public function getEntityID(): int { return (int)$this->fields['entities_id']; }
    public function isRecursive(): bool { return !empty($this->fields['is_recursive']); }
}
class Entity extends User { public static array $rows=[1=>['id'=>1],2=>['id'=>2]]; }
function getAncestorsOf($table,$id): array { return $id===11?[1]:[]; }
final class DBConnection
{
    public static function getDefaultPrimaryKeySignOption(): string { return 'unsigned'; }
    public static function getDefaultCharset(): string { return 'utf8mb4'; }
    public static function getDefaultCollation(): string { return 'utf8mb4_unicode_ci'; }
}
final class Migration
{
    public function __construct($version) {}
    public function addField($table,$field,$type,$options=[]): bool
    {
        global $DB;
        if (!$DB->fieldExists($table,$field)) { $DB->schemaFields[$field]=true; $DB->alterations++; }
        return true;
    }
    public function addKey($table,$field): bool { global $DB; $DB->schemaKeys[$field]=true; return true; }
    public function addRight($name,$rights,$required): void { global $DB; $DB->profileRights[1][$name] ??= $rights; $DB->profileRights[2][$name] ??= $rights; }
    public function executeMigration(): void {}
}
