<template>
  <div class="tower-defense h-full min-h-[750px] flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>

    <!-- Background Ambient Layers -->
    <div class="absolute inset-0 bg-gradient-to-b from-neon-cyan/5 via-transparent to-neon-pink/5 pointer-events-none"></div>

    <!-- SELECCIÓN INICIAL -->
    <TowerDefenseChoice 
      v-if="gameMode === 'choice'"
      :is-loading="store.isLoading"
      :has-saved-game="!!store.savedGame"
      :saved-wave="store.savedGame ? store.savedGame.wave : 1"
      @start-game="startGame"
    />
    
    <!-- INTERFAZ DE JUEGO: COMMAND CONSOLE LAYOUT -->
    <template v-else-if="gameMode === 'playing'">
      <div 
        class="h-full w-full flex flex-col bg-retro-deep relative"
        :class="{ 'damage-shake': isDamaged }"
      >
        <!-- TOP HUD: SYSTEM TELEMETRY -->
        <TowerDefenseHeader
          :lives="gameState.lives"
          :gold="gameState.gold"
          :wave="gameState.wave"
          :wave-active="gameState.waveActive"
          :wave-progress-percent="waveProgressPercent"
          :remaining-enemies="remainingEnemies"
          @start-wave="startWave"
        />

        <!-- BATTLEFIELD VIEWPORT: RESERVED SPACE -->
        <main 
          ref="gameContainer"
          class="flex-1 relative overflow-hidden flex items-center justify-center p-8 bg-black/20"
          @mousemove="handleParallax"
        >
          <!-- Global Effects -->
          <div class="absolute inset-0 pointer-events-none z-10 crt-ripple opacity-[0.02]"></div>
          <div class="absolute inset-0 pointer-events-none z-30 overflow-hidden opacity-20">
            <div class="absolute inset-0 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[length:100%_4px,3px_100%]"></div>
          </div>
          <div 
            class="relative origin-center z-20"
            :style="{ transform: `scale(${finalScale}) rotateY(${parallax.y * 5}deg) rotateX(${parallax.x * -5}deg)` }"
          >
            <div class="gh-panel bg-retro-black border border-white/5 flex flex-col items-center p-6 w-fit shadow-[15px_15px_0_rgba(0,0,0,0.5)]">
              <!-- Integrated Ability Dock -->
              <div class="flex items-center gap-3 mb-6">
                <div v-for="key in ['emp', 'overclock', 'purge']" :key="key" class="group relative">
                  <!-- Tooltip: Now deploying downwards -->
                  <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-64 gh-glass p-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 text-center shadow-xl border-white/10 bg-black/90">
                    <p class="font-pixel text-[10px] text-white/90 leading-relaxed uppercase">
                      {{ key === 'emp' ? 'Sobrecarga de pulso: Paraliza a todas las unidades enemigas temporalmente.' : (key === 'overclock' ? 'Frecuencia Crítica: Duplica la cadencia de fuego de todos los nodos de defensa.' : 'Protocolo Purga: Ejecuta una descarga masiva que daña a todas las entidades activas.') }}
                    </p>
                  </div>
                  
                  <button 
                    @click="activateAbility(key)" 
                    :disabled="gameState.cooldowns[key] > 0 || gameState.isPaused || !inventory.hasItem('td_' + key)" 
                    class="relative border border-white/10 bg-black/60 flex flex-col items-center justify-center min-w-[110px] h-16 px-3 disabled:opacity-30 disabled:grayscale transition-all hover:border-neon-cyan/50 hover:bg-white/5 active:scale-95 group overflow-hidden"
                  >
                    <div class="absolute inset-0 origin-bottom transition-all" :class="`bg-neon-${key==='emp'?'cyan':(key==='overclock'?'yellow':'pink')}/10`" :style="{ height: `${(gameState.cooldowns[key] / (key==='emp'?600:(key==='overclock'?900:1200))) * 100}%` }"></div>
                    
                    <span v-if="!inventory.hasItem('td_' + key)" class="absolute inset-0 flex items-center justify-center bg-black/80 font-pixel text-xs text-neon-pink uppercase z-20">0 USOS - TIENDA</span>
                    
                    <Icon 
                      :icon="key === 'emp' ? 'game-icons:lightning-shield' : (key === 'overclock' ? 'game-icons:speedometer' : 'game-icons:laser-blast')" 
                      class="text-lg mb-1 relative z-10"
                      :class="`text-neon-${key==='emp'?'cyan':(key==='overclock'?'yellow':'pink')}`"
                    />
 
                    <span class="font-display text-[10px] font-black uppercase text-white/60 group-hover:text-white relative z-10 text-center leading-tight">
                      {{ key==='emp' ? 'PULSO EMP' : (key==='overclock' ? 'SOBRECARGA' : 'PURGA TOTAL') }}
                    </span>
                    <span class="font-pixel text-xs text-white/40 mt-0.5 relative z-10">{{ inventory.items['td_' + key] || 0 }} USOS</span>
                    <!-- Border glow on hover -->
                    <div class="absolute inset-0 border border-transparent group-hover:border-neon-cyan/30 pointer-events-none"></div>
                  </button>
                </div>
              </div>

              <div class="relative border border-white/10 bg-black/40">
                <div class="relative z-10" :style="{ width: (mapWidth * cellSize) + 'px', height: (mapHeight * cellSize) + 'px' }">
                  <!-- The Map Grid -->
                  <div v-for="y in mapHeight" :key="'row-' + y" class="flex">
                    <div
                      v-for="x in mapWidth"
                      :key="'cell-' + x + '-' + y"
                      class="hologram-cell"
                      :class="{ 'is-path': isPath(x - 1, y - 1), 'is-selected': selectedCell && selectedCell.x === x - 1 && selectedCell.y === y - 1, 'has-building': getTowerAt(x - 1, y - 1) }"
                      :style="{ width: cellSize + 'px', height: cellSize + 'px' }"
                      @click="handleMapClick" :data-x="x - 1" :data-y="y - 1"
                    >
                      <div v-if="gameState.path.length && x - 1 === gameState.path[0].x && y - 1 === gameState.path[0].y" class="absolute inset-0 bg-[#e02fe8]/10 border border-[#e02fe8]/30 flex items-center justify-center z-10 overflow-hidden">
                        <div class="absolute inset-0 bg-[radial-gradient(circle,rgba(224,47,232,0.2)_0%,transparent_70%)]"></div>
                        <Icon icon="game-icons:entry-door" class="text-neon-fuchsia text-xl relative z-10 animate-pulse shadow-[0_0_10px_#e02fe8]" />
                      </div>
                      <div v-if="gameState.path.length && x - 1 === gameState.path[gameState.path.length-1].x && y - 1 === gameState.path[gameState.path.length-1].y" class="absolute inset-0 bg-neon-pink/10 border border-neon-pink/40 flex items-center justify-center z-10">
                        <div class="absolute inset-0 bg-[radial-gradient(circle,rgba(255,45,85,0.2)_0%,transparent_70%)] animate-pulse"></div>
                        <div class="relative flex items-center justify-center">
                          <div class="absolute size-8 border border-neon-pink animate-[spin_4s_linear_infinite]"></div>
                          <div class="absolute size-6 border border-white/30 animate-[spin_3s_linear_infinite_reverse]"></div>
                          <Icon icon="game-icons:cpu" class="text-neon-pink text-xl relative z-20 drop-shadow-[0_0_8px_rgba(255,45,85,0.8)]" />
                        </div>
                      </div>
                      <div v-if="getTowerAt(x - 1, y - 1)" class="tower-node" :style="{ '--tower-color': getTowerAt(x - 1, y - 1).color }">
                         <div class="tower-base-plate shadow-[2px_2px_0_#000]"></div>
                         <div class="tower-icon-container">
                            <Icon :icon="getTowerAt(x - 1, y - 1).icon" class="tower-icon" />
                         </div>
                         <div class="tower-glow-ring"></div>
                      </div>
                    </div>
                  </div>
                  <div v-if="selectedTower && selectedCell && !isPath(selectedCell.x, selectedCell.y)" class="hologram-range" :style="selectedRangeStyle"></div>
                  <div v-for="enemy in enemies" :key="enemy.id" class="enemy-hologram" :class="[enemy.shapeClass, { 'is-slowed': enemy.slowTimer > 0, 'is-poisoned': enemy.poisonTicks > 0 }]" :style="{ left: `${enemy.pixelX}px`, top: `${enemy.pixelY}px`, width: `${enemy.sizePx}px`, height: `${enemy.sizePx}px`, '--color': enemy.poisonTicks > 0 ? '#22c55e' : (enemy.slowTimer > 0 ? '#00f2ff' : enemy.baseColor) }"><div class="enemy-body"></div><div class="enemy-hp-bar"><div class="enemy-hp-fill" :style="{ width: `${(enemy.hp / enemy.maxHp) * 100}%` }"></div></div></div>
                  <div v-for="projectile in projectiles" :key="projectile.id" class="energy-bolt" :style="{ left: `${projectile.x}px`, top: `${projectile.y}px`, width: `${projectile.size}px`, height: `${projectile.size}px`, '--color': projectile.color }"></div>

                  <!-- Path SVG Line -->
                  <svg class="absolute inset-0 pointer-events-none z-[8]" :style="{ width: (mapWidth * cellSize) + 'px', height: (mapHeight * cellSize) + 'px' }">
                    <path 
                      :d="svgPathData" 
                      fill="none" 
                      stroke="rgba(224, 47, 232, 0.4)" 
                      stroke-width="4" 
                      stroke-linecap="round" 
                      stroke-linejoin="round"
                    />
                    <path 
                      :d="svgPathData" 
                      fill="none" 
                      stroke="rgba(255, 45, 85, 0.6)" 
                      stroke-width="2" 
                      stroke-dasharray="8 12" 
                      class="path-line-flow"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- BOTTOM DOCK: COMMAND & CONTROLS -->
        <footer class="h-24 shrink-0 gh-glass border-t border-white/10 flex items-center justify-between px-6 z-40 bg-black/80 shadow-[0_-4px_0_#000]">
          <!-- Logs Left -->
          <div class="w-64 h-16 gh-glass p-3 bg-black/60 border-white/5 overflow-hidden hidden lg:block shadow-[inset_2px_2px_10px_rgba(0,0,0,0.5)]">
            <div class="h-full overflow-y-auto space-y-1 custom-scroll scrollbar-none opacity-60">
              <div v-for="log in gameLogs.slice(0, 4)" :key="log.id" class="font-pixel text-xs text-neon-cyan uppercase flex gap-2">
                <span class="opacity-30">[{{ log.time }}]</span>
                <span>{{ log.text }}</span>
              </div>
            </div>
          </div>

          <!-- Space Reserved for abilities (now moved) -->
          <div class="flex-1"></div>

          <!-- View/Time Controls Right -->
          <div class="flex items-center gap-6">
            <div class="flex flex-col gap-1.5">
               <div class="flex justify-between items-center px-1 mb-0.5">
                 <span class="font-pixel text-xs text-white/30 uppercase tracking-widest">MAPA_SCALE: {{ (finalScale * 100).toFixed(0) }}%</span>
               </div>
               <div class="flex gap-1">
                 <button @click="userZoom -= 0.1" class="size-9 bg-retro-dark border border-white/10 text-xs font-pixel text-white/40 hover:bg-white/10 transition-all flex items-center justify-center shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none">
                    <Icon icon="game-icons:magnifying-glass" class="rotate-90 scale-x-[-1]" />
                 </button>
                 <button @click="userZoom = 1.0" class="px-3 h-9 bg-retro-dark border border-white/10 text-xs font-pixel text-white/30 hover:bg-white/10 shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none uppercase">RESET</button>
                 <button @click="userZoom += 0.1" class="size-9 bg-retro-dark border border-white/10 text-xs font-pixel text-white/40 hover:bg-white/10 transition-all flex items-center justify-center shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none">
                    <Icon icon="game-icons:magnifying-glass" />
                 </button>
               </div>
            </div>

            <div class="flex flex-col gap-1.5">
               <span class="font-pixel text-xs text-white/30 uppercase text-center tracking-widest">TEMPO_SISTEMA</span>
               <div class="flex gap-1">
                 <button @click="gameState.isPaused = !gameState.isPaused" class="px-4 py-1.5 border border-white/10 text-xs font-pixel transition-all flex items-center justify-center gap-2 shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none" :class="gameState.isPaused ? 'bg-neon-pink text-black' : 'bg-retro-dark text-white/40 hover:bg-white/10'">
                    <Icon :icon="gameState.isPaused ? 'game-icons:play-button' : 'game-icons:pause-button'" />
                    <span>{{ gameState.isPaused ? 'RESUME' : 'PAUSE' }}</span>
                 </button>
                 <button @click="gameState.speed = 1; gameState.isPaused = false" class="w-10 h-9 border border-white/10 text-xs font-pixel transition-all shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none" :class="gameState.speed === 1 && !gameState.isPaused ? 'bg-neon-cyan text-black' : 'bg-retro-dark text-white/40 hover:bg-white/10'">x1</button>
                 <button @click="gameState.speed = 2; gameState.isPaused = false" class="px-3 h-9 border border-white/10 text-xs font-pixel transition-all flex items-center justify-center gap-2 shadow-[2px_2px_0_#000] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none" :class="gameState.speed === 2 && !gameState.isPaused ? 'bg-neon-yellow text-black' : 'bg-retro-dark text-white/40 hover:bg-white/10'">
                    <Icon icon="game-icons:forward-field" />
                    <span>TURBO</span>
                 </button>
               </div>
            </div>
          </div>
        </footer>
      </div>
    </template>

    <!-- PANTALLA DE DERROTA: FALLO TOTAL -->
    <TowerDefenseGameOver
      v-else-if="gameMode === 'gameOver'"
      :wave="gameState.wave"
      @reset="resetToChoice"
    />
    
    <!-- FLOATING TOOLTIP -->
    <Teleport to="body">
      <TowerDefenseTooltip
        :selected-cell="selectedCell"
        :tooltip-position="tooltipPosition"
        :selected-tower="selectedTower"
        :tower-types="towerTypes"
        :gold="gameState.gold"
        :upgrade-cost="upgradeCost"
        :selected-tower-sell-value="selectedTowerSellValue"
        :is-selling="isSelling"
        @close="closeTooltip"
        @build-tower="buildTower"
        @upgrade="upgradeTower"
        @sell="confirmSellTower"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'
