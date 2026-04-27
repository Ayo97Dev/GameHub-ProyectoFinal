FROM nginx:alpine

RUN apk add --no-cache openssl

# Create directory for SSL certificates
RUN mkdir -p /etc/nginx/ssl

# Generate self-signed certificate if it doesn't exist
# We use a script to allow for persistence if mounted, or just generate it on build
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx.key \
    -out /etc/nginx/ssl/nginx.crt \
    -subj "/C=ES/ST=Madrid/L=Madrid/O=GameHub/CN=localhost"

# Copy configuration
COPY conf.d/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80 443
