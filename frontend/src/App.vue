<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Navbar from './components/layout/Navbar.vue'
import Footer from './components/layout/Footer.vue'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'
import { useTheme } from './composables/useTheme'

const authStore = useAuthStore()
const gameStore = useGameStore()
// Initialize theme
useTheme()

onMounted(() => {
  if (authStore.token) {
    authStore.fetchUser()
  }

  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />
    <main class="flex-1">
      <RouterView />
    </main>
    <Footer />
  </div>
</template>