import TowerDefenseChoice from './TowerDefenseChoice.vue'
import TowerDefenseGameOver from './TowerDefenseGameOver.vue'
import TowerDefenseHeader from './TowerDefenseHeader.vue'
import TowerDefenseTooltip from './TowerDefenseTooltip.vue'
import { useTowerDefenseStore } from '../../../stores/games/towerdefense'
import { useInventoryStore } from '../../../stores/inventory'
import BaseLoading from '../../ui/BaseLoading.vue'


const emit = defineEmits(['live-score'])
const store = useTowerDefenseStore()
const inventory = useInventoryStore()

const mapWidth = 12; const mapHeight = 10; const cellSize = 50
const viewportVersion = ref(0); const gameMode = ref('choice')
const isDamaged = ref(false)
const parallax = reactive({ x: 0, y: 0 })
const gameContainer = ref(null)
const containerWidth = ref(0)
const userZoom = ref(1.0)
const isSelling = ref(false)

const finalScale = computed(() => {
  let autoScale = 1.0
  if (containerWidth.value > 0 && gameContainer.value) {
    const cw = containerWidth.value - 100 // Minimal horizontal buffer for full space
    const ch = gameContainer.value.clientHeight - 100 // Minimal vertical buffer
    const mw = mapWidth * cellSize
    const mh = mapHeight * cellSize
    autoScale = Math.max(0.3, Math.min(1, cw / mw, ch / mh))
  }
  return Number((autoScale * userZoom.value).toFixed(2))
})

