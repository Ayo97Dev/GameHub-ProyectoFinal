<template>
  <div class="tower-defense h-full min-h-[750px] flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>

    <!-- Background Ambient Layers -->
    <div class="absolute inset-0 bg-gradient-to-b from-neon-cyan/5 via-transparent to-neon-pink/5 pointer-events-none"></div>

    <!-- SELECCIÓN INICIAL -->
    <div v-if="gameMode === 'choice'" class="relative z-40 flex flex-col items-center justify-center flex-1 p-6">
      <div class="gh-glass max-w-xl w-full p-10 text-center border-white/5 shadow-2xl bg-black/60">
        <div class="flex items-center justify-center gap-4 mb-8">
           <div class="h-px w-12 bg-neon-cyan"></div>
           <p class="font-pixel text-neon-cyan text-lg tracking-[0.4em] uppercase">COMANDO_ESTRATÉGICO</p>
           <div class="h-px w-12 bg-neon-cyan"></div>
        </div>
        
        <h1 class="font-display text-2xl sm:text-3xl font-black text-white mb-6 gh-title-glow tracking-[-0.05em]">PROYECTO_CORTAFUEGOS</h1>
        <p class="font-sans text-xs font-medium uppercase text-white/40 mb-12 max-w-sm mx-auto leading-relaxed">
          CONSTRUYE NODOS DE DEFENSA PARA PROTEGER EL NÚCLEO DEL REACTOR CONTRA LA INFILTRACIÓN DE MALWARE.
        </p>
        
        <div v-if="store.isLoading" class="py-6">
           <div class="size-12 border-t-2 border-neon-cyan rounded-full animate-spin mx-auto"></div>
           <p class="font-pixel text-neon-cyan mt-6 animate-pulse uppercase tracking-widest">CONECTANDO_NÚCLEO...</p>
        </div>
        
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button
            v-if="store.savedGame"
            @click="startGame(true)"
            class="gh-glass border-neon-blue/30 p-6 flex flex-col items-center hover:bg-neon-blue/10 transition-all group"
          >
            <span class="font-pixel text-xs text-neon-blue mb-2">RECUPERAR_ESTADO</span>
            <span class="font-display text-xl text-white font-black">SOL_OLEADA_{{ store.savedGame.wave }}</span>
          </button>
          
          <button
            @click="startGame(false)"
            class="gh-glass border-neon-cyan/30 p-6 flex flex-col items-center hover:bg-neon-cyan/10 transition-all group"
            :class="{ 'sm:col-span-2': !store.savedGame }"
          >
            <span class="font-pixel text-xs text-neon-cyan mb-2">INICIANDO_PROCESO</span>
            <span class="font-display text-xl text-white font-black">NUEVO_DESPLIEGUE</span>
          </button>
        </div>
      </div>
    </div>
    
    <!-- INTERFAZ DE JUEGO: COMMAND CONSOLE LAYOUT -->
    <template v-else-if="gameMode === 'playing'">
      <div 
        class="h-full w-full flex flex-col bg-retro-deep relative"
        :class="{ 'damage-shake': isDamaged }"
      >
        <!-- TOP HUD: SYSTEM TELEMETRY -->
        <header class="h-16 shrink-0 gh-glass border-b border-white/10 flex items-center justify-between px-6 z-40 bg-black/60 shadow-2xl">
          <!-- Stats Left -->
          <div class="flex items-center gap-10">
            <div class="flex flex-col">
              <div class="flex items-center gap-2 mb-1">
                <div class="size-2 bg-neon-cyan animate-pulse shadow-[0_0_5px_#00f2ff]"></div>
                <span class="font-pixel text-xs text-neon-cyan/80 uppercase tracking-widest">NÚCLEO_STB</span>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-32 h-2 bg-retro-deep border border-white/5 flex gap-0.5 p-0.5">
                  <div v-for="i in 10" :key="i" class="flex-1" :class="i <= (gameState.lives/10) ? 'bg-neon-pink shadow-[0_0_5px_#ff2d55]' : 'bg-white/5'"></div>
                </div>
                <span class="font-display text-xl font-black text-neon-pink leading-none">{{ gameState.lives }}%</span>
              </div>
            </div>

            <div class="flex flex-col">
              <span class="font-pixel text-xs text-white/30 uppercase tracking-tighter mb-0.5">DATOS_RED</span>
              <span class="font-display text-xl font-black text-neon-cyan">{{ gameState.gold }}</span>
            </div>
          </div>

          <!-- Wave Info Center -->
          <div class="flex flex-col items-center">
            <div class="flex items-baseline gap-2">
              <span class="font-pixel text-xs text-neon-yellow/60 uppercase">OLEADA</span>
              <span class="font-display text-3xl font-black text-white">#{{ gameState.wave }}</span>
            </div>
            <div v-if="gameState.waveActive" class="w-32 mt-1">
              <div class="h-1 bg-white/5 overflow-hidden">
                <div class="h-full bg-neon-cyan shadow-[0_0_5px_#00f2ff]" :style="{ width: `${waveProgressPercent}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Status Indicators Right -->
          <div class="flex items-center gap-6">
             <button v-if="!gameState.waveActive" @click="startWave" class="px-6 py-2 bg-neon-cyan text-black font-display text-xs font-black uppercase hover:scale-105 active:scale-95 transition-all shadow-[4px_4px_0_#000]">LANZAR_OLEADA</button>
             <div v-else class="flex flex-col items-end">
                <span class="font-pixel text-[10px] text-white/20 uppercase tracking-widest">MALWARE_ACTIVO</span>
                <span class="font-display text-sm font-black text-neon-yellow">{{ remainingEnemies }} UNIDADES</span>
              </div>
          </div>
        </header>

        <!-- BATTLEFIELD VIEWPORT: RESERVED SPACE -->
        <main 
          ref="gameContainer"
          class="flex-1 relative overflow-hidden flex items-center justify-center p-8 bg-black/20"
          @mousemove="handleParallax"
        >
          <!-- Global Effects -->
          <div class="absolute inset-0 pointer-events-none z-10 crt-ripple opacity-[0.02]"></div>
          
          <!-- Map with Respect Scaling -->
          <div 
            class="relative origin-center z-20"
            :style="{ transform: `scale(${finalScale}) rotateY(${parallax.y * 5}deg) rotateX(${parallax.x * -5}deg)` }"
          >
            <div class="gh-panel bg-retro-black border border-white/5 flex flex-col items-center p-6 w-fit shadow-[15px_15px_0_rgba(0,0,0,0.5)]">
              <!-- Integrated Ability Dock -->
              <div class="flex items-center gap-3 mb-6">
                <div v-for="key in ['emp', 'overclock', 'purge']" :key="key" class="group relative">
                  <!-- Tooltip: Now deploying downwards -->
                  <div class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-64 gh-glass p-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 text-center shadow-xl border-white/10 bg-black/90">
                    <p class="font-pixel text-xs text-white/90 leading-relaxed uppercase">
                      {{ key === 'emp' ? 'Sobrecarga de pulso: Paraliza a todas las unidades enemigas temporalmente.' : (key === 'overclock' ? 'Frecuencia Crítica: Duplica la cadencia de fuego de todos los nodos de defensa.' : 'Protocolo Purga: Ejecuta una descarga masiva que daña a todas las entidades activas.') }}
                    </p>
                  </div>
                  
                  <button 
                    @click="activateAbility(key)" 
                    :disabled="gameState.cooldowns[key] > 0 || gameState.isPaused || !inventory.hasItem('td_' + key)" 
                    class="relative border border-white/10 bg-black/60 flex flex-col items-center justify-center min-w-[110px] h-14 px-3 disabled:opacity-30 disabled:grayscale transition-all hover:border-neon-cyan/50 hover:bg-white/5 active:scale-95 group overflow-hidden"
                  >
                    <div class="absolute inset-0 origin-bottom transition-all" :class="`bg-neon-${key==='emp'?'cyan':(key==='overclock'?'yellow':'pink')}/10`" :style="{ height: `${(gameState.cooldowns[key] / (key==='emp'?600:(key==='overclock'?900:1200))) * 100}%` }"></div>
                    
                    <span v-if="!inventory.hasItem('td_' + key)" class="absolute inset-0 flex items-center justify-center bg-black/80 font-pixel text-[8px] text-neon-pink uppercase z-20">0 USOS - TIENDA</span>
                    
                    <span class="font-display text-[9px] font-black uppercase text-white/60 group-hover:text-white relative z-10 text-center leading-tight">
                      {{ key==='emp' ? 'PULSO EMP' : (key==='overclock' ? 'SOBRECARGA' : 'PURGA TOTAL') }}
                    </span>
                    <span class="font-pixel text-[8px] text-white/40 mt-0.5 relative z-10">{{ inventory.items['td_' + key] || 0 }} USOS</span>
                    <!-- Border glow on hover -->
                    <div class="absolute inset-0 border border-transparent group-hover:border-neon-cyan/30 pointer-events-none"></div>
                  </button>
                </div>
              </div>

              <div class="relative border border-white/10 bg-black/40">
                <div class="relative z-10" :style="{ width: (mapWidth * cellSize) + 'px', height: (mapHeight * cellSize) + 'px' }">
                  <!-- The Map Grid -->
                  <div v-for="y in mapHeight" :key="'row-' + y" class="flex">
                    <div
                      v-for="x in mapWidth"
                      :key="'cell-' + x + '-' + y"
                      class="hologram-cell"
                      :class="{ 'is-path': isPath(x - 1, y - 1), 'is-selected': selectedCell && selectedCell.x === x - 1 && selectedCell.y === y - 1, 'has-building': getTowerAt(x - 1, y - 1) }"
                      :style="{ width: cellSize + 'px', height: cellSize + 'px' }"
                      @click="handleMapClick" :data-x="x - 1" :data-y="y - 1"
                    >
                      <div v-if="gameState.path.length && x - 1 === gameState.path[0].x && y - 1 === gameState.path[0].y" class="absolute inset-0 bg-[#e02fe8]/10 border-2 border-[#e02fe8]/40 animate-pulse flex items-center justify-center z-10">
                        <div class="size-4 border-2 border-[#e02fe8] rotate-45 shadow-[0_0_10px_#e02fe8]"></div>
                      </div>
                      <div v-if="gameState.path.length && x - 1 === gameState.path[gameState.path.length-1].x && y - 1 === gameState.path[gameState.path.length-1].y" class="absolute inset-0 bg-neon-pink/20 border-2 border-neon-pink animate-pulse flex items-center justify-center z-10">
                        <div class="size-6 rounded-full bg-neon-pink/50 border-2 border-white/50 flex items-center justify-center shadow-[0_0_15px_#ff2d55]"><div class="size-2 bg-white rounded-full"></div></div>
                      </div>
                      <div v-if="getTowerAt(x - 1, y - 1)" class="tower-hologram" :style="{ '--color': getTowerAt(x - 1, y - 1).color }">
                         <div class="tower-base"></div>
                         <div class="tower-core animate-pulse"></div>
                      </div>
                    </div>
                  </div>
                  <div v-if="selectedTower && selectedCell && !isPath(selectedCell.x, selectedCell.y)" class="hologram-range" :style="selectedRangeStyle"></div>
                  <div v-if="gameState.path.length && !gameState.isPaused" class="path-indicator-pulse" :style="pathIndicatorStyle"><div class="chevron"></div></div>
                  <div v-for="enemy in enemies" :key="enemy.id" class="enemy-hologram" :class="[enemy.shapeClass, { 'is-slowed': enemy.slowTimer > 0, 'is-poisoned': enemy.poisonTicks > 0 }]" :style="{ left: `${enemy.pixelX}px`, top: `${enemy.pixelY}px`, width: `${enemy.sizePx}px`, height: `${enemy.sizePx}px`, '--color': enemy.poisonTicks > 0 ? '#22c55e' : (enemy.slowTimer > 0 ? '#00f2ff' : enemy.baseColor) }"><div class="enemy-body"></div><div class="enemy-hp-bar"><div class="enemy-hp-fill" :style="{ width: `${(enemy.hp / enemy.maxHp) * 100}%` }"></div></div></div>
                  <div v-for="projectile in projectiles" :key="projectile.id" class="energy-bolt" :style="{ left: `${projectile.x}px`, top: `${projectile.y}px`, width: `${projectile.size}px`, height: `${projectile.size}px`, '--color': projectile.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- BOTTOM DOCK: COMMAND & CONTROLS -->
        <footer class="h-24 shrink-0 gh-glass border-t border-white/10 flex items-center justify-between px-6 z-40 bg-black/80">
          <!-- Logs Left -->
          <div class="w-64 h-16 gh-glass p-2 bg-black/40 border-white/5 overflow-hidden hidden lg:block">
            <div class="h-full overflow-y-auto space-y-1 custom-scroll scrollbar-none opacity-40">
              <div v-for="log in gameLogs.slice(0, 3)" :key="log.id" class="font-pixel text-[10px] text-neon-cyan uppercase">> {{ log.text }}</div>
            </div>
          </div>

          <!-- Space Reserved for abilities (now moved) -->
          <div class="flex-1"></div>

          <!-- View/Time Controls Right -->
          <div class="flex items-center gap-6">
            <div class="flex flex-col gap-1.5">
               <div class="flex justify-between items-center px-1 mb-0.5">
                 <span class="font-pixel text-[10px] text-white/20 uppercase">ZOOM: {{ (finalScale * 100).toFixed(0) }}%</span>
               </div>
               <div class="flex gap-1">
                 <button @click="userZoom -= 0.1" class="size-8 border border-white/10 text-xs font-pixel text-white/40 hover:bg-white/10 transition-all flex items-center justify-center">-</button>
                 <button @click="userZoom = 1.0" class="px-3 h-8 border border-white/10 text-xs font-pixel text-white/20 hover:bg-white/10">RST</button>
                 <button @click="userZoom += 0.1" class="size-8 border border-white/10 text-xs font-pixel text-white/40 hover:bg-white/10 transition-all flex items-center justify-center">+</button>
               </div>
            </div>

            <div class="flex flex-col gap-1.5">
               <span class="font-pixel text-[10px] text-white/20 uppercase text-center">VELOCIDAD</span>
               <div class="flex gap-1">
                 <button @click="gameState.isPaused = !gameState.isPaused" class="px-4 py-1.5 border border-white/10 text-xs font-pixel transition-all" :class="gameState.isPaused ? 'bg-neon-pink text-black' : 'text-white/40 hover:bg-white/10'">PAU</button>
                 <button @click="gameState.speed = 1; gameState.isPaused = false" class="px-4 py-1.5 border border-white/10 text-xs font-pixel transition-all" :class="gameState.speed === 1 && !gameState.isPaused ? 'bg-neon-cyan text-black' : 'text-white/40 hover:bg-white/10'">x1</button>
                 <button @click="gameState.speed = 2; gameState.isPaused = false" class="px-4 py-1.5 border border-white/10 text-xs font-pixel transition-all" :class="gameState.speed === 2 && !gameState.isPaused ? 'bg-neon-yellow text-black' : 'text-white/40 hover:bg-white/10'">x2</button>
               </div>
            </div>
          </div>
        </footer>
      </div>
    </template>

    <!-- PANTALLA DE DERROTA: FALLO TOTAL -->
    <div v-else-if="gameMode === 'gameOver'" class="relative z-40 flex flex-col items-center justify-center flex-1 p-6">
       <div class="gh-glass max-w-md w-full p-10 text-center border-neon-pink/30 bg-black/80 backdrop-blur-2xl shadow-[0_0_50px_rgba(255,45,85,0.2)]">
          <div class="mb-8 flex flex-col items-center">
             <div class="size-16 border-2 border-neon-pink flex items-center justify-center mb-4 rotate-45 animate-pulse">
                <span class="font-display text-4xl text-neon-pink -rotate-45">!</span>
             </div>
             <span class="font-pixel text-neon-pink text-xs uppercase tracking-[0.3em] mb-2">FALLO_CRÍTICO_SISTEMA</span>
             <h2 class="font-display text-5xl font-black text-white leading-tight tracking-tighter">FALLO_TOTAL</h2>
          </div>

          <p class="font-sans text-xs font-bold text-white/40 uppercase mb-8 leading-relaxed">
             EL NÚCLEO DEL REACTOR HA SIDO TOTALMENTE COMPROMETIDO. EL MALWARE HA TOMADO EL CONTROL DE LOS SISTEMAS DE DEFENSA.
          </p>

          <div class="grid grid-cols-2 gap-4 mb-10">
             <div class="p-4 bg-white/5 border border-white/5">
                <p class="font-pixel text-[10px] text-white/30 uppercase mb-1">OLEADAS</p>
                <p class="font-display text-3xl font-black text-white">{{ gameState.wave - 1 }}</p>
             </div>
             <div class="p-4 bg-white/5 border border-white/5">
                <p class="font-pixel text-[10px] text-white/30 uppercase mb-1">DATOS_PERDIDOS</p>
                <p class="font-display text-3xl font-black text-neon-cyan">{{ (gameState.wave - 1) * 100 }}</p>
             </div>
          </div>

          <button 
            @click="resetToChoice"
            class="w-full py-5 bg-neon-pink text-black font-display text-xs font-black uppercase shadow-[6px_6px_0_#000] hover:translate-x-1 hover:translate-y-1 hover:shadow-none active:scale-95 transition-all"
          >
            REINICIAR_PROTOCOLO_DEFENSA
          </button>
       </div>
    </div>
    
    <!-- FLOATING TOOLTIP -->
    <Teleport to="body">
       <div v-if="selectedCell" class="fixed z-[100] w-[300px] sm:w-[340px] gh-glass bg-black/95 p-0 border-white/20 shadow-4xl overflow-hidden backdrop-blur-3xl scale-in-anim" :style="tooltipPosition">
          <header class="p-4 border-b border-white/10 flex justify-between items-center bg-white/5">
             <h3 class="font-display text-xs font-black text-white uppercase tracking-widest">COORD_UNIDAD_{{ selectedCell.x }}_{{ selectedCell.y }}</h3>
             <button @click="closeTooltip" class="text-white/40 hover:text-neon-pink">✕</button>
          </header>

          <div class="p-6 overflow-auto max-h-[450px] custom-scroll">
             <!-- MENU CONSTRUCCIÓN -->
             <div v-if="!selectedTower">
                <p class="font-pixel text-xs text-neon-cyan font-bold uppercase mb-4 tracking-[0.2em]">MÓDULOS_DISPONIBLES</p>
                <div class="space-y-3">
                   <div 
                     v-for="(type, key) in towerTypes" :key="key" 
                     @click="buildTower(key)"
                     class="group relative flex items-center p-3  border border-white/10 transition-all cursor-pointer bg-white/5 hover:border-neon-cyan/40 hover:bg-white/10"
                     :class="{ 'opacity-30 grayscale cursor-not-allowed': gameState.gold < type.cost }"
                   >
                      <div class="size-10  shrink-0 border border-white/10 relative overflow-hidden mr-4" :style="{ backgroundColor: type.color }">
                         <div class="absolute inset-0 bg-gradient-to-tr from-black/40 to-transparent"></div>
                      </div>
                      <div class="flex-1 min-w-0">
                         <div class="flex justify-between items-baseline mb-0.5">
                            <span class="font-display text-xs font-black uppercase text-white group-hover:text-neon-cyan">{{ type.name }}</span>
                            <span class="font-pixel text-xs text-neon-yellow">{{ type.cost }}C</span>
                         </div>
                         <p class="font-sans text-xs font-bold text-white/40 uppercase truncate">{{ type.desc }}</p>
                      </div>
                   </div>
                </div>
             </div>

             <!-- MENU UPGRADE -->
             <div v-else class="space-y-8">
                <div class="flex items-center gap-5">
                   <div class="size-16  shrink-0 border-2 border-white/20 shadow-2xl relative overflow-hidden" :style="{ backgroundColor: selectedTower.color }">
                      <div class="absolute inset-0 bg-gradient-to-tr from-black/60 to-transparent"></div>
                   </div>
                   <div>
                      <h4 class="font-display text-lg font-black text-white uppercase leading-tight">{{ selectedTower.name }}</h4>
                      <p class="font-pixel text-xs text-neon-cyan uppercase tracking-widest">LVL_{{ selectedTower.level }}</p>
                   </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-center">
                   <div class="p-3 bg-white/5 border border-white/5 ">
                      <p class="font-pixel text-xs opacity-30 uppercase mb-1">DAÑO</p>
                      <p class="font-display text-xs font-black text-neon-pink">{{ selectedTower.damage.toFixed(1) }} <span class="text-xs ml-1 opacity-60">→ {{(selectedTower.damage * 1.4).toFixed(1)}}</span></p>
                   </div>
                   <div class="p-3 bg-white/5 border border-white/5 ">
                      <p class="font-pixel text-xs opacity-30 uppercase mb-1">RANGO_SEÑAL</p>
                      <p class="font-display text-xs font-black text-neon-cyan">{{ selectedTower.range.toFixed(1) }} <span class="text-xs ml-1 opacity-60">→ {{(selectedTower.range + 0.1).toFixed(1)}}</span></p>
                   </div>
                </div>

                <div class="flex flex-col gap-3">
                   <button 
                     @click="upgradeTower"
                     :disabled="gameState.gold < upgradeCost"
                     class="w-full py-4  bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest shadow-xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-20 disabled:scale-100"
                   >
                      ACTUALIZAR ({{ upgradeCost }}C)
                   </button>
                   <button @click="confirmSellTower" class="w-full py-2 font-pixel text-xs text-neon-pink/60 hover:text-neon-pink uppercase tracking-widest transition-all">
                      [RECICLAR_COMPONENTES]
                   </button>
                </div>
             </div>
          </div>
       </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useTowerDefenseStore } from '../../stores/games/towerdefense'
