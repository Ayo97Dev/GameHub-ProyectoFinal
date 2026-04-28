# Imágenes de Juegos

Coloca aquí las portadas (covers) de los juegos.
Para usarlas en el sistema:

1. Guarda la imagen aquí (ej: `mi-juego.png`).
2. En `src/stores/game.js`, actualiza `DEFAULT_COVERS` para usar la ruta del asset.

Ejemplo con Vite assets:
```javascript
import miJuegoCover from '../assets/images/games/mi-juego.png'
// ...
'mi-juego': miJuegoCover,
```

O si prefieres usar la carpeta `public` para rutas directas:
1. Guarda en `public/images/games/mi-juego.png`.
2. Úsala así: `'mi-juego': '/images/games/mi-juego.png'`.
