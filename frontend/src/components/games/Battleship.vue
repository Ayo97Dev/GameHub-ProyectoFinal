<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import gameEngine from '../../lib/gameEngineService'

const emit = defineEmits(['score-change', 'game-completed'])

const GAME_SLUG = 'battleship'
const BOARD_SIZE = 8
const SHIP_DEFS = [
  { id: 'carrier', name: 'Portaaviones', size: 4 },
  { id: 'destroyer', name: 'Destructor', size: 3 },
  { id: 'submarine', name: 'Submarino', size: 3 },
  { id: 'frigate', name: 'Fragata', size: 2 },
  { id: 'patrol', name: 'Patrullero', size: 2 },
]
const TOTAL_SHIP_CELLS = SHIP_DEFS.reduce((sum, ship) => sum + ship.size, 0)
const ROW_LABELS = Array.from({ length: BOARD_SIZE }, (_, i) => String.fromCharCode(65 + i))
const COL_LABELS = Array.from({ length: BOARD_SIZE }, (_, i) => i + 1)

// ─── Placement phase state ───────────────────────────────────────────────────
const phase = ref('placement')
const placedShips = ref({})
const placementBoard = ref([])
const dragShip = ref(null)
const dragOrientation = ref('horizontal')
const hoverPreview = ref([])
const hoverValid = ref(true)
const placementComplete = computed(() => Object.keys(placedShips.value).length === SHIP_DEFS.length)

const touchDragging = ref(false)
const touchShipId = ref(null)

// ─── Game state ──────────────────────────────────────────────────────────────
const playerBoard = ref([])
const enemyBoard = ref([])
const playerFleet = ref({})
const enemyFleet = ref({})

const gameStatus = ref('idle')
const turn = ref('player')
const enemyThinking = ref(false)

const playerShots = ref(0)
const playerHits = ref(0)
const enemyShots = ref(0)
const enemyHits = ref(0)
const enemyShipsSunk = ref(0)
const playerShipsSunk = ref(0)

const battleLog = ref([])
const enemyTargetQueue = ref([])
const enemyTargetSet = ref(new Set())

const isSyncing = ref(false)
const syncError = ref(null)
const sessionId = ref(null)
const hasCompletedSession = ref(false)
const sessionStartedAt = ref(null)
const reportedPlaytimeSeconds = ref(0)

let enemyTimer = null
let autosaveInterval = null

// ─── Placement helpers ────────────────────────────────────────────────────────
function createPlacementBoard() {
  return Array.from({ length: BOARD_SIZE }, () =>
    Array.from({ length: BOARD_SIZE }, () => ({ shipId: null }))
  )
}

function getShipCells(x, y, size, horizontal) {
  const cells = []
  for (let i = 0; i < size; i++) {
    cells.push({ x: horizontal ? x + i : x, y: horizontal ? y : y + i })
  }
  return cells
}

function cellsInBounds(cells) {
  return cells.every(c => c.x >= 0 && c.x < BOARD_SIZE && c.y >= 0 && c.y < BOARD_SIZE)
}

function cellsCollide(cells, excludeShipId = null) {
  for (const c of cells) {
    const cell = placementBoard.value[c.y][c.x]
    if (cell.shipId && cell.shipId !== excludeShipId) return true
  }
  return false
}

function removePlacedShip(shipId) {
  const ship = placedShips.value[shipId]
  if (!ship) return
  for (const c of ship.cells) {
    placementBoard.value[c.y][c.x].shipId = null
  }
  delete placedShips.value[shipId]
}

function placeShip(shipId, cells) {
  removePlacedShip(shipId)
  for (const c of cells) {
    placementBoard.value[c.y][c.x].shipId = shipId
  }
  placedShips.value[shipId] = { cells, horizontal: dragOrientation.value === 'horizontal' }
}

function computeHoverPreview(x, y) {
  if (!dragShip.value) return { cells: [], valid: false }

  const { size, id, offsetCell } = dragShip.value
  const horizontal = dragOrientation.value === 'horizontal'
  const startX = horizontal ? x - offsetCell : x
  const startY = horizontal ? y : y - offsetCell

  const cells = getShipCells(startX, startY, size, horizontal)
  const valid = cellsInBounds(cells) && !cellsCollide(cells, id)

  hoverPreview.value = cells
  hoverValid.value = valid
  return { cells, valid }
}

function handleCellDragOver(e, x, y) {
  e.preventDefault()
  computeHoverPreview(x, y)
}

function handleCellDrop(e, x, y) {
  e.preventDefault()
  if (!dragShip.value) return

  const preview = computeHoverPreview(x, y)
  if (preview.valid) {
    placeShip(dragShip.value.id, preview.cells)
  }

  hoverPreview.value = []
  dragShip.value = null
}

function handleBoardDragEnd() {
  hoverPreview.value = []
  dragShip.value = null
}

// 🔥 FIX PRINCIPAL: eliminar ghost image del navegador
function startDragFromBoard(e, shipId) {
  const ship = SHIP_DEFS.find(s => s.id === shipId)
  if (!ship) return

  const placed = placedShips.value[shipId]
  dragOrientation.value = placed?.horizontal !== false ? 'horizontal' : 'vertical'

  const target = e.currentTarget
  const rect = target.getBoundingClientRect()
  const cellSize = rect.width / (dragOrientation.value === 'horizontal' ? ship.size : 1)

  const offsetCell = dragOrientation.value === 'horizontal'
    ? Math.floor((e.clientX - rect.left) / cellSize)
    : Math.floor((e.clientY - rect.top) / cellSize)

  dragShip.value = {
    id: shipId,
    size: ship.size,
    name: ship.name,
    offsetCell: Math.min(offsetCell, ship.size - 1)
  }

  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', shipId)

    const img = new Image()
    img.src = 'data:image/png;base64,iVBORw0KGgo='
    e.dataTransfer.setDragImage(img, 0, 0)
  }
}

// 🔥 FIX PRINCIPAL también aquí
function startDragFromDock(e, shipDef) {
  const placed = placedShips.value[shipDef.id]
  dragOrientation.value = placed?.horizontal === false ? 'vertical' : 'horizontal'

  if (placed) {
    removePlacedShip(shipDef.id)
  }

  dragShip.value = {
    id: shipDef.id,
    size: shipDef.size,
    name: shipDef.name,
    offsetCell: 0
  }

  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', shipDef.id)

    const img = new Image()
    img.src = 'data:image/png;base64,iVBORw0KGgo='
    e.dataTransfer.setDragImage(img, 0, 0)
  }
}

