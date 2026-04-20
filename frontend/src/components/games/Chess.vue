<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../lib/axios'

const emit = defineEmits(['game-completed'])
const route = useRoute()

// ────── Crear Tablero ──────
const createEmptyBoard = () => (
  Array.from({ length: 8 }, (_, row) =>
    Array.from({ length: 8 }, (_, col) => ({
      row,
      col,
      piece: null
    }))
  )
)
const board = ref(createEmptyBoard())

const createInitialCastlingRights = () => ({
  white: { kingSide: true, queenSide: true },
  black: { kingSide: true, queenSide: true }
})

const createDefaultCheckState = () => ({
  inCheck: false,
  targetColor: null,
  attackers: []
})

// ────── Inicializacion de piezas ──────
const initialSetup = (targetBoard) => {
  const newBoard = targetBoard
  const backRow = ['rook', 'knight', 'bishop', 'queen', 'king', 'bishop', 'knight', 'rook']

  newBoard[0].forEach((cell, i) => {
    cell.piece = { type: backRow[i], color: 'black' }
  })
  newBoard[1].forEach(cell => {
    cell.piece = { type: 'pawn', color: 'black' }
  })

  newBoard[6].forEach(cell => {
    cell.piece = { type: 'pawn', color: 'white' }
  })
  newBoard[7].forEach((cell, i) => {
    cell.piece = { type: backRow[i], color: 'white' }
  })
}

const resetBoard = () => {
  board.value = createEmptyBoard()
  initialSetup(board.value)
}

resetBoard()

// ────── Estado ──────
const selected = ref(null)
const currentTurn = ref('white')
const roundNumber = ref(1)
const promotionCell = ref(null)
const moveHistory = ref([])
const pendingPromotionMove = ref(null)
const enPassantTarget = ref(null)
const playerColor = ref(null)
const aiMoveTimer = ref(null)
const gameTimer = ref(null)
const gameDurationSeconds = ref(0)
const resultReported = ref(false)
const resultEventEmitted = ref(false)
const resultReportPending = ref(false)
const playerQueenCaptured = ref(false)
const castlingRights = ref(createInitialCastlingRights())
const checkState = ref(createDefaultCheckState())

// ────── Helpers ──────
const isInsideBoard = (row, col) => 
  row >= 0 && row < 8 && col >= 0 && col < 8

const get = (row, col) => {
  if (!isInsideBoard(row, col)) return null
  return board.value[row][col].piece
}

const getPieceImage = (piece) => {
  if (!piece?.type || !piece?.color) return ''
  return `/assets/boardKing/${piece.color}-${piece.type}.svg`
}

const FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']
const SAN_PIECE = {
  king: 'K',
  queen: 'Q',
  rook: 'R',
  bishop: 'B',
  knight: 'N',
  pawn: ''
}
const SAN_PROMOTION = {
  queen: 'Q',
  rook: 'R',
  bishop: 'B',
  knight: 'N'
}
const HOME_ROW_BY_COLOR = {
  white: 7,
  black: 0
}

const gameStarted = computed(() => playerColor.value !== null)
const aiColor = computed(() => {
  if (!playerColor.value) return null
  return playerColor.value === 'white' ? 'black' : 'white'
})
const isAiTurn = computed(() => gameStarted.value && currentTurn.value === aiColor.value)
const formattedGameDuration = computed(() => {
  const totalSeconds = gameDurationSeconds.value
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60

  const paddedMinutes = String(minutes).padStart(2, '0')
  const paddedSeconds = String(seconds).padStart(2, '0')

  if (hours > 0) {
    return `${String(hours).padStart(2, '0')}:${paddedMinutes}:${paddedSeconds}`
  }

  return `${paddedMinutes}:${paddedSeconds}`
})

const clearGameTimer = () => {
  if (!gameTimer.value) return
  clearInterval(gameTimer.value)
  gameTimer.value = null
}

const startGameTimer = () => {
  clearGameTimer()
  gameDurationSeconds.value = 0

  const startedAt = Date.now()
  gameTimer.value = setInterval(() => {
    gameDurationSeconds.value = Math.floor((Date.now() - startedAt) / 1000)
  }, 1000)
}

const startGame = (color) => {
  if (color !== 'white' && color !== 'black') return
  resetBoard()
  currentTurn.value = 'white'
  roundNumber.value = 1
  moveHistory.value = []
  promotionCell.value = null
  pendingPromotionMove.value = null
  enPassantTarget.value = null
  castlingRights.value = createInitialCastlingRights()
  checkState.value = createDefaultCheckState()
  resultReportPending.value = false
  playerColor.value = color
  selected.value = null
  resultReported.value = false
  resultEventEmitted.value = false
  playerQueenCaptured.value = false
  updateCheckState()
  startGameTimer()
}

