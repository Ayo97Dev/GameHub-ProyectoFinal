<script setup>
import { ref, computed, onMounted } from 'vue'

// ────── Crear Tablero ──────
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

// ────── Seleccion y movimiento ──────
const selected = ref(null)

// ────── Helper para manejar clicks en celdas ──────
const isInsideBoard = (row, col) => row >= 0 && row < 8 && col >= 0 && col < 8
const getPieceAt = (row, col) => board.value[row]?.[col]?.piece || null

// ────── SVG piezas ──────
const getPieceImage = (piece) => {
  if (!piece) return ''
  return `/assets/boardKing/${piece.color}-${piece.type}.svg`
}

// ────── Movimiento de piezas (sin reglas) ──────
const movePiece = (targetCell) => {
  if (!selected.value) return

  targetCell.piece = selected.value.piece
  selected.value.piece = null
  selected.value = null
}

// ────── Manejo de clicks en celdas ──────
const handleClick = (cell) => {
  if (selected.value) {
    const moves = getLegalMoves(selected.value)

    const isValid = moves.includes(cell)

    if (isValid) {
      movePiece(cell)
    } else {
      selected.value = null
    }
  } else if (cell.piece) {
    selected.value = cell
  }
}

// ────── Movimientos legales de piezas ──────

// Peón
const getPawnMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dir = piece.color === 'white' ? -1 : 1
  const startRow = piece.color === 'white' ? 6 : 1

  const forward1Row = r + dir
  const forward2Row = r + 2 * dir

  // helper seguro: evitamos duplicar checks
  const get = (row, col) => {
    if (!isInsideBoard(row, col)) return null
    return getPieceAt(row, col)
  }

  // avanzar 1 casilla
  if (isInsideBoard(forward1Row, c) && !get(forward1Row, c)) {
    moves.push(board.value[forward1Row][c])

    // avanzar 2
    if (
      r === startRow &&
      isInsideBoard(forward2Row, c) &&
      !get(forward2Row, c)
    ) {
      moves.push(board.value[forward2Row][c])
    }
  }
  // Capturas diagonales
  for (const dc of [-1, 1]) {
    const tr = forward1Row
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue

    const targetPiece = get(tr, tc)
    if (targetPiece && targetPiece.color !== piece.color) {
      moves.push(board.value[tr][tc])
    }
  }

  return moves
}

// TORRE
const getRookMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  // helper seguro: evitamos duplicar checks
  const get = (row, col) => {
    if (!isInsideBoard(row, col)) return null
    return getPieceAt(row, col)
  }

  // Direcciones: arriba, abajo, izquierda, derecha
  const directions = [
    [-1, 0], [1, 0], [0, -1], [0, 1]
  ]

  for (const [dr, dc] of directions) {
    let tr = r + dr
    let tc = c + dc

    while (isInsideBoard(tr, tc)) {
      const targetPiece = get(tr, tc)

      if (!targetPiece) {
        moves.push(board.value[tr][tc])
      } else {
        if (targetPiece.color !== piece.color) {
          moves.push(board.value[tr][tc])
        }
        break
      }

      tr += dr
      tc += dc
    }
  }

  return moves
}

// ALFIL
const getBishopMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  // helper seguro: evitamos duplicar checks
  const get = (row, col) => {
    if (!isInsideBoard(row, col)) return null
    return getPieceAt(row, col)
  }

  // Direcciones diagonales
  const directions = [
    [-1, -1], [-1, 1], [1, -1], [1, 1]
  ]

  for (const [dr, dc] of directions) {
    let tr = r + dr
    let tc = c + dc

    while (isInsideBoard(tr, tc)) {
      const targetPiece = get(tr, tc)

      if (!targetPiece) {
        moves.push(board.value[tr][tc])
      } else {
        if (targetPiece.color !== piece.color) {
          moves.push(board.value[tr][tc])
        }
        break
      }

      tr += dr
      tc += dc
    }
  }

  return moves
}

// REINA
const getQueenMoves = (cell) => {
  // La reina combina los movimientos de torre y alfil
  return [...getRookMoves(cell), ...getBishopMoves(cell)]
}

// CABALLO
const getKnightMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  // helper seguro: evitamos duplicar checks
  const get = (row, col) => {
    if (!isInsideBoard(row, col)) return null
    return getPieceAt(row, col)
  }

  // Movimientos en "L"
  const knightMoves = [
    [-2, -1], [-2, 1], [-1, -2], [-1, 2],
    [1, -2], [1, 2], [2, -1], [2, 1]
  ]

  for (const [dr, dc] of knightMoves) {
    const tr = r + dr
    const tc = c + dc

    if (isInsideBoard(tr, tc)) {
      const targetPiece = get(tr, tc)

      if (!targetPiece || targetPiece.color !== piece.color) {
        moves.push(board.value[tr][tc])
      }
    }
  }

  return moves
}

// REY
const getKingMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  // helper seguro: evitamos duplicar checks
  const get = (row, col) => {
    if (!isInsideBoard(row, col)) return null
    return getPieceAt(row, col)
  }

  // Movimientos a las 8 casillas adyacentes
  const kingMoves = [
    [-1, -1], [-1, 0], [-1, 1],
    [0, -1],           [0, 1],
    [1, -1], [1, 0], [1, 1]
  ]

  for (const [dr, dc] of kingMoves) {
    const tr = r + dr
    const tc = c + dc

    if (isInsideBoard(tr, tc)) {
      const targetPiece = get(tr, tc)

      if (!targetPiece || targetPiece.color !== piece.color) {
        moves.push(board.value[tr][tc])
      }
    }
  }

  return moves
}

// dispatcher de movimientos legales
const getLegalMoves = (cell) => {
  if (!cell.piece) return []

  switch (cell.piece.type) {
    case 'pawn':
      return getPawnMoves(cell)
    case 'rook':
      return getRookMoves(cell)
    case 'bishop':
      return getBishopMoves(cell)
    case 'queen':
      return getQueenMoves(cell)
    case 'knight':
      return getKnightMoves(cell)
    case 'king':
      return getKingMoves(cell)
    default:
      return []
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