<template>
  <div class="tower-defense gh-neon-ring">
    <!-- ───────────────────────────────────────────────────────────────
         Pantalla de elección: Continuar vs Nueva Partida
         ─────────────────────────────────────────────────────────────── -->
    <div v-if="gameMode === 'choice'" class="screen-choice">
      <div class="choice-container">
        <h1 class="choice-title">Tower Defense</h1>
        <p class="choice-subtitle">Defiende el reactor y escala la dificultad por oleadas.</p>
        
        <div v-if="store.isLoading" class="choice-loading">
          <p>Cargando...</p>
        </div>
        <div v-else class="choice-buttons">
          <button
            v-if="store.savedGame"
            @click="startGame(true)"
            class="btn-choice btn-continue"
          >
            <span class="btn-icon">▶</span>
            <span>Continuar Partida</span>
            <span class="btn-detail">Oleada {{ store.savedGame.wave }}</span>
          </button>
          
          <button
            @click="startGame(false)"
            class="btn-choice btn-new"
          >
            <span class="btn-icon">✨</span>
            <span>Nueva Partida</span>
            <span class="btn-detail">Empieza desde cero</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────
         Pantalla principal del juego
         ─────────────────────────────────────────────────────────────── -->
    <template v-else-if="gameMode === 'playing'">
    <header class="hud">
      <div class="hud-left">
        <div class="game-title">
          <p class="kicker">Modo Estrategia</p>
          <h2 class="game-name">Tower Defense</h2>
          <p class="subtitle small">Defiende el reactor y escala la dificultad por oleadas.</p>
        </div>
      </div>

      <div class="hud-right">
        <div class="stats">
        <article class="stat-card lives">
          <p class="stat-label">Vidas</p>
          <p class="stat-value">{{ gameState.lives }}</p>
        </article>
        <article class="stat-card gold">
          <p class="stat-label">Oro</p>
          <p class="stat-value">{{ gameState.gold }}</p>
        </article>
        <article class="stat-card wave">
          <p class="stat-label">Oleada</p>
          <p class="stat-value">{{ gameState.wave }}</p>
        </article>
        </div>

        <div class="wave-panel" :class="{ danger: gameState.gameOver }">
          <div v-if="gameState.gameOver">
            <p class="wave-title">Base caída</p>
            <p class="wave-text">Sobreviviste {{ gameState.wave - 1 }} oleadas.</p>
            <button @click="resetGameLocal" class="btn-start" aria-label="Reiniciar juego">Volver</button>
          </div>
          <div v-else-if="!gameState.waveActive">
            <p class="wave-title">Fase de Preparación</p>
            <p class="wave-text">Coloca y mejora torres.</p>
            <button @click="startWave" class="btn-start" aria-label="Iniciar oleada">Lanzar Oleada {{ gameState.wave }}</button>
          </div>
          <div v-else>
            <p class="wave-title">Oleada Activa ▶</p>
            <div class="wave-track">
              <div class="wave-fill" :style="{ width: `${waveProgressPercent}%` }"></div>
            </div>
            <p class="wave-text">Enemigos restantes: {{ remainingEnemies }}</p>
          </div>
        </div>
      </div>
    </header>

    <div class="game-area">
      <div class="map-wrap">
        <div class="map-shell">
          <div class="map">
            <div v-for="y in mapHeight" :key="'row-' + y" class="map-row">
              <div
                v-for="x in mapWidth"
                :key="'cell-' + x + '-' + y"
                class="cell"
                :class="{
                  path: isPath(x - 1, y - 1),
                  selected: selectedCell && selectedCell.x === x - 1 && selectedCell.y === y - 1,
                  'has-tower': getTowerAt(x - 1, y - 1)
                }"
                :data-x="x - 1"
                :data-y="y - 1"
                @click="handleMapClick"
              >
                <div v-if="getTowerAt(x - 1, y - 1)" class="tower" :style="{ backgroundColor: getTowerAt(x - 1, y - 1).color }">
                  <div class="tower-aura" :style="{ borderColor: getTowerAt(x - 1, y - 1).color }"></div>
                  <span class="tower-level">{{ getTowerAt(x - 1, y - 1).level }}</span>
                </div>
              </div>
            </div>

            <div v-if="selectedTower && selectedCell && !isPath(selectedCell.x, selectedCell.y)" class="range-ring" :style="selectedRangeStyle"></div>

            <div
              v-for="enemy in enemies"
              :key="enemy.id"
              class="enemy"
              :class="[enemy.shapeClass, { 'is-slowed': enemy.slowTimer > 0, 'is-poisoned': enemy.poisonTicks > 0 }]"
              :style="{ left: `${enemy.pixelX}px`, top: `${enemy.pixelY}px`, width: `${enemy.sizePx}px`, height: `${enemy.sizePx}px`, '--enemy-color': enemy.poisonTicks > 0 ? '#22c55e' : (enemy.slowTimer > 0 ? '#22d3ee' : enemy.baseColor) }"
            >
              <div class="enemy-shape"></div>
              <div class="hp-bar-bg">
                <div class="hp-bar-fill" :style="{ width: `${(enemy.hp / enemy.maxHp) * 100}%` }"></div>
              </div>
            </div>

            <div
              v-for="projectile in projectiles"
              :key="projectile.id"
              class="projectile"
              :class="`proj-${projectile.effect}`"
              :style="{ left: `${projectile.x}px`, top: `${projectile.y}px`, width: `${projectile.size}px`, height: `${projectile.size}px`, '--proj-color': projectile.color }"
            ></div>
          </div>
        </div>

        <div class="legend">
          <span class="legend-item"><i class="legend-dot legend-slow"></i>Ralentizado</span>
          <span class="legend-item"><i class="legend-dot legend-poison"></i>Envenenado</span>
          <span class="legend-item"><i class="legend-dot legend-path"></i>Camino</span>
          <span class="legend-item"><i class="legend-dot legend-normal"></i>Círculo (normal)</span>
          <span class="legend-item"><i class="legend-dot legend-triangle"></i>Triángulo (rápido)</span>
          <span class="legend-item"><i class="legend-dot legend-diamond"></i>Rombo (tanque)</span>
          <span class="legend-item"><i class="legend-dot legend-square"></i>Cuadrado (blindado)</span>
        </div>
      </div>

    </div>

    <teleport to="body">
      <div v-if="selectedCell" class="tooltip-panel" :style="tooltipPosition">
        <div class="tooltip-header">
          <h3 class="sidebar-title">Celda ({{ selectedCell.x }}, {{ selectedCell.y }})</h3>
          <button class="tooltip-close" @click="closeTooltip" aria-label="Cerrar">✕</button>
        </div>

        <div class="tooltip-content">
          <div v-if="!selectedTower">
            <p class="section-title">Construir Torre</p>
            <div v-for="(type, key) in towerTypes" :key="key" class="shop-item" :class="{ disabled: gameState.gold < type.cost }" @click="buildTower(key)">
              <div class="color-preview" :style="{ backgroundColor: type.color }"></div>
              <div class="item-info">
                <div class="item-head">
                  <strong>{{ type.name }}</strong>
                </div>
                <small>Daño: {{ type.damage }} | Rango: {{ type.range }}</small>
                <div class="effect-desc">{{ type.desc }}</div>
              </div>
              <div class="item-cost">{{ type.cost }}</div>
            </div>
          </div>

          <div v-else class="upgrade-panel">
            <h4>{{ selectedTower.name }} (Nvl {{ selectedTower.level }})</h4>
            <div class="stats-grid">
              <div>Daño<span>{{ selectedTower.damage.toFixed(1) }}</span></div>
              <div>Siguiente<span>{{ nextTowerDamage }}</span></div>
              <div>Rango<span>{{ selectedTower.range.toFixed(1) }}</span></div>
              <div>Siguiente<span>{{ nextTowerRange }}</span></div>
            </div>
            <p class="effect-line">Efecto activo: <span class="effect-text">{{ towerEffectLabel(selectedTower.effect) }}</span></p>
            <button class="btn-upgrade" :disabled="gameState.gold < upgradeCost" @click="upgradeTower" aria-label="Mejorar torre">Mejorar ({{ upgradeCost }})</button>
            <button class="btn-sell" @click="confirmSellTower" aria-label="Vender torre">Vender ({{ selectedTowerSellValue }})</button>
          </div>
        </div>
      </div>
    </teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useTowerDefenseStore } from '../../stores/games/towerdefense'
