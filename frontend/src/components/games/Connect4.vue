<template>
  <div class="c4-wrapper">
    <!-- Background grid -->
    <div class="bg-grid"></div>

    <div class="game-container">
      <!-- Header -->
      <header class="game-header">
        <div class="logo">
          <span class="logo-c">C</span><span class="logo-4">4</span>
          <div class="logo-sub">CONNECT FOUR</div>
        </div>
        <span class="mode-pill">IA TACTICA</span>
      </header>

      <!-- Status bar -->
      <div class="status-bar">
        <div class="turn-indicator" :class="{ 'ai-turn': currentTurn === 'ai', 'player-turn': currentTurn === 'player' }">
          <div class="turn-dot" :class="currentTurn === 'player' ? 'dot-player' : 'dot-ai'"></div>
          <span class="turn-text">{{ statusText }}</span>
          <div v-if="isAiThinking" class="thinking-dots">
            <span></span><span></span><span></span>
          </div>
        </div>
        <div class="score-board">
          <div class="score-item">
            <span class="score-label">TÚ</span>
            <span class="score-value player-color">{{ scores.player }}</span>
          </div>
          <div class="score-divider">VS</div>
          <div class="score-item">
            <span class="score-label">IA</span>
            <span class="score-value ai-color">{{ scores.ai }}</span>
          </div>
        </div>
      </div>

      <div class="meta-bar">
        <span>Tiempo: {{ formatSessionDuration(sessionElapsedSeconds) }}</span>
        <span>
          Guardado:
          {{ connect4.lastSaved ? connect4.lastSaved.toLocaleTimeString() : 'pendiente' }}
        </span>
      </div>

      <p v-if="connect4.error" class="sync-error">{{ connect4.error }}</p>

      <!-- Column hover indicators -->
      <div class="column-indicators">
        <div
          v-for="colIndex in COLS"
          :key="`indicator-${colIndex}`"
          class="col-indicator"
          :class="{ active: hoveredCol === (colIndex - 1) && currentTurn === 'player' && gameState === 'playing' }"
          @mouseenter="hoveredCol = colIndex - 1"
          @mouseleave="hoveredCol = -1"
          @click="dropPiece(colIndex - 1)"
        >
          <div class="indicator-arrow" v-show="hoveredCol === (colIndex - 1) && currentTurn === 'player' && gameState === 'playing'">▼</div>
        </div>
      </div>

      <!-- Board -->
      <div class="board-frame">
        <div class="board">
          <div
            v-for="(row, r) in board"
            :key="r"
            class="board-row"
          >
            <div
              v-for="(cell, c) in row"
              :key="c"
              class="cell"
              :class="{
                'cell-player': cell === 'player',
                'cell-ai': cell === 'ai',
                'cell-win': isWinCell(r, c),
                'cell-drop': dropAnimations[`${r}-${c}`]
              }"
              @mouseenter="hoveredCol = c"
              @mouseleave="hoveredCol = -1"
              @click="dropPiece(c)"
            >
              <div class="cell-inner">
                <div class="piece" v-if="cell">
                  <div class="piece-shine"></div>
                  <div class="piece-glow"></div>
                </div>
                <div class="hole" v-else></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Win overlay -->
      <transition name="overlay-fade">
        <div class="win-overlay" v-if="gameState === 'won' || gameState === 'draw'">
          <div class="win-card">
            <div class="win-icon">{{ gameState === 'draw' ? '🤝' : winner === 'player' ? '🏆' : '🤖' }}</div>
            <div class="win-title">
              {{ gameState === 'draw' ? 'EMPATE' : winner === 'player' ? '¡GANASTE!' : 'IA GANA' }}
            </div>
            <div class="win-sub">{{ gameState === 'draw' ? 'Nadie ha ganado esta vez' : winner === 'player' ? '¡Excelente jugada!' : 'La máquina te ha derrotado' }}</div>
            <button class="play-again-btn" @click="resetGame">JUGAR DE NUEVO</button>
          </div>
        </div>
      </transition>

      <!-- New game button -->
      <button class="new-game-btn" @click="resetGame" v-if="gameState === 'playing'">
        ↺ NUEVA PARTIDA
      </button>
    </div>
  </div>

  <Teleport to="body">
    <div class="achievement-toast-stack">
      <TransitionGroup name="toast-fade">
        <div
          v-for="toast in toastQueue"
          :key="toast.id"
          class="achievement-toast"
        >
          <div class="achievement-icon">🏆</div>
          <div class="achievement-content">
            <p class="achievement-kicker">Logro desbloqueado</p>
            <p class="achievement-title">{{ toast.title }}</p>
            <p class="achievement-desc">{{ toast.description }}</p>
          </div>
          <button class="achievement-close" @click="dismissToast(toast.id)" aria-label="Cerrar">✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, reactive, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useConnect4Store } from '../../stores/games/connect4'

