<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
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

const isClicking      = ref(false)
const toastQueue      = ref([])   // { id, title, rarity }
const clickParticles  = ref([])   // { id, x, y, value }
let particleId        = 0
let saveInterval      = null
let dpsInterval       = null
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
  await clicker.initializeGame(true)

  // Auto-guardado cada 30 s
  saveInterval = setInterval(() => clicker.saveGame(), 30_000)

  // DPS cada 100 ms con 1/10 del valor → contador sube de forma continua y fluida
  dpsInterval = setInterval(() => {
    if (clicker.dps > 0) {
      clicker.gameState.balance += clicker.dps / 10
    }
  }, 100)

  // Guardar al ocultar la pestaña y al cerrar la ventana
  document.addEventListener('visibilitychange', saveOnHide)
  window.addEventListener('beforeunload', saveOnUnload)
})

onUnmounted(() => {
  clearInterval(saveInterval)
  clearInterval(dpsInterval)
  toastTimers.forEach(clearTimeout)
  document.removeEventListener('visibilitychange', saveOnHide)
  window.removeEventListener('beforeunload', saveOnUnload)
  clicker.saveGame()
})

// Feedback visual inmediato — sin esperar respuesta de red
function handleClick(event) {
  isClicking.value = true
  clicker.click()

  // Partícula flotante "+N" en la posición del clic
  const rect = event.currentTarget.getBoundingClientRect()
  const id   = ++particleId
  const x    = rect.left + rect.width  / 2 + (Math.random() - 0.5) * 60
  const y    = rect.top  + rect.height / 3
  clickParticles.value.push({ id, x, y, value: clicker.clickPower })
  setTimeout(() => {
    clickParticles.value = clickParticles.value.filter(p => p.id !== id)
  }, 900)

  setTimeout(() => (isClicking.value = false), 80)
}

