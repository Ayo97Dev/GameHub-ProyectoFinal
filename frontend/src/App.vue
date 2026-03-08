<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Navbar from './components/layout/Navbar.vue'
import Footer from './components/layout/Footer.vue'
import { useAuthStore } from './stores/auth'
import { useTheme } from './composables/useTheme'

const authStore = useAuthStore()
// Initialize theme
useTheme()

onMounted(() => {
  if (authStore.token) {
    authStore.fetchUser()
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />
    <main class="flex-1">
      <RouterView />
    </main>
    <Footer />
  </div>
</template>
