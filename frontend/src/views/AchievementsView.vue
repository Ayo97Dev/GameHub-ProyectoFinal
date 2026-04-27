<script setup>
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAchievementStore } from '../stores/achievement'
import { useGameStore } from '../stores/game'

const achievementStore = useAchievementStore()
const gameStore = useGameStore()

const selectedGame = ref('all')
const sortBy = ref('recent') // recent, old, rarity

const RARITY_STYLES = {
  common:    { label: 'Nivel 1 // Común',    color: 'text-retro-white', border: 'border-white/20', bg: 'bg-retro-dark' },
  uncommon:  { label: 'Nivel 2 // Poco común',  color: 'text-neon-cyan',  border: 'border-neon-cyan', bg: 'bg-neon-cyan/5' },
  rare:      { label: 'Nivel 3 // Raro',      color: 'text-neon-blue',  border: 'border-neon-blue', bg: 'bg-neon-blue/5' },
  epic:      { label: 'Nivel 4 // Épico',      color: 'text-neon-pink',  border: 'border-neon-pink', bg: 'bg-neon-pink/5' },
  legendary: { label: 'Nivel 5 // Legendario', color: 'text-neon-yellow', border: 'border-neon-yellow', bg: 'bg-neon-yellow/5' },
}

const unlockedCount = computed(() => achievementStore.achievements.filter(a => a.unlocked).length)
const totalCount = computed(() => achievementStore.achievements.length)
const progressPercent = computed(() => totalCount.value > 0 ? (unlockedCount.value / totalCount.value) * 100 : 0)

const filteredAchievements = computed(() => {
  let list = [...achievementStore.achievements]
  
  if (selectedGame.value !== 'all') {
    list = list.filter(a => a.game_id === Number(selectedGame.value))
  }
  
  if (sortBy.value === 'recent') {
    list.sort((a, b) => new Date(b.earned_at || 0) - new Date(a.earned_at || 0))
  } else if (sortBy.value === 'old') {
    list.sort((a, b) => new Date(a.earned_at || 0) - new Date(b.earned_at || 0))
  } else if (sortBy.value === 'rarity') {
    const rarityOrder = { legendary: 0, epic: 1, rare: 2, uncommon: 3, common: 4 }
    list.sort((a, b) => rarityOrder[a.rarity] - rarityOrder[b.rarity])
  }
  
  return list
})

onMounted(() => {
  achievementStore.fetchAchievements()
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})
</script>