function canAfford(upgradeId) {
  return clicker.balance >= clicker.upgradeCost(upgradeId)
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
</script>

<template>
  <section class="relative flex flex-col gap-4 rounded-xl p-2 overflow-hidden">
    <!-- Fondo degradado ambiental -->
    <div class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-br from-amber-500/5 via-transparent to-violet-500/5 dark:from-amber-500/10 dark:to-violet-500/10" />

    <!-- Cargando -->
    <div v-if="clicker.isLoading" class="flex min-h-60 items-center justify-center">
      <div class="flex flex-col items-center gap-3">
        <div class="size-8 rounded-full border-4 border-amber-400/30 border-t-amber-400 animate-spin" />
        <p class="text-sm text-slate-500 dark:text-slate-400">Cargando partida…</p>
      </div>
    </div>

    <template v-else>
      <!-- Header stats -->
      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="relative overflow-hidden rounded-lg border border-amber-300/40 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-500/20 px-3 py-2">
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-amber-300/15 to-transparent" />
          <p class="text-[11px] uppercase tracking-wide text-amber-600 dark:text-amber-400">Balance</p>
          <p class="text-xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ formatNumber(clicker.balance) }}</p>
        </div>
        <div class="relative overflow-hidden rounded-lg border border-cyan-300/40 bg-cyan-50 dark:bg-cyan-950/30 dark:border-cyan-500/20 px-3 py-2">
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-300/15 to-transparent" />
          <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-400">DPS</p>
          <p class="text-xl font-bold tabular-nums text-cyan-700 dark:text-cyan-300">{{ clicker.dps.toFixed(1) }}</p>
        </div>
        <div class="relative overflow-hidden rounded-lg border border-violet-300/40 bg-violet-50 dark:bg-violet-950/30 dark:border-violet-500/20 px-3 py-2">
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-violet-300/15 to-transparent" />
          <p class="text-[11px] uppercase tracking-wide text-violet-600 dark:text-violet-400">Prestige</p>
          <p class="text-xl font-bold tabular-nums text-violet-700 dark:text-violet-300">{{ clicker.prestigeLevel }}</p>
        </div>
      </div>

      <!-- Botón principal -->
      <div class="relative flex justify-center py-6">
        <!-- Rings de pulso ambiental (activos cuando hay DPS) -->
        <template v-if="clicker.dps > 0">
          <div class="pulse-ring absolute size-52 rounded-full border border-amber-400/25" style="animation-delay: 0s" />
          <div class="pulse-ring absolute size-60 rounded-full border border-amber-300/15" style="animation-delay: 0.8s" />
          <div class="pulse-ring absolute size-64 rounded-full border border-amber-200/10" style="animation-delay: 1.6s" />
        </template>

        <!-- Wrapper animado con borde arcoíris giratorio (conic-gradient + @property) -->
        <div class="spin-border w-fit h-fit rounded-full p-[3px] drop-shadow-[0_0_24px_rgba(251,191,36,0.55)]">
          <button
            @click="handleClick"
            :disabled="clicker.isLoading"
            :class="[
              'relative size-36 rounded-full select-none transition-all duration-75',
              'bg-gradient-to-br from-yellow-300 via-amber-400 to-orange-500',
              'shadow-[0_0_30px_rgba(251,191,36,0.4)]',
              'hover:shadow-[0_0_50px_rgba(251,191,36,0.7)] hover:scale-105',
              isClicking ? 'scale-90 shadow-[0_0_15px_rgba(251,191,36,0.3)]' : 'scale-100',
            ]"
          >
            <!-- Sheen interior -->
            <div class="absolute inset-0 rounded-full bg-gradient-to-t from-orange-600/30 to-yellow-200/40 pointer-events-none" />
            <!-- Destello especular -->
            <div class="absolute top-2 left-5 h-5 w-8 rounded-full bg-white/30 blur-sm -rotate-12 pointer-events-none" />
            <!-- Contenido -->
            <div class="relative flex flex-col items-center justify-center gap-1">
              <span class="text-4xl leading-none select-none" style="filter: drop-shadow(0 2px 6px rgba(0,0,0,0.35))">🪙</span>
              <span class="text-xs font-bold text-white drop-shadow">+{{ clicker.clickPower }}</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Upgrades: 3 cards compactas por tier -->
      <div class="space-y-2">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Mejoras</h3>

        <div
          v-for="tier in TIERS"
          :key="tier.id"
          class="overflow-hidden rounded-xl border"
          :class="TIER_STYLES[tier.color].card"
        >
          <!-- Cabecera del tier -->
          <div class="flex items-center justify-between px-3 py-1.5" :class="TIER_STYLES[tier.color].header">
            <span class="text-[11px] font-bold uppercase tracking-widest">{{ tier.label }}</span>
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
                  : TIER_STYLES[tier.color].locked + ' opacity-50 cursor-not-allowed'"
              >
                <div v-if="canAfford(upgrade.id)" :class="TIER_STYLES[tier.color].shimmer" class="pointer-events-none absolute inset-0" />
                <span class="text-base leading-none shrink-0">{{ upgrade.icon }}</span>
                <div class="relative min-w-0 flex-1">
                  <div class="flex items-baseline justify-between gap-1">
                    <p class="truncate text-[11px] font-semibold text-slate-800 dark:text-white">{{ upgrade.name }}</p>
                    <span v-if="clicker.upgrades[upgrade.id]" class="shrink-0 rounded-full px-1 py-px text-[9px] font-bold" :class="TIER_STYLES[tier.color].badge">
                      ×{{ clicker.upgrades[upgrade.id] }}
                    </span>
                  </div>
                  <p class="text-[9px] leading-tight text-slate-400 dark:text-slate-500">{{ upgrade.description }}</p>
                  <p class="text-[10px]" :class="canAfford(upgrade.id) ? TIER_STYLES[tier.color].cost : 'text-slate-400 dark:text-slate-500'">
                    {{ formatNumber(clicker.upgradeCost(upgrade.id)) }}
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
                  : TIER_STYLES[tier.color].locked + ' opacity-50 cursor-not-allowed'"
              >
                <div v-if="canAfford(upgrade.id)" :class="TIER_STYLES[tier.color].shimmer" class="pointer-events-none absolute inset-0" />
                <span class="text-base leading-none shrink-0">{{ upgrade.icon }}</span>
                <div class="relative min-w-0 flex-1">
                  <div class="flex items-baseline justify-between gap-1">
                    <p class="truncate text-[11px] font-semibold text-slate-800 dark:text-white">{{ upgrade.name }}</p>
                    <span v-if="clicker.upgrades[upgrade.id]" class="shrink-0 rounded-full px-1 py-px text-[9px] font-bold" :class="TIER_STYLES[tier.color].badge">
                      ×{{ clicker.upgrades[upgrade.id] }}
                    </span>
                  </div>
                  <p class="text-[9px] leading-tight text-slate-400 dark:text-slate-500">{{ upgrade.description }}</p>
                  <p class="text-[10px]" :class="canAfford(upgrade.id) ? TIER_STYLES[tier.color].cost : 'text-slate-400 dark:text-slate-500'">
                    {{ formatNumber(clicker.upgradeCost(upgrade.id)) }}
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

      <!-- Prestige -->
      <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
        <!-- Barra de progreso hacia prestige -->
        <div class="mb-2">
          <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500 mb-0.5">
            <span>Progreso al Prestige</span>
            <span>{{ formatNumber(clicker.balance) }} / 1.000.000</span>
          </div>
          <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div
              class="h-full rounded-full bg-gradient-to-r from-violet-500 to-purple-400 transition-all duration-500"
              :style="{ width: Math.min(clicker.balance / 1_000_000 * 100, 100) + '%' }"
            />
          </div>
        </div>
        <button
          @click="clicker.prestige()"
          :disabled="clicker.balance < clicker.PRESTIGE_MIN_BALANCE"
          class="w-full overflow-hidden rounded-lg border px-4 py-2.5 text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed"
          :class="clicker.balance >= clicker.PRESTIGE_MIN_BALANCE
            ? 'border-violet-400 bg-gradient-to-r from-violet-500/10 via-purple-500/10 to-violet-500/10 text-violet-700 hover:from-violet-500/20 hover:to-violet-500/20 dark:border-violet-500/50 dark:text-violet-300 prestige-ready'
            : 'border-violet-300/40 bg-violet-50 text-violet-600 dark:border-violet-700/30 dark:bg-violet-900/10 dark:text-violet-400'"
        >
          ✨ Prestige · +0.5 clic/click permanente
          <span v-if="clicker.prestigeLevel > 0" class="ml-1 text-xs opacity-70">(Nivel {{ clicker.prestigeLevel + 1 }} → +{{ ((clicker.prestigeLevel + 1) * 5).toFixed(0) }}% DPS)</span>
        </button>
        <p v-if="clicker.lastSaved" class="mt-2 text-center text-xs text-slate-400 dark:text-slate-500">
          Guardado: {{ clicker.lastSaved.toLocaleTimeString() }}
        </p>
      </div>
    </template>
  </section>

  <!-- Partículas flotantes "+N" (fixed al viewport) -->
  <Teleport to="body">
    <div class="pointer-events-none fixed inset-0 z-[60] overflow-hidden">
      <div
        v-for="p in clickParticles"
        :key="p.id"
        class="float-particle absolute whitespace-nowrap text-sm font-bold text-amber-400 dark:text-yellow-300"
        :style="{ left: p.x + 'px', top: p.y + 'px' }"
      >+{{ p.value }}</div>
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
/* ── Partículas flotantes "+N" ── */
.float-particle {
  animation: float-up 0.9s ease-out forwards;
  filter: drop-shadow(0 0 5px rgba(251, 191, 36, 0.9));
}
@keyframes float-up {
  0%   { opacity: 1;   transform: translateY(0)     scale(1.4); }
  20%  { opacity: 1;   transform: translateY(-18px)  scale(1); }
  100% { opacity: 0;   transform: translateY(-75px)  scale(0.7); }
}

