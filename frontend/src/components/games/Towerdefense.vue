<template>
  <div class="tower-defense h-full flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>

    <!-- Background Ambient Layers -->
    <div class="absolute inset-0 bg-gradient-to-b from-neon-cyan/5 via-transparent to-neon-pink/5 pointer-events-none"></div>

    <!-- SELECCIÓN INICIAL -->
    <div v-if="gameMode === 'choice'" class="relative z-40 flex flex-col items-center justify-center flex-1 p-6">
      <div class="gh-glass max-w-xl w-full p-10 text-center border-white/5 shadow-2xl bg-black/60">
        <div class="flex items-center justify-center gap-4 mb-8">
           <div class="h-px w-12 bg-neon-cyan"></div>
           <p class="font-pixel text-neon-cyan text-lg tracking-[0.4em] uppercase">COMANDO_ESTRATÉGICO</p>
           <div class="h-px w-12 bg-neon-cyan"></div>
        </div>
        
        <h1 class="font-display text-5xl font-black text-white mb-6 gh-title-glow tracking-tighter">DEFENSA_TORRES</h1>
        <p class="font-sans text-xs font-medium uppercase text-white/40 mb-12 max-w-sm mx-auto leading-relaxed">
          CONSTRUYE NODOS DE DEFENSA PARA PROTEGER EL NÚCLEO DEL REACTOR CONTRA LA INFILTRACIÓN DE MALWARE.
        </p>
        
        <div v-if="store.isLoading" class="py-6">
           <div class="size-12 border-t-2 border-neon-cyan rounded-full animate-spin mx-auto"></div>
           <p class="font-pixel text-neon-cyan mt-6 animate-pulse uppercase tracking-widest">CONECTANDO_NÚCLEO...</p>
        </div>
        
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button
            v-if="store.savedGame"
            @click="startGame(true)"
            class="gh-glass border-neon-blue/30 p-6 flex flex-col items-center hover:bg-neon-blue/10 transition-all group"
          >
            <span class="font-pixel text-xs text-neon-blue mb-2">RECUPERAR_ESTADO</span>
            <span class="font-display text-xl text-white font-black">SOL_OLEADA_{{ store.savedGame.wave }}</span>
          </button>
          
          <button
            @click="startGame(false)"
            class="gh-glass border-neon-cyan/30 p-6 flex flex-col items-center hover:bg-neon-cyan/10 transition-all group"
            :class="{ 'sm:col-span-2': !store.savedGame }"
          >
            <span class="font-pixel text-xs text-neon-cyan mb-2">INICIANDO_PROCESO</span>
            <span class="font-display text-xl text-white font-black">NUEVO_DESPLIEGUE</span>
          </button>
        </div>
      </div>
    </div>

    <!-- INTERFAZ DE JUEGO -->
    <template v-else-if="gameMode === 'playing'">
      <!-- HUD SUPERIOR: Glass & Minimal -->
      <header class="relative z-30 p-4 sm:p-6 flex flex-col md:flex-row items-stretch gap-4">
        <div class="flex-1 gh-glass p-4 border-white/5 bg-black/40 flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2 mb-1">
               <div class="size-1.5 rounded-full bg-neon-pink animate-pulse"></div>
               <span class="font-pixel text-xs tracking-[0.2em] opacity-40 uppercase">SECTOR_ZONA_01</span>
            </div>
            <h2 class="font-display text-xl font-black text-white uppercase tracking-tighter">HUD_BATALLA_v2</h2>
          </div>
          
          <div class="flex gap-4 sm:gap-8">
            <div class="text-right">
              <p class="font-pixel text-xs opacity-40 uppercase">INTEGRIDAD_REACTOR</p>
              <p class="font-display text-xl font-black text-neon-pink">{{ gameState.lives }}</p>
            </div>
            <div class="text-right border-l border-white/10 pl-4 sm:pl-8">
              <p class="font-pixel text-xs opacity-40 uppercase">RECURSOS_DATOS</p>
              <p class="font-display text-xl font-black text-neon-cyan">{{ gameState.gold }}</p>
            </div>
            <div class="text-right border-l border-white/10 pl-4 sm:pl-8">
              <p class="font-pixel text-xs opacity-40 uppercase">OLEADA_ACTUAL</p>
              <p class="font-display text-xl font-black text-neon-yellow">{{ gameState.wave }}</p>
            </div>
          </div>
        </div>

        <!-- Wave Controls as Glass Panel -->
        <div class="md:w-72 gh-glass p-4 bg-black/60 border-neon-cyan/20 flex flex-col justify-center">
          <div v-if="gameState.gameOver" class="text-center">
             <p class="font-pixel text-neon-pink text-xs mb-2">FALLO_CRÍITICO</p>
             <button @click="resetGameLocal" class="w-full py-2 bg-neon-pink/20 hover:bg-neon-pink/40 border border-neon-pink text-white font-pixel uppercase">APAGAR_SISTEMA</button>
          </div>
          <div v-else-if="!gameState.waveActive" class="text-center">
             <button @click="startWave" class="w-full py-3 bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest hover:scale-105 transition-all">
                LANZAR_OLEADA_{{ gameState.wave }}
             </button>
          </div>
          <div v-else class="space-y-2">
             <div class="flex justify-between font-pixel text-xs opacity-60">
                <span>OBJETIVOS_DETECTADOS</span>
                <span>{{ remainingEnemies }} RESTANTES</span>
             </div>iv>
             <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                <div class="h-full bg-neon-cyan shadow-[0_0_10px_#00f2ff] transition-all duration-300" :style="{ width: `${waveProgressPercent}%` }"></div>
             </div>
          </div>
        </div>
      </header>

      <!-- ÁREA ESTRATÉGICA CENTRADA -->
      <main class="flex-1 flex flex-col relative z-20 min-h-0 bg-transparent p-4 sm:p-6 sm:pt-0 overflow-auto custom-scroll">
        
        <div class="flex-1 flex flex-col items-center w-full max-w-5xl mx-auto gap-6 sm:gap-8">
          <!-- BATTLEFIELD: Holographic Grid -->
          <div class="gh-glass bg-black/40 border-white/5 flex items-center justify-center p-6 w-fit shadow-2xl relative">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,122,255,0.05)_0%,transparent_70%)] pointer-events-none"></div>
            
            <div class="relative border border-white/10 shadow-3xl">
               <!-- The Map Grid -->
               <div class="relative z-10" :style="{ width: (mapWidth * cellSize) + 'px', height: (mapHeight * cellSize) + 'px' }">
                  <div v-for="y in mapHeight" :key="'row-' + y" class="flex">
                    <div
                      v-for="x in mapWidth"
                      :key="'cell-' + x + '-' + y"
                      class="hologram-cell"
                      :class="{
                        'is-path': isPath(x - 1, y - 1),
                        'is-selected': selectedCell && selectedCell.x === x - 1 && selectedCell.y === y - 1,
                        'has-building': getTowerAt(x - 1, y - 1)
                      }"
                      :style="{ width: cellSize + 'px', height: cellSize + 'px' }"
                      :data-x="x - 1" :data-y="y - 1"
                      @click="handleMapClick"
                    >
                      <!-- Tower Hologram -->
                      <div v-if="getTowerAt(x - 1, y - 1)" class="tower-hologram" :style="{ '--color': getTowerAt(x - 1, y - 1).color }">
                         <div class="tower-base"></div>
                         <div class="tower-core animate-pulse"></div>
                      </div>
                    </div>
                  </div>

                  <!-- Range Indicator -->
                  <div v-if="selectedTower && selectedCell && !isPath(selectedCell.x, selectedCell.y)" class="hologram-range" :style="selectedRangeStyle"></div>

                  <!-- Enemies: Minimal Glitch Effects -->
                  <div
                    v-for="enemy in enemies"
                    :key="enemy.id"
                    class="enemy-hologram"
                    :class="[enemy.shapeClass, { 'is-slowed': enemy.slowTimer > 0, 'is-poisoned': enemy.poisonTicks > 0 }]"
                    :style="{ left: `${enemy.pixelX}px`, top: `${enemy.pixelY}px`, width: `${enemy.sizePx}px`, height: `${enemy.sizePx}px`, '--color': enemy.poisonTicks > 0 ? '#22c55e' : (enemy.slowTimer > 0 ? '#00f2ff' : enemy.baseColor) }"
                  >
                    <div class="enemy-body"></div>
                    <div class="enemy-hp-bar">
                      <div class="enemy-hp-fill" :style="{ width: `${(enemy.hp / enemy.maxHp) * 100}%` }"></div>
                    </div>
                  </div>

                  <!-- Projectiles -->
                  <div
                    v-for="projectile in projectiles"
                    :key="projectile.id"
                    class="energy-bolt"
                    :style="{ left: `${projectile.x}px`, top: `${projectile.y}px`, width: `${projectile.size}px`, height: `${projectile.size}px`, '--color': projectile.color }"
                  ></div>
               </div>
            </div>
          </div>

          <!-- LEYENDA (Movida abajo para dar respiro al mapa) -->
          <div class="w-full flex-col sm:flex-row flex gap-6 sm:gap-12 justify-center pb-6">
            <div class="gh-glass flex-1 p-5 bg-black/40 border-white/5 max-w-sm">
                <p class="font-pixel text-xs text-white/40 uppercase tracking-[0.3em] mb-4 border-b border-white/5 pb-2">LEYENDA_COMANDOS</p>
                <div class="flex flex-col gap-3 text-xs font-pixel uppercase tracking-widest text-white/60">
                   <div class="flex items-center gap-4"><div class="size-2 rounded-full bg-neon-pink"></div> INTEGRIDAD_REACTOR</div>
                   <div class="flex items-center gap-4"><div class="size-2 rounded-full bg-neon-cyan"></div> DATOS_RECURSOS</div>
                   <div class="flex items-center gap-4"><div class="size-2 border-2 border-dashed border-white/20"></div> NODOS_RANGO_SEÑAL</div>
                </div>
            </div>
            <div class="gh-glass flex-1 p-5 bg-black/40 border-white/5 max-w-md">
                <p class="font-pixel text-xs text-neon-yellow uppercase tracking-widest border-b border-white/5 pb-2 mb-4">IDENTIFICADOR_AMENAZAS</p>
                <div class="grid grid-cols-2 gap-3 opacity-60">
                  <div class="flex items-center gap-3 text-xs font-pixel"><span class="size-1.5 rounded-full bg-[#f43f5e]"></span> PROTOCOL.S_CIRC</div>
                  <div class="flex items-center gap-3 text-xs font-pixel"><span class="size-1.5 bg-[#f97316] rotate-45"></span> PROTOCOL.S_TRIA</div>
                  <div class="flex items-center gap-3 text-xs font-pixel"><span class="size-1.5 bg-[#8b5cf6] scale-125"></span> PROTOCOL.S_DIAM</div>
                  <div class="flex items-center gap-3 text-xs font-pixel"><span class="size-1.5 bg-[#94a3b8] "></span> PROTOCOL.S_SQUA</div>
                </div>
            </div>
          </div>
        </div>

      </main>
    </template>

    <!-- FLOATING TOOLTIP -->
    <Teleport to="body">
       <div v-if="selectedCell" class="fixed z-[100] w-[340px] gh-glass bg-black/95 p-0 border-white/20 shadow-4xl overflow-hidden backdrop-blur-3xl scale-in-anim" :style="tooltipPosition">
          <header class="p-4 border-b border-white/10 flex justify-between items-center bg-white/5">
             <h3 class="font-display text-xs font-black text-white uppercase tracking-widest">COORD_UNIDAD_{{ selectedCell.x }}_{{ selectedCell.y }}</h3>
             <button @click="closeTooltip" class="text-white/40 hover:text-neon-pink">✕</button>
          </header>

          <div class="p-6 overflow-auto max-h-[450px] custom-scroll">
             <!-- MENU CONSTRUCCIÓN -->
             <div v-if="!selectedTower">
                <p class="font-pixel text-xs text-neon-cyan font-bold uppercase mb-4 tracking-[0.2em]">MÓDULOS_DISPONIBLES</p>
                <div class="space-y-3">
                   <div 
                     v-for="(type, key) in towerTypes" :key="key" 
                     @click="buildTower(key)"
                     class="group relative flex items-center p-3  border border-white/10 transition-all cursor-pointer bg-white/5 hover:border-neon-cyan/40 hover:bg-white/10"
                     :class="{ 'opacity-30 grayscale cursor-not-allowed': gameState.gold < type.cost }"
                   >
                      <div class="size-10  shrink-0 border border-white/10 relative overflow-hidden mr-4" :style="{ backgroundColor: type.color }">
                         <div class="absolute inset-0 bg-gradient-to-tr from-black/40 to-transparent"></div>
                      </div>
                      <div class="flex-1 min-w-0">
                         <div class="flex justify-between items-baseline mb-0.5">
                            <span class="font-display text-xs font-black uppercase text-white group-hover:text-neon-cyan">{{ type.name }}</span>
                            <span class="font-pixel text-xs text-neon-yellow">{{ type.cost }}C</span>
                         </div>
                         <p class="font-sans text-xs font-bold text-white/40 uppercase truncate">{{ type.desc }}</p>
                      </div>
                   </div>
                </div>
             </div>

             <!-- MENU UPGRADE -->
             <div v-else class="space-y-8">
                <div class="flex items-center gap-5">
                   <div class="size-16  shrink-0 border-2 border-white/20 shadow-2xl relative overflow-hidden" :style="{ backgroundColor: selectedTower.color }">
                      <div class="absolute inset-0 bg-gradient-to-tr from-black/60 to-transparent"></div>
                   </div>
                   <div>
                      <h4 class="font-display text-lg font-black text-white uppercase leading-tight">{{ selectedTower.name }}</h4>
                      <p class="font-pixel text-xs text-neon-cyan uppercase tracking-widest">LVL_{{ selectedTower.level }}</p>
                   </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-center">
                   <div class="p-3 bg-white/5 border border-white/5 ">
                      <p class="font-pixel text-xs opacity-30 uppercase mb-1">DAÑO</p>
                      <p class="font-display text-xs font-black text-neon-pink">{{ selectedTower.damage.toFixed(1) }} <span class="text-xs ml-1 opacity-60">→ {{(selectedTower.damage * 1.4).toFixed(1)}}</span></p>
                   </div>
                   <div class="p-3 bg-white/5 border border-white/5 ">
                      <p class="font-pixel text-xs opacity-30 uppercase mb-1">RANGO_SEÑAL</p>
                      <p class="font-display text-xs font-black text-neon-cyan">{{ selectedTower.range.toFixed(1) }} <span class="text-xs ml-1 opacity-60">→ {{(selectedTower.range + 0.1).toFixed(1)}}</span></p>
                   </div>
                </div>

                <div class="flex flex-col gap-3">
                   <button 
                     @click="upgradeTower"
                     :disabled="gameState.gold < upgradeCost"
                     class="w-full py-4  bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest shadow-xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-20 disabled:scale-100"
                   >
                      ACTUALIZAR ({{ upgradeCost }}C)
                   </button>
                   <button @click="confirmSellTower" class="w-full py-2 font-pixel text-xs text-neon-pink/60 hover:text-neon-pink uppercase tracking-widest transition-all">
                      [RECICLAR_COMPONENTES]
                   </button>
                </div>
             </div>
          </div>
       </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useTowerDefenseStore } from '../../stores/games/towerdefense'

