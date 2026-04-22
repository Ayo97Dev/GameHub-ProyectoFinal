<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAchievementStore } from '../stores/achievement'
import api from '../lib/axios'
import BaseButton from '../components/ui/BaseButton.vue'

const authStore = useAuthStore()
const achievementStore = useAchievementStore()
const router    = useRouter()
const isLoading = ref(true)

const RARITY_BADGE = {
  common:    'bg-white text-retro-black border-retro-black dark:bg-transparent dark:text-retro-white dark:border-retro-white',
  uncommon:  'bg-neon-cyan/20 text-retro-black border-neon-cyan dark:text-neon-cyan dark:border-neon-cyan',
  rare:      'bg-neon-blue/20 text-retro-black border-neon-blue dark:text-neon-blue dark:border-neon-blue',
  epic:      'bg-neon-pink/20 text-retro-black border-neon-pink dark:text-neon-pink dark:border-neon-pink',
  legendary: 'bg-neon-yellow/20 text-retro-black border-neon-yellow dark:bg-neon-yellow/10 dark:text-neon-yellow dark:border-neon-yellow',
}

// Reset de progreso
const resetTarget   = ref(null)   // { slug, title }
const isResetting   = ref(false)

function confirmReset(stat) {
  resetTarget.value = { slug: stat.game.slug, title: stat.game.title }
}

async function executeReset() {
  if (!resetTarget.value) return
  isResetting.value = true
  try {
    await api.delete(`/games/${resetTarget.value.slug}/reset`)
    await Promise.all([
      authStore.fetchUser(true), 
      achievementStore.fetchAchievements(true)
    ])
  } catch { /* silencioso */ } finally {
    isResetting.value = false
    resetTarget.value = null
  }
}

onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }
  
  try {
    await Promise.all([
      authStore.fetchUser(),
      achievementStore.fetchAchievements()
    ])
  } catch { /* silencioso */ }
  isLoading.value = false
})

function formatTime(seconds) {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  return `${h}h ${m}m`
}

