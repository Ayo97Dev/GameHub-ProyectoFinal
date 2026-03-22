<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'rectangle',
    validator: (value) => ['leaderboard', 'rectangle', 'skyscraper', 'mobile'].includes(value),
  },
  rotate: {
    type: Boolean,
    default: true,
  },
  interval: {
    type: Number,
    default: 6000,
  },
})

const ADS = [
  {
    brand: 'GameHub Plus',
    headline: 'Boost para tus partidas',
    tagline: 'Retos diarios, recompensas y eventos especiales.',
    cta: 'Descubrir',
    color: 'from-cyan-500 to-blue-600',
  },
  {
    brand: 'Cosmic Arena',
    headline: 'Nueva temporada competitiva',
    tagline: 'Sube de liga y desbloquea recompensas exclusivas.',
    cta: 'Jugar ahora',
    color: 'from-violet-500 to-fuchsia-600',
  },
  {
    brand: 'Pixel Store',
    headline: 'Skins de edición limitada',
    tagline: 'Solo esta semana con bonus de lanzamiento.',
    cta: 'Ver ofertas',
    color: 'from-emerald-500 to-teal-600',
  },
  {
    brand: 'Quiz Nights',
    headline: 'Torneo del fin de semana',
    tagline: 'Compite con la comunidad y entra al top 100.',
    cta: 'Unirme',
    color: 'from-amber-500 to-orange-600',
  },
]

const currentIndex = ref(Math.floor(Math.random() * ADS.length))
const currentAd = computed(() => ADS[currentIndex.value])

let rotationTimer = null

function nextAd() {
  currentIndex.value = (currentIndex.value + 1) % ADS.length
}

onMounted(() => {
  if (!props.rotate) return
  rotationTimer = setInterval(nextAd, props.interval)
})

onUnmounted(() => {
  if (rotationTimer) clearInterval(rotationTimer)
})

const wrapperClass = computed(() => {
  if (props.size === 'leaderboard') return 'w-[728px] h-[90px]'
  if (props.size === 'rectangle') return 'w-[300px] h-[250px]'
  if (props.size === 'skyscraper') return 'w-[160px] h-[600px]'
  return 'w-[320px] h-[50px]'
})
</script>

<template>
  <div class="group relative select-none cursor-pointer" :class="wrapperClass" @click="nextAd">
    <div
      v-if="size === 'leaderboard'"
      class="relative flex h-full w-full items-center overflow-hidden rounded-lg border border-slate-200/80 bg-white/95 transition-all duration-200 dark:border-slate-700/70 dark:bg-slate-900/95"
    >
      <div class="h-full w-24 bg-gradient-to-br" :class="currentAd.color" />
      <div class="flex-1 px-3">
        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ currentAd.brand }}</p>
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ currentAd.tagline }}</p>
      </div>
      <button class="mr-3 rounded-lg px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r shadow-sm" :class="currentAd.color">
        {{ currentAd.cta }}
      </button>
      <span class="absolute right-2 top-2 rounded-full border border-slate-200 bg-white/85 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/85 dark:text-slate-400">Ad</span>
    </div>

    <div
      v-else-if="size === 'rectangle'"
      class="relative flex h-full w-full flex-col overflow-hidden rounded-lg border border-slate-200/80 bg-white/95 transition-all duration-200 dark:border-slate-700/70 dark:bg-slate-900/95"
    >
      <div class="h-24 bg-gradient-to-br" :class="currentAd.color" />
      <div class="flex flex-1 flex-col gap-1.5 px-3 py-3">
        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ currentAd.brand }}</p>
        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ currentAd.headline }}</p>
        <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ currentAd.tagline }}</p>
        <button class="mt-auto w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r shadow-sm" :class="currentAd.color">
          {{ currentAd.cta }}
        </button>
      </div>
      <span class="absolute right-2 top-2 rounded-full border border-slate-200 bg-white/85 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/85 dark:text-slate-400">Ad</span>
    </div>

    <div
      v-else-if="size === 'skyscraper'"
      class="relative flex h-full w-full flex-col overflow-hidden rounded-lg border border-slate-200/80 bg-white/95 transition-all duration-200 dark:border-slate-700/70 dark:bg-slate-900/95"
    >
      <div class="h-14 px-3 flex items-center bg-gradient-to-r" :class="currentAd.color">
        <p class="text-sm font-extrabold uppercase tracking-[0.1em] text-white">{{ currentAd.brand }}</p>
      </div>
      <div class="flex-1 bg-gradient-to-b from-transparent to-slate-50/70 dark:to-slate-900/60">
        <div class="h-40 bg-gradient-to-br" :class="currentAd.color" />
        <div class="flex h-[calc(100%-10rem)] flex-col gap-2 px-3 py-3">
        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ currentAd.headline }}</p>
        <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ currentAd.tagline }}</p>
        <button class="mt-auto w-full rounded-lg px-3 py-2 text-xs font-semibold text-white bg-gradient-to-r shadow-sm" :class="currentAd.color">
          {{ currentAd.cta }}
        </button>
        </div>
      </div>
      <span class="absolute right-2 top-2 rounded-full border border-slate-200 bg-white/85 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/85 dark:text-slate-400">Ad</span>
    </div>

    <div
      v-else
      class="relative flex h-full w-full items-center gap-2 overflow-hidden rounded-lg border border-slate-200/80 bg-white/95 px-2 transition-all duration-200 dark:border-slate-700/70 dark:bg-slate-900/95"
    >
      <div class="size-7 rounded-md bg-gradient-to-br" :class="currentAd.color" />
      <div class="min-w-0 flex-1">
        <p class="truncate text-[11px] font-semibold text-slate-800 dark:text-slate-100">{{ currentAd.brand }}</p>
        <p class="truncate text-[10px] text-slate-600 dark:text-slate-300">{{ currentAd.tagline }}</p>
      </div>
      <button class="rounded-md px-2 py-1 text-[10px] font-semibold text-white bg-gradient-to-r" :class="currentAd.color">
        {{ currentAd.cta }}
      </button>
      <span class="absolute right-1.5 top-1 rounded-full border border-slate-200 bg-white/85 px-1.5 py-0.5 text-[8px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/85 dark:text-slate-400">Ad</span>
    </div>
  </div>
</template>
