<script setup>
import { ref, computed, onMounted } from 'vue'

// ────── Crear Tablero ──────
// Crea matriz 8x8 de celdas, cada celda tiene coordenada (row,col) y piece (null o {type,color})
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

// ────── Seleccion──────
// Guarda la celda actualmente seleccionada
// Se usa para saber desde donde mover y para mostrar posibles movimientos
const selected = ref(null)

// ────── Helper para manejar clicks en celdas ──────
// Comprueba si una coordenada está dentro del tablero
const isInsideBoard = (row, col) => 
  row >= 0 && row < 8 && col >= 0 && col < 8

// Devuelve la pieza en una posición (o null)
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
  targetCell.piece = selected.value.piece
  selected.value.piece = null
  selected.value = null
}

// ────── Manejo de clicks en celdas ──────
// si hay pieza seleccinada -> intentar mover
// Si no -> seleccionar pieza
const handleClick = (cell) => {
  if (selected.value) {
    const moves = getLegalMoves(selected.value)

    // includes funciona porque compara referencias de objetos(misma celda)
    if (moves.includes(cell)) {
      movePiece(cell)
    } else {
      selected.value = null
    }
  } else if (cell.piece) {
    selected.value = cell
  }
}

// ────── Movimientos de piezas ──────
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
  return getSlidingMoves(cell, [
    [-1, 0], [1, 0], [0, -1], [0, 1]
  ])
}

// ALFIL
// El alfil se mueve en diagonal
const getBishopMoves = (cell) => {
  return getSlidingMoves(cell, [
    [-1, -1], [-1, 1], [1, -1], [1, 1]
  ])
}

// REINA
// La reina combina los movimientos de torre y alfil
const getQueenMoves = (cell) => {
  return getSlidingMoves(cell, [
    [-1, 0], [1, 0], [0, -1], [0, 1],
    [-1, -1], [-1, 1], [1, -1], [1, 1]
  ])
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
    [0, -1],           [0, 1],
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

// dispatcher de movimientos según tipo de pieza
const getLegalMoves = (cell) => {
  if (!cell.piece) return []

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
</script>

<template>
    <div class="container-board">
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
    </div>
</template>

<style scoped>
.container-board {
  justify-content: center;
  display: flex;
  max-width: 1200px;
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
  font-size: 24px;
  border: 1px solid #333;
  cursor: pointer;
}

.white {
  background-color: #f0d9b5;
}

.black {
  background-color: #b58863;
}

.piece {
  width: 42px;
  height: 42px;
  pointer-events: none;
  user-select: none;
}

.selected {
  background-color: #90ee90;
}

</style>