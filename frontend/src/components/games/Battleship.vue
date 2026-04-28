<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useBattleshipStore } from '../../stores/games/battleship'

const emit = defineEmits(['score-change', 'game-completed'])

const battleshipStore = useBattleshipStore()
const isLoading = computed(() => battleshipStore.isLoading)
const syncError = computed(() => battleshipStore.error)
const score = computed(() => battleshipStore.wins)
const GAME_SLUG = 'battleship'
const BOARD_SIZE = 10
const SHIP_DEFS = [
  { id: 'carrier', name: 'Portaaviones', size: 5, icon: 'game-icons:carrier' },
  { id: 'battleship', name: 'Acorazado', size: 4, icon: 'game-icons:battleship' },
  { id: 'crucero', name: 'Crucero', size: 3, icon: 'game-icons:iron-hulled-warship' },
  { id: 'submarine', name: 'Submarino', size: 3, icon: 'game-icons:submarine' },
  { id: 'destroyer', name: 'Destructor', size: 2, icon: 'game-icons:scout-ship' },
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
const lastHoverCell = ref(null)

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

// ─── Animation state ─────────────────────────────────────────────────────────
const isShakeActive = ref(false)
const lastImpactCoord = ref(null) // { x, y, board: 'player' | 'enemy' }

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

function getOrderedShipCells(ship) {
  if (!ship) return []
  return [...ship.cells].sort((a, b) => (ship.horizontal ? a.x - b.x : a.y - b.y))
}

function clearPlacementDragState() {
  hoverPreview.value = []
  dragShip.value = null
  touchDragging.value = false
  touchShipId.value = null
  lastHoverCell.value = null
}

function beginMovePlacedShip(shipId, x, y) {
  const placed = placedShips.value[shipId]
  const shipDef = SHIP_DEFS.find(s => s.id === shipId)
  if (!placed || !shipDef) return false

  const orderedCells = getOrderedShipCells(placed)
  const offsetCell = Math.max(orderedCells.findIndex(c => c.x === x && c.y === y), 0)

  dragOrientation.value = placed.horizontal ? 'horizontal' : 'vertical'
  dragShip.value = {
    id: shipId,
    size: shipDef.size,
    name: shipDef.name,
    offsetCell
  }
  computeHoverPreview(x, y)
  return true
}

function computeHoverPreview(x, y) {
  if (!dragShip.value) return { cells: [], valid: false }
  lastHoverCell.value = { x, y }

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
  if (!dragShip.value) return

  const preview = computeHoverPreview(x, y)
  if (preview.valid) {
    placeShip(dragShip.value.id, preview.cells)
  }

  clearPlacementDragState()
}

function handleGlobalMouseUp(e) {
  if (phase.value !== 'placement' || !dragShip.value) return
  
  if (lastHoverCell.value) {
    handleCellDrop(e, lastHoverCell.value.x, lastHoverCell.value.y)
  } else {
    // Return ship to dock if dropped outside
    clearPlacementDragState()
  }
}

// 🔥 FIX PRINCIPAL también aquí
function startDragFromDock(shipDef) {
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
}

function startDragFromPlacedCell(x, y) {
  const shipId = getShipIdAt(x, y)
  if (!shipId) return
  beginMovePlacedShip(shipId, x, y)
}

function rotateDragOrientation() {
  if (!dragShip.value) return
  dragOrientation.value = dragOrientation.value === 'horizontal' ? 'vertical' : 'horizontal'

  // Si estamos sobre una celda, actualizamos la preview inmediatamente
  if (lastHoverCell.value) {
    computeHoverPreview(lastHoverCell.value.x, lastHoverCell.value.y)
  }
}

function handlePlacementKeydown(event) {
  if (phase.value !== 'placement' || !dragShip.value) return
  const key = String(event.key).toLowerCase()
  if (key === 'r') {
    event.preventDefault()
    rotateDragOrientation()
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
  battleLog.value = [makeLogEntry('Misión iniciada. El radar está operativo.', 'good')]
  enemyTargetQueue.value = []
  enemyTargetSet.value = new Set()

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

function unplacedShips() {
  return SHIP_DEFS.filter(s => !placedShips.value[s.id])
}

// Touch drag support
function handleTouchStart(e, shipDef) {
  e.preventDefault()
  const placed = placedShips.value[shipDef.id]
  dragOrientation.value = placed?.horizontal === false ? 'vertical' : 'horizontal'

  if (placed) {
    removePlacedShip(shipDef.id)
  }

  touchDragging.value = true
  touchShipId.value = shipDef.id
  dragShip.value = { id: shipDef.id, size: shipDef.size, name: shipDef.name, offsetCell: 0 }
  lastHoverCell.value = null
}

function handleCellTouchStart(e, x, y, shipId) {
  e.preventDefault()
  if (touchDragging.value) {
    handleCellTouchOver(e, x, y)
    return
  }

  if (!shipId) return

  if (!beginMovePlacedShip(shipId, x, y)) return

  touchDragging.value = true
  touchShipId.value = shipId
}

function handleCellTouchOver(e, x, y) {
  if (!touchDragging.value) return
  e.preventDefault()
  computeHoverPreview(x, y)
}

function handleCellTouchDrop(e, x, y) {
  if (!touchDragging.value) return
  e.preventDefault()
  const preview = computeHoverPreview(x, y)
  if (preview && preview.valid) {
    placeShip(dragShip.value.id, preview.cells)
  }
  clearPlacementDragState()
}

function handlePlacementCellClick(x, y, shipId, event) {
  if (phase.value !== 'placement') return

  if (dragShip.value) {
    const preview = computeHoverPreview(x, y)
    if (preview.valid) {
      placeShip(dragShip.value.id, preview.cells)
      clearPlacementDragState()
    }
    return
  }

  if (!shipId) return

  if (event?.altKey) {
    toggleOrientation(shipId)
    return
  }

  beginMovePlacedShip(shipId, x, y)
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
    battleLog: [makeLogEntry('Misión iniciada. El radar está operativo.', 'good')],
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

async function saveProgress() {
  await battleshipStore.saveStats(serializeState())
}

async function finalizeBattle(won = false) {
  if (won) {
    await battleshipStore.recordWin(serializeState())
  } else {
    await battleshipStore.saveStats(serializeState())
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

function endBattle(status) {
  gameStatus.value = status
  turn.value = 'none'
  enemyThinking.value = false
  clearEnemyTimer()
  if (status === 'won') pushLog('Victoria total. Toda la flota enemiga ha sido neutralizada.', 'good')
  else pushLog('Derrota. La flota ha sido diezmada.', 'bad')
  finalizeBattle(status === 'won')
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
      
      // Trigger shake and impact animation
      isShakeActive.value = true
      lastImpactCoord.value = { x: target.x, y: target.y, board: 'player' }
      setTimeout(() => { isShakeActive.value = false }, 500)
      setTimeout(() => { lastImpactCoord.value = null }, 800)

      if (result.sunk) { playerShipsSunk.value += 1; resetEnemyQueue(); pushLog(`El enemigo ha hundido tu ${result.shipName}.`, 'bad') }
      else addEnemyTargetsAround(target.x, target.y)
    } else pushLog(`Fuego fallido del enemigo en ${formatCoordinate(target.x, target.y)}.`, 'neutral')
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
    
    // Trigger impact animation on enemy board
    lastImpactCoord.value = { x, y, board: 'enemy' }
    setTimeout(() => { lastImpactCoord.value = null }, 800)

    if (result.sunk) { enemyShipsSunk.value += 1; pushLog(`Has hundido el ${result.shipName} enemigo.`, 'good') }
  } else pushLog(`Impacto en agua en ${formatCoordinate(x, y)}.`, 'neutral')
  if (result.allSunk) { endBattle('won'); return }
  turn.value = 'enemy'
  scheduleEnemyTurn()
}

async function handleNewGameClick() {
  clearEnemyTimer()
  gameStatus.value = 'playing'
  phase.value = 'placement'
  resetPlacement()
  await battleshipStore.initializeGame(false)
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
    return { id: ship.id, name: ship.name, size: ship.size, icon: ship.icon, hits: runtime?.hits ?? 0, sunk: runtime?.sunk ?? false }
  })
)

const enemyShipStatus = computed(() =>
  SHIP_DEFS.map(ship => {
    const runtime = enemyFleet.value[ship.id]
    return { id: ship.id, name: ship.name, size: ship.size, icon: ship.icon, hits: runtime?.hits ?? 0, sunk: runtime?.sunk ?? false }
  })
)

const playerAccuracy = computed(() => {
  if (playerShots.value === 0) return 0
  return Math.round((playerHits.value / playerShots.value) * 100)
})


const statusText = computed(() => {
  if (gameStatus.value === 'won') return 'Misión completada: Victoria naval.'
  if (gameStatus.value === 'lost') return 'Flota perdida. Reagrupando unidades.'
  if (enemyThinking.value) return 'Turno enemigo: Analizando coordenadas...'
  if (turn.value === 'player') return 'Tu turno: Selecciona un objetivo en el radar.'
  return 'Turno del oponente.'
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


onMounted(async () => {
  await battleshipStore.initializeGame(true)
  if (battleshipStore.sessionId) {
    // If we have a session with state, apply it
    // Note: The store should ideally expose the game_state for hydration
  }
  startAutosave()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('beforeunload', handleBeforeUnload)
  document.addEventListener('keydown', handlePlacementKeydown, { capture: true })
  window.addEventListener('mouseup', handleGlobalMouseUp)
})

onUnmounted(() => {
  stopAutosave()
  clearEnemyTimer()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('beforeunload', handleBeforeUnload)
  document.removeEventListener('keydown', handlePlacementKeydown, { capture: true })
  window.removeEventListener('mouseup', handleGlobalMouseUp)
  void saveProgress()
  emit('score-change', 0)
})
</script>
<template>
  <section 
    class="gh-panel relative overflow-hidden bg-retro-deep p-4 text-retro-white sm:p-5"
    :class="{ 'shake-screen': isShakeActive }"
  >
    <div class="gh-scanlines pointer-events-none absolute inset-0 opacity-20" />

    <!-- START OVERLAY -->
    <div v-if="gameStatus === 'idle'" class="absolute inset-0 z-50 flex items-center justify-center bg-retro-black/90 backdrop-blur-md">
      <div class="gh-panel w-full max-w-lg border-neon-cyan/40 bg-retro-dark p-10 text-center shadow-[0_0_50px_rgba(0,242,255,0.15)]">
        <Icon icon="mdi:ship-wheel" class="mx-auto mb-6 text-6xl text-neon-cyan" />
        <h2 class="gh-title-glow font-display mb-2 text-4xl font-black uppercase tracking-widest text-white">Battleship</h2>
        <p class="font-pixel mb-10 text-xs tracking-[0.4em] text-white/50">SISTEMA DE DEFENSA NAVAL OPERATIVO</p>
        
        <div class="space-y-4">
          <button 
            class="gh-surface gh-surface-hover w-full py-5 font-display text-lg font-black uppercase tracking-[0.2em] text-white bg-neon-cyan/10 border-neon-cyan"
            @click="phase = 'placement'; gameStatus = 'playing'; resetPlacement()"
          >
            INICIAR MISIÓN [ORDEN 001]
          </button>
        </div>
      </div>
    </div>

    <!-- GAME OVER OVERLAY -->
    <div v-if="gameStatus === 'won' || gameStatus === 'lost'" class="absolute inset-0 z-50 flex items-center justify-center bg-retro-black/90 backdrop-blur-md">
      <div class="gh-panel w-full max-w-lg border-neon-pink/40 bg-retro-dark p-10 text-center shadow-[0_0_50px_rgba(255,45,85,0.15)]">
        <Icon 
          :icon="gameStatus === 'won' ? 'mdi:trophy' : 'mdi:skull'" 
          class="mx-auto mb-6 text-6xl"
          :class="gameStatus === 'won' ? 'text-neon-cyan' : 'text-neon-pink'"
        />
        <h2 class="gh-title-glow font-display mb-2 text-4xl font-black uppercase tracking-widest text-white">
          {{ gameStatus === 'won' ? 'VICTORIA' : 'DERROTA' }}
        </h2>
        <p class="font-pixel mb-8 text-xs tracking-[0.4em] text-white/50">INFORME DE COMBATE FINALIZADO</p>
        
        <div class="mb-10 grid grid-cols-2 gap-4 bg-white/5 p-6 font-pixel">
          <div class="text-left">
            <p class="text-[10px] uppercase text-white/40">Precisión</p>
            <p class="text-2xl font-bold text-neon-cyan">{{ playerAccuracy }}%</p>
          </div>
          <div class="text-left">
            <p class="text-[10px] uppercase text-white/40">Bajas Enemigas</p>
            <p class="text-2xl font-bold text-neon-pink">{{ enemyShipsSunk }} / {{ SHIP_DEFS.length }}</p>
          </div>
          <div class="text-left">
            <p class="text-[10px] uppercase text-white/40">Total Disparos</p>
            <p class="text-2xl font-bold text-white">{{ playerShots }}</p>
          </div>
          <div class="text-left">
            <p class="text-[10px] uppercase text-white/40">Bajas Aliadas</p>
            <p class="text-2xl font-bold text-neon-yellow">{{ playerShipsSunk }}</p>
          </div>
        </div>

        <button 
          class="gh-surface gh-surface-hover w-full py-5 font-display text-lg font-black uppercase tracking-[0.2em] text-white border-white/20"
          @click="handleNewGameClick"
        >
          REINTENTAR OPERACIÓN
        </button>
      </div>
    </div>

    <!-- Header -->
    <header class="relative z-10 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
      <div>
        <p class="font-pixel text-[10px] uppercase tracking-[0.3em] text-neon-cyan">Conquista de los Océanos // Modo Estrategia</p>
        <h2 class="gh-title-glow font-display mt-1 text-3xl font-black text-white">Estrategia Naval</h2>
        <p class="font-sans mt-2 max-w-2xl text-xs text-retro-white/70">
          <template v-if="phase === 'placement'">DESPLIEGUE: Posiciona tu flota en el tablero táctico.</template>
          <template v-else>COMBATE: Detecta y elimina la amenaza enemiga.</template>
        </p>
      </div>

      <button
        type="button"
        class="gh-surface-hover gh-surface bg-neon-cyan/10 px-4 py-2 font-display text-sm font-bold text-neon-cyan transition disabled:opacity-50"
        :disabled="isLoading"
        @click="handleNewGameClick"
      >
        {{ isLoading ? 'Sincronizando...' : 'Nueva Misión' }}
      </button>
    </header>

    <div v-if="syncError" class="relative z-10 mt-2 border border-neon-pink/30 bg-neon-pink/10 p-2 font-pixel text-[11px] text-neon-pink uppercase">
      ERROR DE SINCRONIZACIÓN: {{ syncError }}
    </div>

    <!-- FASE DE PREPARACION  -->
    <template v-if="phase === 'placement'">
      <div class="relative z-10 mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">

        <!-- Placement board -->
        <article class="gh-panel bg-retro-black/50 p-4">
          <div class="flex items-center justify-between gap-2 flex-wrap mb-4">
            <h3 class="font-pixel text-xs font-bold uppercase tracking-widest text-neon-cyan">Zona de Despliegue</h3>
            <div class="flex flex-col gap-1 items-end">
              <div class="flex gap-2">
                <button
                  type="button"
                  class="gh-surface-hover gh-surface bg-white/5 px-3 py-1 font-pixel text-[10px] text-white"
                  @click="randomizePlacement"
                >Generar Aleatorio</button>
                <button
                  type="button"
                  class="gh-surface-hover gh-surface bg-neon-pink/10 px-3 py-1 font-pixel text-[10px] text-neon-pink"
                  @click="resetPlacement"
                >Limpiar Tablero</button>
              </div>
              <p v-if="dragShip" class="font-pixel text-[9px] text-neon-cyan animate-pulse">
                Presiona <span class="text-white border border-white/30 px-1 rounded">R</span> para rotar el barco
              </p>
            </div>
          </div>

          <!-- Grid -->
          <div
            class="placement-grid select-none"
            @mouseleave="lastHoverCell = null"
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
                @mouseenter="computeHoverPreview(x, y)"
                @mousedown="cell.shipId ? startDragFromPlacedCell(x, y) : handleCellDrop($event, x, y)"
                @touchstart.prevent="handleCellTouchStart($event, x, y, cell.shipId)"
                @touchmove.prevent="handleCellTouchOver($event, x, y)"
                @touchend.prevent="handleCellTouchDrop($event, x, y)"
                @click="handlePlacementCellClick(x, y, cell.shipId, $event)"
              />
            </template>
          </div>
        </article>

        <!-- Ship dock + confirm -->
        <aside class="space-y-4">
          <div class="gh-panel bg-retro-black/50 p-4">
            <p class="font-pixel text-[10px] uppercase tracking-widest text-neon-cyan">Arsenal Disponible</p>
            
            <div class="mt-4 space-y-3">
              <div
                v-for="ship in SHIP_DEFS"
                :key="`dock-${ship.id}`"
                class="gh-surface p-2 transition"
                :class="placedShips[ship.id] ? 'opacity-30 border-white/5' : 'gh-surface-hover border-neon-cyan/30 bg-neon-cyan/5 cursor-grab'"
                @mousedown="!placedShips[ship.id] ? startDragFromDock(ship) : null"
                @touchstart.prevent="handleTouchStart($event, ship)"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <Icon :icon="ship.icon" class="text-lg text-neon-cyan" :class="{ 'opacity-50': placedShips[ship.id] }" />
                    <span class="font-display text-[10px] font-bold uppercase">{{ ship.name }}</span>
                  </div>
                  <span class="font-pixel text-[10px] text-white/50">{{ ship.size }}U</span>
                </div>
                <div class="mt-2 flex gap-1">
                  <span
                    v-for="i in ship.size"
                    :key="i"
                    class="h-1.5 flex-1 bg-neon-cyan/40"
                    :class="{ 'bg-neon-cyan': !placedShips[ship.id] }"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Progress indicator -->
          <div class="gh-panel border-white/10 bg-white/5 p-3">
            <div class="flex items-center justify-between font-pixel">
              <span class="text-[10px] uppercase tracking-widest text-white/60">Estado del Despliegue</span>
              <span class="text-[10px] text-neon-cyan">{{ Object.keys(placedShips).length }} / {{ SHIP_DEFS.length }}</span>
            </div>
            <div class="mt-2 h-1 bg-white/10">
              <div
                class="h-full bg-neon-cyan transition-all duration-300"
                :style="{ width: `${(Object.keys(placedShips).length / SHIP_DEFS.length) * 100}%` }"
              />
            </div>
          </div>

          <!-- Confirm button -->
          <button
            type="button"
            class="gh-surface w-full py-4 font-display text-sm font-black uppercase tracking-widest transition"
            :class="placementComplete
              ? 'gh-surface-hover border-neon-cyan bg-neon-cyan/20 text-neon-cyan'
              : 'cursor-not-allowed border-white/10 bg-white/5 text-white/20'"
            :disabled="!placementComplete"
            @click="confirmPlacement"
          >
            {{ placementComplete ? 'Iniciar Operación' : 'Esperando Despliegue' }}
          </button>
        </aside>
      </div>
    </template>

    <!-- BATTLE PHASE -->
    <template v-else>
      <div class="relative z-10 mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="gh-panel border-white/10 bg-white/5 p-3">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Historial de Victorias</p>
          <p class="font-pixel mt-1 text-2xl font-black tabular-nums text-neon-cyan">{{ score }}</p>
        </div>
        <div class="gh-panel border-white/10 bg-white/5 p-3">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Ratio de Precisión</p>
          <p class="font-pixel mt-1 text-2xl font-black tabular-nums text-white">{{ playerAccuracy }}%</p>
        </div>
        <div class="gh-panel border-white/10 bg-white/5 p-3">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Objetivos Enemigos</p>
          <p class="font-pixel mt-1 text-2xl font-black tabular-nums text-neon-pink">{{ SHIP_DEFS.length - enemyShipsSunk }} / {{ SHIP_DEFS.length }}</p>
        </div>
        <div class="gh-panel border-white/10 bg-white/5 p-3">
          <p class="font-pixel text-[9px] uppercase tracking-widest text-white/50">Estado de la Misión</p>
          <p class="mt-1 font-pixel text-[11px] font-bold uppercase text-neon-cyan">{{ statusText }}</p>
        </div>
      </div>

      <div class="relative z-10 mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_320px]">
        <!-- Player board -->
        <article class="gh-panel bg-retro-black/50 p-4">
          <div class="flex items-center justify-between gap-2 mb-4 border-b border-white/5 pb-2">
            <h3 class="font-pixel text-xs font-bold uppercase tracking-widest text-neon-cyan">Estado de la Flota Aliada</h3>
            <span class="font-pixel text-[10px] text-neon-pink">Bajas: {{ playerShipsSunk }} / {{ SHIP_DEFS.length }}</span>
          </div>
          <div class="battle-grid">
            <span class="axis-cell" />
            <span v-for="col in COL_LABELS" :key="`p-col-${col}`" class="axis-cell">{{ col }}</span>
            <template v-for="(row, y) in playerBoard" :key="`p-row-${y}`">
              <span class="axis-cell">{{ ROW_LABELS[y] }}</span>
              <button
                v-for="(cell, x) in row"
                :key="`p-cell-${x}-${y}`"
                type="button"
                class="battle-cell"
                :class="[
                  playerCellClass(cell),
                  lastImpactCoord?.x === x && lastImpactCoord?.y === y && lastImpactCoord?.board === 'player' ? 'cell-impact' : ''
                ]"
                disabled
              >{{ playerCellMarker(cell) }}</button>
            </template>
          </div>
        </article>

        <!-- Enemy board -->
        <article class="gh-panel border-neon-cyan/20 bg-retro-black/50 p-4 shadow-[0_0_20px_rgba(0,242,255,0.05)]">
          <div class="flex items-center justify-between gap-2 mb-4 border-b border-white/5 pb-2">
            <h3 class="font-pixel text-xs font-bold uppercase tracking-widest text-neon-cyan">Radar Táctico Enemigo</h3>
            <span class="font-pixel text-[10px] text-white/50">Escaneos Realizados: {{ playerShots }}</span>
          </div>
          <div class="battle-grid">
            <span class="axis-cell" />
            <span v-for="col in COL_LABELS" :key="`e-col-${col}`" class="axis-cell">{{ col }}</span>
            <template v-for="(row, y) in enemyBoard" :key="`e-row-${y}`">
              <span class="axis-cell">{{ ROW_LABELS[y] }}</span>
              <button
                v-for="(cell, x) in row"
                :key="`e-cell-${x}-${y}`"
                type="button"
                class="battle-cell"
                :class="[
                  enemyCellClass(cell), 
                  canShoot && !isTargeted(cell) ? 'gh-surface-hover cursor-pointer border-neon-cyan/40 bg-neon-cyan/5' : 'cursor-not-allowed opacity-80',
                  lastImpactCoord?.x === x && lastImpactCoord?.y === y && lastImpactCoord?.board === 'enemy' ? 'cell-impact' : ''
                ]"
                :disabled="!canShoot || isTargeted(cell)"
                @click="fireAtEnemy(x, y)"
              >{{ enemyCellMarker(cell) }}</button>
            </template>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-4">
          <div class="gh-panel border-white/5 bg-white/2 p-4">
            <p class="font-pixel text-[10px] uppercase tracking-widest text-white/50 mb-3">Lista de Objetivos</p>
            <div class="space-y-2">
                <div
                  v-for="ship in enemyShipStatus"
                  :key="`enemy-ship-${ship.id}`"
                  class="flex items-center justify-between border border-white/10 px-3 py-2 font-pixel"
                  :class="ship.sunk ? 'bg-neon-pink/10 text-neon-pink border-neon-pink/30' : 'bg-white/5 text-white/80'"
                >
                  <div class="flex items-center gap-2">
                    <Icon :icon="ship.icon" class="text-base" />
                    <span class="text-[11px] uppercase">{{ ship.name }}</span>
                  </div>
                  <span class="text-[10px]">{{ ship.sunk ? 'ELIMINADO' : `${ship.hits}/${ship.size}` }}</span>
                </div>
              </div>
            </div>

            <div class="gh-panel border-white/5 bg-white/2 p-4">
            <p class="font-pixel text-[10px] uppercase tracking-widest text-white/50 mb-3">Bitácora de Combate</p>
            <div class="log-scroll space-y-2 pr-2">
              <p
                v-for="entry in battleLog"
                :key="entry.id"
                class="border-l-2 px-3 py-1 font-pixel text-[11px]"
                :class="{
                  'border-neon-cyan text-neon-cyan': entry.tone === 'good',
                  'border-neon-pink text-neon-pink': entry.tone === 'bad',
                  'border-white/20 text-white/60': entry.tone === 'neutral'
                }"
              >> {{ entry.text }}</p>
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
  grid-template-columns: repeat(11, minmax(0, 1fr));
  gap: 4px;
}

