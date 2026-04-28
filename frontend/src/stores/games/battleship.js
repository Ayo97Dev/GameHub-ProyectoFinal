import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import gameEngine from '../../lib/gameEngineService'

export const useBattleshipStore = defineStore('battleship', () => {
  const GAME_SLUG = 'battleship'
  const SAVE_COOLDOWN_MS = 5_000

  const isLoading = ref(false)
  const isSaving = ref(false)
  const error = ref(null)
  const sessionId = ref(null)
  const lastSaved = ref(null)
  const lastSaveRequestAt = ref(0)
  const sessionStartedAt = ref(null)
  const reportedPlaytimeSeconds = ref(0)
  const newAchievements = ref([])

  const stats = ref({
    wins: 0,
  })

  const wins = computed(() => stats.value.wins)

  function hydrateState(gameState = {}) {
    stats.value.wins = Math.max(Number(gameState.wins ?? 0), 0)
  }

  function getSessionDurationSeconds() {
    if (!sessionStartedAt.value) return 0
    return Math.max(Math.floor((Date.now() - sessionStartedAt.value) / 1000), 0)
  }

  async function initializeGame(loadSave = true) {
    isLoading.value = true
    error.value = null

    try {
      const res = await gameEngine.play(GAME_SLUG, loadSave)
      sessionId.value = res.session_id
      hydrateState(res.game_state ?? {})
      sessionStartedAt.value = Date.now()
      reportedPlaytimeSeconds.value = 0
    } catch (e) {
      error.value = e.message
    } finally {
      isLoading.value = false
    }
  }

  async function saveStats(gameState = {}) {
    if (isSaving.value) return
    
    const now = Date.now()
    if (now - lastSaveRequestAt.value < SAVE_COOLDOWN_MS) {
      return
    }
    
    lastSaveRequestAt.value = now
    error.value = null

    try {
      isSaving.value = true
      const elapsedSeconds = getSessionDurationSeconds()
      const pendingPlaytime = Math.max(elapsedSeconds - reportedPlaytimeSeconds.value, 0)

      const res = await gameEngine.save(GAME_SLUG, {
        game_state: {
          ...gameState,
          wins: stats.value.wins,
        },
        score: stats.value.wins,
        playtime: pendingPlaytime,
      })

      reportedPlaytimeSeconds.value += pendingPlaytime
      lastSaved.value = new Date()

      if (res.achievements_unlocked?.length) {
        newAchievements.value.push(...res.achievements_unlocked)
      }
    } catch (e) {
      error.value = e.message
    } finally {
      isSaving.value = false
    }
  }

  async function recordWin(gameState = {}) {
    stats.value.wins += 1
    // For recordWin, we bypass the cooldown to ensure victory is saved
    lastSaveRequestAt.value = 0 
    await saveStats(gameState)
  }

  function $reset() {
    stats.value.wins = 0
    isLoading.value = false
    isSaving.value = false
    error.value = null
    sessionId.value = null
    lastSaved.value = null
    lastSaveRequestAt.value = 0
    sessionStartedAt.value = null
    reportedPlaytimeSeconds.value = 0
    newAchievements.value = []
  }

  return {
    isLoading,
    isSaving,
    error,
    sessionId,
    lastSaved,
    newAchievements,
    wins,
    initializeGame,
    getSessionDurationSeconds,
    saveStats,
    recordWin,
    $reset,
  }
})