const restartGame = async () => {
  if (!gameStarted.value || resultReportPending.value) return

  // Evita perder el resultado si se reinicia justo al terminar la partida.
  if (gameOver.value && !resultReported.value) {
    await reportGameResult()
  }

  resetBoard()
  currentTurn.value = 'white'
  roundNumber.value = 1
  selected.value = null
  moveHistory.value = []
  promotionCell.value = null
  pendingPromotionMove.value = null
  enPassantTarget.value = null
  castlingRights.value = createInitialCastlingRights()
  checkState.value = createDefaultCheckState()
  resultReported.value = false
  resultEventEmitted.value = false
  resultReportPending.value = false
  playerQueenCaptured.value = false
  updateCheckState()
  startGameTimer()
}

const toSquare = (row, col) => `${FILES[col]}${8 - row}`

const advanceTurn = () => {
  const finishedTurn = currentTurn.value
  currentTurn.value = finishedTurn === 'white' ? 'black' : 'white'

  if (finishedTurn === 'black') {
    roundNumber.value += 1
  }
}

const setEnPassantTargetAfterMove = ({ piece, fromCell, toCell }) => {
  enPassantTarget.value = null

  if (piece.type !== 'pawn') return
  if (Math.abs(toCell.row - fromCell.row) !== 2) return

  enPassantTarget.value = {
    row: (fromCell.row + toCell.row) / 2,
    col: fromCell.col,
    capturedRow: toCell.row,
    capturedCol: toCell.col,
    pawnColor: piece.color
  }
}

const getEnPassantCaptureCell = (grid, fromCell, toCell, moverColor) => {
  const target = enPassantTarget.value
  if (!target) return null
  if (target.row !== toCell.row || target.col !== toCell.col) return null

  if (Math.abs(fromCell.row - toCell.row) !== 1 || Math.abs(fromCell.col - toCell.col) !== 1) {
    return null
  }

  const captureCell = grid[target.capturedRow]?.[target.capturedCol]
  const capturedPiece = captureCell?.piece

  if (!capturedPiece || capturedPiece.type !== 'pawn') return null
  if (capturedPiece.color === moverColor) return null
  if (capturedPiece.color !== target.pawnColor) return null

  return captureCell
}

const getMoveDisambiguation = (fromCell, toCell, piece) => {
  if (piece.type === 'pawn' || piece.type === 'king') return ''

  const competitors = []
  for (const row of board.value) {
    for (const cell of row) {
      if (!cell.piece) continue
      if (cell.row === fromCell.row && cell.col === fromCell.col) continue
      if (cell.piece.color !== piece.color || cell.piece.type !== piece.type) continue

      const moves = getLegalMovesForColor(cell, piece.color)
      if (moves.some(m => m.row === toCell.row && m.col === toCell.col)) {
        competitors.push(cell)
      }
    }
  }

  if (competitors.length === 0) return ''

  const sameFile = competitors.some(cell => cell.col === fromCell.col)
  const sameRank = competitors.some(cell => cell.row === fromCell.row)
  const filePart = FILES[fromCell.col]
  const rankPart = String(8 - fromCell.row)

  if (!sameFile) return filePart
  if (!sameRank) return rankPart
  return `${filePart}${rankPart}`
}

const createMoveNotationBase = ({ fromCell, toCell, piece, isCapture }) => {
  if (piece.type === 'king' && Math.abs(toCell.col - fromCell.col) === 2) {
    return toCell.col === 6 ? 'O-O' : 'O-O-O'
  }

  const targetSquare = toSquare(toCell.row, toCell.col)

  if (piece.type === 'pawn') {
    return isCapture
      ? `${FILES[fromCell.col]}x${targetSquare}`
      : targetSquare
  }

  const pieceSan = SAN_PIECE[piece.type] ?? ''
  const disambiguation = getMoveDisambiguation(fromCell, toCell, piece)
  const captureMark = isCapture ? 'x' : ''
  return `${pieceSan}${disambiguation}${captureMark}${targetSquare}`
}

const hasAnyLegalMove = (color) => {
  for (const row of board.value) {
    for (const cell of row) {
      if (!cell.piece || cell.piece.color !== color) continue
      if (getLegalMovesForColor(cell, color).length > 0) {
        return true
      }
    }
  }
  return false
}

const getBoardPieces = () => {
  const pieces = []

  for (const row of board.value) {
    for (const cell of row) {
      if (!cell.piece) continue
      pieces.push({
        type: cell.piece.type,
        color: cell.piece.color,
        row: cell.row,
        col: cell.col
      })
    }
  }

  return pieces
}

