<script setup>
/**
 * PROFILE VIEW
 * 
 * Panel de control central del usuario.
 * Visualiza estadísticas globales, logros desbloqueados y gestiona ajustes de cuenta.
 */
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '../stores/auth'
import { useAchievementStore } from '../stores/achievement'
import { useGameStore } from '../stores/game'
import { useLeaderboardStore } from '../stores/leaderboard'
import api from '../lib/axios'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseLoading from '../components/ui/BaseLoading.vue'


const authStore = useAuthStore()
const achievementStore = useAchievementStore()
const gameStore = useGameStore()
const leaderboardStore = useLeaderboardStore()
const router = useRouter()

const isLoading = ref(true)
const activeTab = ref('stats') // 'stats' | 'settings'

/**
 * CÁLCULO DE MÉTRICAS GLOBALES
 * Agregación de tiempo de juego y progreso en la colección de logros.
 */
const totalTimePlayed = computed(() => {
  return authStore.user?.global_stats?.reduce((acc, s) => acc + s.time_played, 0) || 0
})

const totalAchievementsCount = computed(() => achievementStore.achievements.length)
const unlockedAchievementsCount = computed(() => achievementStore.achievements.filter(a => a.unlocked).length)

/**
 * RESUMEN DE CLASIFICACIONES
 * Extrae las posiciones Top 10 del usuario en todos los juegos disponibles.
 */
const top3Ranks = computed(() => {
  const ranks = []
  if (!authStore.user) return []
  
  gameStore.games.forEach(game => {
    const entries = leaderboardStore.getEntries(game.slug)
    const userRankIndex = entries.findIndex(e => e.user_id === authStore.user.id)
    // Only show if in top 10 for the header summary
    if (userRankIndex >= 0 && userRankIndex < 10) {
      ranks.push({
        game: game.title,
        rank: userRankIndex + 1,
        slug: game.slug
      })
    }
  })
  // Sort by rank
  return ranks.sort((a, b) => a.rank - b.rank)
})

function getUserRank(slug) {
  if (!authStore.user) return null
  const entries = leaderboardStore.getEntries(slug)
  const index = entries.findIndex(e => e.user_id === authStore.user.id)
  return index >= 0 ? index + 1 : null
}

