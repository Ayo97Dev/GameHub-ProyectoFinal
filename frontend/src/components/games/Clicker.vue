<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useClickerStore } from '../../stores/games/clicker'

const clicker = useClickerStore()

const UPGRADES = [
  // ── Tier 1: Básico ──
  { id: 1,  name: 'Autoclicker',      description: '+0.1 DPS',         icon: '🤖', tier: 1, type: 'dps'   },
  { id: 2,  name: 'Dedos Rápidos',    description: '+1 clic/click',    icon: '✋', tier: 1, type: 'click' },
  { id: 3,  name: 'Bot Clicker',      description: '+2 DPS',           icon: '🦾', tier: 1, type: 'dps'   },
  { id: 4,  name: 'Manos Ágiles',    description: '+4 clic/click',    icon: '👌', tier: 1, type: 'click' },
  // ── Tier 2: Avanzado ──
  { id: 5,  name: 'Motor Turbo',      description: '+20 DPS',          icon: '🚀', tier: 2, type: 'dps'   },
  { id: 6,  name: 'Guante de Poder',  description: '+25 clic/click',   icon: '💪', tier: 2, type: 'click' },
  { id: 7,  name: 'Red Neuronal',     description: '+200 DPS',         icon: '🧠', tier: 2, type: 'dps'   },
  { id: 8,  name: 'Reflejos Turbo',   description: '+100 clic/click',  icon: '⚡', tier: 2, type: 'click' },
  // ── Tier 3: Legendario ──
  { id: 9,  name: 'Propulsor Cuántico', description: '+800 DPS',         icon: '🌀', tier: 3, type: 'dps'   },
  { id: 10, name: 'Pulso Oscuro',     description: '+600 clic/click',  icon: '🌑', tier: 3, type: 'click' },
  { id: 11, name: 'Núcleo Universal', description: '+6.000 DPS',       icon: '🌟', tier: 3, type: 'dps'   },
  { id: 12, name: 'Mano Cósmica',    description: '+2.500 clic/click',icon: '✨', tier: 3, type: 'click' },
]

const TIERS = [
  { id: 1, label: 'Básico',     color: 'amber',  barColor: 'bg-amber-400' },
  { id: 2, label: 'Avanzado',   color: 'cyan',   barColor: 'bg-cyan-400'  },
  { id: 3, label: 'Legendario', color: 'violet', barColor: 'bg-violet-400' },
]

const TIER_STYLES = {
  amber: {
    card:      'border-amber-300/50 dark:border-amber-700/40',
    header:    'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    affordable:'border-amber-300 bg-amber-50/80 hover:bg-amber-100 hover:shadow-amber-200/50 dark:border-amber-700/50 dark:bg-amber-900/20 dark:hover:bg-amber-900/40',
    locked:    'border-slate-200 bg-slate-50/60 dark:border-slate-700/50 dark:bg-slate-800/30',
    cost:      'text-amber-600 dark:text-amber-400',
    badge:     'bg-amber-500/20 text-amber-700 dark:bg-amber-500/30 dark:text-amber-400',
    shimmer:   'shimmer',
  },
  cyan: {
    card:      'border-cyan-300/50 dark:border-cyan-700/40',
    header:    'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
    affordable:'border-cyan-300 bg-cyan-50/80 hover:bg-cyan-100 hover:shadow-cyan-200/50 dark:border-cyan-700/50 dark:bg-cyan-900/20 dark:hover:bg-cyan-900/40',
    locked:    'border-slate-200 bg-slate-50/60 dark:border-slate-700/50 dark:bg-slate-800/30',
    cost:      'text-cyan-600 dark:text-cyan-400',
    badge:     'bg-cyan-500/20 text-cyan-700 dark:bg-cyan-500/30 dark:text-cyan-400',
    shimmer:   'shimmer-cyan',
  },
  violet: {
    card:      'border-violet-300/50 dark:border-violet-700/40',
    header:    'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
    affordable:'border-violet-300 bg-violet-50/80 hover:bg-violet-100 hover:shadow-violet-200/50 dark:border-violet-700/50 dark:bg-violet-900/20 dark:hover:bg-violet-900/40',
    locked:    'border-slate-200 bg-slate-50/60 dark:border-slate-700/50 dark:bg-slate-800/30',
    cost:      'text-violet-600 dark:text-violet-400',
    badge:     'bg-violet-500/20 text-violet-700 dark:bg-violet-500/30 dark:text-violet-400',
    shimmer:   'shimmer-violet',
  },
}

// Estados y lógica de juego (clicks, DPS, mejoras, combos, logros, etc.)
const isClicking      = ref(false)
const toastQueue      = ref([])   // { id, title, rarity }
const clickParticles  = ref([])   // { id, x, y, value, critical }
const combatEvents    = ref([])   // { id, text, tone }
const comboCount      = ref(0)
const lastClickAt     = ref(0)
const hitFlash        = ref(false)
const critPulse       = ref(false)
const sessionElapsedSeconds = ref(0)
let particleId        = 0
let combatEventId     = 0
let saveInterval      = null
let dpsInterval       = null
let comboInterval     = null
let sessionClockInterval = null
let toastTimers       = []


