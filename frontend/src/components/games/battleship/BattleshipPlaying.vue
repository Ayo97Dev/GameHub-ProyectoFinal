<template>
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
            @click="$emit('fire-at-enemy', x, y)"
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

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  score: Number,
  playerAccuracy: Number,
  enemyShipsSunk: Number,
  SHIP_DEFS: Array,
  statusText: String,
  playerShipsSunk: Number,
  COL_LABELS: Array,
  ROW_LABELS: Array,
  playerBoard: Array,
  lastImpactCoord: Object,
  playerShots: Number,
  enemyBoard: Array,
  canShoot: Boolean,
  enemyShipStatus: Array,
  battleLog: Array
})

defineEmits(['fire-at-enemy'])

function isTargeted(cell) {
  return cell.state === 'hit' || cell.state === 'miss'
}

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
</script>

<style scoped>
/* ── Shared grid/cell styles ─────────────────────────────────────────────── */
.battle-grid {
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

/* ── Battle cell states ──────────────────────────────────────────────────── */
.cell-water {
  background: rgba(0, 242, 255, 0.05);
  color: rgba(255, 255, 255, 0.1);
}

.cell-fog {
  background: rgba(255, 255, 255, 0.02);
  color: transparent;
}

.cell-ship {
  background: rgba(0, 242, 255, 0.15);
  border-color: rgba(0, 242, 255, 0.3);
  color: var(--color-neon-cyan);
}

.cell-hit {
  background: rgba(255, 45, 85, 0.2);
  border-color: var(--color-neon-pink);
  color: var(--color-neon-pink);
  animation: hit-pulse 2s infinite ease-in-out;
}

.cell-miss {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  opacity: 0.5;
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
  .battle-grid {
    gap: 2px;
  }

  .axis-cell {
    min-height: 1.5rem;
    font-size: 8px;
  }

  .battle-cell {
    font-size: 10px;
  }
}

@media (max-width: 400px) {
  .axis-cell {
    min-height: 1.25rem;
    font-size: 7px;
  }
  
  .battle-cell {
    font-size: 8px;
  }
}
</style>