function formatDate(isoDate) {
  return new Date(isoDate).toLocaleDateString()
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-16 relative z-10 space-y-16">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none -z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(0,242,255,0.05),transparent_70%)] pointer-events-none -z-10"></div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-32 bg-retro-black/40 border-4 border-dashed border-white/5 space-y-8">
      <div class="relative size-20">
         <div class="absolute inset-0 border-4 border-neon-cyan/20"></div>
         <div class="absolute inset-0 border-t-4 border-neon-cyan animate-spin"></div>
      </div>
      <p class="text-neon-cyan font-pixel text-2xl uppercase tracking-[0.6em] blink">LOADING_PROFILE_DATA...</p>
    </div>

    <template v-else-if="authStore.user">
      <!-- Profile Header -->
      <div class="bg-black border-4 border-retro-black p-10 shadow-[20px_20px_0px_#000] relative overflow-hidden flex flex-col sm:flex-row items-center gap-10">
        <!-- Corner Ornaments -->
        <div class="absolute -top-1 -left-1 size-10 border-t-4 border-l-4 border-neon-cyan"></div>
        <div class="absolute -bottom-1 -right-1 size-10 border-b-4 border-r-4 border-neon-cyan"></div>
        
        <div class="absolute inset-0 bg-[linear-gradient(rgba(0,242,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(0,242,255,0.02)_1px,transparent_1px)] bg-[size:40px_40px]"></div>

        <div class="relative z-10 size-32 border-4 border-neon-cyan flex items-center justify-center text-6xl font-display font-black text-black bg-neon-cyan shrink-0 shadow-[8px_8px_0px_#000]">
          {{ authStore.user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="relative z-10 text-center sm:text-left space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
             <span class="font-pixel text-xs text-neon-yellow tracking-[0.5em] uppercase animate-pulse">SISTEMA_AUTORIZADO</span>
             <div class="h-1 flex-1 bg-white/5 hidden sm:block min-w-[100px]"></div>
          </div>
          <h1 class="text-5xl sm:text-7xl font-display font-black uppercase text-white tracking-tighter leading-none gh-title-glow">{{ authStore.user.name }}</h1>
          <p class="font-sans text-xs font-bold text-white/30 tracking-[0.2em] uppercase">ACCESS_LEVEL: ADMIN // ID: {{ authStore.user.email }}</p>
        </div>
      </div>

      <div class="space-y-10">
        <div class="flex items-center gap-6 border-b-4 border-retro-black pb-6">
           <div class="size-12 bg-neon-cyan/10 border-2 border-neon-cyan/20 flex items-center justify-center text-neon-cyan">
              <Icon icon="lucide:database" class="text-2xl" />
           </div>
           <h2 class="font-display text-3xl font-black uppercase tracking-tighter text-white">Archivo_De_Estado</h2>
        </div>
        
        <div v-if="!authStore.user.global_stats || authStore.user.global_stats.length === 0" class="py-24 flex flex-col items-center justify-center bg-retro-black/40 border-4 border-white/5 space-y-6">
          <Icon icon="lucide:terminal" class="text-6xl text-white/10" />
          <p class="text-white/30 font-pixel text-xl uppercase tracking-[0.4em]">NO DATA ON SERVER. INSERT COIN TO PLAY.</p>
        </div>

        <div v-else class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
          <article 
            v-for="stat in authStore.user.global_stats" 
            :key="stat.game_id" 
            class="bg-black border-4 border-retro-black p-8 transition-all relative overflow-hidden group shadow-[12px_12px_0px_#000] hover:border-white/10"
          >
            <!-- Background Decoration -->
            <div class="absolute top-0 right-0 size-16 bg-white/[0.02] -rotate-45 translate-x-8 -translate-y-8 pointer-events-none"></div>

            <h3 class="font-display text-2xl font-black uppercase text-neon-cyan tracking-tight mb-8 border-b-2 border-white/5 pb-4 group-hover:text-white transition-colors">{{ stat.game.title }}</h3>

            <!-- Stats -->
            <div class="space-y-4">
              <div class="flex justify-between items-center bg-retro-black p-4 border border-white/5 relative">
                <div class="absolute left-0 top-0 h-full w-[2px] bg-neon-pink shadow-[0_0_8px_#ff2d55]"></div>
                <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em]">MÁX_PUNTUACIÓN</span>
                <span class="font-display text-xl font-black text-white">{{ Number(stat.high_score).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between items-center bg-retro-black p-4 border border-white/5 relative">
                <div class="absolute left-0 top-0 h-full w-[2px] bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
                <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em]">TIEMPO_TOTAL</span>
                <span class="font-display text-xl font-black text-white">{{ formatTime(stat.time_played) }}</span>
              </div>
              <div class="flex justify-between items-center bg-retro-black p-4 border border-white/5 relative">
                <div class="absolute left-0 top-0 h-full w-[2px] bg-neon-yellow shadow-[0_0_8px_#fff200]"></div>
                <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em]">ÚLTIMA_SESIÓN</span>
                <span class="font-display text-lg font-black text-white">{{ formatDate(stat.last_played_at) }}</span>
              </div>
            </div>

            <!-- Logros del juego -->
            <div v-if="achievementStore.achievementsByGame[stat.game_id]?.length" class="mt-8 pt-8 border-t border-white/5">
              <div class="flex items-center justify-between mb-4">
                <span class="font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/30">LOGROS_ADQUIRIDOS</span>
                <div class="bg-neon-cyan text-black px-2 py-0.5 font-pixel text-[10px] font-black">
                   {{ achievementStore.achievementsByGame[stat.game_id].filter(a => a.unlocked).length }}/{{ achievementStore.achievementsByGame[stat.game_id].length }}
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="a in achievementStore.achievementsByGame[stat.game_id]"
                  :key="a.id"
                  :title="a.title"
                  class="size-10 flex items-center justify-center border transition-all relative group/ach"
                  :class="[
                    a.unlocked ? 'border-neon-cyan/50 bg-neon-cyan/5 text-neon-cyan' : 'border-white/5 bg-transparent text-white/10 border-dashed'
                  ]"
                >
                  <span class="font-pixel text-xl">{{ a.unlocked ? '★' : '✕' }}</span>
                  <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 p-2 bg-black border-2 border-neon-cyan hidden group-hover/ach:block z-50 w-32 pointer-events-none">
                     <p class="font-display text-[10px] font-black text-white uppercase">{{ a.title }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botón reset -->
            <div class="mt-10">
              <button 
                class="w-full py-4 bg-white/5 border border-white/10 text-white/40 hover:bg-neon-pink/10 hover:border-neon-pink hover:text-neon-pink font-display text-[10px] font-black uppercase tracking-widest transition-all group/reset" 
                @click="confirmReset(stat)"
              >
                <span class="group-hover/reset:animate-pulse">ELIMINAR_REGISTROS_LOCALES</span>
              </button>
            </div>
          </article>
        </div>
      </div>
    </template>
  </section>

  <!-- Modal de confirmación de reset -->
  <Teleport to="body">
    <Transition name="pixel-fade">
      <div
        v-if="resetTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-retro-deep/90 backdrop-blur-md p-4"
        @click.self="resetTarget = null"
      >
        <div class="w-full max-w-md bg-black border-4 border-neon-pink p-12 shadow-[24px_24px_0px_#000] relative overflow-hidden">
          <div class="absolute -top-1 -left-1 size-10 border-t-4 border-l-4 border-neon-pink"></div>
          
          <h3 class="font-display text-4xl font-black uppercase tracking-tighter text-neon-pink mb-6 leading-none gh-title-glow">ALERTA_CRÍTICA</h3>
          <div class="h-1 w-full bg-neon-pink/20 mb-8 overflow-hidden">
             <div class="h-full bg-neon-pink w-1/2 animate-pulse"></div>
          </div>
          
          <p class="font-sans text-xs font-bold uppercase text-white/60 leading-relaxed mb-10 tracking-widest">
            EL SISTEMA ELIMINARÁ PERMANENTEMENTE TODOS LOS REGISTROS Y PROGRESO PARA: 
            <span class="text-neon-pink bg-neon-pink/10 px-2 py-1 inline-block mt-4 text-sm font-black">{{ resetTarget.title }}</span>
            <br><br>ESTA ACCIÓN ES IRREVERSIBLE. ¿CONTINUAR?
          </p>
          
          <div class="grid grid-cols-2 gap-4">
            <button 
              @click="resetTarget = null" 
              class="py-4 border-2 border-white/10 text-white/40 font-display text-[10px] font-black uppercase tracking-widest hover:bg-white/5 transition-all"
            >
              ABORTAR
            </button>
            <button 
              @click="executeReset" 
              :disabled="isResetting"
              class="py-4 bg-neon-pink text-black font-display text-[10px] font-black uppercase tracking-widest shadow-[6px_6px_0px_#000] hover:translate-x-[-3px] hover:translate-y-[-3px] hover:shadow-[9px_9px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all"
            >
              {{ isResetting ? 'BORRANDO...' : 'CONFIRMAR' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.blink {
  animation: blink 1.5s step-start infinite;
}
@keyframes blink {
  50% { opacity: 0; }
}
.pixel-fade-enter-active, .pixel-fade-leave-active { transition: opacity 0.1s step-end; }
.pixel-fade-enter-from, .pixel-fade-leave-to       { opacity: 0; }
</style>
