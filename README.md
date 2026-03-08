# GameHub

GameHub es una aplicación Full-Stack que consta de un frontend en Vue 3 (Vite) + Tailwind CSS y un backend en Laravel (API REST) con una base de datos MariaDB. Todo el entorno de desarrollo está dockerizado para facilitar su configuración y ejecución.

## Escenario 1: Inicializar el proyecto desde cero (No hagais esto, porque ya esta laravel y vue instalados)

Si estás creando el proyecto por primera vez y las carpetas `frontend` y `backend` están vacías o no existen, sigue estos pasos:

1. Clona este repositorio base (si aplica) o sitúate en la carpeta raíz del proyecto.
2. Da permisos de ejecución al script de configuración:
   ```bash
   chmod +x setup.sh
   ```
3. Ejecuta el script de configuración. Este script descargará las imágenes necesarias, creará el esqueleto de Laravel, el proyecto de Vue, instalará Tailwind CSS y configurará las variables de entorno automáticamente:
   ```bash
   ./setup.sh
   ```
   *(Nota: Es posible que el script te pida tu contraseña de usuario (`sudo`) para ajustar los permisos de los archivos generados por Docker).*
4. Una vez finalizado el script, levanta los contenedores en segundo plano:
   ```bash
   docker compose up -d
   ```

---

## Escenario 2: Clonar el repositorio ya existente (Con código pero sin usar devcontainer)

Si otro desarrollador ya ha inicializado el proyecto (o lo estás clonando en otra máquina) y las carpetas `frontend` y `backend` **ya contienen el código fuente**, NO debes ejecutar `setup.sh`. Sigue estos pasos:

1. Clona el repositorio:
   ```bash
   git clone <url-del-repositorio>
   cd GameHub
   ```
2. Levanta los contenedores de Docker:
   ```bash
   docker compose up -d
   ```
3. Instala las dependencias del Backend (Laravel):
   ```bash
   docker compose exec backend composer install
   ```
4. Configura el entorno del Backend:
   ```bash
   # Copia el archivo de ejemplo si no existe el .env
   cp backend/.env.example backend/.env
   
   # Genera la clave de la aplicación
   docker compose exec backend php artisan key:generate
   
   # Ejecuta las migraciones de la base de datos
   docker compose exec backend php artisan migrate
   ```
   *Asegúrate de que el archivo `backend/.env` tenga las credenciales correctas de la base de datos (ver sección "Variables de Entorno").*
5. Instala las dependencias del Frontend (Vue):
   ```bash
   docker compose exec frontend npm install
   ```

---

## Escenario 3: Desarrollo con VS Code (Dev Containers)

Esta parte es la que se debe usar, ya he preparado las herramientas y el codigo para el proyecto. El Devcontainer realiza todo lo necesario para que no haya problemas.

1. Asegúrate de tener instalada la extensión **Dev Containers** (`ms-vscode-remote.remote-containers`) en VS Code.
2. Abre la carpeta del proyecto en VS Code.
3. Abre la paleta de comandos (`Ctrl+Shift+P` o `Cmd+Shift+P`).
4. Escribe y selecciona: **Dev Containers: Reopen in Container**.
5. VS Code construirá el entorno, levantará los servicios de Docker Compose. En caso de que de error, pon el siguiente comando fuera del docker, en el WSL: `docker compose run --rm backend composer install --no-interaction` Esto se debe a que no se instala bien las dependencias y da error. Luego cierra los dockers activos con `docker compose down` y vuelve a abrir el devcontainer. 
6. **¡Listo!** El `postCreateCommand` comprobará e instalará dependencias del backend solo si faltan (`vendor`), además de preparar `.env` y generar la `APP_KEY` si no existe.
7. El `docker-compose` levanta `db`, `backend` y `frontend` simultáneamente desde el DevContainer.
8. Para iniciar el frontend, se debe hacer lo siguiente:
   ```bash
   cd frontend
   npm ci
   npm run dev
   ```

---

## Acceso a la Aplicación

Una vez que los contenedores estén corriendo, puedes acceder a los servicios en las siguientes URLs:

- **Frontend (Vue 3 + Vite):** [http://localhost:5173](http://localhost:5173)
- **Backend (Laravel API):** [http://localhost:8000](http://localhost:8000)
- **Base de Datos (MariaDB):** Puerto `3306` en `localhost`

---

## Variables de Entorno (Base de Datos)

Si necesitas configurar manualmente el archivo `backend/.env`, estas son las credenciales que coinciden con la configuración de `docker-compose.yml`:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gamehub_db
DB_USERNAME=gamehub_user
DB_PASSWORD=gamehub_password
```
*Importante: `DB_HOST` debe ser `db` (el nombre del servicio en Docker), no `localhost`.*

---

## Arquitectura del Proyecto

### Backend (Laravel)
- **Autenticación:** Laravel Sanctum (tokens Bearer stateless)
- **Motor de juego:** Patrón `GameService` abstracto con subclases por juego (`ClickerGameService`, etc.)
- **Servicios:** `AchievementService` para comprobar y desbloquear logros automáticamente
- **Middleware:** `game.throttle` — rate limiting por usuario/juego (configurable en `games.config`)
- **Directorio de servicios:** `backend/app/Services/`

### Frontend (Vue 3)
- **Stores por juego:** `src/stores/games/clicker.js`, expandible a más juegos
- **Motor de API:** `src/lib/gameEngineService.js` — interfaz centralizada para todas las llamadas al game engine
- **Vistas disponibles:** Home, Login, Register, Dashboard, Play (por slug), Leaderboard (por slug), Achievements
- **Guard de rutas:** Las rutas `dashboard`, `play` y `achievements` requieren autenticación

## API resumida

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/register` | No | Registro |
| POST | `/api/login` | No | Login |
| POST | `/api/logout` | Sí | Logout |
| GET | `/api/user` | Sí | Perfil del usuario |
| GET | `/api/games` | No | Lista de juegos activos |
| GET | `/api/games/{slug}` | No | Detalle de un juego |
| POST | `/api/games/{slug}/play` | Sí | Iniciar sesión de juego |
| POST | `/api/games/{slug}/action` | Sí | Ejecutar acción de juego |
| GET | `/api/games/{slug}/load` | Sí | Cargar progreso |
| POST | `/api/games/{slug}/complete` | Sí | Finalizar sesión |
| GET | `/api/leaderboard/{slug}` | No | Top 10 del juego |
| GET | `/api/achievements` | Sí | Todos los logros del usuario |
| GET | `/api/achievements/{slug}` | Sí | Logros por juego |

Ver documentación completa en [`backend/api.md`](backend/api.md).

