<template>
  <div class="c4-arcade h-full flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>

    <!-- Background Ambient Glows -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-[600px] bg-neon-blue/10 blur-[150px] rounded-full pointer-events-none"></div>

    <!-- HUD SUPERIOR: Sleek & Floating -->
    <header class="relative z-30 p-4 sm:p-6 flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="flex flex-col items-center md:items-start text-center md:text-left">
        <div class="flex items-center gap-2 mb-1">
           <div class="size-2 rounded-full bg-neon-cyan animate-pulse"></div>
           <span class="font-pixel text-xs tracking-[0.3em] opacity-40 uppercase">SESIÓN_LÓGICA_PUERTA</span>
        </div>
        <h2 class="font-display text-2xl font-black text-white uppercase gh-title-glow tracking-tighter">{{ statusText }}</h2>
      </div>

      <div class="flex gap-4">
        <div class="gh-glass p-3 px-6 text-center border-neon-blue/20">
          <p class="font-pixel text-xs uppercase opacity-40 mb-1">ENTIDAD_USUARIO</p>
          <p class="font-display text-2xl font-black text-neon-blue leading-none">{{ scores.player }}</p>
        </div>
        <div class="gh-glass p-3 px-6 text-center border-neon-pink/20">
          <p class="font-pixel text-xs uppercase opacity-40 mb-1">INTELIGENCIA_CPU</p>
          <p class="font-display text-2xl font-black text-neon-pink leading-none">{{ scores.ai }}</p>
        </div>
      </div>
    </header>

    <!-- BOARD AREA -->
    <main class="flex-1 flex flex-col items-center justify-center relative z-20 min-h-0 p-4 lg:p-8">
      
      <div class="relative gh-glass p-6 sm:p-8 bg-white/5 border-white/5 shadow-2xl">
        <!-- COLUMN SELECTOR -->
        <div class="flex mb-4 px-1 gap-4 sm:gap-6">
          <div
            v-for="colIndex in COLS"
            :key="`indicator-${colIndex}`"
            class="flex-1 h-10 flex items-center justify-center cursor-pointer group  hover:bg-white/5 transition-colors"
            @mouseenter="hoveredCol = colIndex - 1"
            @mouseleave="hoveredCol = -1"
            @click="dropPiece(colIndex - 1)"
          >
            <div 
              v-show="hoveredCol === (colIndex - 1) && currentTurn === 'player' && gameState === 'playing'" 
              class="animate-bounce text-neon-cyan font-display text-xl transition-all"
            >
               ↓
            </div>
          </div>
        </div>

        <!-- THE GRID: Minimalist & High Contrast -->
        <div class="flex flex-col gap-4 sm:gap-6 relative">
          <div
            v-for="(row, r) in board"
            :key="r"
            class="flex gap-4 sm:gap-6"
          >
            <div
              v-for="(cell, c) in row"
              :key="c"
              class="size-11 sm:size-16 relative flex items-center justify-center transition-all group"
              :class="{
                'shadow-[0_0_30px_rgba(255,242,0,0.25)] scale-110 z-50': isWinCell(r, c)
              }"
              @mouseenter="hoveredCol = c"
              @mouseleave="hoveredCol = -1"
              @click="dropPiece(c)"
            >
               <!-- Cell structure -->
               <div class="absolute inset-0 rounded-full border border-white/10 shadow-inner bg-black/40"></div>
               <div 
                 class="absolute inset-0 rounded-full border-2 border-transparent group-hover:border-white/20 transition-all duration-300"
                 :class="{ 'border-neon-yellow shadow-[0_0_15px_#fff200] !border-4': isWinCell(r, c) }"
               ></div>
               
               <!-- The Piece -->
               <div 
                 v-if="cell" 
                 class="absolute size-9 sm:size-13 rounded-full flex items-center justify-center overflow-hidden transition-all duration-500" 
                 :class="[
                   cell === 'player' ? 'bg-neon-blue shadow-[0_0_20px_#007aff]' : 'bg-neon-pink shadow-[0_0_20px_#ff2d55]',
                   dropAnimations[`${r}-${c}`] ? 'piece-drop-anim' : ''
                 ]"
               >
                  <!-- Glossy overlay -->
                  <div class="absolute inset-0 bg-gradient-to-tr from-black/40 via-transparent to-white/40"></div>
                  <div class="absolute top-1 left-2 size-3 bg-white/60 rounded-full blur-[2px]"></div>
               </div>
            </div>
          </div>
        </div>

        <!-- WIN OVERLAY: Elegant Modal -->
        <Transition name="scale-up">
          <div v-if="gameState === 'won' || gameState === 'draw'" class="absolute inset-x-4 inset-y-8 z-[100] gh-glass border-neon-cyan/40 flex flex-col items-center justify-center text-center p-8 border-2">
             <div class="mb-6 relative">
                <div class="size-24 border-2 border-neon-cyan rounded-full flex items-center justify-center bg-black/40 backdrop-blur-3xl text-5xl">
                   <Icon v-if="gameState === 'draw'" icon="lucide:scale" class="text-white" />
                   <Icon v-else-if="winner === 'player'" icon="lucide:trophy" class="text-neon-yellow animate-bounce" />
                   <Icon v-else icon="lucide:bot" class="text-neon-pink opacity-80" />
                </div>
                <div class="absolute inset-0 rounded-full bg-neon-cyan/20 blur-xl animate-pulse"></div>
             </div>
             <p class="font-pixel text-neon-yellow text-xl mb-2 uppercase tracking-[0.4em]">{{ gameState === 'draw' ? 'BÚFER_ESTANCADO' : 'TRANSACCIÓN_COMPLETA' }}</p>
             <h3 class="font-display text-4xl font-black text-white mb-10 leading-none tracking-tighter">
                {{ gameState === 'draw' ? 'ESTADO_EMPATE' : (winner === 'player' ? 'VICTORIA_USUARIO' : 'ÉXITO_CPU') }}
             </h3>
             <button @click="resetGame" class="w-full sm:w-64 py-4  bg-neon-cyan text-black font-display text-lg font-black uppercase tracking-widest shadow-2xl transition-all hover:scale-105 active:scale-95">
                REINICIALIZAR
             </button>
          </div>
        </Transition>
      </div>
    </main>

    <!-- META BAR -->
    <footer class="relative z-30 p-4 border-t border-white/5 bg-black/40 flex flex-col sm:flex-row justify-between items-center gap-4">
       <div class="flex gap-8 font-pixel text-xs text-white/30 uppercase tracking-widest">
          <span class="flex items-center gap-2">TIEMPO_SESIÓN: {{ formatSessionDuration(sessionElapsedSeconds) }}</span>
          <span class="flex items-center gap-2">TURNO: {{ currentTurn === 'player' ? 'PROPIETARIO' : 'ID_INVITADO' }}</span>
       </div>
       <div class="flex items-center gap-4 text-white/40">
          <span class="font-pixel text-xs uppercase">ÚLTIMO_GUARDADO: {{ connect4.lastSaved ? connect4.lastSaved.toLocaleTimeString() : 'ESPERANDO' }}</span>
          <div class="size-2 rounded-full bg-neon-cyan animate-pulse"></div>
       </div>
    </footer>
    
    <!-- ACHIEVEMENTS -->
    <Teleport to="body">
       <div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 w-80 pointer-events-none">
         <TransitionGroup name="toast">
           <div v-for="toast in toastQueue" :key="toast.id" class="gh-glass p-5 bg-black/80 border-neon-cyan/50 pointer-events-auto">
              <div class="flex items-center gap-3 mb-3">
                 <div class="size-8  bg-neon-cyan/10 flex items-center justify-center text-neon-cyan">
                    <Icon icon="lucide:sparkles" />
                 </div>
                 <div class="flex-1">
                    <p class="font-pixel text-xs uppercase tracking-[0.2em] opacity-40">LOGRO_DESBLOQUEADO</p>
                    <h4 class="font-display text-xs font-black text-white uppercase">{{ toast.title }}</h4>
                 </div>
                 <button @click="dismissToast(toast.id)" class="text-white/40 hover:text-white">✕</button>
              </div>
              <p class="font-sans text-xs text-white/60 font-medium uppercase leading-relaxed">{{ toast.description }}</p>
           </div>
         </TransitionGroup>
       </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useConnect4Store } from '../../../stores/games/connect4'

