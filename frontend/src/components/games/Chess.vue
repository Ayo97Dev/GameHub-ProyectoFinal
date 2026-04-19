<script setup>
import { ref, computed, onMounted } from 'vue'

// ────── Crear Tablero ──────
// Creamos matriz 8x8 de celdas, cada celda tiene coordenada (row,col) y piece (null o {type,color})
const board = ref(
  Array.from({ length: 8 }, (_, row) =>
    Array.from({ length: 8 }, (_, col) => ({
      row,
      col,
      piece: null
    }))
  )
)

// ────── Inicializacion de piezas ──────
// Coloca piezas en su posición estandar inicial
// Usa backRow para piezas mayores
const initialSetup = () => {
  const newBoard = board.value
  const backRow = ['rook', 'knight', 'bishop', 'queen', 'king', 'bishop', 'knight', 'rook']

  //Filas negras
  newBoard[0].forEach((cell, i) => {
    cell.piece = { type: backRow[i], color: 'black' }
  })
  newBoard[1].forEach(cell => {
    cell.piece = { type: 'pawn', color: 'black' }
  })

  //Filas blancas
  newBoard[6].forEach(cell => {
    cell.piece = { type: 'pawn', color: 'white' }
  })
  newBoard[7].forEach((cell, i) => {
    cell.piece = { type: backRow[i], color: 'white' }
  })
}
initialSetup()

// ────── Estado ──────
// Guarda la celda actualmente seleccionada
// Se usa para saber desde donde mover y para mostrar posibles movimientos
const selected = ref(null)
const currentTurn = ref('white') // turno actual (white o black)
// Promoción de peon
const promotionCell = ref(null)

// ────── Helper para manejar clicks en celdas ──────
// Comprueba si una coordenada está dentro del tablero
const isInsideBoard = (row, col) => 
  row >= 0 && row < 8 && col >= 0 && col < 8

//Devuelve la pieza en una posición (o null)
const getPieceAt = (row, col) => 
  board.value[row]?.[col]?.piece || null

// ────── helper global ──────
// Centraliza acceso seguro al tablero evintando out-of-bounds
const get = (row, col) => {
  if (!isInsideBoard(row, col)) return null
  return getPieceAt(row, col)
}

// ────── SVG piezas ──────
const getPieceImage = (piece) => {
  if (!piece) return ''
  return `/assets/boardKing/${piece.color}-${piece.type}.svg`
}

// ────── Movimiento de piezas (sin reglas) ──────
// Solo ejecuta movimiento -> NO VALIDA REGLAS
const movePiece = (targetCell) => {
  if (!selected.value) return

  const piece = selected.value.piece

  targetCell.piece = piece
  selected.value.piece = null
  selected.value = null

  // PROMOCIÓN
  if (piece.type === 'pawn') {
    const isPromotion =
      (piece.color === 'white' && targetCell.row === 0) ||
      (piece.color === 'black' && targetCell.row === 7)

    if (isPromotion) {
      promotionCell.value = targetCell
      return
    }
  }
// Cambio de turno
  currentTurn.value = currentTurn.value === 'white' ? 'black' : 'white'
}


// ────── Manejo de clicks en celdas ──────
// si hay pieza seleccinada -> intentar mover
// Si no -> seleccionar pieza
const handleClick = (cell) => {
  // Limpieza defensiva
  if (selected.value && !selected.value.piece) {
    selected.value = null
  }

  // Bloqueo durante promoción
  if (promotionCell.value) return

  // ────── HAY PIEZA SELECCIONADA ──────
  if (selected.value) {
    const moves = getLegalMoves(selected.value)

    if (moves.includes(cell)) {
      movePiece(cell)
    } else {
      selected.value = null
    }

    return
  }

  // ────── SELECCIONAR PIEZA ──────
  if (cell.piece && cell.piece.color === currentTurn.value) {
    selected.value = cell
  }
}

// ────── PROMOCIÓN ──────
const promotePawn = (type) => {
  if (!promotionCell.value) return

  promotionCell.value.piece.type = type
  promotionCell.value = null

  // turno después de promoción
  currentTurn.value =
    currentTurn.value === 'white' ? 'black' : 'white'
}

// ────── Movimientos Base ──────
// Reutilizable para torre, alfil y reina
// direcciones: vector de movimientos (dr, dc) para cada dirección válida
// Se detiene cuando encuentra una pieza (amiga o enemiga) o sale del tablero
const getSlidingMoves = (cell, directions) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  for (const [dr, dc] of directions) {
    let tr = r + dr
    let tc = c + dc

    while (isInsideBoard(tr, tc)) {
      const target = get(tr, tc)

      // casilla vacía -> movimiento válido
      if (!target) {
        moves.push(board.value[tr][tc])
      } else {
        // pieza enemiga -> capturable
        if (target.color !== piece.color) {
          moves.push(board.value[tr][tc])
        }
        // se bloquea siempre que haya una pieza (amiga o enemiga)
        break
      }

      tr += dr
      tc += dc
    }
  }

  return moves
}

