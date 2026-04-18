<template>
  <section class="h-full flex flex-col bg-retro-deep text-retro-white font-sans relative overflow-hidden">
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>

    <!-- Background Cinematic Glows -->
    <div class="absolute inset-0 bg-gradient-to-tr from-neon-pink/5 via-transparent to-neon-cyan/5 pointer-events-none"></div>

    <!-- HEADER: Sleek HUD -->
    <header class="relative z-30 p-4 sm:p-6 flex flex-col md:flex-row items-center justify-between gap-4 border-b border-white/5 bg-black/40 backdrop-blur-md">
      <div class="flex items-center gap-4">
        <div class="size-10  bg-neon-pink/10 flex items-center justify-center text-neon-pink border border-neon-pink/20">🛡️</div>
        <div>
          <h2 class="font-display text-xl font-black text-white uppercase tracking-tighter">HÉROE_CRUZADO_v1</h2>
          <div class="flex items-center gap-2">
             <div class="size-1.5 rounded-full bg-neon-cyan animate-pulse"></div>
             <p class="font-pixel text-xs uppercase opacity-40 tracking-[0.2em]">MISIÓN_SYNC: SENDA_DEL_HÉROE</p>
          </div>
        </div>
      </div>

      <div class="flex gap-4">
        <!-- MAP BAR: Elegant Glass strip (Moved to Header to save space) -->
        <div class="gh-glass px-4 py-2 border-white/5 bg-white/5 flex items-center gap-4 overflow-x-auto custom-scroll no-scrollbar max-w-[400px]">
          <div class="shrink-0 font-pixel text-xs text-white/40 uppercase tracking-widest hidden sm:block">PROGRESO:</div>
          <div class="flex gap-1.5">
            <div
              v-for="(room, idx) in run.map"
              :key="room.id"
              class="relative size-6 shrink-0 flex items-center justify-center rounded border transition-all duration-500"
              :class="idx === currentRoomIndex
                ? 'border-neon-cyan bg-neon-cyan/20 shadow-[0_0_10px_rgba(0,242,255,0.2)] z-10'
                : (idx < currentRoomIndex ? 'border-white/5 opacity-20' : 'border-white/10 opacity-40')"
            >
              <span class="font-pixel text-xs font-bold" :class="idx === currentRoomIndex ? 'text-neon-cyan' : ''">{{ idx + 1 }}</span>
            </div>
          </div>
        </div>
        
        <div class="flex gap-2">
            <button class="size-10 gh-glass flex justify-center items-center font-pixel text-xs hover:bg-white/10" @click="startNewRun" title="Nueva Partida">⟳</button>
            <button class="size-10 gh-glass flex justify-center items-center font-pixel text-xs hover:bg-white/10" @click="loadRun" title="Cargar">📁</button>
            <button class="size-10 gh-glass flex justify-center items-center font-pixel text-xs bg-neon-cyan text-black border-none hover:scale-105 active:scale-95" @click="saveRun" title="Guardar">💾</button>
        </div>
      </div>
    </header>

    <div v-if="error" class="relative z-40 m-4 p-3 bg-neon-pink/10 border border-neon-pink  text-neon-pink font-pixel text-xs uppercase text-center animate-pulse">
      >> ERROR_SISTEMA: {{ error }}
    </div>

    <!-- MAIN GAME LAYOUT -->
    <div class="flex-1 flex flex-col lg:flex-row min-h-0 relative z-20 gap-4 p-4 sm:p-6 overflow-auto custom-scroll">
      
      <!-- BATTLE & ACTION ÁREA -->
      <div class="flex-1 flex flex-col gap-6 lg:max-w-4xl mx-auto w-full">
        
        <!-- STAGE ACTION: Cinematic Combat -->
        <div class="gh-glass border-white/5 bg-black/40 p-6 sm:p-10 flex flex-col relative overflow-hidden min-h-[400px] shadow-2xl">
           
           <div class="flex justify-between items-center mb-8 relative z-30">
              <div class="flex items-center gap-3">
                 <span v-if="phase === 'combat'" class="px-2 py-0.5 rounded bg-neon-pink text-white font-pixel text-xs font-bold animate-pulse">! CONTACTO</span>
                 <p class="font-pixel text-xs text-white/40 uppercase tracking-widest">ESTADO: {{ phase.toUpperCase() }}</p>
              </div>
              <div v-if="phase === 'combat'" class="font-pixel text-xs text-neon-cyan uppercase tracking-[0.3em]">
                 PRIORIDAD_TURNO: {{ turn === 'hero' ? 'USUARIO_SISTEMA' : 'HOSTIL' }}
              </div>
           </div>

           <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-12 items-center relative z-20">
              <!-- HERO UNIT -->
              <div class="flex flex-col items-center">
                 <div class="relative group">
                    <div 
                      class="size-44 sm:size-56 rounded-full border-2 border-white/5 flex items-center justify-center relative transition-all duration-700 bg-[radial-gradient(circle,rgba(0,122,255,0.05)_0%,transparent_70%)]"
                      :class="{ 'scale-105 border-neon-cyan/40 shadow-[0_0_50px_rgba(0,122,255,0.15)] bg-[radial-gradient(circle,rgba(0,122,255,0.15)_0%,transparent_70%)]': turn === 'hero' && phase === 'combat' }"
                    >
                       <span class="text-7xl sm:text-8xl select-none filter drop-shadow-[0_0_20px_rgba(0,122,255,0.3)]">🛡️</span>
                       <div class="absolute -bottom-2 bg-white text-black font-display text-xs font-black px-6 py-1.5 uppercase rounded-full shadow-2xl">CRUZADO</div>
                    </div>
                 </div>
                 
                 <div class="w-full mt-8 space-y-4">
                    <div>
                       <div class="flex justify-between font-pixel text-xs mb-2 opacity-60">
                          <span>MONITOR_SALUD</span>
                          <span class="text-white">{{ hero.hp }}/{{ hero.maxHp }}</span>
                       </div>
                       <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden p-px border border-white/5">
                          <div class="h-full bg-neon-cyan shadow-[0_0_10px_#00f2ff] transition-all duration-300" :style="{ width: (hero.hp/hero.maxHp*100) + '%' }"></div>
                       </div>
                    </div>
                    <div>
                       <div class="flex justify-between font-pixel text-xs mb-2 opacity-60">
                          <span>ESTRÉS_SISTEMA</span>
                          <span class="text-white">{{ hero.stress }}%</span>
                       </div>
                       <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden p-px border border-white/5">
                          <div class="h-full bg-neon-pink shadow-[0_0_10px_#ff2d55] transition-all duration-300" :style="{ width: (hero.stress/hero.maxStress*100) + '%' }"></div>
                       </div>
                    </div>
                 </div>
              </div>

              <!-- HOSTILE UNIT -->
              <div class="flex flex-col items-center">
                 <div class="relative">
                    <div 
                      class="size-44 sm:size-56 rounded-full border-2 border-white/5 flex items-center justify-center relative transition-all duration-700 bg-[radial-gradient(circle,rgba(255,45,85,0.05)_0%,transparent_70%)]"
                      :class="{ 'scale-105 border-neon-pink/40 shadow-[0_0_50px_rgba(255,45,85,0.15)] bg-[radial-gradient(circle,rgba(255,45,85,0.15)_0%,transparent_70%)]': turn === 'enemy' && phase === 'combat' }"
                    >
                       <span class="text-7xl sm:text-8xl select-none filter drop-shadow-[0_0_20px_rgba(255,45,85,0.3)]">😈</span>
                       <div class="absolute -bottom-2 bg-neon-pink text-white font-display text-xs font-black px-6 py-1.5 uppercase rounded-full shadow-2xl">{{ enemy.name }}</div>
                    </div>
                 </div>

                 <div class="w-full mt-8">
                    <div class="flex justify-between font-pixel text-xs mb-2 opacity-60">
                       <span>THREAT_LOAD</span>
                       <span class="text-white">{{ enemy.hp }}/{{ enemy.maxHp }}</span>
                    </div>
                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden p-px border border-white/5">
                       <div class="h-full bg-neon-pink transition-all duration-300" :style="{ width: (enemy.hp/enemy.maxHp*100) + '%' }"></div>
                    </div>
                    <p class="font-pixel text-xs opacity-20 mt-4 text-center uppercase tracking-[0.2em]">OBJ_LVL: {{ enemy.level }} || SECTOR: {{ (currentRoomIndex+1).toString().padStart(2,'0') }}</p>
                 </div>
              </div>
           </div>
        </div>

        <!-- SKILLS AREA (Moved to bottom of combat area for better flow) -->
        <div class="gh-glass p-6 bg-white/5 border-white/5">
           <div class="flex justify-between items-center mb-6 border-b border-white/5 pb-2">
                <p class="font-pixel text-xs text-neon-cyan font-bold uppercase tracking-[0.3em]">PROTOCOLOS_COMANDO</p>
                <button
                    class="py-1 px-4 rounded bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-widest shadow-lg transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-20 disabled:scale-100"
                    :disabled="isLoading || !canAdvanceRoom"
                    @click="advanceRoom"
                  >
                    PROCEDER_SIGUIENTE
                  </button>
           </div>
           
           <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
              <button
                v-for="skill in heroSkills"
                :key="skill.id"
                class="group relative flex flex-col items-center justify-center p-4  border transition-all duration-300 text-center bg-white/5"
                :class="canUseSkill(skill) ? 'border-white/10 hover:border-neon-cyan/40 hover:bg-white/10 hover:shadow-[0_0_15px_rgba(0,242,255,0.1)]' : 'opacity-30 cursor-not-allowed border-transparent bg-black/20'"
                @click="useSkill(skill.id)"
              >
                  <span class="font-display font-black text-xs uppercase mb-1" :class="canUseSkill(skill) ? 'group-hover:text-neon-cyan text-white' : 'text-white' ">{{ skill.name }}</span>
                  <p class="font-sans text-xs font-bold text-white/40 uppercase leading-tight">{{ skill.description }}</p>
                  <div v-if="skillCooldownRemaining(skill.id) > 0" class="absolute top-2 right-2 bg-neon-pink text-white size-5 rounded text-xs font-pixel flex items-center justify-center font-bold">
                     {{ skillCooldownRemaining(skill.id) }}T
                  </div>
              </button>
           </div>
        </div>

      </div>

      <!-- SIDEBAR TOOLS (Only LOG now) -->
      <aside class="w-full lg:w-[350px] flex flex-col gap-6">
        <!-- LOG: Dynamic Data list -->
        <div class="flex-1 gh-glass p-6 bg-black/40 border-white/5 flex flex-col min-h-0">
           <p class="font-pixel text-xs text-white/40 uppercase mb-4 tracking-widest border-b border-white/5 pb-2">REGISTRO_DE_SESIÓN</p>
           <div class="flex-1 overflow-auto space-y-2.5 font-pixel text-xs custom-scroll pr-2">
              <TransitionGroup name="log">
                <div v-for="(line, idx) in log" :key="idx" class="flex gap-3 items-start animate-fade-in opacity-80" :class="{'text-neon-cyan' : idx === 0 && line.includes('VICTORY'), 'text-neon-pink': idx === 0 && line.includes('ATACA')}">
                   <span class="text-white/20">>></span>
                   <span class="uppercase tracking-tight leading-tight">{{ line }}</span>
                </div>
              </TransitionGroup>
           </div>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import gameEngine from '../../lib/gameEngineService'