const emit = defineEmits(['live-score'])

const ROWS = 6
const COLS = 7
const AI_DEPTH = 5
const AI_RESPONSE_DELAY_MS = 380
const connect4 = useConnect4Store()

const board = ref(createEmptyBoard())
const currentTurn = ref('player')
const gameState = ref('playing') // playing | won | draw
const winner = ref(null)
const winCells = ref([])
const hoveredCol = ref(-1)
const isAiThinking = ref(false)
const dropAnimations = reactive({})
const toastQueue = ref([])
const sessionElapsedSeconds = ref(0)
const scores = computed(() => ({ player: connect4.wins, ai: connect4.losses }))

let saveInterval = null
let sessionClockInterval = null
let toastTimers = []
let componentMountedAt = 0
let lastPlayerAction = 0

watch(() => connect4.newAchievements.length, () => {
  while (connect4.newAchievements.length) {
    const achievement = connect4.newAchievements.shift()
    const id = Date.now() + Math.random()
    toastQueue.value.push({ id, ...achievement })
    const timer = setTimeout(() => dismissToast(id), 10_000)
    toastTimers.push(timer)
  }
})

// Callback helpers
let lastSaveTime = 0
const SAVE_MIN_INTERVAL_MS = 5_000

function throttledSaveStats() {
  const now = Date.now()
  if (now - lastSaveTime < SAVE_MIN_INTERVAL_MS) {
    console.log(`[Connect4] save throttled: ${now - lastSaveTime}ms since last save`)
    return
  }
  lastSaveTime = now
  console.log(`[Connect4] save executed`)
  connect4.saveStats()
}

function dismissToast(id) {
  toastQueue.value = toastQueue.value.filter(toast => toast.id !== id)
}

function saveOnHide() {
  if (document.visibilityState === 'hidden') {
    console.log('[Connect4] visibilitychange: hidden ->', 'throttledSaveStats()')
    throttledSaveStats()
  }
}