// Settings Form
const settingsForm = ref({
  name: '',
  bio: '',
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const avatarPreview = ref(null)
const avatarFile = ref(null)
const isUpdatingProfile = ref(false)
const isUpdatingPassword = ref(false)

function onAvatarChange(e) {
  const file = e.target.files[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}

async function updateProfile() {
  isUpdatingProfile.value = true
  const formData = new FormData()
  formData.append('name', settingsForm.value.name)
  formData.append('bio', settingsForm.value.bio)
  if (avatarFile.value) {
    formData.append('avatar', avatarFile.value)
  }

  try {
    const { data } = await api.post('/user/profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    authStore.user = data.user
    alert('Identidad actualizada con éxito')
  } catch (err) {
    alert('Error al actualizar el perfil: ' + (err.response?.data?.message || err.message))
  } finally {
    isUpdatingProfile.value = false
  }
}

async function updatePassword() {
  isUpdatingPassword.value = true
  try {
    await api.post('/user/password', passwordForm.value)
    alert('Seguridad reforzada. Contraseña actualizada.')
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (err) {
    alert('Error de seguridad: ' + (err.response?.data?.message || err.message))
  } finally {
    isUpdatingPassword.value = false
  }
}

// Reset de progreso
const resetTarget = ref(null)
const isResetting = ref(false)

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
      achievementStore.fetchAchievements(),
      gameStore.fetchGames()
    ])
    
    // Initialize form
    settingsForm.value.name = authStore.user.name
    settingsForm.value.bio = authStore.user.bio || ''
    
    // Fetch all leaderboards to check ranks
    gameStore.games.forEach(g => {
      leaderboardStore.fetchLeaderboard(g.slug)
    })
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

const RARITY_BADGE = {
  common:    'bg-white text-retro-black border-retro-black',
  uncommon:  'bg-neon-cyan/20 text-neon-cyan border-neon-cyan',
  rare:      'bg-neon-blue/20 text-neon-blue border-neon-blue',
  epic:      'bg-neon-pink/20 text-neon-pink border-neon-pink',
  legendary: 'bg-neon-yellow/10 text-neon-yellow border-neon-yellow',
}
</script>

<template>
  <section class="mx-auto w-full max-w-7xl px-4 py-10 min-h-screen">
    <BaseLoading 
      v-if="isLoading" 
      message="Sincronizando Perfil..." 
      submessage="Estableciendo conexión segura con la red central" 
    />


    <template v-else-if="authStore.user">
      
      <!-- TOP HUD: Tabs & Logout -->
      <div class="flex flex-wrap items-end justify-between gap-6 mb-12 border-b-4 border-white/5">
         <div class="flex gap-1">
            <button 
               @click="activeTab = 'stats'" 
               class="px-8 py-3 font-display text-sm font-black uppercase tracking-widest transition-all border-t-4"
               :class="activeTab === 'stats' ? 'bg-black text-neon-cyan border-neon-cyan' : 'bg-transparent text-white/30 border-transparent hover:text-white'"
            >
               Resumen de Red
            </button>
            <button 
               @click="activeTab = 'settings'" 
               class="px-8 py-3 font-display text-sm font-black uppercase tracking-widest transition-all border-t-4"
               :class="activeTab === 'settings' ? 'bg-black text-neon-pink border-neon-pink' : 'bg-transparent text-white/30 border-transparent hover:text-white'"
            >
               Protocolos / Ajustes
            </button>
         </div>
         <div class="pb-3">
            <BaseButton variant="danger" size="sm" @click="authStore.logout()">Cerrar sesión</BaseButton>
         </div>
      </div>

      <!-- TAB: STATS -->
      <div v-if="activeTab === 'stats'" class="space-y-12">
         
         <!-- Global Diagnostics Card -->
         <div class="gh-panel p-8 bg-black border-4 border-neon-cyan shadow-[12px_12px_0px_#000] relative overflow-hidden">
            <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none"></div>
            <div class="flex flex-col md:flex-row items-center gap-10 relative z-10">
               
               <!-- Avatar Display -->
               <div class="relative group">
                  <div class="size-32 bg-retro-dark border-4 border-neon-cyan flex items-center justify-center text-5xl font-display font-black text-neon-cyan overflow-hidden shadow-[6px_6px_0px_#000]">
                    <img v-if="authStore.user.avatar" :src="authStore.user.avatar" class="w-full h-full object-cover" />
                    <span v-else>{{ authStore.user.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div class="absolute -bottom-2 -right-2 bg-neon-yellow p-1.5 border-2 border-black">
                     <Icon icon="lucide:verified" class="text-black text-lg" />
                  </div>
               </div>

               <!-- Identity & Global Stats -->
               <div class="flex-1 text-center md:text-left space-y-4">
                  <div>
                     <h1 class="text-5xl font-display font-black uppercase text-white tracking-tighter gh-title-glow">{{ authStore.user.name }}</h1>
                     <p v-if="authStore.user.bio" class="font-sans text-sm font-bold text-white/40 uppercase mt-2 italic max-w-xl">"{{ authStore.user.bio }}"</p>
                  </div>

                  <div class="flex flex-wrap justify-center md:justify-start gap-4">
                     <div class="bg-retro-dark border-2 border-white/10 px-4 py-2">
                        <p class="font-pixel text-[9px] text-white/30 uppercase tracking-widest mb-1">Horas Totales</p>
                        <p class="font-display text-2xl font-black text-neon-cyan">{{ formatTime(totalTimePlayed) }}</p>
                     </div>
                     <div class="bg-retro-dark border-2 border-white/10 px-4 py-2">
                        <p class="font-pixel text-[9px] text-white/30 uppercase tracking-widest mb-1">Logros Ganados</p>
                        <p class="font-display text-2xl font-black text-neon-pink">{{ unlockedAchievementsCount }} / {{ totalAchievementsCount }}</p>
                     </div>
                     <div v-if="top3Ranks.length > 0" class="bg-retro-dark border-2 border-neon-yellow/30 px-4 py-3">
                        <p class="font-pixel text-[9px] text-neon-yellow uppercase tracking-widest mb-2 flex items-center gap-2">
                           <Icon icon="lucide:trophy" class="text-xs" />
                           Clasificaciones Top
                        </p>
                        <div class="flex flex-wrap gap-3">
                           <div v-for="rank in top3Ranks" :key="rank.slug" class="flex items-center gap-2 bg-black/40 pr-3 border border-white/5 hover:border-neon-yellow/50 transition-colors group">
                              <div class="size-7 flex items-center justify-center font-display font-black text-xs transition-colors"
                                 :class="{
                                    'bg-neon-yellow text-black': rank.rank === 1,
                                    'bg-neon-cyan text-black': rank.rank === 2,
                                    'bg-white text-black': rank.rank === 3,
                                    'bg-retro-black text-white/50 border border-white/10': rank.rank > 3
                                 }"
                              >
                                 {{ rank.rank }}º
                              </div>
                              <span class="font-pixel text-[10px] text-white/70 uppercase group-hover:text-white transition-colors">{{ rank.game }}</span>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Game Breakdown -->
         <div class="space-y-8">
            <h2 class="font-display text-2xl font-black uppercase tracking-widest text-white border-l-8 border-neon-cyan pl-4">Módulos de Juego Activos</h2>
            
            <div v-if="!authStore.user.global_stats || authStore.user.global_stats.length === 0" class="gh-panel text-center py-20 bg-black/40 border-4 border-dashed border-white/10">
              <Icon icon="lucide:gamepad-2" class="text-6xl text-white/5 mx-auto mb-4" />
              <p class="font-pixel text-xl text-white/20 uppercase tracking-[0.3em]">No hay datos de telemetría disponibles</p>
              <RouterLink to="/" class="mt-6 inline-block text-neon-cyan font-display text-sm font-black uppercase underline underline-offset-8">Ir a jugar</RouterLink>
            </div>

            <div v-else class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
               <article 
                  v-for="stat in authStore.user.global_stats" 
                  :key="stat.game_id" 
                  class="gh-panel group flex flex-col p-6 bg-black border-4 border-retro-black hover:border-neon-cyan transition-all shadow-[8px_8px_0px_#000] relative overflow-hidden"
               >
                  <div class="gh-scanlines absolute inset-0 opacity-5 pointer-events-none"></div>
                   <div class="flex items-center justify-between border-b-2 border-white/10 pb-2 mb-6">
                      <h3 class="font-display text-2xl font-black uppercase text-white">{{ stat.game.title }}</h3>
                      
                      <div v-if="getUserRank(stat.game.slug)" class="flex items-center gap-2 bg-retro-dark px-2 py-1 border border-white/10">
                         <span class="font-pixel text-[9px] text-white/40 uppercase">Rank</span>
                         <span class="font-display text-sm font-black" 
                            :class="{
                               'text-neon-yellow': getUserRank(stat.game.slug) === 1,
                               'text-neon-cyan': getUserRank(stat.game.slug) === 2,
                               'text-white': getUserRank(stat.game.slug) === 3,
                               'text-white/40': getUserRank(stat.game.slug) > 3
                            }"
                         >
                            #{{ getUserRank(stat.game.slug) }}
                         </span>
                      </div>
                   </div>

                  <!-- Individual Stats Grid -->
                  <div class="grid grid-cols-2 gap-3 mb-8">
                     <div class="bg-retro-dark p-3 border-2 border-white/5">
                        <p class="font-pixel text-[9px] text-white/30 uppercase tracking-widest">Puntuación</p>
                        <p class="font-sans text-lg font-black text-neon-pink">{{ stat.high_score.toLocaleString() }}</p>
                     </div>
                     <div class="bg-retro-dark p-3 border-2 border-white/5">
                        <p class="font-pixel text-[9px] text-white/30 uppercase tracking-widest">Tiempo</p>
                        <p class="font-sans text-lg font-black text-neon-cyan">{{ formatTime(stat.time_played) }}</p>
                     </div>
                  </div>

                  <!-- Mini Achievements Grid -->
                  <div v-if="achievementStore.achievementsByGame[stat.game_id]?.length" class="space-y-3 mb-8">
                     <div class="flex justify-between items-center">
                        <p class="font-pixel text-[10px] font-bold uppercase tracking-widest text-white/40">Logros</p>
                        <span class="font-pixel text-[10px] bg-neon-cyan text-black px-1.5 font-bold">
                           {{ achievementStore.achievementsByGame[stat.game_id].filter(a => a.unlocked).length }} / {{ achievementStore.achievementsByGame[stat.game_id].length }}
                        </span>
                     </div>
                     <div class="flex flex-wrap gap-2">
                        <div
                           v-for="a in achievementStore.achievementsByGame[stat.game_id].slice(0, 8)"
                           :key="a.id"
                           class="size-8 border-2 flex items-center justify-center text-xs transition-all relative"
                           :class="a.unlocked ? (RARITY_BADGE[a.rarity] || 'border-white') : 'opacity-10 border-white grayscale'"
                           :title="a.title"
                        >
                           <Icon :icon="a.unlocked ? 'lucide:award' : 'lucide:lock'" />
                        </div>
                        <div v-if="achievementStore.achievementsByGame[stat.game_id].length > 8" class="size-8 bg-white/5 border-2 border-white/10 flex items-center justify-center font-pixel text-[9px] text-white/30">
                           +{{ achievementStore.achievementsByGame[stat.game_id].length - 8 }}
                        </div>
                     </div>
                  </div>

                  <!-- Actions -->
                  <div class="mt-auto flex gap-3">
                     <RouterLink :to="`/play/${stat.game.slug}`" class="flex-1">
                        <BaseButton size="sm" class="w-full">Jugar</BaseButton>
                     </RouterLink>
                     <button @click="confirmReset(stat)" class="size-10 flex items-center justify-center bg-white/5 border-2 border-white/10 text-white/30 hover:border-neon-pink hover:text-neon-pink transition-all" title="Resetear datos">
                        <Icon icon="lucide:refresh-cw" class="text-lg" />
                     </button>
                  </div>
               </article>
            </div>
         </div>
      </div>

      <!-- TAB: SETTINGS -->
      <div v-else class="max-w-4xl mx-auto space-y-12">
         
         <!-- Profile Identity Settings -->
         <div class="gh-panel p-8 bg-black border-4 border-neon-cyan shadow-[12px_12px_0px_#000]">
            <h2 class="font-display text-2xl font-black uppercase tracking-widest text-neon-cyan mb-8 border-b-2 border-neon-cyan/20 pb-4">Protocolo de Identidad</h2>
            
            <form @submit.prevent="updateProfile" class="grid grid-cols-1 md:grid-cols-3 gap-10">
               <!-- Avatar Column -->
               <div class="flex flex-col items-center gap-4">
                  <div class="relative group cursor-pointer" @click="$refs.avatarInput.click()">
                     <div class="size-40 bg-retro-dark border-4 border-neon-cyan flex items-center justify-center text-6xl font-display font-black text-neon-cyan overflow-hidden shadow-[6px_6px_0px_#000]">
                        <img v-if="avatarPreview || authStore.user.avatar" :src="avatarPreview || authStore.user.avatar" class="w-full h-full object-cover" />
                        <span v-else>{{ authStore.user.name.charAt(0).toUpperCase() }}</span>
                     </div>
                     <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                        <Icon icon="lucide:camera" class="text-3xl text-neon-cyan" />
                     </div>
                  </div>
                  <input type="file" ref="avatarInput" class="hidden" accept="image/*" @change="onAvatarChange" />
                  <p class="font-pixel text-[9px] text-white/30 uppercase text-center">Formato: JPG/PNG/GIF<br/>Max: 2MB</p>
               </div>

               <!-- Form Fields -->
               <div class="md:col-span-2 space-y-6">
                  <div class="space-y-2">
                     <label class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">Nombre de Usuario</label>
                     <input v-model="settingsForm.name" type="text" class="w-full bg-retro-dark border-2 border-white/10 p-3 font-sans font-bold text-white outline-none focus:border-neon-cyan transition-all uppercase" />
                  </div>
                  <div class="space-y-2">
                     <label class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">Bio / Bio-Interface</label>
                     <textarea v-model="settingsForm.bio" rows="4" class="w-full bg-retro-dark border-2 border-white/10 p-3 font-sans font-bold text-white outline-none focus:border-neon-cyan transition-all uppercase resize-none" placeholder="Escribe algo sobre ti..."></textarea>
                  </div>
                  <BaseButton type="submit" :disabled="isUpdatingProfile" class="w-full">
                     <Icon v-if="isUpdatingProfile" icon="svg-spinners:ring-resize" class="mr-2" />

                     Guardar Cambios
                  </BaseButton>
               </div>
            </form>
         </div>

         <!-- Security Settings -->
         <div class="gh-panel p-8 bg-black border-4 border-neon-pink shadow-[12px_12px_0px_#000]">
            <h2 class="font-display text-2xl font-black uppercase tracking-widest text-neon-pink mb-8 border-b-2 border-neon-pink/20 pb-4">Protocolos de Seguridad</h2>
            
            <form @submit.prevent="updatePassword" class="space-y-6">
               <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="space-y-2">
                     <label class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">Contraseña Actual</label>
                     <input v-model="passwordForm.current_password" type="password" class="w-full bg-retro-dark border-2 border-white/10 p-3 font-sans font-bold text-white outline-none focus:border-neon-pink transition-all" />
                  </div>
                  <div class="space-y-2">
                     <label class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">Nueva Contraseña</label>
                     <input v-model="passwordForm.password" type="password" class="w-full bg-retro-dark border-2 border-white/10 p-3 font-sans font-bold text-white outline-none focus:border-neon-pink transition-all" />
                  </div>
                  <div class="space-y-2">
                     <label class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">Confirmar Nueva</label>
                     <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-retro-dark border-2 border-white/10 p-3 font-sans font-bold text-white outline-none focus:border-neon-pink transition-all" />
                  </div>
               </div>
               <div class="flex justify-end">
                  <BaseButton type="submit" variant="danger" :disabled="isUpdatingPassword">
                     <Icon v-if="isUpdatingPassword" icon="svg-spinners:ring-resize" class="mr-2" />

                     Actualizar Seguridad
                  </BaseButton>
               </div>
            </form>
         </div>
      </div>

    </template>
  </section>

  <!-- Modal de confirmación de reset -->
  <Teleport to="body">
    <Transition name="pixel-fade">
      <div
        v-if="resetTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4"
        @click.self="resetTarget = null"
      >
        <div class="w-full max-w-sm gh-panel bg-black border-4 border-neon-pink shadow-[12px_12px_0px_#000]">
          <h3 class="font-display text-2xl font-black uppercase tracking-wider text-neon-pink mb-2 border-b-2 border-neon-pink pb-2">¡Atención!</h3>
          <p class="font-sans text-sm font-bold uppercase text-retro-white mb-6 mt-4 leading-relaxed">
            Se borrará todo tu progreso y estadísticas de 
            <span class="text-neon-pink bg-retro-dark px-1 inline-block">{{ resetTarget.title }}</span>. <br><br>Esta acción no se puede deshacer.
          </p>
          <div class="flex gap-3">
            <BaseButton variant="ghost" @click="resetTarget = null" class="flex-1">
              Cancelar
            </BaseButton>
            <BaseButton variant="danger" @click="executeReset" :disabled="isResetting" class="flex-1">
              <Icon v-if="isResetting" icon="svg-spinners:ring-resize" class="mr-2" />

              {{ isResetting ? 'Borrando...' : 'Confirmar' }}
            </BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.pixel-fade-enter-active,
.pixel-fade-leave-active {
  transition: opacity 0.3s steps(4);
}

.pixel-fade-enter-from,
.pixel-fade-leave-to {
  opacity: 0;
}
</style>