const emit = defineEmits(['live-score'])
const store = useTowerDefenseStore()

const mapWidth = 12; const mapHeight = 10; const cellSize = 50
const viewportVersion = ref(0); const gameMode = ref('choice')

const path = [
  { x: 0, y: 2 }, { x: 1, y: 2 }, { x: 2, y: 2 }, { x: 3, y: 2 }, { x: 4, y: 2 },
  { x: 4, y: 3 }, { x: 4, y: 4 }, { x: 4, y: 5 }, { x: 4, y: 6 }, { x: 4, y: 7 },
  { x: 5, y: 7 }, { x: 6, y: 7 }, { x: 7, y: 7 }, { x: 8, y: 7 }, { x: 9, y: 7 },
  { x: 9, y: 6 }, { x: 9, y: 5 }, { x: 9, y: 4 }, { x: 9, y: 3 }, { x: 10, y: 3 },
  { x: 11, y: 3 }
]

const isPath = (x, y) => path.some((p) => p.x === x && p.y === y)
const gameState = reactive({ lives: 20, gold: 150, wave: 1, waveActive: false, gameOver: false })
const towers = ref([]); const enemies = ref([]); const projectiles = ref([]); const selectedCell = ref(null); const clickPosition = ref(null)

const towerTypes = {
  basic: { name: 'Sentinel', cost: 30, range: 2.5, damage: 15, cooldownMax: 20, color: '#007aff', desc: 'Defensa Estándar', effect: 'none' },
  rapid: { name: 'Pulse_X', cost: 60, range: 2, damage: 4, cooldownMax: 4, color: '#f97316', desc: 'Protocolo de Ataque Rápido', effect: 'fast' },
  sniper: { name: 'DeadEye', cost: 100, range: 5, damage: 60, cooldownMax: 60, color: '#ff2d55', desc: 'Eliminación a Gran Distancia', effect: 'none' },
  heavy: { name: 'Nova_Can', cost: 120, range: 2.5, damage: 40, cooldownMax: 45, color: '#a855f7', desc: 'Disruptor de Área', effect: 'splash' },
  frost: { name: 'CryoNode', cost: 70, range: 2.8, damage: 8, cooldownMax: 28, color: '#00f2ff', desc: 'Bitrate Lento (-20%)', effect: 'slow' },
  poison: { name: 'BioClog', cost: 90, range: 3, damage: 10, cooldownMax: 40, color: '#22c55e', desc: 'Corrupción (Daño Continuo)', effect: 'poison' }
}

