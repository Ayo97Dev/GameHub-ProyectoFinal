import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export const useGameStore = defineStore('game', () => {
  const games = ref([
    {
      slug: 'rpg',
      title: 'Dungeon Realms RPG',
      description: 'Explora mazmorras oscuras y sube de nivel a tu héroe.',
      cover: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80',
      route: '/play/rpg',
    },
    {
      slug: 'clicker',
      title: 'Neon Clicker Rush',
      description: 'Haz clic, mejora tu producción y domina el ranking.',
      cover: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
      route: '/play/clicker',
    },
    {
      slug: 'arcade',
      title: 'Cyber Arcade Blaster',
      description: 'Acción arcade retro con reflejos al límite.',
      cover: 'https://images.unsplash.com/photo-1486572788966-cfd3df1f5b42?auto=format&fit=crop&w=1200&q=80',
      route: '/play/arcade',
    },
  ])

  const gamesBySlug = computed(() => {
    return games.value.reduce((acc, game) => {
      acc[game.slug] = game
      return acc
    }, {})
  })

  return {
    games,
    gamesBySlug,
  }
})