import gameEngineService from '../../lib/gameEngineService'

const emit = defineEmits(['live-score'])

const store = useTowerDefenseStore()

const mapWidth = 12
const mapHeight = 10
const cellSize = 50

const viewportVersion = ref(0)
const gameMode = ref('choice') // 'choice' | 'continue' | 'new' | 'playing'

const path = [
  { x: 0, y: 2 }, { x: 1, y: 2 }, { x: 2, y: 2 }, { x: 3, y: 2 }, { x: 4, y: 2 },
  { x: 4, y: 3 }, { x: 4, y: 4 }, { x: 4, y: 5 }, { x: 4, y: 6 }, { x: 4, y: 7 },
  { x: 5, y: 7 }, { x: 6, y: 7 }, { x: 7, y: 7 }, { x: 8, y: 7 }, { x: 9, y: 7 },
  { x: 9, y: 6 }, { x: 9, y: 5 }, { x: 9, y: 4 }, { x: 9, y: 3 }, { x: 10, y: 3 },
  { x: 11, y: 3 }
]

const isPath = (x, y) => path.some((p) => p.x === x && p.y === y)

const gameState = reactive({
  lives: 20,
  gold: 150,
  wave: 1,
  waveActive: false,
  gameOver: false
})

const towers = ref([])
const enemies = ref([])
const projectiles = ref([])
const selectedCell = ref(null)
const clickPosition = ref(null) // { x, y } en pixels

// Lifecycle guards para evitar saves innecesarios
let componentMountedAt = 0
let lastPlayerAction = 0

const towerTypes = {
  basic: { name: 'Básica', cost: 30, range: 2.5, damage: 15, cooldownMax: 20, color: '#38bdf8', desc: 'Equilibrada', effect: 'none' },
  rapid: { name: 'Ametralladora', cost: 60, range: 2, damage: 4, cooldownMax: 4, color: '#f59e0b', desc: 'Ataque rápido', effect: 'fast' },
  sniper: { name: 'Francotirador', cost: 100, range: 5, damage: 60, cooldownMax: 60, color: '#f43f5e', desc: 'Alto alcance', effect: 'none' },
  heavy: { name: 'Cañón', cost: 120, range: 2.5, damage: 40, cooldownMax: 45, color: '#8b5cf6', desc: 'Daño de área', effect: 'splash' },
  frost: { name: 'Hielo', cost: 70, range: 2.8, damage: 8, cooldownMax: 28, color: '#22d3ee', desc: 'Ralentiza (-20%)', effect: 'slow' },
  poison: { name: 'Veneno', cost: 90, range: 3, damage: 10, cooldownMax: 40, color: '#22c55e', desc: 'Daño en el tiempo', effect: 'poison' }
}

const enemyArchetypes = {
  circle: {
    shapeClass: 'shape-circle',
    hpMultiplier: 1,
    speedMultiplier: 1,
    sizeMultiplier: 0.45,
    rewardMultiplier: 1,
    damageReduction: 0,
    baseColor: '#f43f5e'
  },
  triangle: {
    shapeClass: 'shape-triangle',
    hpMultiplier: 0.55,
    speedMultiplier: 2.1,
    sizeMultiplier: 0.35,
    rewardMultiplier: 1.6,
    damageReduction: 0,
    baseColor: '#f97316'
  },
  diamond: {
    shapeClass: 'shape-diamond',
    hpMultiplier: 3.2,
    speedMultiplier: 0.5,
    sizeMultiplier: 0.72,
    rewardMultiplier: 2.8,
    damageReduction: 0.08,
    baseColor: '#8b5cf6'
  },
  square: {
    shapeClass: 'shape-square',
    hpMultiplier: 1.9,
    speedMultiplier: 0.85,
    sizeMultiplier: 0.56,
    rewardMultiplier: 2.0,
    damageReduction: 0.36,
    baseColor: '#94a3b8'
  }
}

const towerEffectLabel = (effect) => {
  if (effect === 'slow') return 'Ralentiza'
  if (effect === 'poison') return 'Veneno'
  if (effect === 'splash') return 'Área'
  if (effect === 'fast') return 'Rápida'
  return 'Ninguno'
}

const getTowerAt = (x, y) => towers.value.find((t) => t.x === x && t.y === y)

const selectedTower = computed(() => {
  if (!selectedCell.value) return null
  return getTowerAt(selectedCell.value.x, selectedCell.value.y)
})

