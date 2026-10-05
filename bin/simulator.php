<?php
declare(strict_types=1);
// Simulador de eventos para desarrollo. Uso: php bin/simulator.php
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Engine.php';

$db = Db::pdo();
$engine = new Engine($db);
$devs = $db->query('SELECT id, type, name, base FROM devices')->fetchAll();
$ofType = fn(array $t) => array_values(array_filter($devs, fn($d) => in_array($d['type'], $t, true)));
$pick = fn(array $a) => $a ? $a[array_rand($a)] : null;
$rnd = fn() => mt_rand() / mt_getrandmax();

$timers = [];
$after = function (float $sec, string $id, array $patch) use (&$timers) { $timers[] = [microtime(true) + $sec, $id, $patch]; };

echo "Simulador en marcha (Ctrl+C para parar)\n";
while (true) {
    $now = microtime(true);
    foreach ($timers as $i => [$at, $id, $patch]) {
        if ($at <= $now) { $engine->update($id, $patch); unset($timers[$i]); }
    }
    $st = [];
    foreach ($db->query('SELECT id, state FROM devices')->fetchAll() as $r) $st[$r['id']] = json_decode($r['state'], true);

    foreach ($ofType(['temp']) as $d) {
        $v = $st[$d['id']]['v'];
        $engine->update($d['id'], ['v' => $v + ((float)$d['base'] - $v) * .12 + ($rnd() - .5) * .35]);
    }
    if ($rnd() < .4 && ($d = $pick($ofType(['door', 'pir'])))) {
        $k = $d['type'] === 'door' ? 'open' : 'on';
        if (empty($st[$d['id']][$k])) { $engine->update($d['id'], [$k => true]); $after($d['type'] === 'door' ? 5 : 3.5, $d['id'], [$k => false]); }
    }
    if ($rnd() < .03 && isset($st['t3'])) {
        $engine->update('t3', ['v' => $st['t3']['v'] + 6]);
        $engine->log('warn', 'Aviso · temperatura alta (Temperatura cuarto técnico)');
    }
    if ($rnd() < .01 && ($d = $pick($ofType(['smoke']))) && empty($st[$d['id']]['al'])) $engine->update($d['id'], ['al' => true]);
    if ($rnd() < .02 && ($d = $pick($ofType(['cam']))) && empty($st[$d['id']]['off'])) { $engine->update($d['id'], ['off' => true]); $after(7, $d['id'], ['off' => false]); }
    if ($rnd() < .015 && ($d = $pick($ofType(['exp']))) && empty($st[$d['id']]['off'])) { $engine->update($d['id'], ['off' => true]); $after(6, $d['id'], ['off' => false]); }
    if ($rnd() < .015 && !empty($st['ce']['ac'])) { $engine->update('ce', ['ac' => false]); $after(9, 'ce', ['ac' => true]); }
    if (isset($st['nv'])) $engine->update('nv', ['hdd' => min(99, $st['nv']['hdd'] + .03)]);
    sleep(2);
}
