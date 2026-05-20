<script setup>
/**
 * APP ROOT COMPONENT
 * 
 * Este componente actúa como el layout principal de la aplicación.
 * Gestiona la HIDRATACIÓN inicial del estado global.
 */
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Navbar from './components/layout/Navbar.vue'
import Footer from './components/layout/Footer.vue'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'

const authStore = useAuthStore()
const gameStore = useGameStore()

/**
 * CICLO DE VIDA: MONTAJE
 * Aquí recuperamos los datos esenciales antes de que el usuario interactúe.
 */
onMounted(() => {
  // Verificamos si hay una sesión activa para recuperar el perfil del usuario.
  if (authStore.token) {
    authStore.fetchUser()
  }

  // Cargamos el catálogo de juegos si aún no está disponible en memoria.
  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-retro-deep text-retro-white">
    <!-- NAVEGACIÓN GLOBAL -->
    <Navbar />
    
    <main class="flex-1">
      <!-- VISTAS DINÁMICAS (Dashboard, Juegos, Perfil) -->
      <RouterView />
    </main>

    <!-- PIE DE PÁGINA (Información y Enlaces) -->
    <Footer />
  </div>
</template>
