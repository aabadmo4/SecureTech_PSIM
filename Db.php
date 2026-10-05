<?php
declare(strict_types=1);

final class Db
{
    public static function pdo(bool $reset = false): PDO
    {
        $dir = __DIR__ . '/../data';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $file = $dir . '/seguridad.sqlite';
        if ($reset) foreach ([$file, "$file-wal", "$file-shm"] as $f) if (is_file($f)) unlink($f);
        $pdo = new PDO('sqlite:' . $file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=3000; PRAGMA foreign_keys=ON;');
        $pdo->exec(file_get_contents(__DIR__ . '/../db/schema.sql'));
        return $pdo;
    }

    /** Configuración estática: particiones, planos, estancias y dispositivos. */
    public static function config(PDO $pdo): array
    {
        $parts = [];
        foreach ($pdo->query('SELECT * FROM partitions')->fetchAll() as $r) {
            $parts[$r['id']] = ['n' => $r['name'], 'c' => $r['color'], 'a' => (int)$r['armed']];
        }
        $plans = [];
        foreach ($pdo->query('SELECT * FROM plans')->fetchAll() as $r) {
            $g = json_decode($r['geometry'], true);
            $plans[$r['id']] = ['n' => $r['name'], 'R' => [], 'I' => $g['I'], 'O' => $g['O'], 'F' => $g['F']];
        }
        foreach ($pdo->query('SELECT * FROM rooms ORDER BY id')->fetchAll() as $r) {
            $plans[$r['plan_id']]['R'][] = [$r['name'], (float)$r['x'], (float)$r['y'], (float)$r['w'], (float)$r['h'], $r['partition_id']];
        }
        $devs = [];
        foreach ($pdo->query('SELECT * FROM devices ORDER BY rowid')->fetchAll() as $r) {
            $devs[] = [
                'id' => $r['id'], 'p' => $r['plan_id'], 't' => $r['type'], 'n' => $r['name'], 'z' => $r['room'],
                'x' => (float)$r['x'], 'y' => (float)$r['y'],
                'b' => $r['label'] ?? ($r['base'] !== null ? (float)$r['base'] : null),
                'pt' => $r['partition_id'],
            ];
        }
        return ['partitions' => $parts, 'plans' => $plans, 'devices' => $devs];
    }
}
