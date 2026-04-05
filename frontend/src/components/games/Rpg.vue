<template>
  <section class="h-full min-h-80 rounded-xl border border-violet-500/30 bg-linear-to-br from-violet-100 to-white dark:from-violet-950/50 dark:to-slate-900 p-6 transition-colors">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.24em] text-violet-600 dark:text-violet-300">Aventura</p>
        <h2 class="mt-2 text-2xl font-black text-violet-700 dark:text-violet-300 transition-colors">RPG Mode</h2>
        <p class="mt-2 text-slate-600 dark:text-slate-300 transition-colors">
          MVP tipo Darkest Dungeon: mapa lineal (salas) + combate por turnos 1v1 + guardado.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          class="rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
          :disabled="isLoading"
          @click="startNewRun"
        >
          Nueva run
        </button>
        <button
          class="rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
          :disabled="isLoading"
          @click="loadRun"
        >
          Cargar
        </button>
        <button
          class="rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
          :disabled="isLoading || !sessionId"
          @click="saveRun"
        >
          Guardar
        </button>
      </div>
    </div>

    <div v-if="error" class="mt-4 rounded-lg border border-red-500/30 bg-red-50/60 dark:bg-red-950/30 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ error }}
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="rounded-xl border border-violet-500/20 bg-white/60 dark:bg-slate-900/40 p-4">
        <div class="flex items-center justify-between">
          <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Mapa</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Sala {{ currentRoomIndex + 1 }} / {{ run.map.length }}
          </p>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <span
            v-for="(room, idx) in run.map"
            :key="room.id"
            class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
            :class="idx === currentRoomIndex
              ? 'border-violet-500/40 bg-violet-100 dark:bg-violet-950/30 text-violet-700 dark:text-violet-200'
              : 'border-slate-300/40 bg-white/40 dark:bg-slate-950/20 text-slate-700 dark:text-slate-200'"
          >
            <span class="opacity-80">{{ idx + 1 }}</span>
            <span>{{ roomLabel(room.type) }}</span>
          </span>
        </div>

        <div class="mt-4 flex gap-2">
          <button
            class="flex-1 rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
            :disabled="isLoading || !canAdvanceRoom"
            @click="advanceRoom"
          >
            Avanzar
          </button>
          <button
            class="rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
            :disabled="isLoading"
            @click="resetRunLocal"
          >
            Reiniciar
          </button>
        </div>
      </div>

      <div class="rounded-xl border border-violet-500/20 bg-white/60 dark:bg-slate-900/40 p-4 lg:col-span-2">
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Combate</p>
          <div class="text-xs text-slate-500 dark:text-slate-400">
            <span v-if="phase === 'combat'">Turno: {{ turnLabel }}</span>
            <span v-else>Fase: {{ phaseLabel }}</span>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div class="rounded-lg border border-slate-300/40 dark:border-slate-700/40 bg-white/40 dark:bg-slate-950/20 p-3">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Héroe</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ hero.name }} (Nivel {{ hero.level }})</p>
            <div class="mt-2 text-xs text-slate-600 dark:text-slate-300">
              HP: <span class="font-bold">{{ hero.hp }}</span> / {{ hero.maxHp }}
              · Estrés: <span class="font-bold">{{ hero.stress }}</span> / {{ hero.maxStress }}
            </div>
          </div>

          <div class="rounded-lg border border-slate-300/40 dark:border-slate-700/40 bg-white/40 dark:bg-slate-950/20 p-3">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Enemigo</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ enemy.name }} (Nivel {{ enemy.level }})</p>
            <div class="mt-2 text-xs text-slate-600 dark:text-slate-300">
              HP: <span class="font-bold">{{ enemy.hp }}</span> / {{ enemy.maxHp }}
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button
            v-for="skill in heroSkills"
            :key="skill.id"
            class="rounded-lg border border-violet-500/30 bg-white/60 dark:bg-slate-900/40 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
            :disabled="isLoading || !canUseSkill(skill)"
            @click="useSkill(skill.id)"
          >
            {{ skill.name }}
            <span v-if="skillCooldownRemaining(skill.id) > 0" class="ml-1 text-xs opacity-80">
              (CD {{ skillCooldownRemaining(skill.id) }})
            </span>
          </button>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div class="rounded-lg border border-slate-300/40 dark:border-slate-700/40 bg-white/40 dark:bg-slate-950/20 p-3">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Acciones</p>
            <div class="mt-2 text-sm text-slate-700 dark:text-slate-200">
              <p v-if="phase === 'idle'">Inicia una run o carga una partida.</p>
              <p v-else-if="phase === 'room'">Sala actual: <span class="font-bold">{{ roomLabel(currentRoom.type) }}</span></p>
              <p v-else-if="phase === 'combat'">Elige una acción para tu héroe.</p>
              <p v-else-if="phase === 'victory'">Victoria. Puedes avanzar a la siguiente sala.</p>
              <p v-else-if="phase === 'defeat'">Derrota. Reinicia o carga una run.</p>
            </div>
          </div>

          <div class="rounded-lg border border-slate-300/40 dark:border-slate-700/40 bg-white/40 dark:bg-slate-950/20 p-3">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Bitácora</p>
            <div class="mt-2 max-h-40 overflow-auto text-xs text-slate-700 dark:text-slate-200 space-y-1">
              <p v-for="(line, idx) in log" :key="idx">{{ line }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import gameEngine from '../../lib/gameEngineService'

const GAME_SLUG = 'rpg'

const isLoading = ref(false)
const error = ref(null)
const sessionId = ref(null)

const log = ref([])

function pushLog(message) {
  log.value.unshift(message)
  if (log.value.length > 50) log.value.length = 50
}

function randInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function clone(obj) {
  return JSON.parse(JSON.stringify(obj))
}

function makeHero() {
  return {
    name: 'Cruzado',
    level: 1,
    hp: 1000,
    maxHp: 1000,
    stress: 0,
    maxStress: 100,
    atkMin: 4,
    atkMax: 8,
    prot: 0,
    guard: false,
    skillCooldowns: {
      smite: 0,
      defend: 0,
      inspire: 0,
      holy_lance: 0,
    },
  }
}

const heroSkills = [
  {
    id: 'smite',
    name: 'Smite',
    cooldown: 0,
    description: 'Daño consistente.',
  },
  {
    id: 'defend',
    name: 'Bulwark',
    cooldown: 1,
    description: 'Mitiga el siguiente golpe y baja un poco el estrés.',
  },
  {
    id: 'inspire',
    name: 'Inspire',
    cooldown: 2,
    description: 'Reduce estrés y cura poco.',
  },
  {
    id: 'holy_lance',
    name: 'Holy Lance',
    cooldown: 3,
    description: 'Golpe fuerte, más estrés al enemigo.',
  },
]

function makeEnemy(depth = 0) {
  const names = ['Brigante', 'Aullador', 'Necrófago', 'Cultista']
  const name = names[depth % names.length]
  const level = 1 + Math.floor(depth / 3)
  const maxHp = 18 + (depth * 3)
  return {
    name,
    level,
    hp: maxHp,
    maxHp,
    atkMin: 3 + depth,
    atkMax: 6 + depth,
  }
}

function generateMap(length = 7) {
  const map = []
  for (let i = 0; i < length; i += 1) {
    let type = 'combat'
    if (i !== 0 && i !== length - 1 && i % 3 === 2) type = 'camp'
    if (i === length - 1) type = 'boss'
    map.push({ id: `room_${Date.now()}_${i}`, type })
  }
  return map
}

const run = ref({
  map: generateMap(7),
  roomIndex: 0,
  hero: makeHero(),
  enemy: makeEnemy(0),
  gold: 0,
})

const phase = ref('idle')
const turn = ref('hero')

const currentRoomIndex = computed(() => run.value.roomIndex ?? 0)
const currentRoom = computed(() => run.value.map[currentRoomIndex.value] ?? { type: 'combat' })

const hero = computed(() => run.value.hero)
const enemy = computed(() => run.value.enemy)

const phaseLabel = computed(() => {
  if (phase.value === 'idle') return 'idle'
  if (phase.value === 'room') return 'sala'
  if (phase.value === 'combat') return 'combate'
  if (phase.value === 'victory') return 'victoria'
  if (phase.value === 'defeat') return 'derrota'
  return phase.value
})

const turnLabel = computed(() => (turn.value === 'hero' ? 'Héroe' : 'Enemigo'))

const canAct = computed(() => phase.value === 'combat' && turn.value === 'hero' && hero.value.hp > 0 && enemy.value.hp > 0)
const canAdvanceRoom = computed(() => {
  if (phase.value === 'idle') return false
  if (phase.value === 'defeat') return false
  if (phase.value === 'victory') return true
  if (phase.value === 'room' && currentRoom.value.type !== 'combat' && currentRoom.value.type !== 'boss') return true
  return false
})

function roomLabel(type) {
  if (type === 'combat') return 'Combate'
  if (type === 'camp') return 'Campamento'
  if (type === 'boss') return 'Jefe'
  return type
}

function resetRunLocal() {
  error.value = null
  log.value = []
  run.value = {
    map: generateMap(7),
    roomIndex: 0,
    hero: makeHero(),
    enemy: makeEnemy(0),
    gold: 0,
  }
  phase.value = 'room'
  turn.value = 'hero'
  pushLog('Run reiniciada.')
  enterRoom()
}

function enterRoom() {
  const room = currentRoom.value
  hero.value.guard = false

  if (room.type === 'camp') {
    phase.value = 'room'
    const heal = randInt(6, 10)
    hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + heal)
    const relief = randInt(10, 18)
    hero.value.stress = Math.max(0, hero.value.stress - relief)
    pushLog(`Campamento: +${heal} HP, -${relief} estrés.`)
    return
  }

  if (room.type === 'combat' || room.type === 'boss') {
    const depth = currentRoomIndex.value
    run.value.enemy = makeEnemy(room.type === 'boss' ? depth + 2 : depth)
    phase.value = 'combat'
    turn.value = 'hero'
    pushLog(`${room.type === 'boss' ? 'Jefe' : 'Combate'}: aparece ${enemy.value.name}.`)
  }
}

