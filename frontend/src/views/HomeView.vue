<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useGameStore } from '../stores/game'
import GameCard from '../components/ui/GameCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'

const gameStore = useGameStore()

onMounted(() => {
  if (gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:py-12">
    <!-- Hero Section with Retro Styling -->
    <div class="gh-panel gh-scanlines relative overflow-hidden mb-8">
      <div class="relative z-10">
        <p class="font-pixel text-xl sm:text-2xl font-bold uppercase tracking-[0.2em] text-neon-blue dark:text-neon-yellow mb-2">>> SYSTEM.INIT()</p>
        
        <h1 class="text-4xl font-display font-black uppercase text-retro-black dark:text-retro-white sm:text-6xl max-w-3xl leading-none md:leading-[1.1]">
          GAME_HUB <br/>
          <span class="gh-title-gradient">INSERT COIN</span>
        </h1>
        
        <p class="mt-6 max-w-xl font-sans text-sm sm:text-base font-bold uppercase leading-relaxed text-retro-white dark:text-retro-black bg-retro-black dark:bg-neon-cyan p-3 border-[3px] border-retro-black dark:border-neon-cyan shadow-[4px_4px_0px_#22d3ee] dark:shadow-none inline-block">
          La mejor experiencia multijugador arcade. Juega. Compite. Gana.
        </p>
        
        <div class="mt-8 flex flex-wrap gap-4 relative z-10 w-full sm:w-auto">
          <RouterLink to="/play/rpg">
            <BaseButton>START GAME</BaseButton>
          </RouterLink>
          <RouterLink to="/profile">
            <BaseButton variant="ghost">VIEW PROFILE</BaseButton>
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Indicador de carga -->
    <div v-if="gameStore.isLoading" class="mt-16 flex justify-center text-retro-black dark:text-neon-cyan font-pixel text-2xl uppercase blink">
      <p>LOADING MODULES...</p>
    </div>

    <!-- Game Cards Grid -->
    <div v-else class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <GameCard v-for="game in gameStore.games" :key="game.slug" :game="game" />
    </div>
  </section>
</template>

<style scoped>
.blink {
  animation: blink 1s step-start infinite;
}
@keyframes blink {
  50% { opacity: 0; }
}
</style>