function saveOnUnload() {
  console.log('[Connect4] beforeunload ->', 'throttledSaveStats()')
  throttledSaveStats()
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

onMounted(async () => {
  componentMountedAt = Date.now()
  await connect4.initializeGame(true)
  sessionElapsedSeconds.value = connect4.getSessionDurationSeconds()

  sessionClockInterval = setInterval(() => {
    sessionElapsedSeconds.value = connect4.getSessionDurationSeconds()
  }, 1000)

  // Auto-guardado cada 30 s usando throttledSaveStats
  saveInterval = setInterval(() => {
    console.log('[Connect4] autosave interval triggered')
    throttledSaveStats()
  }, 30_000)

  document.addEventListener('visibilitychange', saveOnHide)
  window.addEventListener('beforeunload', saveOnUnload)
})

onUnmounted(() => {
  clearInterval(saveInterval)
  clearInterval(sessionClockInterval)
  toastTimers.forEach(clearTimeout)
  document.removeEventListener('visibilitychange', saveOnHide)
  window.removeEventListener('beforeunload', saveOnUnload)
  
  // Solo guardar si el usuario estuvo jugando al menos 5 segundos
  const timeSinceMount = Date.now() - componentMountedAt
  if (timeSinceMount >= 5_000 || lastPlayerAction > 0) {
    console.log(`[Connect4] onUnmounted save (${timeSinceMount}ms in game)`)
    throttledSaveStats()
  } else {
    console.log(`[Connect4] onUnmounted skipped save (${timeSinceMount}ms < 5s threshold)`)
  }
})

function createEmptyBoard() {
  return Array.from({ length: ROWS }, () => Array(COLS).fill(null))
}

function resetGame() {
  board.value = createEmptyBoard()
  currentTurn.value = 'player'
  gameState.value = 'playing'
  winner.value = null
  winCells.value = []
  hoveredCol.value = -1
  isAiThinking.value = false
  Object.keys(dropAnimations).forEach(k => delete dropAnimations[k])
}

const statusText = computed(() => {
  if (gameState.value === 'won') return winner.value === 'player' ? '¡GANASTE!' : 'IA GANA'
  if (gameState.value === 'draw') return 'EMPATE'
  if (currentTurn.value === 'ai') return isAiThinking.value ? 'IA pensando' : 'Turno de la IA'
  return 'Tu turno'
})

function isWinCell(r, c) {
  return winCells.value.some(([wr, wc]) => wr === r && wc === c)
}

function getLowestEmpty(b, col) {
  for (let r = ROWS - 1; r >= 0; r--) {
    if (!b[r][col]) return r
  }
  return -1
}

async function dropPiece(col) {
  if (currentTurn.value !== 'player' || gameState.value !== 'playing') return
  const row = getLowestEmpty(board.value, col)
  if (row === -1) return

  placeAndAnimate(board.value, row, col, 'player')

  const win = checkWin(board.value, row, col, 'player')
  if (win) {
    winCells.value = win
    gameState.value = 'won'
    winner.value = 'player'
    connect4.recordWin()
    return
  }
  if (isDraw(board.value)) {
    gameState.value = 'draw'
    return
  }

  currentTurn.value = 'ai'
  isAiThinking.value = true

  await nextTick()
  setTimeout(() => {
    aiMove()
  }, AI_RESPONSE_DELAY_MS)
}

function placeAndAnimate(b, row, col, player) {
  b[row][col] = player
  const key = `${row}-${col}`
  dropAnimations[key] = true
  setTimeout(() => delete dropAnimations[key], 600)
}

function aiMove() {
  const b = board.value
  let col = getBestMove(b, AI_DEPTH)
  if (col === -1) col = getRandomMove(b)

  const row = getLowestEmpty(b, col)
  if (row === -1) {
    isAiThinking.value = false
    currentTurn.value = 'player'
    return
  }

  placeAndAnimate(b, row, col, 'ai')

  isAiThinking.value = false

  const win = checkWin(b, row, col, 'ai')
  if (win) {
    winCells.value = win
    gameState.value = 'won'
    winner.value = 'ai'
    connect4.recordLoss()
    return
  }
  if (isDraw(b)) {
    gameState.value = 'draw'
    return
  }

  currentTurn.value = 'player'
}

function getRandomMove(b) {
  // Easy: mostly random but blocks immediate wins
  const winning = findWinningMove(b, 'ai')
  if (winning !== -1) return winning
  const blocking = findWinningMove(b, 'player')
  if (blocking !== -1 && Math.random() > 0.4) return blocking
  const cols = []
  for (let c = 0; c < COLS; c++) if (getLowestEmpty(b, c) !== -1) cols.push(c)
  return cols[Math.floor(Math.random() * cols.length)]
}

function findWinningMove(b, player) {
  for (let c = 0; c < COLS; c++) {
    const r = getLowestEmpty(b, c)
    if (r === -1) continue
    b[r][c] = player
    const win = checkWin(b, r, c, player)
    b[r][c] = null
    if (win) return c
  }
  return -1
}

// Minimax with alpha-beta pruning
function getBestMove(b, depth) {
  let bestScore = -Infinity
  let bestCol = -1
  const cols = getOrderedCols()

  for (const c of cols) {
    const r = getLowestEmpty(b, c)
    if (r === -1) continue
    b[r][c] = 'ai'
    const score = minimax(b, depth - 1, -Infinity, Infinity, false)
    b[r][c] = null
    if (score > bestScore) {
      bestScore = score
      bestCol = c
    }
  }
  return bestCol
}

function getOrderedCols() {
  // Center-first ordering improves pruning
  return [3, 2, 4, 1, 5, 0, 6]
}

function minimax(b, depth, alpha, beta, isMaximizing) {
  const lastPlayer = isMaximizing ? 'player' : 'ai'
  // Terminal check - simplified: check full board and evaluate
  if (depth === 0) return evaluateBoard(b)

  const available = []
  for (let c = 0; c < COLS; c++) {
    if (getLowestEmpty(b, c) !== -1) available.push(c)
  }
  if (available.length === 0) return 0

  // Check for terminal win states from last move perspective
  for (const c of available) {
    const r = getLowestEmpty(b, c)
    if (r === -1) continue
    b[r][c] = isMaximizing ? 'ai' : 'player'
    const win = checkWin(b, r, c, isMaximizing ? 'ai' : 'player')
    b[r][c] = null
    if (win) {
      return isMaximizing ? 1000 + depth : -(1000 + depth)
    }
  }

  if (isMaximizing) {
    let maxEval = -Infinity
    for (const c of getOrderedCols()) {
      const r = getLowestEmpty(b, c)
      if (r === -1) continue
      b[r][c] = 'ai'
      const ev = minimax(b, depth - 1, alpha, beta, false)
      b[r][c] = null
      if (ev > maxEval) maxEval = ev
      if (ev > alpha) alpha = ev
      if (beta <= alpha) break
    }
    return maxEval
  } else {
    let minEval = Infinity
    for (const c of getOrderedCols()) {
      const r = getLowestEmpty(b, c)
      if (r === -1) continue
      b[r][c] = 'player'
      const ev = minimax(b, depth - 1, alpha, beta, true)
      b[r][c] = null
      if (ev < minEval) minEval = ev
      if (ev < beta) beta = ev
      if (beta <= alpha) break
    }
    return minEval
  }
}

function evaluateBoard(b) {
  let score = 0
  // Center column preference
  for (let r = 0; r < ROWS; r++) {
    if (b[r][3] === 'ai') score += 3
    else if (b[r][3] === 'player') score -= 3
  }
  // Horizontal
  for (let r = 0; r < ROWS; r++) {
    for (let c = 0; c <= COLS - 4; c++) {
      score += scoreWindow([b[r][c], b[r][c+1], b[r][c+2], b[r][c+3]])
    }
  }
  // Vertical
  for (let c = 0; c < COLS; c++) {
    for (let r = 0; r <= ROWS - 4; r++) {
      score += scoreWindow([b[r][c], b[r+1][c], b[r+2][c], b[r+3][c]])
    }
  }
  // Diagonal /
  for (let r = 3; r < ROWS; r++) {
    for (let c = 0; c <= COLS - 4; c++) {
      score += scoreWindow([b[r][c], b[r-1][c+1], b[r-2][c+2], b[r-3][c+3]])
    }
  }
  // Diagonal \
  for (let r = 0; r <= ROWS - 4; r++) {
    for (let c = 0; c <= COLS - 4; c++) {
      score += scoreWindow([b[r][c], b[r+1][c+1], b[r+2][c+2], b[r+3][c+3]])
    }
  }
  return score
}

function scoreWindow(w) {
  const ai = w.filter(x => x === 'ai').length
  const player = w.filter(x => x === 'player').length
  const empty = w.filter(x => !x).length
  if (ai === 4) return 100
  if (ai === 3 && empty === 1) return 5
  if (ai === 2 && empty === 2) return 2
  if (player === 4) return -100
  if (player === 3 && empty === 1) return -4
  if (player === 2 && empty === 2) return -1
  return 0
}

function checkWin(b, row, col, player) {
  const directions = [[0,1],[1,0],[1,1],[1,-1]]
  for (const [dr, dc] of directions) {
    const cells = [[row, col]]
    for (let i = 1; i < 4; i++) {
      const r = row + dr * i, c = col + dc * i
      if (r < 0 || r >= ROWS || c < 0 || c >= COLS || b[r][c] !== player) break
      cells.push([r, c])
    }
    for (let i = 1; i < 4; i++) {
      const r = row - dr * i, c = col - dc * i
      if (r < 0 || r >= ROWS || c < 0 || c >= COLS || b[r][c] !== player) break
      cells.push([r, c])
    }
    if (cells.length >= 4) return cells
  }
  return null
}

function isDraw(b) {
  return b[0].every(cell => cell !== null)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600&display=swap');

.c4-wrapper {
  --c4-cell-size: 62px;
  --c4-cell-gap: 8px;
  --c4-board-width: calc((var(--c4-cell-size) * 7) + (var(--c4-cell-gap) * 6));
  width: 100%;
  border-radius: 1rem;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background:
    radial-gradient(ellipse at 18% 14%, rgba(56, 189, 248, 0.2) 0%, transparent 44%),
    radial-gradient(ellipse at 84% 86%, rgba(244, 63, 94, 0.14) 0%, transparent 48%),
    linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(2, 6, 23, 0.98));
  display: flex;
  justify-content: center;
  font-family: 'Rajdhani', sans-serif;
  position: relative;
  overflow: hidden;
  padding: clamp(0.75rem, 2vw, 1.2rem);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.06),
    0 14px 36px rgba(15, 23, 42, 0.35);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(0, 200, 255, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 200, 255, 0.04) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}

