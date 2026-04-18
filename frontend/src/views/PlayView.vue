<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useClickerStore } from '../stores/games/clicker'
import { useConnect4Store } from '../stores/games/connect4'
import { useTowerDefenseStore } from '../stores/games/towerdefense'
import { useLeaderboardStore } from '../stores/leaderboard'
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
const leaderboardStore = useLeaderboardStore()

const game = computed(() => gameStore.gamesBySlug[route.params.slug])

const FALLBACK_TITLE_BY_SLUG = {
  clicker: 'REACTOR_CLICK.EXE',
  rpg: 'RPG_MODULE.EXE',
  quiz: 'QUIZ_MASTER.SYS',
  connect4: 'CONNECT_4.BIN',
  'tower-defense': 'TOWER_DEF.DAT',
}

const pageTitle = computed(() => {
  if (game.value?.title) return game.value.title
  return FALLBACK_TITLE_BY_SLUG[route.params.slug] ?? '>> UNKNOWN_MOD'
})

const leaderboard = computed(() => leaderboardStore.getEntries(route.params.slug).slice(0, 3))
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
  if (route.params.slug === 'connect4') return 'WINS'
  if (route.params.slug === 'tower-defense') return 'WAVES_CLEARED'
  return 'SCORE'
})

const leaderboardLabel = computed(() => {
  if (route.params.slug === 'connect4') return 'TOP_3_WINS'
  if (route.params.slug === 'tower-defense') return 'TOP_3_WAVES'
  return 'TOP_3_RANKING'
})

function handleLiveScore(value) {
  const parsed = Number(value)
  towerDefenseScore.value = Number.isFinite(parsed) ? Math.max(Math.floor(parsed), 0) : 0
}

async function fetchLeaderboard() {
  leaderboardStore.fetchLeaderboard(route.params.slug, true) // Force true to get fresh scores while playing
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
    <header class="mb-5 border-b-4 border-retro-black dark:border-neon-cyan pb-3">
      <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-neon-blue dark:text-neon-yellow">>> SYSTEM_MODE: PLAY</p>
      <h1 class="mt-2 text-3xl font-display font-black uppercase tracking-widest text-retro-black dark:text-retro-white sm:text-4xl">
        {{ pageTitle }}
      </h1>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[240px_minmax(0,1fr)_340px]">
      
      <!-- AD SPACE -->
      <aside class="hidden xl:flex xl:order-1 xl:sticky xl:top-24 h-fit">
        <div class="w-full gh-panel p-3 bg-retro-cream dark:bg-black">
          <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-retro-black dark:text-retro-white border-b-2 border-retro-black dark:border-neon-cyan pb-1 mb-2">SPONSORED_DATA</p>
          <div class="flex items-center justify-center p-1 bg-black border-2 border-retro-black dark:border-neon-cyan">
            <MockAd size="skyscraper" :rotate="true" :interval="7000" />
          </div>
        </div>
      </aside>

      <!-- GAME CONTAINER -->
      <div class="order-2 lg:order-1 xl:order-2 gh-panel p-0 relative overflow-hidden bg-black flex flex-col items-center justify-center min-h-[400px]">
        <div class="gh-scanlines pointer-events-none absolute inset-0 z-10 opacity-20 dark:opacity-30"></div>
        <component :is="gameComponent" v-if="gameComponent" @live-score="handleLiveScore" class="relative z-20 w-full h-full" />
        <div v-else class="relative z-20 p-6 gh-panel text-neon-pink font-pixel text-xl blink border-none shadow-none text-center">
          ERR_MODULE_NOT_FOUND
        </div>
      </div>

      <!-- STATS & LEADERBOARD -->
      <aside class="order-1 lg:order-2 xl:order-3 lg:sticky lg:top-24 h-fit flex flex-col gap-6 gh-panel p-4 sm:p-5 bg-retro-cream dark:bg-black">
        
        <!-- Live Score -->
        <div>
          <h2 class="font-display text-lg font-black uppercase text-neon-blue dark:text-neon-pink border-b-2 border-neon-blue dark:border-neon-pink pb-1">>> LIVE_STATS</h2>
          <div class="mt-3 bg-retro-cream dark:bg-retro-dark border-[3px] border-retro-black dark:border-neon-cyan p-3 shadow-[inset_3px_3px_0px_rgba(0,0,0,0.5)] dark:shadow-[inset_3px_3px_0px_#22d3ee]">
            <p class="font-pixel text-[11px] uppercase tracking-wide text-retro-black dark:text-neon-yellow">{{ liveScoreLabel }}</p>
            <p class="font-sans text-2xl font-bold text-retro-black dark:text-neon-cyan leading-none mt-1">{{ liveScore.toLocaleString() }}</p>
          </div>
        </div>

        <!-- Leaderboard -->
        <div>
          <h2 class="font-display text-lg font-black uppercase text-neon-blue dark:text-neon-pink border-b-2 border-neon-blue dark:border-neon-pink pb-1">>> {{ leaderboardLabel }}</h2>
          <div class="mt-3 space-y-3">
            <div
              v-for="(entry, i) in leaderboard"
              :key="entry.user_id"
              class="flex items-center gap-3 border-[3px] border-retro-black dark:border-neon-pink bg-white dark:bg-retro-dark p-2"
            >
              <span class="font-pixel text-lg font-bold w-8 text-center text-white bg-retro-black dark:bg-neon-pink p-1 block">
                {{ ['1P','2P','3P'][i] }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="truncate font-sans font-bold uppercase text-xs text-retro-black dark:text-retro-white">{{ entry.username }}</p>
              </div>
              <span class="font-sans text-sm font-bold text-neon-blue dark:text-neon-yellow pr-1">
                {{ Number(entry.high_score).toLocaleString() }}
              </span>
            </div>
            <p v-if="leaderboard.length === 0" class="font-pixel text-xs text-retro-black dark:text-retro-white uppercase blink">AWAITING CONNECTIONS...</p>
          </div>
        </div>

      </aside>
    </div>
  </section>
</template>

<style scoped>
.blink {
  animation: blink 1.5s step-start infinite;
}
@keyframes blink {
  50% { opacity: 0; }
}
</style>
