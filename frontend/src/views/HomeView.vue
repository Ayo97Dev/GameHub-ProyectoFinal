<script setup>
/**
 * HOME VIEW
 * 
 * Punto de entrada visual a la plataforma.
 * Gestiona el catálogo de juegos, el filtrado/ordenación y la telemetría global
 * del servidor (uptime, usuarios activos).
 */
import { onMounted, onUnmounted, computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useGameStore } from '../stores/game'
import GameCard from '../components/ui/GameCard.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'


const gameStore = useGameStore()
const uptime = ref('00:00:00')
const selectedSort = ref('POPULAR')

let uptimeInterval = null

// Inicialización de datos y simulación de Uptime
onMounted(() => {
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
  gameStore.fetchTelemetry()

  const start = Date.now()
  uptimeInterval = setInterval(() => {
    // Calculamos el uptime sumando el tiempo transcurrido al valor base del servidor
    const currentUptime = (gameStore.telemetry.server_uptime || 0) + Math.floor((Date.now() - start) / 1000)
    const h = Math.floor(currentUptime / 3600).toString().padStart(2, '0')
    const m = Math.floor((currentUptime % 3600) / 60).toString().padStart(2, '0')
    const s = (currentUptime % 60).toString().padStart(2, '0')
    uptime.value = `${h}:${m}:${s}`
    
    // Polling de telemetría cada 30 segundos
    const diff = Math.floor((Date.now() - start) / 1000)
    if (diff % 30 === 0 && diff > 0) {
      gameStore.fetchTelemetry()
    }
  }, 1000)
})

onUnmounted(() => {
  if (uptimeInterval) clearInterval(uptimeInterval)
})

// El juego destacado (Hero) se selecciona prioritariamente por slug o el primero de la lista
const featuredGame = computed(() => {
  const games = gameStore.games || []
  return games.find(g => g.slug === 'descenso-al-abismo') || games[0]
})

/**
 * LÓGICA DE FILTRADO Y ORDENACIÓN
 * Permite alternar entre orden alfabético y popularidad (basada en usuarios online).
 */
