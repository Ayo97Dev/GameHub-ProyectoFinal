<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useLeaderboardStore } from '../stores/leaderboard'
import { useGameStore } from '../stores/game'
import BaseLoading from '../components/ui/BaseLoading.vue'


const leaderboardStore = useLeaderboardStore()
const gameStore = useGameStore()

const route = useRoute()
const slug  = route.params.slug

const entries = computed(() => leaderboardStore.getEntries(slug))
const top3 = computed(() => entries.value.slice(0, 3))
const remainingEntries = computed(() => entries.value.slice(3))
const gameName = computed(() => gameStore.gamesBySlug[slug]?.title ?? slug)
const isLoading = computed(() => leaderboardStore.isLoading(slug) && entries.value.length === 0)
const isConnect4 = computed(() => slug === 'connect4')

const scoreLabel = computed(() => {
  if (isConnect4.value) return 'VICTORIAS'
  return 'PUNTUACIÓN'
})

onMounted(async () => {
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
  leaderboardStore.fetchLeaderboard(slug)
})
</script>

<template>
  <section class="mx-auto w-full max-w-6xl px-4 py-16">
    <!-- MODULE HEADER -->
    <header class="mb-16 relative">
      <div class="gh-panel p-8 bg-black border-4 border-neon-yellow shadow-[10px_10px_0px_#000] flex flex-col md:flex-row items-center justify-between gap-8 overflow-hidden">
        <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col items-center md:items-start text-center md:text-left">
          <div class="flex items-center gap-3 mb-2">
            <Icon icon="lucide:globe" class="text-neon-yellow animate-spin-slow" />
            <p class="font-pixel text-xs font-bold uppercase tracking-[0.3em] text-neon-yellow">Clasificación global</p>
          </div>
          <h1 class="text-5xl md:text-7xl font-display font-black uppercase text-retro-white tracking-tighter leading-none gh-title-glow">Clasificación</h1>
          <div class="mt-4 flex items-center gap-4 bg-retro-dark px-4 py-2 border-2 border-white/10 shadow-[4px_4px_0px_#000]">
            <span class="font-pixel text-xs text-white/40 uppercase">Juego:</span>
            <span class="font-display text-2xl font-black text-neon-cyan uppercase tracking-widest">{{ gameName }}</span>
          </div>
        </div>

        <div class="relative z-10 hidden lg:block">
          <div class="grid grid-cols-2 gap-4">
            <div class="gh-panel p-4 bg-retro-dark border-2 border-white/5">
              <p class="font-pixel text-[9px] text-white/30 uppercase">Estado del servidor</p>
              <p class="font-pixel text-xs text-neon-green uppercase animate-pulse">En línea</p>
            </div>
            <div class="gh-panel p-4 bg-retro-dark border-2 border-white/5">
              <p class="font-pixel text-[9px] text-white/30 uppercase">Jugadores registrados</p>
              <p class="font-pixel text-xs text-neon-cyan uppercase">{{ entries.length }} Registrados</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <BaseLoading 
      v-if="isLoading" 
      message="Cargando datos..." 
      submessage="Recuperando telemetría global de la red" 
    />


    <div v-else-if="entries.length === 0" class="gh-panel p-16 text-center bg-black border-4 border-dashed border-white/10">
      <Icon icon="lucide:database-zap" class="text-6xl text-white/10 mx-auto mb-4" />
      <p class="font-pixel text-xl text-white/20 uppercase tracking-widest">
        Aún no hay puntuaciones registradas. ¡Sé el primero!
      </p>
    </div>

    <div v-else class="space-y-12">
      <!-- PODIUM SECTION -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
        <!-- 2nd Place -->
        <div v-if="top3[1]" class="order-2 md:order-1">
          <div class="gh-panel bg-black border-4 border-slate-400 p-6 shadow-[8px_8px_0px_#000] relative group">
             <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-400 text-black font-display font-black text-xl px-4 py-1 shadow-[4px_4px_0px_#000]">2ND</div>
             <div class="flex flex-col items-center text-center mt-4">
                <div class="size-20 bg-retro-dark border-4 border-slate-400 flex items-center justify-center text-3xl font-black mb-4 overflow-hidden">
                  <img v-if="top3[1].avatar" :src="top3[1].avatar" class="w-full h-full object-cover" />
                  <span v-else>{{ top3[1].username?.[0]?.toUpperCase() }}</span>
                </div>
                <h3 class="font-display text-xl font-black text-retro-white uppercase truncate w-full mb-1">{{ top3[1].username }}</h3>
                <p class="font-display text-2xl font-black text-neon-cyan">{{ Number(top3[1].high_score).toLocaleString() }}</p>
                <p class="font-pixel text-[10px] text-white/30 uppercase mt-2">{{ scoreLabel }}</p>
             </div>
          </div>
        </div>

        <!-- 1st Place -->
        <div v-if="top3[0]" class="order-1 md:order-2">
          <div class="gh-panel bg-retro-dark border-4 border-neon-yellow p-8 shadow-[12px_12px_0px_#000] relative group animate-glow-yellow">
             <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-neon-yellow text-black font-display font-black text-3xl px-6 py-2 shadow-[6px_6px_0px_#000] animate-bounce-slow">1ST</div>
             <div class="flex flex-col items-center text-center mt-6">
                <div class="size-28 bg-black border-4 border-neon-yellow flex items-center justify-center text-5xl font-black mb-6 text-neon-yellow overflow-hidden">
                  <img v-if="top3[0].avatar" :src="top3[0].avatar" class="w-full h-full object-cover" />
                  <span v-else>{{ top3[0].username?.[0]?.toUpperCase() }}</span>
                </div>
                <h3 class="font-display text-2xl font-black text-retro-white uppercase truncate w-full mb-1">{{ top3[0].username }}</h3>
                <p class="font-display text-4xl font-black text-neon-yellow">{{ Number(top3[0].high_score).toLocaleString() }}</p>
                <p class="font-pixel text-[11px] text-neon-yellow/50 uppercase mt-2 tracking-widest">{{ scoreLabel }}</p>
             </div>
          </div>
        </div>

        <!-- 3rd Place -->
        <div v-if="top3[2]" class="order-3">
          <div class="gh-panel bg-black border-4 border-neon-pink p-6 shadow-[8px_8px_0px_#000] relative group">
             <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-neon-pink text-black font-display font-black text-xl px-4 py-1 shadow-[4px_4px_0px_#000]">3RD</div>
             <div class="flex flex-col items-center text-center mt-4">
                <div class="size-20 bg-retro-dark border-4 border-neon-pink flex items-center justify-center text-3xl font-black mb-4 text-neon-pink overflow-hidden">
                  <img v-if="top3[2].avatar" :src="top3[2].avatar" class="w-full h-full object-cover" />
                  <span v-else>{{ top3[2].username?.[0]?.toUpperCase() }}</span>
                </div>
                <h3 class="font-display text-xl font-black text-retro-white uppercase truncate w-full mb-1">{{ top3[2].username }}</h3>
                <p class="font-display text-2xl font-black text-neon-pink">{{ Number(top3[2].high_score).toLocaleString() }}</p>
                <p class="font-pixel text-[10px] text-white/30 uppercase mt-2">{{ scoreLabel }}</p>
             </div>
          </div>
        </div>
      </div>

      <!-- LIST SECTION -->
      <div class="relative pt-8">
        <!-- CIRCUIT LINE -->
        <div class="absolute left-10 top-0 bottom-0 w-1 bg-white/5 z-0"></div>

        <div class="space-y-4 relative z-10">
          <div
            v-for="(entry, i) in remainingEntries"
            :key="entry.user_id"
            class="flex items-center gap-6 gh-panel p-4 bg-black border-2 border-white/5 hover:border-neon-cyan transition-all duration-300 group shadow-[4px_4px_0px_#000] hover:shadow-[6px_6px_0px_#000]"
          >
            <!-- Rank Circle -->
            <div class="size-12 shrink-0 bg-retro-dark border-2 border-white/10 flex items-center justify-center font-display font-black text-xl group-hover:border-neon-cyan group-hover:text-neon-cyan">
              {{ i + 4 }}
            </div>

            <!-- Profile Info -->
            <div class="flex-1 flex items-center gap-4 min-w-0">
               <div class="size-10 bg-white/5 flex items-center justify-center font-pixel text-lg text-white/20 border border-white/10 uppercase overflow-hidden">
                 <img v-if="entry.avatar" :src="entry.avatar" class="w-full h-full object-cover" />
                 <span v-else>{{ entry.username?.[0] }}</span>
               </div>
               <div class="truncate">
                  <h4 class="font-display text-lg font-black text-retro-white uppercase truncate">{{ entry.username }}</h4>
                  <p class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">ID de usuario: {{ entry.user_id?.slice(0, 8) }}</p>
               </div>
            </div>

            <!-- Score -->
            <div class="text-right shrink-0 px-6 border-x-2 border-white/5">
               <p class="font-display text-2xl font-black text-neon-cyan leading-none">{{ Number(entry.high_score).toLocaleString() }}</p>
               <p class="font-pixel text-[9px] text-white/30 uppercase mt-1">{{ scoreLabel }}</p>
            </div>

            <!-- Metadata -->
            <div class="hidden sm:flex flex-col items-end gap-1 shrink-0 w-32">
               <div v-if="entry.time_played" class="flex items-center gap-1.5 px-2 py-0.5 bg-retro-dark border border-white/5">
                 <Icon icon="lucide:clock" class="text-[10px] text-white/20" />
                 <span class="font-pixel text-[9px] text-white/40 uppercase">{{ Math.floor(entry.time_played / 60) }}M</span>
               </div>
               <span class="font-pixel text-[9px] text-neon-green uppercase opacity-40">Conexión estable</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Estilos globales en style.css */
</style>
