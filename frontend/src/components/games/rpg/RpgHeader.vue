<template>
  <header class="relative z-[60] p-4 sm:p-5 flex flex-col md:flex-row items-center justify-between gap-6 border-b-4 border-double border-[#3c2a1a] bg-black/40 backdrop-blur-sm">
    <div class="flex items-center gap-6">
      <div class="size-14 bg-[#0a0a0a] flex items-center justify-center text-[#8c2d1f] text-3xl border-4 border-[#3c2a1a] shadow-2xl iron-shadow transform -rotate-3">
        <Icon icon="game-icons:crossed-swords" />
      </div>
      <div>
        <h2 class="font-fantasy text-2xl text-[#b8a38a] uppercase tracking-wide drop-shadow-md">Descenso al Abismo</h2>
        <div class="flex items-center gap-3 text-[#b8a38a]/70 font-fantasy text-[10px] uppercase tracking-[0.2em]">
          <span class="animate-pulse text-[#8c2d1f]">●</span>
          <span v-if="hero">{{ hero.className }} • Nivel {{ hero.level }} • PISO {{ run.floor }}</span>
          <span v-else>Seleccionando Destino</span>
        </div>
        
        <!-- XP BAR IN HEADER (Reworked) -->
        <div v-if="hero" class="w-full max-w-[280px] mt-3 group/xp relative">
          <div class="flex justify-between items-end mb-1 px-1">
            <span class="text-[8px] text-[#b8a38a]/40 uppercase tracking-widest font-fantasy">Experiencia</span>
            <span class="text-[9px] text-amber-500 font-fantasy">{{ Math.floor(hero.exp / hero.nextLevelExp * 100) }}%</span>
          </div>
          <div class="h-2.5 w-full bg-black/80 border-2 border-[#3c2a1a] p-0.5 shadow-[inset_0_0_10px_rgba(0,0,0,0.8)] relative overflow-hidden group-hover/xp:border-amber-900/40 transition-colors">
            <div 
              class="h-full bg-gradient-to-r from-amber-950 via-amber-600 to-amber-400 transition-all duration-1000 relative z-10" 
              :style="{ width: (hero.exp/hero.nextLevelExp*100) + '%' }"
            >
              <!-- Spark effect at the end of the bar -->
              <div class="absolute right-0 top-0 bottom-0 w-4 bg-white/20 blur-md animate-pulse"></div>
            </div>
            <!-- Gloss effect -->
            <div class="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent z-20 pointer-events-none"></div>
          </div>
          
          <!-- XP Tooltip on hover -->
          <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 px-3 py-1 bg-[#0a0a0a] border border-amber-900/40 text-[9px] text-amber-500 font-fantasy uppercase tracking-widest opacity-0 group-hover/xp:opacity-100 transition-all z-50 shadow-2xl translate-y-2 group-hover:translate-y-0">
            {{ Math.floor(hero.exp) }} / {{ hero.nextLevelExp }} XP
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-8">
      <!-- MAP TRACKER (Reworked) -->
      <div v-if="hero" class="relative flex gap-8 pt-4 pb-7 px-8 bg-black/40 border-2 border-[#3c2a1a] shadow-inner items-center min-w-[320px] justify-center">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] pointer-events-none"></div>
        
        <!-- Connecting Line (Base) -->
        <div class="absolute top-1/2 left-10 right-10 h-1 bg-[#3c2a1a]/40 -translate-y-1/2 z-0 rounded-full"></div>
        
        <!-- Connecting Line (Progress) -->
        <div 
          class="absolute top-1/2 left-10 h-1 bg-gradient-to-r from-[#8c2d1f] to-amber-600 -translate-y-1/2 z-0 transition-all duration-1000 rounded-full"
          :style="{ width: ((run.roomInFloor - 1) / 2 * 75) + '%' }"
        ></div>

        <div
          v-for="idx in 3"
          :key="idx"
          class="relative z-10 flex flex-col items-center"
        >
          <div
            class="size-10 rounded-sm border-2 transition-all duration-700 flex items-center justify-center bg-[#0a0a0a] transform rotate-45"
            :class="idx === run.roomInFloor
              ? 'border-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.3)] scale-110 bg-[#1a1a1a]'
              : (idx < run.roomInFloor ? 'border-[#8c2d1f] bg-[#240a0a]' : 'border-[#3c2a1a] opacity-40')"
          >
            <div class="-rotate-45">
              <Icon 
                v-if="idx === 3" 
                :icon="run.floor % 10 === 0 ? 'game-icons:death-skull' : 'game-icons:doorway'" 
                class="size-5" 
                :class="idx <= run.roomInFloor ? 'text-amber-500' : 'text-[#3c2a1a]'" 
              />
              <Icon 
                v-else-if="idx < run.roomInFloor" 
                icon="game-icons:check-mark" 
                class="size-4 text-emerald-500" 
              />
              <span v-else class="font-fantasy text-[10px]" :class="idx === run.roomInFloor ? 'text-white' : 'text-[#b8a38a]/20'">{{ idx }}</span>
            </div>
          </div>
          <!-- Label -->
          <span 
            class="absolute -bottom-5 font-fantasy text-[7px] uppercase tracking-tighter whitespace-nowrap transition-colors"
            :class="idx === run.roomInFloor ? 'text-amber-500' : 'text-[#b8a38a]/20'"
          >
            {{ idx === 3 ? (run.floor % 10 === 0 ? 'Guardián' : 'Escaleras') : 'Sala ' + idx }}
          </span>
        </div>
      </div>
      
      <div class="flex items-center gap-4">
          <button @click="$emit('change-class')" class="group relative px-6 py-2 transition-all">
              <div class="absolute inset-0 bg-[#0a0a0a] border border-[#3c2a1a] group-hover:bg-black group-hover:border-[#b8a38a]/40 transition-colors"></div>
              <span class="relative z-10 font-fantasy text-[10px] uppercase text-[#b8a38a] group-hover:text-white">Cambiar Clase</span>
          </button>
          <button @click="$emit('save-and-exit')" class="group relative px-6 py-2 overflow-hidden transition-all shadow-lg">
              <div class="absolute inset-0 bg-[#8c2d1f] border border-white/10 group-hover:scale-105 transition-transform"></div>
              <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/black-paper.png')] opacity-20"></div>
              <span class="relative z-10 font-fantasy text-[10px] uppercase text-white tracking-widest">Guardar partida</span>
          </button>
          <!-- EXIT SEAL (MOVED) -->
          <button 
            @click="$emit('exit-confirm')" 
            class="relative size-11 flex items-center justify-center group transition-transform hover:rotate-12 active:scale-90"
          >
            <div class="absolute inset-0 bg-[#8c2d1f] rounded-full shadow-[0_4px_12px_rgba(0,0,0,1)] border-4 border-[#5c1a11] group-hover:bg-[#a63626] transition-colors"></div>
            <div class="absolute inset-1 border-2 border-dashed border-black/30 rounded-full"></div>
            <Icon icon="game-icons:skull-crossed-bones" class="relative z-10 text-black text-xl pointer-events-none" />
          </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  hero: Object,
  run: Object
})

defineEmits(['change-class', 'save-and-exit', 'exit-confirm'])
</script>
