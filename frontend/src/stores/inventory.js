import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import api from '../lib/axios'
import { useAuthStore } from './auth'

export const useInventoryStore = defineStore('inventory', () => {
  const auth = useAuthStore()
  
  const getInitialItems = () => {
    return {
      'td_emp': 0,
      'td_overclock': 0,
      'td_purge': 0,
      'clicker_autoclick': 0,
      'rpg_class_necromancer': 0,
      'rpg_class_berserker': 0,
      'rpg_class_archmage': 0,
      'rpg_class_assassin': 0,
      'rpg_total_gold': 0
    }
  }

  const items = ref(getInitialItems())
  const isLoading = ref(false)

  // Watch for auth changes to sync inventory
  watch(() => auth.user, (newUser) => {
    if (newUser && newUser.inventory) {
      items.value = { ...items.value, ...newUser.inventory }
    }
  }, { immediate: true })

  // Sync with backend when items change (debounced or on action)
  async function syncWithBackend() {
    if (!auth.isLoggedIn) return
    try {
      await api.post('/inventory/sync', { items: items.value })
    } catch (error) {
      console.error('Failed to sync inventory with backend', error)
    }
  }

  async function fetchInventory() {
    if (!auth.isLoggedIn) return
    isLoading.value = true
    try {
      const { data } = await api.get('/inventory')
      items.value = { ...items.value, ...data }
    } catch (error) {
      console.error('Failed to fetch inventory', error)
    } finally {
      isLoading.value = false
    }
  }

  async function addItems(id, amount) {
    if (items.value[id] === undefined) {
      items.value[id] = 0
    }
    items.value[id] += amount
    
    // Save to backend if logged in
    if (auth.isLoggedIn) {
      try {
        await api.post('/inventory/update', { item_key: id, quantity: amount })
      } catch (error) {
        console.error('Failed to update inventory item in backend', error)
      }
    }
  }

  async function useItem(id, amount = 1) {
    if (hasItem(id, amount)) {
      items.value[id] -= amount
      
      // Save to backend if logged in
      if (auth.isLoggedIn) {
        try {
          await api.post('/inventory/update', { item_key: id, quantity: -amount })
        } catch (error) {
          console.error('Failed to use inventory item in backend', error)
        }
      }
      return true
    }
    return false
  }

  function hasItem(id, amount = 1) {
    return items.value[id] !== undefined && items.value[id] >= amount
  }

  return {
    items,
    isLoading,
    addItems,
    useItem,
    hasItem,
    fetchInventory,
    syncWithBackend
  }
})