import { useInventoryStore } from '../../stores/inventory'

const emit = defineEmits(['live-score'])
const store = useTowerDefenseStore()
const inventory = useInventoryStore()

const mapWidth = 12; const mapHeight = 10; const cellSize = 50
const viewportVersion = ref(0); const gameMode = ref('choice')
const isDamaged = ref(false)
const parallax = reactive({ x: 0, y: 0 })
const gameContainer = ref(null)
const containerWidth = ref(0)
const userZoom = ref(1.0)
const pathIndicatorProgress = ref(0)

const finalScale = computed(() => {
  let autoScale = 1.0
  if (containerWidth.value > 0 && gameContainer.value) {
    const cw = containerWidth.value - 100 // Minimal horizontal buffer for full space
    const ch = gameContainer.value.clientHeight - 100 // Minimal vertical buffer
    const mw = mapWidth * cellSize
    const mh = mapHeight * cellSize
    autoScale = Math.max(0.3, Math.min(1, cw / mw, ch / mh))
  }
  return Number((autoScale * userZoom.value).toFixed(2))
})

const handleParallax = (e) => {
  const x = (e.clientX / window.innerWidth) - 0.5
  const y = (e.clientY / window.innerHeight) - 0.5
  parallax.x = x
  parallax.y = y
}