const isInsufficientMaterial = () => {
  const pieces = getBoardPieces()
  const nonKings = pieces.filter(piece => piece.type !== 'king')

  if (nonKings.length === 0) return true

  if (nonKings.some(piece => ['pawn', 'rook', 'queen'].includes(piece.type))) {
    return false
  }

  const whiteMinors = nonKings.filter(piece => piece.color === 'white')
  const blackMinors = nonKings.filter(piece => piece.color === 'black')
  const countByType = (arr, type) => arr.filter(piece => piece.type === type).length

  const whiteBishops = countByType(whiteMinors, 'bishop')
  const blackBishops = countByType(blackMinors, 'bishop')
  const whiteKnights = countByType(whiteMinors, 'knight')
  const blackKnights = countByType(blackMinors, 'knight')
  const minorCount = nonKings.length

  // Rey vs rey + pieza menor.
  if (minorCount === 1) return true

  // Configuraciones mínimas que no permiten mate forzado ni accidental.
  if (minorCount === 2) {
    if (whiteBishops === 1 && blackBishops === 1 && whiteKnights === 0 && blackKnights === 0) {
      return true
    }
    if (whiteKnights === 1 && blackKnights === 1 && whiteBishops === 0 && blackBishops === 0) {
      return true
    }
    if (whiteKnights === 2 && blackBishops === 0 && blackKnights === 0 && whiteBishops === 0) {
      return true
    }
    if (blackKnights === 2 && whiteBishops === 0 && whiteKnights === 0 && blackBishops === 0) {
      return true
    }
  }

  // Solo alfiles en casillas del mismo color.
  const onlyBishops = nonKings.every(piece => piece.type === 'bishop')
  if (onlyBishops) {
    const bishopSquareColors = new Set(nonKings.map(piece => (piece.row + piece.col) % 2))
    if (bishopSquareColors.size === 1) return true
  }

  return false
}

const hasLegalMoveForTurn = computed(() => hasAnyLegalMove(currentTurn.value))

const checkmateState = computed(() => {
  if (!checkState.value.inCheck || !checkState.value.targetColor) {
    return { isMate: false, targetColor: null }
  }

  const targetColor = checkState.value.targetColor
  return {
    isMate: !hasLegalMoveForTurn.value,
    targetColor
  }
})

const drawState = computed(() => {
  if (checkmateState.value.isMate) {
    return { isDraw: false, reason: null }
  }

  if (isInsufficientMaterial()) {
    return { isDraw: true, reason: 'insufficient-material' }
  }

  if (!checkState.value.inCheck && !hasLegalMoveForTurn.value) {
    return { isDraw: true, reason: 'stalemate' }
  }

  return { isDraw: false, reason: null }
})

const gameOver = computed(() => checkmateState.value.isMate || drawState.value.isDraw)
const canAiPlay = computed(() =>
  isAiTurn.value &&
  !gameOver.value &&
  !promotionCell.value
)

const playerResult = computed(() => {
  if (!gameStarted.value || !playerColor.value || !gameOver.value) return null

  if (drawState.value.isDraw) {
    return 'draw'
  }

  if (!checkmateState.value.isMate || !checkmateState.value.targetColor) {
    return null
  }

  const winnerColor = checkmateState.value.targetColor === 'white' ? 'black' : 'white'
  return winnerColor === playerColor.value ? 'win' : 'loss'
})

const resultLabel = computed(() => {
  if (playerResult.value === 'win') return 'Victoria'
  if (playerResult.value === 'draw') return 'Empate'
  if (playerResult.value === 'loss') return 'Derrota'
  return null
})

const buildResultPayload = (result) => {
  return {
    wins: result === 'win' ? 1 : 0,
    draws: result === 'draw' ? 1 : 0,
    losses: result === 'loss' ? 1 : 0,
    wins_without_queen_loss: result === 'win' && !playerQueenCaptured.value ? 1 : 0,
    time_played: Math.max(gameDurationSeconds.value, 0),
  }
}

const reportGameResult = async () => {
  if (resultReported.value || resultReportPending.value) return

  const result = playerResult.value
  if (!result) return

  // Actualiza el panel en vivo aunque el backend tarde en confirmar.
  if (!resultEventEmitted.value) {
    emit('game-completed', { result })
    resultEventEmitted.value = true
  }

  resultReportPending.value = true

  try {
    await api.post(`/games/${route.params.slug}/stats`, buildResultPayload(result))
    resultReported.value = true
  } catch {
    resultReported.value = false
  } finally {
    resultReportPending.value = false
  }
}

const withCheckSuffix = (san) => {
  if (!checkState.value.inCheck || checkState.value.targetColor !== currentTurn.value) {
    return san
  }

  const isMate = !hasAnyLegalMove(currentTurn.value)
  return `${san}${isMate ? '#' : '+'}`
}

const pushMoveToHistory = (baseSan, promotionType = null) => {
  let san = baseSan
  if (promotionType) {
    san += `=${SAN_PROMOTION[promotionType] ?? 'Q'}`
  }
  moveHistory.value.push(withCheckSuffix(san))
}

const isCastlingMove = (piece, fromCell, toCell) => {
  return piece.type === 'king' &&
    fromCell.row === toCell.row &&
    Math.abs(toCell.col - fromCell.col) === 2
}