const RARITY_STYLES = {
  common:    'border-slate-400  bg-slate-800   text-slate-100',
  uncommon:  'border-green-400  bg-green-900   text-green-100',
  rare:      'border-blue-400   bg-blue-900    text-blue-100',
  epic:      'border-violet-400 bg-violet-900  text-violet-100',
  legendary: 'border-amber-400  bg-amber-900   text-amber-100',
}

const RARITY_LABEL = {
  common:    'Común',
  uncommon:  'Poco común',
  rare:      'Raro',
  epic:      'Épico',
  legendary: 'Legendario',
}

// Definición de los niveles de combo, sus multiplicadores y bonificaciones
const COMBO_TIERS = [
  { threshold: 50, label: 'Impulso', multiplier: 1.05, critBonus: 0.02 },
  { threshold: 100, label: 'Ráfaga', multiplier: 1.12, critBonus: 0.04 },
  { threshold: 200, label: 'Frenesí', multiplier: 1.22, critBonus: 0.06 },
  { threshold: 500, label: 'Sobrecarga', multiplier: 1.4, critBonus: 0.1 },
  { threshold: 1000, label: 'Omega', multiplier: 1.75, critBonus: 0.15 },
]

const currentComboTier = computed(() => {
  let tier = null
  for (const candidate of COMBO_TIERS) {
    if (comboCount.value >= candidate.threshold) {
      tier = candidate
    } else {
      break
    }
  }
  return tier
})

const nextComboTier = computed(() => COMBO_TIERS.find(tier => comboCount.value < tier.threshold) ?? null)

const comboProgress = computed(() => {
  if (!nextComboTier.value) return 1
  const currentThreshold = currentComboTier.value?.threshold ?? 0
  const span = nextComboTier.value.threshold - currentThreshold
  const progressInTier = comboCount.value - currentThreshold
  return Math.max(0, Math.min(progressInTier / span, 1))
})

const comboMultiplier = computed(() => currentComboTier.value?.multiplier ?? 1)
const critChance = computed(() => {
  const baseChance = 0.06
  const tierBonus = currentComboTier.value?.critBonus ?? 0
  return Math.min(baseChance + tierBonus, 0.32)
})
const comboLabel = computed(() => currentComboTier.value?.label ?? 'Calma')

// Cálculo de energía dinámica para efectos visuales basados en DPS y racha de combos
const dpsEnergy = computed(() => Math.min(clicker.dps / 15_000, 1))
const cadenceEnergy = computed(() => Math.min(comboCount.value / 1_000, 1))
const dynamicEnergy = computed(() => Math.min(0.2 + (dpsEnergy.value * 0.55) + (cadenceEnergy.value * 0.45), 1))

const impactStyleVars = computed(() => ({
  '--impact-wave-duration': `${(2.8 - dynamicEnergy.value * 1.6).toFixed(2)}s`,
  '--impact-wave-scale': `${(1.14 + dynamicEnergy.value * 0.28).toFixed(2)}`,
  '--impact-glow-strength': `${(0.22 + dynamicEnergy.value * 0.45).toFixed(2)}`,
  '--impact-grid-opacity': `${(0.25 + dynamicEnergy.value * 0.45).toFixed(2)}`,
  '--impact-core-breathe': `${(3.6 - dynamicEnergy.value * 2).toFixed(2)}s`,
  '--impact-side-pulse': `${(2.6 - dynamicEnergy.value * 1.6).toFixed(2)}s`,
  '--impact-spin-speed': `${(14 - dynamicEnergy.value * 9).toFixed(2)}s`,
}))

const baseNodeAngles = [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352]
const ambientNodes = computed(() => {
  const count = 4 + Math.round(dynamicEnergy.value * 8)
  const radius = 92 + dynamicEnergy.value * 22

  return baseNodeAngles.slice(0, count).map((angle, index) => {
    const radians = (angle * Math.PI) / 180
    return {
      id: index,
      style: {
        left: `calc(50% + ${(Math.cos(radians) * radius).toFixed(1)}px)`,
        top: `calc(50% + ${(Math.sin(radians) * radius).toFixed(1)}px)`,
        animationDelay: `${(index * 0.12).toFixed(2)}s`,
        animationDuration: `${(1.8 - dynamicEnergy.value * 0.9 + (index % 3) * 0.12).toFixed(2)}s`,
      },
    }
  })
})

// Función para crear un log del combate
function pushCombatEvent(text, tone = 'neutral') {
  const id = ++combatEventId
  combatEvents.value.unshift({ id, text, tone })
  combatEvents.value = combatEvents.value.slice(0, 4)
  setTimeout(() => {
    combatEvents.value = combatEvents.value.filter(e => e.id !== id)
  }, 2800)
}

// Mostrar toast cuando llegan logros nuevos
watch(() => clicker.newAchievements.length, () => {
  while (clicker.newAchievements.length) {
    const achievement = clicker.newAchievements.shift()
    const id = Date.now() + Math.random()
    toastQueue.value.push({ id, ...achievement })
    const t = setTimeout(() => dismissToast(id), 10_000)
    toastTimers.push(t)
  }
})

