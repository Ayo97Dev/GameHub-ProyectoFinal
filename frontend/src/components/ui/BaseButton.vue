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
  const baseClasses = 'font-display font-bold uppercase tracking-[0.1em] transition-all duration-200 inline-flex items-center justify-center hover:translate-x-[2px] hover:translate-y-[2px] active:translate-x-[4px] active:translate-y-[4px] focus-visible:outline-none focus-visible:ring-2 disabled:opacity-50 disabled:cursor-not-allowed'
  const sizeClass = props.size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-6 py-2.5 text-sm sm:text-base'

  if (props.variant === 'ghost') {
    return `${baseClasses} ${sizeClass} border-2 border-retro-black bg-white text-retro-black shadow-[4px_4px_0px_#09090b] hover:shadow-[2px_2px_0px_#09090b] active:shadow-none dark:border-neon-cyan dark:bg-transparent dark:text-neon-cyan dark:shadow-[4px_4px_0px_rgba(34,211,238,0.5)] dark:hover:shadow-[2px_2px_0px_rgba(34,211,238,0.5)] dark:active:shadow-none hover:bg-slate-100 dark:hover:bg-neon-cyan/10`
  }

  if (props.variant === 'danger') {
    return `${baseClasses} ${sizeClass} border-2 border-retro-black bg-neon-pink text-retro-black shadow-[4px_4px_0px_#09090b] hover:shadow-[2px_2px_0px_#09090b] active:shadow-none dark:border-neon-pink dark:bg-transparent dark:text-neon-pink dark:shadow-[4px_4px_0px_#f472b6] dark:hover:shadow-[2px_2px_0px_#f472b6] hover:brightness-110 dark:hover:bg-neon-pink/10`
  }

  // Primary
  return `${baseClasses} ${sizeClass} border-2 border-retro-black bg-neon-cyan text-retro-black shadow-[4px_4px_0px_#09090b] hover:shadow-[2px_2px_0px_#09090b] active:shadow-none dark:border-neon-cyan dark:bg-neon-cyan/20 dark:text-neon-cyan dark:shadow-[4px_4px_0px_#22d3ee] dark:hover:shadow-[2px_2px_0px_#22d3ee] dark:hover:bg-neon-cyan/30 hover:brightness-110`
})
</script>

<template>
  <button :type="type" :class="classes">
    <slot />
  </button>
</template>