.c4-wrapper::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(ellipse at 30% 20%, rgba(0, 80, 160, 0.15) 0%, transparent 50%),
              radial-gradient(ellipse at 70% 80%, rgba(160, 0, 80, 0.1) 0%, transparent 50%);
  pointer-events: none;
}

.game-container {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 560px;
  gap: 14px;
  padding: 4px 2px 8px;
}

/* Header */
.game-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  width: 100%;
}

.logo {
  display: flex;
  align-items: baseline;
  gap: 2px;
  flex-direction: row;
  position: relative;
  flex-wrap: wrap;
}

.logo-c, .logo-4 {
  font-family: 'Orbitron', monospace;
  font-weight: 900;
  font-size: 2.4rem;
  line-height: 1;
}
.logo-c { color: #00c8ff; text-shadow: 0 0 20px #00c8ff, 0 0 40px rgba(0,200,255,0.5); }
.logo-4 { color: #ff3a6e; text-shadow: 0 0 20px #ff3a6e, 0 0 40px rgba(255,58,110,0.5); }

.logo-sub {
  font-family: 'Orbitron', monospace;
  font-size: 0.5rem;
  letter-spacing: 0.3em;
  color: rgba(255,255,255,0.3);
  width: 100%;
  margin-top: -4px;
}

.mode-pill {
  font-family: 'Orbitron', monospace;
  font-size: 0.56rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: rgba(217, 245, 255, 0.82);
  border: 1px solid rgba(34, 211, 238, 0.4);
  border-radius: 999px;
  padding: 0.32rem 0.7rem;
  background: linear-gradient(135deg, rgba(6, 95, 143, 0.35), rgba(91, 33, 182, 0.26));
  box-shadow: 0 0 0 1px rgba(125, 211, 252, 0.12) inset;
}

/* Status Bar */
.status-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  background: linear-gradient(120deg, rgba(51, 65, 85, 0.35), rgba(15, 23, 42, 0.55));
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 8px;
  padding: 10px 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.meta-bar {
  width: 100%;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-family: 'Orbitron', monospace;
  font-size: 0.58rem;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.45);
  text-transform: uppercase;
  padding-inline: 4px;
}

.sync-error {
  width: 100%;
  text-align: left;
  font-size: 0.7rem;
  color: #ff8aa9;
  letter-spacing: 0.04em;
}

.turn-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
}