function dismissToast(id) {
  toastQueue.value = toastQueue.value.filter(a => a.id !== id)
}

function saveOnHide() {
  if (document.visibilityState === 'hidden') clicker.saveGame()
}
function saveOnUnload() {
  clicker.saveGame()
}


onMounted(async () => {
  // Guarda el tiempo de juego
  await clicker.initializeGame(true)
  sessionElapsedSeconds.value = clicker.getSessionDurationSeconds()

  sessionClockInterval = setInterval(() => {
    sessionElapsedSeconds.value = clicker.getSessionDurationSeconds()
  }, 1000)

  // Auto-guardado cada 30 s
  saveInterval = setInterval(() => clicker.saveGame(), 30_000)

  // DPS cada 100 ms con 1/10 del valor → contador sube de forma continua y fluida
  dpsInterval = setInterval(() => {
    if (clicker.dps > 0) {
      clicker.gameState.balance += clicker.dps / 10
    }
  }, 100)

  comboInterval = setInterval(() => {
    if (!lastClickAt.value || comboCount.value === 0) return
    if (Date.now() - lastClickAt.value > 850) {
      comboCount.value = Math.max(comboCount.value - 1, 0)
    }
  }, 160)

  // Guardar al ocultar la pestaña y al cerrar la ventana
  document.addEventListener('visibilitychange', saveOnHide)
  window.addEventListener('beforeunload', saveOnUnload)
})

onUnmounted(() => {
  clearInterval(saveInterval)
  clearInterval(dpsInterval)
  clearInterval(comboInterval)
  clearInterval(sessionClockInterval)
  toastTimers.forEach(clearTimeout)
  document.removeEventListener('visibilitychange', saveOnHide)
  window.removeEventListener('beforeunload', saveOnUnload)
  clicker.saveGame()
})

// Feedback visual inmediato — sin esperar respuesta de red
function handleClick(event) {
  const now = Date.now()
  comboCount.value = now - lastClickAt.value <= 650 ? comboCount.value + 1 : 1
  lastClickAt.value = now

  isClicking.value = true
  hitFlash.value = true
  const isCritical = Math.random() < critChance.value
  const totalMultiplier = comboMultiplier.value * (isCritical ? 2 : 1)
  const gained = clicker.click(totalMultiplier)

  if (isCritical) {
    critPulse.value = true
    pushCombatEvent(`Crítico x2 · +${Math.round(gained)}`, 'critical')
    setTimeout(() => { critPulse.value = false }, 220)
  }

  if (nextComboTier.value && comboCount.value === nextComboTier.value.threshold) {
    pushCombatEvent(
      `${nextComboTier.value.label} desbloqueado · x${nextComboTier.value.multiplier.toFixed(2)}`,
      'combo'
    )
  }

  if (comboCount.value > 0 && comboCount.value % 50 === 0) {
    pushCombatEvent(`Racha x${comboCount.value} · x${comboMultiplier.value.toFixed(2)}`, 'combo')
  }

  // Partícula flotante "+N" en la posición del clic
  const rect = event.currentTarget.getBoundingClientRect()
  const id   = ++particleId
  const x    = rect.left + rect.width  / 2 + (Math.random() - 0.5) * 60
  const y    = rect.top  + rect.height / 3
  clickParticles.value.push({ id, x, y, value: Math.round(gained), critical: isCritical })
  setTimeout(() => {
    clickParticles.value = clickParticles.value.filter(p => p.id !== id)
  }, 900)

  setTimeout(() => (hitFlash.value = false), 130)
  setTimeout(() => (isClicking.value = false), 80)
}

function canAfford(upgradeId) {
  return clicker.balance >= clicker.upgradeCost(upgradeId)
}

function missingForUpgrade(upgradeId) {
  return Math.max(clicker.upgradeCost(upgradeId) - clicker.balance, 0)
}

function upgradeProgress(upgradeId) {
  const cost = clicker.upgradeCost(upgradeId)
  return Math.min(clicker.balance / cost, 1)
}

function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return Math.floor(n).toString()
}

function formatSessionDuration(seconds) {
  const total = Math.max(Math.floor(seconds), 0)
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const secs = total % 60

  if (hours > 0) {
    return `${hours}h ${minutes.toString().padStart(2, '0')}m ${secs.toString().padStart(2, '0')}s`
  }

  return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}
</script>

