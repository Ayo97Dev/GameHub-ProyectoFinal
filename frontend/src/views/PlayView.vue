<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useGameStore } from '../stores/game'
import RpgPlaceholder from '../components/games/RpgPlaceholder.vue'
import ClickerPlaceholder from '../components/games/ClickerPlaceholder.vue'
import ArcadePlaceholder from '../components/games/ArcadePlaceholder.vue'

const route = useRoute()
const gameStore = useGameStore()

const game = computed(() => gameStore.gamesBySlug[route.params.slug])

const gameComponent = computed(() => {
  if (route.params.slug === 'rpg') {
    return RpgPlaceholder
  }

  if (route.params.slug === 'clicker') {
    return ClickerPlaceholder
  }

  if (route.params.slug === 'arcade') {
    return ArcadePlaceholder
  }

  return null
})
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:py-10">
    <header class="mb-5">
      <h1 class="text-2xl font-bold text-white sm:text-3xl">
        {{ game?.title || 'Juego no encontrado' }}
      </h1>
      <p class="mt-1 text-slate-300">Modo Cine activo: foco en gameplay + estadísticas en vivo.</p>
    </header>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
      <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-5">
        <component :is="gameComponent" v-if="gameComponent" />
        <div v-else class="min-h-[320px] rounded-xl border border-slate-700 bg-zinc-900 p-6 text-slate-300">
          El slug del juego no es válido.
        </div>
      </div>

      <aside class="rounded-2xl border border-slate-800 bg-zinc-900 p-4 sm:p-5">
        <h2 class="text-lg font-semibold text-cyan-300">Estadísticas en tiempo real</h2>
        <div class="mt-4 space-y-3">
          <div class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2">
            <p class="text-xs uppercase tracking-wide text-slate-400">Puntos</p>
            <p class="text-xl font-bold text-white">0</p>
          </div>
          <div class="rounded-md border border-slate-700 bg-slate-900 px-3 py-2">
            <p class="text-xs uppercase tracking-wide text-slate-400">Tiempo</p>
            <p class="text-xl font-bold text-white">00:00</p>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>
