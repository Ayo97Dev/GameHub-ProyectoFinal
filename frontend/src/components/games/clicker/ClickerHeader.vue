<template>
  <!-- HEADER HUD: Unified Sleek Bar -->
  <header class="relative z-30 px-6 py-4 border-b border-white/5 bg-black/40 backdrop-blur-3xl flex flex-col sm:flex-row items-center justify-between gap-4">
    
    <!-- Main Hub Resource -->
    <div class="flex items-center gap-6">
       <div class="text-left">
          <p class="font-pixel text-xs uppercase text-neon-yellow/60 tracking-widest mb-0.5">FONDOS_DISPONIBLES</p>
          <h2 class="font-display text-4xl sm:text-5xl font-black text-neon-yellow gh-title-glow leading-none tracking-tighter">
            {{ formatNumber(balance) }}
          </h2>
       </div>
    </div>

    <!-- System Stats -->
    <div class="flex items-stretch gap-6 sm:gap-12">
       <div class="text-right">
          <p class="font-pixel text-xs uppercase text-white/40 tracking-widest mb-0.5">RENDIMIENTO_DPS</p>
          <p class="font-display text-xl font-black text-neon-cyan">{{ dps.toFixed(1) }}</p>
       </div>
       <div class="w-px bg-white/10 hidden sm:block"></div>
       <div class="text-right">
          <p class="font-pixel text-xs uppercase text-white/40 tracking-widest mb-0.5">COMBO_ACTUAL</p>
          <div class="flex items-center gap-2 justify-end">
             <p class="font-display text-xl font-black" :class="comboCount > 0 ? 'text-neon-pink' : 'text-white/20'">X{{ comboCount }}</p>
             <span v-if="comboMultiplier > 1" class="font-pixel text-xs bg-neon-pink/20 text-neon-pink px-1 rounded animate-pulse">
                {{ comboMultiplier.toFixed(2) }}x
             </span>
          </div>
       </div>
    </div>

  </header>
</template>

<script setup>
defineProps({
  balance: { type: Number, required: true },
  dps: { type: Number, required: true },
  comboCount: { type: Number, required: true },
  comboMultiplier: { type: Number, required: true }
})

function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(2) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return Math.floor(n).toString()
}
</script>