<template>
  <section class="relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-slate-300/70 dark:border-slate-700/70 bg-slate-100/85 dark:bg-slate-950/60 p-3 sm:p-4 transition-colors">
    <div class="pointer-events-none absolute inset-0 rounded-2xl bg-[radial-gradient(circle_at_12%_8%,rgba(34,211,238,.14),transparent_38%),radial-gradient(circle_at_88%_20%,rgba(244,114,182,.12),transparent_36%),linear-gradient(to_bottom,rgba(255,255,255,.45),rgba(241,245,249,.15))] dark:bg-[radial-gradient(circle_at_12%_8%,rgba(34,211,238,.12),transparent_38%),radial-gradient(circle_at_88%_20%,rgba(167,139,250,.14),transparent_36%),linear-gradient(to_bottom,rgba(15,23,42,.55),rgba(2,6,23,.25))]" />
    <div class="pointer-events-none absolute inset-0 rounded-2xl opacity-35 dark:opacity-45 hud-grid" />

    <!-- Cargando -->
    <div v-if="clicker.isLoading" class="relative z-10 flex min-h-60 items-center justify-center">
      <div class="flex flex-col items-center gap-3">
        <div class="size-8 rounded-full border-4 border-cyan-400/30 dark:border-cyan-500/35 border-t-cyan-500 dark:border-t-cyan-300 animate-spin" />
        <p class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Cargando partida…</p>
      </div>
    </div>

    <template v-else>
      <div class="order-2 lg:order-1 relative z-10 grid gap-3 lg:grid-cols-[1.4fr_minmax(0,1fr)]">
        <div class="rounded-2xl border border-slate-300/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/60 p-3 transition-colors">
          <div class="grid grid-cols-1 gap-2 text-center sm:grid-cols-3">
            <div class="rounded-xl border border-amber-300/50 dark:border-amber-700/40 bg-amber-50/90 dark:bg-amber-950/35 px-3 py-2 shadow-sm dark:shadow-[0_0_0_1px_rgba(245,158,11,.1)] transition-colors">
              <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-amber-600 dark:text-amber-400">Balance</p>
              <p class="text-xl font-black tabular-nums text-amber-700 dark:text-amber-300">{{ formatNumber(clicker.balance) }}</p>
            </div>
            <div class="rounded-xl border border-cyan-300/50 dark:border-cyan-700/40 bg-cyan-50/90 dark:bg-cyan-950/35 px-3 py-2 shadow-sm dark:shadow-[0_0_0_1px_rgba(34,211,238,.1)] transition-colors">
              <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-cyan-600 dark:text-cyan-400">DPS</p>
              <p class="text-xl font-black tabular-nums text-cyan-700 dark:text-cyan-300">{{ clicker.dps.toFixed(1) }}</p>
            </div>
            <div class="rounded-xl border border-violet-300/50 dark:border-violet-700/40 bg-violet-50/90 dark:bg-violet-950/35 px-3 py-2 shadow-sm dark:shadow-[0_0_0_1px_rgba(167,139,250,.1)] transition-colors">
              <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-violet-600 dark:text-violet-400">Prestigio</p>
              <p class="text-xl font-black tabular-nums text-violet-700 dark:text-violet-300">{{ clicker.prestigeLevel }}</p>
            </div>
          </div>

          <div class="mt-2 rounded-xl border border-slate-300/50 dark:border-slate-700/50 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2 text-center transition-colors">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Tiempo de sesión</p>
            <p class="text-lg font-black tabular-nums text-slate-700 dark:text-slate-200">{{ formatSessionDuration(sessionElapsedSeconds) }}</p>
          </div>

          <div class="mt-3 rounded-xl border border-cyan-300/50 dark:border-cyan-700/50 bg-cyan-50/80 dark:bg-cyan-950/25 p-2 transition-colors">
            <div class="mb-1 flex items-center justify-between">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-cyan-700 dark:text-cyan-300">Combo</p>
              <p class="text-xs font-black uppercase tracking-[0.12em] text-cyan-600 dark:text-cyan-300">{{ comboLabel }} · x{{ comboCount }}</p>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-cyan-200 dark:bg-cyan-900/60">
              <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-violet-500 transition-all duration-150" :style="{ width: (comboProgress * 100) + '%' }" />
            </div>
            <div class="mt-1 flex items-center justify-between text-[10px] font-semibold text-cyan-700 dark:text-cyan-300">
              <span>Bonus combo: x{{ comboMultiplier.toFixed(2) }}</span>
              <span>Crítico: {{ Math.round(critChance * 100) }}%</span>
            </div>
            <p class="mt-1 text-[10px] text-cyan-700 dark:text-cyan-300/90">
              Siguiente bonus: {{ nextComboTier ? `${nextComboTier.threshold} clicks` : 'MAX alcanzado' }}
            </p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-300/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/60 p-3 transition-colors">
          <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-violet-600 dark:text-violet-300">Motor de prestigio</p>
          <div class="mt-2">
            <div class="mb-0.5 flex justify-between text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400 dark:text-slate-500">
              <span>Progreso</span>
              <span>{{ formatNumber(clicker.balance) }} / {{ formatNumber(clicker.prestigeRequiredBalance) }}</span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
              <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-purple-400 transition-all duration-500" :style="{ width: Math.min(clicker.balance / clicker.prestigeRequiredBalance * 100, 100) + '%' }" />
            </div>
          </div>

          <button
            @click="clicker.prestige()"
            :disabled="clicker.balance < clicker.prestigeRequiredBalance || clicker.isPrestiging"
            class="mt-3 w-full overflow-hidden rounded-lg border px-4 py-2.5 text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed"
            :class="clicker.balance >= clicker.prestigeRequiredBalance
              ? 'border-violet-400 bg-gradient-to-r from-violet-500/10 via-purple-500/10 to-violet-500/10 text-violet-700 shadow-sm dark:border-violet-500/50 dark:bg-gradient-to-r dark:from-violet-500/15 dark:via-purple-500/15 dark:to-violet-500/15 dark:text-violet-300 dark:shadow-[0_0_0_1px_rgba(139,92,246,.15)] hover:from-violet-500/20 hover:to-violet-500/20 prestige-ready'
              : 'border-violet-300/40 bg-violet-50 text-violet-600 dark:border-violet-700/30 dark:bg-violet-900/10 dark:text-violet-400'"
          >
            {{ clicker.isPrestiging ? 'Procesando prestigio…' : `✨ Prestigio · +${clicker.nextPrestigeClickIncrement.toFixed(2)} clic/click` }}
          </button>

          <p class="mt-1 text-center text-[11px] font-semibold text-violet-600 dark:text-violet-300">
            Siguiente bonificación DPS global: x{{ clicker.nextPrestigeDpsFactor.toFixed(2) }}
          </p>

          <p v-if="clicker.error" class="mt-2 text-center text-xs font-semibold text-rose-600 dark:text-rose-300">
            {{ clicker.error }}
          </p>

          <p v-if="clicker.lastSaved" class="mt-2 text-center text-xs text-slate-400 dark:text-slate-500">
            Guardado: {{ clicker.lastSaved.toLocaleTimeString() }}
          </p>
        </div>
      </div>

      <div class="order-1 lg:order-2 relative z-10 grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="rounded-2xl border border-slate-300/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/60 p-4 transition-colors">
          <p class="text-center text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Núcleo energético</p>
          <div class="relative mt-3 flex justify-center py-4 sm:py-6">
            <div
              class="impact-stage relative flex items-center justify-center"
              :class="[
                critPulse ? 'impact-stage--crit' : '',
                dynamicEnergy > 0.75 ? 'impact-stage--charged' : ''
              ]"
              :style="impactStyleVars"
            >
              <div class="impact-grid-bg absolute" />
              <div class="impact-wave impact-wave--a" />
              <div class="impact-wave impact-wave--b" />
              <div
                v-for="node in ambientNodes"
                :key="node.id"
                class="impact-node"
                :style="node.style"
              />
              <div v-if="hitFlash" class="impact-flash absolute" />

              <div class="impact-frame">
                <button
                  @click="handleClick"
                  :disabled="clicker.isLoading"
                  :class="[
                    'impact-core select-none',
                    isClicking ? 'scale-[0.96]' : 'scale-100',
                  ]"
                >
                  <span class="impact-content">
                    <span class="impact-mark">⬢</span>
                    <span class="impact-label">Golpe +{{ clicker.clickPower }}</span>
                  </span>
                </button>
              </div>

              <div class="impact-side impact-side--left" />
              <div class="impact-side impact-side--right" />
            </div>
          </div>

          <div class="rounded-xl border border-slate-300/70 dark:border-slate-700/60 bg-slate-50/85 dark:bg-slate-950/45 p-2 text-center transition-colors">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pulsa rápido para mantener la racha y llenar la barra de combo.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-300/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/60 p-3 transition-colors">
          <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Registro de combate</p>
          <div class="mt-2 flex min-h-20 flex-col gap-1.5">
            <p v-if="combatEvents.length === 0" class="text-xs text-slate-400 dark:text-slate-500">Golpea el núcleo para iniciar la racha.</p>
            <div
              v-for="evt in combatEvents"
              :key="evt.id"
              class="rounded-md border px-2 py-1 text-xs font-bold uppercase tracking-[0.1em]"
              :class="evt.tone === 'critical'
                ? 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-700/60 dark:bg-rose-950/30 dark:text-rose-300'
                : evt.tone === 'combo'
                  ? 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-700/60 dark:bg-violet-950/30 dark:text-violet-300'
                  : 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300'"
            >
              {{ evt.text }}
            </div>
          </div>
        </div>
      </div>

      <div class="order-3 relative z-10 space-y-2">
        <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Tienda de mejoras</h3>

        <div
          v-for="tier in TIERS"
          :key="tier.id"
          class="overflow-hidden rounded-xl border bg-white/85 dark:bg-slate-900/55"
          :class="TIER_STYLES[tier.color].card"
        >
          <!-- Cabecera del tier -->
          <div class="flex items-center justify-between px-3 py-1.5" :class="TIER_STYLES[tier.color].header">
            <span class="text-[10px] font-black uppercase tracking-[0.2em]">{{ tier.label }}</span>
            <span class="text-[10px] opacity-60">
              {{ UPGRADES.filter(u => u.tier === tier.id && clicker.upgrades[u.id]).length }}/{{ UPGRADES.filter(u => u.tier === tier.id).length }} activas
            </span>
          </div>

          <!-- Grid 2 columnas: DPS izq, Click der -->
          <div class="grid grid-cols-2 divide-x divide-slate-200/60 dark:divide-slate-700/50">
            <!-- Columna DPS -->
            <div class="flex flex-col divide-y divide-slate-100/80 dark:divide-slate-800/60">
              <div class="px-2 py-1 text-center">
                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">⚙ DPS</span>
              </div>
              <button
                v-for="upgrade in UPGRADES.filter(u => u.tier === tier.id && u.type === 'dps')"
                :key="upgrade.id"
                @click="clicker.buyUpgrade(upgrade.id)"
                :disabled="!canAfford(upgrade.id)"
                class="group relative flex items-center gap-2 px-2.5 py-2 text-left transition-all"
                :class="canAfford(upgrade.id)
                  ? TIER_STYLES[tier.color].affordable + ' hover:scale-[1.02] hover:shadow-sm cursor-pointer'
                  : TIER_STYLES[tier.color].locked + ' opacity-75 cursor-not-allowed'"
              >
                <div v-if="canAfford(upgrade.id)" :class="TIER_STYLES[tier.color].shimmer" class="pointer-events-none absolute inset-0" />
                <span class="text-base leading-none shrink-0">{{ upgrade.icon }}</span>
                <div class="relative min-w-0 flex-1">
                  <div class="flex items-baseline justify-between gap-1">
                    <p class="truncate text-[11px] font-bold text-slate-800 dark:text-white">{{ upgrade.name }}</p>
                    <span v-if="clicker.upgrades[upgrade.id]" class="shrink-0 rounded-full px-1 py-px text-[9px] font-bold" :class="TIER_STYLES[tier.color].badge">
                      ×{{ clicker.upgrades[upgrade.id] }}
                    </span>
                  </div>
                  <p class="text-[9px] leading-tight text-slate-500 dark:text-slate-300">{{ upgrade.description }}</p>
                  <p class="text-[10px]" :class="canAfford(upgrade.id) ? TIER_STYLES[tier.color].cost : 'text-slate-500 dark:text-slate-300'">
                    {{ formatNumber(clicker.upgradeCost(upgrade.id)) }}
                  </p>
                  <p v-if="!canAfford(upgrade.id)" class="text-[9px] font-semibold text-slate-600 dark:text-slate-300 lg:hidden">
                    Faltan {{ formatNumber(missingForUpgrade(upgrade.id)) }}
                  </p>
                  <p
                    v-if="!canAfford(upgrade.id)"
                    class="pointer-events-none absolute right-0 top-0 hidden rounded-md bg-slate-900/90 px-1.5 py-0.5 text-[9px] font-semibold text-slate-100 opacity-0 transition-opacity duration-200 lg:block group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-slate-100/90 dark:text-slate-900"
                  >
                    Faltan {{ formatNumber(missingForUpgrade(upgrade.id)) }}
                  </p>
                  <div class="mt-0.5 h-0.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div class="h-full rounded-full transition-all duration-300" :class="tier.barColor" :style="{ width: (upgradeProgress(upgrade.id) * 100) + '%' }" />
                  </div>
                </div>
              </button>
            </div>

            <!-- Columna Click -->
            <div class="flex flex-col divide-y divide-slate-100/80 dark:divide-slate-800/60">
              <div class="px-2 py-1 text-center">
                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">🖱 Click</span>
              </div>
              <button
                v-for="upgrade in UPGRADES.filter(u => u.tier === tier.id && u.type === 'click')"
                :key="upgrade.id"
                @click="clicker.buyUpgrade(upgrade.id)"
                :disabled="!canAfford(upgrade.id)"
                class="group relative flex items-center gap-2 px-2.5 py-2 text-left transition-all"
                :class="canAfford(upgrade.id)
                  ? TIER_STYLES[tier.color].affordable + ' hover:scale-[1.02] hover:shadow-sm cursor-pointer'
                  : TIER_STYLES[tier.color].locked + ' opacity-75 cursor-not-allowed'"
              >
                <div v-if="canAfford(upgrade.id)" :class="TIER_STYLES[tier.color].shimmer" class="pointer-events-none absolute inset-0" />
                <span class="text-base leading-none shrink-0">{{ upgrade.icon }}</span>
                <div class="relative min-w-0 flex-1">
                  <div class="flex items-baseline justify-between gap-1">
                    <p class="truncate text-[11px] font-bold text-slate-800 dark:text-white">{{ upgrade.name }}</p>
                    <span v-if="clicker.upgrades[upgrade.id]" class="shrink-0 rounded-full px-1 py-px text-[9px] font-bold" :class="TIER_STYLES[tier.color].badge">
                      ×{{ clicker.upgrades[upgrade.id] }}
                    </span>
                  </div>
                  <p class="text-[9px] leading-tight text-slate-500 dark:text-slate-300">{{ upgrade.description }}</p>
                  <p class="text-[10px]" :class="canAfford(upgrade.id) ? TIER_STYLES[tier.color].cost : 'text-slate-500 dark:text-slate-300'">
                    {{ formatNumber(clicker.upgradeCost(upgrade.id)) }}
                  </p>
                  <p v-if="!canAfford(upgrade.id)" class="text-[9px] font-semibold text-slate-600 dark:text-slate-300 lg:hidden">
                    Faltan {{ formatNumber(missingForUpgrade(upgrade.id)) }}
                  </p>
                  <p
                    v-if="!canAfford(upgrade.id)"
                    class="pointer-events-none absolute right-0 top-0 hidden rounded-md bg-slate-900/90 px-1.5 py-0.5 text-[9px] font-semibold text-slate-100 opacity-0 transition-opacity duration-200 lg:block group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-slate-100/90 dark:text-slate-900"
                  >
                    Faltan {{ formatNumber(missingForUpgrade(upgrade.id)) }}
                  </p>
                  <div class="mt-0.5 h-0.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div class="h-full rounded-full transition-all duration-300" :class="tier.barColor" :style="{ width: (upgradeProgress(upgrade.id) * 100) + '%' }" />
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>

  <!-- Partículas flotantes "+N" (fixed al viewport) -->
  <Teleport to="body">
    <div class="pointer-events-none fixed inset-0 z-[60] overflow-hidden">
      <div
        v-for="p in clickParticles"
        :key="p.id"
        class="float-particle absolute whitespace-nowrap text-sm font-bold"
        :class="p.critical ? 'text-rose-500 dark:text-rose-300' : 'text-amber-500 dark:text-yellow-300'"
        :style="{ left: p.x + 'px', top: p.y + 'px' }"
      >{{ p.critical ? `CRIT +${p.value}` : `+${p.value}` }}</div>
    </div>
  </Teleport>

  <!-- Toast de logros desbloqueados -->
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-72">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toastQueue"
          :key="toast.id"
          class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 shadow-lg backdrop-blur-sm"
          :class="RARITY_STYLES[toast.rarity] ?? RARITY_STYLES.common"
        >
          <span class="text-2xl leading-none mt-0.5">🏆</span>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-widest opacity-70">
              Logro desbloqueado · {{ RARITY_LABEL[toast.rarity] }}
            </p>
            <p class="font-semibold leading-tight">{{ toast.title }}</p>
            <p class="text-xs opacity-70 truncate">{{ toast.description }}</p>
          </div>
          <button
            @click="dismissToast(toast.id)"
            class="shrink-0 ml-1 opacity-60 hover:opacity-100 transition-opacity text-lg leading-none"
            aria-label="Cerrar"
          >✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.hud-grid {
  background-image:
    linear-gradient(rgba(148, 163, 184, 0.18) 1px, transparent 1px),
    linear-gradient(to right, rgba(148, 163, 184, 0.18) 1px, transparent 1px);
  background-size: 20px 20px;
}