const gameState = reactive({ 
  lives: 100, gold: 150, wave: 1, waveActive: false, gameOver: false, path: [],
  speed: 1, isPaused: false,
  cooldowns: { emp: 0, overclock: 0, purge: 0 },
  activeEffects: { empTimer: 0, overclockTimer: 0 }
})

const gameLogs = ref([
  { id: 0, time: new Date().toLocaleTimeString().slice(0, 5), text: 'NÚCLEO_INICIALIZADO' },
  { id: 1, time: new Date().toLocaleTimeString().slice(0, 5), text: 'MODO_DEFENSA_STANDBY' }
])

const addLog = (text) => {
  gameLogs.value.unshift({ id: Date.now(), time: new Date().toLocaleTimeString().slice(0, 5), text });
  if (gameLogs.value.length > 20) gameLogs.value.pop();
}

const activateAbility = (key) => {
  if (gameState.cooldowns[key] > 0 || gameState.isPaused || gameState.gameOver) return;
  const itemId = 'td_' + key;
  if (!inventory.hasItem(itemId)) {
    addLog(`SIN_CARGAS_DE_${key.toUpperCase()}`);
    return;
  }
  
  inventory.useItem(itemId);

  if (key === 'emp') {
    gameState.activeEffects.empTimer = 100;
    gameState.cooldowns.emp = 600;
    addLog('HABILIDAD_EMP_LANZADA');
  } else if (key === 'overclock') {
    gameState.activeEffects.overclockTimer = 160;
    gameState.cooldowns.overclock = 900;
    addLog('SISTEMAS_SOBRECARGADOS');
  } else if (key === 'purge') {
    enemies.value.forEach(e => e.hp -= 200 * Math.pow(1.2, gameState.wave - 1));
    gameState.cooldowns.purge = 1200;
    addLog('PURGA_LÁSER_EJECUTADA');
  }
}

