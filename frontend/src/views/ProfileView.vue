<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/axios'

const authStore = useAuthStore()
const router    = useRouter()
const isLoading = ref(true)

const achievements     = ref([])
const achievementsByGame = computed(() => {
  const map = {}
  for (const a of achievements.value) {
    if (a.game_id !== null) {
      if (!map[a.game_id]) map[a.game_id] = []
      map[a.game_id].push(a)
    }
  }
  return map
})

const RARITY_BADGE = {
  common:    'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
  uncommon:  'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  rare:      'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
  epic:      'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
  legendary: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
}

// Reset de progreso
const resetTarget   = ref(null)   // { slug, title }
const isResetting   = ref(false)

function confirmReset(stat) {
  resetTarget.value = { slug: stat.game.slug, title: stat.game.title }
}

async function executeReset() {
  if (!resetTarget.value) return
  isResetting.value = true
  try {
    await api.delete(`/games/${resetTarget.value.slug}/reset`)
    // Refrescar el usuario para actualizar stats
    await authStore.fetchUser()
    // Limpiar logros del juego reseteado de la lista local para reflejar el cambio
    achievements.value = []
    const { data } = await api.get('/achievements')
    achievements.value = data.data ?? []
  } catch { /* silencioso */ } finally {
    isResetting.value = false
    resetTarget.value = null
  }
}

onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }
  await authStore.fetchUser()
  try {
    const { data } = await api.get('/achievements')
    achievements.value = data.data ?? []
  } catch { /* silencioso */ }
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
      <div class="gh-panel flex items-center gap-5 overflow-hidden p-6 relative">
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-cyan-300/20 dark:bg-cyan-500/10 blur-3xl" />
        <div class="pointer-events-none absolute -left-10 bottom-0 h-32 w-32 rounded-full bg-violet-300/20 dark:bg-violet-500/10 blur-3xl" />
        <div class="relative z-10 h-16 w-16 rounded-full bg-gradient-to-br from-violet-500 to-cyan-400 flex items-center justify-center text-xl font-bold text-white shadow-lg shadow-violet-500/30 dark:shadow-cyan-500/20 shrink-0">
          {{ authStore.user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="relative z-10">
          <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-400">Perfil</p>
          <h1 class="text-3xl font-black text-slate-800 dark:text-white transition-colors">{{ authStore.user.name }}</h1>
          <p class="mt-1 text-slate-500 dark:text-slate-400 transition-colors">{{ authStore.user.email }}</p>
        </div>
      </div>

      <div class="mt-8">
        <h2 class="mb-4 text-2xl font-black text-slate-800 dark:text-white transition-colors">Estadísticas por Juego</h2>
        
        <div v-if="!authStore.user.global_stats || authStore.user.global_stats.length === 0" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-zinc-900 p-6 text-center text-slate-500 dark:text-slate-400 transition-colors">
          Aún no tienes estadísticas. ¡Juega para empezar a registrar tu progreso!
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <article 
            v-for="stat in authStore.user.global_stats" 
            :key="stat.game_id" 
            class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-zinc-900 p-5 shrink-0 shadow-lg shadow-slate-200/50 dark:shadow-black/20 transition-colors flex flex-col gap-4"
          >
            <h3 class="text-lg font-bold text-cyan-600 dark:text-cyan-300 transition-colors">{{ stat.game.title }}</h3>

            <!-- Stats -->
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

            <!-- Logros del juego -->
            <div v-if="achievementsByGame[stat.game_id]?.length" class="border-t border-slate-100 dark:border-slate-800 pt-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">
                Logros
                <span class="ml-1 text-slate-500 dark:text-slate-400">
                  {{ achievementsByGame[stat.game_id].filter(a => a.unlocked).length }}/{{ achievementsByGame[stat.game_id].length }}
                </span>
              </p>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="a in achievementsByGame[stat.game_id]"
                  :key="a.id"
                  :title="a.title + (a.unlocked ? '\n✅ ' + (a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'Desbloqueado') : '\n🔒 Bloqueado') + '\n' + a.description"
                  class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-all"
                  :class="[
                    RARITY_BADGE[a.rarity] ?? RARITY_BADGE.common,
                    a.unlocked ? 'opacity-100' : 'opacity-35 grayscale'
                  ]"
                >
                  <span>{{ a.unlocked ? '🏆' : '🔒' }}</span>
                  <span class="max-w-28 truncate">{{ a.title }}</span>
                </div>
              </div>
            </div>

            <!-- Botón reset -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-auto">
              <button
                @click="confirmReset(stat)"
                class="w-full rounded-lg border border-red-300 dark:border-red-800/60 bg-red-50 dark:bg-red-900/10 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/25 transition-colors"
              >
                🗑️ Reiniciar progreso
              </button>
            </div>
          </article>
        </div>
      </div>
    </template>
  </section>

  <!-- Modal de confirmación de reset -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="resetTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="resetTarget = null"
      >
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-6 shadow-2xl">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">¿Reiniciar progreso?</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
            Se borrará toda la partida guardada y las estadísticas de
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ resetTarget.title }}</span>.
            Esta acción no se puede deshacer.
          </p>
          <div class="flex gap-3">
            <button
              @click="resetTarget = null"
              class="flex-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="executeReset"
              :disabled="isResetting"
              class="flex-1 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 px-4 py-2 text-sm font-semibold text-white transition-colors"
            >
              {{ isResetting ? 'Borrando…' : 'Sí, reiniciar' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
