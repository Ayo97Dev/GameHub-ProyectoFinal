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
  <section class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-14">
    <div class="gh-surface gh-neon-ring gh-grid-bg relative overflow-hidden p-6 sm:p-10">
      <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-violet-300/30 dark:bg-violet-500/20 blur-3xl transition-colors" />
      <div class="pointer-events-none absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-cyan-300/30 dark:bg-cyan-500/15 blur-3xl transition-colors" />
      
      <p class="text-xs font-bold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-400">GameHub Universe</p>
      
      <h1 class="mt-3 text-3xl font-black leading-tight text-slate-800 dark:text-white sm:text-5xl transition-colors">
        Juega. Compite. Sube en el ranking.
      </h1>
      <p class="mt-4 max-w-2xl text-lg text-slate-600 dark:text-slate-300 transition-colors">
        Tu hub gamer moderno. Diseñado para ofrecer la mejor experiencia multijugador con progresión centralizada.
      </p>
      <div class="mt-6 flex flex-wrap gap-3 relative z-10">
        <RouterLink to="/play/rpg">
          <BaseButton>Comenzar partida</BaseButton>
        </RouterLink>
        <RouterLink to="/dashboard">
          <BaseButton variant="ghost">Ver perfil</BaseButton>
        </RouterLink>
      </div>
    </div>

    <!-- Indicador de carga -->
    <div v-if="gameStore.isLoading" class="mt-20 flex justify-center text-slate-500 dark:text-slate-400">
      <p>Cargando juegos...</p>
    </div>

    <div v-else class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <GameCard v-for="game in gameStore.games" :key="game.slug" :game="game" />
    </div>
  </section>
</template>