const applyCastlingRookMove = (grid, fromCell, toCell, color) => {
  if (toCell.col !== 6 && toCell.col !== 2) return true

  const row = fromCell.row
  const rookFromCol = toCell.col === 6 ? 7 : 0
  const rookToCol = toCell.col === 6 ? 5 : 3
  const rookPiece = grid[row][rookFromCol].piece

  if (!rookPiece || rookPiece.type !== 'rook' || rookPiece.color !== color) {
    return false
  }

  grid[row][rookToCol].piece = rookPiece
  grid[row][rookFromCol].piece = null
  return true
}

const disableCastlingSide = (color, side) => {
  if (!castlingRights.value[color][side]) return
  castlingRights.value[color][side] = false
}

const disableCastlingForRookStart = (color, row, col) => {
  if (row !== HOME_ROW_BY_COLOR[color]) return
  if (col === 0) disableCastlingSide(color, 'queenSide')
  if (col === 7) disableCastlingSide(color, 'kingSide')
}

const updateCastlingRightsAfterMove = ({ piece, fromCell, toCell, capturedPiece }) => {
  if (piece.type === 'king') {
    disableCastlingSide(piece.color, 'kingSide')
    disableCastlingSide(piece.color, 'queenSide')
  }

  if (piece.type === 'rook') {
    disableCastlingForRookStart(piece.color, fromCell.row, fromCell.col)
  }

  if (capturedPiece?.type === 'rook') {
    disableCastlingForRookStart(capturedPiece.color, toCell.row, toCell.col)
  }
}

const moveRows = computed(() => {
  const rows = []
  for (let i = 0; i < moveHistory.value.length; i += 2) {
    rows.push({
      round: i / 2 + 1,
      white: moveHistory.value[i] ?? '',
      black: moveHistory.value[i + 1] ?? ''
    })
  }
  return rows
})

// ────── Movimiento ──────
const movePiece = (targetCell, options = {}) => {
  const fromCell = options.fromCell ?? selected.value
  if (!fromCell || !fromCell.piece) return

  const piece = { ...fromCell.piece }
  const enPassantCaptureCell = piece.type === 'pawn' && !targetCell.piece
    ? getEnPassantCaptureCell(board.value, fromCell, targetCell, piece.color)
    : null

  const capturedPiece = enPassantCaptureCell?.piece
    ? { ...enPassantCaptureCell.piece }
    : (targetCell.piece ? { ...targetCell.piece } : null)

  if (capturedPiece?.type === 'queen' && capturedPiece.color === playerColor.value) {
    playerQueenCaptured.value = true
  }

  const isCapture = Boolean(capturedPiece)
  const baseSan = createMoveNotationBase({ fromCell, toCell: targetCell, piece, isCapture })
  const castlingMove = isCastlingMove(piece, fromCell, targetCell)

  targetCell.piece = piece
  fromCell.piece = null

  if (enPassantCaptureCell) {
    enPassantCaptureCell.piece = null
  }

  if (castlingMove) {
    applyCastlingRookMove(board.value, fromCell, targetCell, piece.color)
  }

  setEnPassantTargetAfterMove({ piece, fromCell, toCell: targetCell })
  updateCastlingRightsAfterMove({ piece, fromCell, toCell: targetCell, capturedPiece })
  selected.value = null

  // Promoción
  if (piece.type === 'pawn') {
    const isPromotion =
      (piece.color === 'white' && targetCell.row === 0) ||
      (piece.color === 'black' && targetCell.row === 7)

    if (isPromotion) {
      const autoPromotionType = options.autoPromotionType
      if (autoPromotionType) {
        const allowedTypes = new Set(['queen', 'rook', 'bishop', 'knight'])
        piece.type = allowedTypes.has(autoPromotionType) ? autoPromotionType : 'queen'
        targetCell.piece = piece
        advanceTurn()
        updateCheckState()
        pushMoveToHistory(baseSan, piece.type)
        return
      }

      pendingPromotionMove.value = baseSan
      promotionCell.value = targetCell
      return
    }
  }

  advanceTurn()
  updateCheckState()
  pushMoveToHistory(baseSan)
}

// ────── Click ──────
const handleClick = (cell) => {
  if (!gameStarted.value || isAiTurn.value) return

  if (checkmateState.value.isMate || drawState.value.isDraw) return

  if (selected.value && !selected.value.piece) {
    selected.value = null
  }

  if (promotionCell.value) return

  if (selected.value) {
    const moves = getLegalMoves(selected.value)

    if (moves.includes(cell)) {
      movePiece(cell)
    } else {
      selected.value = null
    }
    return
  }

  if (cell.piece && cell.piece.color === currentTurn.value && cell.piece.color === playerColor.value) {
    selected.value = cell
  }
}

// ────── Promoción ──────
const promotePawn = (type) => {
  if (!promotionCell.value) return

  const promotedPiece = promotionCell.value.piece
  if (!promotedPiece) {
    promotionCell.value = null
    selected.value = null
    pendingPromotionMove.value = null
    return
  }

  const allowedTypes = new Set(['queen', 'rook', 'bishop', 'knight'])
  promotedPiece.type = allowedTypes.has(type) ? type : 'queen'
  promotionCell.value = null
  selected.value = null

  advanceTurn()
  updateCheckState()

  if (pendingPromotionMove.value) {
    pushMoveToHistory(pendingPromotionMove.value, promotedPiece.type)
    pendingPromotionMove.value = null
  }
}

