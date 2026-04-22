<script setup>
import { onMounted, computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
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
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none z-50"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(0,242,255,0.08),transparent_50%)] pointer-events-none"></div>
    <div class="fixed inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-[0.03] pointer-events-none"></div>

    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:py-12 relative z-10 space-y-16">
      
      <!-- TOP STATUS BAR (Terminal HUD) -->
      <header class="flex flex-wrap items-center justify-between gap-6 p-4 bg-retro-black/60 border-l-4 border-neon-cyan backdrop-blur-md relative overflow-hidden">
         <!-- Technical background ornament -->
         <div class="absolute top-0 right-0 w-32 h-32 bg-neon-cyan/5 -rotate-45 translate-x-16 -translate-y-16 pointer-events-none"></div>
         
         <div class="flex items-center gap-12 relative z-10">
            <div class="flex flex-col">
               <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.4em] mb-1">NÚCLEO_ESTADO</span>
               <div class="flex items-center gap-3">
                  <div class="size-2 bg-neon-cyan animate-pulse shadow-[0_0_10px_#00f2ff]"></div>
                  <span class="font-display text-xs font-black text-neon-cyan tracking-widest">SISTEMA_OPTIMIZADO_V4.2</span>
               </div>
            </div>
            <div class="hidden md:flex flex-col">
               <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.4em] mb-1">UPTIME_SESIÓN</span>
               <div class="flex items-center gap-2">
                  <Icon icon="lucide:clock" class="text-neon-cyan/50 text-xs" />
                  <span class="font-display text-xs font-black text-white tracking-widest">{{ uptime }}</span>
               </div>
            </div>
            <div class="hidden lg:flex flex-col">
               <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.4em] mb-1">LATENCIA_RED</span>
               <span class="font-display text-xs font-black text-neon-yellow tracking-widest">14MS_NOMINAL</span>
            </div>
         </div>

         <div class="flex items-center gap-8 relative z-10">
            <div class="flex flex-col items-end">
               <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.4em] mb-1">USUARIOS_ACTIVOS</span>
               <div class="flex items-center gap-2">
                  <span class="font-display text-xs font-black text-neon-pink tracking-widest">1.248_UNIDADES</span>
                  <div class="h-1 w-8 bg-white/10 overflow-hidden">
                     <div class="h-full bg-neon-pink w-3/4"></div>
                  </div>
               </div>
            </div>
            <div class="size-12 bg-neon-cyan/10 border border-neon-cyan/30 flex items-center justify-center text-neon-cyan shadow-[inset_0_0_10px_rgba(0,242,255,0.1)]">
               <Icon icon="lucide:cpu" class="text-2xl animate-pulse" />
            </div>
         </div>
      </header>

      <!-- HERO SECTION: THE COMMAND CENTER -->
      <div v-if="featuredGame" class="relative group">
        <!-- Corner Ornaments -->
        <div class="absolute -top-2 -left-2 size-10 border-t-4 border-l-4 border-neon-cyan z-30 group-hover:scale-110 transition-transform"></div>
        <div class="absolute -bottom-2 -right-2 size-10 border-b-4 border-r-4 border-neon-cyan z-30 group-hover:scale-110 transition-transform"></div>

        <div class="gh-panel relative overflow-hidden bg-black p-0 border-4 border-retro-black hover:border-neon-cyan/20 transition-all duration-700 shadow-[20px_20px_0px_#000]">
           <div class="grid grid-cols-1 lg:grid-cols-5 h-full min-h-[500px]">
              
              <!-- LEFT: Cinematic Display -->
              <div class="lg:col-span-3 relative overflow-hidden bg-retro-deep group/hero">
                 <img 
                   :src="featuredGame.cover" 
                   class="absolute inset-0 w-full h-full object-cover opacity-40 grayscale group-hover:grayscale-0 group-hover:scale-110 group-hover:opacity-60 transition-all duration-[2000ms] ease-out"
                 />
                 
                 <!-- Tech overlays -->
                 <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-[0.05] z-20"></div>
                 <div class="absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent z-10"></div>
                 <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent z-10"></div>
                 <div class="absolute inset-0 bg-[linear-gradient(rgba(0,242,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(0,242,255,0.03)_1px,transparent_1px)] bg-[size:40px_40px] z-10"></div>
                 
                 <!-- HUD Overlay -->
                 <div class="absolute top-8 left-8 z-20 space-y-3">
                    <div class="flex items-center gap-2">
                       <div class="bg-neon-cyan text-black px-4 py-1.5 font-pixel text-xs font-black uppercase tracking-[0.2em] shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">
                          SIMULACIÓN_RECOMENDADA
                       </div>
                       <div class="bg-neon-pink size-7 flex items-center justify-center text-black shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">
                          <Icon icon="lucide:zap" />
                       </div>
                    </div>
                    <div class="bg-black/60 backdrop-blur-sm border-l-2 border-neon-cyan px-4 py-1.5 font-pixel text-[10px] text-white/80 tracking-[0.3em] uppercase">
                       ID: ARCHIVE_{{ featuredGame.slug.toUpperCase() }}_2026
                    </div>
                 </div>

                 <!-- Blocky Play Button for Hero -->
                 <div class="absolute inset-0 flex items-center justify-center z-30 opacity-0 group-hover:opacity-100 transition-all duration-500 scale-90 group-hover:scale-100">
                    <RouterLink :to="`/play/${featuredGame.slug}`">
                       <button class="bg-neon-cyan/10 border-2 border-neon-cyan p-1 backdrop-blur-md hover:bg-neon-cyan/20 transition-all group/play">
                          <div class="border-2 border-neon-cyan px-8 py-6 flex items-center gap-4 text-neon-cyan">
                             <Icon icon="lucide:play" class="text-5xl group-hover/play:scale-110 transition-transform" />
                             <div class="flex flex-col items-start">
                                <span class="font-display text-lg font-black uppercase tracking-tighter">ACCEDER</span>
                                <span class="font-pixel text-[10px] uppercase tracking-widest opacity-70">CÓDIGO: {{ featuredGame.slug }}</span>
                             </div>
                          </div>
                       </button>
                    </RouterLink>
                 </div>
              </div>

              <!-- RIGHT: Tactical Info -->
              <div class="lg:col-span-2 p-10 sm:p-14 flex flex-col justify-center bg-[#0c0c0e] relative z-20 border-l-4 border-retro-black">
                 <!-- Background tech text -->
                 <div class="absolute bottom-4 right-4 font-pixel text-[8px] text-white/5 uppercase tracking-[0.5em] select-none">
                    PROPERTY_OF_GAMEHUB_CORP_SECURE_ACCESS_ONLY
                 </div>
                 
                 <div class="space-y-8">
                    <div class="flex items-center gap-4">
                       <div class="h-[2px] w-16 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
                       <span class="font-pixel text-xs text-neon-cyan tracking-[0.5em] uppercase animate-pulse">TERMINAL.READY</span>
                    </div>
                    
                    <div class="space-y-2">
                       <h2 class="font-display text-6xl sm:text-7xl font-black text-white uppercase gh-title-glow tracking-tighter leading-[0.8] mb-4">
                         {{ featuredGame.title }}
                       </h2>
                       <div class="flex gap-2">
                          <span v-for="i in 3" :key="i" class="h-1 flex-1 bg-white/5 border-t border-white/10"></span>
                       </div>
                    </div>
                    
                    <p class="font-sans text-sm font-bold text-white/50 uppercase leading-relaxed max-w-sm border-l-2 border-white/5 pl-6 py-2">
                      {{ featuredGame.description }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 pt-6">
                       <RouterLink :to="`/play/${featuredGame.slug}`" class="flex-[2]">
                          <button class="w-full py-5 bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest shadow-[8px_8px_0px_#000] hover:translate-x-[-4px] hover:translate-y-[-4px] hover:shadow-[12px_12px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center justify-center gap-3">
                             <Icon icon="lucide:terminal" class="text-xl" />
                             INICIAR_OPERACIÓN
                          </button>
                       </RouterLink>
                       <RouterLink to="/store" class="flex-1">
                          <button class="w-full h-full py-5 bg-white/5 border border-white/10 text-white hover:border-neon-yellow hover:text-neon-yellow hover:bg-neon-yellow/5 transition-all flex items-center justify-center gap-2">
                             <Icon icon="lucide:shopping-bag" />
                             <span class="font-display text-xs font-black uppercase tracking-widest">TIENDA</span>
                          </button>
                       </RouterLink>
                    </div>
                 </div>
              </div>

           </div>
        </div>
      </div>

      <!-- MAIN CATALOG GRID -->
      <div class="space-y-10">
         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-b-4 border-retro-black pb-6">
            <div class="flex items-center gap-5">
               <div class="size-12 bg-neon-cyan/10 flex items-center justify-center text-neon-cyan border-2 border-neon-cyan/20 shadow-[inset_0_0_15px_rgba(0,242,255,0.1)]">
                  <Icon icon="lucide:layout-grid" class="text-2xl" />
               </div>
               <div class="flex flex-col">
                  <h3 class="font-display text-3xl font-black text-white uppercase tracking-tighter leading-none mb-1">Archivo_De_Simulaciones</h3>
                  <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.5em]">DIRECTORIO_LOCAL: /GAMES/ALL</span>
               </div>
            </div>
            <div class="flex items-center gap-6 font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em] bg-retro-black/40 px-6 py-2 border border-white/5">
               <div class="flex items-center gap-2 text-neon-cyan">
                  <span class="size-1 bg-neon-cyan"></span>
                  <span>TODOS</span>
               </div>
               <span>|</span>
               <div class="hover:text-white cursor-pointer transition-colors">POPULAR</div>
               <span>|</span>
               <div class="hover:text-white cursor-pointer transition-colors">NUEVO</div>
            </div>
         </div>

         <!-- Indicador de carga -->
         <div v-if="gameStore.isLoading" class="py-32 flex flex-col items-center justify-center gap-8 bg-retro-black/20 border-2 border-dashed border-white/5">
            <div class="relative size-20">
               <div class="absolute inset-0 border-4 border-neon-cyan/20"></div>
               <div class="absolute inset-0 border-t-4 border-neon-cyan animate-spin"></div>
            </div>
            <p class="font-pixel text-neon-cyan text-xl animate-pulse tracking-[0.5em] uppercase">SINCRONIZANDO_NÚCLEO_DATOS...</p>
         </div>

         <!-- Game Cards Grid -->
         <div v-else class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
           <GameCard v-for="game in otherGames" :key="game.slug" :game="game" />
         </div>
      </div>

      <!-- FOOTER DIAGNOSTICS (Tech Panels) -->
      <footer class="pt-16 border-t-4 border-retro-black grid grid-cols-1 md:grid-cols-3 gap-10">
         <div class="relative group">
            <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-neon-cyan opacity-50 group-hover:opacity-100 transition-opacity"></div>
            <div class="gh-glass p-8 bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors h-full">
               <div class="flex items-center gap-4 mb-6 text-neon-cyan">
                  <div class="size-10 bg-neon-cyan/10 flex items-center justify-center border border-neon-cyan/30">
                     <Icon icon="lucide:database" class="text-xl" />
                  </div>
                  <span class="font-display text-sm font-black uppercase tracking-widest">Red_Distribuida</span>
               </div>
               <p class="font-sans text-[11px] font-bold text-white/40 uppercase leading-relaxed tracking-wider">
                  Nuestra arquitectura utiliza nodos descentralizados para garantizar una latencia inferior a 20ms en todas las regiones arcade. Optimizado para protocolos de alta velocidad.
               </p>
            </div>
         </div>

         <div class="relative group">
            <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-neon-pink opacity-50 group-hover:opacity-100 transition-opacity"></div>
            <div class="gh-glass p-8 bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors h-full">
               <div class="flex items-center gap-4 mb-6 text-neon-pink">
                  <div class="size-10 bg-neon-pink/10 flex items-center justify-center border border-neon-pink/30">
                     <Icon icon="lucide:shield" class="text-xl" />
                  </div>
                  <span class="font-display text-sm font-black uppercase tracking-widest">Protocolo_Seguro</span>
               </div>
               <p class="font-sans text-[11px] font-bold text-white/40 uppercase leading-relaxed tracking-wider">
                  Todas las transacciones en el mercado digital están cifradas mediante algoritmos cuánticos de 512 bits. Seguridad de grado militar para sus créditos arcade.
               </p>
            </div>
         </div>

         <div class="relative group">
            <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-neon-yellow opacity-50 group-hover:opacity-100 transition-opacity"></div>
            <div class="gh-glass p-8 bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors h-full">
               <div class="flex items-center gap-4 mb-6 text-neon-yellow">
                  <div class="size-10 bg-neon-yellow/10 flex items-center justify-center border border-neon-yellow/30">
                     <Icon icon="lucide:zap" class="text-xl" />
                  </div>
                  <span class="font-display text-sm font-black uppercase tracking-widest">Alto_Rendimiento</span>
               </div>
               <p class="font-sans text-[11px] font-bold text-white/40 uppercase leading-relaxed tracking-wider">
                  Sistema optimizado para núcleos de procesamiento de última generación. FPS estables garantizados mediante balanceo de carga adaptativo en tiempo real.
               </p>
            </div>
         </div>
      </footer>

    </div>
  </section>
</template>

<style scoped>
/* Transiciones y efectos adicionales si fueran necesarios */
</style>
