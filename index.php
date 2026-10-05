<?php
declare(strict_types=1);
/**
 * Router. Desarrollo: php -S 0.0.0.0:8080 -t public public/index.php
 * Apache/Nginx: redirigir /api/* a este archivo.
 */
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Engine.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if (!str_starts_with($path, '/api/')) {
    if ($path === '/') { readfile(__DIR__ . '/index.html'); return true; }
    return false; // el servidor integrado sirve el archivo estático
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function body(): array
{
    $b = json_decode(file_get_contents('php://input') ?: '[]', true);
    return is_array($b) ? $b : [];
}

try {
    $pdo = Db::pdo();
    $engine = new Engine($pdo);

    if ($path === '/api/config' && $method === 'GET') {
        echo json_encode(Db::config($pdo));
    } elseif ($path === '/api/state' && $method === 'GET') {
        echo json_encode($engine->state());
    } elseif (preg_match('#^/api/partitions/(\w+)/arm$#', $path, $m) && $method === 'POST') {
        // TODO: exigir sesión de usuario y permisos por partición antes de exponerlo fuera de la red local
        $engine->arm($m[1], !empty(body()['armed']));
        echo '{"ok":true}';
    } elseif ($path === '/api/ack' && $method === 'POST') {
        // TODO: exigir sesión de usuario
        $engine->ack();
        echo '{"ok":true}';
    } elseif (preg_match('#^/api/devices/(\w+)/state$#', $path, $m) && $method === 'POST') {
        // Entrada para puentes reales (MQTT, central...). Requiere clave en SEG_API_KEY.
        $key = getenv('SEG_API_KEY') ?: '';
        if ($key === '' || !hash_equals($key, $_SERVER['HTTP_X_API_KEY'] ?? '')) {
            http_response_code(401);
            echo '{"error":"clave de API no válida"}';
        } else {
            $engine->update($m[1], body());
            echo '{"ok":true}';
        }
    } else {
        http_response_code(404);
        echo '{"error":"no encontrado"}';
    }
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log((string)$e);
    echo '{"error":"error interno"}';
}