// PEON
// Se mueve 1 casilla hacia adelante (2 desde su posición inicial) y captura diagonal
const getPawnMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dir = piece.color === 'white' ? -1 : 1
  const startRow = piece.color === 'white' ? 6 : 1

  const f1 = r + dir
  const f2 = r + 2 * dir

  // Movimiento hacia adelante
 if (isInsideBoard(f1, c) && !get(f1, c)) {
  moves.push(board.value[f1][c])

  if (r === startRow && isInsideBoard(f2, c) && !get(f2, c)) {
    moves.push(board.value[f2][c])
  }
}
  // Capturas diagonales
  for (const dc of [-1, 1]) {
    const tr = f1
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const target = get(tr, tc)
    if (target && target.color !== piece.color) {
      moves.push(board.value[tr][tc])
    }
  }

  return moves
}

// TORRE
// La torre se mueve en línea recta (horizontal y vertical)
const getRookMoves = (cell) => {
  return getSlidingMoves(cell, [[-1, 0], [1, 0], [0, -1], [0, 1]])
}

// ALFIL
// El alfil se mueve en diagonal
const getBishopMoves = (cell) => {
  return getSlidingMoves(cell, [[-1, -1], [-1, 1], [1, -1], [1, 1]])
}

// REINA
// La reina combina los movimientos de torre y alfil
const getQueenMoves = (cell) => {
  return getSlidingMoves(cell, [[-1, 0], [1, 0], [0, -1], [0, 1],
    [-1, -1], [-1, 1], [1, -1], [1, 1]])
}

// CABALLO 
// El caballo se mueve en forma de "L"
const getKnightMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const jumps = [
    [-2, -1], [-2, 1], [-1, -2], [-1, 2],
    [1, -2], [1, 2], [2, -1], [2, 1]
  ]

  for (const [dr, dc] of jumps) {
    const tr = r + dr
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const target = get(tr, tc)
    if (!target || target.color !== piece.color) {
      moves.push(board.value[tr][tc])
    }
  }

  return moves
}

// REY
// El rey se mueve 1 casilla en cualquier dirección y no puede moverse a una casilla atacada por el oponente
const getKingMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const directions = [
    [-1, -1], [-1, 0], [-1, 1],
    [0, -1], [0, 1],
    [1, -1], [1, 0], [1, 1]
  ]

  for (const [dr, dc] of directions) {
    const tr = r + dr
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const target = get(tr, tc)
    if (!target || target.color !== piece.color) {
      moves.push(board.value[tr][tc])
    }
  }

  return moves
}

// dispatcher de pseudo movimientos según tipo de pieza
const getPseudoMoves = (cell) => {
  switch (cell.piece.type) {
    case 'pawn': return getPawnMoves(cell)
    case 'rook': return getRookMoves(cell)
    case 'bishop': return getBishopMoves(cell)
    case 'queen': return getQueenMoves(cell)
    case 'knight': return getKnightMoves(cell)
    case 'king': return getKingMoves(cell)
    default: return []
  }
}

// ────── Motor real ──────

// helpers tablero simulado
const getFromBoard = (b,r,c)=>{
  if(!isInsideBoard(r,c)) return null
  return b[r]?.[c]?.piece||null
}

const cloneBoard = ()=>{
  return board.value.map(r=>
    r.map(c=>({
      row:c.row,
      col:c.col,
      piece:c.piece?{...c.piece}:null
    }))
  )
}

const simulateMove = (b, from, to) => {
  const piece = b[from.row][from.col].piece
  if (!piece) return

  b[to.row][to.col].piece = piece
  b[from.row][from.col].piece = null
}

const findKing = (b,color)=>{
  for(const r of b){
    for(const c of r){
      if(c.piece?.type==='king' && c.piece.color===color){
        return c
      }
    }
  }
  return null
}

// dispatcher simulado
const getPseudoMovesFromBoard = (cell,b)=>{
  const piece=b[cell.row][cell.col].piece
  if(!piece) return []

  const fake={row:cell.row,col:cell.col,piece}

  switch(piece.type){
    case 'pawn': return getPawnMovesFromBoard(fake,b)
    case 'rook': return getSlidingMovesFromBoard(fake,b,[[-1,0],[1,0],[0,-1],[0,1]])
    case 'bishop': return getSlidingMovesFromBoard(fake,b,[[-1,-1],[-1,1],[1,-1],[1,1]])
    case 'queen': return getSlidingMovesFromBoard(fake,b,[
      [-1,0],[1,0],[0,-1],[0,1],
      [-1,-1],[-1,1],[1,-1],[1,1]
    ])
    case 'knight': {
      const moves=[]
      const jumps=[[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]]
      for(const[dr,dc] of jumps){
        const tr=fake.row+dr, tc=fake.col+dc
        const t=getFromBoard(b,tr,tc)
        if(!t||t.color!==piece.color){
          moves.push({row:tr,col:tc})
        }
      }
      return moves
    }
    case 'king': {
      const moves=[]
      const dirs=[[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]]
      for(const[dr,dc] of dirs){
        const tr=fake.row+dr, tc=fake.col+dc
        const t=getFromBoard(b,tr,tc)
        if(!t||t.color!==piece.color){
          moves.push({row:tr,col:tc})
        }
      }
      return moves
    }
  }
  return []
}