.impact-stage {
  width: 17rem;
  height: 17rem;
}

.impact-stage--crit {
  animation: impact-crit 0.24s ease-out;
}

.impact-grid-bg {
  inset: 14%;
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background-image:
    linear-gradient(rgba(34, 211, 238, 0.15) 1px, transparent 1px),
    linear-gradient(to right, rgba(34, 211, 238, 0.15) 1px, transparent 1px);
  background-size: 14px 14px;
  opacity: var(--impact-grid-opacity, 0.5);
  transition: opacity 180ms ease;
}

.impact-wave {
  position: absolute;
  inset: 18%;
  border-radius: 9999px;
  border: 1px solid rgba(34, 211, 238, 0.22);
  pointer-events: none;
}

.impact-wave--a {
  animation: impact-wave var(--impact-wave-duration, 2.2s) ease-out infinite;
}

.impact-wave--b {
  animation: impact-wave var(--impact-wave-duration, 2.2s) ease-out infinite;
  animation-delay: 1.1s;
}

.impact-frame {
  position: relative;
  z-index: 20;
  pointer-events: none;
  padding: 4px;
  clip-path: polygon(25% 4%, 75% 4%, 96% 50%, 75% 96%, 25% 96%, 4% 50%);
  background: linear-gradient(145deg, rgba(34, 211, 238, 0.9), rgba(139, 92, 246, 0.9));
  box-shadow:
    0 0 0 1px rgba(34, 211, 238, 0.28),
    0 18px 34px rgba(14, 165, 233, 0.24);
  animation: impact-frame-spin var(--impact-spin-speed, 14s) linear infinite;
}