const ROWS = 6; const COLS = 7; const AI_DEPTH = 5; const AI_RESPONSE_DELAY_MS = 600
const connect4 = useConnect4Store()

const board = ref(Array.from({ length: ROWS }, () => Array(COLS).fill(null)))
const currentTurn = ref('player'); const gameState = ref('playing'); const winner = ref(null); const winCells = ref([])
const hoveredCol = ref(-1); const isAiThinking = ref(false); const toastQueue = ref([])
const dropAnimations = reactive({})
const sessionElapsedSeconds = ref(0); const scores = computed(() => ({ player: connect4.wins, ai: connect4.losses }))

let toastTimers = []; let saveInterval = null; let sessionClockInterval = null
let _isUnmounted = false

watch(() => connect4.newAchievements.length, () => {
  while (connect4.newAchievements.length) {
    const achievement = connect4.newAchievements.shift()
    const id = Date.now() + Math.random()
    toastQueue.value.push({ id, ...achievement })
    toastTimers.push(setTimeout(() => dismissToast(id), 10_000))
  }
})

function formatSessionDuration(s) { 
  return `${Math.floor(s/60).toString().padStart(2, '0')}:${(s%60).toString().padStart(2, '0')}` 
}
function dismissToast(id) { toastQueue.value = toastQueue.value.filter(t => t.id !== id) }

