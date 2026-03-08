import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import gameEngine from '../../lib/gameEngineService'

export const useClickerStore = defineStore('clicker', () => {
  const GAME_SLUG = 'clicker'

  const isLoading = ref(false)
  const error     = ref(null)
  const sessionId = ref(null)
  const lastSaved = ref(null)
  // Cola de logros recién desbloqueados para notificar al usuario
  const newAchievements = ref([])

  const gameState = ref({
    balance:        0,
    click_power:    1,
    dps:            0,
    upgrades:       {},
    total_clicks:   0,
    prestige_level: 0,
  })

  const balance      = computed(() => gameState.value.balance)
  const clickPower   = computed(() => gameState.value.click_power)
  const dps          = computed(() => gameState.value.dps)
  const upgrades     = computed(() => gameState.value.upgrades)
  const prestigeLevel = computed(() => gameState.value.prestige_level)
  const totalClicks  = computed(() => gameState.value.total_clicks)

  const PRESTIGE_MIN_BALANCE = 1_000_000

  // Datos estáticos de mejoras (espejo del backend)
  const UPGRADE_BASE_COSTS = {
     1: 10,
     2: 50,
     3: 200,
     4: 400,
     5: 2_000,
     6: 5_000,
     7: 30_000,
     8: 20_000,
     9: 150_000,
    10: 250_000,
    11: 800_000,
    12: 600_000,
  }
  const UPGRADE_BONUSES = {
     1: { dps_bonus: 0.1,   click_bonus: 0 },
     2: { dps_bonus: 0,     click_bonus: 1 },
     3: { dps_bonus: 2,     click_bonus: 0 },
     4: { dps_bonus: 0,     click_bonus: 4 },
     5: { dps_bonus: 20,    click_bonus: 0 },
     6: { dps_bonus: 0,     click_bonus: 25 },
     7: { dps_bonus: 200,   click_bonus: 0 },
     8: { dps_bonus: 0,     click_bonus: 100 },
     9: { dps_bonus: 800,   click_bonus: 0 },
    10: { dps_bonus: 0,     click_bonus: 600 },
    11: { dps_bonus: 6_000, click_bonus: 0 },
    12: { dps_bonus: 0,     click_bonus: 2_500 },
  }

  // Coste escalado: baseCost × 1.15^cantidad_comprada
  function upgradeCost(upgradeId) {
    const base  = UPGRADE_BASE_COSTS[upgradeId] ?? 0
    const count = gameState.value.upgrades[upgradeId] ?? 0
    return Math.ceil(base * Math.pow(1.15, count))
  }

  // Recalcula DPS y click_power desde cero a partir del array de mejoras
  function _recalcStats() {
    let dps        = 0
    let clickPower = 1 + (gameState.value.prestige_level ?? 0) * 0.5
    const dpsMul   = gameState.value.prestige_dps_mul ?? 1
    for (const [id, count] of Object.entries(gameState.value.upgrades)) {
      const u = UPGRADE_BONUSES[id]
      if (u) {
        dps        += u.dps_bonus   * count
        clickPower += u.click_bonus * count
      }
    }
    gameState.value.dps         = dps * dpsMul
    gameState.value.click_power = clickPower
  }

  async function initializeGame(loadSave = true) {
    isLoading.value = true
    error.value     = null
    try {
      const res = await gameEngine.play(GAME_SLUG, loadSave)
      sessionId.value = res.session_id
      Object.assign(gameState.value, res.game_state)
    } catch (e) {
      error.value = e.message
    } finally {
      isLoading.value = false
    }
  }

  // Actualización local inmediata
  function click() {
    gameState.value.balance      += gameState.value.click_power
    gameState.value.total_clicks += 1
  }

  function buyUpgrade(upgradeId) {
    const cost = upgradeCost(upgradeId)
    if (gameState.value.balance < cost) return

    // Actualización local inmediata
    gameState.value.balance -= cost
    gameState.value.upgrades = {
      ...gameState.value.upgrades,
      [upgradeId]: (gameState.value.upgrades[upgradeId] ?? 0) + 1,
    }
    _recalcStats()

    // Sincronización con el backend en segundo plano (sin await)
    gameEngine.action(GAME_SLUG, {
      action:  'buy_upgrade',
      payload: { upgrade_id: upgradeId },
    }).catch(() => {
      // El estado completo se reconciliará en el próximo saveGame()
    })
  }

  async function prestige() {
    if (gameState.value.balance < PRESTIGE_MIN_BALANCE) return
    try {
      const res = await gameEngine.action(GAME_SLUG, {
        action:  'prestige',
        payload: {},
      })
      if (res.success) {
        gameState.value.balance          = 0
        gameState.value.upgrades         = {}
        gameState.value.prestige_level   = res.data.prestige_level
        gameState.value.click_power      = res.data.click_power
        gameState.value.dps              = 0
        gameState.value.prestige_dps_mul = res.data.prestige_dps_mul ?? 1
      }
    } catch (e) {
      error.value = e.message
    }
  }

  async function saveGame() {
    try {
      const res = await gameEngine.save(GAME_SLUG, {
        game_state: gameState.value,
        score:      gameState.value.balance,
        playtime:   0,
      })
      lastSaved.value = new Date()
      if (res.achievements_unlocked?.length) {
        newAchievements.value.push(...res.achievements_unlocked)
      }
    } catch (e) {
      error.value = e.message
    }
  }

  async function completeGame(durationSeconds) {
    if (!sessionId.value) return null
    try {
      const res = await gameEngine.complete(GAME_SLUG, {
        session_id:  sessionId.value,
        final_score: gameState.value.balance,
        duration:    durationSeconds,
      })
      return res
    } catch (e) {
      error.value = e.message
      return null
    }
  }

  function $reset() {
    gameState.value     = { balance: 0, click_power: 1, dps: 0, upgrades: {}, total_clicks: 0, prestige_level: 0, prestige_dps_mul: 1 }
    sessionId.value     = null
    lastSaved.value     = null
    error.value         = null
    newAchievements.value = []
  }

  return {
    // state
    gameState, isLoading, error, lastSaved, sessionId, newAchievements,
    // computed
    balance, clickPower, dps, upgrades, prestigeLevel, totalClicks,
    // helpers
    upgradeCost, PRESTIGE_MIN_BALANCE,
    // actions
    initializeGame, click, buyUpgrade, prestige, saveGame, completeGame,
    $reset,
  }
})
