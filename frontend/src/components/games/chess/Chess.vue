<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useChessStore } from '../../../stores/games/chess'

const emit = defineEmits(['game-completed'])
const chessStore = useChessStore()
const GAME_SLUG = 'chess'

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

const PIECE_ICONS = {
  king: 'fa6-solid:chess-king',
  queen: 'fa6-solid:chess-queen',
  rook: 'fa6-solid:chess-rook',
  bishop: 'fa6-solid:chess-bishop',
  knight: 'fa6-solid:chess-knight',
  pawn: 'fa6-solid:chess-pawn'
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

const playerColorLabel = computed(() => {
  if (playerColor.value === 'white') return 'Blancas'
  if (playerColor.value === 'black') return 'Negras'
  return 'No seleccionado'
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
  chessStore.initializeGame(false)
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
  chessStore.initializeGame(false)
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

const resultReason = computed(() => {
  if (checkmateState.value.isMate) return 'Jaque Mate'
  if (drawState.value.isDraw) {
    if (drawState.value.reason === 'stalemate') return 'Rey Ahogado (Stalemate)'
    if (drawState.value.reason === 'insufficient-material') return 'Material Insuficiente'
    return 'Tablas'
  }
  return ''
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
    if (result === 'win') {
      await chessStore.recordWin({ moveHistory: moveHistory.value })
    } else {
      await chessStore.saveStats({ moveHistory: moveHistory.value })
    }
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

const downloadMoveLog = () => {
  if (!gameOver.value || moveHistory.value.length === 0) return

  const now = new Date()
  const fileStamp = now.toISOString().replace(/[:.]/g, '-')
  const playerLabel = playerColor.value === 'white' ? 'Blancas' : 'Negras'

  const lines = [
    'Ajedrez - Registro de jugadas',
    `Fecha: ${now.toLocaleString()}`,
    `Jugador: ${playerLabel}`,
    `Resultado: ${resultLabel.value ?? 'Sin resultado'}`,
    ''
  ]

  for (const row of moveRows.value) {
    const whiteMove = row.white || '-'
    const blackMove = row.black || '-'
    lines.push(`${row.round}. ${whiteMove} ${blackMove}`)
  }

  const textContent = `${lines.join('\n')}\n`
  const blob = new Blob([textContent], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = url
  anchor.download = `ajedrez-registro-${fileStamp}.txt`
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
  URL.revokeObjectURL(url)
}

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

// ────── Drag and Drop ──────
const handleDragStart = (e, cell) => {
  if (!gameStarted.value || isAiTurn.value || gameOver.value) return
  if (cell.piece?.color !== currentTurn.value || cell.piece?.color !== playerColor.value) {
    e.preventDefault()
    return
  }

  selected.value = cell
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', JSON.stringify({ row: cell.row, col: cell.col }))
  
  // Custom drag image could be set here if needed
}

const handleDragOver = (e) => {
  if (!selected.value) return
  e.preventDefault()
  e.dataTransfer.dropEffect = 'move'
}

const handleDrop = (e, targetCell) => {
  e.preventDefault()
  if (!selected.value) return

  const moves = getLegalMoves(selected.value)
  if (moves.includes(targetCell)) {
    movePiece(targetCell)
  }
  selected.value = null
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

const legalMovesForSelected = computed(() => {
  if (!selected.value?.piece) return []
  if (promotionCell.value || gameOver.value || isAiTurn.value) return []
  return getLegalMoves(selected.value)
})

const isPossibleMove = (cell) => {
  return legalMovesForSelected.value.some(move => move.row === cell.row && move.col === cell.col)
}

const isCaptureMove = (cell) => {
  if (!selected.value || !selected.value.piece) return false
  if (!isPossibleMove(cell)) return false
  
  // Captura normal: hay una pieza enemiga en el destino
  if (cell.piece && cell.piece.color !== selected.value.piece.color) return true
  
  // Captura al paso (En Passant): peón moviéndose en diagonal a una celda vacía
  if (selected.value.piece.type === 'pawn' && cell.col !== selected.value.col && !cell.piece) {
    const epCell = getEnPassantCaptureCell(board.value, selected.value, cell, selected.value.piece.color)
    if (epCell) return true
  }
  
  return false
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
  reportGameResult()
})

const logRef = ref(null)
watch(moveHistory, () => {
  if (!logRef.value) return
  setTimeout(() => {
    logRef.value.scrollTop = logRef.value.scrollHeight
  }, 100)
}, { deep: true })

onMounted(async () => {
  await chessStore.initializeGame(true)
})

onBeforeUnmount(() => {
  clearGameTimer()
  if (aiMoveTimer.value) clearTimeout(aiMoveTimer.value)
})

updateCheckState()
</script>

<template>
  <div class="gh-panel relative bg-retro-deep p-6 text-retro-white">
    <div class="gh-scanlines pointer-events-none absolute inset-0 opacity-10" />

    <!-- START OVERLAY -->
    <div v-if="!gameStarted" class="absolute inset-0 z-50 flex items-center justify-center bg-retro-black/80 backdrop-blur-sm">
      <div class="gh-panel w-full max-w-md border-neon-cyan/40 bg-retro-dark p-8 text-center shadow-[0_0_30px_rgba(0,242,255,0.1)]">
        <h2 class="gh-title-glow font-display mb-2 text-2xl font-bold uppercase tracking-widest text-white">Centro de Ajedrez</h2>
        <p class="font-pixel mb-8 text-xs tracking-widest text-white/50">Selecciona tu bando para comenzar</p>
        
        <div class="grid gap-4">
          <button 
            class="gh-surface gh-surface-hover border-neon-cyan/20 bg-retro-black py-4 font-display text-sm font-bold uppercase tracking-widest text-neon-cyan"
            @click="startGame('white')"
          >
            Equipo Blanco [Jugar]
          </button>
          <button 
            class="gh-surface gh-surface-hover border-neon-pink/20 bg-retro-black py-4 font-display text-sm font-bold uppercase tracking-widest text-neon-pink"
            @click="startGame('black')"
          >
            Equipo Negro [Jugar]
          </button>
        </div>
      </div>
    </div>
    
    <!-- GAME HEADER / STATUS -->
    <header class="relative z-10 mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-white/5 pb-4">
      <div class="flex items-center gap-4">
        <div class="gh-panel bg-white/5 px-4 py-2">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Turno Actual</p>
          <p 
            class="gh-title-glow font-display text-lg font-bold uppercase tracking-widest"
            :class="currentTurn === 'white' ? 'text-neon-cyan' : 'text-neon-pink'"
          >
            {{ currentTurn === 'white' ? 'BLANCAS' : 'NEGRAS' }}
          </p>
        </div>
        <div class="gh-panel bg-white/5 px-4 py-2">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Ronda</p>
          <p class="font-pixel text-lg font-bold text-neon-cyan">{{ roundNumber }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div v-if="canAiPlay" class="gh-panel border-neon-cyan/20 bg-neon-cyan/5 px-3 py-1 font-pixel text-[10px] uppercase text-neon-cyan">
          La IA está pensando...
        </div>
        <div v-if="checkmateState.isMate" class="gh-panel border-neon-pink/40 bg-neon-pink/10 px-3 py-1 font-pixel text-[10px] uppercase text-neon-pink">
          Jaque Mate detectado
        </div>
        <div v-else-if="drawState.isDraw" class="gh-panel border-white/20 bg-white/5 px-3 py-1 font-pixel text-[10px] uppercase text-white/70">
          Tablas por el sistema
        </div>
        <div v-else-if="checkState.inCheck" class="gh-panel border-neon-yellow/40 bg-neon-yellow/10 px-3 py-1 font-pixel text-[10px] uppercase text-neon-yellow">
          Alerta de Jaque
        </div>
      </div>
    </header>

    <!-- GAME OVER OVERLAY -->
    <div v-if="gameOver" class="absolute inset-0 z-[60] flex items-center justify-center bg-retro-black/90 backdrop-blur-md">
      <div 
        class="gh-panel w-full max-w-lg p-10 text-center shadow-2xl"
        :class="playerResult === 'win' ? 'border-neon-cyan/40 bg-neon-cyan/5 shadow-neon-cyan/10' : playerResult === 'loss' ? 'border-neon-pink/40 bg-neon-pink/5 shadow-neon-pink/10' : 'border-white/20 bg-white/5'"
      >
        <Icon 
          :icon="playerResult === 'win' ? 'fa6-solid:trophy' : playerResult === 'loss' ? 'fa6-solid:skull-crossbones' : 'fa6-solid:handshake'" 
          class="mx-auto mb-6 text-6xl"
          :class="playerResult === 'win' ? 'text-neon-cyan' : playerResult === 'loss' ? 'text-neon-pink' : 'text-white/60'"
        />
        
        <h2 class="gh-title-glow font-display mb-2 text-4xl font-black uppercase tracking-widest text-white">
          {{ resultLabel }}
        </h2>
        <p 
          class="font-pixel mb-10 text-xs tracking-[0.4em] uppercase"
          :class="playerResult === 'win' ? 'text-neon-cyan/70' : playerResult === 'loss' ? 'text-neon-pink/70' : 'text-white/50'"
        >
          {{ resultReason }}
        </p>

        <div class="mb-10 grid grid-cols-2 gap-4 bg-white/5 p-6 font-pixel">
          <div class="text-left border-r border-white/10 pr-4">
            <p class="text-[10px] uppercase text-white/40">Duración</p>
            <p class="text-xl font-bold text-white">{{ formattedGameDuration }}</p>
          </div>
          <div class="text-left pl-4">
            <p class="text-[10px] uppercase text-white/40">Movimientos</p>
            <p class="text-xl font-bold text-white">{{ moveHistory.length }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <button 
            class="gh-surface gh-surface-hover w-full py-4 font-display text-sm font-black uppercase tracking-[0.2em] text-white border-white/20"
            @click="downloadMoveLog"
          >
            Descargar Registro táctico
          </button>
          <button 
            class="gh-surface gh-surface-hover w-full py-4 font-display text-sm font-black uppercase tracking-[0.2em]"
            :class="playerResult === 'win' ? 'bg-neon-cyan/10 border-neon-cyan text-neon-cyan' : 'bg-white/5 border-white/20 text-white'"
            :disabled="resultReportPending"
            @click="restartGame"
          >
            {{ resultReportPending ? 'Sincronizando...' : 'Nueva Partida' }}
          </button>
        </div>
      </div>
    </div>

    <div class="game-columns relative z-10 grid gap-8 lg:grid-cols-[1fr_340px]">
      <div class="board-container flex flex-col items-center">
        <!-- BOARD -->
        <div class="board gh-panel border-white/10 bg-retro-black p-2 shadow-2xl">
          <div 
            v-for="(row, rowIndex) in board" 
            :key="rowIndex" 
            class="row"
          >
            <div 
              v-for="(cell, colIndex) in row" 
              :key="colIndex" 
              class="cell transition-all duration-200"
              :class="[
                (rowIndex + colIndex) % 2 === 0 ? 'cell-white' : 'cell-black',
                selected?.row === cell.row && selected?.col === cell.col ? 'cell-selected' : '',
                isPossibleMove(cell) ? 'cell-possible' : '',
                isCaptureMove(cell) ? 'cell-capture' : '',
                isCheckingAttacker(cell) ? 'cell-check' : ''
              ]"
              @click="handleClick(cell)"
              @dragover="handleDragOver($event)"
              @drop="handleDrop($event, cell)"
            >
              <div v-if="isCaptureMove(cell)" class="capture-reticle">
                <Icon icon="mdi:target-variant" />
              </div>
              <div
                v-if="cell.piece"
                class="piece-container flex items-center justify-center w-full h-full cursor-grab active:cursor-grabbing"
                :draggable="true"
                @dragstart="handleDragStart($event, cell)"
              >
                <Icon
                  :icon="PIECE_ICONS[cell.piece.type]"
                  class="piece w-[75%] h-[75%]"
                  :class="cell.piece.color === 'white' ? 'text-neon-cyan' : 'text-neon-pink'"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SIDEBAR / LOG -->
      <section class="gh-panel flex flex-col bg-retro-dark p-4">
        <div class="mb-4 flex items-center justify-between border-b border-white/5 pb-4">
          <h3 class="font-pixel text-xs font-bold uppercase tracking-widest text-white/50">Registro Táctico</h3>
          <div class="font-pixel text-xl font-bold tracking-widest text-neon-cyan">
            {{ formattedGameDuration }}
          </div>
        </div>

        <div class="mb-4 bg-white/2 p-2">
          <p class="font-pixel text-[10px] uppercase text-white/40">Jugador Actual</p>
          <p 
            class="font-display mt-1 text-[11px] font-bold uppercase tracking-widest"
            :class="playerColor === 'white' ? 'text-neon-cyan' : 'text-neon-pink'"
          >
            {{ playerColorLabel }}
          </p>
        </div>

        <div ref="logRef" class="log-scroll flex-1 bg-retro-black/30">
          <p v-if="moveRows.length === 0" class="font-pixel p-4 text-center text-[11px] text-white/20">Esperando movimientos...</p>
          <table v-else class="w-full border-collapse font-pixel text-[11px]">
            <thead class="sticky top-0 bg-retro-dark">
              <tr>
                <th class="border-b border-white/5 p-2 text-left text-white/30">#</th>
                <th class="border-b border-white/5 p-2 text-left text-neon-cyan">BLANCAS</th>
                <th class="border-b border-white/5 p-2 text-left text-neon-pink">NEGRAS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in moveRows" :key="entry.round" class="border-b border-white/2">
                <td class="p-2 text-white/20">{{ entry.round }}.</td>
                <td class="p-2 font-bold text-white">{{ entry.white }}</td>
                <td class="p-2 font-bold text-white/60">{{ entry.black || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- PROMOCIÓN MODAL -->
    <div v-if="promotionCell !== null" class="fixed inset-0 z-[100] flex items-center justify-center bg-retro-black/80 backdrop-blur-md">
      <div class="gh-panel w-full max-w-sm border-neon-cyan/40 bg-retro-dark p-8 shadow-[0_0_40px_rgba(0,242,255,0.15)]">
        <h3 class="gh-title-glow font-display mb-6 text-center text-xl font-bold uppercase tracking-widest text-white">Promoción de Peón</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <button 
            v-for="p in ['queen', 'rook', 'bishop', 'knight']" 
            :key="p"
            class="gh-surface gh-surface-hover py-4 font-pixel text-xs font-bold uppercase tracking-widest"
            :class="currentTurn === 'white' ? 'text-neon-cyan border-neon-cyan/20' : 'text-neon-pink border-neon-pink/20'"
            @click="promotePawn(p)"
          >
            {{ p === 'queen' ? '♛ REINA' : p === 'rook' ? '♜ TORRE' : p === 'bishop' ? '♝ ALFIL' : '♞ CABALLO' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.board {
  --cell-size: 56px;
  display: grid;
  grid-template-rows: repeat(8, var(--cell-size));
  width: fit-content;
}

.row {
  display: grid;
  grid-template-columns: repeat(8, var(--cell-size));
}

@media (max-width: 640px) {
  .board {
    --cell-size: 44px;
  }
}

@media (max-width: 440px) {
  .board {
    --cell-size: 38px;
  }
}

@media (max-width: 360px) {
  .board {
    --cell-size: 32px;
  }
}

.cell {
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.cell-white {
  background-color: #334155; /* Slate 700 */
}

.cell-black {
  background-color: #1e293b; /* Slate 800 */
}

.cell-selected {
  background-color: rgba(0, 242, 255, 0.4) !important;
  box-shadow: inset 0 0 15px rgba(0, 242, 255, 0.3);
}

.cell-possible {
  position: relative;
}

.cell-possible::after {
  content: '';
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid var(--color-neon-cyan);
  box-shadow: 0 0 10px var(--color-neon-cyan);
  opacity: 0.8;
}

.cell-possible:hover {
  background-color: rgba(0, 242, 255, 0.1) !important;
}

.cell-capture {
  position: relative;
  z-index: 10;
}

.cell-capture::before {
  content: '';
  position: absolute;
  inset: 0;
  background-color: rgba(255, 45, 85, 0.15);
  box-shadow: inset 0 0 15px rgba(255, 45, 85, 0.3);
  z-index: -1;
}

.cell-capture::after {
  content: '';
  position: absolute;
  inset: 4px;
  border: 1px dashed var(--color-neon-pink);
  opacity: 0.8;
  animation: capture-scan 2s linear infinite;
  pointer-events: none;
}

@keyframes capture-scan {
  0% { transform: scale(0.95); opacity: 0.4; }
  50% { transform: scale(1.05); opacity: 0.8; }
  100% { transform: scale(0.95); opacity: 0.4; }
}

.capture-reticle {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-neon-pink);
  font-size: 24px;
  pointer-events: none;
  animation: reticle-rotate 4s linear infinite;
  opacity: 0.6;
}

@keyframes reticle-rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(90deg); }
}

.cell-capture:hover::after {
  border-style: solid;
  opacity: 1;
  box-shadow: 0 0 15px var(--color-neon-pink);
}

.cell-check {
  background-color: rgba(255, 45, 85, 0.4) !important;
  box-shadow: inset 0 0 15px rgba(255, 45, 85, 0.3);
}

.piece {
  pointer-events: none;
}

.log-scroll {
  max-height: 400px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--color-neon-cyan) transparent;
}

.log-scroll::-webkit-scrollbar {
  width: 4px;
}

.log-scroll::-webkit-scrollbar-thumb {
  background: var(--color-neon-cyan);
}

@media (max-width: 980px) {
  .game-columns {
    grid-template-columns: 1fr;
  }
  
  .board-container {
    width: 100%;
    overflow-x: auto;
    padding-bottom: 20px;
  }
}
</style>
