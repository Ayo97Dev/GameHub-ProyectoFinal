<script setup>
defineProps({
  show:  { type: Boolean, default: false },
  title: { type: String,  default: 'MODAL' },
})
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <Transition name="pixel-fade">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
        @click.self="emit('close')"
      >
        <!-- Panel principal -->
        <div class="gh-panel relative w-full max-w-md bg-retro-dark border-4 border-neon-cyan shadow-[12px_12px_0px_#000] overflow-hidden">

          <!-- Scanlines overlay -->
          <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-0"></div>

          <!-- Header -->
          <div class="relative z-10 flex items-center justify-between p-5 border-b-2 border-neon-cyan/30 bg-black/60">
            <div class="flex items-center gap-3">
              <span class="size-2 bg-neon-cyan animate-pulse block"></span>
              <h2 class="font-display text-lg font-black uppercase tracking-widest text-neon-cyan">
                {{ title }}
              </h2>
            </div>
            <button
              class="size-8 flex items-center justify-center text-white/40 hover:text-neon-pink border-2 border-transparent hover:border-neon-pink transition-all text-xl font-black shadow-none hover:shadow-[2px_2px_0px_#000]"
              @click="emit('close')"
            >✕</button>
          </div>

          <!-- Content slot -->
          <div class="relative z-10 p-6">
            <slot />
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>