const handleParallax = (e) => {
  const x = (e.clientX / window.innerWidth) - 0.5
  const y = (e.clientY / window.innerHeight) - 0.5
  parallax.x = x
  parallax.y = y
}

const gameState = reactive({ 
  lives: 100, gold: 150, wave: 1, waveActive: false, gameOver: false, path: [],
  speed: 1, isPaused: false,
  cooldowns: { emp: 0, overclock: 0, purge: 0 },
  activeEffects: { empTimer: 0, overclockTimer: 0 }
})

const gameLogs = ref([
  { id: 0, time: new Date().toLocaleTimeString().slice(0, 5), text: 'NÚCLEO_INICIALIZADO' },
  { id: 1, time: new Date().toLocaleTimeString().slice(0, 5), text: 'MODO_DEFENSA_STANDBY' }
])

const addLog = (text) => {
  gameLogs.value.unshift({ id: Date.now(), time: new Date().toLocaleTimeString().slice(0, 5), text });
  if (gameLogs.value.length > 20) gameLogs.value.pop();
}

const activateAbility = (key) => {
  if (gameState.cooldowns[key] > 0 || gameState.isPaused || gameState.gameOver) return;
  const itemId = 'td_' + key;
  if (!inventory.hasItem(itemId)) {
    addLog(`SIN_CARGAS_DE_${key.toUpperCase()}`);
    return;
  }
  
  inventory.useItem(itemId);

  if (key === 'emp') {
    gameState.activeEffects.empTimer = 100;
    gameState.cooldowns.emp = 600;
    addLog('HABILIDAD_EMP_LANZADA');
  } else if (key === 'overclock') {
    gameState.activeEffects.overclockTimer = 160;
    gameState.cooldowns.overclock = 900;
    addLog('SISTEMAS_SOBRECARGADOS');
  } else if (key === 'purge') {
    enemies.value.forEach(e => e.hp -= 200 * Math.pow(1.2, gameState.wave - 1));
    gameState.cooldowns.purge = 1200;
    addLog('PURGA_LÁSER_EJECUTADA');
  }
}

