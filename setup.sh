#!/bin/bash

# Colores para los mensajes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Iniciando la configuración del entorno GameHub...${NC}"

# 1. Crear carpetas
echo -e "${GREEN}Creando carpetas /frontend y /backend...${NC}"
mkdir -p frontend backend

# 2. Generar Backend (Laravel) usando un contenedor temporal de Composer
echo -e "${GREEN}Generando proyecto Laravel en /backend...${NC}"
# Usamos un contenedor temporal para crear el proyecto Laravel
docker run --rm -v $(pwd)/backend:/app composer create-project laravel/laravel .

# Configurar permisos para Laravel
echo -e "${GREEN}Configurando permisos para Laravel...${NC}"
sudo chown -R $USER:$USER backend/
chmod -R 775 backend/storage backend/bootstrap/cache

# Configurar el archivo .env de Laravel
echo -e "${GREEN}Configurando conexión a base de datos en Laravel...${NC}"
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' backend/.env
sed -i 's/DB_HOST=.*/DB_HOST=db/' backend/.env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' backend/.env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=gamehub_db/' backend/.env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=gamehub_user/' backend/.env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=gamehub_password/' backend/.env

# 3. Generar Frontend (Vue 3 + Vite) usando un contenedor temporal de Node
echo -e "${GREEN}Generando proyecto Vue 3 + Vite en /frontend...${NC}"
# Usamos un contenedor temporal para crear el proyecto Vue
docker run --rm -v $(pwd)/frontend:/app -w /app node:20-alpine sh -c "npm create vite@latest . -- --template vue && npm install"

# Instalar Tailwind CSS
echo -e "${GREEN}Instalando Tailwind CSS en el frontend...${NC}"
docker run --rm -v $(pwd)/frontend:/app -w /app node:20-alpine sh -c "npm install -D tailwindcss postcss autoprefixer && npx tailwindcss init -p"

# Configurar Tailwind CSS
echo -e "${GREEN}Configurando Tailwind CSS...${NC}"
cat << 'EOF' > frontend/tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
EOF

cat << 'EOF' > frontend/src/style.css
@tailwind base;
@tailwind components;
@tailwind utilities;
EOF

# Asegurar que main.js/ts importe style.css (Vite por defecto crea style.css y lo importa en main.js)

# Configurar vite.config.js para Hot-Reload en Docker
echo -e "${GREEN}Configurando vite.config.js para Hot-Reload...${NC}"
cat << 'EOF' > frontend/vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    watch: {
      usePolling: true
    }
  }
})
EOF

# Configurar permisos para Frontend
sudo chown -R $USER:$USER frontend/

echo -e "${YELLOW}¡Configuración completada!${NC}"
echo -e "Para levantar el entorno, ejecuta: ${GREEN}docker compose up -d${NC}"
echo -e "Frontend disponible en: ${GREEN}http://localhost:5173${NC}"
echo -e "Backend disponible en: ${GREEN}http://localhost:8000${NC}"