/* ── Anillo arcoíris giratorio (conic-gradient animado via @property) ── */
@property --spin-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}
.spin-border {
  background: conic-gradient(
    from var(--spin-angle),
    #fbbf24, #f97316, #ef4444, #a855f7, #22d3ee, #a3e635, #fbbf24
  );
  animation: spin-hue 3s linear infinite;
}
@keyframes spin-hue {
  to { --spin-angle: 360deg; }
}

/* ── Rings de pulso ambiental (DPS) ── */
.pulse-ring {
  animation: pulse-radiate 2.4s ease-out infinite;
}
@keyframes pulse-radiate {
  0%   { transform: scale(0.85); opacity: 0.7; }
  100% { transform: scale(1.25); opacity: 0; }
}

/* ── Barrido shimmer en upgrades asequibles ── */
.shimmer {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(251, 191, 36, 0.2) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2s ease-in-out infinite;
}
.shimmer-cyan {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(34, 211, 238, 0.2) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2s ease-in-out infinite;
}
.shimmer-violet {
  background: linear-gradient(
    105deg,
    transparent 40%,
    rgba(167, 139, 250, 0.25) 50%,
    transparent 60%
  );
  background-size: 200% 100%;
  animation: shimmer-sweep 2s ease-in-out infinite;
}
@keyframes shimmer-sweep {
  0%   { background-position: 200% center; }
  100% { background-position: -200% center; }
}

/* ── Pulso del botón prestige cuando está disponible ── */
.prestige-ready {
  animation: prestige-glow 1.8s ease-in-out infinite;
}
@keyframes prestige-glow {
  0%, 100% { box-shadow: 0 0 0 0   rgba(139, 92, 246, 0);    }
  50%       { box-shadow: 0 0 0 8px rgba(139, 92, 246, 0.25); }
}

/* ── Transiciones de toasts ── */
.toast-enter-active  { transition: all 0.3s ease-out; }
.toast-leave-active  { transition: all 0.4s ease-in;  }
.toast-enter-from    { opacity: 0; transform: translateY(1rem); }
.toast-leave-to      { opacity: 0; transform: translateX(100%); }
</style>