const upgradeCost = computed(() => {
  if (!selectedTower.value) return 0
  return Math.floor(selectedTower.value.baseCost * Math.pow(1.5, selectedTower.value.level))
})

const selectedTowerSellValue = computed(() => {
  if (!selectedTower.value) return 0
  return Math.floor(selectedTower.value.totalSpent * 0.5)
})

const nextTowerDamage = computed(() => {
  if (!selectedTower.value) return '0'
  return (selectedTower.value.damage * 1.4).toFixed(1)
})

const nextTowerRange = computed(() => {
  if (!selectedTower.value) return '0'
  return (selectedTower.value.range + 0.1).toFixed(1)
})

const selectedRangeStyle = computed(() => {
  if (!selectedTower.value) return {}

  const diameter = selectedTower.value.range * 2 * cellSize
  const left = (selectedTower.value.x + 0.5) * cellSize - (diameter / 2)
  const top = (selectedTower.value.y + 0.5) * cellSize - (diameter / 2)

  return {
    width: `${diameter}px`,
    height: `${diameter}px`,
    left: `${left}px`,
    top: `${top}px`
  }
})

const enemiesToSpawn = ref(0)
const totalWaveEnemies = ref(0)

const remainingEnemies = computed(() => enemies.value.length + enemiesToSpawn.value)

const waveProgressPercent = computed(() => {
  if (!gameState.waveActive || totalWaveEnemies.value <= 0) return 0

  const done = totalWaveEnemies.value - remainingEnemies.value
  const progress = (done / totalWaveEnemies.value) * 100
  return Math.max(0, Math.min(progress, 100))
})

const getTowerCenter = (tower) => ({
  x: (tower.x + 0.5) * cellSize,
  y: (tower.y + 0.5) * cellSize
})

const getEnemyCenter = (enemy) => ({
  x: enemy.pixelX + (enemy.sizePx / 2),
  y: enemy.pixelY + (enemy.sizePx / 2)
})

const projectileSpeedByEffect = (effect) => {
  if (effect === 'fast') return 17
  if (effect === 'splash') return 8
  return 12
}

const projectileSizeByEffect = (effect) => {
  if (effect === 'fast') return 6
  if (effect === 'splash') return 11
  return 8
}

const pickEnemyArchetype = (wave) => {
  const roll = Math.random()

  if (wave < 3) {
    return roll < 0.75 ? enemyArchetypes.circle : enemyArchetypes.triangle
  }

  if (wave < 6) {
    if (roll < 0.52) return enemyArchetypes.circle
    if (roll < 0.78) return enemyArchetypes.triangle
    if (roll < 0.92) return enemyArchetypes.square
    return enemyArchetypes.diamond
  }

  if (roll < 0.4) return enemyArchetypes.circle
  if (roll < 0.65) return enemyArchetypes.triangle
  if (roll < 0.84) return enemyArchetypes.square
  return enemyArchetypes.diamond
}

const applyDamageToEnemy = (enemy, rawDamage) => {
  const reduction = Math.max(0, Math.min(enemy.damageReduction ?? 0, 0.85))
  const finalDamage = rawDamage * (1 - reduction)
  enemy.hp -= Math.max(finalDamage, 0.05)
}

const createEnemy = (baseHp, baseSpeed, baseReward, spawnPoint) => {
  const archetype = pickEnemyArchetype(gameState.wave)
  const maxHp = Math.round(baseHp * archetype.hpMultiplier)
  const sizePx = Math.max(14, Math.round(cellSize * archetype.sizeMultiplier))
  const offset = (cellSize - sizePx) / 2

  return {
    id: enemyIdCounter++,
    progress: 0,
    hp: maxHp,
    maxHp,
    speed: Math.min(baseSpeed * archetype.speedMultiplier, 0.22),
    slowTimer: 0,
    poisonTicks: 0,
    reward: Math.max(1, Math.round(baseReward * archetype.rewardMultiplier)),
    pixelX: (spawnPoint.x * cellSize) + offset,
    pixelY: (spawnPoint.y * cellSize) + offset,
    sizePx,
    shapeClass: archetype.shapeClass,
    damageReduction: archetype.damageReduction,
    baseColor: archetype.baseColor
  }
}

const createProjectile = (tower, target) => {
  const towerCenter = getTowerCenter(tower)

  return {
    id: projectileIdCounter++,
    x: towerCenter.x,
    y: towerCenter.y,
    targetId: target.id,
    damage: tower.damage,
    effect: tower.effect,
    color: tower.color,
    speed: projectileSpeedByEffect(tower.effect),
    size: projectileSizeByEffect(tower.effect)
  }
}

const applyProjectileImpact = (projectile, target) => {
  applyDamageToEnemy(target, projectile.damage)

  if (projectile.effect === 'slow') {
    target.slowTimer = Math.max(target.slowTimer, 60)
  }

  if (projectile.effect === 'poison') {
    target.poisonTicks = Math.max(target.poisonTicks, 120)
  }

  if (projectile.effect === 'splash') {
    const targetCenter = getEnemyCenter(target)
    enemies.value.forEach((enemy) => {
      if (enemy.id === target.id) return

      const enemyCenter = getEnemyCenter(enemy)
      const splashDistance = Math.hypot(enemyCenter.x - targetCenter.x, enemyCenter.y - targetCenter.y)
      if (splashDistance < 70) {
        applyDamageToEnemy(enemy, projectile.damage * 0.5)
      }
    })
  }
}

const tooltipPosition = computed(() => {
  // Acceder a viewportVersion para que el computed se recalcule en resize
  viewportVersion.value

  if (!selectedCell.value || !clickPosition.value) return {}

  const { x: clickX, y: clickY } = clickPosition.value
  const tooltipWidth = 320
  const tooltipHeight = 300 // estimado
  const padding = 12
  const cellCenterX = clickX + cellSize / 2
  const cellCenterY = clickY + cellSize / 2

  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  let left, top

  // Intentar posicionar a la derecha de la celda
  if (cellCenterX + padding + tooltipWidth < viewportWidth) {
    left = cellCenterX + padding
  } else if (cellCenterX - padding - tooltipWidth > 0) {
    // Si no hay lugar a la derecha, intentar a la izquierda
    left = cellCenterX - padding - tooltipWidth
  } else {
    // Si no hay lugar en ninguno de los lados, centrar en X
    left = Math.max(padding, Math.min(viewportWidth - tooltipWidth - padding, viewportWidth / 2 - tooltipWidth / 2))
  }

  // Posicionar verticalmente cerca del clic, pero asegurado dentro del viewport
  let preferredTop = cellCenterY - tooltipHeight / 2
  top = Math.max(padding, Math.min(viewportHeight - tooltipHeight - padding, preferredTop))

  return {
    left: `${left}px`,
    top: `${top}px`,
    position: 'fixed'
  }
})

