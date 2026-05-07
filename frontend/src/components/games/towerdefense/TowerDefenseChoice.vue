<template>
  <div class="relative z-40 flex flex-col items-center justify-center flex-1 p-6">
    <div class="gh-glass max-w-xl w-full p-10 text-center border-white/5 shadow-2xl bg-black/60">
      <div class="flex items-center justify-center gap-4 mb-8">
         <div class="h-px w-12 bg-neon-cyan"></div>
         <p class="font-pixel text-neon-cyan text-lg tracking-[0.4em] uppercase">COMANDO_ESTRATÉGICO</p>
         <div class="h-px w-12 bg-neon-cyan"></div>
      </div>
      
      <h1 class="font-display text-2xl sm:text-3xl font-black text-white mb-6 gh-title-glow tracking-[-0.05em]">PROYECTO CORTAFUEGOS</h1>
      <p class="font-sans text-xs font-medium uppercase text-white/40 mb-12 max-w-sm mx-auto leading-relaxed">
        CONSTRUYE NODOS DE DEFENSA PARA PROTEGER EL NÚCLEO DEL REACTOR CONTRA LA INFILTRACIÓN DE MALWARE.
      </p>
      
      <BaseLoading 
        v-if="isLoading" 
        message="CONECTANDO_NÚCLEO..." 
        submessage="Sincronizando protocolos de defensa" 
      />
      
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
          v-if="hasSavedGame"
          @click="$emit('start-game', true)"
          class="gh-glass border-neon-blue/30 p-6 flex flex-col items-center hover:bg-neon-blue/10 transition-all group"
        >
          <span class="font-pixel text-xs text-neon-blue mb-2">RECUPERAR_ESTADO</span>
          <span class="font-display text-xl text-white font-black">SOL_OLEADA_{{ savedWave }}</span>
        </button>
        
        <button
          @click="$emit('start-game', false)"
          class="gh-glass border-neon-cyan/30 p-6 flex flex-col items-center hover:bg-neon-cyan/10 transition-all group"
          :class="{ 'sm:col-span-2': !hasSavedGame }"
        >
          <span class="font-pixel text-xs text-neon-cyan mb-2">INICIANDO_PROCESO</span>
          <span class="font-display text-xl text-white font-black">NUEVO_DESPLIEGUE</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseLoading from '../../ui/BaseLoading.vue'

defineProps({
  isLoading: Boolean,
  hasSavedGame: Boolean,
  savedWave: Number
})

defineEmits(['start-game'])
</script>
