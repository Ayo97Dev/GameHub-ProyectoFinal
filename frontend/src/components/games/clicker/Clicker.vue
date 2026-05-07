<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useClickerStore } from '../../../stores/games/clicker'
import { useInventoryStore } from '../../../stores/inventory'
import { Icon } from '@iconify/vue'
import BaseLoading from '../../ui/BaseLoading.vue'
import ClickerHeader from './ClickerHeader.vue'
import ClickerUpgrades from './ClickerUpgrades.vue'


const emit = defineEmits(['live-score'])

const clicker = useClickerStore()
const inventory = useInventoryStore()

const UPGRADES = [
  { id: 1,  name: 'CLICK_AUTOMÁTICO.BAT', description: '+0.1 DPS',         icon: 'game-icons:robot-grab', tier: 1, type: 'dps'   },
  { id: 2,  name: 'DEDOS_RÁPIDOS.EXE',    description: '+1 por clic',      icon: 'game-icons:palm', tier: 1, type: 'click' },
  { id: 3,  name: 'BOT_DE_CLICK_v1',      description: '+2 DPS',           icon: 'game-icons:processor', tier: 1, type: 'dps'   },
  { id: 4,  name: 'MANOS_ÁGILES.DLL',     description: '+4 por clic',      icon: 'game-icons:hand-wing', tier: 1, type: 'click' },
  { id: 5,  name: 'MOTOR_TURBO.SYS',      description: '+20 DPS',          icon: 'game-icons:rocket', tier: 2, type: 'dps'   },
  { id: 6,  name: 'GUANTE_DE_PODER.CFG',  description: '+25 por clic',     icon: 'game-icons:metal-hand', tier: 2, type: 'click' },
  { id: 7,  name: 'RED_NEURAL.BIN',       description: '+200 DPS',         icon: 'game-icons:brain', tier: 2, type: 'dps'   },
  { id: 8,  name: 'REFLEJO_TURBO.SO',     description: '+100 por clic',    icon: 'game-icons:heart-beats', tier: 2, type: 'click' },
  { id: 9,  name: 'IMPULSOR_CUÁNTICO',    description: '+800 DPS',         icon: 'game-icons:atomic-slashes', tier: 3, type: 'dps'   },
  { id: 10, name: 'PULSO_OSCURO',         description: '+600 por clic',    icon: 'game-icons:psychic-waves', tier: 3, type: 'click' },
  { id: 11, name: 'NÚCLEO_UNIVERSAL.INF', description: '+6.000 DPS',       icon: 'game-icons:atom-core', tier: 3, type: 'dps'   },
  { id: 12, name: 'MANO_CÓSMICA',         description: '+2.500 por clic',  icon: 'game-icons:sparkles', tier: 3, type: 'click' },
]

const TIERS = [
  { id: 1, label: 'T1 // BÁSICO',   color: 'neon-yellow', barColor: 'bg-neon-yellow' },
  { id: 2, label: 'T2 // AVANZADO', color: 'neon-cyan',   barColor: 'bg-neon-cyan'  },
  { id: 3, label: 'T3 // OMEGA',    color: 'neon-pink',   barColor: 'bg-neon-pink' },
]

const isClicking      = ref(false)
const toastQueue      = ref([])
const clickParticles  = ref([])
const combatEvents    = ref([])
const comboCount      = ref(0)
const lastClickAt     = ref(0)
const hitFlash        = ref(false)
const critPulse       = ref(false)
const sessionElapsedSeconds = ref(0)
const openTiers       = ref([1]) // Default to having T1 open


let particleId        = 0
let combatEventId     = 0
let saveInterval      = null
let dpsInterval       = null
let comboInterval     = null
let sessionClockInterval = null
let componentMountedAt = 0
let lastPlayerAction  = 0
let _isUnmounted = false

const autoClickActive = ref(false)
const autoClickTimeLeft = ref(0)
let autoClickIntervalId = null
let autoClickTimerId = null

