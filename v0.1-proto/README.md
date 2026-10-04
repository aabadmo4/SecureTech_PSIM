# 🛡️ PSIM Web Security Dashboard (Prototipo)

Una interfaz web ligera, interactiva y responsiva para la gestión integral de seguridad física (*Physical Security Information Management* - PSIM), construida completamente en **HTML5, CSS3 y JavaScript vanilla** (sin dependencias externas ni frameworks).

---

## 🌟 Características Principales

- **Plano Interactivo vectorizado (SVG):** Visualización de plantas de instalación con estados de dispositivos representados dinámicamente en tiempo real.
- **Simulación de Vídeo CCTV:** Canvas interactivo que simula señal en vivo de cámaras, incluyendo detección de movimiento y pérdida de señal.
- **Gestión de Particiones:** Control para armar/desarmar áreas independientes (Planta Baja, Zona Noche, Garaje) y armado general del sistema.
- **Monitoreo de Sensores:** Soporte visual para:
  - Cámaras CCTV
  - Contactos magnéticos de puertas/ventanas
  - Sensores PIR de movimiento
  - Sensores de temperatura ambiental
  - Detectores de humo
  - Equipos principales (Central de Alarma, Expansores, NVR)
- **Registro de Eventos (Log):** Consola dinámica con alertas en tiempo real categorizadas por nivel (*Normal*, *Aviso*, *Alarma*).
- **Diseño Adaptativo (Responsive):** Totalmente funcional en escritorios, tabletas y dispositivos móviles.

---

## 🚀 Inicio Rápido

No se requiere ningún paso de compilación o instalación de dependencias.

1. Clona este repositorio:
   ```bash
   git clone https://github.com/tu-usuario/tu-repositorio.git
   ```
2. Abre el archivo `psim.html` en tu navegador preferido.

---

## 🛠️ Tecnologías Utilizadas

* **HTML5 & SVG:** Renderizado de planos arquitectónicos y simbología de seguridad vectorizada.
* **CSS Custom Properties & Grid/Flexbox:** Diseño adaptable con temas dinámicos (modo claro/oscuro).
* **JavaScript Vanilla:** Lógica de simulación de eventos, manipulación del DOM y animación en Canvas HTML5.

---

## 📄 Licencia

Este proyecto está bajo la Licencia **MIT**. Consulta el archivo `LICENSE` para más información.