const generateRandomPath = (width, height) => {
  const newPath = [];
  let currentX = 0;
  let currentY = Math.floor(Math.random() * (height - 4)) + 2;
  newPath.push({ x: currentX, y: currentY });
  currentX++;
  newPath.push({ x: currentX, y: currentY });

  while (currentX < width - 1) {
    if (Math.random() < 0.4 || currentX === 0) {
      currentX++;
      newPath.push({ x: currentX, y: currentY });
    } else {
      const verticalSteps = Math.floor(Math.random() * 3) + 1;
      const direction = Math.random() < 0.5 ? 1 : -1;
      
      for (let i = 0; i < verticalSteps; i++) {
        const nextY = currentY + direction;
        if (nextY >= 1 && nextY < height - 1) {
          currentY = nextY;
          newPath.push({ x: currentX, y: currentY });
        } else {
          break;
        }
      }
      currentX++;
      newPath.push({ x: currentX, y: currentY });
    }
  }
  return newPath;
}

const isPath = (x, y) => gameState.path.some((p) => p.x === x && p.y === y)
const towers = ref([]); const enemies = ref([]); const projectiles = ref([]); const selectedCell = ref(null); const clickPosition = ref(null)

const towerTypes = {
  basic: { name: 'Centinela', cost: 30, range: 2.5, damage: 15, cooldownMax: 20, color: '#007aff', desc: 'Defensa Estándar', effect: 'none', icon: 'game-icons:turret' },
  rapid: { name: 'Pulso-X', cost: 60, range: 2, damage: 4, cooldownMax: 4, color: '#f97316', desc: 'Protocolo de Ataque Rápido', effect: 'fast', icon: 'game-icons:striking-arrows' },
  sniper: { name: 'Francotirador', cost: 100, range: 5, damage: 60, cooldownMax: 60, color: '#ff2d55', desc: 'Eliminación a Gran Distancia', effect: 'none', icon: 'game-icons:crosshair' },
  heavy: { name: 'Cañón Nova', cost: 120, range: 2.5, damage: 40, cooldownMax: 45, color: '#a855f7', desc: 'Disruptor de Área', effect: 'splash', icon: 'game-icons:cannon' },
  frost: { name: 'Criocentral', cost: 70, range: 2.8, damage: 8, cooldownMax: 28, color: '#00f2ff', desc: 'Bitrate Lento (-20%)', effect: 'slow', icon: 'game-icons:snowflake-1' },
  poison: { name: 'Bio-Nodo', cost: 90, range: 3, damage: 10, cooldownMax: 40, color: '#22c55e', desc: 'Corrupción (Daño Continuo)', effect: 'poison', icon: 'game-icons:poison-gas' }
}

const enemyArchetypes = {
  circle: { shapeClass: 's-circ', hpMultiplier: 1, speedMultiplier: 1, sizeMultiplier: 0.45, rewardMultiplier: 1, damageReduction: 0, baseColor: '#ff2d55' },
  triangle: { shapeClass: 's-tria', hpMultiplier: 0.55, speedMultiplier: 2.1, sizeMultiplier: 0.35, rewardMultiplier: 1.6, damageReduction: 0, baseColor: '#f97316' },
  diamond: { shapeClass: 's-diam', hpMultiplier: 3.2, speedMultiplier: 0.5, sizeMultiplier: 0.72, rewardMultiplier: 2.8, damageReduction: 0.08, baseColor: '#8b5cf6' },
  square: { shapeClass: 's-squa', hpMultiplier: 1.9, speedMultiplier: 0.85, sizeMultiplier: 0.56, rewardMultiplier: 2.0, damageReduction: 0.36, baseColor: '#94a3b8' },
  boss: { shapeClass: 's-boss', hpMultiplier: 15.0, speedMultiplier: 0.4, sizeMultiplier: 0.9, rewardMultiplier: 25.0, damageReduction: 0.6, baseColor: '#e02fe8' }
}