function activateAutoClick() {
  if (autoClickActive.value || !inventory.hasItem('clicker_autoclick')) return
  inventory.useItem('clicker_autoclick')
  
  autoClickActive.value = true
  autoClickTimeLeft.value = 10
  pushCombatEvent('AUTO-CLICK ACTIVADO', 'neutral')
  
  autoClickIntervalId = setInterval(() => {
    doClick(true)
  }, 100) // 10 clicks per second
  
  autoClickTimerId = setInterval(() => {
    autoClickTimeLeft.value--
    if (autoClickTimeLeft.value <= 0) {
      clearInterval(autoClickIntervalId)
      clearInterval(autoClickTimerId)
      autoClickActive.value = false
      pushCombatEvent('AUTO-CLICK DESACTIVADO', 'neutral')
    }
  }, 1000)
}

const COMBO_TIERS = [
  { threshold: 50, label: 'IMPULSO', multiplier: 1.05, critBonus: 0.02 },
  { threshold: 100, label: 'RÁFAGA', multiplier: 1.12, critBonus: 0.04 },
  { threshold: 200, label: 'FRENESÍ', multiplier: 1.22, critBonus: 0.06 },
  { threshold: 500, label: 'SOBRECARGA', multiplier: 1.4, critBonus: 0.1 },
  { threshold: 1000, label: 'SINGULARIDAD', multiplier: 1.75, critBonus: 0.15 },
]

const currentComboTier = computed(() => {
  let tier = null; for (const candidate of COMBO_TIERS) { if (comboCount.value >= candidate.threshold) tier = candidate; else break }; return tier
})

const nextComboTier = computed(() => COMBO_TIERS.find(tier => comboCount.value < tier.threshold) ?? null)
const comboProgress = computed(() => {
  if (!nextComboTier.value) return 1
  const currentThreshold = currentComboTier.value?.threshold ?? 0
  const span = nextComboTier.value.threshold - currentThreshold
  return Math.max(0, Math.min((comboCount.value - currentThreshold) / span, 1))
})
const comboMultiplier = computed(() => currentComboTier.value?.multiplier ?? 1)
const critChance = computed(() => 0.06 + (currentComboTier.value?.critBonus ?? 0))
const comboLabel = computed(() => currentComboTier.value?.label ?? 'SISTEMA_ESTABLE')

function pushCombatEvent(text, tone = 'neutral') {
  const id = ++combatEventId; combatEvents.value.unshift({ id, text: text.toUpperCase(), tone }); combatEvents.value = combatEvents.value.slice(0, 5)
  setTimeout(() => { combatEvents.value = combatEvents.value.filter(e => e.id !== id) }, 3000)
}

watch(() => clicker.newAchievements.length, () => {
  while (clicker.newAchievements.length) {
    const achievement = clicker.newAchievements.shift(); const id = Date.now() + Math.random(); toastQueue.value.push({ id, ...achievement })
    setTimeout(() => { toastQueue.value = toastQueue.value.filter(a => a.id !== id) }, 10_000)
  }
})

onMounted(async () => {
  componentMountedAt = Date.now()
  await clicker.initializeGame(true)
  if (_isUnmounted) return
  
  sessionElapsedSeconds.value = clicker.getSessionDurationSeconds()
  sessionClockInterval = setInterval(() => { sessionElapsedSeconds.value = clicker.getSessionDurationSeconds() }, 1000)
  saveInterval = setInterval(() => { clicker.saveGame() }, 30_000)
  dpsInterval = setInterval(() => { if (clicker.dps > 0) clicker.gameState.balance += clicker.dps / 10 }, 100)
  comboInterval = setInterval(() => { if (!lastClickAt.value || comboCount.value === 0) return; if (Date.now() - lastClickAt.value > 850) comboCount.value = Math.max(comboCount.value - 1, 0) }, 160)
})

