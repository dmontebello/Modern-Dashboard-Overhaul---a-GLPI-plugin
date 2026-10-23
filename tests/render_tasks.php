<?php
/** Render the real controller against isolated doubles for browser tests. CLI only. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { return; }
require_once __DIR__ . '/task_bootstrap.php';
taskFixture();
$options = json_decode($argv[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
$count = max(0, min(100, (int) ($options['count'] ?? 100)));
ProjectTask::$rows = [];
Project::$rows = [1 => ['id' => 1, 'name' => 'Progetto di esempio · interfaccia e accessibilità',
    'entities_id' => 1, 'users_id' => 1, 'writable' => true]];
for ($id = 1; $id <= $count; $id++) {
    ProjectTask::$rows[$id] = ['id' => $id,
        'name' => $id === 1 ? str_repeat('TitoloLungoSenzaSpazi', 14) : 'Attività ' . $id . ' · migliorare il flusso di lavoro',
        'projects_id' => 1, 'entities_id' => 1, 'users_id' => 1, 'projectstates_id' => $id % 4,
        'percent_done' => $id % 10 === 0 ? 100 : ($id % 3 === 0 ? 0 : 50),
        'plan_end_date' => $id % 5 === 0 ? '' : '2000-01-01 12:00:00',
        'real_end_date' => $id % 10 === 0 ? '2025-03-10 09:00:00' : '',
        'content' => 'Descrizione di esempio per verificare leggibilità, contrasto e titoli lunghi.'];
}
$DB->fail = !empty($options['error']);
echo taskPage($options['query'] ?? ['view' => 'board']);