.impact-core {
  width: 9.4rem;
  height: 9.4rem;
  clip-path: polygon(25% 4%, 75% 4%, 96% 50%, 75% 96%, 25% 96%, 4% 50%);
  border: 1px solid rgba(224, 242, 254, 0.85);
  position: relative;
  color: rgb(255 255 255);
  background:
    radial-gradient(circle at 28% 25%, rgba(255,255,255,0.34), transparent 45%),
    linear-gradient(155deg, rgba(8, 145, 178, 0.95), rgba(91, 33, 182, 0.95));
  text-shadow: 0 1px 5px rgba(15, 23, 42, 0.65);
  box-shadow:
    0 0 0 1px rgba(255, 255, 255, 0.2),
    0 0 28px rgba(34, 211, 238, var(--impact-glow-strength, 0.25));
  transition: transform 120ms ease, filter 150ms ease;
  animation: impact-core-breathe var(--impact-core-breathe, 3s) ease-in-out infinite;
  cursor: pointer;
  overflow: hidden;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  pointer-events: auto;
}

.impact-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-inline: 0.35rem;
  gap: 0.25rem;
  animation: impact-counter-spin var(--impact-spin-speed, 14s) linear infinite;
  pointer-events: none;
}

.impact-core:hover {
  filter: brightness(1.08);
}