function tickCooldowns() {
  const cds = hero.value.skillCooldowns ?? {}
  for (const key of Object.keys(cds)) {
    cds[key] = Math.max(Number(cds[key] ?? 0) - 1, 0)
  }
  hero.value.skillCooldowns = cds
}

function setCooldown(skillId) {
  const skill = heroSkills.find(s => s.id === skillId)
  if (!skill) return
  const cds = hero.value.skillCooldowns ?? {}
  cds[skillId] = Math.max(Number(skill.cooldown ?? 0), 0)
  hero.value.skillCooldowns = cds
}

function skillCooldownRemaining(skillId) {
  const cds = hero.value.skillCooldowns ?? {}
  return Math.max(Number(cds[skillId] ?? 0), 0)
}

function canUseSkill(skill) {
  if (!canAct.value) return false
  return skillCooldownRemaining(skill.id) === 0
}

function endCombatIfNeeded() {
  if (enemy.value.hp <= 0) {
    enemy.value.hp = 0
    phase.value = 'victory'
    const reward = randInt(8, 16) + currentRoomIndex.value
    run.value.gold += reward
    pushLog(`Victoria: +${reward} oro.`)
    return true
  }
  if (hero.value.hp <= 0 || hero.value.stress >= hero.value.maxStress) {
    hero.value.hp = Math.max(0, hero.value.hp)
    hero.value.stress = Math.min(hero.value.maxStress, hero.value.stress)
    phase.value = 'defeat'
    pushLog('Derrota: la run termina.')
    return true
  }
  return false
}