const enemyArchetypes = {
  circle: { shapeClass: 's-circ', hpMultiplier: 1, speedMultiplier: 1, sizeMultiplier: 0.45, rewardMultiplier: 1, damageReduction: 0, baseColor: '#ff2d55' },
  triangle: { shapeClass: 's-tria', hpMultiplier: 0.55, speedMultiplier: 2.1, sizeMultiplier: 0.35, rewardMultiplier: 1.6, damageReduction: 0, baseColor: '#f97316' },
  diamond: { shapeClass: 's-diam', hpMultiplier: 3.2, speedMultiplier: 0.5, sizeMultiplier: 0.72, rewardMultiplier: 2.8, damageReduction: 0.08, baseColor: '#8b5cf6' },
  square: { shapeClass: 's-squa', hpMultiplier: 1.9, speedMultiplier: 0.85, sizeMultiplier: 0.56, rewardMultiplier: 2.0, damageReduction: 0.36, baseColor: '#94a3b8' }
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

const enemiesToSpawn = ref(0); const totalWaveEnemies = ref(0)
let towerIdCounter = 0; let enemyIdCounter = 0; let projectileIdCounter = 0; let spawnInterval = null; let gameLoopId = null; let resizeListener = null

const handleMapClick = (e) => {
  e.stopPropagation(); const cell = e.target.closest('.hologram-cell'); if (!cell) return
  const x = parseInt(cell.dataset.x, 10), y = parseInt(cell.dataset.y, 10)
  if (isPath(x, y) && !getTowerAt(x, y)) { selectedCell.value = null; clickPosition.value = null; return }
  const rect = cell.getBoundingClientRect(); clickPosition.value = { x: rect.left, y: rect.top }; selectedCell.value = { x, y }
}

const closeTooltip = () => { selectedCell.value = null; clickPosition.value = null }

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
  gameState.gold -= t.cost; towers.value.push({ ...t, id: towerIdCounter++, x, y, baseCost: t.cost, totalSpent: t.cost, level: 1, cooldown: 0 })
}

