<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

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

const router = useRouter()

const ADS = [
  {
    brand: 'REACTOR_CLICK.EXE',
    headline: 'IMPULSO_CRÍTICO',
    tagline: 'GOLPEA EL NÚCLEO PARA INICIAR LA SECUENCIA DE COMBO.',
    cta: 'CONECTAR',
    color: 'border-neon-cyan bg-neon-cyan/10 text-neon-cyan',
    accent: 'bg-neon-cyan',
    slug: 'clicker',
  },
  {
    brand: 'CONNECT_4.BIN',
    headline: 'VS_CORE_INTELLIGENCE',
    tagline: 'RETA A LA IA EN UN DUELO DE CÓDIGO CLÁSICO.',
    cta: 'DESAFÍO',
    color: 'border-neon-blue bg-neon-blue/10 text-neon-blue',
    accent: 'bg-neon-blue',
    slug: 'connect4',
  },
  {
    brand: 'QUIZ_MASTER.SYS',
    headline: 'DATA_MINING_MODE',
    tagline: 'EXAMINA TU MEMORIA EN EL DUELO DE DATOS.',
    cta: 'INICIAR',
    color: 'border-neon-fuchsia bg-neon-fuchsia/10 text-neon-fuchsia',
    accent: 'bg-neon-fuchsia',
    slug: 'quiz',
  },
  {
    brand: 'RPG_MODULE.EXE',
    headline: 'SENDA_DEL_HÉROE',
    tagline: 'EXPLORA LAS SALAS DEL SISTEMA. SOBREVIVE AL CÓDIGO.',
    cta: 'BOOT_GAME',
    color: 'border-neon-pink bg-neon-pink/10 text-neon-pink',
    accent: 'bg-neon-pink',
    slug: 'rpg',
  },
  {
    brand: 'TOWER_DEF.DAT',
    headline: 'CORE_DEFENSE',
    tagline: 'PROTEGE EL REACTOR DE LAS OLEADAS DE MALWARE.',
    cta: 'DEFENDER',
    color: 'border-neon-yellow bg-neon-yellow/10 text-neon-yellow',
    accent: 'bg-neon-yellow',
    slug: 'tower-defense',
  },
]

const currentIndex = ref(Math.floor(Math.random() * ADS.length))
const currentAd = computed(() => ADS[currentIndex.value])

let rotationTimer = null

function nextAd() {
  currentIndex.value = (currentIndex.value + 1) % ADS.length
}

function handleAdClick() {
  if (currentAd.value.slug) {
    router.push({ name: 'play', params: { slug: currentAd.value.slug } })
  }
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
  if (props.size === 'rectangle') return 'w-full max-w-[300px] h-[250px]'
  if (props.size === 'skyscraper') return 'w-full max-w-[160px] h-[600px]'
  return 'w-full max-w-[320px] h-fit'
})
</script>

