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
if (!empty($options['independent_count'])) {
    $flags = array_fill_keys(array_keys(\GlpiPlugin\Mdoverhaul\Settings::defaults()), '0');
    $flags['task_workspace'] = $flags['tasks_standalone'] = '1';
    \GlpiPlugin\Mdoverhaul\Settings::save($flags);
    Session::$standaloneRights = \GlpiPlugin\Mdoverhaul\StandaloneTask::READMY | CREATE | UPDATE;
    for ($n = 1; $n <= min(10, (int)$options['independent_count']); $n++) {
        (new \GlpiPlugin\Mdoverhaul\StandaloneTask())->add(['name'=>'Independent fixture '.$n, 'entities_id'=>1, 'status'=>$n===1?1:2]);
    }
}
$DB->fail = !empty($options['error']);
$body = taskPage($options['query'] ?? ['view' => 'board']);
echo !empty($options['response']) ? json_encode(['body' => $body, 'redirect' => Html::$redirect], JSON_THROW_ON_ERROR) : $body;
