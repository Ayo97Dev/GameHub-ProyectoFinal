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
    return `rounded-lg border border-slate-300 dark:border-slate-700 bg-white/60 dark:bg-slate-900/40 text-slate-700 dark:text-slate-200 font-semibold tracking-wide transition hover:border-cyan-500 hover:bg-cyan-50 hover:text-cyan-600 dark:hover:border-cyan-400 dark:hover:bg-cyan-950/30 dark:hover:text-cyan-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/50 dark:focus-visible:ring-cyan-400/50 ${sizeClass}`
  }

  return `rounded-lg bg-gradient-to-r from-violet-600 via-fuchsia-500 to-cyan-500 font-bold tracking-wide text-white shadow-lg shadow-violet-500/25 transition hover:-translate-y-0.5 hover:brightness-110 hover:shadow-cyan-500/30 active:translate-y-0 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/60 focus-visible:ring-offset-2 dark:focus-visible:ring-violet-400/60 dark:focus-visible:ring-offset-slate-900 ${sizeClass}`
})
</script>

<template>
  <button :type="type" :class="classes">
    <slot />
  </button>
</template>
