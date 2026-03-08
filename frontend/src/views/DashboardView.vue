<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const isLoading = ref(true)

onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }
  
  await authStore.fetchUser()
  isLoading.value = false
})

function formatTime(seconds) {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  return `${h}h ${m}m`
}

function formatDate(isoDate) {
  return new Date(isoDate).toLocaleDateString()
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-10">
    <div v-if="isLoading" class="text-center text-slate-500 dark:text-slate-400 py-20">
      Cargando perfil...
    </div>

    <template v-else-if="authStore.user">
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 p-6 flex items-center gap-5 shadow-sm dark:shadow-none backdrop-blur-md overflow-hidden relative transition-colors">
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-cyan-300/20 dark:bg-cyan-500/10 blur-3xl" />
        <div class="pointer-events-none absolute -left-10 bottom-0 h-32 w-32 rounded-full bg-violet-300/20 dark:bg-violet-500/10 blur-3xl" />
        <div class="relative z-10 h-16 w-16 rounded-full bg-gradient-to-br from-violet-500 to-cyan-400 flex items-center justify-center text-xl font-bold text-white shadow-lg shadow-violet-500/30 dark:shadow-cyan-500/20 shrink-0">
          {{ authStore.user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="relative z-10">
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white transition-colors">{{ authStore.user.name }}</h1>
          <p class="mt-1 text-slate-500 dark:text-slate-400 transition-colors">{{ authStore.user.email }}</p>
        </div>
      </div>

      <div class="mt-8">
        <h2 class="text-2xl font-semibold text-slate-800 dark:text-white mb-4 transition-colors">Estadísticas por Juego</h2>
        
        <div v-if="!authStore.user.global_stats || authStore.user.global_stats.length === 0" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-zinc-900 p-6 text-center text-slate-500 dark:text-slate-400 transition-colors">
          Aún no tienes estadísticas. ¡Juega para empezar a registrar tu progreso!
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <article 
            v-for="stat in authStore.user.global_stats" 
            :key="stat.game_id" 
            class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-zinc-900 p-5 shrink-0 shadow-sm dark:shadow-none transition-colors"
          >
            <h3 class="text-lg font-bold text-cyan-600 dark:text-cyan-300 mb-4 transition-colors">{{ stat.game.title }}</h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center bg-slate-50 dark:bg-zinc-950 px-3 py-2 rounded border-l-2 border-l-cyan-400 dark:border-l-cyan-500 border border-slate-100 dark:border-slate-800/50 transition-colors">
                <span class="text-sm text-slate-500 dark:text-slate-400">Puntaje Máx</span>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ stat.high_score }}</span>
              </div>
              <div class="flex justify-between items-center bg-slate-50 dark:bg-zinc-950 px-3 py-2 rounded border-l-2 border-l-violet-400 dark:border-l-violet-500 border border-slate-100 dark:border-slate-800/50 transition-colors">
                <span class="text-sm text-slate-500 dark:text-slate-400">Tiempo Jugado</span>
                <span class="font-semibold text-violet-600 dark:text-violet-300">{{ formatTime(stat.time_played) }}</span>
              </div>
              <div class="flex justify-between items-center bg-slate-50 dark:bg-zinc-950 px-3 py-2 rounded border-l-2 border-l-slate-300 dark:border-l-slate-600 border border-slate-100 dark:border-slate-800/50 transition-colors">
                <span class="text-sm text-slate-500 dark:text-slate-400">Última partida</span>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ formatDate(stat.last_played_at) }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </template>
  </section>
</template>
