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
  difficulty: props.game.slug === 'rpg' ? 'DIFÍCIL' : (props.game.slug === 'td' ? 'MEDIO' : 'FÁCIL'),
  version: 'v2.4.0'
}
</script>

<template>
  <article class="group relative flex flex-col h-full bg-black border-4 border-retro-black transition-all duration-500 hover:border-neon-cyan/40 shadow-[12px_12px_0px_#000] hover:shadow-[16px_16px_0px_#000] overflow-hidden">
    
    <!-- TOP HUD: Version & Online Status -->
    <div class="flex justify-between items-center p-2.5 bg-retro-black border-b-2 border-white/5 relative overflow-hidden">
      <!-- Decorative background line -->
      <div class="absolute bottom-0 left-0 h-[1px] w-full bg-gradient-to-r from-neon-cyan/20 via-transparent to-transparent"></div>
      
      <span class="font-pixel text-[10px] text-white/40 tracking-[0.2em] uppercase relative z-10">ARCHIVE_{{ game.slug.toUpperCase() }} // {{ stats.version }}</span>
      <div class="flex items-center gap-2 relative z-10">
        <div class="size-2 bg-neon-green animate-pulse shadow-[0_0_8px_#22c55e]"></div>
        <span class="font-pixel text-[10px] text-neon-green uppercase tracking-widest">{{ stats.players }}_NÚCLEOS</span>
      </div>
    </div>

    <!-- COVER AREA: Cinematic & Gritty -->
    <div class="relative aspect-[16/9] overflow-hidden bg-retro-deep group-hover:bg-black">
      <div class="gh-scanlines absolute inset-0 z-20 opacity-30 pointer-events-none"></div>
      
      <!-- Grid overlay -->
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:20px_20px] z-10"></div>

      <img 
        :src="game.cover" 
        :alt="game.title" 
        class="h-full w-full object-cover grayscale opacity-40 transition-all duration-1000 group-hover:grayscale-0 group-hover:opacity-60 group-hover:scale-110" 
      />

      <!-- Image Overlays -->
      <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent z-20"></div>
      
      <!-- Difficulty Badge (Corner) -->
      <div class="absolute bottom-4 left-4 z-40 bg-black/80 border-l-2 border-neon-yellow px-3 py-1 flex items-center gap-2 shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">
         <Icon icon="lucide:activity" class="text-[10px] text-neon-yellow" />
         <span class="font-pixel text-[10px] text-white/80 tracking-[0.2em] uppercase">{{ stats.difficulty }}</span>
      </div>

      <!-- Play Icon Overlay on Hover -->
      <div class="absolute inset-0 flex items-center justify-center z-30 opacity-0 group-hover:opacity-100 transition-all duration-500 scale-150 group-hover:scale-100">
         <div class="size-16 bg-neon-cyan/10 border border-neon-cyan/40 backdrop-blur-sm flex items-center justify-center text-neon-cyan">
            <Icon icon="lucide:play" class="text-3xl" />
         </div>
      </div>
    </div>
    
    <!-- CONTENT AREA: Data Density -->
    <div class="flex flex-col flex-grow p-6 bg-[#0c0c0e] relative group-hover:bg-[#121214] transition-colors">
      <!-- Decorative corner -->
      <div class="absolute top-0 right-0 size-8 bg-white/5 -rotate-45 translate-x-4 -translate-y-4 pointer-events-none"></div>
      
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
           <div class="h-[1px] w-6 bg-neon-cyan/50"></div>
           <p class="font-pixel text-[9px] text-neon-cyan/70 uppercase tracking-[0.4em]">MÓDULO_EJECUTABLE</p>
        </div>
        <h3 class="font-display text-4xl font-black uppercase text-white tracking-tighter leading-none mb-4 group-hover:text-neon-cyan transition-colors">
          {{ game.title }}
        </h3>
        <p class="font-sans text-[11px] font-bold uppercase leading-relaxed text-white/30 line-clamp-2 h-9 border-l border-white/5 pl-4">
          {{ game.description }}
        </p>
      </div>

      <!-- Footer Actions -->
      <div class="mt-auto pt-6 border-t border-white/5 flex items-center gap-4">
        <RouterLink :to="game.route || `/play/${game.slug}`" class="flex-1">
          <button class="w-full py-4 bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-widest shadow-[6px_6px_0px_#000] hover:translate-x-[-3px] hover:translate-y-[-3px] hover:shadow-[9px_9px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center justify-center gap-2 group/btn">
             <span>INICIAR_SECUENCIA</span>
             <Icon icon="lucide:chevron-right" class="text-xl group-hover/btn:translate-x-1 transition-transform" />
          </button>
        </RouterLink>
        
        <RouterLink :to="`/leaderboard/${game.slug}`" class="shrink-0">
          <button class="size-12 flex items-center justify-center bg-white/5 border border-white/10 hover:border-neon-yellow hover:text-neon-yellow hover:bg-neon-yellow/5 transition-all shadow-[6px_6px_0px_#000] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]" title="Leaderboard">
            <Icon icon="lucide:bar-chart-3" class="text-xl" />
          </button>
        </RouterLink>
      </div>
    </div>
  </article>
</template>

<style scoped>
/* No additional styles needed, using Tailwind + Design System classes */
</style>
