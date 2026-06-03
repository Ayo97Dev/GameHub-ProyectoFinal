# Sobre el projecto.

Este proyecto es un **trabajo de final de curso** realizado entre Pedro Barrera y Ayoze Méndez. El objetivo de este proyecto era realizar un aprendizaje que fue el siguiente:

- Arquitectura cercana a una profesional, usando Laravel Octane para mantener Laravel en activo en memoria, MariaDB y cache Redis para aliviar la carga de la Base de datos. En el Frontend usamos VUE y Tailwind.
- Uso total de Docker en un Monorepo con pipelines para minimizar el error humano.
- BBDD con una filosofia keep-simple y evitando complejidades grandes gracias a los JSON. (7 Tablas en total en la BD)
- Uso de las API de forma intensiva, llamadas continuas, uso de JSON para grandes cantidades de datos simultaneos.
- SPA usando VUE para aglutinar componentes, practicamente toda la pagina es una landing page para los juegos. 
- Uso de Pinia para la gestion de estados y Axios para las llamadas a la API
- Autentificaciones basadas en tokens, usuario encriptado en la BBDD.
- Uso de algoritmos complejos para tener una CPU en ciertos juegos.
- Juegos con complejidades tecnicas usando la minima cantidad de bibliotecas.
- Optimizaciones realizadas en la llamada a la API volviendola asincrona.
- Despliege de pruebas en Digital Ocean, junto a un dominio y usando nginx como proxy inverso para el SSL

# Gamehub.

GameHub es una plataforma Full-Stack de juegos retro-futuristas. Consta de un frontend en Vue 3 (Vite) + Tailwind CSS y un backend de alto rendimiento en Laravel (Octane) con MariaDB y Redis.


## 🚀 Cómo empezar

Todo el entorno está dockerizado para una configuración inmediata. Elige el modo según tu necesidad:

### 🛠️ Entorno de Desarrollo (Local)

Para levantar el proyecto localmente con Hot-Reload:

1.  **Clonar y Levantar**:
    ```bash
    git clone <url-del-repositorio>
    cd GameHub
    docker compose up -d
    ```

2.  **Configurar Backend**:
    ```bash
    # Instalar dependencias
    docker compose exec backend composer install

    # Preparar entorno
    docker compose exec backend php artisan key:generate
    docker compose exec backend php artisan migrate --seed
    docker compose exec backend php artisan storage:link
    ```

3.  **Configurar Frontend**:
    ```bash
    docker compose exec frontend npm install
    ```

4.  **Acceso**:
    - **Frontend:** [http://localhost:5173](http://localhost:5173) (Vite Dev Server)
    - **API Backend:** [http://localhost:8000](http://localhost:8000)
    - **URL Principal (HTTPS):** [https://localhost:8443](https://localhost:8443)
    - **URL Alternativa (HTTP):** [http://localhost:8080](http://localhost:8080)

---

### 🌐 Entorno de Producción (Servidor)

Para desplegar en un servidor real con HTTPS y optimizaciones de rendimiento:

1.  **Configurar Entorno**:
    Copia `.envExample` a `.env` y ajusta los valores necesarios (especialmente las contraseñas de DB y `APP_URL`).

2.  **Levantar Servicios**:
    ```bash
    docker compose -f docker-compose.prod.yml up -d
    ```

3.  **Configurar SSL (Certbot)**:
    Si es la primera vez, genera los certificados para tu dominio:
    ```bash
    docker compose -f docker-compose.prod.yml run --rm --entrypoint certbot certbot certonly --webroot --webroot-path=/var/www/certbot --email admin@gamehubs.games --agree-tos --no-eff-email -d gamehubs.games
    ```
    Luego recarga Nginx para activar HTTPS:
    ```bash
    docker compose -f docker-compose.prod.yml exec nginx nginx -s reload
    ```

4.  **Inicializar Base de Datos**:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend php artisan migrate --seed --force
    ```

5.  **Acceso**:
    - **URL Principal:** [https://gamehubs.games](https://gamehubs.games)
    - **API Backend:** [https://gamehubs.games/api](https://gamehubs.games/api)


---

## 🛠️ Infraestructura y Tecnologías

- **Backend:** Laravel 12 + **Octane (RoadRunner)** para máxima velocidad de respuesta.
- **Frontend:** Vue 3 (Composition API) + Vite + **Tailwind CSS 4**.
- **Gestión de Estado:** **Pinia** en el frontend y **Redis** alpine en el backend.
- **Base de Datos:** MariaDB 10.11.
- **Seguridad:** **Laravel Sanctum** para autenticación stateless y **Nginx** con terminación SSL
- **Orquestación:** **Docker & Docker Compose** para la gestión de 5 servicios (db, backend, frontend, redis, proxy).

---

## 🎮 Catálogo de Juegos

GameHub presenta una variedad de experiencias con dos sistemas de diseño distintivos:

1.  **CoreClicker**: Clicker incremental.
2.  **Descenso al Abismo**: RPG de mazmorras.
3.  **Proyecto Cortafuegos**: Tower Defense táctico.
4.  **Connect 4**: Puzzle clásico.
5.  **Battleship**: hundir la flota.
6.  **Chess**: Ajedrez.

---

## 📊 Arquitectura del Motor de Juego

El backend implementa un patrón `GameService` abstracto que permite:
- **Persistencia Atómica**: Guardado automático cada 30s o al cerrar sesión.
- **Validación de Acciones**: Cada clic o compra es validado en el servidor para prevenir trampas.
- **Sistema de Logros**: `AchievementService` analiza el estado del juego en cada guardado para desbloquear recompensas automáticamente.
- **Rate Limiting**: Middleware `game.throttle` configurable por juego para proteger la API.

---

## 📝 API resumida

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/login` / `/api/register` | No | Autenticación (Sanctum) |
| GET | `/api/user` | Sí | Perfil y estadísticas globales |
| POST | `/api/user/profile` | Sí | Actualizar avatar y bio |
| POST | `/api/user/password` | Sí | Cambiar contraseña |
| GET | `/api/games` | No | Lista de juegos |
| POST | `/api/games/{slug}/play` | Sí | Iniciar sesión de juego |
| POST | `/api/games/{slug}/action` | Sí | Ejecutar acción validada |
| POST | `/api/games/{slug}/save` | Sí | Persistir progreso y logros |
| DELETE | `/api/games/{slug}/reset` | Sí | Reiniciar progreso (Irreversible) |
| GET | `/api/inventory` | Sí | Ver inventario global de items |
| GET | `/api/leaderboard/{slug}` | No | Top 10 mundial |

Ver documentación completa en [`backend/api.md`](backend/api.md).