onUnmounted(() => {
  _isUnmounted = true
  clearInterval(saveInterval); clearInterval(dpsInterval); clearInterval(comboInterval); clearInterval(sessionClockInterval)
  if (Date.now() - componentMountedAt >= 5000 || lastPlayerAction > 0) clicker.saveGame()
})

function handleClick(event) {
  doClick(false, event)
}

function doClick(isAuto = false, event = null) {
  const now = Date.now(); comboCount.value = now - lastClickAt.value <= 650 ? comboCount.value + 1 : 1; lastClickAt.value = now
  isClicking.value = true; hitFlash.value = true
  const isCritical = Math.random() < critChance.value; const gained = clicker.click(comboMultiplier.value * (isCritical ? 2 : 1))
  
  if (isCritical) {
    critPulse.value = true; pushCombatEvent(`SOBRECARGA CRÍTICA · +${Math.round(gained)}`, 'critical')
    setTimeout(() => { critPulse.value = false }, 220)
  }

  const id = ++particleId
  let x, y;
  if (event && event.currentTarget) {
    const rect = event.currentTarget.getBoundingClientRect();
    x = rect.left + rect.width / 2 + (Math.random() - 0.5) * 60;
    y = rect.top + rect.height / 3;
  } else {
    // Fallback for auto-clicks
    x = window.innerWidth / 2 + (Math.random() - 0.5) * 60;
    y = window.innerHeight / 2 - 50;
  }

  clickParticles.value.push({ id, x, y, value: Math.round(gained), critical: isCritical })
  setTimeout(() => { clickParticles.value = clickParticles.value.filter(p => p.id !== id) }, 900)
  setTimeout(() => { hitFlash.value = false; isClicking.value = false }, 130); lastPlayerAction = Date.now()
}

function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(2) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return Math.floor(n).toString()
}

function formatSessionDuration(s) { const total = Math.max(Math.floor(s), 0); return `${Math.floor((total % 3600) / 60).toString().padStart(2, '0')}:${(total % 60).toString().padStart(2, '0')}` }

function toggleTier(tierId) {
  if (openTiers.value.includes(tierId)) {
    openTiers.value = openTiers.value.filter(id => id !== tierId)
  } else {
    openTiers.value.push(tierId)
  }
}
</script>