const generateRandomPath = (width, height) => {
  const newPath = [];
  let currentX = 0;
  let currentY = Math.floor(Math.random() * (height - 4)) + 2;
  newPath.push({ x: currentX, y: currentY });
  currentX++;
  newPath.push({ x: currentX, y: currentY });

  while (currentX < width - 1) {
    if (Math.random() < 0.4 || currentX === 0) {
      currentX++;
      newPath.push({ x: currentX, y: currentY });
    } else {
      const verticalSteps = Math.floor(Math.random() * 3) + 1;
      const direction = Math.random() < 0.5 ? 1 : -1;
      
      for (let i = 0; i < verticalSteps; i++) {
        const nextY = currentY + direction;
        if (nextY >= 1 && nextY < height - 1) {
          currentY = nextY;
          newPath.push({ x: currentX, y: currentY });
        } else {
          break;
        }
      }
      currentX++;
      newPath.push({ x: currentX, y: currentY });
    }
  }
  return newPath;
}

const isPath = (x, y) => gameState.path.some((p) => p.x === x && p.y === y)
const towers = ref([]); const enemies = ref([]); const projectiles = ref([]); const selectedCell = ref(null); const clickPosition = ref(null)

const towerTypes = {
  basic: { name: 'Centinela', cost: 30, range: 2.5, damage: 15, cooldownMax: 20, color: '#007aff', desc: 'Defensa Estándar', effect: 'none' },
  rapid: { name: 'Pulso-X', cost: 60, range: 2, damage: 4, cooldownMax: 4, color: '#f97316', desc: 'Protocolo de Ataque Rápido', effect: 'fast' },
  sniper: { name: 'Francotirador', cost: 100, range: 5, damage: 60, cooldownMax: 60, color: '#ff2d55', desc: 'Eliminación a Gran Distancia', effect: 'none' },
  heavy: { name: 'Cañón Nova', cost: 120, range: 2.5, damage: 40, cooldownMax: 45, color: '#a855f7', desc: 'Disruptor de Área', effect: 'splash' },
  frost: { name: 'Criocentral', cost: 70, range: 2.8, damage: 8, cooldownMax: 28, color: '#00f2ff', desc: 'Bitrate Lento (-20%)', effect: 'slow' },
  poison: { name: 'Bio-Nodo', cost: 90, range: 3, damage: 10, cooldownMax: 40, color: '#22c55e', desc: 'Corrupción (Daño Continuo)', effect: 'poison' }
}

