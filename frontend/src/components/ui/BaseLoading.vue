<script setup>
import { computed } from 'vue'

const props = defineProps({
  message: {
    type: String,
    default: 'Procesando...'
  },
  submessage: {
    type: String,
    default: 'Sincronizando con la red central'
  },
  fullScreen: {
    type: Boolean,
    default: false
  }
})

const containerClasses = computed(() => {
  if (props.fullScreen) {
    return 'fixed inset-0 z-[1000] bg-retro-deep/95 backdrop-blur-md flex flex-col items-center justify-center p-6 overflow-hidden'
  }
  return 'relative flex flex-col items-center justify-center p-8 w-full min-h-[300px]'
})
</script>

<template>
  <div :class="containerClasses" class="base-loading">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none"></div>
    
    <!-- CYBER HUD LOADER -->
    <div class="relative size-48 flex items-center justify-center mb-12">
      <!-- Outer Decorative Ring -->
      <div class="absolute inset-0 border-2 border-neon-cyan/20 rounded-full animate-[spin_10s_linear_infinite]"></div>
      <div class="absolute inset-2 border-t-4 border-r-4 border-neon-cyan rounded-full animate-[spin_1.5s_linear_infinite]"></div>
      
      <!-- Middle Pulsing Ring -->
      <div class="absolute inset-8 border border-neon-pink/30 rounded-full animate-pulse"></div>
      <div class="absolute inset-8 border-b-2 border-l-2 border-neon-pink rounded-full animate-[spin_2s_linear_infinite_reverse]"></div>
      
      <!-- Inner Orbitals -->
      <div class="absolute inset-14 flex items-center justify-center animate-[spin_3s_linear_infinite]">
        <div class="size-3 bg-neon-yellow shadow-[0_0_15px_rgba(255,242,0,0.8)] translate-x-20"></div>
      </div>
      
      <!-- Center Core -->
      <div class="relative size-12 bg-retro-deep border-2 border-neon-cyan flex items-center justify-center shadow-[0_0_30px_rgba(0,242,255,0.3)]">
        <div class="size-6 bg-neon-cyan/20 animate-pulse"></div>
        <div class="absolute -inset-1 border border-neon-cyan/40 animate-ping"></div>
      </div>

      <!-- Corner Accents -->
      <div class="absolute -top-2 -left-2 size-6 border-t-2 border-l-2 border-neon-cyan"></div>
      <div class="absolute -top-2 -right-2 size-6 border-t-2 border-r-2 border-neon-cyan"></div>
      <div class="absolute -bottom-2 -left-2 size-6 border-b-2 border-l-2 border-neon-cyan"></div>
      <div class="absolute -bottom-2 -right-2 size-6 border-b-2 border-r-2 border-neon-cyan"></div>
    </div>

    <!-- TEXT AREA -->
    <div class="text-center relative z-10">
      <h3 class="font-display text-2xl font-black text-white uppercase tracking-[0.2em] mb-2 gh-title-glow text-neon-cyan">
        {{ message }}
      </h3>
      <div class="flex items-center justify-center gap-2 mb-6">
        <div class="h-1 w-12 bg-neon-pink"></div>
        <p class="font-pixel text-neon-pink text-xs uppercase tracking-[0.4em]">{{ submessage }}</p>
        <div class="h-1 w-12 bg-neon-pink"></div>
      </div>
      
      <!-- Progress Bar Simulation -->
      <div class="w-64 h-1.5 bg-white/5 border border-white/10 relative overflow-hidden mx-auto">
        <div class="absolute inset-y-0 left-0 bg-neon-cyan w-full animate-gh-loading-slide"></div>
        <!-- Glitch line -->
        <div class="absolute inset-y-0 left-0 bg-neon-yellow w-1/4 animate-gh-loading-glitch"></div>
      </div>
      
      <p class="mt-4 font-sans text-[10px] text-white/30 uppercase tracking-widest">
        Acceso encriptado // Protokoll {{ Math.floor(Math.random() * 9999) }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.gh-title-glow {
  text-shadow: 0 0 10px rgba(0, 242, 255, 0.5);
}

@keyframes gh-loading-slide {
  0% { transform: translateX(-100%); }
  50% { transform: translateX(0); }
  100% { transform: translateX(100%); }
}

@keyframes gh-loading-glitch {
  0% { transform: translateX(-200%); }
  20% { transform: translateX(100%); }
  100% { transform: translateX(400%); }
}

.animate-gh-loading-slide {
  animation: gh-loading-slide 2s cubic-bezier(0.65, 0, 0.35, 1) infinite;
}

.animate-gh-loading-glitch {
  animation: gh-loading-glitch 1.5s linear infinite;
}

/* Base colors from theme in case they aren't available in scope (though they should be via Tailwind) */
.base-loading {
  --color-neon-cyan: #00f2ff;
  --color-neon-pink: #ff2d55;
  --color-neon-yellow: #fff200;
  --color-retro-deep: #08080a;
}
</style>