const handleMapClick = (event) => {
  // evitar que el handler global de "click fuera" cierre inmediatamente
  if (event && event.stopPropagation) event.stopPropagation()

  const cell = event.target.closest('.cell')
  if (!cell) return

  const x = parseInt(cell.dataset.x, 10)
  const y = parseInt(cell.dataset.y, 10)

  // No abrir tooltip en casillas de camino (no construibles)
  if (isPath(x, y) && !getTowerAt(x, y)) {
    selectedCell.value = null
    clickPosition.value = null
    return
  }

  // Guardar la posición del clic en pixels
  const rect = cell.getBoundingClientRect()
  clickPosition.value = {
    x: rect.left,
    y: rect.top
  }

  selectedCell.value = { x, y }
}

const closeTooltip = () => {
  selectedCell.value = null
  clickPosition.value = null
}

let towerIdCounter = 0
let enemyIdCounter = 0
let projectileIdCounter = 0
let spawnInterval = null
let gameLoopId = null
let resizeListener = null
let outsideClickHandler = null

const buildTower = (typeKey) => {
  const type = towerTypes[typeKey]
  if (!type || !selectedCell.value) return

  const { x, y } = selectedCell.value
  if (isPath(x, y) || getTowerAt(x, y) || gameState.gold < type.cost) return

  gameState.gold -= type.cost
  towers.value.push({
    ...type,
    id: towerIdCounter++,
    x,
    y,
    baseCost: type.cost,
    totalSpent: type.cost,
    level: 1,
    cooldown: 0
  })
}

const upgradeTower = () => {
  if (gameState.gold >= upgradeCost.value && selectedTower.value) {
    gameState.gold -= upgradeCost.value
    selectedTower.value.totalSpent += upgradeCost.value
    selectedTower.value.level++
    selectedTower.value.damage *= 1.4
    selectedTower.value.range += 0.1
  }
}

const sellTower = () => {
  if (selectedTower.value) {
    gameState.gold += selectedTowerSellValue.value
    towers.value = towers.value.filter((tower) => tower.id !== selectedTower.value.id)
    selectedCell.value = null
  }
}

const confirmSellTower = () => {
  const confirmed = window.confirm(`¿Vender por ${selectedTowerSellValue.value} oro?`)
  if (confirmed) {
    sellTower()
  }
}

const clearSpawnLoop = () => {
  if (spawnInterval) {
    clearInterval(spawnInterval)
    spawnInterval = null
  }
}

const startWave = () => {
  if (gameState.waveActive || gameState.gameOver || path.length === 0) return

  gameState.waveActive = true

  const stage = Math.floor((gameState.wave - 1) / 5)

  enemiesToSpawn.value = 6 + Math.floor(gameState.wave * 1.15) + (stage * 3)
  totalWaveEnemies.value = enemiesToSpawn.value

  const baseHpWave = Math.round(28 * Math.pow(1.12, gameState.wave - 1))
  const baseHp = Math.max(6, Math.round(baseHpWave * Math.pow(1.6, stage)))

  const baseSpeed = 0.055 + (gameState.wave * 0.0022)

  const baseRewardWave = Math.max(2, Math.round(4 * Math.pow(1.06, gameState.wave - 1)))
  const baseReward = Math.max(1, Math.round(baseRewardWave * Math.pow(1.35, stage)))

  const spawnPoint = path[0]

  clearSpawnLoop()

  spawnInterval = setInterval(() => {
    if (enemiesToSpawn.value > 0) {
      enemies.value.push(createEnemy(baseHp, baseSpeed, baseReward, spawnPoint))
      enemiesToSpawn.value--
    } else {
      clearSpawnLoop()
    }
  }, 1000)
}

const gameTick = () => {
  if (gameState.gameOver) return

  for (let i = enemies.value.length - 1; i >= 0; i--) {
    const enemy = enemies.value[i]

    if (enemy.poisonTicks > 0) {
      enemy.hp -= 0.15
      enemy.poisonTicks--
    }

    const currentSpeed = enemy.slowTimer > 0 ? enemy.speed * 0.6 : enemy.speed
    if (enemy.slowTimer > 0) enemy.slowTimer--

    enemy.progress += currentSpeed
    const currentIndex = Math.floor(enemy.progress)
    const nextIndex = currentIndex + 1

    if (nextIndex >= path.length) {
      enemies.value.splice(i, 1)
      gameState.lives--
      if (gameState.lives <= 0) {
        gameState.gameOver = true
        emit('live-score', gameState.wave - 1)
      }
      continue
    }

    const currentPoint = path[currentIndex]
    const nextPoint = path[nextIndex]
    const fraction = enemy.progress - currentIndex

    const exactX = currentPoint.x + (nextPoint.x - currentPoint.x) * fraction
    const exactY = currentPoint.y + (nextPoint.y - currentPoint.y) * fraction

    const offset = (cellSize - enemy.sizePx) / 2
    enemy.pixelX = (exactX * cellSize) + offset
    enemy.pixelY = (exactY * cellSize) + offset

    if (enemy.hp <= 0) {
      gameState.gold += enemy.reward
      enemies.value.splice(i, 1)
    }
  }

  towers.value.forEach((tower) => {
    if (tower.cooldown > 0) {
      tower.cooldown--
      return
    }

    const towerCenter = getTowerCenter(tower)
    const target = enemies.value.find((enemy) => {
      const enemyCenter = getEnemyCenter(enemy)
      const distanceCells = Math.hypot(enemyCenter.x - towerCenter.x, enemyCenter.y - towerCenter.y) / cellSize
      return distanceCells <= tower.range
    })

    if (!target) return

    projectiles.value.push(createProjectile(tower, target))
    tower.cooldown = tower.cooldownMax
  })

  for (let i = projectiles.value.length - 1; i >= 0; i--) {
    const projectile = projectiles.value[i]
    const target = enemies.value.find((enemy) => enemy.id === projectile.targetId)

    if (!target) {
      projectiles.value.splice(i, 1)
      continue
    }

    const targetCenter = getEnemyCenter(target)
    const dx = targetCenter.x - projectile.x
    const dy = targetCenter.y - projectile.y
    const distance = Math.hypot(dx, dy)

    if (distance <= Math.max(projectile.speed, 1)) {
      projectile.x = targetCenter.x
      projectile.y = targetCenter.y
      applyProjectileImpact(projectile, target)
      projectiles.value.splice(i, 1)
      continue
    }

    if (distance > 0) {
      projectile.x += (dx / distance) * projectile.speed
      projectile.y += (dy / distance) * projectile.speed
    }
  }

  for (let i = enemies.value.length - 1; i >= 0; i--) {
    if (enemies.value[i].hp <= 0) {
      gameState.gold += Math.max(1, Math.round(enemies.value[i].reward))
      enemies.value.splice(i, 1)
    }
  }

  if (gameState.waveActive && enemiesToSpawn.value === 0 && enemies.value.length === 0) {
    gameState.waveActive = false
    totalWaveEnemies.value = 0
    clearSpawnLoop()
    gameState.wave++
    
    // Autosave al terminar onda
    saveWaveProgress()
  }
  
  // Si vidas llegan a 0, game over
  if (gameState.lives <= 0 && !gameState.gameOver) {
    handleGameOver()
  }
}