const enemyArchetypes = {
  circle: { shapeClass: 's-circ', hpMultiplier: 1, speedMultiplier: 1, sizeMultiplier: 0.45, rewardMultiplier: 1, damageReduction: 0, baseColor: '#ff2d55' },
  triangle: { shapeClass: 's-tria', hpMultiplier: 0.55, speedMultiplier: 2.1, sizeMultiplier: 0.35, rewardMultiplier: 1.6, damageReduction: 0, baseColor: '#f97316' },
  diamond: { shapeClass: 's-diam', hpMultiplier: 3.2, speedMultiplier: 0.5, sizeMultiplier: 0.72, rewardMultiplier: 2.8, damageReduction: 0.08, baseColor: '#8b5cf6' },
  square: { shapeClass: 's-squa', hpMultiplier: 1.9, speedMultiplier: 0.85, sizeMultiplier: 0.56, rewardMultiplier: 2.0, damageReduction: 0.36, baseColor: '#94a3b8' },
  boss: { shapeClass: 's-boss', hpMultiplier: 15.0, speedMultiplier: 0.4, sizeMultiplier: 0.9, rewardMultiplier: 25.0, damageReduction: 0.6, baseColor: '#e02fe8' }
}

const towerEffectLabel = (effect) => {
  if (effect === 'slow') return 'Ralentizado'
  if (effect === 'poison') return 'Corrupto'
  if (effect === 'splash') return 'Disruptivo'
  if (effect === 'fast') return 'Frecuente'
  return 'Base'
}

const getTowerAt = (x, y) => towers.value.find((t) => t.x === x && t.y === y)
const selectedTower = computed(() => selectedCell.value ? getTowerAt(selectedCell.value.x, selectedCell.value.y) : null)
const upgradeCost = computed(() => selectedTower.value ? Math.floor(selectedTower.value.baseCost * Math.pow(1.5, selectedTower.value.level)) : 0)
const selectedTowerSellValue = computed(() => selectedTower.value ? Math.floor(selectedTower.value.totalSpent * 0.5) : 0)

const selectedRangeStyle = computed(() => {
  if (!selectedTower.value) return {}
  const d = selectedTower.value.range * 2 * cellSize
  return { width: `${d}px`, height: `${d}px`, left: `${(selectedTower.value.x + 0.5) * cellSize - (d / 2)}px`, top: `${(selectedTower.value.y + 0.5) * cellSize - (d / 2)}px` }
})

const remainingEnemies = computed(() => enemies.value.length + enemiesToSpawn.value)
const waveProgressPercent = computed(() => {
  if (!gameState.waveActive || totalWaveEnemies.value <= 0) return 0
  return Math.max(0, Math.min(((totalWaveEnemies.value - remainingEnemies.value) / totalWaveEnemies.value) * 100, 100))
})

const pathIndicatorStyle = computed(() => {
  if (!gameState.path.length) return { display: 'none' }
  const idx = Math.floor(pathIndicatorProgress.value)
  if (idx + 1 >= gameState.path.length) return { display: 'none' }
  const f = pathIndicatorProgress.value - idx
  const cp = gameState.path[idx], np = gameState.path[idx+1]
  const px = (cp.x + (np.x-cp.x)*f)*cellSize + cellSize/2
  const py = (cp.y + (np.y-cp.y)*f)*cellSize + cellSize/2
  const angle = Math.atan2(np.y - cp.y, np.x - cp.x) * (180 / Math.PI)
  return { left: `${px}px`, top: `${py}px`, transform: `translate(-50%, -50%) rotate(${angle}deg)` }
})