const GAME_SLUG = 'rpg'
const isLoading = ref(false); const error = ref(null); const sessionId = ref(null); const log = ref([])

function pushLog(m) { log.value.unshift(m.toUpperCase()); if (log.value.length > 50) log.value.length = 50 }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min }
function clone(obj) { return JSON.parse(JSON.stringify(obj)) }

function makeHero() {
  return {
    name: 'Cruzado', level: 1, hp: 30, maxHp: 30, stress: 0, maxStress: 100,
    atkMin: 5, atkMax: 9, prot: 0, guard: false,
    skillCooldowns: { smite: 0, defend: 0, inspire: 0, holy_lance: 0 }
  }
}

const heroSkills = [
  { id: 'smite', name: 'GOLPE_DATOS', cooldown: 0, description: 'Ataque directo de datos.' },
  { id: 'defend', name: 'BALUARTE', cooldown: 1, description: 'Fire-wall reactivo.' },
  { id: 'inspire', name: 'INSPIRAR', cooldown: 2, description: 'Recuperación de sistema.' },
  { id: 'holy_lance', name: 'LANZA_S', cooldown: 3, description: 'Brecha masiva de seguridad.' }
]

function makeEnemy(depth = 0) {
  const names = ['Brigante', 'Aullador', 'Necrófago', 'Cultista']
  const name = names[depth % names.length]; const level = 1 + Math.floor(depth / 3); const maxHp = 20 + (depth * 4)
  return { name, level, hp: maxHp, maxHp, atkMin: 4 + depth, atkMax: 7 + depth }
}

