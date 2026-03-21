<script setup>
import { onMounted, ref } from 'vue'
import api from '../lib/axios'

const achievements = ref([])
const isLoading    = ref(true)

const RARITY_STYLES = {
  common:    { label: 'Común',        classes: 'border-slate-300 bg-slate-50 dark:bg-slate-800/40 dark:border-slate-600' },
  uncommon:  { label: 'Poco común',   classes: 'border-green-400/50 bg-green-50 dark:bg-green-900/20 dark:border-green-600/40' },
  rare:      { label: 'Raro',         classes: 'border-blue-400/50 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600/40' },
  epic:      { label: 'Épico',        classes: 'border-violet-400/50 bg-violet-50 dark:bg-violet-900/20 dark:border-violet-600/40' },
  legendary: { label: 'Legendario',   classes: 'border-yellow-400/50 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-600/40' },
}

onMounted(async () => {
  try {
    const { data } = await api.get('/achievements')
    achievements.value = data.data ?? []
  } catch {
    achievements.value = []
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="mx-auto w-full max-w-5xl px-4 py-10">
    <header class="mb-6">
      <p class="text-xs font-bold uppercase tracking-[0.24em] text-violet-600 dark:text-violet-300">Colección</p>
      <h1 class="mt-2 text-3xl font-black text-slate-800 dark:text-white transition-colors">🎖️ Logros</h1>
      <p class="mt-1 text-slate-500 dark:text-slate-400">
        {{ achievements.filter(a => a.unlocked).length }} / {{ achievements.length }} desbloqueados
      </p>
    </header>

    <div v-if="isLoading" class="flex justify-center py-16 text-slate-500 dark:text-slate-400">
      Cargando logros…
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="a in achievements"
        :key="a.id"
        class="relative rounded-xl border p-4 transition-all"
        :class="[RARITY_STYLES[a.rarity]?.classes ?? RARITY_STYLES.common.classes, !a.unlocked && 'opacity-50 grayscale']"
      >
        <!-- Icono o emoji -->
        <div class="mb-3 flex items-start gap-3">
          <div class="size-10 shrink-0 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xl shadow-sm shadow-slate-200/60 dark:shadow-black/20 transition-colors">
            {{ a.icon_url ?? '🏅' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-800 dark:text-white leading-tight">{{ a.title }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ a.description }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <span class="text-xs font-medium px-2 py-0.5 rounded-full border"
            :class="RARITY_STYLES[a.rarity]?.classes ?? ''">
            {{ RARITY_STYLES[a.rarity]?.label ?? a.rarity }}
          </span>
          <span class="text-xs font-bold text-amber-600 dark:text-amber-400">+{{ a.points_reward }} pts</span>
        </div>

        <div v-if="a.unlocked" class="mt-2 text-xs text-green-600 dark:text-green-400">
          ✅ {{ a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'Desbloqueado' }}
        </div>

        <!-- Candado si no está desbloqueado -->
        <div v-else class="absolute right-3 top-3 text-slate-400 dark:text-slate-600 text-lg">🔒</div>
      </div>

      <div v-if="achievements.length === 0" class="col-span-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-8 text-center text-slate-500 dark:text-slate-400">
        No hay logros disponibles aún.
      </div>
    </div>
  </section>
</template>
