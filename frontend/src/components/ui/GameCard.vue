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
  <article class="group gh-surface gh-surface-hover flex flex-col relative overflow-hidden p-0 h-full">
    <div class="relative border-b-4 border-retro-black dark:border-neon-cyan">
      <div class="gh-scanlines aspect-video overflow-hidden bg-black">
        <img :src="game.cover" :alt="game.title" class="h-full w-full object-cover grayscale opacity-80 transition-all duration-300 group-hover:grayscale-0 group-hover:opacity-100 group-hover:scale-105" />
      </div>
      <div class="absolute top-2 left-2 border-2 border-retro-black bg-neon-yellow px-2 py-0.5 font-pixel text-sm uppercase text-retro-black shadow-[2px_2px_0px_#09090b] dark:border-neon-pink dark:bg-neon-pink/90 dark:text-retro-white dark:shadow-[2px_2px_0px_#f472b6]">
        ONLINE
      </div>
    </div>
    
    <div class="flex flex-col flex-grow p-5 justify-between bg-white dark:bg-retro-dark">
      <div>
        <h3 class="font-display text-2xl font-black uppercase tracking-wider text-retro-black dark:text-retro-white border-b-2 border-retro-black dark:border-neon-cyan pb-2 mb-3 group-hover:text-neon-blue dark:group-hover:text-neon-cyan transition-colors">{{ game.title }}</h3>
        <p class="font-sans text-xs font-bold uppercase leading-relaxed text-retro-black dark:text-retro-white line-clamp-3 mb-6">{{ game.description }}</p>
      </div>
      <RouterLink :to="game.route || `/play/${game.slug}`" class="block mt-auto w-full">
        <BaseButton class="w-full">PLAY NOW</BaseButton>
      </RouterLink>
    </div>
  </article>
</template>
