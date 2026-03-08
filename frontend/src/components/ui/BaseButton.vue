<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'md',
  },
})

const classes = computed(() => {
  const sizeClass = props.size === 'sm' ? 'px-3 py-2 text-sm' : 'px-4 py-2.5 text-sm sm:text-base'

  if (props.variant === 'ghost') {
    return `rounded-md border border-slate-300 dark:border-slate-700 bg-transparent text-slate-700 dark:text-slate-200 transition hover:border-cyan-500 hover:text-cyan-600 dark:hover:border-cyan-400 dark:hover:text-cyan-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/50 ${sizeClass}`
  }

  return `rounded-md bg-gradient-to-r from-violet-600 to-cyan-500 font-semibold text-white transition hover:brightness-110 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/60 focus-visible:ring-offset-2 ${sizeClass}`
})
</script>

<template>
  <button :type="type" :class="classes">
    <slot />
  </button>
</template>
