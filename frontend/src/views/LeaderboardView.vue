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
  <section class="mx-auto w-full max-w-4xl px-4 py-16">
    <header class="mb-10 flex flex-col items-center border-[4px] border-retro-black dark:border-neon-yellow bg-white dark:bg-black p-6 shadow-[8px_8px_0px_#09090b] dark:shadow-[8px_8px_0px_#fef08a] relative overflow-hidden">
      <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none"></div>
      <p class="relative z-10 font-pixel text-xs font-bold uppercase tracking-widest text-neon-pink dark:text-neon-pink">>> NETWORK_RANKINGS</p>
      <h1 class="relative z-10 mt-3 text-4xl sm:text-5xl font-display font-black uppercase text-retro-black dark:text-retro-white text-center">
        LEADERBOARD <!--{{ gameName }}-->
      </h1>
      <h2 class="relative z-10 mt-1 text-2xl font-display font-black uppercase text-neon-blue dark:text-neon-cyan">
        [{{ gameName }}]
      </h2>
      <p class="relative z-10 mt-4 bg-retro-black text-white dark:bg-neon-yellow dark:text-black font-pixel text-[10px] px-2 py-1 uppercase">{{ leaderboardSubtitle }}</p>
    </header>

    <div v-if="isLoading" class="flex justify-center py-16 text-retro-black dark:text-neon-yellow font-pixel text-2xl uppercase blink gh-panel">
      FETCHING_RECORDS...
    </div>

    <div v-else-if="entries.length === 0" class="gh-panel flex justify-center py-16 text-retro-black dark:text-retro-white font-pixel text-xl uppercase">
      SERVER DB IS EMPTY. BE THE FIRST.
    </div>

    <ol v-else class="space-y-4">
      <li
        v-for="(entry, i) in entries"
        :key="entry.user_id"
        class="flex flex-col sm:flex-row sm:items-center gap-4 gh-panel p-4 transition-all"
        :class="i === 0 ? '!border-neon-yellow !shadow-[6px_6px_0px_#fef08a]'
               : i === 1 ? '!border-retro-black dark:!border-retro-white !shadow-[6px_6px_0px_#09090b] dark:!shadow-[6px_6px_0px_#fafafa]'
               : i === 2 ? '!border-neon-pink !shadow-[6px_6px_0px_#f472b6] dark:!shadow-[6px_6px_0px_#f472b6]'
               : ''"
      >
        <span class="w-16 text-center font-pixel text-4xl sm:text-3xl font-black text-retro-black dark:text-retro-white"
          :class="i === 0 ? '!text-neon-yellow' : i === 2 ? '!text-neon-pink' : ''"
        >
          {{ ['1P', '2P', '3P'][i] ?? `${i + 1}P` }}
        </span>
        <div class="flex flex-1 items-center gap-4 min-w-0 border-l-[3px] border-retro-black dark:border-retro-white pl-4 sm:border-r-[3px] sm:pr-4">
          <div class="size-12 border-[3px] border-retro-black dark:border-neon-cyan bg-neon-cyan flex items-center justify-center shrink-0 shadow-[2px_2px_0px_#09090b]">
            <span class="font-pixel text-xl font-bold text-retro-black">{{ entry.username?.[0]?.toUpperCase() }}</span>
          </div>
          <p class="truncate font-display text-2xl font-black text-retro-black dark:text-retro-white uppercase">{{ entry.username }}</p>
        </div>
        <div class="sm:text-right shrink-0 mt-4 sm:mt-0 border-t-2 sm:border-0 border-retro-black dark:border-retro-white pt-2 sm:pt-0">
          <p class="font-sans text-3xl font-black text-neon-blue dark:text-neon-yellow leading-none">{{ Number(entry.high_score).toLocaleString() }}</p>
          <div class="flex sm:flex-col justify-between items-center sm:items-end mt-1">
            <p class="font-pixel text-[10px] uppercase tracking-widest text-retro-black dark:text-retro-white">{{ scoreLabel }}</p>
            <p v-if="entry.time_played" class="font-pixel text-[10px] text-slate-500 dark:text-slate-400 mt-1">
              {{ Math.floor(entry.time_played / 60) }}m_PLAYED
            </p>
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