function generateMap(len = 8) {
  const m = []; for (let i = 0; i < len; i++) { let type = 'combat'; if (i !== 0 && i !== len - 1 && i % 3 === 2) type = 'camp'; if (i === len - 1) type = 'boss'; m.push({ id: `rm_${Date.now()}_${i}`, type }) }
  return m
}

const run = ref({ map: generateMap(), roomIndex: 0, hero: makeHero(), enemy: makeEnemy(0), gold: 0 })
const phase = ref('idle'); const turn = ref('hero')

const currentRoomIndex = computed(() => run.value.roomIndex ?? 0)
const hero = computed(() => run.value.hero); const enemy = computed(() => run.value.enemy)

function resetRunLocal() { error.value = null; log.value = []; run.value = { map: generateMap(), roomIndex: 0, hero: makeHero(), enemy: makeEnemy(0), gold: 0 }; phase.value = 'room'; turn.value = 'hero'; pushLog('Iniciando simulador...'); enterRoom() }
function enterRoom() {
  const room = run.value.map[currentRoomIndex.value]; hero.value.guard = false
  if (room.type === 'camp') {
    phase.value = 'room'; const h = randInt(8, 12); hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + h); const s = randInt(12, 20); hero.value.stress = Math.max(0, hero.value.stress - s); pushLog(`Campamento: +${h} HP / -${s}% estrés.`)
  } else { run.value.enemy = makeEnemy(room.type === 'boss' ? currentRoomIndex.value + 3 : currentRoomIndex.value); phase.value = 'combat'; turn.value = 'hero'; pushLog(`Contacto detectado: ${enemy.value.name}.`) }
}