/**
 * Guarda el progreso (autosave al terminar onda)
 */
async function saveWaveProgress() {
  try {
    const response = await store.saveProgress(gameState.wave)
    if (response?.achievements_unlocked && response.achievements_unlocked.length > 0) {
      console.log('Achievements unlocked:', response.achievements_unlocked)
    }
  } catch (error) {
    console.error('Error saving wave progress:', error)
  }
}

/**
 * Maneja el game over y guarda la partida
 */
async function handleGameOver() {
  gameState.gameOver = true
  gameState.waveActive = false
  
  try {
    // Guardar estado final
    const response = await store.saveProgress(gameState.wave)
    if (response?.achievements_unlocked) {
      console.log('Final achievements:', response.achievements_unlocked)
    }
    
    // Completar sesión
    await store.completeSession(gameState.wave, 0)
    
    // Borrar guardado
    await store.resetGame()
  } catch (error) {
    console.error('Error handling game over:', error)
  }
}

/**
 * Inicia el juego con las opciones del usuario
 */
async function startGame(continueGame = false) {
  await store.initializeTowerDefense(continueGame)
  
  // Copiar estado del store al componente
  Object.assign(gameState, store.gameState)
  towers.value = store.gameState.towers || []
  
  gameMode.value = 'playing'
  
  // Iniciar el game loop cuando el juego comienza
  if (!gameLoopId) {
    gameLoopId = setInterval(gameTick, 30)  // GameLoop a 30ms
  }
}

const resetGameLocal = () => {
  Object.assign(gameState, {
    lives: 20,
    gold: 150,
    wave: 1,
    waveActive: false,
    gameOver: false
  })

  enemiesToSpawn.value = 0
  totalWaveEnemies.value = 0
  enemyIdCounter = 0
  towerIdCounter = 0
  projectileIdCounter = 0
  towers.value = []
  enemies.value = []
  projectiles.value = []
  selectedCell.value = null
  clickPosition.value = null
  clearSpawnLoop()
  emit('live-score', 0)
  gameMode.value = 'choice'
}

watch(
  () => gameState.wave,
  (currentWave) => {
    emit('live-score', Math.max(currentWave - 1, 0))
  },
  { immediate: true }
)

onMounted(async () => {
  componentMountedAt = Date.now()
  
  // Cargar si hay partida guardada (esto solo marca si existe, no inicia el juego)
  await store.loadGame()
  
  // El game loop solo debería iniciarse cuando el juego empieza
  // Por ahora es null; se iniciará en startGame()
  
  // Reposicionar tooltip cuando se redimensiona la ventana
  resizeListener = () => {
    viewportVersion.value++
  }
  window.addEventListener('resize', resizeListener)
  
  // Cerrar tooltip al hacer click fuera de él
  outsideClickHandler = (e) => {
    // si no hay tooltip abierto, nada que hacer
    const tooltip = document.querySelector('.tooltip-panel')
    if (!tooltip) return

    // si el click ocurrió dentro del tooltip, no cerramos
    if (e.target.closest && e.target.closest('.tooltip-panel')) return

    selectedCell.value = null
    clickPosition.value = null
  }
  document.addEventListener('click', outsideClickHandler)
})

onUnmounted(() => {
  if (gameLoopId) clearInterval(gameLoopId)
  clearSpawnLoop()
  if (resizeListener) window.removeEventListener('resize', resizeListener)
  if (outsideClickHandler) document.removeEventListener('click', outsideClickHandler)
  
  // Solo guardar si el usuario estuvo jugando al menos 5 segundos
  const timeSinceMount = Date.now() - componentMountedAt
  if (timeSinceMount >= 5_000 || lastPlayerAction > 0) {
    console.log(`[TowerDefense] onUnmounted save (${timeSinceMount}ms in game)`)
    store.saveProgress(gameState.wave)
  } else {
    console.log(`[TowerDefense] onUnmounted skipped save (${timeSinceMount}ms < 5s threshold)`)
  }
})
</script>

<style scoped>
.tower-defense {
  --td-cyan: #22d3ee;
  --td-violet: #8b5cf6;
  --td-amber: #f59e0b;
  --td-rose: #f43f5e;
  --td-slate: #0f172a;
  --td-border: rgba(148, 163, 184, 0.3);
  position: relative;
  overflow: hidden;
  max-width: 980px;
  margin: 0 auto;
  padding: 18px;
  border-radius: 1.25rem;
  border: 1px solid var(--td-border);
  color: #e2e8f0;
  background:
    radial-gradient(75rem 30rem at -10% -25%, rgba(34, 211, 238, 0.2), transparent 55%),
    radial-gradient(65rem 26rem at 110% -15%, rgba(139, 92, 246, 0.2), transparent 55%),
    linear-gradient(165deg, rgba(15, 23, 42, 0.97), rgba(2, 6, 23, 0.98));
  font-family: 'Rajdhani', sans-serif;
  user-select: none;
}

.tower-defense::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
    linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
  background-size: 28px 28px;
  mask-image: radial-gradient(circle at 50% 45%, black 35%, transparent 100%);
}

.hud {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 8px 10px;
  margin-bottom: 16px;
  background: linear-gradient(180deg, rgba(255,255,255,0.02), transparent);
  border-radius: 0.85rem;
}

.hud-intro,
.wave-panel {
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 0.95rem;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(8px);
  padding: 12px 14px;
}

