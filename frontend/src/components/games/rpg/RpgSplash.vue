<template>
  <Transition name="fade-grim">
    <div v-if="show" class="absolute inset-0 z-[100] flex items-center justify-center bg-[#070707]">
      <!-- Flickering Shadow Overlay -->
      <div class="absolute inset-0 opacity-30 animate-flicker pointer-events-none bg-[radial-gradient(circle_at_50%_50%,rgba(60,40,20,0.4),transparent_70%)]"></div>
      
      <div class="relative z-10 flex flex-col items-center gap-10 text-center px-6">
        <div class="space-y-3">
          <h1 class="text-6xl md:text-7xl font-fantasy text-[#b8a38a] drop-shadow-[0_10px_20px_rgba(0,0,0,1)] tracking-tighter uppercase leading-none">Descenso al Abismo</h1>
          <div class="h-1 w-32 mx-auto bg-gradient-to-r from-transparent via-[#8c2d1f] to-transparent"></div>
        </div>
        
        <div class="flex flex-col gap-4">
          <button 
            @click="$emit('start-new')" 
            class="group relative px-12 py-6 transition-all duration-500 transform hover:scale-105 active:scale-95"
          >
            <div class="absolute inset-0 bg-[#1a1a1a] border-4 border-[#3c2a1a] shadow-[10px_10px_30px_rgba(0,0,0,0.8)]"></div>
            <div class="absolute inset-1 border-2 border-[#8c2d1f]/30"></div>
            <span class="relative z-10 font-fantasy text-3xl text-[#b8a38a] group-hover:text-white uppercase tracking-[0.2em] transition-colors">Nueva Gesta</span>
          </button>

          <button 
            v-if="hasSave && saveSummary"
            @click="$emit('load-run')" 
            class="group relative px-12 py-4 transition-all duration-500 transform hover:scale-105 active:scale-95 overflow-hidden"
          >
            <div class="absolute inset-0 bg-[#3c2a1a] border-2 border-[#8c2d1f]/40 shadow-lg"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
            <div class="relative z-10 flex flex-col items-center">
              <span class="font-fantasy text-xl text-[#b8a38a] group-hover:text-white uppercase tracking-widest transition-colors">Continuar Gesta</span>
              <span class="text-[10px] text-amber-600 font-fantasy uppercase tracking-wider mt-1 opacity-80">
                {{ saveSummary.className }} • Nivel {{ saveSummary.level }} • Piso {{ saveSummary.floor }}
              </span>
            </div>
          </button>
        </div>

        <p class="text-[#5c4a3a] font-serif italic text-lg max-w-md leading-relaxed">
          "Donde la luz se desvanece y la cordura se quiebra, comienza tu verdadera gesta."
        </p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: Boolean,
  hasSave: Boolean,
  saveSummary: Object
})
defineEmits(['start-new', 'load-run'])
</script>