function enemyTurn() {
  if (phase.value !== 'combat') return
  if (endCombatIfNeeded()) return

  const dmg = randInt(enemy.value.atkMin, enemy.value.atkMax)
  const blocked = hero.value.guard ? Math.floor(dmg * 0.5) : 0
  const taken = Math.max(dmg - blocked, 0)
  hero.value.hp = Math.max(0, hero.value.hp - taken)
  const stress = randInt(4, 8)
  hero.value.stress = Math.min(hero.value.maxStress, hero.value.stress + stress)

  pushLog(`${enemy.value.name} ataca: -${taken} HP${blocked ? ` (bloqueados ${blocked})` : ''}, +${stress} estrés.`)
  hero.value.guard = false

  if (!endCombatIfNeeded()) {
    tickCooldowns()
    turn.value = 'hero'
  }
}

function useSkill(skillId) {
  const skill = heroSkills.find(s => s.id === skillId)
  if (!skill) return
  if (!canUseSkill(skill)) return
  error.value = null

  if (skillId === 'smite') {
    const dmg = randInt(hero.value.atkMin, hero.value.atkMax)
    enemy.value.hp = Math.max(0, enemy.value.hp - dmg)
    pushLog(`${hero.value.name} usa ${skill.name}: -${dmg} HP a ${enemy.value.name}.`)
  } else if (skillId === 'defend') {
    hero.value.guard = true
    const relief = randInt(2, 5)
    hero.value.stress = Math.max(0, hero.value.stress - relief)
    pushLog(`${hero.value.name} usa ${skill.name}: guard activo, -${relief} estrés.`)
  } else if (skillId === 'inspire') {
    const heal = randInt(4, 7)
    hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + heal)
    const relief = randInt(12, 18)
    hero.value.stress = Math.max(0, hero.value.stress - relief)
    pushLog(`${hero.value.name} usa ${skill.name}: +${heal} HP, -${relief} estrés.`)
  } else if (skillId === 'holy_lance') {
    const dmg = randInt(hero.value.atkMin + 3, hero.value.atkMax + 6)
    enemy.value.hp = Math.max(0, enemy.value.hp - dmg)
    const stressHit = randInt(6, 10)
    pushLog(`${hero.value.name} usa ${skill.name}: -${dmg} HP y ${enemy.value.name} se tambalea (+${stressHit} tensión).`)
  }

  setCooldown(skillId)

  if (endCombatIfNeeded()) return
  turn.value = 'enemy'
  window.setTimeout(enemyTurn, 450)
}

