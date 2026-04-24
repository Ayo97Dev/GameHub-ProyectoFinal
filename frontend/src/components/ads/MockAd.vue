<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'

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
    brand: 'SYSTEM_STORE',
    headline: 'ARMERÍA_DIGITAL',
    tagline: 'CARGAS DE HABILIDAD Y MEJORAS CRÍTICAS DISPONIBLES PARA TU PERFIL.',
    cta: 'EQUIPARSE',
    color: 'border-neon-yellow bg-neon-yellow/10 text-neon-yellow',
    accent: 'bg-neon-yellow',
    icon: 'lucide:shopping-bag',
    routeName: 'store',
  },
  {
    brand: 'DEFENSE_PROTOCOL',
    headline: 'CORE_UNDER_ATTACK',
    tagline: 'DESPLIEGA DEFENSAS PARA PROTEGER EL REACTOR DE LA RED.',
    cta: 'DESPLEGAR',
    color: 'border-neon-cyan bg-neon-cyan/10 text-neon-cyan',
    accent: 'bg-neon-cyan',
    icon: 'lucide:shield-check',
    slug: 'tower-defense',
  },
  {
    brand: 'DUNGEON_OS',
    headline: 'SYSTEM_BREACH',
    tagline: 'SOBREVIVE A LAS PROFUNDIDADES DEL KERNEL EN ESTE RPG TÉCNICO.',
    cta: 'EXPLORAR',
    color: 'border-neon-pink bg-neon-pink/10 text-neon-pink',
    accent: 'bg-neon-pink',
    icon: 'lucide:swords',
    slug: 'rpg',
  },
  {
    brand: 'REACTOR_PULSE',
    headline: 'HACK_THE_LIMIT',
    tagline: 'GENERA ENERGÍA MASIVA MEDIANTE CLICS DE ALTA FRECUENCIA.',
    cta: 'CONECTAR',
    color: 'border-neon-yellow bg-neon-yellow/10 text-neon-yellow',
    accent: 'bg-neon-yellow',
    icon: 'lucide:zap',
    slug: 'clicker',
  },
  {
    brand: 'QUIZ_MASTER.SYS',
    headline: 'DATA_MINING',
    tagline: 'PRUEBA TU MEMORIA EN EL DUELO DE DATOS MÁS AGRESIVO.',
    cta: 'INICIAR',
    color: 'border-neon-blue bg-neon-blue/10 text-neon-blue',
    accent: 'bg-neon-blue',
    icon: 'lucide:database',
    slug: 'quiz',
  },
]

const currentIndex = ref(Math.floor(Math.random() * ADS.length))
const currentAd = computed(() => ADS[currentIndex.value])

let rotationTimer = null

function nextAd() {
  currentIndex.value = (currentIndex.value + 1) % ADS.length
}

function handleAdClick() {
  if (currentAd.value.routeName) {
    router.push({ name: currentAd.value.routeName })
  } else if (currentAd.value.slug) {
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
  if (props.size === 'leaderboard') return 'w-full max-w-[728px] h-[90px]'
  if (props.size === 'rectangle') return 'w-full max-w-[300px] h-[250px]'
  if (props.size === 'skyscraper') return 'w-full max-w-[160px] h-[600px]'
  return 'w-full max-w-[320px] h-fit'
})
</script>

