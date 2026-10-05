# Sistema de seguridad · panel web sobre plano

Panel tipo SCADA para ver sensores de intrusión, contactos magnéticos, temperatura, humo, cámaras CCTV y el estado de centrales, expansores y grabadores, sobre el plano de la instalación. Con armado y visionado por particiones.

PHP 8.1+ (extensión `pdo_sqlite`) + SQLite. Sin dependencias ni Composer.

## Arranque rápido

```bash
php bin/seed.php                              # crea la base de datos con los datos de ejemplo
php bin/simulator.php &                       # genera eventos simulados (dejar en marcha)
php -S 0.0.0.0:8080 -t public public/index.php
```

Abre http://localhost:8080

## Estructura

| Ruta | Qué hace |
|---|---|
| `public/index.html` | Frontend (SVG + JS sin frameworks). Consulta `/api/state` cada 2 s |
| `public/index.php` | Router de la API |
| `src/Engine.php` | Lógica: estado, armado, alarmas y eventos |
| `src/Db.php` | Conexión SQLite y carga de configuración |
| `db/schema.sql` | Tablas: `partitions`, `plans`, `rooms`, `devices`, `events` |
| `db/seed.json` | Datos de ejemplo (2 plantas, 3 particiones, 41 dispositivos) |
| `bin/simulator.php` | Sustituye al hardware real mientras desarrollas |
| `docs/prototipo-simulado.html` | Prototipo de un solo archivo, sin servidor |

## API

| Método y ruta | Descripción |
|---|---|
| `GET /api/config` | Planos, estancias, particiones y dispositivos |
| `GET /api/state` | Estado en vivo y últimos 10 eventos |
| `POST /api/partitions/{id}/arm` | `{"armed":true}` arma o desarma una partición |
| `POST /api/ack` | Reconoce las alarmas |
| `POST /api/devices/{id}/state` | Entrada para hardware real. Cabecera `X-API-Key` = variable `SEG_API_KEY`. Cuerpo: `{"open":true}`, `{"v":21.5}`, `{"off":true}`... |

Un contacto o PIR solo genera evento y alarma si su partición está armada. El estado del icono se actualiza siempre.

## ⚠️ Pendiente antes de usarlo de verdad

- **Login y permisos.** Armar, desarmar y reconocer alarmas no piden autenticación. No lo expongas fuera de una red local de confianza hasta añadirlo.
- Las credenciales de cámaras y central deben quedarse en el servidor, nunca en el navegador.

## Hoja de ruta

1. Login (sesión) y permisos por usuario y partición.
2. Puente MQTT en PHP CLI (`php-mqtt/client`) que llame a `Engine::update()`.
3. Cámaras reales con [go2rtc](https://github.com/AlexxIT/go2rtc): sustituir el canvas simulado por WebRTC/HLS.
4. SSE en lugar de polling si hace falta menos latencia.
5. Editor de planos (arrastrar iconos y guardar posición).
6. Historial de temperaturas y pantalla de eventos con filtros.