.impact-mark {
  font-size: 2rem;
  line-height: 1;
  filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.55));
  pointer-events: none;
}

.impact-label {
  max-width: 7.8rem;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-transform: uppercase;
  pointer-events: none;
}

.impact-side {
  position: absolute;
  z-index: 10;
  width: 34px;
  height: 6px;
  top: 50%;
  margin-top: -3px;
  border-radius: 9999px;
  background: linear-gradient(to right, rgba(34, 211, 238, 0.15), rgba(34, 211, 238, 0.8), rgba(34, 211, 238, 0.15));
  animation: impact-side-pulse var(--impact-side-pulse, 2.2s) ease-in-out infinite;
}

.impact-side--left {
  left: 14px;
}

.impact-side--right {
  right: 14px;
}

.impact-flash {
  z-index: 25;
  width: 9.6rem;
  height: 9.6rem;
  clip-path: polygon(25% 4%, 75% 4%, 96% 50%, 75% 96%, 25% 96%, 4% 50%);
  background: radial-gradient(circle, rgba(34, 211, 238, 0.38), rgba(34, 211, 238, 0));
  animation: impact-flash 0.16s ease-out forwards;
  pointer-events: none;
}

.impact-node {
  position: absolute;
  z-index: 12;
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background: radial-gradient(circle, rgba(34, 211, 238, 0.95), rgba(34, 211, 238, 0.15));
  transform: translate(-50%, -50%);
  pointer-events: none;
  animation-name: impact-node-float;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}