// ────── Movimientos base ──────
const getSlidingMoves = (cell, directions) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  for (const [dr, dc] of directions) {
    let tr = r + dr
    let tc = c + dc

    while (isInsideBoard(tr, tc)) {
      const target = get(tr, tc)

      if (!target) {
        moves.push(board.value[tr][tc])
      } else {
        if (target.color !== piece.color) {
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

const getPawnAttacksFromBoard = (cell) => {
  const attacks = []
  const { row: r, col: c, piece } = cell
  const dir = piece.color === 'white' ? -1 : 1
  const tr = r + dir

  for (const dc of [-1, 1]) {
    const tc = c + dc
    if (!isInsideBoard(tr, tc)) continue
    attacks.push({ row: tr, col: tc })
  }

  return attacks
}
const getPawnMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dir = piece.color === 'white' ? -1 : 1
  const startRow = piece.color === 'white' ? 6 : 1

  const f1 = r + dir
  const f2 = r + 2 * dir

  if (isInsideBoard(f1, c) && !get(f1, c)) {
    moves.push(board.value[f1][c])

    if (r === startRow && isInsideBoard(f2, c) && !get(f2, c)) {
      moves.push(board.value[f2][c])
    }
  }

  for (const dc of [-1, 1]) {
    const tr = f1
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const target = get(tr, tc)
    const targetCell = board.value[tr][tc]

    if (target && target.color !== piece.color) {
      moves.push(targetCell)
      continue
    }

    const enPassantCaptureCell = getEnPassantCaptureCell(board.value, cell, targetCell, piece.color)
    if (enPassantCaptureCell) {
      moves.push(targetCell)
    }
  }

  return moves
}

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

const getKingMoves = (cell) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dirs = [
    [-1,-1],[-1,0],[-1,1],
    [0,-1],[0,1],
    [1,-1],[1,0],[1,1]
  ]

  for (const [dr, dc] of dirs) {
    const tr = r + dr
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const target = get(tr, tc)

    if (!target || target.color !== piece.color) {
      moves.push(board.value[tr][tc])
    }
  }

  if (r === HOME_ROW_BY_COLOR[piece.color] && c === 4) {
    if (canCastle(piece.color, 'kingSide')) {
      moves.push(board.value[r][6])
    }
    if (canCastle(piece.color, 'queenSide')) {
      moves.push(board.value[r][2])
    }
  }

  return moves
}

const getPseudoMoves = (cell) => {
  switch (cell.piece.type) {
    case 'pawn': return getPawnMoves(cell)
    case 'rook': return getSlidingMoves(cell,[[-1,0],[1,0],[0,-1],[0,1]])
    case 'bishop': return getSlidingMoves(cell,[[-1,-1],[-1,1],[1,-1],[1,1]])
    case 'queen': return getSlidingMoves(cell,[[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[-1,1],[1,-1],[1,1]])
    case 'knight': return getKnightMoves(cell)
    case 'king': return getKingMoves(cell)
    default: return []
  }
}

// ────── Motor real ──────
const cloneBoard = () =>
  board.value.map(r =>
    r.map(c => ({
      row: c.row,
      col: c.col,
      piece: c.piece ? { ...c.piece } : null
    }))
  )

const simulateMove = (b, from, to) => {
  const piece = b[from.row][from.col].piece
  if (!piece) return false

  const enPassantCaptureCell = piece.type === 'pawn' && !b[to.row][to.col].piece
    ? getEnPassantCaptureCell(b, from, to, piece.color)
    : null

  if (piece.type === 'pawn' && from.col !== to.col && !b[to.row][to.col].piece && !enPassantCaptureCell) {
    return false
  }

  const castlingMove = piece.type === 'king' && from.row === to.row && Math.abs(to.col - from.col) === 2

  b[to.row][to.col].piece = piece
  b[from.row][from.col].piece = null

  if (enPassantCaptureCell) {
    enPassantCaptureCell.piece = null
  }

  if (castlingMove && !applyCastlingRookMove(b, from, to, piece.color)) {
    return false
  }

  return true
}

const findKing = (b, color) => {
  for (const r of b) {
    for (const c of r) {
      if (c.piece?.type === 'king' && c.piece.color === color) {
        return c
      }
    }
  }
  return null
}

const getFromBoard = (b, r, c) => {
  if (!isInsideBoard(r, c)) return null
  return b[r]?.[c]?.piece
}

const getPseudoMovesFromBoard = (cell, b) => {
  const piece = b[cell.row][cell.col].piece
  if (!piece) return []

  const fake = { row: cell.row, col: cell.col, piece }

  switch (piece.type) {
    // Para detectar jaque usamos casillas atacadas, no avance frontal de peón.
    case 'pawn': return getPawnAttacksFromBoard(fake)
    case 'rook': return getSlidingMovesFromBoard(fake, b,[[-1,0],[1,0],[0,-1],[0,1]])
    case 'bishop': return getSlidingMovesFromBoard(fake, b,[[-1,-1],[-1,1],[1,-1],[1,1]])
    case 'queen': return getSlidingMovesFromBoard(fake, b,[[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[-1,1],[1,-1],[1,1]])
    case 'knight': return getKnightMovesFromBoard(fake, b)
    case 'king': return getKingMovesFromBoard(fake, b)
  }
  return []
}

const getSlidingMovesFromBoard = (cell, b, dirs) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  for (const [dr, dc] of dirs) {
    let tr = r + dr
    let tc = c + dc

    while (isInsideBoard(tr, tc)) {
      const t = getFromBoard(b, tr, tc)

      if (!t) moves.push({ row: tr, col: tc })
      else {
        if (t.color !== piece.color) moves.push({ row: tr, col: tc })
        break
      }

      tr += dr
      tc += dc
    }
  }
  return moves
}

const getPawnMovesFromBoard = (cell, b) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dir = piece.color === 'white' ? -1 : 1
  const start = piece.color === 'white' ? 6 : 1

  const f1 = r + dir
  const f2 = r + 2 * dir

  if (isInsideBoard(f1, c) && !getFromBoard(b, f1, c)) {
    moves.push({ row: f1, col: c })

    if (r === start && isInsideBoard(f2, c) && !getFromBoard(b, f2, c)) {
      moves.push({ row: f2, col: c })
    }
  }

  for (const dc of [-1, 1]) {
    const tr = f1
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const t = getFromBoard(b, tr, tc)

    if (t && t.color !== piece.color) {
      moves.push({ row: tr, col: tc })
    }
  }

  return moves
}

const getKnightMovesFromBoard = (cell, b) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const jumps = [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]]

  for (const [dr, dc] of jumps) {
    const tr = r + dr
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const t = getFromBoard(b, tr, tc)

    if (!t || t.color !== piece.color) {
      moves.push({ row: tr, col: tc })
    }
  }
  return moves
}

