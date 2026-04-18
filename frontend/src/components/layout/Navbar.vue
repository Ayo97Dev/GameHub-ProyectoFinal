<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { useTheme } from '../../composables/useTheme'
import BaseButton from '../ui/BaseButton.vue'

const authStore = useAuthStore()
const gameStore = useGameStore()
const router = useRouter()
const { isDark, toggleTheme } = useTheme()
const navGames = computed(() => gameStore.games)
const isLoggingOut = ref(false)

onMounted(() => {
  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})

async function handleLogout() {
  if (isLoggingOut.value) return // Prevenir múltiples clics
  
  isLoggingOut.value = true
  try {
    await authStore.logout()
    router.push('/')
  } catch (error) {
    console.error('Error during logout:', error)
    // El logout ya se ejecutó, redirigir de todas formas
    router.push('/')
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b-4 border-retro-black bg-retro-cream dark:border-b-4 dark:border-neon-cyan dark:bg-retro-dark transition-colors duration-300">
    <nav class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center justify-between gap-4">
        <RouterLink to="/" class="text-3xl font-display font-black tracking-widest uppercase gh-title-gradient hover:-translate-y-0.5 active:translate-y-0 transition-transform">
          GameHub
        </RouterLink>
        <span class="border-2 border-retro-black bg-neon-yellow px-2 py-0.5 text-xs font-bold uppercase tracking-widest text-retro-black shadow-[2px_2px_0px_#09090b] dark:border-neon-pink dark:bg-neon-pink/20 dark:text-neon-pink dark:shadow-[2px_2px_0px_#f472b6]">
          Arcade
        </span>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4">
        <button 
          @click="toggleTheme" 
          class="flex items-center justify-center p-2 gh-surface gh-surface-hover w-10 h-10 text-retro-black dark:text-neon-cyan border-2 border-retro-black dark:border-neon-cyan dark:bg-transparent"
          title="Alternar Tema"
        >
          <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <!-- Moon icon -->
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <!-- Sun icon -->
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <RouterLink
            v-for="game in navGames"
            :key="game.slug"
            :to="game.route || `/play/${game.slug}`"
            class="gh-surface gh-surface-hover px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-retro-black dark:text-retro-white dark:bg-transparent"
            active-class="bg-retro-black text-white dark:bg-neon-cyan dark:text-retro-black !shadow-none translate-x-[4px] translate-y-[4px] border-retro-black dark:border-neon-cyan"
          >
            {{ game.title.split(' ')[0] }}
          </RouterLink>

          <template v-if="authStore.isLoggedIn">
            <RouterLink
              to="/profile"
              class="gh-surface gh-surface-hover px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-retro-black dark:text-retro-white dark:bg-transparent"
              active-class="bg-retro-black text-white dark:bg-neon-cyan dark:text-retro-black !shadow-none translate-x-[4px] translate-y-[4px] border-retro-black dark:border-neon-cyan"
            >
              Perfil
            </RouterLink>
            <BaseButton size="sm" @click="handleLogout" :disabled="isLoggingOut" variant="danger">
              {{ isLoggingOut ? 'Cerrando' : 'Salir' }}
            </BaseButton>
          </template>
          <template v-else>
            <RouterLink to="/login">
              <BaseButton size="sm" variant="ghost">Entrar</BaseButton>
            </RouterLink>
            <RouterLink to="/register">
              <BaseButton size="sm" variant="primary">Registro</BaseButton>
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>
  </header>
</template>