const enemiesToSpawn = ref(0); const totalWaveEnemies = ref(0)
let towerIdCounter = 0; let enemyIdCounter = 0; let projectileIdCounter = 0; let spawnInterval = null; let gameLoopId = null; let resizeListener = null

const handleMapClick = (e) => {
  e.stopPropagation(); const cell = e.target.closest('.hologram-cell'); if (!cell) return
  const x = parseInt(cell.dataset.x, 10), y = parseInt(cell.dataset.y, 10)
  if (isPath(x, y) && !getTowerAt(x, y)) { selectedCell.value = null; clickPosition.value = null; return }
  const rect = cell.getBoundingClientRect(); clickPosition.value = { x: rect.left, y: rect.top }; selectedCell.value = { x, y }
}

const closeTooltip = () => { selectedCell.value = null; clickPosition.value = null }

const tooltipPosition = computed(() => {
  viewportVersion.value
  if (!selectedCell.value || !clickPosition.value) return {}
  const { x, y } = clickPosition.value; const tw = 340, th = 300, pad = 12, vW = window.innerWidth, vH = window.innerHeight
  let left = x + cellSize / 2 + pad + tw < vW ? x + cellSize / 2 + pad : (x + cellSize / 2 - pad - tw > 0 ? x + cellSize / 2 - pad - tw : Math.max(pad, Math.min(vW - tw - pad, vW / 2 - tw / 2)))
  let top = Math.max(pad, Math.min(vH - th - pad, y + cellSize / 2 - th / 2))
  return { left: `${left}px`, top: `${top}px`, position: 'fixed' }
})

const buildTower = (key) => {
  const t = towerTypes[key]; if (!t || !selectedCell.value) return
  const { x, y } = selectedCell.value; if (isPath(x, y) || getTowerAt(x, y) || gameState.gold < t.cost) return
  gameState.gold -= t.cost; towers.value.push({ ...t, id: towerIdCounter++, x, y, baseCost: t.cost, totalSpent: t.cost, level: 1, cooldown: 0 });
  addLog(`NODO_${t.name.toUpperCase()}_DESPLEGADO`);
}

const upgradeTower = () => { if (gameState.gold >= upgradeCost.value && selectedTower.value) { gameState.gold -= upgradeCost.value; selectedTower.value.totalSpent += upgradeCost.value; selectedTower.value.level++; selectedTower.value.damage *= 1.4; selectedTower.value.range += 0.1; addLog(`NODO_NIVEL_${selectedTower.value.level}_UPGRADE`); } }
const confirmSellTower = () => { if (selectedTower.value && window.confirm(`Reciclar por ${selectedTowerSellValue.value}C?`)) { addLog(`NODO_RECICLADO_+${selectedTowerSellValue.value}C`); gameState.gold += selectedTowerSellValue.value; towers.value = towers.value.filter(t => t.id !== selectedTower.value.id); selectedCell.value = null } }

const startWave = () => {
  if (gameState.waveActive || gameState.gameOver) return
  gameState.waveActive = true; 
  addLog(`OLEADA_${gameState.wave}_INICIADA`);
  if (gameState.wave % 5 === 0) addLog('ADVERTENCIA: ENTIDAD_TITÁN_DETECTADA');
  const stage = Math.floor((gameState.wave - 1) / 5)
  const isBossWave = gameState.wave % 5 === 0;
  enemiesToSpawn.value = isBossWave ? 1 : 6 + Math.floor(gameState.wave * 1.15) + (stage * 3); 
  totalWaveEnemies.value = enemiesToSpawn.value
  const baseHp = Math.round(Math.round(28 * Math.pow(1.12, gameState.wave - 1)) * Math.pow(1.6, stage))
  const baseSpeed = 0.055 + (gameState.wave * 0.0022), baseRew = Math.max(1, Math.round(Math.max(2, Math.round(4 * Math.pow(1.06, gameState.wave - 1))) * Math.pow(1.35, stage)))
  if (spawnInterval) clearInterval(spawnInterval)
  spawnInterval = setInterval(() => {
    if (gameState.isPaused) return;
    if (enemiesToSpawn.value > 0) {
      const arch = pickEnemyArchetype(gameState.wave, enemiesToSpawn.value === totalWaveEnemies.value), hp = Math.round(baseHp * arch.hpMultiplier), sz = Math.max(14, Math.round(cellSize * arch.sizeMultiplier))
      enemies.value.push({
        id: enemyIdCounter++, progress: 0, hp, maxHp: hp, speed: Math.min(baseSpeed * arch.speedMultiplier, 0.22), slowTimer: 0, poisonTicks: 0,
        reward: Math.max(1, Math.round(baseRew * arch.rewardMultiplier)), pixelX: (gameState.path[0].x * cellSize) + (cellSize-sz)/2, pixelY: (gameState.path[0].y * cellSize) + (cellSize-sz)/2,
        sizePx: sz, shapeClass: arch.shapeClass, damageReduction: arch.damageReduction, baseColor: arch.baseColor
      }); enemiesToSpawn.value--
    } else { clearInterval(spawnInterval); spawnInterval = null }
  }, 1000)
}

const pickEnemyArchetype = (w, isFirst) => {
  if (w % 5 === 0 && isFirst) return enemyArchetypes.boss;
  const r = Math.random(); if (w < 3) return r < 0.75 ? enemyArchetypes.circle : enemyArchetypes.triangle
  if (w < 6) return r < 0.52 ? enemyArchetypes.circle : (r < 0.78 ? enemyArchetypes.triangle : (r < 0.92 ? enemyArchetypes.square : enemyArchetypes.diamond))
  return r < 0.4 ? enemyArchetypes.circle : (r < 0.65 ? enemyArchetypes.triangle : (r < 0.84 ? enemyArchetypes.square : enemyArchetypes.diamond))
}