onMounted(async () => {
  await connect4.initializeGame(true)
  if (_isUnmounted) return
  
  sessionElapsedSeconds.value = connect4.getSessionDurationSeconds()
  sessionClockInterval = setInterval(() => { sessionElapsedSeconds.value = connect4.getSessionDurationSeconds() }, 1000)
  saveInterval = setInterval(() => { connect4.saveStats() }, 30000)
})

onUnmounted(() => {
  _isUnmounted = true
  clearInterval(saveInterval); clearInterval(sessionClockInterval); toastTimers.forEach(clearTimeout); connect4.saveStats()
})

function resetGame() {
  board.value = Array.from({ length: ROWS }, () => Array(COLS).fill(null))
  currentTurn.value = 'player'; gameState.value = 'playing'; winner.value = null; winCells.value = []; hoveredCol.value = -1; isAiThinking.value = false
  Object.keys(dropAnimations).forEach(k => delete dropAnimations[k])
}

const statusText = computed(() => {
  if (gameState.value === 'won') return winner.value === 'player' ? 'CONEXIÓN_ESTABLE' : 'EQUIPO_FALLIDO'
  if (gameState.value === 'draw') return 'LÍNEA_SATURADA'
  return currentTurn.value === 'ai' ? 'CPU_PROCESANDO...' : 'TURNO_USUARIO'
})

function isWinCell(r, c) { return winCells.value.some(([wr, wc]) => wr === r && wc === c) }
function getLowestEmpty(b, col) { for (let r = ROWS - 1; r >= 0; r--) if (!b[r][col]) return r; return -1 }

async function dropPiece(col) {
  if (currentTurn.value !== 'player' || gameState.value !== 'playing') return
  const row = getLowestEmpty(board.value, col); if (row === -1) return
  board.value[row][col] = 'player'; const key = `${row}-${col}`; dropAnimations[key] = true; setTimeout(() => delete dropAnimations[key], 600)
  const win = checkWin(board.value, row, col, 'player')
  if (win) { winCells.value = win; gameState.value = 'won'; winner.value = 'player'; connect4.recordWin(); return }
  if (isDraw(board.value)) { gameState.value = 'draw'; return }
  currentTurn.value = 'ai'; isAiThinking.value = true; setTimeout(aiMove, AI_RESPONSE_DELAY_MS)
}

function aiMove() {
  const b = board.value; let col = getBestMove(b, AI_DEPTH); if (col === -1) col = Math.floor(Math.random() * COLS)
  const row = getLowestEmpty(b, col); if (row === -1) { isAiThinking.value = false; currentTurn.value = 'player'; return }
  b[row][col] = 'ai'; const key = `${row}-${col}`; dropAnimations[key] = true; setTimeout(() => delete dropAnimations[key], 600)
  isAiThinking.value = false; const win = checkWin(b, row, col, 'ai')
  if (win) { winCells.value = win; gameState.value = 'won'; winner.value = 'ai'; connect4.recordLoss(); return }
  if (isDraw(b)) { gameState.value = 'draw'; return }
  currentTurn.value = 'player'
}

function getBestMove(b, depth) {
  let bestScore = -Infinity, bestCol = -1; const cols = [3, 2, 4, 1, 5, 0, 6]
  for (const c of cols) {
    const r = getLowestEmpty(b, c); if (r === -1) continue
    b[r][c] = 'ai'; const score = minimax(b, depth - 1, -Infinity, Infinity, false); b[r][c] = null
    if (score > bestScore) { bestScore = score; bestCol = c }
  }
  return bestCol
}

