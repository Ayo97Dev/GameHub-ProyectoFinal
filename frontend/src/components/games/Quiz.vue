<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="h-full min-h-[500px] flex flex-col gh-panel p-0 overflow-hidden bg-retro-deep text-retro-white border-neon-yellow shadow-[6px_6px_0_#000] relative">
    <!-- Scanlines -->
    <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none z-10"></div>
    
    <!-- Warning Stripes (Top) -->
    <div class="h-4 w-full bg-neon-yellow flex overflow-hidden relative z-20 border-b-2 border-black">
      <div class="warning-stripes w-full h-full"></div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-8 relative z-20 text-center">
      <!-- Icon Container -->
      <div class="mb-8 relative">
        <div class="size-32 sm:size-40 border-4 border-neon-yellow flex items-center justify-center bg-retro-black/60 shadow-[8px_8px_0_rgba(0,0,0,0.5)]">
           <Icon icon="lucide:construction" class="text-6xl sm:text-7xl text-neon-yellow animate-pulse" />
        </div>
        <!-- Small alert corner -->
        <div class="absolute -top-4 -right-4 size-10 sm:size-12 bg-neon-pink border-2 border-retro-black flex items-center justify-center shadow-[4px_4px_0_#000] animate-bounce">
          <Icon icon="lucide:alert-triangle" class="text-xl sm:text-2xl text-white" />
        </div>
      </div>

      <h2 class="font-display text-4xl sm:text-5xl font-black text-neon-yellow uppercase tracking-tighter mb-4 gh-title-glow">
        WORK IN PROGRESS
      </h2>
      
      <div class="bg-black/60 border-2 border-neon-yellow/20 p-6 max-w-xl mb-10 font-pixel relative overflow-hidden group">
         <!-- Corner deco -->
         <div class="absolute top-0 right-0 size-4 border-t-2 border-r-2 border-neon-yellow"></div>
         <div class="absolute bottom-0 left-0 size-4 border-b-2 border-l-2 border-neon-yellow"></div>
         
         <p class="text-neon-yellow text-lg uppercase tracking-widest mb-4">Juego en Desarrollo</p>
         <div class="flex flex-col gap-2 text-left text-[10px] sm:text-xs opacity-60">
            <p>> ESPERANDO AL CONSTRUCTOR</p>
            <p>> ETA: ¿?</p>
            <p>> STATUS: NO DISPONIBLE</p>
            <p>> ERROR: RASCADA DE HUEVOS MONUMENTAL</p>
         </div>
      </div>

      <RouterLink 
        to="/" 
        class="gh-surface-hover gh-surface px-8 py-4 bg-retro-black text-neon-cyan border-neon-cyan/50 font-display text-sm font-black uppercase tracking-widest flex items-center gap-3"
      >
        <Icon icon="lucide:arrow-left" />
        Volver a la lista de juegos
      </RouterLink>
    </div>

    <!-- Warning Stripes (Bottom) -->
    <div class="h-10 w-full bg-retro-black flex items-center justify-center border-t-2 border-neon-yellow/20 relative z-20">
       <span class="font-pixel text-[10px] text-neon-yellow uppercase tracking-[0.5em] animate-pulse px-4 text-center">
         PRECAUCIÓN: ÁREA EN DESARROLLO // NO VAYA A SER...
       </span>
    </div>
  </section>
</template>

<style scoped>
@reference "../../style.css";

.warning-stripes {
  background-image: linear-gradient(
    45deg,
    #fff200 25%,
    #000 25%,
    #000 50%,
    #fff200 50%,
    #fff200 75%,
    #000 75%,
    #000 100%
  );
  background-size: 40px 40px;
  animation: slide-stripes 2s linear infinite;
}

@keyframes slide-stripes {
  from { background-position: 0 0; }
  to { background-position: 40px 0; }
}

.gh-title-glow {
  text-shadow: 3px 3px 0px #000, 0 0 15px rgba(255, 242, 0, 0.4);
}

.gh-panel {
  /* Sobrescribimos bordes para que sea amarillo industrial */
  border-color: var(--color-neon-yellow) !important;
}
</style>
