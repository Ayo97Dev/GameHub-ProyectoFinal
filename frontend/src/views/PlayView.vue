<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useClickerStore } from '../stores/games/clicker'
import { useConnect4Store } from '../stores/games/connect4'
import { useTowerDefenseStore } from '../stores/games/towerdefense'
import { useBattleshipStore } from '../stores/games/battleship'
import { useChessStore } from '../stores/games/chess'
import { useLeaderboardStore } from '../stores/leaderboard'
import api from '../lib/axios'
import Rpg from '../components/games/Rpg.vue'
import Clicker from '../components/games/Clicker.vue'
import Quiz from '../components/games/Quiz.vue'
import Connect4 from '../components/games/Connect4.vue'
import Towerdefense from '../components/games/Towerdefense.vue'
import Battleship from '../components/games/Battleship.vue'
import Chess from '../components/games/Chess.vue'

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
const battleship = useBattleshipStore()
const chess = useChessStore()
const leaderboardStore = useLeaderboardStore()

const game = computed(() => gameStore.gamesBySlug[route.params.slug])

const FALLBACK_TITLE_BY_SLUG = {
  'core-clicker': 'CORE_SYNC.EXE',
  'descenso-al-abismo': 'ABISMO_INIT.EXE',
  quiz: 'QUIZ_MASTER.SYS',
  connect4: 'CONNECT_4.BIN',
  'proyecto-cortafuegos': 'FIREWALL_DEF.PRO',
  battleship: 'BATTLESHIP.BIN',
  chess: 'CHESS_GRANDMASTER.SYS',
}

const pageTitle = computed(() => {
  if (game.value?.title) return game.value.title
  return FALLBACK_TITLE_BY_SLUG[route.params.slug] ?? '>> UNKNOWN_MOD'
})

const leaderboard = computed(() => leaderboardStore.getEntries(route.params.slug).slice(0, 3))
const towerDefenseScore = ref(0)
const debouncedFetchLeaderboard = useDebouncedFunction(fetchLeaderboard, 300)

// Session Stats
const sessionTime = ref('00:00')
const sessionSeconds = ref(0)
const onlinePlayers = computed(() => gameStore.telemetry.games_telemetry[route.params.slug] ?? 0)

function updateSessionStats() {
  gameStore.fetchTelemetry()
}

const gameComponent = computed(() => {
  if (route.params.slug === 'descenso-al-abismo')     return Rpg
  if (route.params.slug === 'core-clicker') return Clicker
  if (route.params.slug === 'quiz')    return Quiz
  if (route.params.slug === 'connect4') return Connect4
  if (route.params.slug === 'proyecto-cortafuegos') return Towerdefense
  if (route.params.slug === 'battleship') return Battleship
  if (route.params.slug === 'chess') return Chess
  return null
})

const liveScore = computed(() => {
  if (route.params.slug === 'core-clicker') return Math.floor(clicker.balance)
  if (route.params.slug === 'connect4') return connect4.wins
  if (route.params.slug === 'proyecto-cortafuegos') return Math.floor(towerDefense.gameState?.wave ?? 0)
  if (route.params.slug === 'battleship') return battleship.wins
  if (route.params.slug === 'chess') return chess.wins
  return 0
})

const liveScoreLabel = computed(() => {
  if (route.params.slug === 'connect4') return 'VICTORIAS'
  if (route.params.slug === 'proyecto-cortafuegos') return 'Oleadas superadas'
  if (route.params.slug === 'battleship') return 'VICTORIAS'
  if (route.params.slug === 'chess') return 'VICTORIAS'
  return 'Puntuación'
})

const leaderboardLabel = computed(() => {
  if (route.params.slug === 'connect4') return 'Mejores puntuaciones'
  if (route.params.slug === 'proyecto-cortafuegos') return 'Mejores puntuaciones'
  return 'Mejores puntuaciones'
})

function handleLiveScore(value) {
  const parsed = Number(value)
  towerDefenseScore.value = Number.isFinite(parsed) ? Math.max(Math.floor(parsed), 0) : 0
}

async function fetchLeaderboard() {
  leaderboardStore.fetchLeaderboard(route.params.slug, true) // Force true to get fresh scores while playing
}

let statsInterval = null

onMounted(() => {
  fetchLeaderboard()
  updateSessionStats()
  
  statsInterval = setInterval(() => {
    sessionSeconds.value++
    const m = Math.floor(sessionSeconds.value / 60).toString().padStart(2, '0')
    const s = (sessionSeconds.value % 60).toString().padStart(2, '0')
    sessionTime.value = `${m}:${s}`
    
    if (sessionSeconds.value % 10 === 0) {
      updateSessionStats()
    }
  }, 1000)
})