const gameTick = () => {
  if (gameState.gameOver || gameState.isPaused) return

  for (let s = 0; s < gameState.speed; s++) {
    // Update Cooldowns and Effects
    if (gameState.cooldowns.emp > 0) gameState.cooldowns.emp--;
    if (gameState.cooldowns.overclock > 0) gameState.cooldowns.overclock--;
    if (gameState.cooldowns.purge > 0) gameState.cooldowns.purge--;
    
    let isEmpActive = false;
    if (gameState.activeEffects.empTimer > 0) {
      gameState.activeEffects.empTimer--;
      isEmpActive = true;
    }
    
    let isOverclockActive = false;
    if (gameState.activeEffects.overclockTimer > 0) {
      gameState.activeEffects.overclockTimer--;
      isOverclockActive = true;
    }

    for (let i = enemies.value.length - 1; i >= 0; i--) {
      const e = enemies.value[i]; if (e.poisonTicks > 0) { e.hp -= 0.15; e.poisonTicks-- }
      
      if (!isEmpActive) {
        const spd = e.slowTimer > 0 ? e.speed * 0.6 : e.speed; if (e.slowTimer > 0) e.slowTimer--
        e.progress += spd; const idx = Math.floor(e.progress)
        if (idx + 1 >= gameState.path.length) { 
          enemies.value.splice(i,1); 
          gameState.lives -= 5; 
          isDamaged.value = true;
          setTimeout(() => isDamaged.value = false, 400);
          addLog('FALLO_CRÍTICO: BRECHA_DETECTADA');
          if (gameState.lives <= 0) {
            gameState.lives = 0;
            handleGameOver(); 
          }
          continue 
        }
        const f = e.progress - idx, cp = gameState.path[idx], np = gameState.path[idx+1], o = (cellSize - e.sizePx)/2
        e.pixelX = (cp.x + (np.x-cp.x)*f)*cellSize + o; e.pixelY = (cp.y + (np.y-cp.y)*f)*cellSize + o
      }
      
      if (e.hp <= 0) { gameState.gold += e.reward; enemies.value.splice(i,1) }
    }
    
    towers.value.forEach(tw => {
      if (tw.cooldown > 0) { 
        tw.cooldown -= isOverclockActive ? 2 : 1; 
        if (tw.cooldown < 0) tw.cooldown = 0;
        return; 
      }
      const tc = { x:(tw.x+0.5)*cellSize, y:(tw.y+0.5)*cellSize }
      const tgt = enemies.value.find(e => Math.hypot(e.pixelX+e.sizePx/2 - tc.x, e.pixelY+e.sizePx/2 - tc.y)/cellSize <= tw.range)
      if (!tgt) return
      projectiles.value.push({ id: projectileIdCounter++, x:tc.x, y:tc.y, targetId: tgt.id, damage: tw.damage, effect: tw.effect, color: tw.color, speed: tw.effect==='fast'?17:8, size: tw.effect==='fast'?6:10 })
      tw.cooldown = tw.cooldownMax
    })
    
    for (let i = projectiles.value.length - 1; i >= 0; i--) {
      const p = projectiles.value[i], tgt = enemies.value.find(e => e.id===p.targetId)
      if (!tgt) { projectiles.value.splice(i,1); continue }
      const tc = { x:tgt.pixelX+tgt.sizePx/2, y:tgt.pixelY+tgt.sizePx/2 }, dx = tc.x-p.x, dy = tc.y-p.y, d = Math.hypot(dx,dy)
      if (d <= Math.max(p.speed,1)) {
        tgt.hp -= p.damage * (1 - (tgt.damageReduction || 0))
        if (p.effect==='slow') tgt.slowTimer = 60; if (p.effect==='poison') tgt.poisonTicks = 120
        if (p.effect==='splash') enemies.value.forEach(e => { if (e.id!==tgt.id && Math.hypot(e.pixelX+e.sizePx/2-tc.x, e.pixelY+e.sizePx/2-tc.y)<70) e.hp -= p.damage*0.5 })
        projectiles.value.splice(i,1)
      } else { p.x += (dx/d)*p.speed; p.y += (dy/d)*p.speed }
    }
    if (gameState.waveActive && enemiesToSpawn.value === 0 && enemies.value.length === 0) { gameState.waveActive = false; gameState.wave++; store.saveProgress(gameState.wave) }
    
    // Update path indicator
    if (gameState.path.length) {
      pathIndicatorProgress.value += 0.08 * gameState.speed
      if (pathIndicatorProgress.value >= gameState.path.length - 1) pathIndicatorProgress.value = 0
    }
  }
}

async function handleGameOver() { 
  gameState.gameOver = true; 
  gameState.waveActive = false; 
  gameMode.value = 'gameOver';
  emit('live-score', gameState.wave-1); 
  try { 
    await store.saveProgress(gameState.wave); 
    await store.completeSession(gameState.wave,0); 
    await store.resetGame() 
  } catch(e){} 
}

const resetToChoice = () => {
  if (gameLoopId) {
    clearInterval(gameLoopId);
    gameLoopId = null;
  }
  
  // Reset local state
  Object.assign(gameState, {
    lives: 100, gold: 150, wave: 1, waveActive: false, gameOver: false, path: [],
    speed: 1, isPaused: false,
    cooldowns: { emp: 0, overclock: 0, purge: 0 },
    activeEffects: { empTimer: 0, overclockTimer: 0 }
  });
  
  enemies.value = [];
  towers.value = [];
  projectiles.value = [];
  selectedCell.value = null;
  
  gameMode.value = 'choice';
}
async function startGame(cont) { 
  await store.initializeTowerDefense(cont); 
  Object.assign(gameState, store.gameState); 
  
  // Force 100% integrity on new games
  if (!cont) gameState.lives = 100;
  
  towers.value = store.gameState.towers || []; 
  
  if (!cont || !store.gameState.path || store.gameState.path.length === 0) {
    gameState.path = generateRandomPath(mapWidth, mapHeight);
    store.updateGameState({ path: gameState.path });
  }

  gameMode.value = 'playing'; 
  if (!gameLoopId) gameLoopId = setInterval(gameTick, 30); 
}
function resetGameLocal() { 
  Object.assign(gameState, { 
    lives:100, gold:150, wave:1, waveActive:false, gameOver:false, path:[],
    speed: 1, isPaused: false, cooldowns: { emp: 0, overclock: 0, purge: 0 }, activeEffects: { empTimer: 0, overclockTimer: 0 }
  }); 
  towers.value=[]; enemies.value=[]; projectiles.value=[]; gameMode.value='choice' 
}

