<?php
declare(strict_types=1);
// Recrea la base de datos desde db/seed.json. Uso: php bin/seed.php
require __DIR__ . '/../src/Db.php';

$pdo = Db::pdo(true);
$seed = json_decode(file_get_contents(__DIR__ . '/../db/seed.json'), true);

$pdo->beginTransaction();
$ip = $pdo->prepare('INSERT INTO partitions(id,name,color,armed) VALUES(?,?,?,0)');
foreach ($seed['partitions'] as $id => $p) $ip->execute([$id, $p['n'], $p['c']]);

$ipl = $pdo->prepare('INSERT INTO plans(id,name,geometry) VALUES(?,?,?)');
$ir = $pdo->prepare('INSERT INTO rooms(plan_id,name,x,y,w,h,partition_id) VALUES(?,?,?,?,?,?,?)');
$roomPart = [];
foreach ($seed['plans'] as $id => $pl) {
    $ipl->execute([$id, $pl['n'], json_encode(['I' => $pl['I'], 'O' => $pl['O'], 'F' => $pl['F']])]);
    foreach ($pl['R'] as [$n, $x, $y, $w, $h, $pt]) {
        $ir->execute([$id, $n, $x, $y, $w, $h, $pt]);
        $roomPart["$id|$n"] = $pt;
    }
}

$id = $pdo->prepare('INSERT INTO devices(id,plan_id,type,name,room,x,y,label,base,partition_id,state) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
foreach ($seed['devices'] as $d) {
    $equipo = in_array($d['t'], ['ctr', 'exp', 'nvr'], true);
    $base = $d['t'] === 'temp' ? (float)$d['b'] : null;
    $state = ['open' => false, 'on' => false, 'al' => false, 'off' => false, 'ac' => true,
              'v' => $base ?? 0, 'bat' => 100, 'hdd' => $d['t'] === 'nvr' ? 62 : 0];
    $id->execute([
        $d['id'], $d['p'], $d['t'], $d['n'], $d['z'], $d['x'], $d['y'],
        $equipo ? $d['b'] : null, $base,
        $equipo ? null : ($roomPart[$d['p'] . '|' . $d['z']] ?? null),
        json_encode($state),
    ]);
}
$pdo->commit();
echo count($seed['devices']) . " dispositivos cargados.\n";