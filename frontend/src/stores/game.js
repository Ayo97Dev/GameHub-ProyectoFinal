import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useGameStore = defineStore('game', () => {
  const games = ref([])
  const isLoading = ref(false)

  const TITLE_OVERRIDES_BY_SLUG = {
    chess: 'Board King',
  }

  const DEFAULT_COVERS = {
    'space-invaders': 'https://images.unsplash.com/photo-1486572788966-cfd3df1f5b42?auto=format&fit=crop&w=1200&q=80',
    'cookie-clicker': 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
    'rpg': '/assets/legendPath/LegendPathLogo.png',
    'clicker': 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
    'quiz': 'https://images.unsplash.com/photo-1486572788966-cfd3df1f5b42?auto=format&fit=crop&w=1200&q=80',
    'battleship': '/assets/fleetStrategy/fleetStrategyLogo.png',
    'chess': '/assets/boardKing/boardKingLogo.png',
  
  }

  const LOCAL_GAMES = [
    {
      slug: 'rpg',
      title: 'Dungeon Realms RPG',
      description: 'Explora mazmorras oscuras y sube de nivel a tu héroe.',
      cover: DEFAULT_COVERS['rpg'],
      route: '/play/rpg',
    },
    {
      slug: 'clicker',
      title: 'Neon Clicker Rush',
      description: 'Haz clic, mejora tu producción y domina el ranking.',
      cover: DEFAULT_COVERS['clicker'],
      route: '/play/clicker',
    },
    {
      slug: 'quiz',
      title: 'Quiz Master',
      description: 'Preguntas rápidas y ranking en tiempo real.',
      cover: DEFAULT_COVERS['quiz'],
      route: '/play/quiz',
    },
    {
      slug: 'battleship',
      title: 'Fleet Strategy',
      description: 'Comanda tu flota de barcos para ser el mejor en el mar.',
      cover: DEFAULT_COVERS['battleship'],
      route: '/play/battleship',
    },
    {
      slug: 'chess',
      title: 'Board King',
      description: 'Sé el rey del tablero y sé el primero del ranking.',
      cover: DEFAULT_COVERS['chess'],
      route: '/play/chess',
    },
  ]

  function normalizeGame(game) {
    const normalizedGame = {
      ...game,
      cover: game.cover || DEFAULT_COVERS[game.slug] || DEFAULT_COVERS['quiz'],
      route: game.route || `/play/${game.slug}`,
    }

    const forcedTitle = TITLE_OVERRIDES_BY_SLUG[normalizedGame.slug]
    if (forcedTitle) {
      normalizedGame.title = forcedTitle
    }

    return normalizedGame
  }

  async function fetchGames() {
    isLoading.value = true
    try {
      const { data } = await api.get('/games')
      const remoteGames = (data.data ?? []).map(normalizeGame)
      const bySlug = new Map(remoteGames.map(game => [game.slug, game]))

      for (const localGame of LOCAL_GAMES) {
        if (!bySlug.has(localGame.slug)) {
          bySlug.set(localGame.slug, normalizeGame(localGame))
        }
      }

      games.value = Array.from(bySlug.values())
    } catch (error) {
      console.error('Error fetching games:', error)
      // Fallback a los juegos base temporales por si la BD no está lista o falla
      if (games.value.length === 0) {
        games.value = LOCAL_GAMES.map(normalizeGame)
      }
    } finally {
      isLoading.value = false
    }
  }

  const gamesBySlug = computed(() => {
    return games.value.reduce((acc, game) => {
      acc[game.slug] = game
      return acc
    }, {})
  })

  return {
    games,
    isLoading,
    fetchGames,
    gamesBySlug,
  }
})
