<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../lib/axios'

const route = useRoute()
const slug  = route.params.slug

const entries  = ref([])
const gameName = ref('')
const isLoading = ref(true)

onMounted(async () => {
  try {
    const [lbRes, gameRes] = await Promise.all([
      api.get(`/leaderboard/${slug}`),
      api.get(`/games/${slug}`),
    ])
    entries.value  = lbRes.data.data ?? []
    gameName.value = gameRes.data.data?.title ?? slug
  } catch {
    entries.value = []
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="mx-auto w-full max-w-3xl px-4 py-10">
    <header class="mb-6">
      <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-400">Competitivo</p>
      <h1 class="mt-2 text-3xl font-black text-slate-800 dark:text-white transition-colors">
        🏆 Leaderboard — {{ gameName }}
      </h1>
      <p class="mt-1 text-slate-500 dark:text-slate-400">Top jugadores de todos los tiempos.</p>
    </header>

    <div v-if="isLoading" class="flex justify-center py-16 text-slate-500 dark:text-slate-400">
      Cargando ranking…
    </div>

    <div v-else-if="entries.length === 0" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center text-slate-500 dark:text-slate-400 transition-colors">
      Aún no hay puntuaciones registradas.
    </div>

    <ol v-else class="space-y-3">
      <li
        v-for="(entry, i) in entries"
        :key="entry.user_id"
        class="flex items-center gap-4 rounded-xl border px-5 py-3 transition-all"
        :class="i === 0 ? 'border-yellow-400/50 bg-yellow-50 shadow-lg shadow-yellow-200/60 dark:bg-yellow-900/20 dark:shadow-yellow-900/20'
               : i === 1 ? 'border-slate-300/50 bg-slate-50 shadow-md shadow-slate-200/50 dark:bg-slate-800/40 dark:shadow-black/20'
               : i === 2 ? 'border-amber-600/30 bg-orange-50 shadow-md shadow-orange-200/50 dark:bg-orange-900/10 dark:shadow-black/20'
               : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900'"
      >
        <span class="w-10 text-center text-2xl font-black">
          {{ ['🥇', '🥈', '🥉'][i] ?? `#${i + 1}` }}
        </span>
        <div class="flex flex-1 items-center gap-3 min-w-0">
          <div v-if="entry.avatar" class="size-8 rounded-full overflow-hidden shrink-0">
            <img :src="entry.avatar" :alt="entry.username" class="size-full object-cover" />
          </div>
          <div v-else class="size-8 rounded-full bg-gradient-to-br from-cyan-400 to-violet-500 flex items-center justify-center shrink-0">
            <span class="text-xs font-bold text-white">{{ entry.username?.[0]?.toUpperCase() }}</span>
          </div>
          <p class="truncate font-semibold text-slate-800 dark:text-white">{{ entry.username }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-lg font-bold text-violet-600 dark:text-cyan-300">{{ Number(entry.high_score).toLocaleString() }}</p>
          <p v-if="entry.time_played" class="text-xs text-slate-400 dark:text-slate-500">
            {{ Math.floor(entry.time_played / 60) }}m jugadas
          </p>
        </div>
      </li>
    </ol>
  </section>
</template>
