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
    return `rounded-md border border-slate-700 bg-transparent text-slate-200 transition hover:border-cyan-400 hover:text-cyan-300 ${sizeClass}`
  }

  return `rounded-md bg-gradient-to-r from-violet-600 to-cyan-500 font-semibold text-white transition hover:brightness-110 active:scale-[0.99] ${sizeClass}`
})
</script>

<template>
  <button :type="type" :class="classes">
    <slot />
  </button>
</template>