const getKingMovesFromBoard = (cell, b) => {
  const moves = []
  const { row: r, col: c, piece } = cell

  const dirs = [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]]

  for (const [dr, dc] of dirs) {
    const tr = r + dr
    const tc = c + dc

    if (!isInsideBoard(tr, tc)) continue
    const t = getFromBoard(b, tr, tc)

    if (!t || t.color !== piece.color) {
      moves.push({ row: tr, col: tc })
    }
  }
  return moves
}

const isSquareAttacked = (b, row, col, byColor) => {
  for (const r of b) {
    for (const c of r) {
      if (c.piece?.color !== byColor) continue

      const moves = getPseudoMovesFromBoard(c, b)
      if (moves.some(m => m.row === row && m.col === col)) {
        return true
      }
    }
  }
  return false
}

const canCastle = (color, side) => {
  if (!castlingRights.value[color][side]) return false

  const row = HOME_ROW_BY_COLOR[color]
  const king = board.value[row][4].piece
  if (!king || king.type !== 'king' || king.color !== color) return false

  const rookFromCol = side === 'kingSide' ? 7 : 0
  const rook = board.value[row][rookFromCol].piece
  if (!rook || rook.type !== 'rook' || rook.color !== color) return false

  const betweenCols = side === 'kingSide' ? [5, 6] : [1, 2, 3]
  if (betweenCols.some(col => board.value[row][col].piece)) return false

  const enemy = color === 'white' ? 'black' : 'white'
  if (isSquareAttacked(board.value, row, 4, enemy)) return false

  const kingPathCols = side === 'kingSide' ? [5, 6] : [3, 2]
  if (kingPathCols.some(col => isSquareAttacked(board.value, row, col, enemy))) {
    return false
  }

  return true
}

const isKingInCheck = (b, color) => {
  const king = findKing(b, color)
  if (!king) return false

  const enemy = color === 'white' ? 'black' : 'white'

  for (const r of b) {
    for (const c of r) {
      if (c.piece?.color === enemy) {
        const moves = getPseudoMovesFromBoard(c, b)
        if (moves.some(m => m.row === king.row && m.col === king.col)) {
          return true
        }
      }
    }
  }
  return false
}

const getCheckingAttackers = (b, color) => {
  const king = findKing(b, color)
  if (!king) return []

  const enemy = color === 'white' ? 'black' : 'white'
  const attackers = []

  for (const r of b) {
    for (const c of r) {
      if (c.piece?.color !== enemy) continue

      const moves = getPseudoMovesFromBoard(c, b)
      if (moves.some(m => m.row === king.row && m.col === king.col)) {
        attackers.push({ row: c.row, col: c.col })
      }
    }
  }

  return attackers
}

