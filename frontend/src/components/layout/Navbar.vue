<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { Icon } from '@iconify/vue'
import BaseButton from '../ui/BaseButton.vue'

const authStore = useAuthStore()
const gameStore = useGameStore()
const router = useRouter()
const navGames = computed(() => gameStore.games)
const isLoggingOut = ref(false)

onMounted(() => {
  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})

async function handleLogout() {
  if (isLoggingOut.value) return // Prevenir múltiples clics
  
  isLoggingOut.value = true
  try {
    await authStore.logout()
    router.push('/')
  } catch (error) {
    console.error('Error during logout:', error)
    // El logout ya se ejecutó, redirigir de todas formas
    router.push('/')
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <header class="sticky top-0 z-[100] border-b-4 border-retro-black bg-black transition-all">
    <nav class="mx-auto flex w-full max-w-[100rem] flex-col gap-6 px-6 py-4 sm:flex-row sm:items-center sm:justify-between relative overflow-hidden">
      <!-- Decorator Line -->
      <div class="absolute bottom-0 left-0 h-[2px] w-full bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>

      <div class="flex items-center justify-between gap-6 relative">
        <RouterLink to="/" class="group flex items-center gap-4">
           <div class="size-10 bg-neon-cyan border-2 border-black shadow-[4px_4px_0px_#fff200] flex items-center justify-center group-hover:translate-x-[-2px] group-hover:translate-y-[-2px] group-hover:shadow-[6px_6px_0px_#fff200] transition-all">
              <Icon icon="lucide:zap" class="text-2xl text-black" />
           </div>
           <span class="text-4xl font-display font-black tracking-tighter uppercase text-white gh-title-glow group-hover:text-neon-cyan transition-colors">
             GameHub
           </span>
        </RouterLink>
        <div class="flex items-center gap-2">
           <span class="size-2 bg-neon-green shadow-[0_0_8px_#22c55e] animate-pulse"></span>
           <span class="font-pixel text-[10px] text-white/40 uppercase tracking-[0.2em]">SYS_OK</span>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-4 sm:gap-8">
        <div class="flex flex-wrap items-center gap-4">
          <RouterLink
            to="/"
            class="px-4 py-2 font-display text-xs font-black uppercase tracking-[0.2em] text-white/60 hover:text-white transition-all border-b-2 border-transparent relative group"
            active-class="!text-neon-cyan !border-neon-cyan"
          >
            Terminal
            <div class="absolute -top-1 -right-1 size-1 bg-neon-cyan opacity-0 group-hover:opacity-100"></div>
          </RouterLink>

          <RouterLink
            to="/store"
            class="px-4 py-2 font-display text-xs font-black uppercase tracking-[0.2em] text-white/60 hover:text-white transition-all border-b-2 border-transparent relative group"
            active-class="!text-neon-yellow !border-neon-yellow"
          >
            Arsenal
            <div class="absolute -top-1 -right-1 size-1 bg-neon-yellow opacity-0 group-hover:opacity-100"></div>
          </RouterLink>

          <div class="h-6 w-px bg-white/10 mx-2"></div>

          <template v-if="authStore.isLoggedIn">
            <RouterLink
              to="/profile"
              class="flex items-center gap-3 px-4 py-2 bg-white/5 border border-white/5 hover:border-neon-cyan hover:bg-neon-cyan/5 transition-all group"
              active-class="!bg-neon-cyan/10 !border-neon-cyan !text-neon-cyan"
            >
              <Icon icon="lucide:user" class="text-lg text-white/40 group-hover:text-neon-cyan transition-colors" />
              <span class="font-display text-xs font-black uppercase tracking-widest text-white">{{ authStore.user.name }}</span>
            </RouterLink>
            
            <button 
              @click="handleLogout" 
              :disabled="isLoggingOut" 
              class="size-10 flex items-center justify-center bg-white/5 border border-white/5 text-white/40 hover:text-neon-pink hover:border-neon-pink hover:bg-neon-pink/10 transition-all shadow-[4px_4px_0px_#000]"
            >
              <Icon v-if="isLoggingOut" icon="lucide:loader-2" class="animate-spin text-xl" />
              <Icon v-else icon="lucide:log-out" class="text-xl" />
            </button>
          </template>
          
          <template v-else>
            <RouterLink to="/login" class="px-6 py-2 border-2 border-white/10 font-display text-xs font-black text-white/60 hover:text-white hover:border-white/20 transition-all uppercase tracking-widest">
              Conectar
            </RouterLink>
            <RouterLink to="/register" class="px-6 py-2 bg-neon-pink text-black font-display text-xs font-black uppercase tracking-widest shadow-[4px_4px_0px_#000] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all">
              Registrar
            </RouterLink>
          </template>
        </div>
      </div>
    </nav>
  </header>
</template>