const towerEffectLabel = (effect) => {
  if (effect === 'slow') return 'Ralentizado'
  if (effect === 'poison') return 'Corrupto'
  if (effect === 'splash') return 'Disruptivo'
  if (effect === 'fast') return 'Frecuente'
  return 'Base'
}

const getTowerAt = (x, y) => towers.value.find((t) => t.x === x && t.y === y)
const selectedTower = computed(() => selectedCell.value ? getTowerAt(selectedCell.value.x, selectedCell.value.y) : null)
const upgradeCost = computed(() => selectedTower.value ? Math.floor(selectedTower.value.baseCost * Math.pow(1.5, selectedTower.value.level)) : 0)
const selectedTowerSellValue = computed(() => selectedTower.value ? Math.floor(selectedTower.value.totalSpent * 0.5) : 0)

const selectedRangeStyle = computed(() => {
  if (!selectedTower.value) return {}
  const d = selectedTower.value.range * 2 * cellSize
  return { width: `${d}px`, height: `${d}px`, left: `${(selectedTower.value.x + 0.5) * cellSize - (d / 2)}px`, top: `${(selectedTower.value.y + 0.5) * cellSize - (d / 2)}px` }
})

const remainingEnemies = computed(() => enemies.value.length + enemiesToSpawn.value)
const waveProgressPercent = computed(() => {
  if (!gameState.waveActive || totalWaveEnemies.value <= 0) return 0
  return Math.max(0, Math.min(((totalWaveEnemies.value - remainingEnemies.value) / totalWaveEnemies.value) * 100, 100))
})

const svgPathData = computed(() => {
  if (!gameState.path.length) return ''
  return gameState.path.map((p, i) => {
    const x = (p.x + 0.5) * cellSize
    const y = (p.y + 0.5) * cellSize
    return (i === 0 ? 'M' : 'L') + x + ',' + y
  }).join(' ')
})

const enemiesToSpawn = ref(0); const totalWaveEnemies = ref(0)
let towerIdCounter = 0; let enemyIdCounter = 0; let projectileIdCounter = 0; let spawnInterval = null; let gameLoopId = null; let resizeListener = null

const handleMapClick = (e) => {
  e.stopPropagation(); const cell = e.target.closest('.hologram-cell'); if (!cell) return
  const x = parseInt(cell.dataset.x, 10), y = parseInt(cell.dataset.y, 10)
  if (isPath(x, y) && !getTowerAt(x, y)) { selectedCell.value = null; clickPosition.value = null; return }
  const rect = cell.getBoundingClientRect(); clickPosition.value = { x: rect.left, y: rect.top }; selectedCell.value = { x, y }
}

const closeTooltip = () => { selectedCell.value = null; clickPosition.value = null; isSelling.value = false }

const tooltipPosition = computed(() => {
  viewportVersion.value
  if (!selectedCell.value || !clickPosition.value) return {}
  const { x, y } = clickPosition.value; const tw = 340, th = 300, pad = 12, vW = window.innerWidth, vH = window.innerHeight
  let left = x + cellSize / 2 + pad + tw < vW ? x + cellSize / 2 + pad : (x + cellSize / 2 - pad - tw > 0 ? x + cellSize / 2 - pad - tw : Math.max(pad, Math.min(vW - tw - pad, vW / 2 - tw / 2)))
  let top = Math.max(pad, Math.min(vH - th - pad, y + cellSize / 2 - th / 2))
  return { left: `${left}px`, top: `${top}px`, position: 'fixed' }
})

const buildTower = (key) => {
  const t = towerTypes[key]; if (!t || !selectedCell.value) return
  const { x, y } = selectedCell.value; if (isPath(x, y) || getTowerAt(x, y) || gameState.gold < t.cost) return
  gameState.gold -= t.cost; towers.value.push({ ...t, id: towerIdCounter++, x, y, baseCost: t.cost, totalSpent: t.cost, level: 1, cooldown: 0 });
  addLog(`NODO_${t.name.toUpperCase()}_DESPLEGADO`);
}

const upgradeTower = () => { if (gameState.gold >= upgradeCost.value && selectedTower.value) { gameState.gold -= upgradeCost.value; selectedTower.value.totalSpent += upgradeCost.value; selectedTower.value.level++; selectedTower.value.damage *= 1.4; selectedTower.value.range += 0.1; addLog(`NODO_NIVEL_${selectedTower.value.level}_UPGRADE`); } }
const confirmSellTower = () => { 
  if (!selectedTower.value) return;
  if (!isSelling.value) {
    isSelling.value = true;
    setTimeout(() => { if (selectedCell.value) isSelling.value = false; }, 3000);
    return;
  }
  addLog(`NODO_RECICLADO_+${selectedTowerSellValue.value}C`); 
  gameState.gold += selectedTowerSellValue.value; 
  towers.value = towers.value.filter(t => t.id !== selectedTower.value.id); 
  selectedCell.value = null;
  isSelling.value = false;
}

