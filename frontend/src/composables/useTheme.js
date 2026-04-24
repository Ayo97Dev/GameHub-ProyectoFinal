import { ref } from 'vue'

const isDark = ref(true)

function applyTheme() {
  if (typeof document !== 'undefined') {
    document.documentElement.classList.add('dark')
  }
}

// Initial apply
if (typeof window !== 'undefined') {
  applyTheme()
}

export function useTheme() {
  return {
    isDark
  }
}