const updateCheckState = () => {
  const attackers = getCheckingAttackers(board.value, currentTurn.value)

  if (attackers.length > 0) {
    checkState.value = {
      inCheck: true,
      targetColor: currentTurn.value,
      attackers
    }
    return
  }

  checkState.value = {
    inCheck: false,
    targetColor: null,
    attackers: []
  }
}

const isCheckingAttacker = (cell) => {
  return checkState.value.attackers.some(attacker =>
    attacker.row === cell.row && attacker.col === cell.col
  )
}

const getLegalMovesForColor = (cell, color) => {
  if (!cell.piece || cell.piece.color !== color) return []

  const pseudo = getPseudoMoves(cell)

  return pseudo.filter(target => {
    const b = cloneBoard()
    if (!simulateMove(b, cell, target)) return false
    return !isKingInCheck(b, cell.piece.color)
  })
}

const getLegalMoves = (cell) => {
  return getLegalMovesForColor(cell, currentTurn.value)
}

const getAllLegalMovesForColor = (color) => {
  const legalMoves = []

  for (const row of board.value) {
    for (const cell of row) {
      if (!cell.piece || cell.piece.color !== color) continue

      const targets = getLegalMovesForColor(cell, color)
      for (const target of targets) {
        const isEnPassantCapture =
          cell.piece.type === 'pawn' &&
          !target.piece &&
          cell.col !== target.col &&
          Boolean(getEnPassantCaptureCell(board.value, cell, target, color))

        const isCapture = Boolean(target.piece) || isEnPassantCapture
        const isPromotion =
          cell.piece.type === 'pawn' &&
          ((color === 'white' && target.row === 0) || (color === 'black' && target.row === 7))

        legalMoves.push({
          fromCell: cell,
          toCell: target,
          isCapture,
          isPromotion
        })
      }
    }
  }

  return legalMoves
}

const chooseAiMove = (moves) => {
  if (moves.length === 0) return null

  const promotionCaptures = moves.filter(move => move.isPromotion && move.isCapture)
  const promotions = moves.filter(move => move.isPromotion)
  const captures = moves.filter(move => move.isCapture)

  const pool =
    promotionCaptures.length > 0 ? promotionCaptures :
    promotions.length > 0 ? promotions :
    captures.length > 0 ? captures :
    moves

  const index = Math.floor(Math.random() * pool.length)
  return pool[index]
}

const clearAiTimer = () => {
  if (!aiMoveTimer.value) return
  clearTimeout(aiMoveTimer.value)
  aiMoveTimer.value = null
}

const runAiMove = () => {
  if (!canAiPlay.value || !aiColor.value) return

  const legalMoves = getAllLegalMovesForColor(aiColor.value)
  const selectedMove = chooseAiMove(legalMoves)
  if (!selectedMove) return

  movePiece(selectedMove.toCell, {
    fromCell: selectedMove.fromCell,
    autoPromotionType: 'queen'
  })
}

const scheduleAiMove = () => {
  if (!canAiPlay.value) {
    clearAiTimer()
    return
  }

  clearAiTimer()
  aiMoveTimer.value = setTimeout(() => {
    aiMoveTimer.value = null
    runAiMove()
  }, 350)
}

watch(canAiPlay, (nextCanPlay) => {
  if (nextCanPlay) {
    scheduleAiMove()
    return
  }

  clearAiTimer()
}, { immediate: true })

watch(gameOver, (isOver) => {
  if (!isOver) return
  clearGameTimer()
  void reportGameResult()
})

onBeforeUnmount(() => {
  clearAiTimer()
  clearGameTimer()
})

updateCheckState()
</script>