const startWave = () => {
  if (gameState.waveActive || gameState.gameOver) return
  gameState.waveActive = true; 
  addLog(`OLEADA_${gameState.wave}_INICIADA`);
  if (gameState.wave % 5 === 0) addLog('ADVERTENCIA: ENTIDAD_TITÁN_DETECTADA');
  const stage = Math.floor((gameState.wave - 1) / 5)
  const isBossWave = gameState.wave % 5 === 0;
  enemiesToSpawn.value = isBossWave ? 1 : 6 + Math.floor(gameState.wave * 1.15) + (stage * 3); 
  totalWaveEnemies.value = enemiesToSpawn.value
  const baseHp = Math.round(Math.round(28 * Math.pow(1.12, gameState.wave - 1)) * Math.pow(1.6, stage))
  const baseSpeed = 0.055 + (gameState.wave * 0.0022), baseRew = Math.max(1, Math.round(Math.max(2, Math.round(4 * Math.pow(1.06, gameState.wave - 1))) * Math.pow(1.35, stage)))
  if (spawnInterval) clearInterval(spawnInterval)
  spawnInterval = setInterval(() => {
    if (gameState.isPaused) return;
    if (enemiesToSpawn.value > 0) {
      const arch = pickEnemyArchetype(gameState.wave, enemiesToSpawn.value === totalWaveEnemies.value), hp = Math.round(baseHp * arch.hpMultiplier), sz = Math.max(14, Math.round(cellSize * arch.sizeMultiplier))
      enemies.value.push({
        id: enemyIdCounter++, progress: 0, hp, maxHp: hp, speed: Math.min(baseSpeed * arch.speedMultiplier, 0.22), slowTimer: 0, poisonTicks: 0,
        reward: Math.max(1, Math.round(baseRew * arch.rewardMultiplier)), pixelX: (gameState.path[0].x + 0.5) * cellSize, pixelY: (gameState.path[0].y + 0.5) * cellSize,
        sizePx: sz, shapeClass: arch.shapeClass, damageReduction: arch.damageReduction, baseColor: arch.baseColor
      }); enemiesToSpawn.value--
    } else { clearInterval(spawnInterval); spawnInterval = null }
  }, 1000)
}

const pickEnemyArchetype = (w, isFirst) => {
  if (w % 5 === 0 && isFirst) return enemyArchetypes.boss;
  const r = Math.random(); if (w < 3) return r < 0.75 ? enemyArchetypes.circle : enemyArchetypes.triangle
  if (w < 6) return r < 0.52 ? enemyArchetypes.circle : (r < 0.78 ? enemyArchetypes.triangle : (r < 0.92 ? enemyArchetypes.square : enemyArchetypes.diamond))
  return r < 0.4 ? enemyArchetypes.circle : (r < 0.65 ? enemyArchetypes.triangle : (r < 0.84 ? enemyArchetypes.square : enemyArchetypes.diamond))
}

const gameTick = () => {
  if (gameState.gameOver || gameState.isPaused) return

  for (let s = 0; s < gameState.speed; s++) {
    // Update Cooldowns and Effects
    if (gameState.cooldowns.emp > 0) gameState.cooldowns.emp--;
    if (gameState.cooldowns.overclock > 0) gameState.cooldowns.overclock--;
    if (gameState.cooldowns.purge > 0) gameState.cooldowns.purge--;
    
    let isEmpActive = false;
    if (gameState.activeEffects.empTimer > 0) {
      gameState.activeEffects.empTimer--;
      isEmpActive = true;
    }
    
    let isOverclockActive = false;
    if (gameState.activeEffects.overclockTimer > 0) {
      gameState.activeEffects.overclockTimer--;
      isOverclockActive = true;
    }

    for (let i = enemies.value.length - 1; i >= 0; i--) {
      const e = enemies.value[i]; if (e.poisonTicks > 0) { e.hp -= 0.15; e.poisonTicks-- }
      
      if (!isEmpActive) {
        const spd = e.slowTimer > 0 ? e.speed * 0.6 : e.speed; if (e.slowTimer > 0) e.slowTimer--
        e.progress += spd; const idx = Math.floor(e.progress)
        if (idx + 1 >= gameState.path.length) { 
          enemies.value.splice(i,1); 
          gameState.lives -= 5; 
          isDamaged.value = true;
          setTimeout(() => isDamaged.value = false, 400);
          addLog('FALLO_CRÍTICO: BRECHA_DETECTADA');
          if (gameState.lives <= 0) {
            gameState.lives = 0;
            handleGameOver(); 
          }
          continue 
        }
        const f = e.progress - idx, cp = gameState.path[idx], np = gameState.path[idx+1]
        e.pixelX = (cp.x + (np.x-cp.x)*f + 0.5) * cellSize; e.pixelY = (cp.y + (np.y-cp.y)*f + 0.5) * cellSize
      }
      
      if (e.hp <= 0) { gameState.gold += e.reward; enemies.value.splice(i,1) }
    }
    
    towers.value.forEach(tw => {
      if (tw.cooldown > 0) { 
        tw.cooldown -= isOverclockActive ? 2 : 1; 
        if (tw.cooldown < 0) tw.cooldown = 0;
        return; 
      }
      const tc = { x:(tw.x+0.5)*cellSize, y:(tw.y+0.5)*cellSize }
      
      // Filtrar enemigos en rango
      const inRange = enemies.value.filter(e => Math.hypot(e.pixelX+e.sizePx/2 - tc.x, e.pixelY+e.sizePx/2 - tc.y)/cellSize <= tw.range)
      if (inRange.length === 0) return

      let tgt = null
      const isUtility = tw.effect === 'slow' || tw.effect === 'poison'

      if (isUtility) {
        // ESTRATEGIA ROUND ROBIN: Rotar entre enemigos para repartir efectos
        if (!tw.targetIndex) tw.targetIndex = 0
        tgt = inRange[tw.targetIndex % inRange.length]
        tw.targetIndex++
      } else {
        // ESTRATEGIA FIFO: Atacar al que más ha avanzado (mayor progreso)
        tgt = inRange.reduce((max, e) => e.progress > max.progress ? e : max, inRange[0])
      }

      if (!tgt) return
      projectiles.value.push({ id: projectileIdCounter++, x:tc.x, y:tc.y, targetId: tgt.id, damage: tw.damage, effect: tw.effect, color: tw.color, speed: tw.effect==='fast'?17:8, size: tw.effect==='fast'?6:10 })
      tw.cooldown = tw.cooldownMax
    })
    
    for (let i = projectiles.value.length - 1; i >= 0; i--) {
      const p = projectiles.value[i], tgt = enemies.value.find(e => e.id===p.targetId)
      if (!tgt) { projectiles.value.splice(i,1); continue }
      const tc = { x:tgt.pixelX+tgt.sizePx/2, y:tgt.pixelY+tgt.sizePx/2 }, dx = tc.x-p.x, dy = tc.y-p.y, d = Math.hypot(dx,dy)
      if (d <= Math.max(p.speed,1)) {
        tgt.hp -= p.damage * (1 - (tgt.damageReduction || 0))
        if (p.effect==='slow') tgt.slowTimer = 60; if (p.effect==='poison') tgt.poisonTicks = 120
        if (p.effect==='splash') enemies.value.forEach(e => { if (e.id!==tgt.id && Math.hypot(e.pixelX+e.sizePx/2-tc.x, e.pixelY+e.sizePx/2-tc.y)<70) e.hp -= p.damage*0.5 })
        projectiles.value.splice(i,1)
      } else { p.x += (dx/d)*p.speed; p.y += (dy/d)*p.speed }
    }
    if (gameState.waveActive && enemiesToSpawn.value === 0 && enemies.value.length === 0) { gameState.waveActive = false; gameState.wave++; store.saveProgress(gameState.wave) }
    
    // Update projectiles logic...
  }
}

