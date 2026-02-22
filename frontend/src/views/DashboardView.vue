<script setup>
const stats = [
  { label: 'Puntaje Máximo', value: '12,450' },
  { label: 'Tiempo Total', value: '09h 31m' },
  { label: 'Partidas Guardadas', value: '14' },
]

const achievements = [
  'Primer Boss derrotado',
  'Combo x100',
  'Maratón de 2 horas',
]
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-10">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
      <h1 class="text-3xl font-bold text-white">Mi Dashboard</h1>
      <p class="mt-2 text-slate-300">Resumen de tu progreso, estadísticas y logros desbloqueados.</p>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-3">
      <article v-for="item in stats" :key="item.label" class="rounded-xl border border-slate-800 bg-zinc-900 p-4">
        <p class="text-sm text-slate-400">{{ item.label }}</p>
        <p class="mt-2 text-2xl font-semibold text-cyan-300">{{ item.value }}</p>
      </article>
    </div>

    <div class="mt-6 rounded-xl border border-slate-800 bg-zinc-900 p-5">
      <h2 class="text-xl font-semibold text-white">Logros</h2>
      <ul class="mt-3 space-y-2">
        <li v-for="achievement in achievements" :key="achievement" class="rounded-md border border-violet-500/30 bg-violet-500/10 px-3 py-2 text-sm text-violet-200">
          {{ achievement }}
        </li>
      </ul>
    </div>
  </section>
</template>
