<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useClickerStore } from '../stores/games/clicker'
import api from '../lib/axios'
import RpgPlaceholder from '../components/games/RpgPlaceholder.vue'
import ClickerPlaceholder from '../components/games/ClickerPlaceholder.vue'
import ArcadePlaceholder from '../components/games/ArcadePlaceholder.vue'

const route      = useRoute()
const gameStore  = useGameStore()
const clicker    = useClickerStore()

const game = computed(() => gameStore.gamesBySlug[route.params.slug])

const FALLBACK_TITLE_BY_SLUG = {
  clicker: 'Reactor de Clics',
  rpg: 'Modo RPG',
  arcade: 'Modo Arcade',
}

const pageTitle = computed(() => {
  if (game.value?.title) return game.value.title
  return FALLBACK_TITLE_BY_SLUG[route.params.slug] ?? 'Modo Juego'
})

const leaderboard = ref([])

const gameComponent = computed(() => {
  if (route.params.slug === 'rpg')     return RpgPlaceholder
  if (route.params.slug === 'clicker') return ClickerPlaceholder
  if (route.params.slug === 'arcade')  return ArcadePlaceholder
  return null
})

const liveScore = computed(() => {
  if (route.params.slug === 'clicker') return Math.floor(clicker.balance)
  return 0
})

onMounted(async () => {
  try {
    const { data } = await api.get(`/leaderboard/${route.params.slug}`)
    leaderboard.value = data.data?.slice(0, 3) ?? []
  } catch {
    leaderboard.value = []
  }
})
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:py-10">
    <header class="mb-5">
      <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-400">Modo Juego</p>
      <h1 class="mt-2 text-2xl font-black text-slate-800 dark:text-white transition-colors sm:text-3xl">
        {{ pageTitle }}
      </h1>
      <p class="mt-1 text-slate-600 dark:text-slate-300 transition-colors">Modo Cine activo: foco en gameplay + estadísticas en vivo.</p>
    </header>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
      <div class="order-2 lg:order-1 gh-panel p-4 sm:p-5">
        <component :is="gameComponent" v-if="gameComponent" />
        <div v-else class="min-h-[320px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-900 p-6 text-slate-600 dark:text-slate-300 transition-colors">
          El slug del juego no es válido.
        </div>
      </div>

      <aside class="order-1 lg:order-2 lg:sticky lg:top-24 h-fit flex flex-col gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-zinc-900 p-4 sm:p-5 shadow-lg shadow-slate-200/50 dark:shadow-black/20 transition-colors">
        <!-- Puntuación en vivo -->
        <div>
          <h2 class="text-base sm:text-lg font-black text-violet-600 dark:text-cyan-300 transition-colors">Estadísticas en vivo</h2>
          <div class="mt-2 space-y-2">
            <div class="rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 transition-colors">
              <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Puntos</p>
              <p class="text-lg sm:text-xl font-bold text-slate-800 dark:text-white">{{ liveScore.toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Top 3 de clasificación -->
        <div>
          <h2 class="text-base font-semibold text-slate-700 dark:text-slate-200 transition-colors">Top 3 — Clasificación</h2>
          <div class="mt-2 space-y-2">
            <div
              v-for="(entry, i) in leaderboard"
              :key="entry.user_id"
              class="flex items-center gap-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 transition-colors"
            >
              <span class="text-lg font-bold w-6 text-center" :class="['#FFD700','#C0C0C0','#CD7F32'][i] ? '' : ''">
                {{ ['🥇','🥈','🥉'][i] }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="truncate text-sm font-medium text-slate-800 dark:text-white">{{ entry.username }}</p>
              </div>
              <span class="text-sm font-bold text-violet-600 dark:text-cyan-400">{{ Number(entry.high_score).toLocaleString() }}</span>
            </div>
            <p v-if="leaderboard.length === 0" class="text-xs text-slate-400 dark:text-slate-500">Aún no hay puntuaciones.</p>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>

