# Dockerfile para Producción - Frontend (Vue + Vite)

# Etapa 1: Construcción
FROM node:20-alpine AS build-stage
WORKDIR /app

# Instalar dependencias
COPY package*.json ./
RUN npm ci

# Copiar código y construir
COPY . .
RUN npm run build

# Etapa 2: Servidor de producción
FROM nginx:alpine

# Copiar los archivos compilados al directorio de Nginx
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Copiar una configuración básica de Nginx para SPA (Single Page Application)
COPY <<'EOF' /etc/nginx/conf.d/default.conf
server {
    listen 80;
    location / {
        root /usr/share/nginx/html;
        index index.html;
        try_files $uri $uri/ /index.html;
    }
}
EOF

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