<template>
  <div class="container-board">
    <div v-if="!gameStarted" class="start-overlay">
      <div class="start-card">
        <h2 class="start-title">Elige tu color</h2>
        <p class="start-subtitle">El color contrario lo jugará la IA.</p>
        <div class="start-actions">
          <button class="start-button" @click="startGame('white')">Jugar con Blancas</button>
          <button class="start-button start-button-secondary" @click="startGame('black')">Jugar con Negras</button>
        </div>
      </div>
    </div>
    
    <div class="turn">
      Turno: {{ currentTurn === 'white' ? 'Blancas' : 'Negras' }} · Ronda: {{ roundNumber }}
      <span v-if="gameStarted"> · Tú: {{ playerColor === 'white' ? 'Blancas' : 'Negras' }}</span>
      <span v-if="gameStarted"> · Tiempo: {{ formattedGameDuration }}</span>
    </div>
    <div v-if="canAiPlay" class="ai-status">La IA está pensando...</div>
    <div v-if="checkmateState.isMate" class="mate-alert">
      Jaque Mate a {{ checkmateState.targetColor === 'white' ? 'Blancas' : 'Negras' }}
    </div>
    <div v-else-if="drawState.isDraw" class="draw-alert">
      {{ drawState.reason === 'stalemate' ? 'Tablas por Ahogado' : 'Tablas por Material Insuficiente' }}
    </div>
    <div v-else-if="checkState.inCheck" class="check-alert">
      Jaque a {{ checkState.targetColor === 'white' ? 'Blancas' : 'Negras' }}
    </div>

    <div v-if="gameOver" class="result-actions">
      <p v-if="resultLabel" class="result-label">Resultado: {{ resultLabel }}</p>
      <button
        class="restart-button"
        :disabled="resultReportPending"
        @click="restartGame"
      >
        {{ resultReportPending ? 'Guardando resultado...' : 'Volver a empezar' }}
      </button>
    </div>

    <div class="board">
      <div 
        v-for="(row, rowIndex) in board" 
        :key="rowIndex" 
        class="row"
      >
        <div 
          v-for="(cell, colIndex) in row" 
          :key="colIndex" 
          class="cell"
          :class="[
            (rowIndex + colIndex) % 2 === 0 ? 'white' : 'black',
            selected?.row === cell.row && selected?.col === cell.col ? 'selected' : '',
            isCheckingAttacker(cell) ? 'checking-attacker' : ''
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

    <section class="move-log">
      <h3 class="move-log-title">Registro de jugadas</h3>
      <p v-if="moveRows.length === 0" class="move-log-empty">Sin jugadas todavía.</p>
      <table v-else class="move-log-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Blancas</th>
            <th>Negras</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in moveRows" :key="entry.round">
            <td>{{ entry.round }}.</td>
            <td>{{ entry.white }}</td>
            <td>{{ entry.black || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- PROMOCIÓN -->
    <div v-if="promotionCell !== null" class="promotion-modal">
      <div class="promotion-box">
        <h3>Promoción de Peón</h3>

        <button @click="promotePawn('queen')">♛ Reina</button>
        <button @click="promotePawn('rook')">♜ Torre</button>
        <button @click="promotePawn('bishop')">♝ Alfil</button>
        <button @click="promotePawn('knight')">♞ Caballo</button>
      </div>
    </div>

  </div>
</template>

<style scoped>
.container-board {
  position: relative;
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  /*max-width: 1200px;*/
}

.start-overlay {
  position: absolute;
  inset: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.62);
  border-radius: 10px;
}

.start-card {
  width: min(420px, 92%);
  border-radius: 12px;
  border: 1px solid #0f172a;
  background: #ffffff;
  color: #000000;
  padding: 20px;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.24);
}

.start-title {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
}

.start-subtitle {
  margin: 8px 0 0;
  font-size: 14px;
}

.start-actions {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.start-button {
  border: 1px solid #111827;
  border-radius: 8px;
  background: #111827;
  color: #ffffff;
  padding: 10px 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.start-button:hover {
  background: #1f2937;
}

.start-button-secondary {
  background: #f8fafc;
  color: #111827;
}

.start-button-secondary:hover {
  background: #e2e8f0;
}

.turn {
  margin-bottom: 10px;
  font-weight: bold;
}

.ai-status {
  margin-bottom: 10px;
  padding: 6px 10px;
  border: 1px solid #0369a1;
  border-radius: 8px;
  background: #e0f2fe;
  color: #0c4a6e;
  font-weight: 700;
}

.check-alert {
  margin-bottom: 10px;
  padding: 6px 10px;
  border: 1px solid #f59e0b;
  border-radius: 8px;
  background-color: #fef3c7;
  color: #7c2d12;
  font-weight: 700;
}

.mate-alert {
  margin-bottom: 10px;
  padding: 8px 10px;
  border: 1px solid #b91c1c;
  border-radius: 8px;
  background-color: #fee2e2;
  color: #7f1d1d;
  font-weight: 800;
}

.draw-alert {
  margin-bottom: 10px;
  padding: 8px 10px;
  border: 1px solid #1d4ed8;
  border-radius: 8px;
  background-color: #dbeafe;
  color: #1e3a8a;
  font-weight: 800;
}

.result-actions {
  margin-bottom: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.result-label {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  color: #111827;
}

.restart-button {
  border: 1px solid #111827;
  border-radius: 8px;
  background: #111827;
  color: #ffffff;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.restart-button:hover:not(:disabled) {
  background: #1f2937;
}

.restart-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.move-log {
  margin-top: 14px;
  width: 480px;
  border: 1px solid #d6d3d1;
  border-radius: 8px;
  background: #ffffff;
  color: #000000;
  padding: 10px;
}

.move-log-title {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 700;
  color: #000000;
}

.move-log-empty {
  margin: 0;
  color: #000000;
  font-size: 14px;
}

.move-log-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.move-log-table th,
.move-log-table td {
  text-align: left;
  padding: 4px 6px;
  border-bottom: 1px solid #000000;
  color: #000000;
}

.move-log-table th:first-child,
.move-log-table td:first-child {
  width: 50px;
  color: #000000;
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

.checking-attacker {
  background-color: #fca5a5 !important;
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
  background:rgb(158, 157, 157);
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