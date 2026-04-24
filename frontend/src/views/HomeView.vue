<script setup>
import { onMounted, computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useGameStore } from '../stores/game'
import GameCard from '../components/ui/GameCard.vue'

const gameStore = useGameStore()
const uptime = ref('00:00:00')

// Simulated uptime counter
onMounted(() => {
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }

  const start = Date.now()
  setInterval(() => {
    const diff = Math.floor((Date.now() - start) / 1000)
    const h = Math.floor(diff / 3600).toString().padStart(2, '0')
    const m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0')
    const s = (diff % 60).toString().padStart(2, '0')
    uptime.value = `${h}:${m}:${s}`
  }, 1000)
})

const featuredGame = computed(() => gameStore.games.find(g => g.slug === 'rpg') || gameStore.games[0])
const otherGames = computed(() => gameStore.games.filter(g => g.slug !== featuredGame.value?.slug))
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
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">SERVIDOR_ESTADO</span>
               <div class="flex items-center gap-2">
                  <div class="size-2 bg-neon-cyan animate-pulse shadow-[0_0_8px_#00f2ff]"></div>
                  <span class="font-display text-xs font-black text-neon-cyan">EN_LINEA_OPTIMO</span>
               </div>
            </div>
            <div class="hidden md:flex flex-col">
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">UPTIME_SISTEMA</span>
               <span class="font-display text-xs font-black text-white">{{ uptime }}</span>
            </div>
         </div>
         <div class="flex items-center gap-6">
            <div class="flex flex-col items-end">
               <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] mb-1">USUARIOS_CONECTADOS</span>
               <span class="font-display text-xs font-black text-neon-pink">1.248_NÚCLEOS</span>
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
                    <div class="bg-neon-cyan text-black px-3 py-1 font-pixel text-xs font-black uppercase tracking-widest border-2 border-black">DESTACADO_DEL_MES</div>
                    <div class="bg-black/80 border-2 border-neon-cyan/50 px-3 py-1 font-pixel text-[10px] text-white/60 tracking-widest uppercase">CAT: {{ featuredGame.slug.toUpperCase() }}</div>
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
                             INICIAR_OPERACIÓN
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
      <div class="space-y-8">
         <div class="flex items-center justify-between border-b-4 border-neon-cyan/20 pb-4">
            <div class="flex items-center gap-4">
               <div class="size-8 bg-neon-cyan/10 flex items-center justify-center text-neon-cyan border-2 border-neon-cyan/50 shadow-[2px_2px_0px_#000]">
                  <Icon icon="lucide:layout-grid" />
               </div>
               <h3 class="font-display text-2xl font-black text-white uppercase tracking-tighter">Archivo_De_Simulaciones</h3>
            </div>
            <div class="hidden sm:flex items-center gap-4 font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em]">
               <span>Filtro: TODOS</span>
               <span>|</span>
               <span>Orden: RECIENTE</span>
            </div>
         </div>

         <!-- Indicador de carga -->
         <div v-if="gameStore.isLoading" class="py-24 flex flex-col items-center justify-center gap-6">
            <div class="size-16 border-4 border-neon-cyan border-t-transparent animate-spin"></div>
            <p class="font-pixel text-neon-cyan text-xl animate-pulse tracking-[0.4em] uppercase">SINCRONIZANDO_DATOS...</p>
         </div>

         <!-- Game Cards Grid -->
         <div v-else class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
           <GameCard v-for="game in otherGames" :key="game.slug" :game="game" />
         </div>
      </div>

      <!-- FOOTER DIAGNOSTICS -->
      <footer class="pt-12 border-t-4 border-neon-cyan/10 grid grid-cols-1 md:grid-cols-3 gap-8">
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-cyan/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-cyan">
               <Icon icon="lucide:database" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Red_Distribuida</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Nuestra arquitectura utiliza nodos descentralizados para garantizar una latencia inferior a 20ms en todas las regiones arcade.
            </p>
         </div>
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-pink/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-pink">
               <Icon icon="lucide:shield" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Protocolo_Seguro</span>
            </div>
            <p class="font-pixel text-[10px] text-white/40 uppercase leading-relaxed tracking-widest">
               Todas las transacciones en el mercado digital están cifradas mediante algoritmos cuánticos de 512 bits.
            </p>
         </div>
         <div class="gh-glass p-6 bg-retro-dark/80 border-2 border-neon-yellow/30 shadow-[6px_6px_0px_#000]">
            <div class="flex items-center gap-3 mb-4 text-neon-yellow">
               <Icon icon="lucide:zap" class="text-2xl" />
               <span class="font-display text-sm font-black uppercase">Alto_Rendimiento</span>
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
/* Transiciones y efectos adicionales si fueran necesarios */
</style>