//----------------------------------------------------------------------------------------------------------------------------
// sliding simulado
const getSlidingMovesFromBoard = (cell,b,dirs)=>{
  const moves=[]
  const {row:r,col:c,piece}=cell

  for(const[dr,dc] of dirs){
    let tr=r+dr, tc=c+dc

    while(isInsideBoard(tr,tc)){
      const t=getFromBoard(b,tr,tc)

      if(!t) moves.push({row:tr,col:tc})
      else{
        if(t.color!==piece.color) moves.push({row:tr,col:tc})
        break
      }

      tr+=dr
      tc+=dc
    }
  }
  return moves
}

// pawn simulado
const getPawnMovesFromBoard = (cell,b)=>{
  const moves=[]
  const {row:r,col:c,piece}=cell

  const dir=piece.color==='white'?-1:1
  const start=piece.color==='white'?6:1

  const f1=r+dir
  const f2=r+2*dir

  if(!getFromBoard(b,f1,c)){
    moves.push({row:f1,col:c})
    if(r===start && !getFromBoard(b,f2,c)){
      moves.push({row:f2,col:c})
    }
  }

  for(const dc of[-1,1]){
    const tr=f1, tc=c+dc
    const t=getFromBoard(b,tr,tc)
    if(t && t.color!==piece.color){
      moves.push({row:tr,col:tc})
    }
  }
  return moves
}

const isKingInCheck = (b,color)=>{
  const king = findKing(b,color)
  if (!king) return false 

  const enemy = color==='white'?'black':'white'

  for(const r of b){
    for(const c of r){
      if(c.piece?.color===enemy){
        const moves=getPseudoMovesFromBoard(c,b)
        if(moves.some(m=>m.row===king.row && m.col===king.col)){
          return true
        }
      }
    }
  }
  return false
}

// movimiento legal
const getLegalMoves = (cell)=>{
  if (!cell.piece || cell.piece.color !== currentTurn.value) return []

  const pseudo = getPseudoMoves(cell)

  return pseudo.filter(target=>{
    const b = cloneBoard()

    if (!b[cell.row][cell.col].piece) return false

    simulateMove(b,cell,target)
    return !isKingInCheck(b,cell.piece.color)
  })
}
</script>

<template>
    <div class="container-board">
      <div class="turn">
          Turno: {{ currentTurn }}
      </div>
      <div class="board">
        <div 
          v-for="(row, rowIndex) in board" :key="rowIndex" class="row">
          <div 
            v-for="(cell, colIndex) in row" 
            :key="colIndex" 
            class="cell"
            :class="[
              (rowIndex + colIndex) % 2 === 0 ? 'white' : 'black',
              selected === cell ? 'selected' : ''
            ]"
            @click="handleClick(cell)"
          >
            <img
            v-if="cell.piece"
            :src="getPieceImage(cell.piece)"
            class="piece"
          /> 
          </div>
        </div>
      </div>
      <div v-if="promotionCell" class="promotion-modal">
        <h3>Promoción de Peón</h3>
        <button @click="promotePawn('queen')">♛ Reina</button>
        <button @click="promotePawn('rook')">♜ Torre</button>
        <button @click="promotePawn('bishop')">♝ Alfil</button>
        <button @click="promotePawn('knight')">♞ Caballo</button>
      </div>
    </div>
</template>

<style scoped>
.container-board {
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  /*max-width: 1200px;*/
}

.turn {
  margin-bottom: 10px;
  font-weight: bold;
}

.board {
  display: grid;
  grid-template-rows: repeat(8, 60px);
  width: 480px;
}

.row {
  display: grid;
  grid-template-columns: repeat(8, 60px);
}

.cell {
  display: flex;
  align-items: center;
  justify-content: center;
  /* font-size: 24px; */
  border: 1px solid #333;
  cursor: pointer;
}

.white {
  background-color: #f0d9b5;
}

.black {
  background-color: #b58863;
}

.selected {
  background-color: #90ee90;
}

.piece {
  width: 42px;
  height: 42px;
  pointer-events: none;
}

.promotion-modal{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.5);
  display:flex;
  justify-content:center;
  align-items:center;
}

.promotion-box{
  background:white;
  padding:20px;
  border-radius:10px;
  display:flex;
  flex-direction:column;
  gap:10px;
  text-align:center;
}

.promotion-box button{
  padding:10px;
  cursor:pointer;
  font-size:18px;
}
</style>