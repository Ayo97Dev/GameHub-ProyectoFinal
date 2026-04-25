import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useGameStore = defineStore('game', () => {
  const isLoading = ref(false)
  const hasFetched = ref(false)
  let pendingRequest = null

  const DEFAULT_GAMES = [
    { slug: 'descenso-al-abismo', title: 'Descenso al Abismo', description: 'Sobrevive a las profundidades en este RPG de exploración táctica.' },
    { slug: 'core-clicker', title: 'CoreClicker', description: 'Optimiza el núcleo del sistema mediante clics de alta frecuencia.' },
    { slug: 'quiz', title: 'Quiz', description: 'Preguntas rápidas.' },
    { slug: 'connect4', title: 'Conecta 4', description: 'Clásico Conecta 4 contra la IA' },
    { slug: 'proyecto-cortafuegos', title: 'Proyecto_Cortafuegos', description: 'Protege la red central contra intrusiones masivas desplegando contramedidas tácticas.' },
  ]

  const DEFAULT_COVERS = {
    'space-invaders': 'https://images.unsplash.com/photo-1486572788966-cfd3df1f5b42?auto=format&fit=crop&w=1200&q=80',
    'cookie-clicker': 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
    'descenso-al-abismo': 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80',
    'core-clicker': 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
    'quiz': 'https://images.unsplash.com/photo-1486572788966-cfd3df1f5b42?auto=format&fit=crop&w=1200&q=80',
    'connect4': 'https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&w=1200&q=80',
    'proyecto-cortafuegos': 'https://images.unsplash.com/photo-1518709268805-4e9042af2176?auto=format&fit=crop&w=1200&q=80'
  }

  function withPresentation(game) {
    return {
      ...game,
      cover: DEFAULT_COVERS[game.slug] || DEFAULT_COVERS.quiz,
      route: `/play/${game.slug}`,
    }
  }

  function defaultGames() {
    return DEFAULT_GAMES.map(withPresentation)
  }

  const games = ref(defaultGames())

  function mergeWithDefaults(apiGames) {
    const merged = new Map(defaultGames().map(game => [game.slug, game]))

    for (const game of apiGames) {
      merged.set(game.slug, withPresentation(game))
    }

    return Array.from(merged.values())
  }

  async function fetchGames(options = {}) {
    const { force = false } = options

    if (!force && hasFetched.value && games.value.length > 0) {
      return games.value
    }

    if (!force && pendingRequest) {
      return pendingRequest
    }

    isLoading.value = true

    pendingRequest = (async () => {
      try {
        const { data } = await api.get('/games')
        const apiGames = Array.isArray(data?.data) ? data.data : []

        games.value = apiGames.length > 0
          ? mergeWithDefaults(apiGames)
          : defaultGames()
      } catch (error) {
        console.error('Error fetching games:', error)

        if (games.value.length === 0) {
          games.value = defaultGames()
        }
      } finally {
        hasFetched.value = true
        isLoading.value = false
        pendingRequest = null
      }

      return games.value
    })()

    return pendingRequest
  }

  const gamesBySlug = computed(() => {
    return games.value.reduce((acc, game) => {
      acc[game.slug] = game
      return acc
    }, {})
  })

  const telemetry = ref({
    active_users: 0,
    active_sessions: 0,
    games_telemetry: {}
  })

  async function fetchTelemetry() {
    try {
      const { data } = await api.get('/telemetry')
      telemetry.value = data
    } catch (error) {
      console.error('Error fetching telemetry:', error)
    }
  }

  return {
    games,
    isLoading,
    hasFetched,
    fetchGames,
    gamesBySlug,
    telemetry,
    fetchTelemetry,
  }
})
