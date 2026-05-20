/**
 * LEADERBOARD STORE
 * 
 * Gestiona los rankings mundiales segmentados por juego.
 * Implementa un sistema de mapeo (Map) para almacenar múltiples clasificaciones de forma eficiente.
 */
import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useLeaderboardStore = defineStore('leaderboard', () => {
  // Store a map of slug -> entries
  const leaderboards = ref(new Map())
  // Store a map of slug -> boolean for fetching state
  const loadingStates = ref(new Map())
  
  // Pending requests mapping slug -> promise
  const pendingRequests = new Map()

  /**
   * RECUPERAR CLASIFICACIÓN
   * Obtiene el ranking de un juego específico.
   * Utiliza de-duplicación de peticiones para evitar saturar la red en cargas rápidas.
   */
  async function fetchLeaderboard(slug, force = false) {
    if (!slug) return []

    const currentEntries = leaderboards.value.get(slug) || []
    
    // If not forcing and we already have data, just return it
    if (!force && leaderboards.value.has(slug)) {
      return currentEntries
    }

    // Deduplication of concurrent requests for this specific game
    if (!force && pendingRequests.has(slug)) {
      return pendingRequests.get(slug)
    }

    // Set loading state
    setLoadingState(slug, true)

    const request = (async () => {
      try {
        const { data } = await api.get(`/leaderboard/${slug}`)
        const entries = data.data ?? []
        leaderboards.value.set(slug, entries)
        return entries
      } catch (error) {
        console.error(`Error fetching leaderboard for ${slug}:`, error)
        if (!leaderboards.value.has(slug)) {
          leaderboards.value.set(slug, [])
        }
        return []
      } finally {
        setLoadingState(slug, false)
        pendingRequests.delete(slug)
      }
    })()

    pendingRequests.set(slug, request)
    return request
  }

  function setLoadingState(slug, state) {
    loadingStates.value.set(slug, state)
  }

  function isLoading(slug) {
    return loadingStates.value.get(slug) || false
  }

  function getEntries(slug) {
    return leaderboards.value.get(slug) || []
  }

  function clearStore() {
    leaderboards.value.clear()
    loadingStates.value.clear()
    pendingRequests.clear()
  }

  return {
    leaderboards,
    loadingStates,
    fetchLeaderboard,
    isLoading,
    getEntries,
    clearStore
  }
})
