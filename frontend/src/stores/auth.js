/**
 * AUTH STORE
 * 
 * Gestiona el estado de autenticación del usuario, persistencia de tokens 
 * y sincronización con el perfil del backend.
 */
import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../lib/axios'

export const useAuthStore = defineStore('auth', () => {
  // Estado inicial recuperado de localStorage para evitar parpadeos en carga.
  const storedUser = localStorage.getItem('user')
  const isLoggedIn = ref(!!localStorage.getItem('token'))
  const user = ref(storedUser ? JSON.parse(storedUser) : null)
  const token = ref(localStorage.getItem('token') || null)
  const isLoggingOut = ref(false)

  /**
   * INICIO DE SESIÓN
   * Autentica al usuario y guarda el token tanto en memoria como en localStorage.
   */
  async function login(credentials) {
    const { data } = await api.post('/login', credentials)
    setToken(data.access_token)
    user.value = data.user
    localStorage.setItem('user', JSON.stringify(data.user))
  }

  /**
   * REGISTRO
   * Crea una nueva cuenta e inicia sesión automáticamente.
   */
  async function register(userData) {
    const { data } = await api.post('/register', userData)
    setToken(data.access_token)
    user.value = data.user
    localStorage.setItem('user', JSON.stringify(data.user))
  }

  const hasFetched = ref(false)
  let pendingFetch = null

  /**
   * SINCRONIZACIÓN DE PERFIL
   * Recupera los datos más recientes del usuario desde el servidor.
   * Utiliza una promesa pendiente (pendingFetch) para evitar llamadas redundantes.
   */
  async function fetchUser(force = false) {
    if (!token.value) return null
    if (!force && hasFetched.value && user.value) return user.value
    if (!force && pendingFetch) return pendingFetch

    pendingFetch = (async () => {
      try {
        const { data } = await api.get('/user')
        user.value = data.data
        localStorage.setItem('user', JSON.stringify(data.data))
        isLoggedIn.value = true
        hasFetched.value = true
      } catch (error) {
        // Si el token es inválido (ej: expirado), forzamos logout local.
        logout()
      } finally {
        pendingFetch = null
      }
      return user.value
    })()

    return pendingFetch
  }

  /**
   * CIERRE DE SESIÓN
   * Limpia el estado local y notifica al servidor para invalidar el token.
   */
  async function logout() {
    // Protección contra race conditions en el proceso de salida.
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
          console.warn('[auth] error al llamar /logout:', error?.message)
        }
      }
      setToken(null)
      user.value = null
      localStorage.removeItem('user')
    } finally {
      isLoggingOut.value = false
    }
  }

  /**
   * GESTIÓN DE TOKEN
   * Centraliza la lógica de guardado/borrado en localStorage.
   */
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
