# GameHub

GameHub es una plataforma Full-Stack de juegos retro-futuristas. Consta de un frontend en Vue 3 (Vite) + Tailwind CSS y un backend de alto rendimiento en Laravel (Octane) con MariaDB y Redis.

## 🚀 Cómo empezar

Todo el entorno está dockerizado para una configuración inmediata.

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

---

## 🌐 Acceso a la Aplicación

La aplicación utiliza un proxy **Nginx** con soporte para **SSL** y **HTTP/2**.

- **URL Principal (HTTPS):** [https://localhost:8443](https://localhost:8443)
- **URL Alternativa (HTTP):** [http://localhost:8080](http://localhost:8080)
- **Backend API (Directo):** [http://localhost:8000](http://localhost:8000) (Solo para desarrollo)

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

