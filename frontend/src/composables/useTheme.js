import { ref } from 'vue'

const isDark = ref(true)

function applyTheme() {
  if (typeof window !== 'undefined') {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  }
}

// Initial apply
applyTheme()

export function useTheme() {
  return {
    isDark,
    toggleTheme: () => {} // No-op
  }
}
