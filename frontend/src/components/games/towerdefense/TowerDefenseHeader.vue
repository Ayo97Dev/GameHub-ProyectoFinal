<template>
  <header class="h-16 shrink-0 gh-glass border-b border-white/10 flex items-center justify-between px-6 z-40 bg-black/60 shadow-[0_4px_0_#000]">
    <!-- Stats Left -->
    <div class="flex items-center gap-10">
      <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-1">
          <Icon icon="game-icons:heart-organ" class="text-neon-pink text-xs" />
          <span class="font-pixel text-xs text-neon-cyan/80 uppercase tracking-[0.2em]">SISTEMA_INTEGRIDAD</span>
        </div>
        <div class="flex items-center gap-4">
          <div class="w-32 h-3 bg-retro-deep border border-white/10 flex gap-0.5 p-0.5 shadow-[2px_2px_0_#000]">
            <div v-for="i in 10" :key="i" class="flex-1 transition-all duration-500" :class="i <= (lives/10) ? 'bg-neon-pink shadow-[0_0_8px_#ff2d55]' : 'bg-white/5'"></div>
          </div>
          <span class="font-display text-xl font-black text-neon-pink leading-none tracking-tighter">{{ lives }}%</span>
        </div>
      </div>

      <div class="flex flex-col">
        <div class="flex items-center gap-2 mb-0.5">
          <Icon icon="game-icons:database" class="text-neon-cyan text-[10px]" />
          <span class="font-pixel text-xs text-white/40 uppercase tracking-widest">CRÉDITOS_NODO</span>
        </div>
        <span class="font-display text-xl font-black text-neon-cyan tracking-tighter">{{ gold }}<span class="text-xs ml-1 opacity-50 font-pixel">CR</span></span>
      </div>
    </div>

    <!-- Wave Info Center -->
    <div class="flex flex-col items-center">
      <div class="flex items-baseline gap-2">
        <span class="font-pixel text-sm text-neon-yellow uppercase tracking-[0.2em]">OLEADA</span>
        <span class="font-display text-3xl font-black text-white gh-title-glow">#{{ wave }}</span>
      </div>
      <div v-if="waveActive" class="w-40 mt-1">
        <div class="h-1 bg-white/5 overflow-hidden shadow-[1px_1px_0_#000]">
          <div class="h-full bg-neon-cyan shadow-[0_0_10px_#00f2ff]" :style="{ width: `${waveProgressPercent}%` }"></div>
        </div>
      </div>
    </div>

    <!-- Status Indicators Right -->
    <div class="flex items-center gap-6">
       <button v-if="!waveActive" @click="$emit('start-wave')" class="px-6 py-2 bg-neon-cyan text-black font-display text-sm font-black uppercase hover:scale-105 active:scale-95 transition-all shadow-[4px_4px_0_#000] hover:shadow-none hover:translate-x-1 hover:translate-y-1">INICIAR OLEADA</button>
       <div v-else class="flex flex-col items-end">
          <span class="font-pixel text-xs text-neon-yellow uppercase tracking-widest animate-pulse">MALWARE_DETECTADO</span>
          <span class="font-display text-sm font-black text-white">{{ remainingEnemies }} UNIDADES</span>
        </div>
    </div>
  </header>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  lives: Number,
  gold: Number,
  wave: Number,
  waveActive: Boolean,
  waveProgressPercent: Number,
  remainingEnemies: Number
})

defineEmits(['start-wave'])
</script>