.impact-stage--charged .impact-grid-bg {
  box-shadow: inset 0 0 24px rgba(34, 211, 238, 0.2);
}

html.dark .impact-frame {
  box-shadow:
    0 0 0 1px rgba(167, 139, 250, 0.3),
    0 22px 40px rgba(76, 29, 149, 0.35);
}

@keyframes impact-wave {
  0% { transform: scale(0.84); opacity: 0.55; }
  100% { transform: scale(var(--impact-wave-scale, 1.18)); opacity: 0; }
}

@keyframes impact-frame-spin {
  to { transform: rotate(360deg); }
}

@keyframes impact-counter-spin {
  to { transform: rotate(-360deg); }
}

@keyframes impact-core-breathe {
  0%, 100% { filter: brightness(1) saturate(1); }
  50% { filter: brightness(1.12) saturate(1.18); }
}

@keyframes impact-side-pulse {
  0%, 100% { opacity: 0.45; transform: scaleX(0.9); }
  50% { opacity: 0.9; transform: scaleX(1.08); }
}

@keyframes impact-node-float {
  0%, 100% { opacity: 0.35; transform: translate(-50%, -50%) scale(0.8); }
  50% { opacity: 0.95; transform: translate(-50%, -50%) scale(1.2); }
}

@keyframes impact-crit {
  0% { transform: scale(1); }
  50% { transform: scale(1.045); }
  100% { transform: scale(1); }
}

@keyframes impact-flash {
  0% { opacity: 0.9; transform: scale(0.85); }
  100% { opacity: 0; transform: scale(1.16); }
}

/* ── Partículas flotantes "+N" ── */
.float-particle {
  animation: float-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  filter: drop-shadow(0 0 5px rgba(14, 165, 233, 0.45));
}
@keyframes float-up {
  0%   { opacity: 0.95; transform: translateY(0) scale(1.08); }
  100% { opacity: 0; transform: translateY(-62px) scale(0.86); }
}

/* ── Barrido shimmer en upgrades asequibles ── */
.shimmer {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(251, 191, 36, 0.14) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2.8s ease-in-out infinite;
}
.shimmer-cyan {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(34, 211, 238, 0.14) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2.8s ease-in-out infinite;
}
.shimmer-violet {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(167, 139, 250, 0.18) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2.8s ease-in-out infinite;
}
@keyframes shimmer-sweep {
  0%   { background-position: 200% center; }
  100% { background-position: -200% center; }
}

/* ── Pulso del botón prestige cuando está disponible ── */
.prestige-ready {
  animation: prestige-glow 2.4s ease-in-out infinite;
}
@keyframes prestige-glow {
  0%, 100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
  50% { box-shadow: 0 0 0 6px rgba(139, 92, 246, 0.15); }
}

/* ── Transiciones de toasts ── */
.toast-enter-active  { transition: all 0.3s ease-out; }
.toast-leave-active  { transition: all 0.4s ease-in;  }
.toast-enter-from    { opacity: 0; transform: translateY(1rem); }
.toast-leave-to      { opacity: 0; transform: translateX(100%); }
</style>

