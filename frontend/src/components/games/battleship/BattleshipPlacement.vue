<template>
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
              @click="$emit('randomize')"
            >Generar Aleatorio</button>
            <button
              type="button"
              class="gh-surface-hover gh-surface bg-neon-pink/10 px-3 py-1 font-pixel text-[10px] text-neon-pink"
              @click="$emit('reset')"
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
        @mouseleave="$emit('mouseleave-grid')"
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
            @mouseenter="$emit('compute-hover-preview', x, y)"
            @mousedown="cell.shipId ? $emit('start-drag-placed', x, y, $event) : $emit('cell-drop', $event, x, y)"
            @touchstart.prevent="$emit('touch-start-cell', $event, x, y, cell.shipId)"
            @touchmove.prevent="$emit('touch-over-cell', $event, x, y)"
            @touchend.prevent="$emit('touch-drop-cell', $event, x, y)"
            @click="$emit('cell-click', x, y, cell.shipId, $event)"
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
            @mousedown="!placedShips[ship.id] ? $emit('start-drag-dock', ship, $event) : null"
            @touchstart.prevent="$emit('touch-start-dock', $event, ship)"
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
        @click="$emit('confirm')"
      >
        {{ placementComplete ? 'Iniciar Operación' : 'Esperando Despliegue' }}
      </button>
    </aside>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  placementBoard: Array,
  COL_LABELS: Array,
  ROW_LABELS: Array,
  dragShip: Object,
  placedShips: Object,
  SHIP_DEFS: Array,
  placementComplete: Boolean,
  placementCellClass: Function
})

defineEmits([
  'randomize', 'reset', 'mouseleave-grid', 'compute-hover-preview',
  'start-drag-placed', 'cell-drop', 'touch-start-cell', 'touch-over-cell',
  'touch-drop-cell', 'cell-click', 'start-drag-dock', 'touch-start-dock',
  'confirm'
])
</script>

<style scoped>
/* ── Shared grid/cell styles ─────────────────────────────────────────────── */
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

.placement-cell {
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

.cell-water {
  background: rgba(0, 242, 255, 0.05);
  color: rgba(255, 255, 255, 0.1);
}

.cell-ship {
  background: rgba(0, 242, 255, 0.15);
  border-color: rgba(0, 242, 255, 0.3);
  color: var(--color-neon-cyan);
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

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
  .placement-grid {
    gap: 2px;
  }

  .axis-cell {
    min-height: 1.5rem;
    font-size: 8px;
  }

  .placement-cell {
    font-size: 10px;
  }
}

@media (max-width: 400px) {
  .axis-cell {
    min-height: 1.25rem;
    font-size: 7px;
  }
  
  .placement-cell {
    font-size: 8px;
  }
}
</style>
