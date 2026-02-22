<script setup>
import { RouterLink } from 'vue-router'
import { useGameStore } from '../stores/game'
import GameCard from '../components/ui/GameCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'

const gameStore = useGameStore()
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-14">
    <div class="gh-surface gh-neon-ring gh-grid-bg relative overflow-hidden p-6 sm:p-10">
      <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-violet-500/20 blur-3xl" />
      <div class="pointer-events-none absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-cyan-500/15 blur-3xl" />
      <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">GameHub Universe</p>
      <h1 class="mt-3 text-3xl font-bold leading-tight text-white sm:text-5xl">
        Juega. Compite. Sube en el ranking.
      </h1>
      <p class="mt-4 max-w-2xl text-slate-300">
        Tu hub gamer en modo oscuro, diseñado para RPG, Clicker y Arcade con progreso centralizado.
      </p>
      <div class="mt-6 flex flex-wrap gap-3">
        <RouterLink to="/play/rpg">
          <BaseButton>Comenzar partida</BaseButton>
        </RouterLink>
        <RouterLink to="/dashboard">
          <BaseButton variant="ghost">Ver perfil</BaseButton>
        </RouterLink>
      </div>
    </div>

    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <GameCard v-for="game in gameStore.games" :key="game.slug" :game="game" />
    </div>
  </section>
</template>
