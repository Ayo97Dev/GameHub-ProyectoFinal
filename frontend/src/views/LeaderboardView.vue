<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useLeaderboardStore } from '../stores/leaderboard'
import { useGameStore } from '../stores/game'

const leaderboardStore = useLeaderboardStore()
const gameStore = useGameStore()

const route = useRoute()
const slug  = route.params.slug

const entries = computed(() => leaderboardStore.getEntries(slug))
const gameName = computed(() => gameStore.gamesBySlug[slug]?.title ?? slug)
const isLoading = computed(() => leaderboardStore.isLoading(slug) && entries.value.length === 0)
const isConnect4 = computed(() => slug === 'connect4')
const leaderboardSubtitle = computed(() => {
  if (isConnect4.value) return 'TOP_PLAYERS // WINS'
  return 'ALL-TIME_HIGH_SCORES'
})
const scoreLabel = computed(() => {
  if (isConnect4.value) return 'WINS'
  return 'PTS'
})

onMounted(async () => {
  // Ensure games are loaded to get the proper title
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
  
  leaderboardStore.fetchLeaderboard(slug)
})
</script>

<template>
  <section class="mx-auto w-full max-w-5xl px-4 py-16 relative z-10 space-y-12">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none -z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(0,242,255,0.05),transparent_70%)] pointer-events-none -z-10"></div>

    <header class="mb-10 flex flex-col items-center border-4 border-retro-black bg-black p-10 shadow-[16px_16px_0px_#000] relative overflow-hidden">
      <!-- Corner Ornaments -->
      <div class="absolute -top-1 -left-1 size-8 border-t-4 border-l-4 border-neon-yellow"></div>
      <div class="absolute -bottom-1 -right-1 size-8 border-b-4 border-r-4 border-neon-yellow"></div>
      
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,252,0,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,252,0,0.02)_1px,transparent_1px)] bg-[size:30px_30px]"></div>

      <p class="relative z-10 font-pixel text-xs font-bold uppercase tracking-[0.5em] text-neon-pink">>> NETWORK_RANKINGS_V.2.4</p>
      <h1 class="relative z-10 mt-4 text-5xl sm:text-7xl font-display font-black uppercase text-white text-center tracking-tighter leading-[0.8] gh-title-glow">
        Hall_Of_Fame
      </h1>
      <div class="relative z-10 mt-6 flex items-center gap-4">
         <div class="h-[2px] w-8 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
         <h2 class="text-2xl font-display font-black uppercase text-neon-cyan tracking-widest">
           [{{ gameName }}]
         </h2>
         <div class="h-[2px] w-8 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
      </div>
      <p class="relative z-10 mt-8 bg-neon-yellow text-black font-pixel text-[10px] px-4 py-1.5 uppercase tracking-[0.2em] font-black shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">{{ leaderboardSubtitle }}</p>
    </header>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 bg-retro-black/40 border-4 border-dashed border-white/5 space-y-6">
      <div class="relative size-16">
         <div class="absolute inset-0 border-4 border-neon-yellow/20"></div>
         <div class="absolute inset-0 border-t-4 border-neon-yellow animate-spin"></div>
      </div>
      <p class="text-neon-yellow font-pixel text-xl uppercase tracking-[0.5em] blink">FETCHING_RECORDS_FROM_NODE...</p>
    </div>

    <div v-else-if="entries.length === 0" class="flex flex-col items-center justify-center py-24 bg-retro-black/40 border-4 border-white/5">
      <Icon icon="lucide:database-zap" class="text-6xl text-white/10 mb-6" />
      <p class="text-white/40 font-pixel text-xl uppercase tracking-[0.4em]">SERVER DB IS EMPTY. BE THE FIRST.</p>
    </div>

    <ol v-else class="space-y-6">
      <li
        v-for="(entry, i) in entries"
        :key="entry.user_id"
        class="flex flex-col sm:flex-row sm:items-center gap-6 bg-black border-4 p-6 transition-all relative overflow-hidden group"
        :class="i === 0 ? 'border-neon-yellow shadow-[12px_12px_0px_#000] bg-neon-yellow/5'
               : i === 1 ? 'border-white/20 shadow-[10px_10px_0px_#000]'
               : i === 2 ? 'border-neon-pink shadow-[8px_8px_0px_#000] bg-neon-pink/5'
               : 'border-retro-black shadow-[8px_8px_0px_#000] opacity-80 hover:opacity-100 hover:border-white/10'"
      >
        <!-- Background rank text -->
        <div class="absolute -right-4 -bottom-8 font-display text-[120px] font-black text-white/[0.03] select-none group-hover:text-white/[0.05] transition-colors">
           #{{ i + 1 }}
        </div>

        <div class="flex items-center gap-6 flex-1 min-w-0">
           <div class="size-16 shrink-0 flex items-center justify-center font-display text-4xl font-black relative"
             :class="i === 0 ? 'text-neon-yellow' : i === 1 ? 'text-white' : i === 2 ? 'text-neon-pink' : 'text-white/20'"
           >
             {{ i + 1 }}
             <div v-if="i < 3" class="absolute -top-1 -left-1 size-4 border-t-2 border-l-2" :class="i === 0 ? 'border-neon-yellow' : i === 1 ? 'border-white' : 'border-neon-pink'"></div>
           </div>

           <div class="flex items-center gap-6 flex-1 min-w-0 border-l-2 border-white/5 pl-6">
             <div class="size-14 border-2 flex items-center justify-center shrink-0 shadow-[4px_4px_0px_#000] transition-transform group-hover:scale-105"
               :class="i === 0 ? 'border-neon-yellow bg-neon-yellow/10 text-neon-yellow' : 'border-neon-cyan bg-neon-cyan/10 text-neon-cyan'"
             >
               <span class="font-pixel text-2xl font-black">{{ entry.username?.[0]?.toUpperCase() }}</span>
             </div>
             <div class="flex flex-col min-w-0">
                <p class="truncate font-display text-3xl font-black text-white uppercase tracking-tighter leading-none mb-1">{{ entry.username }}</p>
                <div class="flex items-center gap-3">
                   <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.2em]">USER_ID: {{ entry.user_id }}</span>
                   <div v-if="entry.time_played" class="h-1 w-1 bg-white/20"></div>
                   <p v-if="entry.time_played" class="font-pixel text-[10px] text-neon-cyan/50 uppercase tracking-[0.2em]">
                     {{ Math.floor(entry.time_played / 60) }}m_SESIÓN
                   </p>
                </div>
             </div>
           </div>
        </div>

        <div class="sm:text-right shrink-0 mt-4 sm:mt-0 border-t-2 sm:border-0 border-white/5 pt-4 sm:pt-0 relative z-10">
          <p class="font-display text-4xl font-black leading-none tracking-tighter" :class="i === 0 ? 'text-neon-yellow gh-title-glow' : 'text-white'">
            {{ Number(entry.high_score).toLocaleString() }}
          </p>
          <div class="flex sm:flex-col justify-between items-center sm:items-end mt-2">
            <p class="font-pixel text-[11px] uppercase tracking-[0.3em] font-black" :class="i === 0 ? 'text-neon-yellow/70' : 'text-white/40'">{{ scoreLabel }}_ACUMULADOS</p>
          </div>
        </div>
      </li>
    </ol>
  </section>
</template>

<style scoped>
.blink {
  animation: blink 1s step-start infinite;
}
@keyframes blink {
  50% { opacity: 0; }
}
</style>
