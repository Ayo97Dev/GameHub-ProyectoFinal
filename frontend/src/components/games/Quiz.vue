<template>
  <section class="h-full min-h-[400px] flex flex-col gh-panel p-0 overflow-hidden bg-black text-retro-white border-none shadow-none font-sans relative">
    <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none z-10"></div>
    
    <header class="p-6 border-b-4 border-retro-black bg-white dark:bg-black relative z-20">
       <div class="flex items-center gap-3 mb-2">
           <span class="bg-neon-cyan text-black font-pixel px-2 py-0.5 text-xs font-black uppercase">CON_MEMORIA.SYS</span>
           <p class="font-pixel text-xs uppercase opacity-40">ID_ESTACIÓN: GH_QZ_99</p>
       </div>
       <h2 class="font-display text-4xl font-black text-retro-black dark:text-retro-white uppercase">MODO_TRIVIA</h2>
    </header>

    <div class="flex-1 flex flex-col items-center justify-center p-8 relative z-20 text-center">
       <div class="mb-12 relative">
          <div class="size-32 border-4 border-neon-cyan flex items-center justify-center rotate-45 bg-retro-black/40">
             <span class="font-display text-6xl -rotate-45 text-white animate-pulse">?</span>
          </div>
          <div class="absolute -top-4 -right-4 size-10 bg-neon-pink flex items-center justify-center font-pixel text-xl text-white shadow-[4px_4px_0_#000]">!</div>
       </div>

       <p class="font-pixel text-neon-cyan text-sm tracking-[0.2em] mb-4 uppercase">ESPERANDO_FLUJO_DATOS...</p>
       <p class="max-w-md font-sans text-xs font-bold text-retro-white/50 uppercase leading-relaxed mx-auto">
          ESTE MÓDULO REQUIERE UNA CONEXIÓN ACTIVA AL SERVIDOR DE DATOS. 
          PREGUNTAS RÁPIDAS, RACHAS DE ACIERTOS Y COMPETICIÓN POR PUNTUACIÓN.
       </p>
       
       <button class="mt-10 gh-surface px-10 py-3 bg-neon-cyan text-black font-pixel text-xl uppercase opacity-30 cursor-not-allowed shadow-[6px_6px_0_#fff]">
          SECUENCIA_INICIAL
       </button>
    </div>

    <footer class="p-4 border-t-2 border-retro-white/5 bg-retro-black/40 text-center relative z-20">
       <p class="font-pixel text-xs text-retro-white/30 uppercase tracking-[0.4em]">PROTOTIPO_ESTACIÓN_VER_2026</p>
    </footer>
  </section>
</template>

<script setup>
// Placeholder - No logic to restore here as it was empty
</script>