function advanceRoom() {
  if (!canAdvanceRoom.value) return
  if (phase.value === 'victory' || phase.value === 'room') {
    if (currentRoomIndex.value >= run.value.map.length - 1) {
      pushLog('Run completada.')
      phase.value = 'victory'
      return
    }
    run.value.roomIndex += 1
    phase.value = 'room'
    pushLog(`Avanzas a la sala ${currentRoomIndex.value + 1}.`)
    enterRoom()
  }
}

async function startNewRun() {
  isLoading.value = true
  error.value = null
  try {
    const res = await gameEngine.play(GAME_SLUG, false)
    sessionId.value = res.session_id ?? null
    if (res.game_state) {
      applyLoadedState(res.game_state)
      pushLog('Run nueva (desde backend).')
    } else {
      resetRunLocal()
      pushLog('Run nueva (local).')
    }
  } catch (e) {
    resetRunLocal()
    error.value = 'No se pudo iniciar con backend; usando run local.'
  } finally {
    isLoading.value = false
  }
}

function applyLoadedState(state) {
  const safe = {
    map: Array.isArray(state.map) ? state.map : generateMap(7),
    roomIndex: Number.isFinite(state.roomIndex) ? state.roomIndex : 0,
    hero: state.hero ? { ...makeHero(), ...state.hero } : makeHero(),
    enemy: state.enemy ? { ...makeEnemy(0), ...state.enemy } : makeEnemy(0),
    gold: Number.isFinite(state.gold) ? state.gold : 0,
  }
  run.value = safe
  phase.value = state.phase ?? 'room'
  turn.value = state.turn ?? 'hero'
  log.value = Array.isArray(state.log) ? state.log : []
  if (phase.value === 'idle') phase.value = 'room'
  if (phase.value === 'room' || phase.value === 'combat') {
    if (safe.roomIndex < 0) run.value.roomIndex = 0
    if (safe.roomIndex > safe.map.length - 1) run.value.roomIndex = safe.map.length - 1
  }
}

async function saveRun() {
  if (!sessionId.value) {
    error.value = 'No hay sesión activa para guardar.'
    return
  }
  isLoading.value = true
  error.value = null
  try {
    const payload = {
      session_id: sessionId.value,
      game_state: {
        map: clone(run.value.map),
        roomIndex: run.value.roomIndex,
        hero: clone(run.value.hero),
        enemy: clone(run.value.enemy),
        gold: run.value.gold,
        phase: phase.value,
        turn: turn.value,
        log: clone(log.value),
      },
    }
    await gameEngine.save(GAME_SLUG, payload)
    pushLog('Guardado OK.')
  } catch (e) {
    error.value = 'Error guardando la run.'
  } finally {
    isLoading.value = false
  }
}

async function loadRun() {
  isLoading.value = true
  error.value = null
  try {
    const res = await gameEngine.play(GAME_SLUG, true)
    sessionId.value = res.session_id ?? sessionId.value
    if (res.game_state) {
      applyLoadedState(res.game_state)
      pushLog('Partida cargada.')
      return
    }
    const loaded = await gameEngine.load(GAME_SLUG)
    if (loaded?.game_state) {
      applyLoadedState(loaded.game_state)
      pushLog('Partida cargada.')
    } else {
      error.value = 'No hay partida guardada.'
    }
  } catch (e) {
    error.value = 'Error cargando la run.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  resetRunLocal()
})
</script>
