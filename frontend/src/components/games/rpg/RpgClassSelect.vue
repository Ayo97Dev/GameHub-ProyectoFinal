<template>
  <div class="absolute inset-0 z-[100] bg-[#1a1714] p-8 overflow-auto custom-scroll flex flex-col items-center">
    <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] pointer-events-none"></div>
    <h2 class="text-4xl font-fantasy text-[#b8a38a] mb-12 uppercase tracking-[0.2em] relative z-10">Escoge tu Clase</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-7xl w-full pb-20 relative z-10">
      <div 
        v-for="c in classes" 
        :key="c.id"
        @click="unlockedClasses.includes(c.id) && $emit('select-class', c.id)"
        class="group relative p-6 bg-[#0a0a0a] border-4 border-[#3c2a1a] transition-all flex flex-col gap-4 transform hover:-translate-y-2"
        :class="unlockedClasses.includes(c.id) ? 'hover:border-[#8c2d1f] cursor-pointer' : 'opacity-60 grayscale cursor-not-allowed'"
      >
        <div class="flex items-center justify-between border-b border-[#3c2a1a] pb-2 relative z-10">
          <span class="font-fantasy text-xl text-[#b8a38a] group-hover:text-white uppercase">{{ c.name }}</span>
          <div class="flex flex-col items-end">
            <span class="text-[10px] text-[#8c2d1f] font-fantasy uppercase px-2 py-0.5 bg-black border border-[#8c2d1f]/30">{{ c.role }}</span>
            <span v-if="!unlockedClasses.includes(c.id)" class="text-[9px] text-amber-500 font-fantasy mt-1 flex items-center gap-1">
              <Icon icon="game-icons:padlock" class="size-3" /> Tienda Global
            </span>
          </div>
        </div>
        <p class="font-serif text-sm italic text-[#b8a38a]/60 leading-tight h-12 overflow-hidden">{{ c.description }}</p>
        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[9px] font-fantasy text-[#b8a38a]/40 uppercase">
          <div class="flex justify-between border-b border-[#3c2a1a]/40 pb-0.5"><span>Vida</span><span class="text-red-500">{{ c.stats.hp }}</span></div>
          <div class="flex justify-between border-b border-[#3c2a1a]/40 pb-0.5"><span>Maná</span><span class="text-blue-500">{{ c.stats.mp }}</span></div>
          <div class="flex justify-between"><span>Ataque</span><span class="text-amber-500">{{ c.stats.attack }}</span></div>
          <div class="flex justify-between"><span>P. Mágico</span><span class="text-blue-400">{{ c.stats.magicAttack }}</span></div>
          <div class="flex justify-between"><span>Defensa</span><span class="text-slate-400">{{ c.stats.defense }}</span></div>
          <div class="flex justify-between"><span>D. Mágica</span><span class="text-purple-400">{{ c.stats.magicDefense }}</span></div>
          <div class="flex justify-between"><span>Agilidad</span><span class="text-green-400">{{ c.stats.speed }}</span></div>
          <div class="flex justify-between"><span>Rec. Maná</span><span class="text-blue-300">+{{ c.stats.manaRegen }}</span></div>
        </div>
        <div v-if="unlockedClasses.includes(c.id)" class="absolute inset-0 bg-[#8c2d1f]/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
      </div>
    </div>
    <button @click="$emit('go-back')" class="mt-8 px-10 py-3 bg-[#3c2a1a] text-[#b8a38a] font-fantasy uppercase tracking-widest hover:bg-[#4d3621]">Regresar</button>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  classes: Array,
  unlockedClasses: Array
})

defineEmits(['select-class', 'go-back'])
</script>
