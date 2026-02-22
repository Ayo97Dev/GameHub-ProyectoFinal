<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import BaseButton from '../ui/BaseButton.vue'

const authStore = useAuthStore()
const gameStore = useGameStore()

const authLabel = computed(() => (authStore.isLoggedIn ? 'Cerrar sesión' : 'Mock Login'))

function handleAuthAction() {
  if (authStore.isLoggedIn) {
    authStore.logout()
    return
  }

  authStore.login()
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-slate-800/70 bg-zinc-950/85 backdrop-blur-xl">
    <nav class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center justify-between gap-3">
        <RouterLink to="/" class="text-2xl font-bold tracking-wide text-cyan-300 drop-shadow-[0_0_16px_rgba(34,211,238,.35)]">
          GameHub
        </RouterLink>
        <span class="rounded-full border border-violet-400/45 bg-violet-500/10 px-2.5 py-1 text-xs font-semibold text-violet-200">
          Dark Arena
        </span>
      </div>

      <div class="gh-surface gh-neon-ring flex flex-wrap items-center gap-1.5 p-1.5 sm:gap-2">
        <RouterLink
          v-for="game in gameStore.games"
          :key="game.slug"
          :to="game.route"
          class="rounded-md px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800/80 hover:text-cyan-300"
          active-class="bg-slate-800 text-cyan-300 shadow-[0_0_18px_rgba(34,211,238,.2)]"
        >
          {{ game.title.split(' ')[0] }}
        </RouterLink>

        <RouterLink
          to="/dashboard"
          class="rounded-md px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-800/80 hover:text-cyan-300"
          active-class="bg-slate-800 text-cyan-300 shadow-[0_0_18px_rgba(34,211,238,.2)]"
        >
          Perfil
        </RouterLink>

        <BaseButton size="sm" @click="handleAuthAction">
          {{ authLabel }}
        </BaseButton>
      </div>
    </nav>
  </header>
</template>