<template>
  <div class="group relative select-none cursor-pointer gh-surface gh-surface-hover p-0 overflow-hidden bg-black" :class="wrapperClass" @click="handleAdClick">
    <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none z-10"></div>
    
    <!-- Leaderboard -->
    <div v-if="size === 'leaderboard'" class="relative flex h-full w-full items-center">
      <div class="h-full w-12 shrink-0 border-r-2 border-retro-black dark:border-current opacity-40" :class="currentAd.color"></div>
      <div class="flex-1 px-4">
        <p class="font-pixel text-[10px] font-bold uppercase tracking-widest opacity-60 text-retro-white">{{ currentAd.brand }}</p>
        <p class="font-display text-sm font-black text-neon-yellow dark:text-retro-white truncate">{{ currentAd.headline }}</p>
        <p class="font-sans text-[10px] uppercase font-bold text-retro-white/70 truncate">{{ currentAd.tagline }}</p>
      </div>
      <div class="px-4">
        <button class="gh-surface px-4 py-1.5 font-pixel text-sm font-bold uppercase transition-all bg-white text-black hover:bg-neon-yellow active:translate-y-0.5">
          {{ currentAd.cta }}
        </button>
      </div>
      <span class="absolute right-0 top-0 bg-retro-black text-white px-2 py-0.5 font-pixel text-[8px] uppercase tracking-tighter">PROM_01</span>
    </div>

    <!-- Rectangle -->
    <div v-else-if="size === 'rectangle'" class="relative flex h-full w-full flex-col">
      <div class="h-24 shrink-0 border-b-2 border-retro-black dark:border-current overflow-hidden flex items-center justify-center bg-retro-black" :class="currentAd.color">
         <span class="font-pixel text-6xl opacity-30 select-none">DATA_PULSE</span>
      </div>
      <div class="flex flex-1 flex-col gap-2 p-4">
        <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-neon-cyan">{{ currentAd.brand }}</p>
        <p class="font-display text-lg font-black text-retro-white leading-tight">{{ currentAd.headline }}</p>
        <p class="font-sans text-[10px] font-bold text-retro-white/60 mb-2 uppercase">{{ currentAd.tagline }}</p>
        <button class="mt-auto w-full gh-surface py-2 font-pixel text-lg font-bold uppercase bg-white text-black hover:bg-neon-yellow">
          {{ currentAd.cta }}
        </button>
      </div>
      <span class="absolute right-0 top-0 bg-retro-black text-white px-2 py-0.5 font-pixel text-[8px] uppercase tracking-tighter">PROM_04</span>
    </div>

    <!-- Skyscraper -->
    <div v-else-if="size === 'skyscraper'" class="relative flex h-full w-full flex-col">
      <div class="p-3 border-b-2 border-retro-black dark:border-current bg-retro-black" :class="currentAd.color">
        <p class="font-pixel text-xs font-bold uppercase tracking-widest text-white">{{ currentAd.brand }}</p>
      </div>
      <div class="flex-1 flex flex-col p-3">
        <div class="h-48 shrink-0 mb-4 border-2 border-retro-white/20 bg-retro-white/5 flex items-center justify-center relative">
          <div class="absolute inset-0 flex items-center justify-center font-pixel text-4xl opacity-10 rotate-90">SKYSCRAPER_MOD</div>
          <span class="font-pixel text-2xl text-neon-pink">>> {{ currentAd.slug }}</span>
        </div>
        <p class="font-display text-sm font-black text-retro-white underline decoration-neon-cyan underline-offset-4 mb-2">{{ currentAd.headline }}</p>
        <p class="font-sans text-[10px] font-bold text-retro-white/50 uppercase leading-relaxed">{{ currentAd.tagline }}</p>
        <button class="mt-auto w-full gh-surface py-3 font-pixel text-xl font-bold uppercase bg-white text-black hover:bg-neon-yellow">
          {{ currentAd.cta }}
        </button>
      </div>
      <span class="absolute right-0 top-0 bg-retro-black text-white px-2 py-0.5 font-pixel text-[8px] uppercase tracking-tighter">PROM_09</span>
    </div>

    <!-- Mobile / Small -->
    <div v-else class="relative flex h-16 w-full items-center gap-3 px-3">
      <div class="size-10 shrink-0 border-2 border-retro-white/20 bg-retro-white/5 flex items-center justify-center font-pixel text-xl text-neon-cyan">
        !
      </div>
      <div class="min-w-0 flex-1">
        <p class="truncate font-pixel text-[10px] font-bold text-neon-yellow">{{ currentAd.brand }}</p>
        <p class="truncate font-sans text-[10px] font-bold text-retro-white/50 uppercase">{{ currentAd.headline }}</p>
      </div>
      <button class="gh-surface px-3 py-1 font-pixel text-xs font-bold uppercase bg-white text-black">
        {{ currentAd.cta }}
      </button>
      <span class="absolute right-0 top-0 bg-retro-black text-white px-1.5 py-0.5 font-pixel text-[7px] uppercase">ADV_MOBILE</span>
    </div>
  </div>
</template>