onUnmounted(() => {
  if (statsInterval) clearInterval(statsInterval)
})

watch(() => route.params.slug, () => {
  towerDefenseScore.value = 0 // Reset score when changing game
  sessionSeconds.value = 0
  sessionTime.value = '00:00'
  updateSessionStats()
  debouncedFetchLeaderboard()
})
</script>

<template>
  <section class="mx-auto w-full max-w-[96rem] px-3 py-8 sm:px-4 lg:px-5 xl:px-6 sm:py-10">
    <header class="mb-5 border-b-4 border-neon-cyan pb-3">
      <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-neon-yellow">>> Modo: Juego</p>
      <h1 class="mt-2 text-3xl font-display font-black uppercase tracking-widest text-retro-white sm:text-4xl">
        {{ pageTitle }}
      </h1>
    </header>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">

      <!-- GAME CONTAINER -->
      <div class="flex flex-col gap-8">
        <div class="gh-panel p-0 relative overflow-hidden bg-black border-4 border-retro-black flex flex-col items-center justify-center min-h-[500px] shadow-[8px_8px_0_#000]">
          <div class="gh-scanlines pointer-events-none absolute inset-0 z-10 opacity-30"></div>
          <component :is="gameComponent" v-if="gameComponent" @live-score="handleLiveScore" class="relative z-20 w-full h-full" />
          <div v-else class="relative z-20 p-6 gh-panel text-neon-pink font-pixel text-xl blink border-none shadow-none text-center">
            Error: Juego no encontrado
          </div>
        </div>
      </div>

      <!-- RIGHT SIDEBAR: STATS & SECONDARY AD -->
      <aside class="order-1 lg:order-2 xl:order-3 lg:sticky lg:top-24 h-fit flex flex-col gap-8">
        
        <!-- STATS PANEL -->
        <div class="gh-panel p-5 bg-black border-4 border-neon-pink shadow-[8px_8px_0_#000] space-y-8">
          
          <!-- Session Data -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-retro-dark border-[3px] border-neon-cyan p-3 shadow-[inset_3px_3px_0px_#000]">
              <p class="font-pixel text-[9px] uppercase tracking-wide text-white/40">Sesión</p>
              <p class="font-sans text-xl font-bold text-neon-cyan leading-none mt-1">{{ sessionTime }}</p>
            </div>
            <div class="bg-retro-dark border-[3px] border-neon-yellow p-3 shadow-[inset_3px_3px_0px_#000]">
              <p class="font-pixel text-[9px] uppercase tracking-wide text-white/40">Jugadores</p>
              <p class="font-sans text-xl font-bold text-neon-yellow leading-none mt-1">{{ onlinePlayers }}</p>
            </div>
          </div>

          <!-- Live Score -->
          <div>
            <h2 class="font-display text-lg font-black uppercase text-neon-pink border-b-2 border-neon-pink pb-1">>> Estadísticas en vivo</h2>
            <div class="mt-3 bg-retro-dark border-[3px] border-neon-cyan p-3 shadow-[inset_3px_3px_0px_#000]">
              <p class="font-pixel text-[11px] uppercase tracking-wide text-neon-yellow">{{ liveScoreLabel }}</p>
              <p class="font-sans text-2xl font-bold text-neon-cyan leading-none mt-1">{{ liveScore.toLocaleString() }}</p>
            </div>
          </div>

          <!-- Leaderboard -->
          <div>
            <h2 class="font-display text-lg font-black uppercase text-neon-pink border-b-2 border-neon-pink pb-1">>> {{ leaderboardLabel }}</h2>
            <div class="mt-3 space-y-3">
              <div
                v-for="(entry, i) in leaderboard"
                :key="entry.user_id"
                class="flex items-center gap-3 border-[3px] border-neon-pink bg-retro-dark p-2"
              >
                <span class="font-pixel text-lg font-bold w-8 text-center text-black bg-neon-pink p-1 block">
                  {{ ['1P','2P','3P'][i] }}
                </span>
                <div class="flex-1 min-w-0">
                  <p class="truncate font-sans font-bold uppercase text-xs text-retro-white">{{ entry.username }}</p>
                </div>
                <span class="font-sans text-sm font-bold text-neon-yellow pr-1">
                  {{ Number(entry.high_score).toLocaleString() }}
                </span>
              </div>
              <p v-if="leaderboard.length === 0" class="font-pixel text-xs text-retro-white uppercase blink">Esperando resultados...</p>
            </div>
          </div>
        </div>

      </aside>
    </div>
  </section>
</template>

<style scoped>
/* Estilos globales en style.css */
</style>
