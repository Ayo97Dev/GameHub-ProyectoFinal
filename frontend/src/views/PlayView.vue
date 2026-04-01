<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useClickerStore } from '../stores/games/clicker'
import { useConnect4Store } from '../stores/games/connect4'
import { useTowerDefenseStore } from '../stores/games/towerdefense'
import api from '../lib/axios'
import Rpg from '../components/games/Rpg.vue'
import Clicker from '../components/games/Clicker.vue'
import Quiz from '../components/games/Quiz.vue'
import Connect4 from '../components/games/Connect4.vue'
import Towerdefense from '../components/games/Towerdefense.vue'
import MockAd from '../components/ads/MockAd.vue'

// Helper para debounce
function useDebouncedFunction(fn, delay = 300) {
  let timeoutId = null
  return function(...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}

const route      = useRoute()
const gameStore  = useGameStore()
const clicker    = useClickerStore()
const connect4   = useConnect4Store()
const towerDefense = useTowerDefenseStore()

const game = computed(() => gameStore.gamesBySlug[route.params.slug])

const FALLBACK_TITLE_BY_SLUG = {
  clicker: 'Reactor de Clics',
  rpg: 'Modo RPG',
  quiz: 'Modo Quiz',
  connect4: 'Conecta 4',
  'tower-defense': 'Tower Defense',
}

const pageTitle = computed(() => {
  if (game.value?.title) return game.value.title
  return FALLBACK_TITLE_BY_SLUG[route.params.slug] ?? 'Modo Juego'
})

const leaderboard = ref([])
const towerDefenseScore = ref(0)
const debouncedFetchLeaderboard = useDebouncedFunction(fetchLeaderboard, 300)

const gameComponent = computed(() => {
  if (route.params.slug === 'rpg')     return Rpg
  if (route.params.slug === 'clicker') return Clicker
  if (route.params.slug === 'quiz')    return Quiz
  if (route.params.slug === 'connect4') return Connect4
  if (route.params.slug === 'tower-defense') return Towerdefense
  return null
})

const liveScore = computed(() => {
  if (route.params.slug === 'clicker') return Math.floor(clicker.balance)
  if (route.params.slug === 'connect4') return connect4.wins
  if (route.params.slug === 'tower-defense') return Math.floor(towerDefense.gameState?.wave ?? 0)
  return 0
})

const liveScoreLabel = computed(() => {
  if (route.params.slug === 'connect4') return 'Victorias'
  if (route.params.slug === 'tower-defense') return 'Rondas superadas'
  return 'Puntos'
})

const leaderboardLabel = computed(() => {
  if (route.params.slug === 'connect4') return 'Top 3 — Victorias'
  if (route.params.slug === 'tower-defense') return 'Top 3 — Rondas'
  return 'Top 3 — Clasificación'
})

function handleLiveScore(value) {
  const parsed = Number(value)
  towerDefenseScore.value = Number.isFinite(parsed) ? Math.max(Math.floor(parsed), 0) : 0
}

async function fetchLeaderboard() {
  try {
    const { data } = await api.get(`/leaderboard/${route.params.slug}`)
    leaderboard.value = data.data?.slice(0, 3) ?? []
  } catch {
    leaderboard.value = []
  }
}

onMounted(() => {
  fetchLeaderboard()
})

watch(() => route.params.slug, () => {
  towerDefenseScore.value = 0 // Reset score when changing game
  debouncedFetchLeaderboard()
})
</script>

<template>
  <section class="mx-auto w-full max-w-[96rem] px-3 py-8 sm:px-4 lg:px-5 xl:px-6 sm:py-10">
    <header class="mb-5">
      <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-400">Modo Juego</p>
      <h1 class="mt-2 text-2xl font-black text-slate-800 dark:text-white transition-colors sm:text-3xl">
        {{ pageTitle }}
      </h1>
      <p class="mt-1 text-slate-600 dark:text-slate-300 transition-colors">Modo Cine activo: foco en gameplay + estadísticas en vivo.</p>
    </header>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[240px_minmax(0,1fr)_340px]">
      <aside class="hidden xl:flex xl:order-1 xl:sticky xl:top-24 h-fit">
        <div class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-zinc-900 p-3 shadow-lg shadow-slate-200/50 dark:shadow-black/20 transition-colors">
          <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Publicidad</p>
          <div class="mt-2 flex items-center justify-center px-1 py-2">
            <MockAd size="skyscraper" :rotate="true" :interval="7000" />
          </div>
        </div>
      </aside>

      <div class="order-2 lg:order-1 xl:order-2 gh-panel p-4 sm:p-5">
        <component :is="gameComponent" v-if="gameComponent" @live-score="handleLiveScore" />
        <div v-else class="min-h-[320px] rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-900 p-6 text-slate-600 dark:text-slate-300 transition-colors">
          El slug del juego no es válido.
        </div>
      </div>

      <aside class="order-1 lg:order-2 xl:order-3 lg:sticky lg:top-24 h-fit flex flex-col gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-zinc-900 p-4 sm:p-5 shadow-lg shadow-slate-200/50 dark:shadow-black/20 transition-colors">
        <!-- Puntuación en vivo -->
        <div>
          <h2 class="text-base sm:text-lg font-black text-violet-600 dark:text-cyan-300 transition-colors">Estadísticas en vivo</h2>
          <div class="mt-2 space-y-2">
            <div class="rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 transition-colors">
              <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ liveScoreLabel }}</p>
              <p class="text-lg sm:text-xl font-bold text-slate-800 dark:text-white">{{ liveScore.toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Top 3 de clasificación -->
        <div>
          <h2 class="text-base font-semibold text-slate-700 dark:text-slate-200 transition-colors">{{ leaderboardLabel }}</h2>
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
              <span class="text-sm font-bold text-violet-600 dark:text-cyan-400">
                {{ Number(entry.high_score).toLocaleString() }}
              </span>
            </div>
            <p v-if="leaderboard.length === 0" class="text-xs text-slate-400 dark:text-slate-500">Aún no hay puntuaciones.</p>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>

