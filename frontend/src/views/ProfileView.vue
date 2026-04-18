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
  <section class="mx-auto w-full max-w-7xl px-4 py-10">
    <div v-if="isLoading" class="text-center text-retro-black dark:text-retro-white py-20 font-pixel text-2xl uppercase blink">
      LOADING_PROFILE...
    </div>

    <template v-else-if="authStore.user">
      <!-- Profile Header -->
      <div class="gh-panel flex flex-col sm:flex-row items-center gap-6 overflow-hidden p-6 relative bg-retro-cream dark:bg-black mb-10">
        <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none"></div>
        <div class="relative z-10 h-24 w-24 border-4 border-retro-black dark:border-neon-cyan flex items-center justify-center text-5xl font-display font-black text-retro-black bg-neon-yellow dark:text-retro-black dark:bg-neon-cyan shrink-0 shadow-[4px_4px_0px_#09090b] dark:shadow-[4px_4px_0px_#f472b6]">
          {{ authStore.user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="relative z-10 text-center sm:text-left">
          <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-neon-blue dark:text-neon-yellow border-b-2 border-retro-black dark:border-neon-cyan mb-2 pb-1 inline-block">PLAYER_DATA</p>
          <h1 class="text-4xl font-display font-black uppercase text-retro-black dark:text-retro-white">{{ authStore.user.name }}</h1>
          <p class="font-sans text-sm font-bold mt-1 text-slate-600 dark:text-slate-400">ID: {{ authStore.user.email }}</p>
        </div>
      </div>

      <div class="mt-8">
        <h2 class="mb-6 font-display text-2xl font-black uppercase tracking-widest text-retro-black dark:text-retro-white border-b-4 border-retro-black dark:border-neon-cyan pb-2 inline-block">SYSTEM_STATS</h2>
        
        <div v-if="!authStore.user.global_stats || authStore.user.global_stats.length === 0" class="gh-panel text-center text-retro-black dark:text-retro-white font-pixel uppercase tracking-widest p-10 bg-retro-cream dark:bg-retro-dark">
          NO DATA ON SERVER. INSERT COIN TO PLAY.
        </div>

        <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <article 
            v-for="stat in authStore.user.global_stats" 
            :key="stat.game_id" 
            class="gh-panel flex flex-col gap-4 p-5 bg-white dark:bg-retro-dark"
          >
            <h3 class="font-display text-xl font-black uppercase text-retro-black dark:text-neon-cyan border-b-2 border-retro-black dark:border-neon-cyan pb-1">{{ stat.game.title }}</h3>

            <!-- Stats -->
            <div class="space-y-3 mt-2">
              <div class="flex justify-between items-center bg-retro-cream dark:bg-black px-3 py-2 border-2 border-retro-black dark:border-neon-pink shadow-[inset_2px_2px_0px_#09090b] dark:shadow-[inset_2px_2px_0px_#f472b6]">
                <span class="font-pixel text-xs text-retro-black dark:text-retro-white uppercase tracking-wider">HIGH_SCORE</span>
                <span class="font-sans font-bold text-retro-black dark:text-neon-pink">{{ stat.high_score }}</span>
              </div>
              <div class="flex justify-between items-center bg-retro-cream dark:bg-black px-3 py-2 border-2 border-retro-black dark:border-neon-cyan shadow-[inset_2px_2px_0px_#09090b] dark:shadow-[inset_2px_2px_0px_#22d3ee]">
                <span class="font-pixel text-xs text-retro-black dark:text-retro-white uppercase tracking-wider">TIME_PLAYED</span>
                <span class="font-sans font-bold text-retro-black dark:text-neon-cyan">{{ formatTime(stat.time_played) }}</span>
              </div>
              <div class="flex justify-between items-center bg-retro-cream dark:bg-black px-3 py-2 border-2 border-retro-black dark:border-neon-yellow shadow-[inset_2px_2px_0px_#09090b] dark:shadow-[inset_2px_2px_0px_#fef08a]">
                <span class="font-pixel text-xs text-retro-black dark:text-retro-white uppercase tracking-wider">LAST_LOGIN</span>
                <span class="font-sans font-bold text-retro-black dark:text-neon-yellow">{{ formatDate(stat.last_played_at) }}</span>
              </div>
            </div>

            <!-- Logros del juego -->
            <div v-if="achievementStore.achievementsByGame[stat.game_id]?.length" class="border-t-2 border-retro-black dark:border-neon-cyan border-dashed pt-4 mt-2">
              <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-retro-black dark:text-retro-white mb-3">
                ACHIEVEMENTS
                <span class="ml-2 bg-retro-black text-white dark:bg-neon-cyan dark:text-black px-1">
                  {{ achievementStore.achievementsByGame[stat.game_id].filter(a => a.unlocked).length }}/{{ achievementStore.achievementsByGame[stat.game_id].length }}
                </span>
              </p>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="a in achievementStore.achievementsByGame[stat.game_id]"
                  :key="a.id"
                  :title="a.title + (a.unlocked ? '\n✅ ' + (a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'Desbloqueado') : '\n🔒 Bloqueado') + '\n' + a.description"
                  class="flex items-center gap-1.5 border-2 px-2 py-0.5 font-sans text-[10px] font-bold uppercase tracking-wider transition-all"
                  :class="[
                    RARITY_BADGE[a.rarity] ?? RARITY_BADGE.common,
                    a.unlocked ? '' : 'opacity-40 grayscale border-dashed border-slate-400 dark:border-slate-600 bg-transparent'
                  ]"
                >
                  <span class="font-pixel">{{ a.unlocked ? '★' : 'x' }}</span>
                  <span class="max-w-28 truncate">{{ a.title }}</span>
                </div>
              </div>
            </div>

            <!-- Botón reset -->
            <div class="pt-4 mt-auto">
              <BaseButton size="sm" variant="danger" class="w-full" @click="confirmReset(stat)">
                RESET_DATA
              </BaseButton>
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        @click.self="resetTarget = null"
      >
        <div class="w-full max-w-sm gh-panel bg-white dark:bg-black border-4 border-retro-black dark:border-neon-pink">
          <h3 class="font-display text-2xl font-black uppercase tracking-wider text-neon-pink mb-2 border-b-2 border-neon-pink pb-2">WARNING!</h3>
          <p class="font-sans text-sm font-bold uppercase text-retro-black dark:text-retro-white mb-6 mt-4">
            SYSTEM WILL ERASE ALL PROGRESS AND STATS FOR 
            <span class="text-neon-pink bg-retro-black dark:bg-retro-dark px-1 inline-block">{{ resetTarget.title }}</span>. <br><br>THIS CANNOT BE UNDONE.
          </p>
          <div class="flex gap-3">
            <BaseButton variant="ghost" @click="resetTarget = null" class="flex-1">
              CANCEL
            </BaseButton>
            <BaseButton variant="danger" @click="executeReset" :disabled="isResetting" class="flex-1">
              {{ isResetting ? 'ERASING...' : 'CONFIRM' }}
            </BaseButton>
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
