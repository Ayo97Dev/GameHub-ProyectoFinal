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
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-black/70 p-4 transition-colors" @click.self="emit('close')">
    <div class="w-full max-w-md rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5 shadow-xl shadow-slate-200/50 dark:shadow-black/20 transition-colors">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white transition-colors">{{ title }}</h2>
        <button class="text-slate-500 dark:text-slate-400 transition hover:text-violet-600 dark:hover:text-cyan-300" @click="emit('close')">✕</button>
      </div>
      <slot />
    </div>
  </div>
</template>