function tickCooldowns() { const cds = hero.value.skillCooldowns ?? {}; for (const k of Object.keys(cds)) cds[k] = Math.max(Number(cds[k] ?? 0) - 1, 0); hero.value.skillCooldowns = cds }
function setCooldown(id) { const s = heroSkills.find(sk => sk.id === id); if (!s) return; const cds = hero.value.skillCooldowns ?? {}; cds[id] = s.cooldown; hero.value.skillCooldowns = cds }
function skillCooldownRemaining(id) { return Math.max(Number((hero.value.skillCooldowns ?? {})[id] ?? 0), 0) }
function canUseSkill(s) { return phase.value === 'combat' && turn.value === 'hero' && hero.value.hp > 0 && enemy.value.hp > 0 && skillCooldownRemaining(s.id) === 0 }

function endCombatIfNeeded() {
  if (enemy.value.hp <= 0) { phase.value = 'victory'; const r = randInt(10, 20); run.value.gold += r; pushLog(`¡Victoria! Log_ID: ${r} créditos.`); return true }
  if (hero.value.hp <= 0 || hero.stress >= hero.maxStress) { phase.value = 'defeat'; pushLog('Fallo crítico del sistema.'); return true }; return false
}

function enemyTurn() {
  if (phase.value !== 'combat' || endCombatIfNeeded()) return
  const dmg = randInt(enemy.value.atkMin, enemy.value.atkMax); const tk = Math.max(dmg - (hero.value.guard ? Math.floor(dmg*0.5) : 0), 0); hero.value.hp = Math.max(0, hero.value.hp - tk); const s = randInt(5, 10); hero.value.stress = Math.min(hero.value.maxStress, hero.value.stress + s)
  pushLog(`${enemy.value.name} ataca: -${tk} HP / +${s}% estrés.`); hero.value.guard = false; if (!endCombatIfNeeded()) { tickCooldowns(); turn.value = 'hero' }
}