.kicker {
  margin: 0;
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.22em;
  color: rgba(34, 211, 238, 0.9);
  font-weight: 700;
}

.title {
  margin: 4px 0 2px;
  font-family: 'Syne', 'Rajdhani', sans-serif;
  font-size: 1.45rem;
  line-height: 1.1;
  color: #f8fafc;
}

.subtitle {
  margin: 0;
  font-size: 0.84rem;
  color: rgba(203, 213, 225, 0.85);
}

.stats {
  display: flex;
  gap: 10px;
  align-items: center;
}
/* Layout del header: zonas izquierda/derecha y tipografía */
.hud-left .game-title {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.hud-right {
  display: flex;
  gap: 12px;
  align-items: center;
}

.kicker {
  margin: 0;
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: rgba(34, 211, 238, 0.95);
  font-weight: 800;
}

.game-name {
  margin: 0;
  font-size: 1.15rem;
  line-height: 1;
  color: #f8fafc;
  font-weight: 800;
}

.subtitle.small {
  margin: 0;
  font-size: 0.78rem;
  color: rgba(203, 213, 225, 0.86);
}

.stat-card {
  border-radius: 0.9rem;
  border: 1px solid rgba(148, 163, 184, 0.14);
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(2,6,23,0.55));
  padding: 10px 14px;
  min-width: 96px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-label {
  margin: 0;
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  opacity: 0.9;
  color: #e2e8f0;
}

.stat-value {
  margin: 6px 0 0;
  font-size: 1.25rem;
  font-weight: 900;
  color: #f8fafc;
}

.stat-card.lives .stat-value {
  color: #fb7185;
}

.stat-card.gold .stat-value {
  color: #facc15;
}

.stat-card.wave .stat-value {
  color: #22d3ee;
}

.wave-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 0.9rem;
  background: linear-gradient(125deg, rgba(34,211,238,0.08), rgba(139,92,246,0.06));
  border: 1px solid rgba(148,163,184,0.12);
}

.wave-panel.danger {
  border-color: rgba(244, 63, 94, 0.45);
  background: rgba(76, 5, 25, 0.45);
}

.wave-title {
  margin: 0;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-weight: 700;
  color: rgba(203, 213, 225, 0.95);
}

.wave-text {
  margin: 0;
  font-size: 0.88rem;
  color: rgba(226, 232, 240, 0.88);
}

.wave-track {
  position: relative;
  height: 10px;
  border-radius: 999px;
  background: rgba(2, 6, 23, 0.85);
  overflow: hidden;
  border: 1.5px solid rgba(34, 211, 238, 0.35);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
  margin: 8px 0;
}

.wave-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--td-cyan), var(--td-violet));
  transition: width 0.3s ease;
  box-shadow: 0 0 12px rgba(34, 211, 238, 0.6);
}

.btn-start,
.btn-upgrade,
.btn-sell {
  border: none;
  border-radius: 0.65rem;
  padding: 10px 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
}

.btn-start {
  color: #e2e8f0;
  background: linear-gradient(125deg, rgba(34, 211, 238, 0.85), rgba(139, 92, 246, 0.88));
  box-shadow: 0 8px 24px rgba(14, 116, 144, 0.32);
}

.btn-start:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(14, 116, 144, 0.5);
}

.btn-start:active {
  transform: translateY(0);
}

.btn-upgrade {
  width: 100%;
  color: #0f172a;
  background: linear-gradient(125deg, #22d3ee, #67e8f9);
  box-shadow: 0 4px 12px rgba(34, 211, 238, 0.25);
}

.btn-upgrade:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(34, 211, 238, 0.4);
}

.btn-upgrade:active:not(:disabled) {
  transform: translateY(0);
}

.btn-upgrade:disabled {
  filter: grayscale(0.7);
  opacity: 0.45;
  cursor: not-allowed;
}

.btn-sell {
  width: 100%;
  color: #fff;
  background: linear-gradient(125deg, #fb7185, #f43f5e);
  box-shadow: 0 4px 12px rgba(249, 115, 115, 0.25);
  margin-top: 8px;
}

.btn-sell:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(249, 115, 115, 0.4);
}

.btn-sell:active {
  transform: translateY(0);
}



.btn-start:hover,
.btn-upgrade:hover,
.btn-sell:hover {
  transform: translateY(-1px);
}

.game-area {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

.map-wrap {
  min-width: 0;
}

.map-shell {
  border-radius: 1rem;
  border: 1px solid rgba(148, 163, 184, 0.22);
  background: rgba(15, 23, 42, 0.55);
  padding: 10px;
  overflow-x: auto;
  display: flex;
  justify-content: center;
}

.map {
  position: relative;
  width: 90vw;
  max-width: 600px;
  height: 500px;
  border-radius: 0.65rem;
  border: 1px solid rgba(148, 163, 184, 0.22);
  overflow: hidden;
  background:
    radial-gradient(circle at 30% 12%, rgba(34, 211, 238, 0.14), transparent 55%),
    linear-gradient(180deg, rgba(51, 65, 85, 0.75), rgba(30, 41, 59, 0.9));
  display: flex;
  flex-direction: column;
  margin: 0 auto;
}

.map-row {
  display: flex;
}

.cell {
  width: 50px;
  height: 50px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  box-sizing: border-box;
  position: relative;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.cell:hover {
  background-color: rgba(148, 163, 184, 0.16);
}

.cell.path {
  border: none;
  background: linear-gradient(145deg, rgba(245, 158, 11, 0.85), rgba(217, 119, 6, 0.82));
  box-shadow: inset 0 0 0 1px rgba(254, 215, 170, 0.4);
}

.cell.selected {
  box-shadow: inset 0 0 0 2px rgba(34, 211, 238, 0.92);
  background: rgba(34, 211, 238, 0.2);
}

.tower {
  width: 80%;
  height: 80%;
  margin: 10%;
  border-radius: 0.55rem;
  border: 1px solid rgba(255, 255, 255, 0.36);
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.5);
}

.tower-aura {
  position: absolute;
  width: 116%;
  height: 116%;
  border: 1px solid;
  border-radius: 50%;
  top: -8%;
  left: -8%;
  opacity: 0.25;
  animation: auraPulse 2.3s ease-in-out infinite;
}

@keyframes auraPulse {
  0%,
  100% {
    transform: scale(0.94);
    opacity: 0.2;
  }
  50% {
    transform: scale(1.06);
    opacity: 0.38;
  }
}

.tower-level {
  position: absolute;
  bottom: -4px;
  right: -4px;
  min-width: 24px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.95);
  border: 2px solid #22d3ee;
  color: #e0f2fe;
  font-size: 0.85rem;
  font-weight: 700;
  text-align: center;
  padding: 2px 5px;
}