<template>
  <section class="mx-auto w-full max-w-6xl px-4 py-16">
    <!-- SYSTEM STATUS HEADER -->
    <header class="mb-12 relative">
      <div class="gh-panel p-8 bg-black border-4 border-neon-cyan shadow-[10px_10px_0px_#000] relative overflow-hidden">
        <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none"></div>
        <div class="absolute top-0 right-0 p-4 opacity-10">
          <Icon icon="lucide:database" class="text-9xl" />
        </div>
        
        <div class="relative z-10">
          <div class="flex items-center gap-3 mb-2">
            <span class="size-2 bg-neon-cyan animate-pulse"></span>
            <p class="font-pixel text-xs font-bold uppercase tracking-[0.3em] text-neon-cyan">Logros</p>
          </div>
          
          <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
              <h1 class="text-5xl md:text-6xl font-display font-black uppercase text-retro-white tracking-tighter gh-title-glow">Mis logros</h1>
              <p class="font-sans text-sm font-bold text-white/40 uppercase mt-2 tracking-widest">Sincronización de hitos históricos de la red.</p>
            </div>
            
            <div class="w-full md:w-80">
              <div class="flex justify-between items-end mb-2">
                <span class="font-pixel text-[10px] text-neon-yellow uppercase">Progreso</span>
                <span class="font-display text-xl font-black text-neon-yellow">{{ progressPercent.toFixed(0) }}%</span>
              </div>
              <div class="h-6 w-full bg-retro-dark border-2 border-white/10 p-0.5 relative">
                <div 
                  class="h-full bg-neon-yellow shadow-[0_0_10px_rgba(255,242,0,0.5)] transition-all duration-1000 ease-out"
                  :style="{ width: `${progressPercent}%` }"
                ></div>
                <!-- Marks -->
                <div class="absolute inset-0 flex justify-between px-1 pointer-events-none">
                  <div v-for="i in 10" :key="i" class="h-full w-px bg-black/20"></div>
                </div>
              </div>
              <p class="mt-2 font-pixel text-[10px] text-right text-white/40 uppercase">
                Logros conseguidos: {{ unlockedCount }} / {{ totalCount }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <!-- FILTERS -->
    <div class="mb-10 flex flex-wrap items-center gap-6 p-6 bg-retro-dark/50 border-2 border-white/5 shadow-[4px_4px_0px_#000]">
      <div class="flex items-center gap-3">
        <span class="font-pixel text-[10px] text-white/30 uppercase tracking-widest">Filtrar por juego:</span>
        <select v-model="selectedGame" class="bg-black border-2 border-neon-cyan px-3 py-1.5 font-display text-xs text-retro-white outline-none focus:shadow-[0_0_10px_rgba(0,242,255,0.3)] uppercase cursor-pointer">
          <option value="all">Todos los juegos</option>
          <option v-for="g in gameStore.games" :key="g.id" :value="g.id">{{ g.title }}</option>
        </select>
      </div>

      <div class="flex items-center gap-3">
        <span class="font-pixel text-[10px] text-white/30 uppercase tracking-widest">Ordenar por:</span>
        <select v-model="sortBy" class="bg-black border-2 border-neon-pink px-3 py-1.5 font-display text-xs text-retro-white outline-none focus:shadow-[0_0_10px_rgba(255,45,85,0.3)] uppercase cursor-pointer">
          <option value="recent">Más recientes</option>
          <option value="old">Más antiguos</option>
          <option value="rarity">Rareza (Mayor a menor)</option>
        </select>
      </div>
      
      <div class="ml-auto hidden md:block">
        <p class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">Resultados: {{ filteredAchievements.length }} / {{ totalCount }}</p>
      </div>
    </div>

    <div v-if="achievementStore.isLoading && achievementStore.achievements.length === 0" class="flex flex-col items-center justify-center py-24 gh-panel bg-black/40">
      <Icon icon="lucide:loader-2" class="text-6xl text-neon-pink animate-spin mb-4" />
      <p class="font-pixel text-2xl text-neon-pink uppercase blink tracking-widest">Cargando registros...</p>
    </div>

    <div v-else class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="a in filteredAchievements"
        :key="a.id"
        class="gh-panel group relative flex flex-col p-6 transition-all duration-300 bg-black border-4 shadow-[8px_8px_0px_#000] hover:translate-x-[-4px] hover:translate-y-[-4px] hover:shadow-[12px_12px_0px_#000]"
        :class="[
          a.unlocked ? (RARITY_STYLES[a.rarity]?.border ?? 'border-white/20') : 'border-retro-black opacity-60 grayscale'
        ]"
      >
        <!-- Card Header: Status & Rarity -->
        <div class="flex justify-between items-start mb-6">
          <div class="flex flex-col gap-1">
            <span 
              class="font-pixel text-[9px] px-2 py-0.5 uppercase tracking-tighter"
              :class="a.unlocked ? 'bg-neon-cyan text-black' : 'bg-retro-black text-white/30'"
            >
              {{ a.unlocked ? '[Conseguido]' : '[Bloqueado]' }}
            </span>
            <span class="font-pixel text-[10px] font-bold uppercase tracking-widest" :class="a.unlocked ? RARITY_STYLES[a.rarity]?.color : 'text-white/20'">
              {{ RARITY_STYLES[a.rarity]?.label ?? a.rarity }}
            </span>
          </div>
          <div v-if="!a.unlocked" class="text-white/20">
            <Icon icon="lucide:lock" class="text-xl" />
          </div>
          <div v-else :class="RARITY_STYLES[a.rarity]?.color">
            <Icon icon="lucide:check-circle" class="text-xl animate-pulse" />
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
          <div class="flex gap-4 items-center mb-4">
            <div 
              class="size-16 shrink-0 border-4 flex items-center justify-center text-3xl shadow-[4px_4px_0px_#000] relative"
              :class="[
                a.unlocked ? (RARITY_STYLES[a.rarity]?.border + ' ' + RARITY_STYLES[a.rarity]?.bg) : 'border-white/5 bg-white/5'
              ]"
            >
              <div v-if="a.unlocked" class="gh-scanlines absolute inset-0 opacity-20"></div>
              <Icon :icon="a.unlocked ? 'lucide:award' : 'lucide:help-circle'" :class="a.unlocked ? RARITY_STYLES[a.rarity]?.color : 'text-white/10'" />
            </div>
            <div class="min-w-0">
              <h3 
                class="font-display text-xl font-black uppercase leading-none mb-1 truncate"
                :class="a.unlocked ? 'text-retro-white group-hover:gh-title-glow' : 'text-white/20'"
              >
                {{ a.unlocked ? a.title : '??_??_??' }}
              </h3>
              <p class="font-sans text-[11px] font-bold leading-tight uppercase" :class="a.unlocked ? 'text-white/40' : 'text-white/10'">
                {{ a.unlocked ? a.description : 'Consigue este logro para ver su descripción.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer: Points & Date -->
        <div class="mt-6 pt-4 border-t-2 border-dashed flex items-center justify-between" :class="a.unlocked ? 'border-white/10' : 'border-white/5'">
          <div class="flex flex-col">
            <span class="font-pixel text-[9px] text-white/30 uppercase">Fecha</span>
            <span class="font-pixel text-xs font-bold" :class="a.unlocked ? 'text-neon-cyan' : 'text-white/10'">
              {{ a.unlocked && a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'XX/XX/XXXX' }}
            </span>
          </div>
          <div class="flex items-center gap-2 bg-retro-dark px-3 py-1 border-2 border-white/5 shadow-[2px_2px_0px_#000]">
            <Icon icon="lucide:zap" class="text-neon-yellow text-xs" />
            <span class="font-display font-black text-sm" :class="a.unlocked ? 'text-neon-yellow' : 'text-white/10'">
              +{{ a.points_reward }}
            </span>
          </div>
        </div>
        
        <!-- Diagonal Stripe Overlay for locked -->
        <div v-if="!a.unlocked" class="absolute inset-0 pointer-events-none opacity-[0.03]" style="background-image: linear-gradient(45deg, #fff 25%, transparent 25%, transparent 50%, #fff 50%, #fff 75%, transparent 75%, transparent); background-size: 10px 10px;"></div>
      </article>

      <div v-if="achievementStore.achievements.length === 0" class="col-span-full gh-panel p-12 text-center bg-black/40 border-dashed border-4 border-white/10">
        <Icon icon="lucide:alert-triangle" class="text-6xl text-white/10 mx-auto mb-4" />
        <p class="font-pixel text-xl text-white/20 uppercase tracking-widest">
          Aún no tienes logros registrados.
        </p>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Estilos globales en style.css */
</style>