async function handleGameOver() { 
  gameState.gameOver = true; 
  gameState.waveActive = false; 
  gameMode.value = 'gameOver';
  emit('live-score', gameState.wave-1); 
  try { 
    await store.saveProgress(gameState.wave); 
    await store.completeSession(gameState.wave,0); 
    await store.resetGame() 
  } catch(e){} 
}

const resetToChoice = () => {
  if (gameLoopId) {
    clearInterval(gameLoopId);
    gameLoopId = null;
  }
  
  // Reset local state
  Object.assign(gameState, {
    lives: 100, gold: 150, wave: 1, waveActive: false, gameOver: false, path: [],
    speed: 1, isPaused: false,
    cooldowns: { emp: 0, overclock: 0, purge: 0 },
    activeEffects: { empTimer: 0, overclockTimer: 0 }
  });
  
  enemies.value = [];
  towers.value = [];
  projectiles.value = [];
  selectedCell.value = null;
  
  gameMode.value = 'choice';
}
async function startGame(cont) { 
  await store.initializeTowerDefense(cont); 
  Object.assign(gameState, store.gameState); 
  
  // Force 100% integrity on new games
  if (!cont) gameState.lives = 100;
  
  towers.value = store.gameState.towers || []; 
  
  if (!cont || !store.gameState.path || store.gameState.path.length === 0) {
    gameState.path = generateRandomPath(mapWidth, mapHeight);
    store.updateGameState({ path: gameState.path });
  }

  gameMode.value = 'playing'; 
  if (!gameLoopId) gameLoopId = setInterval(gameTick, 30); 
}
function resetGameLocal() { 
  Object.assign(gameState, { 
    lives:100, gold:150, wave:1, waveActive:false, gameOver:false, path:[],
    speed: 1, isPaused: false, cooldowns: { emp: 0, overclock: 0, purge: 0 }, activeEffects: { empTimer: 0, overclockTimer: 0 }
  }); 
  towers.value=[]; enemies.value=[]; projectiles.value=[]; gameMode.value='choice' 
}

onMounted(() => { 
  resizeListener = () => {
    viewportVersion.value++
    if (gameContainer.value) containerWidth.value = gameContainer.value.clientWidth
  }
  window.addEventListener('resize', resizeListener)
  setTimeout(() => { if (gameContainer.value) containerWidth.value = gameContainer.value.clientWidth }, 100)
})
onUnmounted(() => { clearInterval(gameLoopId); clearInterval(spawnInterval); window.removeEventListener('resize', resizeListener) })
</script>

<style scoped>
.gh-glass { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
.gh-panel { position: relative; overflow: hidden; }

.hologram-cell { border: 1px solid rgba(255, 255, 255, 0.05); cursor: pointer; transition: all 0.2s ease-out; position: relative; background: rgba(255, 255, 255, 0.01); }
.hologram-cell:hover { background: rgba(0, 242, 255, 0.1); box-shadow: inset 0 0 20px rgba(0, 242, 255, 0.2); border-color: rgba(0, 242, 255, 0.4); z-index: 20; }
.hologram-cell.is-path { 
  background: rgba(224, 47, 232, 0.05);
  border: 1px solid rgba(224, 47, 232, 0.15); 
  z-index: 5;
  position: relative;
}
.hologram-cell.is-path::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle, rgba(224, 47, 232, 0.1) 1px, transparent 1px);
  background-size: 8px 8px;
  opacity: 0.3;
}
.hologram-cell.is-path::before {
  content: '';
  position: absolute;
  inset: -1px;
  border: 1px solid rgba(224, 47, 232, 0.1);
  pointer-events: none;
}