const upgradeTower = () => { if (gameState.gold >= upgradeCost.value && selectedTower.value) { gameState.gold -= upgradeCost.value; selectedTower.value.totalSpent += upgradeCost.value; selectedTower.value.level++; selectedTower.value.damage *= 1.4; selectedTower.value.range += 0.1 } }
const confirmSellTower = () => { if (selectedTower.value && window.confirm(`Reciclar por ${selectedTowerSellValue.value}C?`)) { gameState.gold += selectedTowerSellValue.value; towers.value = towers.value.filter(t => t.id !== selectedTower.value.id); selectedCell.value = null } }

const startWave = () => {
  if (gameState.waveActive || gameState.gameOver) return
  gameState.waveActive = true; const stage = Math.floor((gameState.wave - 1) / 5)
  enemiesToSpawn.value = 6 + Math.floor(gameState.wave * 1.15) + (stage * 3); totalWaveEnemies.value = enemiesToSpawn.value
  const baseHp = Math.round(Math.round(28 * Math.pow(1.12, gameState.wave - 1)) * Math.pow(1.6, stage))
  const baseSpeed = 0.055 + (gameState.wave * 0.0022), baseRew = Math.max(1, Math.round(Math.max(2, Math.round(4 * Math.pow(1.06, gameState.wave - 1))) * Math.pow(1.35, stage)))
  if (spawnInterval) clearInterval(spawnInterval)
  spawnInterval = setInterval(() => {
    if (enemiesToSpawn.value > 0) {
      const arch = pickEnemyArchetype(gameState.wave), hp = Math.round(baseHp * arch.hpMultiplier), sz = Math.max(14, Math.round(cellSize * arch.sizeMultiplier))
      enemies.value.push({
        id: enemyIdCounter++, progress: 0, hp, maxHp: hp, speed: Math.min(baseSpeed * arch.speedMultiplier, 0.22), slowTimer: 0, poisonTicks: 0,
        reward: Math.max(1, Math.round(baseRew * arch.rewardMultiplier)), pixelX: (path[0].x * cellSize) + (cellSize-sz)/2, pixelY: (path[0].y * cellSize) + (cellSize-sz)/2,
        sizePx: sz, shapeClass: arch.shapeClass, damageReduction: arch.damageReduction, baseColor: arch.baseColor
      }); enemiesToSpawn.value--
    } else { clearInterval(spawnInterval); spawnInterval = null }
  }, 1000)
}

