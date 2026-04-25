<script setup>
import { onMounted, onUnmounted, computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useGameStore } from '../stores/game'
import GameCard from '../components/ui/GameCard.vue'

const gameStore = useGameStore()
const uptime = ref('00:00:00')
const selectedFilter = ref('TODOS')
const selectedSort = ref('POPULAR')

let uptimeInterval = null

// Simulated uptime counter and real telemetry fetch
onMounted(() => {
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
  gameStore.fetchTelemetry()

  const start = Date.now()
  uptimeInterval = setInterval(() => {
    // Update Uptime (Local simulation based on server base)
    const currentUptime = (gameStore.telemetry.server_uptime || 0) + Math.floor((Date.now() - start) / 1000)
    const h = Math.floor(currentUptime / 3600).toString().padStart(2, '0')
    const m = Math.floor((currentUptime % 3600) / 60).toString().padStart(2, '0')
    const s = (currentUptime % 60).toString().padStart(2, '0')
    uptime.value = `${h}:${m}:${s}`
    
    // Fetch telemetry every 30 seconds
    const diff = Math.floor((Date.now() - start) / 1000)
    if (diff % 30 === 0 && diff > 0) {
      gameStore.fetchTelemetry()
    }
  }, 1000)
})

onUnmounted(() => {
  if (uptimeInterval) clearInterval(uptimeInterval)
})

const featuredGame = computed(() => {
  const games = gameStore.games || []
  return games.find(g => g.slug === 'descenso-al-abismo') || games[0]
})

const categories = computed(() => {
  const games = gameStore.games || []
  const cats = new Set(games.map(g => g.category).filter(Boolean))
  return ['TODOS', ...Array.from(cats).sort()]
})

const filteredGames = computed(() => {
  const allGames = Array.isArray(gameStore.games) ? gameStore.games : []
  if (allGames.length === 0) return []

  let list = allGames.filter(g => g.slug !== featuredGame.value?.slug)
  
  // Filtering logic
  if (selectedFilter.value !== 'TODOS') {
    list = list.filter(g => g.category === selectedFilter.value)
  }

  // Sorting logic
  const result = [...list]
  if (selectedSort.value === 'ALFABÉTICO') {
    result.sort((a, b) => (a.title || '').localeCompare(b.title || ''))
  } else if (selectedSort.value === 'POPULAR') {
    result.sort((a, b) => {
      const playersA = gameStore.telemetry.games_telemetry[a.slug] ?? 0
      const playersB = gameStore.telemetry.games_telemetry[b.slug] ?? 0
      return playersB - playersA // Mayor a menor
    })
  }

  return result
})

const toggleSort = () => {
  selectedSort.value = selectedSort.value === 'POPULAR' ? 'ALFABÉTICO' : 'POPULAR'
}
</script>