.turn-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  transition: all 0.3s;
}
.dot-player { background: #00c8ff; box-shadow: 0 0 10px #00c8ff; }
.dot-ai { background: #ff3a6e; box-shadow: 0 0 10px #ff3a6e; }

.turn-text {
  font-family: 'Orbitron', monospace;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: rgba(255,255,255,0.8);
}

.thinking-dots { display: flex; gap: 3px; align-items: center; }
.thinking-dots span {
  display: block;
  width: 4px; height: 4px;
  border-radius: 50%;
  background: #ff3a6e;
  animation: blink 1.2s infinite;
}
.thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
.thinking-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes blink {
  0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
  40% { opacity: 1; transform: scale(1.2); }
}

.score-board {
  display: flex;
  align-items: center;
  gap: 12px;
}

.score-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}
.score-label {
  font-family: 'Orbitron', monospace;
  font-size: 0.5rem;
  letter-spacing: 0.15em;
  color: rgba(255,255,255,0.3);
}
.score-value {
  font-family: 'Orbitron', monospace;
  font-size: 1.4rem;
  font-weight: 900;
}
.player-color { color: #00c8ff; text-shadow: 0 0 12px rgba(0,200,255,0.7); }
.ai-color { color: #ff3a6e; text-shadow: 0 0 12px rgba(255,58,110,0.7); }
.score-divider {
  font-family: 'Orbitron', monospace;
  font-size: 0.55rem;
  color: rgba(255,255,255,0.2);
  letter-spacing: 0.1em;
}

/* Column Indicators */
.column-indicators {
  display: flex;
  width: var(--c4-board-width);
  margin-inline: auto;
  gap: var(--c4-cell-gap);
}
.col-indicator {
  flex: 0 0 var(--c4-cell-size);
  width: var(--c4-cell-size);
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 8px;
}

.col-indicator.active {
  background: linear-gradient(180deg, rgba(0, 200, 255, 0.18), rgba(0, 200, 255, 0.03));
}
.indicator-arrow {
  font-size: 0.9rem;
  color: #00c8ff;
  text-shadow: 0 0 8px #00c8ff;
  animation: bounce 0.6s infinite alternate;
}
@keyframes bounce {
  from { transform: translateY(0); }
  to { transform: translateY(4px); }
}

/* Board Frame */
.board-frame {
  width: fit-content;
  margin-inline: auto;
  background: linear-gradient(145deg, #0a1628, #0d1f3c);
  border: 2px solid rgba(0, 100, 200, 0.3);
  border-radius: 16px;
  padding: 14px;
  box-shadow:
    0 0 0 1px rgba(0,200,255,0.05),
    0 20px 60px rgba(0,0,0,0.8),
    inset 0 1px 0 rgba(255,255,255,0.05);
  position: relative;
}

.board-frame::before {
  content: '';
  position: absolute;
  inset: -2px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(0,200,255,0.3), transparent 40%, rgba(255,58,110,0.2));
  z-index: -1;
}

.board {
  display: flex;
  flex-direction: column;
  width: var(--c4-board-width);
  gap: var(--c4-cell-gap);
}

.board-row {
  display: flex;
  gap: var(--c4-cell-gap);
}

.cell {
  width: var(--c4-cell-size);
  height: var(--c4-cell-size);
  cursor: pointer;
  position: relative;
  border-radius: 50%;
}

.cell-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
}

.hole {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: #060a12;
  box-shadow:
    inset 0 4px 12px rgba(0,0,0,0.9),
    inset 0 0 0 1px rgba(255,255,255,0.04);
}

.piece {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  position: relative;
  transition: all 0.15s;
}

.cell-player .piece {
  background: radial-gradient(circle at 35% 35%, #4de8ff, #00a8d8 50%, #006fa0);
  box-shadow:
    0 0 15px rgba(0,200,255,0.6),
    0 0 30px rgba(0,200,255,0.3),
    inset 0 -3px 6px rgba(0,0,0,0.4);
}

.cell-ai .piece {
  background: radial-gradient(circle at 35% 35%, #ff7a9a, #e82060 50%, #a0103a);
  box-shadow:
    0 0 15px rgba(255,58,110,0.6),
    0 0 30px rgba(255,58,110,0.3),
    inset 0 -3px 6px rgba(0,0,0,0.4);
}

.piece-shine {
  position: absolute;
  top: 12%;
  left: 18%;
  width: 30%;
  height: 20%;
  background: rgba(255,255,255,0.5);
  border-radius: 50%;
  filter: blur(2px);
}

.piece-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.3s;
}
.cell-win .piece-glow {
  opacity: 1;
  animation: pulse-win 0.8s infinite alternate;
}
.cell-player.cell-win .piece-glow {
  background: radial-gradient(circle, rgba(0,200,255,0.4), transparent 70%);
}
.cell-ai.cell-win .piece-glow {
  background: radial-gradient(circle, rgba(255,58,110,0.4), transparent 70%);
}

@keyframes pulse-win {
  from { transform: scale(0.95); opacity: 0.6; }
  to { transform: scale(1.15); opacity: 1; }
}

.cell-win .piece {
  animation: win-flash 0.8s infinite alternate;
}
@keyframes win-flash {
  from { filter: brightness(1); }
  to { filter: brightness(1.6); }
}

/* Drop animation */
@keyframes drop-in {
  0% { transform: translateY(-300px); opacity: 0; }
  70% { transform: translateY(6px); }
  85% { transform: translateY(-3px); }
  100% { transform: translateY(0); opacity: 1; }
}
.cell-drop .piece {
  animation: drop-in 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

/* Win overlay */
.win-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(6, 10, 18, 0.85);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  z-index: 10;
}

.win-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 32px 40px;
}

.win-icon { font-size: 3.5rem; filter: drop-shadow(0 0 20px rgba(255,255,255,0.3)); }

.win-title {
  font-family: 'Orbitron', monospace;
  font-size: 2rem;
  font-weight: 900;
  background: linear-gradient(90deg, #00c8ff, #ff3a6e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: 0.05em;
}

.win-sub {
  font-size: 1rem;
  color: rgba(255,255,255,0.5);
  letter-spacing: 0.05em;
}

.play-again-btn {
  margin-top: 8px;
  font-family: 'Orbitron', monospace;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  padding: 12px 28px;
  border: none;
  border-radius: 6px;
  background: linear-gradient(135deg, #00c8ff, #0080cc);
  color: #fff;
  cursor: pointer;
  box-shadow: 0 0 20px rgba(0,200,255,0.4);
  transition: all 0.2s;
}
.play-again-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 24px rgba(0,200,255,0.6);
}

.overlay-fade-enter-active, .overlay-fade-leave-active { transition: opacity 0.4s; }
.overlay-fade-enter-from, .overlay-fade-leave-to { opacity: 0; }

/* New game button */
.new-game-btn {
  font-family: 'Orbitron', monospace;
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.15em;
  padding: 8px 20px;
  border: 1px solid rgba(255,255,255,0.12);
  background: transparent;
  color: rgba(255,255,255,0.35);
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
}
.new-game-btn:hover {
  border-color: rgba(255,255,255,0.35);
  color: rgba(255,255,255,0.7);
}

.achievement-toast-stack {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 60;
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: min(320px, calc(100vw - 24px));
}

.achievement-toast {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid rgba(0, 200, 255, 0.45);
  background: rgba(6, 18, 34, 0.9);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(6px);
}

.achievement-icon {
  font-size: 1.25rem;
  line-height: 1;
}

.achievement-content {
  flex: 1;
  min-width: 0;
}

.achievement-kicker {
  font-family: 'Orbitron', monospace;
  font-size: 0.55rem;
  letter-spacing: 0.13em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.6);
}

.achievement-title {
  margin-top: 2px;
  font-weight: 700;
  color: #d7f5ff;
  line-height: 1.2;
}

.achievement-desc {
  margin-top: 2px;
  font-size: 0.78rem;
  color: rgba(255, 255, 255, 0.65);
  line-height: 1.2;
}

.achievement-close {
  border: 0;
  background: transparent;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  font-size: 0.95rem;
}

.achievement-close:hover {
  color: rgba(255, 255, 255, 0.95);
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.25s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* Responsive */
@media (max-width: 520px) {
  .c4-wrapper {
    --c4-cell-size: 44px;
    --c4-cell-gap: 5px;
  }

  .game-header {
    flex-direction: column;
    justify-content: center;
    gap: 6px;
  }

  .status-bar {
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
  }

  .turn-indicator,
  .score-board {
    justify-content: center;
  }

  .meta-bar {
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }

  .board-frame { padding: 10px; }
  .logo-c, .logo-4 { font-size: 1.8rem; }
  .meta-bar { font-size: 0.5rem; }
}
</style>

<!-- Light Mode Styles (Global, not scoped) -->
<style>
html:not(.dark) .c4-wrapper {
  background:
    radial-gradient(75rem 30rem at -10% -25%, rgba(34, 211, 238, 0.08), transparent 55%),
    radial-gradient(65rem 26rem at 110% -15%, rgba(139, 92, 246, 0.08), transparent 55%),
    linear-gradient(180deg, #f8fafc, #e2e8f0 65%) !important;
  color: #1e293b !important;
  border-color: rgba(51, 65, 85, 0.2) !important;
}

html:not(.dark) .status-bar {
  background: linear-gradient(120deg, rgba(226, 232, 240, 0.6), rgba(248, 250, 252, 0.8)) !important;
  border-color: rgba(51, 65, 85, 0.2) !important;
}

html:not(.dark) .meta-bar {
  color: #475569 !important;
}

html:not(.dark) .turn-text {
  color: #0f172a !important;
}

html:not(.dark) .logo-c,
html:not(.dark) .logo-4 {
  color: #0f172a !important;
}

html:not(.dark) .score-label {
  color: #475569 !important;
}

html:not(.dark) .score-value {
  color: #0f172a !important;
}

html:not(.dark) .board-frame {
  background: linear-gradient(145deg, rgba(240, 249, 255, 0.9), rgba(226, 232, 240, 0.85)) !important;
  border-color: rgba(100, 116, 139, 0.3) !important;
}

html:not(.dark) .hole {
  background: #e2e8f0 !important;
  box-shadow:
    inset 0 2px 6px rgba(0,0,0,0.15),
    inset 0 -2px 6px rgba(0,0,0,0.1),
    0 0 12px rgba(2, 6, 23, 0.2) !important;
}

html:not(.dark) .win-overlay {
  background: rgba(248, 250, 252, 0.9) !important;
}

html:not(.dark) .win-sub {
  color: rgba(15, 23, 42, 0.7) !important;
}

html:not(.dark) .achievement-toast {
  background: rgba(248, 250, 252, 0.95) !important;
  border-color: rgba(34, 211, 238, 0.3) !important;
  color: #1e293b !important;
}

html:not(.dark) .achievement-title {
  color: #0f172a !important;
}

html:not(.dark) .achievement-kicker {
  color: #475569 !important;
}

html:not(.dark) .achievement-desc {
  color: #334155 !important;
}
</style>