.axis-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2rem;
  background: var(--color-retro-black);
  border: 1px solid rgba(255, 255, 255, 0.1);
  font-family: var(--font-pixel);
  font-size: 10px;
  color: var(--color-neon-cyan);
  opacity: 0.8;
}

.battle-cell {
  aspect-ratio: 1 / 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  font-family: var(--font-pixel);
  font-size: 14px;
  font-weight: 900;
  transition: all 100ms ease;
  position: relative;
  overflow: hidden;
}

.shake-screen {
  animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

.cell-impact::after {
  content: '';
  position: absolute;
  inset: 0;
  background: white;
  animation: flash 0.8s ease-out forwards;
  z-index: 5;
}

@keyframes flash {
  0% { opacity: 0.8; transform: scale(0.5); }
  100% { opacity: 0; transform: scale(2); }
}

/* ── Placement grid cells ────────────────────────────────────────────────── */
.placement-cell {
  aspect-ratio: 1 / 1;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 100ms ease;
  cursor: pointer;
  position: relative;
}

/* ── Cell states ─────────────────────────────────────────────────────────── */
.cell-water {
  background: rgba(0, 242, 255, 0.03);
}

.cell-ship {
  background: var(--color-neon-cyan);
  box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
  border-color: var(--color-neon-cyan);
}

.cell-fog {
  background: var(--color-retro-dark);
}

.cell-hit {
  background: linear-gradient(135deg, var(--color-neon-pink), #9f1239);
  box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.6), 0 0 10px rgba(255, 45, 85, 0.3);
  border-color: var(--color-neon-pink);
  color: white;
  text-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
}

.cell-miss {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  opacity: 0.5;
}

/* ── Placement preview states ────────────────────────────────────────────── */
.cell-preview-valid {
  background: rgba(0, 242, 255, 0.4) !important;
  border-color: var(--color-neon-cyan) !important;
  box-shadow: inset 0 0 15px rgba(0, 242, 255, 0.3), 0 0 10px rgba(0, 242, 255, 0.2);
  animation: pulse-cyan 1.5s infinite ease-in-out;
  z-index: 10;
}

.cell-preview-invalid {
  background: rgba(255, 45, 85, 0.4) !important;
  border-color: var(--color-neon-pink) !important;
  box-shadow: inset 0 0 15px rgba(255, 45, 85, 0.3);
  animation: pulse-pink 1s infinite ease-in-out;
  z-index: 10;
}

@keyframes pulse-cyan {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

@keyframes pulse-pink {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(0.95); }
}

/* ── Battle log scroll ───────────────────────────────────────────────────── */
.log-scroll {
  max-height: 180px;
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

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
  .battle-grid,
  .placement-grid {
    gap: 2px;
  }

  .axis-cell {
    min-height: 1.5rem;
    font-size: 8px;
  }

  .battle-cell,
  .placement-cell {
    font-size: 10px;
  }
}

@media (max-width: 400px) {
  .axis-cell {
    min-height: 1.25rem;
    font-size: 7px;
  }
  
  .battle-cell,
  .placement-cell {
    font-size: 8px;
  }
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