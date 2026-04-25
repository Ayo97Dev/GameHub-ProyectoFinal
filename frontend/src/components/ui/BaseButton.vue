<script setup>
import { computed } from 'vue'

const props = defineProps({
  type:    { type: String, default: 'button' },
  variant: { type: String, default: 'primary' },
  size:    { type: String, default: 'md' },
})

const base = 'font-display font-bold uppercase tracking-[0.12em] transition-all duration-150 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neon-cyan focus-visible:ring-offset-2 focus-visible:ring-offset-retro-deep disabled:opacity-40 disabled:cursor-not-allowed hover:translate-x-[2px] hover:translate-y-[2px] active:translate-x-[4px] active:translate-y-[4px]'

const sizes = {
  sm: 'px-3 py-1.5 text-xs',
  md: 'px-6 py-2.5 text-sm',
  lg: 'px-8 py-3.5 text-base',
}

const variants = {
  // ── Cyan: acción principal ──
  primary: 'border-2 border-black bg-neon-cyan text-black shadow-[4px_4px_0px_#000] hover:shadow-[2px_2px_0px_#000] active:shadow-none hover:brightness-110',

  // ── Outline: acciones secundarias / ghost ──
  ghost: 'border-2 border-neon-cyan bg-transparent text-neon-cyan shadow-[4px_4px_0px_#000] hover:shadow-[2px_2px_0px_#000] active:shadow-none hover:bg-neon-cyan/10',

  // ── Pink: peligro / destrucción ──
  danger: 'border-2 border-black bg-neon-pink text-black shadow-[4px_4px_0px_#000] hover:shadow-[2px_2px_0px_#000] active:shadow-none hover:brightness-110',

  // ── Yellow: CTA tienda / recursos ──
  yellow: 'border-2 border-black bg-neon-yellow text-black shadow-[4px_4px_0px_#000] hover:shadow-[2px_2px_0px_#000] active:shadow-none hover:brightness-110',
}

const classes = computed(() => `${base} ${sizes[props.size] ?? sizes.md} ${variants[props.variant] ?? variants.primary}`)
</script>

<template>
  <button :type="type" :class="classes">
    <slot />
  </button>
</template>