<template>
  <section class="min-h-screen bg-retro-deep text-retro-white font-sans overflow-hidden">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-10 pointer-events-none z-50"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(0,242,255,0.03),transparent_70%)] pointer-events-none"></div>

    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:py-12 relative z-10 space-y-12">
      
      <!-- TOP STATUS BAR (Diagnostics) -->
      <header class="flex flex-wrap items-center justify-between gap-6 p-4 bg-black/40 border-2 border-neon-cyan shadow-[4px_4px_0px_#000] backdrop-blur-md">
         <div class="flex items-center gap-8">
            <div class="flex flex-col">
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">Estado del servidor</span>
               <div class="flex items-center gap-2">
                  <div class="size-2 bg-neon-cyan animate-pulse shadow-[0_0_8px_#00f2ff]"></div>
                  <span class="font-display text-xs font-black text-neon-cyan">ÓPTIMO</span>
               </div>
            </div>
            <div class="hidden md:flex flex-col">
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">Tiempo de actividad</span>
               <span class="font-display text-xs font-black text-white">{{ uptime }}</span>
            </div>
         </div>
         <div class="flex items-center gap-6">
            <div class="flex flex-col items-end">
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">Sesiones activas</span>
               <span class="font-display text-xs font-black text-neon-pink">{{ gameStore.telemetry.active_users }} Usuarios</span>
            </div>
            <div class="size-10 bg-white/5 border-2 border-neon-cyan flex items-center justify-center text-neon-cyan">
               <Icon icon="lucide:shield-check" class="text-xl" />
            </div>
         </div>
      </header>

      <!-- HERO SECTION: THE COMMAND CENTER -->
      <div v-if="featuredGame" class="relative group">
        <div class="gh-panel relative overflow-hidden bg-black p-0 border-4 border-retro-black hover:border-neon-cyan transition-all duration-500 shadow-[12px_12px_0px_#000]">
           <div class="grid grid-cols-1 lg:grid-cols-5 h-full min-h-[450px]">
              
              <!-- LEFT: Cinematic Display -->
              <div class="lg:col-span-3 relative overflow-hidden bg-retro-deep">
                 <img 
                   :src="featuredGame.cover" 
                   class="absolute inset-0 w-full h-full object-cover opacity-60 grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-1000"
                 />
                 <div class="absolute inset-0 bg-gradient-to-r from-black via-black/40 to-transparent z-10"></div>
                 <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent z-10"></div>
                 
                 <!-- HUD Overlay -->
                 <div class="absolute top-6 left-6 z-20 flex flex-col gap-2">
                     <div class="bg-neon-cyan text-black px-3 py-1 font-pixel text-xs font-black uppercase tracking-widest border-2 border-black">Recomendado</div>
                     <div class="bg-black/80 border-2 border-neon-cyan/50 px-3 py-1 font-pixel text-[10px] text-white/60 tracking-widest uppercase">Categoría: {{ featuredGame.slug.toUpperCase() }}</div>
                 </div>

                 <!-- Big Play Button for Hero -->
                 <div class="absolute inset-0 flex items-center justify-center z-30 opacity-0 group-hover:opacity-100 transition-opacity">
                    <RouterLink :to="`/play/${featuredGame.slug}`">
                        <button class="size-20 bg-neon-cyan/20 border-4 border-neon-cyan backdrop-blur-xl flex items-center justify-center text-neon-cyan hover:scale-110 transition-transform shadow-[6px_6px_0px_#000]">
                          <Icon icon="lucide:play" class="text-4xl translate-x-1" />
                       </button>
                    </RouterLink>
                 </div>
              </div>

              <!-- RIGHT: Tactical Info -->
              <div class="lg:col-span-2 p-8 sm:p-12 flex flex-col justify-center bg-retro-black relative z-20">
                 <div class="space-y-6">
                    <div class="flex items-center gap-3">
                       <div class="h-1 w-12 bg-neon-cyan"></div>
                       <span class="font-pixel text-xs text-neon-cyan tracking-[0.4em] uppercase">SYSTEM.BOOT_COMPLETE</span>
                    </div>
                    
                    <h2 class="font-display text-5xl sm:text-6xl font-black text-white uppercase gh-title-glow tracking-tighter leading-none">
                      {{ featuredGame.title }}
                    </h2>
                    
                    <p class="font-sans text-sm font-bold text-white/50 uppercase leading-relaxed max-w-md">
                      {{ featuredGame.description }}
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4">
                       <RouterLink :to="`/play/${featuredGame.slug}`" class="flex-1">
                          <button class="w-full py-4 bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest shadow-[6px_6px_0px_#000] hover:translate-x-[-3px] hover:translate-y-[-3px] hover:shadow-[9px_9px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all border-2 border-black">
                             Jugar ahora
                          </button>
                       </RouterLink>
                       <RouterLink to="/store" class="shrink-0">
                          <button class="h-full px-6 bg-white/5 border-2 border-neon-yellow/50 text-neon-yellow hover:bg-neon-yellow/10 transition-all flex items-center gap-2 shadow-[4px_4px_0px_#000]">
                             <Icon icon="lucide:shopping-bag" />
                             <span class="font-display text-xs font-black uppercase">TIENDA</span>
                          </button>
                       </RouterLink>
                    </div>
                 </div>
              </div>

           </div>
        </div>
      </div>

      <!-- MAIN CATALOG GRID -->
      <div class="space-y-12">
         
         <!-- FILTERS PANEL (Aesthetic similar to Leaderboard View Header) -->
         <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Category Filter -->
            <div class="md:col-span-3 gh-panel p-6 bg-black border-4 border-neon-cyan shadow-[8px_8px_0px_#000] relative overflow-hidden flex flex-col sm:flex-row sm:items-center gap-6">
               <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none"></div>
               
               <div class="relative z-10 flex items-center gap-3 shrink-0 border-r-2 border-white/10 pr-6 mr-2 hidden sm:flex">
                  <Icon icon="lucide:filter" class="text-neon-cyan text-xl" />
                  <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.2em]">Filtros de<br/>subsistema</span>
               </div>

               <div class="relative z-10 flex flex-wrap gap-3">
                  <button 
                    v-for="cat in categories" 
                    :key="cat"
                    @click="selectedFilter = cat"
                    class="px-5 py-2 border-2 font-display text-[10px] font-black uppercase tracking-[0.2em] relative group"
                    :class="selectedFilter === cat 
                      ? 'bg-neon-cyan text-black border-black shadow-none translate-x-[2px] translate-y-[2px]' 
                      : 'bg-retro-dark text-white/40 border-white/5 hover:border-white/20 hover:text-white shadow-[4px_4px_0px_#000] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]'"
                  >
                    {{ cat }}
                  </button>
               </div>
            </div>

            <!-- Sort Toggle -->
            <div class="gh-panel p-6 bg-black border-4 border-neon-yellow shadow-[8px_8px_0px_#000] relative overflow-hidden">
               <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none"></div>
               <button @click="toggleSort" class="relative z-10 w-full h-full flex flex-col items-center justify-center gap-1 group">
                  <span class="font-pixel text-[9px] text-white/30 uppercase tracking-widest group-hover:text-neon-yellow transition-colors">Orden de salida:</span>
                  <div class="flex items-center gap-3">
                     <Icon :icon="selectedSort === 'POPULAR' ? 'lucide:flame' : 'lucide:type'" class="text-neon-yellow text-xl" />
                     <span class="font-display text-xl font-black text-white uppercase tracking-tighter">{{ selectedSort }}</span>
                  </div>
               </button>
            </div>
         </div>

         <!-- Header Info -->
         <div class="flex items-center justify-between border-b-2 border-white/5 pb-4">
            <div class="flex items-center gap-4">
               <div class="size-6 bg-neon-cyan/20 border border-neon-cyan flex items-center justify-center text-neon-cyan">
                  <Icon icon="lucide:layout-grid" class="text-xs" />
               </div>
               <h3 class="font-display text-xl font-black text-white uppercase tracking-tighter">Módulos disponibles: <span class="text-neon-cyan">{{ filteredGames.length }}</span></h3>
            </div>
            <div class="hidden sm:block font-pixel text-[9px] text-white/20 uppercase tracking-[0.4em]">
               Sincronizando con el servidor central...
            </div>
         </div>

         <!-- Indicador de carga -->
         <div v-if="gameStore.isLoading && gameStore.games.length === 0" class="py-32 flex flex-col items-center justify-center gap-8 gh-panel border-none">
            <div class="size-20 border-4 border-neon-cyan border-t-transparent animate-spin shadow-[0_0_20px_#00f2ff]"></div>
            <div class="text-center space-y-2">
               <p class="font-pixel text-neon-cyan text-2xl animate-pulse tracking-[0.5em] uppercase">Booting System...</p>
               <p class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.2em]">Cargando biblioteca de datos históricos</p>
            </div>
         </div>

         <!-- Game Cards Grid -->
         <div v-else-if="filteredGames.length > 0" class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
           <GameCard v-for="game in filteredGames" :key="game.slug" :game="game" />
         </div>

         <!-- Empty State -->
         <div v-else class="py-32 flex flex-col items-center justify-center gap-6 gh-panel bg-black/40 border-dashed border-4 border-white/10">
            <Icon icon="lucide:search-x" class="text-6xl text-white/10" />
            <div class="text-center">
               <p class="font-pixel text-xl text-white/20 uppercase tracking-widest">No se encontraron módulos activos</p>
               <button @click="selectedFilter = 'TODOS'" class="mt-4 font-display text-xs font-black text-neon-cyan uppercase underline underline-offset-4 hover:text-white transition-colors">Reiniciar filtros</button>
            </div>
         </div>
      </div>


      <!-- FOOTER DIAGNOSTICS -->
      <footer class="pt-12 border-t-4 border-neon-cyan/10 grid grid-cols-1 md:grid-cols-3 gap-8">
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-cyan/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-cyan">
               <Icon icon="lucide:database" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Infraestructura distribuida</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Nuestra arquitectura utiliza nodos descentralizados para garantizar una latencia inferior a 20ms en todas las regiones arcade.
            </p>
         </div>
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-pink/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-pink">
               <Icon icon="lucide:shield" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Protocolos de seguridad</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Todas las transacciones en el mercado digital están cifradas mediante algoritmos cuánticos de 512 bits.
            </p>
         </div>
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-yellow/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-yellow">
               <Icon icon="lucide:zap" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Optimización de hardware</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Sistema optimizado para núcleos de procesamiento de última generación. FPS estables garantizados.
            </p>
         </div>
      </footer>

    </div>
  </section>
</template>

<style scoped>
/* Estilos globales en style.css */
</style>