function toggleOrientation(shipId) {
  const placed = placedShips.value[shipId]
  if (!placed) return
  const ship = SHIP_DEFS.find(s => s.id === shipId)
  const anchor = placed.cells[0]
  const newHorizontal = !placed.horizontal
  const newCells = getShipCells(anchor.x, anchor.y, ship.size, newHorizontal)
  if (!cellsInBounds(newCells) || cellsCollide(newCells, shipId)) return
  dragOrientation.value = newHorizontal ? 'horizontal' : 'vertical'
  placeShip(shipId, newCells)
  placedShips.value[shipId].horizontal = newHorizontal
}

function randomizePlacement() {
  placementBoard.value = createPlacementBoard()
  placedShips.value = {}
  const board = createPlacementBoard()

  for (const ship of SHIP_DEFS) {
    let placed = false
    for (let attempt = 0; attempt < 400 && !placed; attempt++) {
      const horizontal = Math.random() < 0.5
      const startX = randomInt(0, horizontal ? BOARD_SIZE - ship.size : BOARD_SIZE - 1)
      const startY = randomInt(0, horizontal ? BOARD_SIZE - 1 : BOARD_SIZE - ship.size)
      const cells = getShipCells(startX, startY, ship.size, horizontal)
      const collision = cells.some(c => board[c.y][c.x].shipId)
      if (collision) continue
      for (const c of cells) board[c.y][c.x].shipId = ship.id
      placedShips.value[ship.id] = { cells, horizontal }
      placed = true
    }
  }
  placementBoard.value = board
}

function resetPlacement() {
  placementBoard.value = createPlacementBoard()
  placedShips.value = {}
}

function confirmPlacement() {
  if (!placementComplete.value) return
  // Build player board from placement
  const board = createBoard()
  const fleet = {}

  for (const ship of SHIP_DEFS) {
    const placed = placedShips.value[ship.id]
    for (const c of placed.cells) {
      board[c.y][c.x].hasShip = true
      board[c.y][c.x].shipId = ship.id
    }
    fleet[ship.id] = {
      ...ship,
      hits: 0,
      sunk: false,
      cells: placed.cells,
    }
  }

  const foe = buildRandomFleet()
  playerBoard.value = board
  playerFleet.value = fleet
  enemyBoard.value = foe.board
  enemyFleet.value = foe.fleet
  gameStatus.value = 'playing'
  turn.value = 'player'
  enemyThinking.value = false
  playerShots.value = 0
  playerHits.value = 0
  enemyShots.value = 0
  enemyHits.value = 0
  enemyShipsSunk.value = 0
  playerShipsSunk.value = 0
  battleLog.value = [makeLogEntry('Mision iniciada. Tu radar esta listo para disparar.', 'good')]
  enemyTargetQueue.value = []
  enemyTargetSet.value = new Set()
  sessionStartedAt.value = Date.now()
  reportedPlaytimeSeconds.value = 0
  hasCompletedSession.value = false

  phase.value = 'playing'
}

// Placement cell display helpers
function placementCellClass(x, y) {
  const cell = placementBoard.value[y]?.[x]
  const inPreview = hoverPreview.value.some(c => c.x === x && c.y === y)

  if (inPreview) return hoverValid.value ? 'cell-preview-valid' : 'cell-preview-invalid'
  if (cell?.shipId) return 'cell-ship'
  return 'cell-water'
}

function getPlacedShipStyle(shipId) {
  const placed = placedShips.value[shipId]
  if (!placed) return {}
  const { cells, horizontal } = placed
  const anchor = cells[0]
  return {
    gridColumn: `${anchor.x + 2} / span ${horizontal ? cells.length : 1}`,
    gridRow: `${anchor.y + 2} / span ${horizontal ? 1 : cells.length}`,
  }
}

function unplacedShips() {
  return SHIP_DEFS.filter(s => !placedShips.value[s.id])
}

// Touch drag support
function handleTouchStart(e, shipDef) {
  touchDragging.value = true
  touchShipId.value = shipDef.id
  dragOrientation.value = 'horizontal'
  dragShip.value = { id: shipDef.id, size: shipDef.size, name: shipDef.name, offsetCell: 0 }
}

function handleCellTouchOver(e, x, y) {
  if (!touchDragging.value) return
  e.preventDefault()
  computeHoverPreview(x, y)
}

function handleCellTouchDrop(e, x, y) {
  if (!touchDragging.value) return
  const preview = computeHoverPreview(x, y)
  if (preview && preview.valid) {
    placeShip(dragShip.value.id, preview.cells)
  }
  hoverPreview.value = []
  dragShip.value = null
  touchDragging.value = false
  touchShipId.value = null
}

// ─── Core helpers ─────────────────────────────────────────────────────────────
function createCell() {
  return { hasShip: false, shipId: null, state: 'unknown' }
}

function createBoard() {
  return Array.from({ length: BOARD_SIZE }, () =>
    Array.from({ length: BOARD_SIZE }, () => createCell())
  )
}

function randomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function inBounds(x, y) {
  return x >= 0 && x < BOARD_SIZE && y >= 0 && y < BOARD_SIZE
}

function isTargeted(cell) {
  return cell.state === 'hit' || cell.state === 'miss'
}

function queueKey(x, y) { return `${x}:${y}` }

function isQueueKeyValid(value) {
  if (typeof value !== 'string') return false
  const [xRaw, yRaw] = value.split(':')
  const x = Number(xRaw); const y = Number(yRaw)
  return Number.isInteger(x) && Number.isInteger(y) && inBounds(x, y)
}

function formatCoordinate(x, y) { return `${ROW_LABELS[y]}${x + 1}` }

