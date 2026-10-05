<?php
declare(strict_types=1);

/**
 * Lógica de negocio: estado de dispositivos, armado de particiones y eventos.
 * La usan la API, el simulador y (en el futuro) el puente MQTT / central.
 */
final class Engine
{
    public function __construct(private PDO $db) {}

    public function log(string $level, string $msg): void
    {
        $this->db->prepare('INSERT INTO events(ts,level,message) VALUES(?,?,?)')->execute([time(), $level, $msg]);
    }

    private function device(string $id): ?array
    {
        $q = $this->db->prepare('SELECT d.*, p.name AS pname, p.armed AS parmed FROM devices d LEFT JOIN partitions p ON p.id = d.partition_id WHERE d.id = ?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    private function save(string $id, array $s): void
    {
        $this->db->prepare('UPDATE devices SET state = ? WHERE id = ?')->execute([json_encode($s), $id]);
    }

    /** Estado en vivo que consulta el navegador. */
    public function state(): array
    {
        $parts = [];
        foreach ($this->db->query('SELECT id, armed FROM partitions')->fetchAll() as $r) $parts[$r['id']] = (bool)$r['armed'];
        $devs = [];
        foreach ($this->db->query('SELECT id, state FROM devices')->fetchAll() as $r) $devs[$r['id']] = json_decode($r['state'], true);
        $ev = [];
        foreach ($this->db->query('SELECT ts, level, message FROM events ORDER BY id DESC LIMIT 10')->fetchAll() as $r) {
            $ev[] = ['t' => date('H:i:s', (int)$r['ts']), 'l' => $r['level'], 'm' => $r['message']];
        }
        return ['partitions' => $parts, 'devices' => $devs, 'events' => $ev];
    }

    public function arm(string $id, bool $armed): void
    {
        $q = $this->db->prepare('SELECT name FROM partitions WHERE id = ?');
        $q->execute([$id]);
        $name = $q->fetchColumn();
        if ($name === false) throw new InvalidArgumentException('Partición desconocida');
        $this->db->prepare('UPDATE partitions SET armed = ? WHERE id = ?')->execute([(int)$armed, $id]);
        $this->log('', "Partición «$name»: " . ($armed ? 'armada' : 'desarmada'));
    }

    public function ack(): void
    {
        foreach ($this->db->query('SELECT id, state FROM devices')->fetchAll() as $r) {
            $s = json_decode($r['state'], true);
            if (!empty($s['al'])) { $s['al'] = false; $this->save($r['id'], $s); }
        }
        $this->log('', 'Alarmas reconocidas');
    }

    /**
     * Aplica un cambio de estado a un dispositivo (open, on, off, ac, al, v, bat, hdd)
     * y genera los eventos que correspondan. Un contacto o PIR solo genera
     * evento y alarma si su partición está armada.
     */
    public function update(string $id, array $p): void
    {
        $d = $this->device($id);
        if (!$d) throw new InvalidArgumentException('Dispositivo desconocido');
        $o = json_decode($d['state'], true);
        $s = $o;
        foreach (['open', 'on', 'off', 'ac', 'al'] as $k) if (array_key_exists($k, $p)) $s[$k] = (bool)$p[$k];
        foreach (['v', 'bat', 'hdd'] as $k) if (array_key_exists($k, $p)) $s[$k] = (float)$p[$k];

        $t = $d['type']; $n = $d['name']; $armed = (bool)$d['parmed'];
        if ($t === 'door' || $t === 'pir') {
            $k = $t === 'door' ? 'open' : 'on';
            if ($s[$k] && !$o[$k] && $armed) {
                $s['al'] = true;
                $this->log('alarm', 'ALARMA · ' . ($t === 'door' ? "$n: abierta" : $n) . ' (' . $d['pname'] . ')');
            }
            if ($t === 'door' && !$s[$k] && $o[$k] && $armed) $this->log('', "$n: cerrada");
        }
        if ($t === 'smoke' && $s['al'] && !$o['al']) $this->log('alarm', "ALARMA · humo detectado ({$d['room']})");
        if ($t === 'cam' && $s['off'] !== $o['off']) {
            $this->log($s['off'] ? 'warn' : '', $s['off'] ? "Sin señal · $n" : "$n: señal recuperada");
        }
        if (in_array($t, ['ctr', 'exp', 'nvr'], true)) {
            if ($s['off'] !== $o['off']) {
                $this->log($s['off'] ? 'alarm' : '', $s['off'] ? "ALARMA · $n: sin comunicación" : "$n: comunicación recuperada");
            }
            if ($s['ac'] !== $o['ac']) {
                $this->log($s['ac'] ? '' : 'warn', $s['ac'] ? "$n: red 220 V restablecida" : "Aviso · $n: fallo de red 220 V");
            }
        }
        $this->save($id, $s);
    }
}
