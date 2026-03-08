import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useAuthStore = defineStore('auth', () => {
  const isLoggedIn = ref(!!localStorage.getItem('token'))
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

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

  async function fetchUser() {
    if (!token.value) return null
    try {
      const { data } = await api.get('/user')
      user.value = data.data
      isLoggedIn.value = true
    } catch (error) {
      logout()
    }
  }

  async function logout() {
    if (token.value) {
      try {
        await api.post('/logout')
      } catch (error) {
        // Ignorar posibles errores al desloguear
      }
    }
    setToken(null)
    user.value = null
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
    user,
    token,
    login,
    register,
    logout,
    fetchUser
  }
})
