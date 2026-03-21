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
  <article class="group gh-surface gh-neon-ring overflow-hidden transition duration-300 hover:-translate-y-1.5">
    <div class="relative">
      <img :src="game.cover" :alt="game.title" class="h-48 w-full object-cover opacity-90 dark:opacity-85 transition duration-500 group-hover:scale-[1.03] group-hover:opacity-100" />
      <div class="absolute inset-0 bg-gradient-to-t from-white/90 via-white/10 dark:from-slate-950/90 dark:via-slate-950/10 to-transparent transition-colors" />
      <div class="absolute left-3 top-3 rounded-full border border-violet-300 bg-violet-100 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-violet-700 dark:border-violet-500/50 dark:bg-violet-500/20 dark:text-violet-200 transition-colors">
        Online
      </div>
    </div>
    <div class="space-y-3 p-4">
      <h3 class="text-xl font-bold text-slate-800 dark:text-white transition-colors">{{ game.title }}</h3>
      <p class="text-sm text-slate-600 dark:text-slate-300 line-clamp-2 transition-colors">{{ game.description }}</p>
      <RouterLink :to="game.route || `/play/${game.slug}`" class="inline-flex mt-2">
        <BaseButton>Jugar ahora</BaseButton>
      </RouterLink>
    </div>
  </article>
</template>
