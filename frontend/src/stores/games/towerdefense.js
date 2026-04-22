import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import gameEngineService from '../../lib/gameEngineService'

export const useTowerDefenseStore = defineStore('tower-defense', () => {
  const SAVE_COOLDOWN_MS = 5_000 // Mínimo 5 segundos entre saves
  
  const gameState = reactive({
    lives: 100,
    gold: 150,
    wave: 1,
    waveActive: false,
    gameOver: false,
    towers: [],
  })

  const isLoading = ref(false)
  const isSaving = ref(false)
  const lastSaveRequestAt = ref(0)
  const hasLoadedGame = ref(false)
  const savedGame = ref(null)
  const sessionId = ref(null)
  const unlockedAchievements = ref([])

  /**
   * Carga una partida guardada o inicia una nueva
   */
  async function loadGame() {
    isLoading.value = true
    try {
      const response = await gameEngineService.load('tower-defense')
      if (response.game_state && Object.keys(response.game_state).length > 0) {
        savedGame.value = response.game_state
      }
    } catch (error) {
      console.error('Error loading game:', error)
    } finally {
      isLoading.value = false
      hasLoadedGame.value = true
    }
  }

  /**
   * Inicia una nueva partida o continúa una guardada
   */
  async function initializeTowerDefense(continueGame = false) {
    isLoading.value = true
    lastSaveRequestAt.value = 0
    try {
      const response = await gameEngineService.play('tower-defense', continueGame && !!savedGame.value)
      sessionId.value = response.session_id

      const state = response.game_state
      Object.assign(gameState, state)
    } catch (error) {
      console.error('Error initializing game:', error)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Guarda el progreso (autosave al terminar onda)
   */
  async function saveProgress(score = gameState.wave) {
    if (isSaving.value) return
    
    const now = Date.now()
    if (now - lastSaveRequestAt.value < SAVE_COOLDOWN_MS) {
      return
    }
    
    lastSaveRequestAt.value = now
    
    try {
      isSaving.value = true
      const response = await gameEngineService.save('tower-defense', {
        game_state: gameState,
        score,
        playtime: 0,
      })

      if (response.achievements_unlocked) {
        unlockedAchievements.value = response.achievements_unlocked
      }

      return response
    } catch (error) {
      console.error('Error saving progress:', error)
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Completa la sesión (game over)
   */
  async function completeSession(finalScore, duration) {
    try {
      const response = await gameEngineService.complete('tower-defense', {
        session_id: sessionId.value,
        final_score: finalScore,
        duration,
      })

      if (response.achievements_unlocked) {
        unlockedAchievements.value = response.achievements_unlocked
      }

      return response
    } catch (error) {
      console.error('Error completing session:', error)
    }
  }

  /**
   * Resetea la partida guardada
   */
  async function resetGame() {
    try {
      await gameEngineService.reset('tower-defense')
      savedGame.value = null
    } catch (error) {
      console.error('Error resetting game:', error)
    }
  }

  /**
   * Actualiza el estado del juego
   */
  function updateGameState(updates) {
    Object.assign(gameState, updates)
  }

  return {
    gameState,
    isLoading,
    isSaving,
    lastSaveRequestAt,
    hasLoadedGame,
    savedGame,
    sessionId,
    unlockedAchievements,
    loadGame,
    initializeTowerDefense,
    saveProgress,
    completeSession,
    resetGame,
    updateGameState,
  }
})
