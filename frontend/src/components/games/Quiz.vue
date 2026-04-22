<script setup>
import { Icon } from '@iconify/vue'
</script>

<template>
  <section class="h-full min-h-[500px] flex flex-col bg-black text-retro-white font-sans relative overflow-hidden border-4 border-retro-black">
    <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none z-10"></div>
    
    <!-- WIP Background Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,242,0,0.05),transparent_70%)] pointer-events-none"></div>

    <header class="p-6 border-b-4 border-retro-black bg-black relative z-20 flex justify-between items-center">
       <div class="flex items-center gap-3">
           <div class="size-3 bg-neon-yellow animate-pulse shadow-[0_0_8px_#fff200]"></div>
           <p class="font-pixel text-xs uppercase opacity-40 tracking-[0.2em]">SISTEMA_RESTRINGIDO: TRIVIA_CORE</p>
       </div>
       <div class="bg-neon-yellow text-black font-pixel text-[10px] px-3 py-1 font-black uppercase tracking-widest">WIP_STATUS: 95%</div>
    </header>

    <div class="flex-1 flex flex-col items-center justify-center p-8 relative z-20 text-center">
       <!-- Cinematic Warning Sign -->
       <div class="mb-10 relative group">
          <div class="size-40 border-8 border-neon-yellow flex items-center justify-center bg-neon-yellow/5 relative overflow-hidden">
             <!-- Animated Caution Stripes -->
             <div class="absolute inset-0 opacity-20 bg-[repeating-linear-gradient(45deg,#fff200_0px,#fff200_20px,transparent_20px,transparent_40px)] animate-[move-bg_2s_linear_infinite]"></div>
             
             <Icon icon="lucide:construction" class="text-7xl text-neon-yellow relative z-10" />
          </div>
          
          <!-- Floating "Restricted" Tags -->
          <div class="absolute -top-4 -right-12 bg-neon-pink text-white font-pixel text-xs px-4 py-1 shadow-[4px_4px_0_#000] rotate-12 uppercase font-black">PROHIBIDO</div>
          <div class="absolute -bottom-4 -left-12 bg-neon-cyan text-black font-pixel text-xs px-4 py-1 shadow-[4px_4px_0_#000] -rotate-6 uppercase font-black">EN DESARROLLO</div>
       </div>

       <div class="space-y-4 max-w-xl">
          <h2 class="font-display text-5xl font-black text-white uppercase gh-title-glow tracking-tighter">Acceso_Denegado</h2>
          <div class="h-1 w-24 bg-neon-yellow mx-auto"></div>
          
          <p class="font-pixel text-neon-yellow text-sm tracking-[0.2em] uppercase">Módulo de Trivia en Proceso de Despliegue</p>
          
          <p class="font-sans text-xs font-bold text-white/40 uppercase leading-relaxed mx-auto px-10">
             Los protocolos de sincronización de preguntas están siendo calibrados. 
             La base de datos de conocimientos globales estará disponible en la próxima actualización del núcleo.
          </p>
       </div>
       
       <!-- BLOCKED BUTTON -->
       <div class="mt-12 relative group">
          <div class="absolute inset-0 bg-white/5 blur-xl group-hover:bg-neon-yellow/10 transition-colors"></div>
          <button 
            disabled
            class="relative px-12 py-4 bg-retro-black border-4 border-white/10 text-white/20 font-display text-lg font-black uppercase tracking-[0.3em] cursor-not-allowed overflow-hidden"
          >
             <span class="relative z-10">BLOQUEADO</span>
             <!-- Lock Icon -->
             <Icon icon="lucide:lock" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-4xl opacity-10" />
          </button>
       </div>
    </div>

    <!-- TECHNICAL LOGS AT BOTTOM -->
    <footer class="p-6 border-t-2 border-white/5 bg-black/60 relative z-20">
       <div class="flex justify-between items-center font-pixel text-[9px] text-white/20 uppercase tracking-[0.4em]">
          <div class="flex gap-6">
             <span>ERROR_403: RESTRICTED_AREA</span>
             <span class="hidden sm:inline">|</span>
             <span class="hidden sm:inline">BUILD_HASH: 0x88AF_QZ</span>
          </div>
          <p class="animate-pulse">SINCRONIZANDO_RECURSOS...</p>
       </div>
    </footer>
  </section>
</template>

<style scoped>
@keyframes move-bg {
  from { transform: translateX(-40px); }
  to { transform: translateX(0); }
}

.gh-title-glow {
  text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
}
</style>
