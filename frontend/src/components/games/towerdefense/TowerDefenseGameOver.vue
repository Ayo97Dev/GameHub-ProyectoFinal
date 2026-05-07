<template>
  <div class="relative z-40 flex flex-col items-center justify-center flex-1 p-6">
     <div class="gh-glass max-w-md w-full p-10 text-center border-neon-pink/30 bg-black/80 backdrop-blur-2xl shadow-[0_0_50px_rgba(255,45,85,0.2)]">
        <div class="mb-8 flex flex-col items-center">
           <div class="size-16 border-2 border-neon-pink flex items-center justify-center mb-4 rotate-45 animate-pulse">
              <span class="font-display text-4xl text-neon-pink -rotate-45">!</span>
           </div>
           <span class="font-pixel text-neon-pink text-xs uppercase tracking-[0.3em] mb-2">FALLO_CRÍTICO_SISTEMA</span>
           <h2 class="font-display text-5xl font-black text-white leading-tight tracking-tighter">FALLO_TOTAL</h2>
        </div>

        <p class="font-sans text-xs font-bold text-white/40 uppercase mb-8 leading-relaxed">
           EL NÚCLEO DEL REACTOR HA SIDO TOTALMENTE COMPROMETIDO. EL MALWARE HA TOMADO EL CONTROL DE LOS SISTEMAS DE DEFENSA.
        </p>

        <div class="grid grid-cols-2 gap-4 mb-10">
           <div class="p-4 bg-white/5 border border-white/5">
              <p class="font-pixel text-xs text-white/30 uppercase mb-1">OLEADAS</p>
              <p class="font-display text-3xl font-black text-white">{{ wave - 1 }}</p>
           </div>
           <div class="p-4 bg-white/5 border border-white/5">
              <p class="font-pixel text-xs text-white/30 uppercase mb-1">DATOS_PERDIDOS</p>
              <p class="font-display text-3xl font-black text-neon-cyan">{{ (wave - 1) * 100 }}</p>
           </div>
        </div>

        <button 
          @click="$emit('reset')"
          class="w-full py-5 bg-neon-pink text-black font-display text-xs font-black uppercase shadow-[6px_6px_0_#000] hover:translate-x-1 hover:translate-y-1 hover:shadow-none active:scale-95 transition-all"
        >
          REINICIAR_PROTOCOLO_DEFENSA
        </button>
     </div>
  </div>
</template>

<script setup>
defineProps({
  wave: Number
})

defineEmits(['reset'])
</script>
