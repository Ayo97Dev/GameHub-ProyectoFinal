<script setup>
import { RouterLink } from 'vue-router'
import BaseButton from './BaseButton.vue'

defineProps({
  game: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <article class="group gh-surface gh-neon-ring overflow-hidden transition hover:-translate-y-1">
    <div class="relative">
      <img :src="game.cover" :alt="game.title" class="h-48 w-full object-cover opacity-85 transition duration-500 group-hover:scale-[1.03] group-hover:opacity-100" />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-transparent" />
    </div>
    <div class="space-y-3 p-4">
      <h3 class="text-lg font-semibold text-white">{{ game.title }}</h3>
      <p class="text-sm text-slate-300">{{ game.description }}</p>
      <RouterLink :to="game.route" class="inline-flex">
        <BaseButton>Jugar ahora</BaseButton>
      </RouterLink>
    </div>
  </article>
</template>