function minimax(b, depth, alpha, beta, isM) {
  if (depth === 0) return evaluateBoard(b)
  const available = []; for (let c = 0; c < COLS; c++) { if (getLowestEmpty(b, c) !== -1) available.push(c) }
  if (available.length === 0) return 0
  for (const c of available) {
    const r = getLowestEmpty(b, c); b[r][c] = isM ? 'ai' : 'player'
    const win = checkWin(b, r, c, isM ? 'ai' : 'player'); b[r][c] = null
    if (win) return isM ? 1000 + depth : -(1000 + depth)
  }
  if (isM) {
    let maxE = -Infinity; for (const c of available) { const r = getLowestEmpty(b, c); b[r][c] = 'ai'; const ev = minimax(b, depth - 1, alpha, beta, false); b[r][c] = null; maxE = Math.max(maxE, ev); alpha = Math.max(alpha, ev); if (beta <= alpha) break }; return maxE
  } else {
    let minE = Infinity; for (const c of available) { const r = getLowestEmpty(b, c); b[r][c] = 'player'; const ev = minimax(b, depth - 1, alpha, beta, true); b[r][c] = null; minE = Math.min(minE, ev); beta = Math.min(beta, ev); if (beta <= alpha) break }; return minE
  }
}

function evaluateBoard(b) {
  let s = 0; for (let r = 0; r < ROWS; r++) { if (b[r][3] === 'ai') s += 3; else if (b[r][3] === 'player') s -= 3 }
  for (let r = 0; r < ROWS; r++) for (let c = 0; c <= COLS - 4; c++) s += scoreWindow([b[r][c], b[r][c+1], b[r][c+2], b[r][c+3]])
  for (let c = 0; c < COLS; c++) for (let r = 0; r <= ROWS - 4; r++) s += scoreWindow([b[r][c], b[r+1][c], b[r+2][c], b[r+3][c]])
  for (let r = 3; r < ROWS; r++) for (let c = 0; c <= COLS - 4; c++) s += scoreWindow([b[r][c], b[r-1][c+1], b[r-2][c+2], b[r-3][c+3]])
  for (let r = 0; r <= ROWS - 4; r++) for (let c = 0; c <= COLS - 4; c++) s += scoreWindow([b[r][c], b[r+1][c+1], b[r+2][c+2], b[r+3][c+3]])
  return s
}

function scoreWindow(w) {
  const ai = w.filter(x => x === 'ai').length; const player = w.filter(x => x === 'player').length; const empty = w.filter(x => !x).length
  if (ai === 4) return 100; if (ai === 3 && empty === 1) return 5; if (ai === 2 && empty === 2) return 2
  if (player === 4) return -100; if (player === 3 && empty === 1) return -4; if (player === 2 && empty === 2) return -1
  return 0
}

function checkWin(b, row, col, player) {
  const directions = [[0,1],[1,0],[1,1],[1,-1]]
  for (const [dr, dc] of directions) {
    const cells = [[row, col]]
    for (let i = 1; i < 4; i++) {
      const r = row + dr * i, c = col + dc * i; if (r < 0 || r >= ROWS || c < 0 || c >= COLS || b[r][c] !== player) break; cells.push([r, c])
    }
    for (let i = 1; i < 4; i++) {
      const r = row - dr * i, c = col - dc * i; if (r < 0 || r >= ROWS || c < 0 || c >= COLS || b[r][c] !== player) break; cells.push([r, c])
    }
    if (cells.length >= 4) return cells
  }
  return null
}
function isDraw(b) { return b[0].every(cell => cell !== null) }
</script>

<style scoped>
.piece-drop-anim { animation: piece-drop 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
@keyframes piece-drop { 0% { transform: translateY(-400px); opacity: 0; } 70% { transform: translateY(8px); opacity: 1; } 100% { transform: translateY(0); opacity: 1; } }

.scale-up-enter-active, .scale-up-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.scale-up-enter-from, .scale-up-leave-to { opacity: 0; transform: scale(0.8) translateY(20px); }

.toast-enter-active, .toast-leave-active { transition: all 0.4s ease; }
.toast-enter-from, .toast-leave-to { transform: translateX(80px); opacity: 0; }

.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
</style>
