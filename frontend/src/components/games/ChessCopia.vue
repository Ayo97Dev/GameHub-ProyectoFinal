<script setup>
import { ref, computed, onMounted } from 'vue'

const emit = defineEmits(['score-change', 'game-completed'])

const BOARD_SIZE = 8
const FILES = ['A','B','C','D','E','F','G','H']

// ─── Game state ─────────────────────────────────────────────
const board = ref([])
const turn = ref('white')
const selected = ref(null)
const legalMoves = ref([])
const gameStatus = ref('playing')

// ─── Init board ─────────────────────────────────────────────
function createEmptyBoard() {
  return Array.from({ length: BOARD_SIZE }, () =>
    Array.from({ length: BOARD_SIZE }, () => null)
  )
}

function initChess() {
  const b = createEmptyBoard()

  const backRank = ['rook','knight','bishop','queen','king','bishop','knight','rook']

  // black
  backRank.forEach((p, x) => b[0][x] = { type: p, color: 'black', hasMoved: false })
  for (let x = 0; x < 8; x++) b[1][x] = { type: 'pawn', color: 'black', hasMoved: false }

  // white
  backRank.forEach((p, x) => b[7][x] = { type: p, color: 'white', hasMoved: false })
  for (let x = 0; x < 8; x++) b[6][x] = { type: 'pawn', color: 'white', hasMoved: false }

  board.value = b
}

// ─── Helpers ────────────────────────────────────────────────
function inBounds(x,y) {
  return x >= 0 && x < 8 && y >= 0 && y < 8
}

function enemy(color) {
  return color === 'white' ? 'black' : 'white'
}

function getPiece(x,y) {
  return board.value[y][x]
}

// ─── Move generation (básico) ────────────────────────────────
function getMoves(x, y) {
  const p = getPiece(x,y)
  if (!p) return []

  const moves = []

  const push = (nx, ny) => {
    if (!inBounds(nx,ny)) return
    const target = getPiece(nx,ny)
    if (!target || target.color !== p.color) {
      moves.push({ x: nx, y: ny })
    }
  }

  if (p.type === 'pawn') {
    const dir = p.color === 'white' ? -1 : 1

    // forward
    if (!getPiece(x, y + dir)) push(x, y + dir)

    // capture
    for (const dx of [-1, 1]) {
      const t = getPiece(x + dx, y + dir)
      if (t && t.color !== p.color) push(x + dx, y + dir)
    }
  }

  if (p.type === 'rook') {
    const dirs = [[1,0],[-1,0],[0,1],[0,-1]]
    for (const [dx,dy] of dirs) {
      let i = 1
      while (true) {
        const nx = x + dx*i, ny = y + dy*i
        if (!inBounds(nx,ny)) break
        const t = getPiece(nx,ny)
        if (!t) {
          moves.push({x:nx,y:ny})
        } else {
          if (t.color !== p.color) moves.push({x:nx,y:ny})
          break
        }
        i++
      }
    }
  }

  if (p.type === 'knight') {
    const jumps = [
      [1,2],[2,1],[-1,2],[-2,1],
      [1,-2],[2,-1],[-1,-2],[-2,-1]
    ]
    for (const [dx,dy] of jumps) push(x+dx,y+dy)
  }

  if (p.type === 'bishop') {
    const dirs = [[1,1],[1,-1],[-1,1],[-1,-1]]
    for (const [dx,dy] of dirs) {
      let i = 1
      while (true) {
        const nx = x + dx*i, ny = y + dy*i
        if (!inBounds(nx,ny)) break
        const t = getPiece(nx,ny)
        if (!t) moves.push({x:nx,y:ny})
        else {
          if (t.color !== p.color) moves.push({x:nx,y:ny})
          break
        }
        i++
      }
    }
  }

  if (p.type === 'queen') {
    return [
      ...getMovesLike(x,y,'rook'),
      ...getMovesLike(x,y,'bishop')
    ]
  }

  if (p.type === 'king') {
    for (let dx=-1; dx<=1; dx++)
      for (let dy=-1; dy<=1; dy++)
        if (dx || dy) push(x+dx,y+dy)
  }

  return moves
}

// helper queen reuse
function getMovesLike(x,y,type) {
  const original = getPiece(x,y).type
  getPiece(x,y).type = type
  const m = getMoves(x,y)
  getPiece(x,y).type = original
  return m
}

// ─── Selection ───────────────────────────────────────────────
function selectCell(x,y) {
  if (gameStatus.value !== 'playing') return

  const p = getPiece(x,y)

  if (selected.value) {
    const valid = legalMoves.value.find(m => m.x === x && m.y === y)

    if (valid) {
      movePiece(selected.value.x, selected.value.y, x, y)
      selected.value = null
      legalMoves.value = []
      return
    }
  }

  if (p && p.color === turn.value) {
    selected.value = {x,y}
    legalMoves.value = getMoves(x,y)
  } else {
    selected.value = null
    legalMoves.value = []
  }
}

// ─── Movement ────────────────────────────────────────────────
function movePiece(x1,y1,x2,y2) {
  const p = getPiece(x1,y1)
  const target = getPiece(x2,y2)

  board.value[y2][x2] = p
  board.value[y1][x1] = null

  if (target?.type === 'king') {
    gameStatus.value = 'finished'
    emit('game-completed')
  }

  turn.value = enemy(turn.value)
  emit('score-change', Math.floor(Math.random()*1000))
}

// ─── Display helpers ─────────────────────────────────────────
function cellClass(x,y) {
  const p = getPiece(x,y)
  const isSel = selected.value?.x === x && selected.value?.y === y
  const isMove = legalMoves.value.some(m => m.x === x && m.y === y)

  return {
    'cell-dark': (x+y)%2,
    'cell-light': !(x+y)%2,
    'cell-selected': isSel,
    'cell-move': isMove,
    'cell-white': p?.color === 'white',
    'cell-black': p?.color === 'black'
  }
}

function pieceSymbol(p) {
  if (!p) return ''
  const map = {
    pawn:'♟',
    rook:'♜',
    knight:'♞',
    bishop:'♝',
    queen:'♛',
    king:'♚'
  }
  return p.color === 'white' ? map[p.type].toUpperCase() : map[p.type]
}

// ─── Lifecycle ───────────────────────────────────────────────
onMounted(() => {
  initChess()
})
</script>

<template>
  <section class="chess-wrapper">
    <header class="mb-3">
      <h2 class="text-xl font-black">Ajedrez</h2>
      <p class="text-sm opacity-70">
        Turno: {{ turn }} · Estado: {{ gameStatus }}
      </p>
    </header>

    <div class="chess-board">
      <template v-for="(row,y) in board" :key="y">
        <button
          v-for="(cell,x) in row"
          :key="x"
          class="chess-cell"
          :class="cellClass(x,y)"
          @click="selectCell(x,y)"
        >
          {{ pieceSymbol(cell) }}
        </button>
      </template>
    </div>
  </section>
</template>

<style scoped>
.chess-board {
  display: grid;
  grid-template-columns: repeat(8, 50px);
  grid-template-rows: repeat(8, 50px);
  gap: 2px;
}

.chess-cell {
  display:flex;
  align-items:center;
  justify-content:center;
  font-size: 22px;
  border: 1px solid #333;
  cursor: pointer;
}

.cell-dark { background: #2f2f2f; color: white; }
.cell-light { background: #e5e5e5; }
.cell-selected { outline: 2px solid gold; }
.cell-move { box-shadow: inset 0 0 0 2px lime; }
.cell-white { color: white; }
.cell-black { color: black; }
</style>