<template>
  <section class="clicker-game h-full flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>
    
    <!-- Cinematic Background Glows -->
    <div class="absolute top-1/2 left-1/4 -translate-1/2 size-[600px] bg-neon-cyan/5 blur-[150px] rounded-full pointer-events-none"></div>

    <BaseLoading 
      v-if="clicker.isLoading" 
      message="CONECTANDO_NÚCLEO..." 
      submessage="Sincronizando flujo de datos" 
    />


    <template v-else>
      <!-- HEADER HUD -->
      <ClickerHeader
        :balance="clicker.balance"
        :dps="clicker.dps"
        :combo-count="comboCount"
        :combo-multiplier="comboMultiplier"
      />

      <div class="flex-1 relative z-20 min-h-0 overflow-y-auto custom-scroll">
        
        <div class="w-full max-w-4xl mx-auto flex flex-col items-center p-4 sm:p-8 gap-12">
          
          <!-- MAIN INTERACTIVE AREA -->
          <main class="w-full flex flex-col gap-8 relative shrink-0">
          
          <!-- COMBO PROGRESS BAR -->
          <div class="w-full max-w-xl mx-auto h-2 bg-black/40 rounded-full border border-white/5 overflow-hidden relative shadow-inner">
            <div 
              class="h-full bg-gradient-to-r from-neon-cyan via-neon-fuchsia to-neon-pink transition-all duration-300 relative"
              :style="{ width: (comboProgress * 100) + '%' }"
            >
               <div class="absolute right-0 top-0 bottom-0 w-4 bg-white blur-[4px]"></div>
            </div>
            <div class="absolute inset-0 flex items-center justify-center mix-blend-difference pointer-events-none">
               <span class="font-pixel text-xs text-white/60 uppercase tracking-[0.4em]">{{ comboLabel }}</span>
            </div>
          </div>

          <!-- ABILITY DOCK -->
          <div class="flex items-center justify-center gap-3 mt-4">
             <div class="group relative">
               <button 
                 @click="activateAutoClick" 
                 :disabled="autoClickActive || !inventory.hasItem('clicker_autoclick')"
                 class="relative border border-white/10 bg-black/60 flex flex-col items-center justify-center min-w-[110px] h-14 px-3 disabled:opacity-30 disabled:grayscale transition-all hover:border-neon-cyan/50 hover:bg-white/5 active:scale-95 overflow-hidden"
               >
                 <div v-if="autoClickActive" class="absolute inset-0 bg-neon-cyan/20 animate-pulse"></div>
                 
                 <span v-if="!inventory.hasItem('clicker_autoclick') && !autoClickActive" class="absolute inset-0 flex items-center justify-center bg-black/80 font-pixel text-[8px] text-neon-pink uppercase z-20">0 USOS - TIENDA</span>
                 <span v-else-if="autoClickActive" class="absolute inset-0 flex items-center justify-center bg-black/80 font-pixel text-[10px] text-neon-cyan uppercase z-20">{{ autoClickTimeLeft }}s</span>
                 
                 <span class="font-display text-[9px] font-black uppercase text-white/60 group-hover:text-white relative z-10 text-center leading-tight">
                   AUTO-CLICK
                 </span>
                 <span class="font-pixel text-[8px] text-white/40 mt-0.5 relative z-10">{{ inventory.items['clicker_autoclick'] || 0 }} USOS</span>
                 
                 <div class="absolute inset-0 border border-transparent group-hover:border-neon-cyan/30 pointer-events-none"></div>
               </button>
             </div>
          </div>

          <!-- THE REACTOR CORE -->
          <div class="flex-1 flex flex-col items-center justify-center relative scale-90 sm:scale-100">
            
            <div class="relative group cursor-pointer select-none" @click="handleClick">
              
              <!-- Orbital Rings -->
              <div class="absolute inset-0 m-[-40px] border border-dashed border-neon-cyan/20 rounded-full animate-[spin_20s_linear_infinite] group-hover:border-neon-cyan/40 transition-colors"></div>
              <div class="absolute inset-0 m-[-80px] border-l-2 border-r-2 border-neon-pink/10 rounded-full animate-[spin_15s_linear_infinite_reverse]"></div>
              
              <!-- Core Glow -->
              <div 
                class="absolute inset-0 bg-neon-cyan/20 blur-[80px] rounded-full transition-all duration-500"
                :class="{ 'scale-150 opacity-100 bg-white/40': hitFlash, 'opacity-40 group-hover:opacity-80': !hitFlash }"
              ></div>
              
              <!-- Core Physics Container -->
              <div 
                class="relative size-60 sm:size-72 rounded-full border-4 border-white/10 p-4 transition-all duration-300 scale-100 active:scale-95 bg-[radial-gradient(circle,rgba(0,122,255,0.05)_0%,transparent_70%)]"
                :class="{ 'border-white bg-white/10 shadow-[0_0_100px_rgba(255,255,255,0.3)]': hitFlash, 'shadow-[0_0_50px_rgba(0,242,255,0.1)]': !hitFlash }"
              >
                <!-- Central Sphere Glass -->
                <div class="absolute inset-4 rounded-full flex flex-col items-center justify-center bg-gradient-to-tr from-black/80 to-black/20 backdrop-blur-2xl border-t border-l border-white/20 shadow-[inset_0_0_40px_rgba(0,0,0,0.8),0_20px_40px_rgba(0,0,0,0.5)] overflow-hidden group-hover:border-neon-cyan/50">
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(255,255,255,0.2)_0%,transparent_50%)]"></div>
                    <span 
                      class="font-display text-5xl font-black text-white mb-2 leading-none select-none relative z-10 transition-colors" 
                      :class="{ 'text-neon-cyan gh-title-glow': hitFlash }"
                    >
                      NÚCLEO
                    </span>
                    <div class="flex gap-1.5 relative z-10">
                       <div v-for="i in 3" :key="i" class="size-2 rounded-full bg-neon-cyan animate-pulse shadow-[0_0_10px_#00f2ff]" :style="{ animationDelay: (i*0.2)+'s' }"></div>
                    </div>
                </div>
              </div>
            </div>

            <!-- LOGS -->
            <div class="absolute bottom-0 w-full flex justify-center pointer-events-none">
               <div class="flex flex-col gap-1 items-center">
                  <TransitionGroup name="log">
                    <p 
                      v-for="evt in combatEvents" 
                      :key="evt.id"
                      class="font-pixel text-xs uppercase tracking-widest"
                      :class="evt.tone === 'critical' ? 'text-neon-pink font-bold drop-shadow-[0_0_5px_rgba(255,45,85,0.5)]' : 'text-neon-cyan/60'"
                    >
                      >> {{ evt.text }}
                    </p>
                  </TransitionGroup>
               </div>
            </div>
          </div>
        </main>

        <!-- UPGRADES PANEL -->
        <ClickerUpgrades
          :session-elapsed-seconds="sessionElapsedSeconds"
          :tiers="TIERS"
          :upgrades="UPGRADES"
        />
        </div>
      </div>
    </template>

    <!-- FLOATING PARTICLES (CRITS AND CLICKS) -->
    <Teleport to="body">
      <div class="pointer-events-none fixed inset-0 z-[100] overflow-hidden">
        <div
          v-for="p in clickParticles"
          :key="p.id"
          class="absolute whitespace-nowrap font-display text-sm font-black animate-rise-fade"
          :style="{ left: p.x + 'px', top: p.y + 'px', color: p.critical ? '#ff2d55' : '#00f2ff', textShadow: p.critical ? '0 0 10px #ff2d55' : '0 0 10px #00f2ff' }"
        >
          {{ p.critical ? '¡CRÍTICO!' : '' }} +{{ p.value }}
        </div>
      </div>
      
      <!-- ACHIEVEMENTS QUEUE -->
      <div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 w-80 pointer-events-none">
        <TransitionGroup name="toast">
          <div v-for="toast in toastQueue" :key="toast.id" class="gh-glass p-5 bg-black/80 border-neon-cyan/50 pointer-events-auto">
             <div class="flex items-center gap-3 mb-3">
                <div class="size-8  bg-neon-cyan/10 flex items-center justify-center text-neon-cyan">
                   <Icon icon="game-icons:trophy" />
                </div>
                <div class="flex-1">
                   <p class="font-pixel text-xs uppercase tracking-[0.2em] opacity-40">LOGRO_DESBLOQUEADO</p>
                   <h4 class="font-display text-xs font-black text-white uppercase">{{ toast.title }}</h4>
                </div>
             </div>
             <p class="font-sans text-xs text-white/60 font-medium uppercase leading-relaxed">{{ toast.description }}</p>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.animate-rise-fade {
  animation: rise-fade 0.8s ease-out forwards;
}
@keyframes rise-fade {
  0% { transform: translateY(0) scale(0.8); opacity: 0; }
  15% { opacity: 1; transform: translateY(-30px) scale(1.3); }
  100% { transform: translateY(-120px) scale(1.5); opacity: 0; }
}

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 242, 255, 0.3); }

.log-enter-active, .log-leave-active { transition: all 0.3s; }
.log-enter-from { opacity: 0; transform: translateY(10px); }
.log-leave-to { opacity: 0; transform: translateY(-10px); }

.toast-enter-active, .toast-leave-active { transition: all 0.4s ease; }
.toast-enter-from, .toast-leave-to { transform: translateX(80px); opacity: 0; }

.accordion-enter-active, .accordion-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-height: 800px; overflow: hidden; }
.accordion-enter-from, .accordion-leave-to { max-height: 0; opacity: 0; }
</style>
