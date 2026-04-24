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
const { isDark } = useTheme()
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
  <header class="sticky top-0 z-40 border-b-4 border-neon-cyan bg-retro-dark transition-colors duration-300">
    <nav class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center justify-between gap-4">
        <RouterLink to="/" class="text-3xl font-display font-black tracking-widest uppercase gh-title-gradient hover:-translate-y-0.5 active:translate-y-0 transition-transform">
          GameHub
        </RouterLink>
        <span class="border-2 border-neon-pink bg-neon-pink/20 px-2 py-0.5 text-xs font-bold uppercase tracking-widest text-neon-pink shadow-[2px_2px_0px_#f472b6]">
          Arcade
        </span>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-4">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <RouterLink
            to="/"
            class="gh-surface gh-surface-hover px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-retro-white dark:bg-transparent flex items-center gap-2"
            active-class="bg-neon-cyan text-retro-black !shadow-none translate-x-[4px] translate-y-[4px] border-neon-cyan"
          >
            <Icon icon="lucide:layout-grid" class="text-lg" />
            Juegos
          </RouterLink>

          <RouterLink
            to="/store"
            class="gh-surface gh-surface-hover px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-neon-yellow dark:bg-transparent border-neon-yellow/50 flex items-center gap-2"
            active-class="bg-neon-yellow text-black dark:bg-neon-yellow dark:text-black !shadow-none translate-x-[4px] translate-y-[4px] border-neon-yellow"
          >
            <Icon icon="lucide:shopping-bag" class="text-lg" />
            Tienda
          </RouterLink>

          <template v-if="authStore.isLoggedIn">
            <RouterLink
              to="/profile"
              class="gh-surface gh-surface-hover px-3 py-1.5 font-display text-xs font-bold uppercase tracking-wider text-retro-white dark:bg-transparent flex items-center gap-2"
              active-class="bg-neon-cyan text-retro-black !shadow-none translate-x-[4px] translate-y-[4px] border-neon-cyan"
            >
              <Icon icon="lucide:user" class="text-lg" />
              Perfil
            </RouterLink>
            <BaseButton size="sm" @click="handleLogout" :disabled="isLoggingOut" variant="danger" class="flex items-center gap-2">
              <Icon :icon="isLoggingOut ? 'lucide:loader-2' : 'lucide:log-out'" :class="{ 'animate-spin': isLoggingOut }" />
              {{ isLoggingOut ? 'Cerrando' : 'Salir' }}
            </BaseButton>
          </template>
          <template v-else>
            <RouterLink to="/login">
              <BaseButton size="sm" variant="ghost" class="flex items-center gap-2">
                <Icon icon="lucide:log-in" />
                Entrar
              </BaseButton>
            </RouterLink>
            <RouterLink to="/register">
              <BaseButton size="sm" variant="primary" class="flex items-center gap-2">
                <Icon icon="lucide:user-plus" />
                Registro
              </BaseButton>
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>
  </header>
</template>
