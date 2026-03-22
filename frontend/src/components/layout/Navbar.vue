<script setup>
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { useTheme } from '../../composables/useTheme'
import BaseButton from '../ui/BaseButton.vue'

const authStore = useAuthStore()
const gameStore = useGameStore()
const router = useRouter()
const { isDark, toggleTheme } = useTheme()

async function handleLogout() {
  await authStore.logout()
  router.push('/')
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-slate-300/80 dark:border-slate-800/70 bg-slate-50/95 dark:bg-zinc-950/85 shadow-sm shadow-slate-200/80 dark:shadow-black/20 backdrop-blur-xl transition-colors duration-300">
    <nav class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center justify-between gap-3">
        <RouterLink to="/" class="text-3xl font-black tracking-[0.06em] text-cyan-600 dark:text-cyan-300 drop-shadow-sm dark:drop-shadow-[0_0_16px_rgba(34,211,238,.35)] transition-colors">
          GameHub
        </RouterLink>
        <span class="rounded-full border border-violet-200 bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:border-violet-400/45 dark:bg-violet-500/10 dark:text-violet-200 transition-colors">
          Arena
        </span>
      </div>

      <div class="flex items-center justify-between gap-3">
        <button 
          @click="toggleTheme" 
          class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition-colors"
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

        <div class="gh-surface gh-neon-ring flex flex-wrap items-center gap-1.5 p-1.5 sm:gap-2">
          <RouterLink
            v-for="game in gameStore.games"
            :key="game.slug"
            :to="game.route || `/play/${game.slug}`"
            class="rounded-md px-3 py-2 text-sm font-semibold uppercase tracking-wide text-slate-600 hover:bg-slate-100 hover:text-cyan-600 dark:text-slate-300 transition-colors dark:hover:bg-slate-800/80 dark:hover:text-cyan-300"
            active-class="bg-slate-100 text-cyan-600 shadow-sm dark:bg-slate-800 dark:text-cyan-300 dark:shadow-[0_0_18px_rgba(34,211,238,.2)]"
          >
            {{ game.title.split(' ')[0] }}
          </RouterLink>

          <template v-if="authStore.isLoggedIn">
            <RouterLink
              to="/profile"
              class="rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-cyan-600 dark:text-slate-300 transition-colors dark:hover:bg-slate-800/80 dark:hover:text-cyan-300"
              active-class="bg-slate-100 text-cyan-600 shadow-sm dark:bg-slate-800 dark:text-cyan-300 dark:shadow-[0_0_18px_rgba(34,211,238,.2)]"
            >
              Perfil
            </RouterLink>
            <BaseButton size="sm" @click="handleLogout">
              Cerrar sesión
            </BaseButton>
          </template>
          <template v-else>
            <RouterLink to="/login">
              <BaseButton size="sm" variant="ghost">Entrar</BaseButton>
            </RouterLink>
            <RouterLink to="/register">
              <BaseButton size="sm">Registro</BaseButton>
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>
  </header>
</template>