.range-ring {
  position: absolute;
  pointer-events: none;
  border-radius: 50%;
  border: 1px dashed rgba(34, 211, 238, 0.62);
  background: radial-gradient(circle, rgba(34, 211, 238, 0.08), transparent 70%);
  z-index: 5;
}

.enemy {
  position: absolute;
  z-index: 10;
  transform: translateZ(0);
}

.enemy-shape {
  width: 100%;
  height: 100%;
  background: var(--enemy-color);
  box-shadow:
    0 0 12px rgba(15, 23, 42, 0.6),
    inset 0 -2px 5px rgba(15, 23, 42, 0.45);
  transition: background 0.25s ease;
}

.enemy.shape-circle .enemy-shape {
  border-radius: 50%;
}

.enemy.shape-triangle .enemy-shape {
  clip-path: polygon(50% 4%, 5% 96%, 95% 96%);
}

.enemy.shape-diamond .enemy-shape {
  clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
}

.enemy.shape-square .enemy-shape {
  border-radius: 0.3rem;
}

.enemy.is-slowed {
  filter: drop-shadow(0 0 4px rgba(34, 211, 238, 0.95));
}

.enemy.is-poisoned {
  filter: drop-shadow(0 0 4px rgba(34, 197, 94, 0.95));
}

.hp-bar-bg {
  position: absolute;
  top: -8px;
  left: -10%;
  width: 120%;
  height: 4px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.78);
  overflow: hidden;
}

.hp-bar-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #4ade80, #22c55e);
  transition: width 0.1s;
}

.projectile {
  position: absolute;
  z-index: 12;
  border-radius: 999px;
  background: var(--proj-color);
  transform: translate(-50%, -50%);
  pointer-events: none;
  box-shadow:
    0 0 12px color-mix(in srgb, var(--proj-color) 65%, white 35%),
    0 0 22px color-mix(in srgb, var(--proj-color) 72%, transparent 28%);
  animation: projectilePulse 0.24s ease-in-out infinite alternate;
}

.projectile::after {
  content: '';
  position: absolute;
  inset: -40%;
  border-radius: 999px;
  background: radial-gradient(circle, color-mix(in srgb, var(--proj-color) 45%, white 55%), transparent 72%);
  opacity: 0.38;
}

.projectile.proj-fast {
  animation-duration: 0.12s;
}

.projectile.proj-splash {
  border-radius: 35%;
}

@keyframes projectilePulse {
  from {
    transform: translate(-50%, -50%) scale(0.92);
  }
  to {
    transform: translate(-50%, -50%) scale(1.08);
  }
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 0.81rem;
  letter-spacing: 0.02em;
  color: #cbd5e1;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(15, 23, 42, 0.6);
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend-slow {
  background: #22d3ee;
}

.legend-poison {
  background: #22c55e;
}

.legend-path {
  background: #f59e0b;
}

.legend-normal {
  background: #f43f5e;
}

.legend-triangle {
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-bottom: 8px solid #f97316;
  border-radius: 0;
  background: transparent;
}

.legend-diamond {
  border-radius: 0;
  background: #8b5cf6;
  transform: rotate(45deg);
}

.legend-square {
  border-radius: 2px;
  background: #94a3b8;
}

.tooltip-panel {
  position: fixed;
  border-radius: 1rem;
  border: 2px solid rgba(34, 211, 238, 0.45);
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.95));
  backdrop-filter: blur(12px);
  padding: 14px;
  width: 320px;
  max-height: 55vh;
  overflow-y: auto;
  box-shadow: 
    0 20px 60px rgba(15, 23, 42, 0.95),
    0 0 40px rgba(34, 211, 238, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
  z-index: 9999;
  pointer-events: auto;
  animation: tooltipSlideIn 0.15s ease-out;
}

@keyframes tooltipSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.sidebar-title {
  margin: 0;
  font-size: 1.05rem;
  color: #f8fafc;
}

.section-title {
  margin: 8px 0 12px;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-size: 0.68rem;
  color: rgba(34, 211, 238, 0.9);
  font-weight: 700;
}

.shop-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  margin-bottom: 10px;
  border-radius: 0.7rem;
  border: 1px solid rgba(34, 211, 238, 0.25);
  background: linear-gradient(125deg, rgba(15, 23, 42, 0.7), rgba(30, 41, 59, 0.8));
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.shop-item:hover:not(.disabled) {
  transform: translateY(-2px);
  border-color: rgba(34, 211, 238, 0.65);
  background: linear-gradient(125deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.9));
  box-shadow: 0 4px 16px rgba(34, 211, 238, 0.2);
}

.shop-item.disabled {
  opacity: 0.48;
  filter: grayscale(0.5);
  cursor: not-allowed;
}

.color-preview {
  width: 20px;
  height: 20px;
  border-radius: 0.4rem;
  border: 1px solid rgba(255, 255, 255, 0.38);
  flex: 0 0 auto;
}

.item-info {
  flex-grow: 1;
  min-width: 0;
}

.item-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}

.item-head strong {
  color: #f8fafc;
  font-size: 0.97rem;
}

.effect-chip {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  border-radius: 999px;
  padding: 2px 7px;
  border: 1px solid rgba(34, 211, 238, 0.5);
  color: #a5f3fc;
}

.item-info small {
  display: block;
  margin-top: 3px;
  color: rgba(203, 213, 225, 0.9);
  font-size: 0.78rem;
}

.effect-desc {
  margin-top: 2px;
  color: rgba(250, 204, 21, 0.95);
  font-size: 0.72rem;
}

.item-cost {
  color: #facc15;
  font-weight: 700;
  font-size: 1.25rem;
  white-space: nowrap;
}

/* Header inside tooltip so the close button scrolls with content */
.tooltip-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.tooltip-close {
  position: relative;
  width: 36px;
  height: 36px;
  border: 1px solid rgba(244, 63, 94, 0.35);
  border-radius: 50%;
  background: rgba(244, 63, 94, 0.12);
  color: #fb7185;
  cursor: pointer;
  font-size: 1.1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.18s ease;
  flex: 0 0 auto;
}

.tooltip-close:hover {
  background: rgba(244, 63, 94, 0.28);
  border-color: rgba(244, 63, 94, 0.65);
  transform: rotate(90deg);
}

