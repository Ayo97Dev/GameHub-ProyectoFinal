# GameHub

GameHub es una aplicación Full-Stack que consta de un frontend en Vue 3 (Vite) + Tailwind CSS y un backend en Laravel (API REST) con una base de datos MariaDB. Todo el entorno de desarrollo está dockerizado para facilitar su configuración y ejecución.

## Requisitos Previos

Para ejecutar este proyecto, solo necesitas tener instalado:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- (Opcional pero recomendado) [Visual Studio Code](https://code.visualstudio.com/) con la extensión [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers).

---

## Escenario 1: Inicializar el proyecto desde cero (Primera vez)

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

## Escenario 2: Clonar el repositorio ya existente (Con código)

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

Si usas Visual Studio Code, este proyecto incluye una configuración completa de `.devcontainer` que te permite desarrollar dentro del contenedor con todas las herramientas y extensiones preconfiguradas.

**¿Cómo funciona?**
Aunque el DevContainer se "conecta" al contenedor del backend, **levanta todos los servicios** (Frontend, Backend y Base de Datos). El contenedor del backend tiene montada la carpeta raíz del proyecto, por lo que podrás ver y editar tanto el código de Vue como el de Laravel desde la misma ventana de VS Code.

1. Asegúrate de tener instalada la extensión **Dev Containers** (`ms-vscode-remote.remote-containers`) en VS Code.
2. Abre la carpeta del proyecto en VS Code.
3. Abre la paleta de comandos (`Ctrl+Shift+P` o `Cmd+Shift+P`).
4. Escribe y selecciona: **Dev Containers: Reopen in Container**.
5. VS Code construirá el entorno, levantará los servicios de Docker Compose.
6. **¡Listo!** El `postCreateCommand` comprobará e instalará dependencias del backend solo si faltan (`vendor`), además de preparar `.env` y generar la `APP_KEY` si no existe.
7. El `docker-compose` levanta `db`, `backend` y `frontend` simultáneamente desde el DevContainer.
8. El contenedor de `backend` incluye Node.js/NPM, así que también puedes lanzar Vite desde la terminal del DevContainer si lo necesitas:
   ```bash
   cd frontend
   npm ci
   npm run dev -- --host 0.0.0.0 --port 5173
   ```

---

## Acceso a la Aplicación

Una vez que los contenedores estén corriendo, puedes acceder a los servicios en las siguientes URLs:

- **Frontend (Vue 3 + Vite):** [http://localhost:5173](http://localhost:5173) (Con Hot-Reloading habilitado)
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

## Comandos Útiles

Aquí tienes algunos comandos frecuentes que podrías necesitar durante el desarrollo:

**Ver logs de los contenedores:**
```bash
docker compose logs -f           # Todos los servicios
docker compose logs -f backend   # Solo el backend
docker compose logs -f frontend  # Solo el frontend
```

**Validar frontend activo (Vite):**
```bash
docker compose ps frontend
ss -ltnp | grep 5173
curl -I http://localhost:5173
```

**Ejecutar comandos de Artisan (Laravel):**
```bash
docker compose exec backend php artisan make:controller MiControlador
docker compose exec backend php artisan migrate
```

**Ejecutar comandos de NPM (Vue):**
```bash
docker compose exec frontend npm install <paquete>
```

**Detener los contenedores:**
```bash
docker compose down
```