.path-line-flow {
  animation: pathFlow 2s linear infinite;
}

@keyframes pathFlow {
  from { stroke-dashoffset: 20; }
  to { stroke-dashoffset: 0; }
}
.hologram-cell.is-selected { 
  border: 2px solid #00f2ff; 
  background: rgba(0, 242, 255, 0.1); 
  box-shadow: inset 0 0 20px rgba(0, 242, 255, 0.3), 0 0 10px rgba(0, 242, 255, 0.2); 
  z-index: 25; 
}
.hologram-cell.is-selected::before {
  content: '';
  position: absolute;
  inset: -4px;
  border: 1px solid rgba(0, 242, 255, 0.3);
  pointer-events: none;
}


.tower-node {
  position: absolute;
  inset: 10%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 30;
}

.tower-base-plate {
  position: absolute;
  bottom: 0;
  width: 90%;
  height: 90%;
  background: var(--tower-color);
  opacity: 0.15;
  border: 1px solid var(--tower-color);
}

.tower-icon-container {
  position: relative;
  z-index: 10;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.5);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.tower-node:hover .tower-icon-container {
  transform: translateY(-4px) scale(1.1);
  border-color: var(--tower-color);
  box-shadow: 0 0 15px var(--tower-color), 4px 4px 0 rgba(0, 0, 0, 0.8);
}

.tower-icon {
  font-size: 24px;
  color: var(--tower-color);
  filter: drop-shadow(0 0 5px var(--tower-color));
}

.tower-glow-ring {
  position: absolute;
  inset: -2px;
  border: 1px solid var(--tower-color);
  opacity: 0.3;
  animation: towerPulse 2s ease-in-out infinite;
}

@keyframes towerPulse {
  0%, 100% { transform: scale(1); opacity: 0.3; }
  50% { transform: scale(1.1); opacity: 0.6; }
}

.enemy-hologram { position: absolute; transform: translate(-50%, -50%); display: flex; align-items:center; justify-content: center; pointer-events: none; z-index: 40; transition: transform 0.1s linear; }
.enemy-body { width: 100%; height: 100%; background: var(--color); opacity: 0.9; box-shadow: 0 0 15px var(--color); border: 1px solid rgba(255,255,255,0.4); }
.s-circ .enemy-body { border-radius: 0; clip-path: circle(50% at 50% 50%); }
.s-tria .enemy-body { clip-path: polygon(50% 0%, 0% 100%, 100% 100%); }
.s-diam .enemy-body { clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
.s-squa .enemy-body { border-radius: 0; }
.s-boss .enemy-body { clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%); border: 2px solid white; box-shadow: 0 0 30px var(--color); }

.enemy-hp-bar { position: absolute; top: -8px; left: 0; width: 100%; height: 2px; background: rgba(0,0,0,0.5); }
.enemy-hp-fill { height: 100%; background: #22c55e; transition: width 0.1s; }

.energy-bolt { position: absolute; border-radius: 50%; background: var(--color); transform: translate(-50%, -50%); box-shadow: 0 0 8px var(--color); }
.hologram-range { position: absolute; border: 1px dashed rgba(0, 242, 255, 0.4); border-radius: 50%; background: rgba(0, 242, 255, 0.03); pointer-events: none; }

.bg-danger-stripes {
  background-image: repeating-linear-gradient(
    45deg,
    #ff2d55 0,
    #ff2d55 10px,
    transparent 10px,
    transparent 20px
  );
}

.bg-scan-line {
  background: linear-gradient(
    to bottom,
    transparent 0%,
    rgba(0, 242, 255, 0.1) 50%,
    transparent 100%
  );
  background-size: 100% 4px;
}

@keyframes diag-scanning {
  0% { transform: translateY(-100%); }
  100% { transform: translateY(100%); }
}

.animate-in { animation: animate-in 0.3s ease-out; }
@keyframes animate-in { from { opacity: 0; } to { opacity: 1; } }

.segmented-bar div {
  clip-path: polygon(10% 0%, 100% 0%, 90% 100%, 0% 100%);
}

.crt-ripple {
  background: linear-gradient(
    rgba(18, 16, 16, 0) 50%,
    rgba(0, 0, 0, 0.15) 50%
  );
  background-size: 100% 4px;
  animation: scan-scroll 10s linear infinite;
}

@keyframes scan-scroll {
  from { background-position: 0 0; }
  to { background-position: 0 100%; }
}

.damage-shake {
  animation: glitch-shake 0.2s linear infinite;
}

@keyframes glitch-shake {
  0% { transform: translate(0); filter: hue-rotate(0deg); }
  25% { transform: translate(4px, -4px); filter: hue-rotate(90deg) brightness(1.5); }
  50% { transform: translate(-4px, 4px); filter: hue-rotate(180deg); }
  75% { transform: translate(4px, 4px); filter: hue-rotate(270deg) brightness(1.2); }
  100% { transform: translate(0); filter: hue-rotate(360deg); }
}

.animate-spin-slow {
  animation: spin 8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-glitch-text {
  animation: text-flicker 2s linear infinite;
}

@keyframes text-flicker {
  0% { opacity: 1; }
  2% { opacity: 0.4; transform: skewX(10deg); }
  4% { opacity: 1; transform: skewX(0); }
  90% { opacity: 1; }
  91% { opacity: 0.4; }
  92% { opacity: 1; }
}

.custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0,242,255,0.2); }
.scrollbar-none::-webkit-scrollbar { display: none; }
</style>