onMounted(() => { 
  resizeListener = () => {
    viewportVersion.value++
    if (gameContainer.value) containerWidth.value = gameContainer.value.clientWidth
  }
  window.addEventListener('resize', resizeListener)
  setTimeout(() => { if (gameContainer.value) containerWidth.value = gameContainer.value.clientWidth }, 100)
})
onUnmounted(() => { clearInterval(gameLoopId); clearInterval(spawnInterval); window.removeEventListener('resize', resizeListener) })
</script>

<style scoped>
.gh-glass { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
.gh-panel { position: relative; overflow: hidden; }
.gh-panel::after { content: ''; position: absolute; inset: 0; background: linear-gradient(45deg, transparent 48%, rgba(255,255,255,0.05) 50%, transparent 52%); background-size: 200% 200%; animation: shine 10s linear infinite; pointer-events: none; }
@keyframes shine { 0% { background-position: -100% -100%; } 100% { background-position: 100% 100%; } }

.hologram-cell { border: 1px solid rgba(255, 255, 255, 0.03); cursor: pointer; transition: all 0.2s ease-out; position: relative; }
.hologram-cell:hover { background: rgba(0, 242, 255, 0.15); box-shadow: inset 0 0 15px rgba(0, 242, 255, 0.3); border-color: rgba(0, 242, 255, 0.5); z-index: 20; }
.hologram-cell.is-path { 
  background: rgba(224, 47, 232, 0.05);
  border: 1px solid rgba(224, 47, 232, 0.15); 
  z-index: 5;
  position: relative;
}

.path-indicator-pulse {
  position: absolute;
  width: 24px;
  height: 24px;
  z-index: 15;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.path-indicator-pulse .chevron {
  width: 12px;
  height: 12px;
  border-right: 3px solid #00f2ff;
  border-bottom: 3px solid #00f2ff;
  transform: rotate(-45deg);
  filter: drop-shadow(0 0 5px #00f2ff);
  animation: chevronGlow 1s ease-in-out infinite;
}

@keyframes chevronGlow {
  0%, 100% { opacity: 0.4; filter: drop-shadow(0 0 2px #00f2ff); }
  50% { opacity: 1; filter: drop-shadow(0 0 10px #00f2ff); }
}

.hologram-cell.is-selected { border: 2px solid #00f2ff; background: rgba(0, 242, 255, 0.15); box-shadow: inset 0 0 15px rgba(0, 242, 255, 0.4); z-index: 10; }


.tower-hologram { position: absolute; inset: 15%; display: flex; flex-direction: column; align-items: center; justify-content: center; transform-style: preserve-3d; }
.tower-base { width: 80%; height: 4px; background: var(--color); opacity: 0.5; box-shadow: 0 0 10px var(--color); border-radius: 50%; }
.tower-core { width: 50%; height: 80%; background: var(--color); clip-path: polygon(50% 0%, 100% 100%, 0% 100%); box-shadow: 0 0 15px var(--color); transform: translateY(-2px); }

.enemy-hologram { position: absolute; transform: translate(-50%, -50%); display: flex; align-items:center; justify-content: center; pointer-events: none; z-index: 20; transition: transform 0.1s linear; }
.enemy-body { width: 100%; height: 100%; background: var(--color); opacity: 0.8; box-shadow: 0 0 10px var(--color); }
.s-circ .enemy-body { border-radius: 99px; }
.s-tria .enemy-body { clip-path: polygon(50% 0%, 0% 100%, 100% 100%); }
.s-diam .enemy-body { clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
.s-squa .enemy-body { border-radius: 2px; }
.s-boss .enemy-body { clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%); border: 2px solid white; box-shadow: 0 0 20px var(--color); }

.enemy-hp-bar { position: absolute; top: -8px; left: 0; width: 100%; height: 2px; background: rgba(0,0,0,0.5); }
.enemy-hp-fill { height: 100%; background: #22c55e; transition: width 0.1s; }

.energy-bolt { position: absolute; border-radius: 50%; background: var(--color); transform: translate(-50%, -50%); box-shadow: 0 0 8px var(--color); }
.hologram-range { position: absolute; border: 1px dashed rgba(0, 242, 255, 0.4); border-radius: 50%; background: rgba(0, 242, 255, 0.03); pointer-events: none; }

.bg-danger-stripes {
  background-image: repeating-linear-gradient(
    45deg,
    #ff2d55 0,
    #ff2d55 10px,
    transparent 10px,
    transparent 20px
  );
}

.bg-scan-line {
  background: linear-gradient(
    to bottom,
    transparent 0%,
    rgba(0, 242, 255, 0.1) 50%,
    transparent 100%
  );
  background-size: 100% 4px;
}

@keyframes diag-scanning {
  0% { transform: translateY(-100%); }
  100% { transform: translateY(100%); }
}

.animate-in { animation: animate-in 0.3s ease-out; }
@keyframes animate-in { from { opacity: 0; } to { opacity: 1; } }

.segmented-bar div {
  clip-path: polygon(10% 0%, 100% 0%, 90% 100%, 0% 100%);
}

.crt-ripple {
  background: linear-gradient(
    rgba(18, 16, 16, 0) 50%,
    rgba(0, 0, 0, 0.15) 50%
  );
  background-size: 100% 4px;
  animation: scan-scroll 10s linear infinite;
}

@keyframes scan-scroll {
  from { background-position: 0 0; }
  to { background-position: 0 100%; }
}

.damage-shake {
  animation: glitch-shake 0.2s linear infinite;
}

@keyframes glitch-shake {
  0% { transform: translate(0); filter: hue-rotate(0deg); }
  25% { transform: translate(4px, -4px); filter: hue-rotate(90deg) brightness(1.5); }
  50% { transform: translate(-4px, 4px); filter: hue-rotate(180deg); }
  75% { transform: translate(4px, 4px); filter: hue-rotate(270deg) brightness(1.2); }
  100% { transform: translate(0); filter: hue-rotate(360deg); }
}

.animate-spin-slow {
  animation: spin 8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-glitch-text {
  animation: text-flicker 2s linear infinite;
}

@keyframes text-flicker {
  0% { opacity: 1; }
  2% { opacity: 0.4; transform: skewX(10deg); }
  4% { opacity: 1; transform: skewX(0); }
  90% { opacity: 1; }
  91% { opacity: 0.4; }
  92% { opacity: 1; }
}

.custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0,242,255,0.2); }
.scrollbar-none::-webkit-scrollbar { display: none; }
</style>
