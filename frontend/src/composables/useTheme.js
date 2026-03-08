import { ref, watch } from 'vue'

const isDark = ref(typeof window !== 'undefined' ? localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) : true)

function applyTheme() {
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

// Initial apply
if (typeof window !== 'undefined') {
  applyTheme()
}

export function useTheme() {
  function toggleTheme() {
    isDark.value = !isDark.value
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    applyTheme()
  }

  return {
    isDark,
    toggleTheme
  }
}