const pickEnemyArchetype = (w) => {
  const r = Math.random(); if (w < 3) return r < 0.75 ? enemyArchetypes.circle : enemyArchetypes.triangle
  if (w < 6) return r < 0.52 ? enemyArchetypes.circle : (r < 0.78 ? enemyArchetypes.triangle : (r < 0.92 ? enemyArchetypes.square : enemyArchetypes.diamond))
  return r < 0.4 ? enemyArchetypes.circle : (r < 0.65 ? enemyArchetypes.triangle : (r < 0.84 ? enemyArchetypes.square : enemyArchetypes.diamond))
}

const gameTick = () => {
  if (gameState.gameOver) return
  for (let i = enemies.value.length - 1; i >= 0; i--) {
    const e = enemies.value[i]; if (e.poisonTicks > 0) { e.hp -= 0.15; e.poisonTicks-- }
    const spd = e.slowTimer > 0 ? e.speed * 0.6 : e.speed; if (e.slowTimer > 0) e.slowTimer--
    e.progress += spd; const idx = Math.floor(e.progress)
    if (idx + 1 >= path.length) { enemies.value.splice(i,1); gameState.lives--; if (gameState.lives <= 0) handleGameOver(); continue }
    const f = e.progress - idx, cp = path[idx], np = path[idx+1], o = (cellSize - e.sizePx)/2
    e.pixelX = (cp.x + (np.x-cp.x)*f)*cellSize + o; e.pixelY = (cp.y + (np.y-cp.y)*f)*cellSize + o
    if (e.hp <= 0) { gameState.gold += e.reward; enemies.value.splice(i,1) }
  }
  towers.value.forEach(tw => {
    if (tw.cooldown > 0) { tw.cooldown--; return }
    const tc = { x:(tw.x+0.5)*cellSize, y:(tw.y+0.5)*cellSize }
    const tgt = enemies.value.find(e => Math.hypot(e.pixelX+e.sizePx/2 - tc.x, e.pixelY+e.sizePx/2 - tc.y)/cellSize <= tw.range)
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
}

async function handleGameOver() { gameState.gameOver = true; gameState.waveActive = false; emit('live-score', gameState.wave-1); try { await store.saveProgress(gameState.wave); await store.completeSession(gameState.wave,0); await store.resetGame() } catch(e){} }
async function startGame(cont) { await store.initializeTowerDefense(cont); Object.assign(gameState, store.gameState); towers.value = store.gameState.towers || []; gameMode.value = 'playing'; if (!gameLoopId) gameLoopId = setInterval(gameTick, 30) }
function resetGameLocal() { Object.assign(gameState, { lives:20, gold:150, wave:1, waveActive:false, gameOver:false }); towers.value=[]; enemies.value=[]; projectiles.value=[]; gameMode.value='choice' }

onMounted(() => { resizeListener = () => viewportVersion.value++; window.addEventListener('resize', resizeListener) })
onUnmounted(() => { clearInterval(gameLoopId); clearInterval(spawnInterval); window.removeEventListener('resize', resizeListener) })
</script>

<style scoped>
.hologram-cell { border: 1px solid rgba(255, 255, 255, 0.03); cursor: pointer; transition: all 0.3s; position: relative; }
.hologram-cell:hover { background: rgba(0, 242, 255, 0.05); }
.hologram-cell.is-path { background: rgba(255, 255, 255, 0.01); }
.hologram-cell.is-selected { border: 1px solid #00f2ff; background: rgba(0, 242, 255, 0.08); box-shadow: inset 0 0 10px rgba(0, 242, 255, 0.2); }

.tower-hologram { position: absolute; inset: 15%; display: flex; align-items: center; justify-content: center; transform-style: preserve-3d; }
.tower-base { position: absolute; bottom: 0; width: 80%; height: 4px; background: var(--color); opacity: 0.4; border-radius: 50%; }
.tower-core { width: 50%; height: 80%; background: var(--color); clip-path: polygon(50% 0%, 100% 100%, 0% 100%); box-shadow: 0 0 15px var(--color); transform: translateY(-2px); }

.enemy-hologram { position: absolute; transform: translate(-50%, -50%); display: flex; align-items:center; justify-content: center; pointer-events: none; }
.enemy-body { width: 100%; height: 100%; background: var(--color); box-shadow: 0 0 10px var(--color); }
.s-circ .enemy-body { border-radius: 99px; }
.s-tria .enemy-body { clip-path: polygon(50% 0%, 0% 100%, 100% 100%); }
.s-diam .enemy-body { clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
.s-squa .enemy-body { border-radius: 2px; }

.enemy-hp-bar { position: absolute; top: -8px; left: 0; width: 100%; height: 2px; background: rgba(0,0,0,0.5); }
.enemy-hp-fill { height: 100%; background: #22c55e; transition: width 0.1s; }

.energy-bolt { position: absolute; border-radius: 50%; background: var(--color); transform: translate(-50%, -50%); box-shadow: 0 0 8px var(--color); }
.hologram-range { position: absolute; border: 1px dashed rgba(0, 242, 255, 0.4); border-radius: 50%; background: rgba(0, 242, 255, 0.03); pointer-events: none; }

.scale-in-anim { animation: scale-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes scale-in { 0% { opacity: 0; transform: scale(0.9) translateY(10px); } 100% { opacity: 1; transform: scale(1); } }

.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
</style>
