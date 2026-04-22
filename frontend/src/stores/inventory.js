import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useInventoryStore = defineStore('inventory', () => {
  const getInitialItems = () => {
    const saved = localStorage.getItem('gamehub_inventory')
    if (saved) {
      try {
        return JSON.parse(saved)
      } catch (e) {
        console.error('Failed to parse inventory from localStorage', e)
      }
    }
    // Initialize with 0 for all items if not found
    return {
      'td_emp': 0,
      'td_overclock': 0,
      'td_purge': 0,
      'clicker_autoclick': 0
    }
  }

  const items = ref(getInitialItems())

  // Automatically save to localStorage when items change
  watch(items, (newItems) => {
    localStorage.setItem('gamehub_inventory', JSON.stringify(newItems))
  }, { deep: true })

  function addItems(id, amount) {
    if (items.value[id] === undefined) {
      items.value[id] = 0
    }
    items.value[id] += amount
  }

  function useItem(id, amount = 1) {
    if (hasItem(id, amount)) {
      items.value[id] -= amount
      return true
    }
    return false
  }

  function hasItem(id, amount = 1) {
    return items.value[id] !== undefined && items.value[id] >= amount
  }

  return {
    items,
    addItems,
    useItem,
    hasItem
  }
})