const filteredGames = computed(() => {
  const allGames = Array.isArray(gameStore.games) ? gameStore.games : []
  if (allGames.length === 0) return []

  // Excluimos el juego destacado del grid principal
  let list = allGames.filter(g => g.slug !== featuredGame.value?.slug)
  
  const result = [...list]
  if (selectedSort.value === 'ALFABÉTICO') {
    result.sort((a, b) => (a.title || '').localeCompare(b.title || ''))
  } else if (selectedSort.value === 'POPULAR') {
    result.sort((a, b) => {
      const playersA = gameStore.telemetry.games_telemetry[a.slug] ?? 0
      const playersB = gameStore.telemetry.games_telemetry[b.slug] ?? 0
      return playersB - playersA 
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
               <div class="lg:col-span-3 relative overflow-hidden bg-retro-deep group/hero cursor-crosshair">
                  <!-- Layer 1: Abyssal Background -->
                  <img 
                    :src="featuredGame.cover" 
                    class="absolute inset-0 w-full h-full object-cover opacity-30 blur-2xl scale-110"
                  />
                  
                  <!-- Layer 2: Flickering Torchlight Glow -->
                  <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(140,45,31,0.2),transparent_70%)] animate-pulse"></div>

                  <!-- Layer 3: The Ancient Frame -->
                  <div class="absolute inset-0 z-20 flex items-center justify-center p-8 lg:p-14">
                     <div class="relative w-full h-full max-w-[85%] max-h-[85%] transition-all duration-700 ease-out transform group-hover/hero:scale-[1.02]">
                        
                        <!-- Forged Iron Corners -->
                        <div class="absolute -top-2 -left-2 size-16 border-t-8 border-l-8 border-[#3c2a1a] z-40"></div>
                        <div class="absolute -top-2 -right-2 size-16 border-t-8 border-r-8 border-[#3c2a1a] z-40"></div>
                        <div class="absolute -bottom-2 -left-2 size-16 border-b-8 border-l-8 border-[#3c2a1a] z-40"></div>
                        <div class="absolute -bottom-2 -right-2 size-16 border-b-8 border-r-8 border-[#3c2a1a] z-40"></div>
                        
                        <!-- Inner Crimson Glow -->
                        <div class="absolute inset-0 border-2 border-[#8c2d1f]/50 shadow-[0_0_40px_rgba(140,45,31,0.4)] z-30 pointer-events-none"></div>

                        <!-- Main Cinematic Image -->
                        <div class="w-full h-full overflow-hidden border-8 border-[#1a1a1a] shadow-[0_0_100px_rgba(0,0,0,0.9)] relative">
                           <img 
                             :src="featuredGame.cover" 
                             class="w-full h-full object-cover opacity-80 group-hover/hero:opacity-100 group-hover/hero:scale-110 transition-all duration-1000"
                           />
                           <!-- Blood/Shadow Overlay -->
                           <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-transparent to-transparent z-10"></div>
                           <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors duration-700"></div>
                        </div>

                        <!-- Ancient Labels -->
                        <div class="absolute -top-10 left-0 flex items-center gap-3">
                           <Icon icon="game-icons:ancient-sword" class="text-neon-pink text-xl animate-pulse" />
                           <span class="font-display text-sm text-[#b8a38a] uppercase tracking-[0.4em] drop-shadow-[0_0_8px_rgba(140,45,31,0.8)]">El descenso ha comenzado</span>
                        </div>
                        <div class="absolute -bottom-10 right-0 flex items-center gap-3">
                           <span class="font-pixel text-xs text-[#8c2d1f] uppercase tracking-widest">Peligro detectado</span>
                           <div class="size-2 bg-[#8c2d1f] animate-ping"></div>
                        </div>
                     </div>
                  </div>

                  <!-- Layer 4: Dark Vignette -->
                  <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-transparent z-10"></div>
                  <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black/40 z-10 shadow-[inset_0_0_100px_rgba(0,0,0,0.8)]"></div>
                  

                  <!-- Big Play Button for Hero -->
                  <div class="absolute inset-0 flex items-center justify-center z-30 opacity-0 group-hover:opacity-100 transition-opacity">
                     <RouterLink :to="`/play/${featuredGame.slug}`">
                         <button class="size-24 bg-[#8c2d1f] border-4 border-double border-[#3c2a1a] rounded-full flex items-center justify-center text-[#b8a38a] hover:scale-110 hover:bg-[#a83526] transition-all duration-300 shadow-[0_0_30px_rgba(140,45,31,0.6)] group/playbtn relative overflow-hidden">
                           <!-- Inner Glow Effect -->
                           <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_70%)]"></div>
                           <Icon icon="lucide:play" class="text-4xl translate-x-1 relative z-10 drop-shadow-[0_0_8px_rgba(0,0,0,0.5)]" />
                           
                           <!-- Runic Ring (Pulse) -->
                           <div class="absolute inset-0 border-2 border-[#b8a38a]/20 rounded-full animate-ping opacity-20"></div>
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
                     
                     <h2 class="font-display text-4xl sm:text-5xl lg:text-6xl font-black text-white uppercase gh-title-glow tracking-tighter leading-none break-words">
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
         <div class="flex justify-end">
            <!-- Sort Toggle -->
            <div class="gh-panel p-6 bg-black border-4 border-neon-yellow shadow-[8px_8px_0px_#000] relative overflow-hidden min-w-[280px]">
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
         <BaseLoading 
           v-if="gameStore.isLoading && gameStore.games.length === 0" 
           message="Booting System..." 
           submessage="Cargando biblioteca de datos históricos" 
         />


         <!-- Game Cards Grid -->
         <div v-else-if="filteredGames.length > 0" class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
           <GameCard v-for="game in filteredGames" :key="game.slug" :game="game" />
         </div>

         <!-- Empty State -->
         <div v-else class="py-32 flex flex-col items-center justify-center gap-6 gh-panel bg-black/40 border-dashed border-4 border-white/10">
            <Icon icon="lucide:search-x" class="text-6xl text-white/10" />
            <div class="text-center">
               <p class="font-pixel text-xl text-white/20 uppercase tracking-widest">No se encontraron módulos activos</p>
            </div>
         </div>
      </div>


      <!-- FOOTER DIAGNOSTICS -->
      <footer class="pt-12 border-t-4 border-neon-cyan/10 grid grid-cols-1 md:grid-cols-3 gap-8">
         <!-- FRONTEND -->
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-cyan/30 shadow-[6px_6px_0px_#000] group hover:border-neon-cyan/60 transition-colors">
            <div class="flex items-center gap-3 mb-4 text-neon-cyan">
               <Icon icon="lucide:cpu" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase tracking-tighter">Núcleo Frontend</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Arquitectura SPA sobre <span class="text-white/60">Vue 3.5</span> & <span class="text-white/60">Vite 7</span>. Compilación optimizada mediante Rollup para carga modular y reactividad granular mediante Composition API.
            </p>
            <div class="mt-4 pt-4 border-t border-white/5 flex gap-4">
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">CSS Engine</span>
                  <span class="font-display text-[10px] text-neon-cyan font-bold">TAILWIND 4.2</span>
               </div>
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">State</span>
                  <span class="font-display text-[10px] text-neon-cyan font-bold">PINIA 3.0</span>
               </div>
            </div>
         </div>
         
         <!-- BACKEND -->
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-pink/30 shadow-[6px_6px_0px_#000] group hover:border-neon-pink/60 transition-colors">
            <div class="flex items-center gap-3 mb-4 text-neon-pink">
               <Icon icon="lucide:server" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase tracking-tighter">Servicios Backend</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Motor de alto rendimiento en <span class="text-white/60">Laravel 12 + Octane</span>. Procesamiento paralelo mediante <span class="text-white/60">RoadRunner (Go)</span> con persistencia en MariaDB 10.11 y caché en Redis.
            </p>
            <div class="mt-4 pt-4 border-t border-white/5 flex gap-4">
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">Database</span>
                  <span class="font-display text-[10px] text-neon-pink font-bold">MARIADB + REDIS</span>
               </div>
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">Auth</span>
                  <span class="font-display text-[10px] text-neon-pink font-bold">SANCTUM</span>
               </div>
            </div>
         </div>

         <!-- INFRASTRUCTURE -->
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-yellow/30 shadow-[6px_6px_0px_#000] group hover:border-neon-yellow/60 transition-colors">
            <div class="flex items-center gap-3 mb-4 text-neon-yellow">
               <Icon icon="lucide:box" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase tracking-tighter">Infraestructura & Red</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Entorno orquestado con <span class="text-white/60">Docker</span> (5 microservicios). Proxy inverso <span class="text-white/60">Nginx</span> con terminación SSL y soporte nativo para protocolos HTTP/2 y WebSockets.
            </p>
            <div class="mt-4 pt-4 border-t border-white/5 flex gap-4">
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">Proxy</span>
                  <span class="font-display text-[10px] text-neon-yellow font-bold">NGINX / SSL</span>
               </div>
               <div class="flex flex-col">
                  <span class="font-pixel text-[8px] text-white/20 uppercase">Container</span>
                  <span class="font-display text-[10px] text-neon-yellow font-bold">DOCKER COMPOSE</span>
               </div>
            </div>
         </div>
      </footer>

    </div>
  </section>
</template>

<style scoped>
/* Estilos globales en style.css */
</style>
