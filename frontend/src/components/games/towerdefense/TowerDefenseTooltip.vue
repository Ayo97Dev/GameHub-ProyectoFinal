<template>
  <div v-if="selectedCell" class="fixed z-[100] w-[300px] sm:w-[340px] gh-glass bg-black/95 p-0 border-white/20 shadow-4xl overflow-hidden backdrop-blur-3xl scale-in-anim" :style="tooltipPosition">
    <header class="p-4 border-b border-white/10 flex justify-between items-center bg-white/5">
       <h3 class="font-display text-xs font-black text-white uppercase tracking-widest">COORD_UNIDAD_{{ selectedCell.x }}_{{ selectedCell.y }}</h3>
       <button @click="$emit('close')" class="text-white/40 hover:text-neon-pink">✕</button>
    </header>

    <div class="p-6 overflow-auto max-h-[450px] custom-scroll">
       <!-- MENU CONSTRUCCIÓN -->
       <div v-if="!selectedTower">
          <p class="font-pixel text-xs text-neon-cyan font-bold uppercase mb-4 tracking-[0.3em]">MÓDULOS_CONSTRUCCIÓN</p>
          <div class="space-y-2">
             <div 
               v-for="(type, key) in towerTypes" :key="key" 
               @click="$emit('build-tower', key)"
               class="group relative flex items-center p-3 border border-white/5 transition-all cursor-pointer bg-white/5 hover:border-neon-cyan/40 hover:bg-neon-cyan/5 shadow-[2px_2px_0_#000] hover:translate-x-1 hover:translate-y-1 hover:shadow-none"
               :class="{ 'opacity-30 grayscale cursor-not-allowed': gold < type.cost }"
             >
                <div class="size-10 shrink-0 border border-white/10 relative overflow-hidden mr-4 flex items-center justify-center bg-black/40" :style="{ '--c': type.color }">
                   <div class="absolute inset-0 opacity-20" :style="{ backgroundColor: 'var(--c)' }"></div>
                   <Icon :icon="type.icon" class="text-white text-xl relative z-10" :style="{ color: type.color }" />
                </div>
                <div class="flex-1 min-w-0">
                   <div class="flex justify-between items-baseline mb-0.5">
                      <span class="font-display text-[11px] font-black uppercase text-white group-hover:text-neon-cyan">{{ type.name }}</span>
                      <span class="font-pixel text-xs text-neon-yellow">{{ type.cost }}C</span>
                   </div>
                   <p class="font-sans text-xs font-medium text-white/40 uppercase truncate tracking-tight">{{ type.desc }}</p>
                </div>
             </div>
          </div>
        </div>

       <!-- MENU UPGRADE -->
       <div v-else class="space-y-6">
          <div class="flex items-center gap-5 p-3 bg-white/5 border border-white/10 shadow-[4px_4px_0_#000]">
             <div class="size-16 shrink-0 border border-white/20 relative overflow-hidden flex items-center justify-center bg-black/40">
                <div class="absolute inset-0 opacity-20" :style="{ backgroundColor: selectedTower.color }"></div>
                <Icon :icon="selectedTower.icon" class="text-white text-3xl relative z-10" :style="{ color: selectedTower.color }" />
             </div>
             <div class="flex-1 min-w-0">
                <h4 class="font-display text-lg font-black text-white uppercase leading-tight truncate">{{ selectedTower.name }}</h4>
                <p class="font-pixel text-xs text-neon-cyan uppercase tracking-[0.3em]">NIVEL_{{ selectedTower.level }}</p>
             </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
             <div class="p-3 bg-retro-dark border border-white/5 shadow-[2px_2px_0_#000]">
                <p class="font-pixel text-xs opacity-30 uppercase mb-1 tracking-widest">POTENCIA_FUEGO</p>
                <div class="flex items-center justify-between">
                  <span class="font-display text-xs font-black text-neon-pink">{{ selectedTower.damage.toFixed(1) }}</span>
                  <Icon icon="game-icons:fast-arrow" class="text-[10px] text-white/20" />
                  <span class="font-display text-xs font-black text-white">{{(selectedTower.damage * 1.4).toFixed(1)}}</span>
                </div>
             </div>
             <div class="p-3 bg-retro-dark border border-white/5 shadow-[2px_2px_0_#000]">
                <p class="font-pixel text-xs opacity-30 uppercase mb-1 tracking-widest">RANGO_ESCÁNER</p>
                <div class="flex items-center justify-between">
                  <span class="font-display text-xs font-black text-neon-cyan">{{ selectedTower.range.toFixed(1) }}</span>
                  <Icon icon="game-icons:fast-arrow" class="text-[10px] text-white/20" />
                  <span class="font-display text-xs font-black text-white">{{(selectedTower.range + 0.1).toFixed(1)}}</span>
                </div>
             </div>
          </div>

          <div class="flex flex-col gap-3">
             <button 
               @click="$emit('upgrade')"
               :disabled="gold < upgradeCost"
               class="w-full py-4 bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-[0.2em] shadow-[4px_4px_0_#000] transition-all hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none active:scale-95 disabled:opacity-20 disabled:grayscale"
             >
                ACTUALIZAR_SISTEMA ({{ upgradeCost }}C)
             </button>
             <button 
               @click="$emit('sell')" 
               class="w-full py-3 font-pixel text-xs uppercase tracking-widest transition-all border border-transparent flex items-center justify-center gap-2"
               :class="isSelling ? 'bg-neon-pink/20 text-neon-pink border-neon-pink animate-pulse' : 'text-white/30 hover:text-neon-pink hover:bg-white/5'"
             >
                <Icon v-if="isSelling" icon="game-icons:alert" />
                {{ isSelling ? `¿CONFIRMAR RECICLAJE? (+${selectedTowerSellValue}C)` : `[RECICLAR_MÓDULO: +${selectedTowerSellValue}C]` }}
             </button>
          </div>
       </div>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  selectedCell: Object,
  tooltipPosition: Object,
  selectedTower: Object,
  towerTypes: Object,
  gold: Number,
  upgradeCost: Number,
  selectedTowerSellValue: Number,
  isSelling: Boolean
})

defineEmits(['close', 'build-tower', 'upgrade', 'sell'])
</script>
