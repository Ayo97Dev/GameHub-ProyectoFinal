import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useAuthStore = defineStore('auth', () => {
  const isLoggedIn = ref(!!localStorage.getItem('token'))
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const isLoggingOut = ref(false)

  async function login(credentials) {
    const { data } = await api.post('/login', credentials)
    setToken(data.access_token)
    user.value = data.user
  }

  async function register(userData) {
    const { data } = await api.post('/register', userData)
    setToken(data.access_token)
    user.value = data.user
  }

  const hasFetched = ref(false)
  let pendingFetch = null

  async function fetchUser(force = false) {
    if (!token.value) return null
    if (!force && hasFetched.value && user.value) return user.value
    if (!force && pendingFetch) return pendingFetch

    pendingFetch = (async () => {
      try {
        const { data } = await api.get('/user')
        user.value = data.data
        isLoggedIn.value = true
        hasFetched.value = true
      } catch (error) {
        logout()
      } finally {
        pendingFetch = null
      }
      return user.value
    })()

    return pendingFetch
  }

  async function logout() {
    // Evitar múltiples logout concurrentes
    if (isLoggingOut.value) {
      console.warn('[auth] logout bloqueado: ya hay un logout en progreso')
      return
    }
    
    isLoggingOut.value = true
    try {
      if (token.value) {
        try {
          await api.post('/logout')
        } catch (error) {
          // Ignorar posibles errores al desloguear
          console.warn('[auth] error al llamar /logout:', error?.message)
        }
      }
      setToken(null)
      user.value = null
    } finally {
      isLoggingOut.value = false
    }
  }

  function setToken(newToken) {
    token.value = newToken
    isLoggedIn.value = !!newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  return {
    isLoggedIn,
    isLoggingOut,
    user,
    token,
    login,
    register,
    logout,
    fetchUser
  }
})