.tooltip-close:active {
  transform: rotate(90deg) scale(0.95);
}

.tooltip-panel .item-cost {
  font-size: 1.2rem;
}

.upgrade-panel h4 {
  margin: 0 0 8px;
  color: #f8fafc;
  font-size: 1.05rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin-bottom: 10px;
}

.stats-grid div {
  border-radius: 0.65rem;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: rgba(30, 41, 59, 0.65);
  padding: 7px;
  font-size: 0.76rem;
  color: rgba(226, 232, 240, 0.82);
}

.stats-grid span {
  display: block;
  margin-top: 3px;
  color: #f8fafc;
  font-size: 0.98rem;
  font-weight: 700;
}

.effect-line {
  margin: 0 0 10px;
  font-size: 0.84rem;
  color: rgba(203, 213, 225, 0.9);
}

.effect-text {
  color: #22d3ee;
  font-weight: 700;
}

/* close button positioned inside header so it scrolls with content (keep earlier .tooltip-close rules) */

@media (max-width: 1024px) {
  .hud {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    padding: 8px;
  }

  .hud-right {
    justify-content: space-between;
    align-items: center;
  }

  .stats {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 1024px) {
  .game-area {
    grid-template-columns: 1fr;
  }
}

/* ─────────────────────────────────────────────────────────────────────── */
/* Pantalla de elección */
/* ─────────────────────────────────────────────────────────────────────── */

.screen-choice {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 500px;
}

.choice-container {
  text-align: center;
  max-width: 500px;
}

.choice-title {
  margin: 0 0 8px;
  font-size: 2.5rem;
  font-weight: 900;
  background: linear-gradient(135deg, #22d3ee, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.choice-subtitle {
  margin: 0 0 32px;
  font-size: 1rem;
  color: rgba(203, 213, 225, 0.9);
}

.choice-loading {
  margin: 30px 0;
  font-size: 1.1rem;
  color: rgba(226, 232, 240, 0.8);
}

.choice-buttons {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.btn-choice {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px 24px;
  border-radius: 1rem;
  border: 2px solid;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
}

.btn-choice .btn-icon {
  font-size: 1.8rem;
}

.btn-choice .btn-detail {
  display: block;
  font-size: 0.8rem;
  font-weight: 400;
  opacity: 0.8;
  margin-top: 4px;
}

.btn-continue {
  color: #e2e8f0;
  border-color: rgba(34, 211, 238, 0.7);
  background: linear-gradient(125deg, rgba(34, 211, 238, 0.15), rgba(139, 92, 246, 0.15));
}

.btn-continue:hover {
  border-color: rgba(34, 211, 238, 1);
  background: linear-gradient(125deg, rgba(34, 211, 238, 0.25), rgba(139, 92, 246, 0.25));
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(34, 211, 238, 0.2);
}

.btn-new {
  color: #0f172a;
  border-color: rgba(34, 211, 238, 0.9);
  background: linear-gradient(125deg, #22d3ee, #67e8f9);
}

.btn-new:hover {
  border-color: rgba(34, 211, 238, 1);
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(34, 211, 238, 0.4);
}

@media (max-width: 640px) {
  .tower-defense {
    padding: 12px;
  }

  .stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .title {
    font-size: 1.2rem;
  }

  .choice-title {
    font-size: 2rem;
  }

  .choice-subtitle {
    font-size: 0.9rem;
  }
}
</style>

<!-- Light Mode Styles (Global, not scoped) -->
<style>
html:not(.dark) .tower-defense {
  background:
    radial-gradient(75rem 30rem at -10% -25%, rgba(34, 211, 238, 0.08), transparent 55%),
    radial-gradient(65rem 26rem at 110% -15%, rgba(139, 92, 246, 0.08), transparent 55%),
    linear-gradient(165deg, rgba(255, 255, 255, 0.95), rgba(240, 249, 255, 0.98)) !important;
  color: #1e293b !important;
  border-color: rgba(51, 65, 85, 0.3) !important;
}

html:not(.dark) .hud-intro,
html:not(.dark) .wave-panel {
  background: rgba(248, 250, 252, 0.8) !important;
  border-color: rgba(51, 65, 85, 0.3) !important;
  color: #1f2937 !important;
}

html:not(.dark) .stat-card {
  background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(240, 249, 255, 0.9)) !important;
  border-color: rgba(51, 65, 85, 0.25) !important;
}

html:not(.dark) .stat-label {
  color: #1f2937 !important;
}

html:not(.dark) .stat-value {
  color: #0f172a !important;
}

html:not(.dark) .btn-start,
html:not(.dark) .btn-upgrade,
html:not(.dark) .btn-sell {
  color: #fff !important;
}

html:not(.dark) .map-shell {
  background: rgba(240, 249, 255, 0.7) !important;
  border-color: rgba(100, 116, 139, 0.3) !important;
}

html:not(.dark) .map {
  background:
    radial-gradient(circle at 30% 12%, rgba(34, 211, 238, 0.1), transparent 55%),
    linear-gradient(180deg, rgba(226, 232, 240, 0.8), rgba(203, 213, 225, 0.9)) !important;
}

html:not(.dark) .tooltip-panel {
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(240, 249, 255, 0.95)) !important;
  border-color: rgba(34, 211, 238, 0.4) !important;
  color: #1e293b !important;
}

html:not(.dark) .kicker {
  color: #0369a1 !important;
}

html:not(.dark) .game-name,
html:not(.dark) .subtitle,
html:not(.dark) .wave-title,
html:not(.dark) .wave-text {
  color: #0f172a !important;
}

html:not(.dark) .section-title {
  color: #0369a1 !important;
}

html:not(.dark) .sidebar-title {
  color: #0f172a !important;
}

html:not(.dark) .choice-subtitle,
html:not(.dark) .choice-loading {
  color: #334155 !important;
}

html:not(.dark) .btn-new {
  color: #000 !important;
  border-color: #0891b2 !important;
  background: linear-gradient(125deg, #06b6d4, #0891b2) !important;
}

html:not(.dark) .btn-new:hover {
  border-color: #0891b2 !important;
  box-shadow: 0 12px 30px rgba(6, 182, 212, 0.4) !important;
}

html:not(.dark) .btn-continue {
  color: #0f172a !important;
  border-color: #0891b2 !important;
  background: linear-gradient(125deg, rgba(6, 182, 212, 0.3), rgba(139, 92, 246, 0.2)) !important;
}
</style>
