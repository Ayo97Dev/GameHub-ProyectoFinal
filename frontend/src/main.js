/**
 * ENTRY POINT - FRONTEND
 * 
 * Este archivo inicializa la instancia principal de Vue y registra los 
 * plugins globales necesarios para el funcionamiento de GameHub.
 */
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { Icon } from '@iconify/vue'

const app = createApp(App)

/**
 * REGISTRO DE COMPONENTES GLOBALES
 * Usamos Icon de Iconify globalmente para evitar importaciones repetitivas 
 * en cada componente de juego. ESTO REDUCE EL BOILERPLATE.
 */
app.component('Icon', Icon)

/**
 * ESTADO GLOBAL (PINIA)
 * Centralizamos la lógica de autenticación y datos de juegos.
 */
app.use(createPinia())

/**
 * ENRUTAMIENTO
 * Maneja la navegación entre el dashboard y las vistas de juego.
 */
app.use(router)

app.mount('#app')
