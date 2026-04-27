import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'
import { useAuthStore } from './auth'

export const useAchievementStore = defineStore('achievement', () => {
  const achievements = ref([])
  const isLoading = ref(false)
  const hasFetched = ref(false)
  let pendingRequest = null

  async function fetchAchievements(force = false) {
    const authStore = useAuthStore()
    if (!authStore.isLoggedIn) {
      achievements.value = []
      return []
    }

    if (!force && hasFetched.value && achievements.value.length > 0) {
      return achievements.value
    }
    if (!force && pendingRequest) {
      return pendingRequest
    }

    isLoading.value = true
    pendingRequest = (async () => {
      try {
        const { data } = await api.get('/achievements')
        achievements.value = data.data ?? []
      } catch (error) {
        console.error('Error fetching achievements:', error)
        achievements.value = []
      } finally {
        hasFetched.value = true
        isLoading.value = false
        pendingRequest = null
      }
      return achievements.value
    })()

    return pendingRequest
  }

  const achievementsByGame = computed(() => {
    const map = {}
    for (const a of achievements.value) {
      if (a.game_id !== null) {
        if (!map[a.game_id]) map[a.game_id] = []
        map[a.game_id].push(a)
      }
    }
    return map
  })

  function clearStore() {
    achievements.value = []
    hasFetched.value = false
    pendingRequest = null
  }

  return {
    achievements,
    achievementsByGame,
    isLoading,
    hasFetched,
    fetchAchievements,
    clearStore
  }
})