<template>
  <div 
    class="group relative select-none cursor-pointer border-4 border-retro-black shadow-[4px_4px_0px_#000] overflow-hidden bg-black transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_#000]" 
    :class="[wrapperClass, currentAd.color]" 
    @click="handleAdClick"
  >
    <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none z-10"></div>
    
    <!-- Leaderboard -->
    <div v-if="size === 'leaderboard'" class="relative flex h-full w-full items-center p-2">
      <div class="h-full w-14 shrink-0 flex items-center justify-center bg-black/40 border-2 border-current">
        <Icon :icon="currentAd.icon" class="text-3xl" />
      </div>
      <div class="flex-1 px-4 min-w-0">
        <p class="font-pixel text-[9px] font-bold uppercase tracking-[0.3em] opacity-60 mb-1">{{ currentAd.brand }}</p>
        <h4 class="font-display text-xl font-black uppercase tracking-tighter truncate leading-none mb-1">{{ currentAd.headline }}</h4>
        <p class="font-sans text-[10px] uppercase font-bold text-white/50 truncate">{{ currentAd.tagline }}</p>
      </div>
      <div class="px-2">
        <button class="bg-current text-black px-6 py-2 font-display text-xs font-black uppercase tracking-widest transition-all hover:scale-105 active:scale-95 shadow-[3px_3px_0px_#000]">
          {{ currentAd.cta }}
        </button>
      </div>
      <div class="absolute right-0 top-0 bg-current text-black px-1.5 py-0.5 font-pixel text-[8px] font-black uppercase">AD_01</div>
    </div>

    <!-- Rectangle -->
    <div v-else-if="size === 'rectangle'" class="relative flex h-full w-full flex-col">
      <div class="h-1/3 shrink-0 flex items-center justify-center bg-black/60 relative border-b-4 border-current overflow-hidden">
         <div class="absolute inset-0 opacity-10 flex flex-wrap gap-2 p-2">
           <div v-for="i in 20" :key="i" class="size-2 bg-current"></div>
         </div>
         <Icon :icon="currentAd.icon" class="text-6xl relative z-10" />
      </div>
      <div class="flex flex-1 flex-col p-5 bg-black/20">
        <p class="font-pixel text-[10px] font-bold uppercase tracking-[0.4em] mb-1 opacity-60">{{ currentAd.brand }}</p>
        <h4 class="font-display text-2xl font-black uppercase leading-none mb-2 tracking-tight">{{ currentAd.headline }}</h4>
        <p class="font-sans text-[10px] font-bold text-white/40 mb-4 uppercase leading-relaxed">{{ currentAd.tagline }}</p>
        <button class="mt-auto w-full bg-current text-black py-3 font-display text-sm font-black uppercase tracking-widest shadow-[4px_4px_0px_#000] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_#000] transition-all">
          {{ currentAd.cta }}
        </button>
      </div>
      <div class="absolute right-0 top-0 bg-current text-black px-2 py-0.5 font-pixel text-[9px] font-black">SPONSORED</div>
    </div>

    <!-- Skyscraper (160x600) -->
    <div v-else-if="size === 'skyscraper'" class="relative flex h-full w-full flex-col bg-black">
      <!-- HEADER MODULE -->
      <div class="p-3 bg-black/80 border-b-4 border-current flex flex-col items-center gap-2">
        <div class="w-full flex justify-between items-center px-1">
          <span class="font-pixel text-[8px] opacity-40">REV_4.2.0</span>
          <div class="flex gap-0.5">
            <div v-for="i in 3" :key="i" class="size-1 bg-current animate-pulse" :style="{ animationDelay: `${i * 0.2}s` }"></div>
          </div>
        </div>
        <div class="size-16 border-2 border-current flex items-center justify-center bg-current/5 shadow-[0_0_15px_rgba(current,0.1)]">
          <Icon :icon="currentAd.icon" class="text-4xl" />
        </div>
        <p class="font-pixel text-[9px] font-black uppercase tracking-[0.3em] text-center mt-1">{{ currentAd.brand }}</p>
      </div>

      <!-- VISUAL DATA MODULE -->
      <div class="relative flex-1 flex flex-col border-b-4 border-current overflow-hidden group/module">
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 8px 8px;"></div>
        
        <!-- Rotated Side Label -->
        <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 -rotate-90">
          <span class="font-pixel text-[10px] whitespace-nowrap opacity-20 tracking-[0.5em] uppercase">SYSTEM_AD_NETWORK</span>
        </div>

        <div class="flex-1 flex items-center justify-center relative p-4">
           <!-- Decorative brackets -->
           <div class="absolute top-4 left-4 size-4 border-t-2 border-l-2 border-current opacity-30"></div>
           <div class="absolute bottom-4 right-4 size-4 border-b-2 border-r-2 border-current opacity-30"></div>
           
           <div class="text-center z-10">
              <Icon :icon="currentAd.icon" class="text-7xl opacity-10 absolute inset-0 m-auto animate-ping pointer-events-none" />
              <h4 class="font-display text-xl font-black uppercase tracking-tighter leading-tight mb-2 gh-title-glow">{{ currentAd.headline }}</h4>
              <div class="h-1 w-12 bg-current mx-auto mb-4"></div>
              <p class="font-sans text-[11px] font-bold text-white/40 uppercase leading-relaxed px-2">{{ currentAd.tagline }}</p>
           </div>
        </div>

        <!-- Decorative Progress Bar -->
        <div class="p-3 bg-current/5 border-t-2 border-current/20">
          <div class="flex justify-between font-pixel text-[8px] mb-1 opacity-50">
            <span>SYNC_STATUS</span>
            <span>98%</span>
          </div>
          <div class="h-1 w-full bg-black/40 border border-current/20">
            <div class="h-full bg-current w-[98%]"></div>
          </div>
        </div>
      </div>

      <!-- FOOTER ACTION MODULE -->
      <div class="p-4 bg-black/90 flex flex-col gap-4">
        <div class="flex flex-col gap-1">
           <div class="flex items-center gap-2">
             <div class="size-2 bg-neon-green"></div>
             <span class="font-pixel text-[9px] text-neon-green uppercase">SIGNAL_STABLE</span>
           </div>
           <p class="font-pixel text-[8px] text-white/20 uppercase tracking-widest leading-none">ID_HASH: {{ Math.random().toString(16).slice(2, 10).toUpperCase() }}</p>
        </div>
        
        <button class="w-full bg-current text-black py-5 font-display text-base font-black uppercase tracking-[0.2em] shadow-[4px_4px_0px_#000] transition-all hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none border-2 border-black">
          {{ currentAd.cta }}
        </button>
      </div>

      <!-- Technical Sticker -->
      <div class="absolute right-0 top-0 bg-current text-black px-2 py-0.5 font-pixel text-[10px] font-black z-20 shadow-[-2px_2px_0px_#000]">
        PROM_MOD_V8
      </div>
    </div>

    <!-- Mobile / Small -->
    <div v-else class="relative flex h-20 w-full items-center gap-4 px-4 bg-black/40">
      <div class="size-12 shrink-0 border-4 border-current flex items-center justify-center text-2xl bg-black shadow-[3px_3px_0px_#000]">
        <Icon :icon="currentAd.icon" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="truncate font-pixel text-[9px] font-bold uppercase tracking-widest opacity-60">{{ currentAd.brand }}</p>
        <p class="truncate font-display text-lg font-black uppercase tracking-tighter">{{ currentAd.headline }}</p>
      </div>
      <button class="bg-current text-black px-4 py-2 font-display text-[10px] font-black uppercase shadow-[3px_3px_0px_#000]">
        {{ currentAd.cta }}
      </button>
      <div class="absolute right-0 top-0 bg-current text-black px-1 py-0.5 font-pixel text-[7px] font-black">MOB_AD</div>
    </div>
  </div>
</template>

<style scoped>
.animate-spin-slow {
  animation: spin 6s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>

