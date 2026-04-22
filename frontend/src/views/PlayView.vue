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
  <section class="mx-auto w-full max-w-[100rem] px-4 py-12 relative z-10 flex flex-col space-y-12">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none -z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(0,242,255,0.03),transparent_70%)] pointer-events-none -z-10"></div>

    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-8 border-b-4 border-retro-black pb-8 relative">
      <!-- Corner Ornament -->
      <div class="absolute -top-1 -left-1 size-6 border-t-2 border-l-2 border-neon-cyan/30"></div>
      
      <div class="space-y-4">
        <div class="flex items-center gap-4">
           <span class="font-pixel text-[10px] font-black uppercase tracking-[0.5em] text-neon-yellow">>> SYSTEM_MODE: PLAY</span>
           <div class="h-[2px] w-12 bg-white/5"></div>
        </div>
        <h1 class="text-5xl sm:text-7xl font-display font-black uppercase tracking-tighter text-white leading-none gh-title-glow">
          {{ pageTitle }}
        </h1>
      </div>

      <div class="flex items-center gap-6 bg-retro-black/40 border border-white/5 p-4 relative overflow-hidden group">
         <div class="absolute inset-y-0 left-0 w-1 bg-neon-cyan animate-pulse"></div>
         <div class="flex flex-col items-end">
            <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.4em] mb-1">LATENCIA_RED</span>
            <span class="font-display text-xl font-black text-neon-cyan">24 MS</span>
         </div>
         <Icon icon="lucide:signal" class="text-3xl text-white/10 group-hover:text-neon-cyan transition-colors" />
      </div>
    </header>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px] xl:grid-cols-[280px_minmax(0,1fr)_380px]">
      
      <!-- LEFT SIDEBAR: AD SPACE / TECH DATA -->
      <aside class="hidden xl:flex xl:flex-col gap-8 xl:sticky xl:top-32 h-fit">
        <div class="bg-black border-4 border-retro-black p-6 shadow-[12px_12px_0px_#000] relative overflow-hidden group">
          <div class="absolute top-0 right-0 size-8 border-t-2 border-r-2 border-white/5 group-hover:border-neon-cyan transition-colors"></div>
          <p class="font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/20 border-b border-white/5 pb-2 mb-6">PROTOCOLO_COMERCIAL</p>
          <div class="flex items-center justify-center p-2 bg-retro-black border-2 border-white/5 transition-all group-hover:border-neon-cyan/30 group-hover:bg-neon-cyan/5">
            <MockAd size="skyscraper" :rotate="true" :interval="7000" />
          </div>
          <p class="font-pixel text-[9px] text-white/10 mt-6 uppercase tracking-[0.3em] text-center">END_OF_TRANSMISSION</p>
        </div>

        <div class="bg-black border-4 border-retro-black p-6 shadow-[12px_12px_0px_#000]">
           <p class="font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/20 border-b border-white/5 pb-2 mb-4">KERN_INFO</p>
           <div class="space-y-3">
              <div class="flex justify-between font-pixel text-[9px] text-white/40 uppercase">
                 <span>Mem_Pool:</span>
                 <span class="text-neon-cyan">OK</span>
              </div>
              <div class="flex justify-between font-pixel text-[9px] text-white/40 uppercase">
                 <span>Thread_01:</span>
                 <span class="text-neon-yellow">IDLE</span>
              </div>
           </div>
        </div>
      </aside>

      <!-- MAIN GAME CONTAINER -->
      <main class="order-2 lg:order-1 xl:order-2 bg-black border-4 border-retro-black shadow-[24px_24px_0px_#000] relative overflow-hidden flex flex-col items-center justify-center min-h-[600px] group">
        <!-- Technical HUD Elements -->
        <div class="gh-scanlines pointer-events-none absolute inset-0 z-10 opacity-30"></div>
        <div class="absolute inset-0 border-[20px] border-white/[0.02] pointer-events-none"></div>
        
        <!-- Corner Brackets -->
        <div class="absolute top-6 left-6 size-12 border-t-2 border-l-2 border-white/5 group-hover:border-neon-cyan transition-colors duration-700"></div>
        <div class="absolute bottom-6 right-6 size-12 border-b-2 border-r-2 border-white/5 group-hover:border-neon-pink transition-colors duration-700"></div>

        <!-- Metadata -->
        <div class="absolute top-6 right-6 flex flex-col items-end opacity-20 group-hover:opacity-60 transition-opacity">
           <span class="font-pixel text-[9px] text-white uppercase tracking-[0.3em]">RES: 1920X1080</span>
           <span class="font-pixel text-[9px] text-white uppercase tracking-[0.3em]">RENDER: WEBGL_V2</span>
        </div>

        <component :is="gameComponent" v-if="gameComponent" @live-score="handleLiveScore" class="relative z-20 w-full h-full" />
        <div v-else class="relative z-20 p-12 bg-neon-pink/10 border-2 border-neon-pink/30 flex flex-col items-center gap-6">
           <Icon icon="lucide:alert-triangle" class="text-5xl text-neon-pink animate-pulse" />
           <p class="text-neon-pink font-pixel text-xl uppercase tracking-[0.5em] blink">ERR_MODULE_NOT_FOUND</p>
        </div>
      </main>

      <!-- RIGHT SIDEBAR: STATS & LEADERBOARD -->
      <aside class="order-1 lg:order-2 xl:order-3 lg:sticky lg:top-32 h-fit flex flex-col gap-10">
        
        <!-- Live Score Panel -->
        <div class="bg-black border-4 border-retro-black p-8 shadow-[12px_12px_0px_#000] relative overflow-hidden group">
          <div class="absolute top-0 right-0 size-8 bg-neon-cyan/5 -rotate-45 translate-x-4 -translate-y-4"></div>
          <h2 class="font-display text-xl font-black uppercase text-neon-cyan tracking-tighter border-b-2 border-white/5 pb-4 mb-8">>> ESTADO_SESIÓN</h2>
          <div class="bg-retro-black p-6 border-2 border-neon-cyan/30 shadow-[inset_4px_4px_0px_#000] relative">
            <div class="absolute right-4 top-4 size-2 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
            <p class="font-pixel text-[10px] uppercase tracking-[0.4em] text-white/30 mb-2 font-black">{{ liveScoreLabel }}</p>
            <p class="font-display text-4xl font-black text-white leading-none tracking-tighter">{{ liveScore.toLocaleString() }}</p>
          </div>
        </div>

        <!-- Leaderboard Panel -->
        <div class="bg-black border-4 border-retro-black p-8 shadow-[12px_12px_0px_#000] relative overflow-hidden group">
          <h2 class="font-display text-xl font-black uppercase text-neon-pink tracking-tighter border-b-2 border-white/5 pb-4 mb-8">>> {{ leaderboardLabel }}</h2>
          <div class="space-y-4">
            <div
              v-for="(entry, i) in leaderboard"
              :key="entry.user_id"
              class="flex items-center gap-4 bg-retro-black border border-white/5 p-4 transition-all hover:border-white/10 relative overflow-hidden"
            >
              <!-- Rank Badge -->
              <div class="size-10 shrink-0 flex items-center justify-center font-display text-xl font-black relative"
                :class="i === 0 ? 'text-neon-yellow bg-neon-yellow/10' : 'text-white/20 bg-white/5'"
              >
                {{ i + 1 }}
                <div v-if="i === 0" class="absolute -top-1 -left-1 size-3 border-t-2 border-l-2 border-neon-yellow"></div>
              </div>

              <div class="flex-1 min-w-0">
                <p class="truncate font-display text-sm font-black uppercase text-white tracking-widest">{{ entry.username }}</p>
                <div class="flex items-center gap-2 mt-1">
                   <div class="size-1" :class="i === 0 ? 'bg-neon-yellow' : 'bg-white/10'"></div>
                   <p class="font-pixel text-[8px] text-white/30 uppercase tracking-[0.2em]">NODE_RANK: {{ i + 1 }}</p>
                </div>
              </div>
              <span class="font-display text-lg font-black text-neon-yellow tracking-tighter">
                {{ Number(entry.high_score).toLocaleString() }}
              </span>
            </div>
            
            <div v-if="leaderboard.length === 0" class="flex flex-col items-center py-10 opacity-20 space-y-4">
               <Icon icon="lucide:satellite-dish" class="text-4xl animate-pulse" />
               <p class="font-pixel text-[10px] uppercase tracking-[0.4em]">SYNCING_RANKS...</p>
            </div>
          </div>
        </div>

        <div class="bg-neon-yellow text-black p-4 font-pixel text-[10px] font-black uppercase tracking-[0.3em] flex items-center gap-4 animate-pulse">
           <Icon icon="lucide:alert-circle" class="text-xl" />
           Transmisión_De_Datos_Encriptada_V3
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