function makeLogId() { return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}` }

function makeLogEntry(text, tone = 'neutral') { return { id: makeLogId(), text, tone } }

function clampInt(value, min, max) {
  const n = Number(value)
  if (!Number.isFinite(n)) return min
  return Math.max(min, Math.min(Math.round(n), max))
}

function buildRandomFleet() {
  const board = createBoard()
  const fleet = {}

  for (const ship of SHIP_DEFS) {
    let placed = false
    for (let attempt = 0; attempt < 400 && !placed; attempt++) {
      const horizontal = Math.random() < 0.5
      const startX = randomInt(0, horizontal ? BOARD_SIZE - ship.size : BOARD_SIZE - 1)
      const startY = randomInt(0, horizontal ? BOARD_SIZE - 1 : BOARD_SIZE - ship.size)
      const coords = []
      let collision = false
      for (let i = 0; i < ship.size; i++) {
        const x = startX + (horizontal ? i : 0)
        const y = startY + (horizontal ? 0 : i)
        if (board[y][x].hasShip) { collision = true; break }
        coords.push({ x, y })
      }
      if (collision) continue
      for (const coord of coords) {
        board[coord.y][coord.x].hasShip = true
        board[coord.y][coord.x].shipId = ship.id
      }
      fleet[ship.id] = { ...ship, hits: 0, sunk: false, cells: coords }
      placed = true
    }
    if (!placed) return buildRandomFleet()
  }
  return { board, fleet }
}

function applyShot(board, fleet, x, y) {
  const cell = board[y][x]
  if (isTargeted(cell)) return { repeated: true, hit: false, sunk: false, shipName: null, allSunk: false }
  if (!cell.hasShip) { cell.state = 'miss'; return { repeated: false, hit: false, sunk: false, shipName: null, allSunk: false } }
  cell.state = 'hit'
  const ship = fleet[cell.shipId]
  ship.hits += 1
  const sunk = ship.hits >= ship.size
  if (sunk) ship.sunk = true
  const allSunk = Object.values(fleet).every(item => item.sunk)
  return { repeated: false, hit: true, sunk, shipName: ship.name, allSunk }
}

function clearEnemyTimer() {
  if (enemyTimer) { window.clearTimeout(enemyTimer); enemyTimer = null }
}

function pushLog(text, tone = 'neutral') {
  battleLog.value.unshift(makeLogEntry(text, tone))
  battleLog.value = battleLog.value.slice(0, 10)
}

// ─── Snapshot / persistence ───────────────────────────────────────────────────
function buildInitialSnapshot() {
  const own = buildRandomFleet()
  const foe = buildRandomFleet()
  return {
    playerBoard: own.board, enemyBoard: foe.board,
    playerFleet: own.fleet, enemyFleet: foe.fleet,
    gameStatus: 'playing', turn: 'player', enemyThinking: false,
    playerShots: 0, playerHits: 0, enemyShots: 0, enemyHits: 0,
    enemyShipsSunk: 0, playerShipsSunk: 0,
    battleLog: [makeLogEntry('Mision iniciada. Tu radar esta listo para disparar.', 'good')],
    enemyTargetQueue: [], enemyTargetSet: [],
  }
}

function normalizeCell(raw) {
  const state = raw?.state === 'hit' || raw?.state === 'miss' ? raw.state : 'unknown'
  return { hasShip: Boolean(raw?.hasShip), shipId: typeof raw?.shipId === 'string' ? raw.shipId : null, state }
}

function normalizeBoard(rawBoard, fallbackBoard) {
  if (!Array.isArray(rawBoard) || rawBoard.length !== BOARD_SIZE) return fallbackBoard
  return rawBoard.map((rawRow, y) => {
    if (!Array.isArray(rawRow) || rawRow.length !== BOARD_SIZE) return fallbackBoard[y]
    return rawRow.map(rawCell => normalizeCell(rawCell))
  })
}

function normalizeCells(rawCells, fallbackCells) {
  if (!Array.isArray(rawCells)) return fallbackCells
  const cells = rawCells.map(raw => ({ x: Number(raw?.x), y: Number(raw?.y) }))
    .filter(cell => Number.isInteger(cell.x) && Number.isInteger(cell.y) && inBounds(cell.x, cell.y))
  return cells.length > 0 ? cells : fallbackCells
}

function normalizeFleet(rawFleet, fallbackFleet) {
  const normalized = {}
  for (const shipDef of SHIP_DEFS) {
    const fallback = fallbackFleet[shipDef.id]
    const source = rawFleet?.[shipDef.id] ?? {}
    const hits = clampInt(source?.hits, 0, shipDef.size)
    normalized[shipDef.id] = {
      id: shipDef.id, name: shipDef.name, size: shipDef.size, hits,
      sunk: Boolean(source?.sunk) || hits >= shipDef.size,
      cells: normalizeCells(source?.cells, fallback.cells),
    }
  }
  return normalized
}

function normalizeQueue(rawQueue) {
  if (!Array.isArray(rawQueue)) return []
  return rawQueue.map(raw => ({ x: Number(raw?.x), y: Number(raw?.y) }))
    .filter(cell => Number.isInteger(cell.x) && Number.isInteger(cell.y) && inBounds(cell.x, cell.y))
}

function normalizeLog(rawLog, fallbackLog) {
  if (!Array.isArray(rawLog)) return fallbackLog
  const normalized = rawLog.slice(0, 10)
    .map(raw => ({ id: typeof raw?.id === 'string' ? raw.id : makeLogId(), text: typeof raw?.text === 'string' ? raw.text : '', tone: raw?.tone === 'good' || raw?.tone === 'bad' ? raw.tone : 'neutral' }))
    .filter(entry => entry.text.length > 0)
  return normalized.length > 0 ? normalized : fallbackLog
}

function applySnapshot(rawSnapshot) {
  clearEnemyTimer()
  const fallback = buildInitialSnapshot()
  const source = (rawSnapshot && typeof rawSnapshot === 'object') ? rawSnapshot : fallback
  const snapshot = {
    playerBoard: normalizeBoard(source.playerBoard ?? source.player_board, fallback.playerBoard),
    enemyBoard: normalizeBoard(source.enemyBoard ?? source.enemy_board, fallback.enemyBoard),
    playerFleet: normalizeFleet(source.playerFleet ?? source.player_fleet, fallback.playerFleet),
    enemyFleet: normalizeFleet(source.enemyFleet ?? source.enemy_fleet, fallback.enemyFleet),
    gameStatus: ['won','lost','playing'].includes(source.gameStatus) ? source.gameStatus
      : (['won','lost','playing'].includes(source.game_status) ? source.game_status : fallback.gameStatus),
    turn: ['player','enemy','none'].includes(source.turn) ? source.turn : fallback.turn,
    playerShots: clampInt(source.playerShots ?? source.player_shots, 0, 999999),
    playerHits: clampInt(source.playerHits ?? source.player_hits, 0, TOTAL_SHIP_CELLS),
    enemyShots: clampInt(source.enemyShots ?? source.enemy_shots, 0, 999999),
    enemyHits: clampInt(source.enemyHits ?? source.enemy_hits, 0, TOTAL_SHIP_CELLS),
    enemyShipsSunk: clampInt(source.enemyShipsSunk ?? source.enemy_ships_sunk, 0, SHIP_DEFS.length),
    playerShipsSunk: clampInt(source.playerShipsSunk ?? source.player_ships_sunk, 0, SHIP_DEFS.length),
    battleLog: normalizeLog(source.battleLog ?? source.battle_log, fallback.battleLog),
    enemyTargetQueue: normalizeQueue(source.enemyTargetQueue ?? source.enemy_target_queue),
    enemyTargetSet: (() => {
      const rawSet = source.enemyTargetSet ?? source.enemy_target_set
      if (Array.isArray(rawSet)) return [...new Set(rawSet.filter(isQueueKeyValid))]
      return []
    })(),
  }

  playerBoard.value = snapshot.playerBoard
  enemyBoard.value = snapshot.enemyBoard
  playerFleet.value = snapshot.playerFleet
  enemyFleet.value = snapshot.enemyFleet
  gameStatus.value = snapshot.gameStatus
  turn.value = snapshot.turn
  enemyThinking.value = false
  playerShots.value = snapshot.playerShots
  playerHits.value = snapshot.playerHits
  enemyShots.value = snapshot.enemyShots
  enemyHits.value = snapshot.enemyHits
  enemyShipsSunk.value = snapshot.enemyShipsSunk
  playerShipsSunk.value = snapshot.playerShipsSunk
  battleLog.value = snapshot.battleLog
  enemyTargetQueue.value = snapshot.enemyTargetQueue
  enemyTargetSet.value = new Set(snapshot.enemyTargetSet)

  if (gameStatus.value === 'playing' && turn.value === 'enemy') scheduleEnemyTurn()

  // Skip placement phase if we're loading a saved game in progress
  phase.value = 'playing'
}

function serializeState() {
  return JSON.parse(JSON.stringify({
    playerBoard: playerBoard.value, enemyBoard: enemyBoard.value,
    playerFleet: playerFleet.value, enemyFleet: enemyFleet.value,
    gameStatus: gameStatus.value, turn: turn.value,
    playerShots: playerShots.value, playerHits: playerHits.value,
    enemyShots: enemyShots.value, enemyHits: enemyHits.value,
    enemyShipsSunk: enemyShipsSunk.value, playerShipsSunk: playerShipsSunk.value,
    battleLog: battleLog.value,
    enemyTargetQueue: enemyTargetQueue.value,
    enemyTargetSet: Array.from(enemyTargetSet.value),
  }))
}

function resetEnemyQueue() {
  enemyTargetQueue.value = []
  enemyTargetSet.value = new Set()
}

function getSessionDurationSeconds() {
  if (!sessionStartedAt.value) return 0
  return Math.max(Math.floor((Date.now() - sessionStartedAt.value) / 1000), 0)
}

async function saveProgress({ silent = true } = {}) {
  if (!sessionId.value) return false
  try {
    const elapsedSeconds = getSessionDurationSeconds()
    const pendingPlaytime = Math.max(elapsedSeconds - reportedPlaytimeSeconds.value, 0)
    await gameEngine.save(GAME_SLUG, { session_id: sessionId.value, game_state: serializeState(), score: score.value, playtime: pendingPlaytime })
    reportedPlaytimeSeconds.value += pendingPlaytime
    syncError.value = null
    return true
  } catch {
    syncError.value = 'No se pudo guardar en backend; progreso local activo.'
    return false
  }
}

async function completeCurrentSession() {
  if (!sessionId.value || hasCompletedSession.value) return false
  try {
    await gameEngine.complete(GAME_SLUG, { session_id: sessionId.value, final_score: score.value, duration: getSessionDurationSeconds(), game_state: serializeState() })
    hasCompletedSession.value = true
    emit('game-completed')
    return true
  } catch {
    syncError.value = 'No se pudo reportar el resultado final al backend.'
    return false
  }
}

// ─── Enemy AI ─────────────────────────────────────────────────────────────────
function addEnemyTargetsAround(x, y) {
  const candidates = [{ x: x + 1, y }, { x: x - 1, y }, { x, y: y + 1 }, { x, y: y - 1 }]
  for (const candidate of candidates) {
    if (!inBounds(candidate.x, candidate.y)) continue
    const targetCell = playerBoard.value[candidate.y][candidate.x]
    if (isTargeted(targetCell)) continue
    const key = queueKey(candidate.x, candidate.y)
    if (enemyTargetSet.value.has(key)) continue
    enemyTargetSet.value.add(key)
    enemyTargetQueue.value.push(candidate)
  }
}

function pickEnemyTarget() {
  while (enemyTargetQueue.value.length > 0) {
    const candidate = enemyTargetQueue.value.shift()
    enemyTargetSet.value.delete(queueKey(candidate.x, candidate.y))
    if (!inBounds(candidate.x, candidate.y)) continue
    const cell = playerBoard.value[candidate.y][candidate.x]
    if (!isTargeted(cell)) return candidate
  }
  const fallback = []
  for (let y = 0; y < BOARD_SIZE; y++) for (let x = 0; x < BOARD_SIZE; x++) {
    if (!isTargeted(playerBoard.value[y][x])) fallback.push({ x, y })
  }
  if (fallback.length === 0) return null
  return fallback[randomInt(0, fallback.length - 1)]
}

function finalizeBattle() {
  void saveProgress({ silent: true })
  void completeCurrentSession()
}

function endBattle(status) {
  gameStatus.value = status
  turn.value = 'none'
  enemyThinking.value = false
  clearEnemyTimer()
  if (status === 'won') pushLog('Victoria total. Has hundido toda la flota enemiga.', 'good')
  else pushLog('Derrota. Tu flota ha quedado fuera de combate.', 'bad')
  finalizeBattle()
}

function scheduleEnemyTurn() {
  if (gameStatus.value !== 'playing') return
  enemyThinking.value = true
  clearEnemyTimer()
  enemyTimer = window.setTimeout(() => {
    enemyThinking.value = false
    if (gameStatus.value !== 'playing') return
    const target = pickEnemyTarget()
    if (!target) return
    const result = applyShot(playerBoard.value, playerFleet.value, target.x, target.y)
    enemyShots.value += 1
    if (result.hit) {
      enemyHits.value += 1
      pushLog(`Impacto enemigo en ${formatCoordinate(target.x, target.y)}.`, 'bad')
      if (result.sunk) { playerShipsSunk.value += 1; resetEnemyQueue(); pushLog(`Han hundido tu ${result.shipName}.`, 'bad') }
      else addEnemyTargetsAround(target.x, target.y)
    } else pushLog(`El enemigo fallo en ${formatCoordinate(target.x, target.y)}.`, 'neutral')
    if (result.allSunk) { endBattle('lost'); return }
    turn.value = 'player'
  }, 700)
}

function fireAtEnemy(x, y) {
  if (gameStatus.value !== 'playing') return
  if (turn.value !== 'player' || enemyThinking.value) return
  const cell = enemyBoard.value[y][x]
  if (isTargeted(cell)) return
  const result = applyShot(enemyBoard.value, enemyFleet.value, x, y)
  playerShots.value += 1
  if (result.hit) {
    playerHits.value += 1
    pushLog(`Impacto confirmado en ${formatCoordinate(x, y)}.`, 'good')
    if (result.sunk) { enemyShipsSunk.value += 1; pushLog(`Has hundido el ${result.shipName} enemigo.`, 'good') }
  } else pushLog(`Agua en ${formatCoordinate(x, y)}.`, 'neutral')
  if (result.allSunk) { endBattle('won'); return }
  turn.value = 'enemy'
  scheduleEnemyTurn()
}

async function handleNewGameClick() {
  clearEnemyTimer()
  phase.value = 'placement'
  resetPlacement()
  isSyncing.value = true
  try {
    const res = await gameEngine.play(GAME_SLUG, false)
    sessionId.value = res?.session_id ?? null
  } catch {
    sessionId.value = null
  } finally {
    isSyncing.value = false
  }
  hasCompletedSession.value = false
  syncError.value = null
}

function startAutosave() {
  if (autosaveInterval) window.clearInterval(autosaveInterval)
  autosaveInterval = window.setInterval(() => {
    if (gameStatus.value === 'playing') void saveProgress({ silent: true })
  }, 20000)
}

function stopAutosave() {
  if (!autosaveInterval) return
  window.clearInterval(autosaveInterval)
  autosaveInterval = null
}

function handleVisibilityChange() {
  if (document.visibilityState === 'hidden') void saveProgress({ silent: true })
}

function handleBeforeUnload() { void saveProgress({ silent: true }) }

// ─── Computed ─────────────────────────────────────────────────────────────────
const canShoot = computed(() => gameStatus.value === 'playing' && turn.value === 'player' && !enemyThinking.value)

const playerShipStatus = computed(() =>
  SHIP_DEFS.map(ship => {
    const runtime = playerFleet.value[ship.id]
    return { id: ship.id, name: ship.name, size: ship.size, hits: runtime?.hits ?? 0, sunk: runtime?.sunk ?? false }
  })
)

const enemyShipStatus = computed(() =>
  SHIP_DEFS.map(ship => {
    const runtime = enemyFleet.value[ship.id]
    return { id: ship.id, name: ship.name, size: ship.size, hits: runtime?.hits ?? 0, sunk: runtime?.sunk ?? false }
  })
)

const playerAccuracy = computed(() => {
  if (playerShots.value === 0) return 0
  return Math.round((playerHits.value / playerShots.value) * 100)
})

const score = computed(() => {
  const baseScore = 1200
  const precisionBonus = playerHits.value * 95
  const sunkBonus = enemyShipsSunk.value * 220
  const extraShotsPenalty = Math.max(playerShots.value - TOTAL_SHIP_CELLS, 0) * 28
  const damagePenalty = enemyHits.value * 45
  const winBonus = gameStatus.value === 'won' ? 1300 : 0
  return Math.max(0, Math.round(baseScore + precisionBonus + sunkBonus + winBonus - extraShotsPenalty - damagePenalty))
})

const statusText = computed(() => {
  if (gameStatus.value === 'won') return 'Mision completada: victoria naval.'
  if (gameStatus.value === 'lost') return 'Flota perdida. Reagrupa y vuelve a intentarlo.'
  if (enemyThinking.value) return 'Turno enemigo: analizando objetivo...'
  if (turn.value === 'player') return 'Tu turno: selecciona una celda en el radar enemigo.'
  return 'Turno enemigo.'
})

// ─── Cell display helpers ─────────────────────────────────────────────────────
function playerCellClass(cell) {
  if (cell.state === 'hit') return 'cell-hit'
  if (cell.state === 'miss') return 'cell-miss'
  if (cell.hasShip) return 'cell-ship'
  return 'cell-water'
}

function enemyCellClass(cell) {
  if (cell.state === 'hit') return 'cell-hit'
  if (cell.state === 'miss') return 'cell-miss'
  return 'cell-fog'
}

function playerCellMarker(cell) {
  if (cell.state === 'hit') return 'X'
  if (cell.state === 'miss') return '•'
  if (cell.hasShip) return '■'
  return ''
}

function enemyCellMarker(cell) {
  if (cell.state === 'hit') return 'X'
  if (cell.state === 'miss') return '•'
  return ''
}

function logToneClass(tone) {
  if (tone === 'good') return 'border-emerald-300/60 bg-emerald-500/10 text-emerald-100'
  if (tone === 'bad') return 'border-rose-300/60 bg-rose-500/10 text-rose-100'
  return 'border-sky-300/40 bg-sky-500/10 text-sky-100'
}

// ─── Placement grid helper ────────────────────────────────────────────────────
function placementCellHasShip(x, y) {
  return placementBoard.value[y]?.[x]?.shipId != null
}

function getShipIdAt(x, y) {
  return placementBoard.value[y]?.[x]?.shipId
}

watch(score, (value) => emit('score-change', value), { immediate: true })

async function initializeGame(loadSave = true) {
  isSyncing.value = true
  syncError.value = null
  hasCompletedSession.value = false
  clearEnemyTimer()

  try {
    const res = await gameEngine.play(GAME_SLUG, loadSave)
    sessionId.value = res?.session_id ?? null

    if (res?.game_state && typeof res.game_state === 'object') {
      applySnapshot(res.game_state)
    } else {
      // No saved game – go to placement
      phase.value = 'placement'
      resetPlacement()
    }
  } catch {
    sessionId.value = null
    phase.value = 'placement'
    resetPlacement()
    syncError.value = 'Modo local activo: no se pudo sincronizar con backend.'
  } finally {
    isSyncing.value = false
  }
}

onMounted(() => {
  placementBoard.value = createPlacementBoard()
  startAutosave()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('beforeunload', handleBeforeUnload)
  void initializeGame(true)
})

onUnmounted(() => {
  clearEnemyTimer()
  stopAutosave()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('beforeunload', handleBeforeUnload)
  void saveProgress({ silent: true })
  emit('score-change', 0)
})
</script>

<template>
  <section class="relative overflow-hidden rounded-2xl border border-cyan-200/70 bg-gradient-to-br from-slate-900 via-cyan-950 to-slate-900 p-4 text-slate-100 shadow-[0_18px_60px_rgba(2,132,199,0.25)] sm:p-5">
    <div class="pointer-events-none absolute -left-20 -top-24 h-64 w-64 rounded-full bg-cyan-400/30 blur-3xl" />
    <div class="pointer-events-none absolute -right-24 bottom-0 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl" />

    <!-- Header -->
    <header class="relative z-10 flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-cyan-300">Operacion Atlantico</p>
        <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">Hundir la Flota</h2>
        <p class="mt-1 max-w-2xl text-sm text-cyan-100/90">
          <template v-if="phase === 'placement'">Coloca tu flota arrastrando los barcos al tablero para de iniciar la batalla.</template>
          <template v-else>Descubre la posicion enemiga y hunde sus cinco barcos antes de que derriben tu escuadron.</template>
        </p>
      </div>

      <button
        type="button"
        class="inline-flex items-center rounded-lg border border-cyan-300/70 bg-cyan-400/20 px-4 py-2 text-sm font-bold text-cyan-50 transition hover:scale-[1.02] hover:bg-cyan-400/30 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSyncing"
        @click="handleNewGameClick"
      >
        {{ isSyncing ? 'Sincronizando...' : 'Nueva partida' }}
      </button>
    </header>

    <p v-if="isSyncing" class="relative z-10 mt-2 text-xs font-semibold text-cyan-200/90">Sincronizando sesión con backend...</p>
    <p v-else-if="syncError" class="relative z-10 mt-2 text-xs font-semibold text-amber-200">{{ syncError }}</p>

    <!-- FASE DE PREPARACION  -->
    <template v-if="phase === 'placement'">
      <div class="relative z-10 mt-4 grid gap-4 lg:grid-cols-[1fr_280px]">

        <!-- Placement board -->
        <article class="rounded-2xl border border-cyan-300/40 bg-slate-950/45 p-3 sm:p-4">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-cyan-200">Posiciona tu flota</h3>
            <div class="flex gap-2">
              <button
                type="button"
                class="rounded-lg border border-cyan-300/50 bg-cyan-500/15 px-3 py-1 text-xs font-bold text-cyan-100 transition hover:bg-cyan-500/25"
                @click="randomizePlacement"
              >⚓ Aleatorio</button>
              <button
                type="button"
                class="rounded-lg border border-rose-300/50 bg-rose-500/10 px-3 py-1 text-xs font-bold text-rose-100 transition hover:bg-rose-500/20"
                @click="resetPlacement"
              >Borrar</button>
            </div>
          </div>

          <p class="mt-2 text-[11px] text-cyan-200/70">
            Arrastra los barcos del panel derecho al tablero • Haz clic en un barco colocado para girarlo
          </p>

          <!-- Grid -->
          <div
            class="placement-grid mt-3 select-none"
            @dragover.prevent
            @drop.prevent
            @dragend="handleBoardDragEnd"
          >
            <!-- Column labels -->
            <span class="axis-cell" />
            <span v-for="col in COL_LABELS" :key="`pc-col-${col}`" class="axis-cell">{{ col }}</span>

            <!-- Rows -->
            <template v-for="(row, y) in placementBoard" :key="`pc-row-${y}`">
              <span class="axis-cell">{{ ROW_LABELS[y] }}</span>

              <div
                v-for="(cell, x) in row"
                :key="`pc-cell-${x}-${y}`"
                class="placement-cell"
                :class="placementCellClass(x, y)"
                @dragover.prevent="handleCellDragOver($event, x, y)"
                @drop.prevent="handleCellDrop($event, x, y)"
                @click="cell.shipId ? toggleOrientation(cell.shipId) : null"
              />
            </template>
          </div>
        </article>

        <!-- Ship dock + confirm -->
        <aside class="space-y-3">
          <div class="rounded-2xl border border-cyan-300/35 bg-slate-950/55 p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Arsenal disponible</p>
            <p class="mt-1 text-xs text-cyan-200/55">Arrastra al tablero para colocar</p>

            <div class="mt-3 space-y-2">
              <div
                v-for="ship in SHIP_DEFS"
                :key="`dock-${ship.id}`"
                class="dock-ship"
                :class="placedShips[ship.id] ? 'dock-ship--placed' : 'dock-ship--available'"
                draggable="true"
                @dragstart="startDragFromDock($event, ship)"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-bold">{{ ship.name }}</span>
                  <span class="text-[10px] font-semibold opacity-70">{{ ship.size }} celdas</span>
                </div>
                <div class="mt-1.5 flex gap-1">
                  <span
                    v-for="i in ship.size"
                    :key="i"
                    class="ship-pip"
                    :class="placedShips[ship.id] ? 'ship-pip--placed' : 'ship-pip--available'"
                  />
                </div>
                <span v-if="placedShips[ship.id]" class="mt-1 block text-[10px] font-semibold text-emerald-300">✓ Colocado — arrastra para recolocar o clic en tablero para girar</span>
              </div>
            </div>
          </div>

          <!-- Progress indicator -->
          <div class="rounded-xl border border-cyan-300/30 bg-slate-950/45 p-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-cyan-200/80">Progreso</span>
              <span class="text-xs font-bold text-cyan-100">{{ Object.keys(placedShips).length }} / {{ SHIP_DEFS.length }}</span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-slate-700/60">
              <div
                class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-emerald-400 transition-all duration-300"
                :style="{ width: `${(Object.keys(placedShips).length / SHIP_DEFS.length) * 100}%` }"
              />
            </div>
          </div>

          <!-- Confirm button -->
          <button
            type="button"
            class="w-full rounded-xl border py-3 text-sm font-black uppercase tracking-[0.12em] transition"
            :class="placementComplete
              ? 'border-emerald-300/70 bg-emerald-500/20 text-emerald-100 hover:bg-emerald-500/30 hover:scale-[1.01]'
              : 'cursor-not-allowed border-slate-600/50 bg-slate-800/50 text-slate-500'"
            :disabled="!placementComplete"
            @click="confirmPlacement"
          >
            {{ placementComplete ? '⚓ ¡Iniciar batalla!' : 'Coloca todos los barcos' }}
          </button>
        </aside>
      </div>
    </template>

    <!-- ════════════════════════════════════════════════════════════
         BATTLE PHASE (unchanged from original)
    ════════════════════════════════════════════════════════════ -->
    <template v-else>
      <div class="relative z-10 mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-cyan-300/40 bg-slate-950/45 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Puntuacion</p>
          <p class="mt-1 text-2xl font-black tabular-nums text-cyan-100">{{ score.toLocaleString() }}</p>
        </div>
        <div class="rounded-xl border border-cyan-300/40 bg-slate-950/45 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Precision</p>
          <p class="mt-1 text-2xl font-black tabular-nums text-cyan-100">{{ playerAccuracy }}%</p>
        </div>
        <div class="rounded-xl border border-cyan-300/40 bg-slate-950/45 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Barcos enemigos</p>
          <p class="mt-1 text-2xl font-black tabular-nums text-cyan-100">{{ SHIP_DEFS.length - enemyShipsSunk }} / {{ SHIP_DEFS.length }}</p>
        </div>
        <div class="rounded-xl border border-cyan-300/40 bg-slate-950/45 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Estado</p>
          <p class="mt-1 text-sm font-bold text-cyan-50">{{ statusText }}</p>
        </div>
      </div>

      <div class="relative z-10 mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_300px]">
        <!-- Player board -->
        <article class="rounded-2xl border border-cyan-300/40 bg-slate-950/45 p-3 sm:p-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-cyan-200">Tu oceano</h3>
            <span class="text-xs font-semibold text-cyan-100/80">Hundidos: {{ playerShipsSunk }} / {{ SHIP_DEFS.length }}</span>
          </div>
          <div class="battle-grid mt-3">
            <span class="axis-cell" />
            <span v-for="col in COL_LABELS" :key="`p-col-${col}`" class="axis-cell">{{ col }}</span>
            <template v-for="(row, y) in playerBoard" :key="`p-row-${y}`">
              <span class="axis-cell">{{ ROW_LABELS[y] }}</span>
              <button
                v-for="(cell, x) in row"
                :key="`p-cell-${x}-${y}`"
                type="button"
                class="battle-cell"
                :class="playerCellClass(cell)"
                disabled
              >{{ playerCellMarker(cell) }}</button>
            </template>
          </div>
        </article>

        <!-- Enemy board -->
        <article class="rounded-2xl border border-emerald-300/35 bg-slate-950/45 p-3 sm:p-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-200">Radar enemigo</h3>
            <span class="text-xs font-semibold text-emerald-100/80">Disparos: {{ playerShots }}</span>
          </div>
          <div class="battle-grid mt-3">
            <span class="axis-cell" />
            <span v-for="col in COL_LABELS" :key="`e-col-${col}`" class="axis-cell">{{ col }}</span>
            <template v-for="(row, y) in enemyBoard" :key="`e-row-${y}`">
              <span class="axis-cell">{{ ROW_LABELS[y] }}</span>
              <button
                v-for="(cell, x) in row"
                :key="`e-cell-${x}-${y}`"
                type="button"
                class="battle-cell"
                :class="[enemyCellClass(cell), canShoot && !isTargeted(cell) ? 'cursor-crosshair hover:scale-[1.02]' : 'cursor-not-allowed']"
                :disabled="!canShoot || isTargeted(cell)"
                @click="fireAtEnemy(x, y)"
              >{{ enemyCellMarker(cell) }}</button>
            </template>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-3 rounded-2xl border border-cyan-300/35 bg-slate-950/55 p-3 sm:p-4">
          <div class="rounded-xl border border-cyan-300/35 bg-cyan-500/10 p-3">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200/80">Flota enemiga</p>
            <div class="mt-2 space-y-2">
              <div
                v-for="ship in enemyShipStatus"
                :key="`enemy-ship-${ship.id}`"
                class="flex items-center justify-between rounded-lg border px-2 py-1.5"
                :class="ship.sunk ? 'border-rose-300/60 bg-rose-500/15 text-rose-100' : 'border-cyan-300/40 bg-slate-900/55 text-cyan-100'"
              >
                <span class="text-xs font-semibold">{{ ship.name }}</span>
                <span class="text-xs font-bold">{{ ship.hits }} / {{ ship.size }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-emerald-300/35 bg-emerald-500/10 p-3">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-200/80">Tu flota</p>
            <div class="mt-2 space-y-2">
              <div
                v-for="ship in playerShipStatus"
                :key="`player-ship-${ship.id}`"
                class="flex items-center justify-between rounded-lg border px-2 py-1.5"
                :class="ship.sunk ? 'border-rose-300/60 bg-rose-500/15 text-rose-100' : 'border-emerald-300/40 bg-slate-900/55 text-emerald-100'"
              >
                <span class="text-xs font-semibold">{{ ship.name }}</span>
                <span class="text-xs font-bold">{{ ship.hits }} / {{ ship.size }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-sky-300/35 bg-sky-500/10 p-3">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sky-200/80">Bitacora tactica</p>
            <div class="log-scroll mt-2 space-y-2">
              <p
                v-for="entry in battleLog"
                :key="entry.id"
                class="rounded-lg border px-2 py-1.5 text-xs"
                :class="logToneClass(entry.tone)"
              >{{ entry.text }}</p>
            </div>
          </div>
        </aside>
      </div>
    </template>
  </section>
</template>

<style scoped>
/* ── Shared grid/cell styles ─────────────────────────────────────────────── */
.battle-grid,
.placement-grid {
  display: grid;
  grid-template-columns: repeat(9, minmax(0, 1fr));
  gap: 0.3rem;
}

.axis-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 1.95rem;
  border-radius: 0.45rem;
  font-size: 0.68rem;
  font-weight: 700;
  color: rgba(165, 243, 252, 0.85);
  background: rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(125, 211, 252, 0.2);
}

.battle-cell {
  aspect-ratio: 1 / 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.45rem;
  border: 1px solid rgba(165, 243, 252, 0.28);
  font-size: 0.86rem;
  font-weight: 900;
  transition: transform 120ms ease, border-color 120ms ease, background-color 120ms ease;
}

/* ── Placement grid cells ────────────────────────────────────────────────── */
.placement-cell {
  aspect-ratio: 1 / 1;
  border-radius: 0.45rem;
  border: 1px solid rgba(165, 243, 252, 0.2);
  transition: background-color 80ms ease, border-color 80ms ease;
  cursor: crosshair;
  position: relative;
}

/* ── Ship token overlays on placement grid ───────────────────────────────── */
.placement-grid {
  position: relative;
}

.ship-token-overlay {
  grid-row: auto;
  grid-column: auto;
  border-radius: 0.5rem;
  border: 2px solid rgba(52, 211, 153, 0.8);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.55), rgba(5, 150, 105, 0.45));
  cursor: grab;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  transition: opacity 120ms, border-color 120ms, box-shadow 120ms;
  box-shadow: 0 0 0 1px rgba(52, 211, 153, 0.25), 0 4px 12px rgba(0, 0, 0, 0.35);
  user-select: none;
}

.ship-token-overlay:hover {
  border-color: rgba(110, 231, 183, 0.9);
  box-shadow: 0 0 0 2px rgba(52, 211, 153, 0.4), 0 6px 18px rgba(0, 0, 0, 0.4);
}

.ship-token-overlay:active {
  cursor: grabbing;
  opacity: 0.8;
}

.ship-label {
  font-size: 0.7rem;
  font-weight: 900;
  color: rgba(209, 250, 229, 0.9);
  pointer-events: none;
}

/* ── Cell states ─────────────────────────────────────────────────────────── */
.cell-water {
  color: rgba(125, 211, 252, 0.35);
  background: linear-gradient(160deg, rgba(8, 47, 73, 0.6), rgba(12, 74, 110, 0.5));
}

.cell-ship {
  color: rgba(217, 249, 157, 0.9);
  background: linear-gradient(160deg, rgba(20, 83, 45, 0.65), rgba(21, 128, 61, 0.55));
  border-color: rgba(134, 239, 172, 0.5);
}

.cell-fog {
  color: transparent;
  background: linear-gradient(165deg, rgba(2, 132, 199, 0.38), rgba(15, 23, 42, 0.68));
}

.cell-hit {
  color: #fee2e2;
  border-color: rgba(251, 113, 133, 0.8);
  background: linear-gradient(165deg, rgba(190, 24, 93, 0.88), rgba(136, 19, 55, 0.85));
}

.cell-miss {
  color: rgba(207, 250, 254, 0.88);
  border-color: rgba(103, 232, 249, 0.6);
  background: linear-gradient(165deg, rgba(6, 78, 110, 0.8), rgba(15, 23, 42, 0.85));
}

/* ── Placement preview states ────────────────────────────────────────────── */
.cell-preview-valid {
  background: linear-gradient(160deg, rgba(16, 185, 129, 0.45), rgba(5, 150, 105, 0.35));
  border-color: rgba(52, 211, 153, 0.8);
  box-shadow: 0 0 0 1px rgba(52, 211, 153, 0.3);
}

.cell-preview-invalid {
  background: linear-gradient(160deg, rgba(190, 24, 93, 0.45), rgba(136, 19, 55, 0.35));
  border-color: rgba(251, 113, 133, 0.8);
  box-shadow: 0 0 0 1px rgba(251, 113, 133, 0.3);
}

/* ── Dock ships ──────────────────────────────────────────────────────────── */
.dock-ship {
  border-radius: 0.65rem;
  border: 1px solid;
  padding: 0.5rem 0.65rem;
  transition: opacity 150ms, border-color 150ms, background-color 150ms, transform 100ms;
  user-select: none;
}

.dock-ship--available {
  border-color: rgba(125, 211, 252, 0.45);
  background: rgba(8, 47, 73, 0.55);
  color: rgba(224, 247, 254, 0.9);
  cursor: grab;
}

.dock-ship--available:hover {
  border-color: rgba(125, 211, 252, 0.75);
  background: rgba(8, 47, 73, 0.75);
  transform: translateY(-1px);
}

.dock-ship--available:active {
  cursor: grabbing;
  transform: translateY(0);
}

.dock-ship--placed {
  border-color: rgba(52, 211, 153, 0.4);
  background: rgba(5, 46, 22, 0.45);
  color: rgba(167, 243, 208, 0.75);
  opacity: 0.7;
  cursor: default;
}

.ship-pip {
  height: 0.45rem;
  flex: 1;
  border-radius: 0.2rem;
}

.ship-pip--available {
  background: rgba(125, 211, 252, 0.6);
}

.ship-pip--placed {
  background: rgba(52, 211, 153, 0.55);
}

/* ── Battle log scroll ───────────────────────────────────────────────────── */
.log-scroll {
  max-height: 15.5rem;
  overflow: auto;
  scrollbar-width: thin;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
  .axis-cell {
    min-height: 1.7rem;
    font-size: 0.62rem;
  }

  .battle-cell,
  .placement-cell {
    font-size: 0.78rem;
  }
}
</style>