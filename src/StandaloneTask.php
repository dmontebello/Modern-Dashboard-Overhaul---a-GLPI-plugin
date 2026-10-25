<?php
declare(strict_types=1);
namespace GlpiPlugin\Mdoverhaul;

use CommonDBTM;
use ExtraVisibilityCriteria;
use Group;
use Profile_User;
use Plugin;
use Session;
use User;

/** A GLPI plugin object, independent of ProjectTask and its mandatory parent. */
final class StandaloneTask extends CommonDBTM implements ExtraVisibilityCriteria
{
    public static $rightname = 'plugin_mdoverhaul_standalonetask';
    public const READALL = READ;
    public const READMY = 1024;
    public $dohistory = true;

    public static function text(string $it, string $en): string
    {
        return str_starts_with(strtolower((string) Session::getLanguage()), 'it') ? $it : $en;
    }

    public static function getTypeName($nb = 0): string
    {
        return $nb > 1 ? self::text('Attività indipendenti', 'Independent tasks') : self::text('Attività indipendente', 'Independent task');
    }

    public static function getTable($classname = null): string { return $classname !== null && $classname !== self::class ? parent::getTable($classname) : TaskSchema::TABLE; }
    public static function getIcon(): string { return 'ti ti-list-check'; }
    public static function enabled(): bool
    {
        $settings = Settings::get();
        return $settings['task_workspace'] && $settings['tasks_standalone'] && Plugin::isPluginActive('mdoverhaul') && TaskSchema::isReady();
    }
    public static function canView(): bool { return (int) Session::getLoginUserID() > 0 && self::enabled() && Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]); }
    public static function canCreate(): bool { return (int) Session::getLoginUserID() > 0 && self::enabled() && Session::haveRight(self::$rightname, CREATE); }
    public static function canUpdate(): bool { return self::enabled() && Session::haveRight(self::$rightname, UPDATE); }
    public static function canDelete(): bool { return self::enabled() && Session::haveRight(self::$rightname, DELETE); }
    public static function canPurge(): bool { return false; }

    public function getRights($interface = 'central'): array
    {
        return [self::READALL => self::text('Leggi tutte nell’entità', 'Read all in active entities'),
            self::READMY => self::text('Leggi proprie/assegnate', 'Read owned/assigned'),
            CREATE => __('Create'), UPDATE => __('Update'), DELETE => __('Delete')];
    }

    public function canViewItem(): bool
    {
        return self::canView() && Session::haveAccessToEntity($this->getEntityID())
            && (Session::haveRight(self::$rightname, self::READALL)
                || (int) ($this->fields['users_id_owner'] ?? 0) === (int) Session::getLoginUserID()
                || (int) ($this->fields['users_id_assign'] ?? 0) === (int) Session::getLoginUserID()
                || ((int) ($this->fields['groups_id_assign'] ?? 0) > 0
                    && in_array((int) $this->fields['groups_id_assign'], array_map('intval', $_SESSION['glpigroups'] ?? []), true)));
    }
    public function canCreateItem(): bool { return self::canCreate() && Session::haveAccessToEntity($this->getEntityID()); }
    public function canUpdateItem(): bool { return empty($this->fields['is_deleted']) && $this->canViewItem(); }
    public function canDeleteItem(): bool { return $this->canViewItem(); }
    public function canPurgeItem(): bool { return false; }
    public function pre_deleteItem(): bool
    {
        return empty($this->input['_purge']) && $this->can((int) $this->fields['id'], DELETE);
    }
    public function delete(array $input, $force = false, $history = true)
    {
        $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
        if ($force || !empty($input['purge']) || !empty($input['_purge']) || $id === false || $id < 1) { return false; }
        return parent::delete(['id' => $id], false, $history);
    }
    public function restore(array $input, $history = true)
    {
        $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
        return $id !== false && $id > 0 && $this->can($id, DELETE) && parent::restore(['id'=>$id], $history);
    }

    /** Shared by the native search hook, HL API and bounded read-only board. */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        $table = self::getTable();
        if (!self::canView()) { return ['WHERE' => [$table . '.id' => -1], 'LEFT JOIN' => []]; }
        $where = getEntitiesRestrictCriteria($table);
        if (!Session::haveRight(self::$rightname, self::READALL)) {
            $actors = [$table . '.users_id_owner' => (int) Session::getLoginUserID(),
                $table . '.users_id_assign' => (int) Session::getLoginUserID()];
            $groups = array_values(array_filter(array_map('intval', $_SESSION['glpigroups'] ?? []), static fn(int $id): bool => $id > 0));
            if ($groups !== []) { $actors[$table . '.groups_id_assign'] = $groups; }
            $where[] = ['OR' => $actors];
        }
        return ['WHERE' => $where, 'LEFT JOIN' => []];
    }

    public static function statuses(): array
    {
        return [1 => self::text('Da fare', 'To do'), 2 => self::text('In corso', 'Doing'), 3 => self::text('Completata', 'Done')];
    }

    public function prepareInputForAdd($input)
    {
        if (!self::canCreate()) { return false; }
        return $this->validated($input, true);
    }
    public function prepareInputForUpdate($input)
    {
        if (!$this->can((int) ($input['id'] ?? 0), UPDATE)) { return false; }
        return $this->validated($input, false);
    }

    private function validated(array $input, bool $adding): array|false
    {
        // Allowlist all writable fields. Owner/entity cannot be transferred by update.
        $entity = $adding ? filter_var($input['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? -1), FILTER_VALIDATE_INT)
            : (int) $this->fields['entities_id'];
        $name = $input['name'] ?? $this->fields['name'] ?? '';
        $content = $input['content'] ?? $this->fields['content'] ?? '';
        $status = filter_var($input['status'] ?? $this->fields['status'] ?? 1, FILTER_VALIDATE_INT);
        $due = $input['due_date'] ?? $this->fields['due_date'] ?? '';
        if (is_string($due) && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d(?::\d\d)?$/D', $due)) {
            $due = str_replace('T', ' ', $due) . (strlen($due) === 16 ? ':00' : '');
        }
        if ($entity === false || $entity < 0 || !Session::haveAccessToEntity($entity)
            || !is_string($name) || trim($name) === '' || (function_exists('mb_strlen') ? mb_strlen($name,'UTF-8') : strlen($name)) > 255
            || !is_string($content) || strlen($content) > 100000 || !isset(self::statuses()[$status])
            || !is_string($due) || ($due !== '' && TaskBoard::dateTimestamp($due) === null)) {
            return false;
        }
        $result = ['name' => trim($name), 'content' => $content, 'status' => $status, 'due_date' => $due === '' ? null : $due];
        foreach (['users_id_assign' => User::class, 'groups_id_assign' => Group::class] as $key => $type) {
            $id = filter_var($input[$key] ?? $this->fields[$key] ?? 0, FILTER_VALIDATE_INT);
            if ($id === false || $id < 0) { return false; }
            if ($id > 0 && ($adding || $id !== (int)($this->fields[$key] ?? 0))) {
                $actor = new $type();
                if (!$actor->getFromDB($id) || !empty($actor->fields['is_deleted']) || !$actor->can($id, READ)) { return false; }
                if ($type === User::class && !in_array($entity, array_map('intval', Profile_User::getUserEntities($id)), true)) { return false; }
                if ($type === Group::class && $actor->getEntityID() !== $entity
                    && (!$actor->isRecursive() || !in_array($actor->getEntityID(), array_map('intval', getAncestorsOf('glpi_entities', $entity)), true))) { return false; }
            }
            $result[$key] = $id;
        }
        if ($adding) {
            $result['entities_id'] = $entity;
            $result['users_id_owner'] = (int) Session::getLoginUserID();
        } else { $result['id'] = (int) $this->fields['id']; }
        return $result;
    }

    public function rawSearchOptions(): array
    {
        $table = self::getTable();
        $options = [
            ['id'=>'common', 'name'=>self::getTypeName(2)],
            ['id'=>1,'table'=>$table,'field'=>'name','name'=>__('Name'),'datatype'=>'itemlink'],
            ['id'=>2,'table'=>$table,'field'=>'id','name'=>__('ID'),'datatype'=>'number'],
            ['id'=>3,'table'=>$table,'field'=>'content','name'=>__('Description'),'datatype'=>'text'],
            ['id'=>4,'table'=>$table,'field'=>'status','name'=>__('Status'),'datatype'=>'specific','searchtype'=>['equals','notequals']],
            ['id'=>5,'table'=>$table,'field'=>'due_date','name'=>self::text('Scadenza','Due date'),'datatype'=>'datetime'],
            ['id'=>6,'table'=>'glpi_users','field'=>'name','linkfield'=>'users_id_owner','name'=>__('Owner'),'datatype'=>'dropdown'],
            ['id'=>7,'table'=>'glpi_users','field'=>'name','linkfield'=>'users_id_assign','name'=>__('Assigned to'),'datatype'=>'dropdown'],
            ['id'=>8,'table'=>'glpi_groups','field'=>'completename','linkfield'=>'groups_id_assign','name'=>__('Group'),'datatype'=>'dropdown'],
            ['id'=>80,'table'=>'glpi_entities','field'=>'completename','name'=>__('Entity'),'datatype'=>'dropdown'],
        ];
        // Public Search metadata disambiguates this explicit table override.
        // Reverse table-name inference would otherwise resolve "Task", not StandaloneTask.
        foreach ($options as &$option) {
            if (($option['table'] ?? '') === $table) { $option['itemtype'] = self::class; }
        }
        unset($option);
        return $options;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = []): string
    {
        $value = is_array($values) ? ($values[$field] ?? 0) : $values;
        return $field === 'status' ? htmlspecialchars(self::statuses()[(int) $value] ?? '—', ENT_QUOTES) : '';
    }
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if ($field !== 'status') { return ''; }
        // Native GLPI Search expects returned HTML. The dropdown's default
        // display=true instead echoes the HTML and returns an integer rand.
        return \Dropdown::showFromArray($name, self::statuses(), array_replace($options, [
            'value' => $values,
            'display' => false,
            'aria_label' => self::text('Stato attività', 'Task status'),
        ]));
    }

    public static function loadBoard(): array
    {
        global $DB;
        $result = ['tasks'=>[], 'truncated'=>false, 'scanned'=>0];
        if (!self::canView()) { return $result; }
        $table = self::getTable();
        $rows = $DB->request(['SELECT'=>[$table.'.*'],'FROM'=>$table,
            'WHERE'=>['AND'=>[self::getVisibilityCriteria()['WHERE'],[$table.'.is_deleted'=>0]]],
            'ORDER'=>[$table.'.id DESC'],'LIMIT'=>TaskBoard::MAX_CANDIDATES+1]);
        foreach ($rows as $row) {
            if ($result['scanned'] >= TaskBoard::MAX_CANDIDATES) { $result['truncated']=true; break; }
            $result['scanned']++;
            $item = new self(); $item->getFromResultSet($row); $item->post_getFromDB();
            if (!$item->can((int) $row['id'], READ)) { continue; }
            $result['tasks'][] = ['id'=>(int)$row['id'],'name'=>(string)$row['name'],
                'project_id'=>0,'project'=>'','project_readable'=>false,'source'=>'standalone',
                'state_id'=>(int)$row['status'],'state'=>self::statuses()[(int)$row['status']] ?? '—',
                'percent_done'=>(int)$row['status'] === 3 ? 100 : 0,
                'due'=>(string)($row['due_date'] ?? ''),'real_end'=>'',
                'description'=>TaskBoard::description((string)($row['content'] ?? ''))];
        }
        return $result;
    }
}
