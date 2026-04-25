<script setup>
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  game: {
    type: Object,
    required: true,
  },
})

// Simulated data for the "Wow" effect
const stats = {
  players: Math.floor(Math.random() * 500) + 50,
  difficulty: props.game.slug === 'rpg' ? 'Dificultad: Alta' : (props.game.slug === 'td' ? 'Dificultad: Media' : 'Dificultad: Baja'),
  version: 'v2.4.0'
}
</script>

<template>
  <article class="group relative flex flex-col h-full bg-black border-4 border-retro-black transition-all duration-300 hover:border-neon-cyan shadow-[8px_8px_0px_#000] hover:shadow-[12px_12px_0px_#000] overflow-hidden">
    
    <!-- TOP HUD: Version & Online Status -->
    <div class="flex justify-between items-center p-2 bg-retro-black/80 border-b-2 border-neon-cyan/20">
      <span class="font-pixel text-[10px] text-white/40 tracking-widest uppercase">ID_{{ game.slug.toUpperCase() }} // {{ stats.version }}</span>
      <div class="flex items-center gap-1.5">
        <div class="size-1.5 bg-neon-green animate-pulse shadow-[0_0_5px_#22c55e]"></div>
        <span class="font-pixel text-[10px] text-neon-green uppercase tracking-widest">{{ stats.players }} en línea</span>
      </div>
    </div>

    <!-- COVER AREA: Cinematic & Gritty -->
    <div class="relative aspect-[16/10] overflow-hidden bg-retro-deep group-hover:bg-black">
      <div class="gh-scanlines absolute inset-0 z-20 opacity-30 pointer-events-none"></div>
      
      <img 
        :src="game.cover" 
        :alt="game.title" 
        class="h-full w-full object-cover grayscale opacity-60 transition-all duration-700 group-hover:grayscale-0 group-hover:opacity-100 group-hover:scale-110" 
      />

      <!-- Image Overlays -->
      <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent z-10"></div>
      <div class="absolute inset-0 border-[10px] border-black/20 group-hover:border-transparent transition-all duration-300 pointer-events-none z-30"></div>
      
      <!-- Difficulty Badge (Corner) -->
      <div class="absolute bottom-4 left-4 z-40 bg-black/80 border-2 border-neon-yellow px-2 py-1 flex items-center gap-2">
         <Icon icon="lucide:activity" class="text-[10px] text-neon-yellow" />
         <span class="font-pixel text-[10px] text-white/60 tracking-widest uppercase">{{ stats.difficulty }}</span>
      </div>
    </div>
    
    <!-- CONTENT AREA: Data Density -->
    <div class="flex flex-col flex-grow p-5 bg-retro-black relative">
      <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
      
      <div class="mb-4">
        <div class="flex items-center gap-2 mb-1">
           <div class="h-px w-4 bg-neon-cyan"></div>
           <p class="font-pixel text-[9px] text-neon-cyan uppercase tracking-[0.3em]">Módulo de juego</p>
        </div>
        <h3 class="font-display text-3xl font-black uppercase text-white tracking-tighter leading-none mb-4 group-hover:text-neon-cyan transition-colors">
          {{ game.title }}
        </h3>
        <p class="font-sans text-[11px] font-bold uppercase leading-relaxed text-white/40 line-clamp-2 h-8">
          {{ game.description }}
        </p>
      </div>

      <!-- Footer Actions -->
      <div class="mt-auto pt-6 border-t-2 border-neon-cyan/20 flex items-center gap-3">
        <RouterLink :to="game.route || `/play/${game.slug}`" class="flex-1">
          <button class="w-full py-3 bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-widest shadow-[4px_4px_0px_#000] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all border-2 border-black flex items-center justify-center gap-2 group/btn">
             <span>Jugar ahora</span>
             <Icon icon="lucide:play" class="text-lg group-hover/btn:translate-x-1 transition-transform" />
          </button>
        </RouterLink>
        
        <RouterLink :to="`/leaderboard/${game.slug}`" class="shrink-0">
          <button class="size-10 flex items-center justify-center bg-retro-dark border-2 border-neon-yellow text-neon-yellow hover:bg-neon-yellow/10 shadow-[4px_4px_0px_#000] transition-all" title="Leaderboard">
            <Icon icon="lucide:trophy" class="text-lg" />
          </button>
        </RouterLink>
      </div>
    </div>
  </article>
</template>

<style scoped>
/* No additional styles needed, using Tailwind + Design System classes */
</style>