function useSkill(sid) {
  const s = heroSkills.find(sk => sk.id === sid); if (!s || !canUseSkill(s)) return; error.value = null
  if (sid === 'smite') { const d = randInt(hero.value.atkMin, hero.value.atkMax); enemy.value.hp = Math.max(0, enemy.value.hp - d); pushLog(`Smite: -${d} HP.`) }
  else if (sid === 'defend') { hero.value.guard = true; const r = randInt(3, 6); hero.value.stress = Math.max(0, hero.value.stress - r); pushLog(`Defend: escudo ACTIVO / -${r}% estrés.`) }
  else if (sid === 'inspire') { const h = randInt(6, 10); hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + h); const r = randInt(15, 25); hero.value.stress = Math.max(0, hero.value.stress - r); pushLog(`Inspire: +${h} HP / -${r}% estrés.`) }
  else if (sid === 'holy_lance') { const d = randInt(hero.value.atkMin+4, hero.value.atkMax+8); enemy.value.hp = Math.max(0, enemy.value.hp-d); pushLog(`Holy Lance: -${d} HP.`) }
  setCooldown(sid); if (endCombatIfNeeded()) return; turn.value = 'enemy'; setTimeout(enemyTurn, 600)
}

function advanceRoom() {
  if (phase.value !== 'victory' && !(phase.value === 'room' && run.value.map[currentRoomIndex.value].type === 'camp')) return
  if (currentRoomIndex.value >= run.value.map.length - 1) { pushLog('Misión completada.'); phase.value = 'victory'; return }; run.value.roomIndex++; phase.value = 'room'; pushLog(`Cambio de sector. Sala ${currentRoomIndex.value + 1}.`); enterRoom()
}

const canAdvanceRoom = computed(() => phase.value === 'victory' || (phase.value === 'room' && run.value.map[currentRoomIndex.value].type === 'camp'))

async function startNewRun() { isLoading.value = true; try { const res = await gameEngine.play(GAME_SLUG, false); sessionId.value = res.session_id; applyLoadedState(res.game_state || {}); pushLog('Nueva run inicial.') } catch (e) { resetRunLocal() } finally { isLoading.value = false } }
async function loadRun() { isLoading.value = true; try { const res = await gameEngine.play(GAME_SLUG, true); sessionId.value = res.session_id; if (res.game_state) { applyLoadedState(res.game_state); pushLog('Partida recuperada.'); return } } catch (e) { error.value = 'Error.' } finally { isLoading.value = false } }
async function saveRun() { if (!sessionId.value) return; isLoading.value = true; try { const p = { session_id: sessionId.value, game_state: { map: clone(run.value.map), roomIndex: run.value.roomIndex, hero: clone(run.value.hero), enemy: clone(run.value.enemy), gold: run.value.gold, phase: phase.value, turn: turn.value, log: clone(log.value) } }; await gameEngine.save(GAME_SLUG, p); pushLog('Persistencia OK.') } catch (e) { error.value = 'Error.' } finally { isLoading.value = false } }

function applyLoadedState(s) { run.value = { map: s.map || generateMap(), roomIndex: s.roomIndex || 0, hero: s.hero || makeHero(), enemy: s.enemy || makeEnemy(0), gold: s.gold || 0 }; phase.value = s.phase || 'room'; turn.value = s.turn || 'hero'; log.value = s.log || [] }

onMounted(() => resetRunLocal())
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 242, 255, 0.3); }

.log-enter-active, .log-leave-active { transition: all 0.3s; }
.log-enter-from { opacity: 0; transform: translateX(-10px); }
.no-scrollbar::-webkit-scrollbar { display: none; }
</style>
