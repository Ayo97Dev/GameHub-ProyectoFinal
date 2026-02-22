<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Modal',
  },
})

const emit = defineEmits(['close'])
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="emit('close')">
    <div class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">{{ title }}</h2>
        <button class="text-slate-400 transition hover:text-cyan-300" @click="emit('close')">✕</button>
      </div>
      <slot />
    </div>
  </div>
</template>
