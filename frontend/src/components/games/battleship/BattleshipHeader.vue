<template>
  <div>
    <header class="relative z-10 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
      <div>
        <p class="font-pixel text-[10px] uppercase tracking-[0.3em] text-neon-cyan">Conquista de los Océanos // Modo Estrategia</p>
        <h2 class="gh-title-glow font-display mt-1 text-3xl font-black text-white">Estrategia Naval</h2>
        <p class="font-sans mt-2 max-w-2xl text-xs text-retro-white/70">
          <template v-if="phase === 'placement'">DESPLIEGUE: Posiciona tu flota en el tablero táctico.</template>
          <template v-else>COMBATE: Detecta y elimina la amenaza enemiga.</template>
        </p>
      </div>

      <button
        type="button"
        class="gh-surface-hover gh-surface bg-neon-cyan/10 px-4 py-2 font-display text-sm font-bold text-neon-cyan transition disabled:opacity-50"
        :disabled="isLoading"
        @click="$emit('new-game')"
      >
        {{ isLoading ? 'Sincronizando...' : 'Nueva Misión' }}
      </button>
    </header>

    <div v-if="syncError" class="relative z-10 mt-2 border border-neon-pink/30 bg-neon-pink/10 p-2 font-pixel text-[11px] text-neon-pink uppercase">
      ERROR DE SINCRONIZACIÓN: {{ syncError }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  phase: String,
  isLoading: Boolean,
  syncError: String
})

defineEmits(['new-game'])
</script>
