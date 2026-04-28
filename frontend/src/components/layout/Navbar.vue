<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'

const authStore = useAuthStore()
const gameStore = useGameStore()
const router    = useRouter()
const route     = useRoute()
const isLoggingOut = ref(false)
const isMenuOpen   = ref(false)

onMounted(() => {
  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
})

// Cerrar menú al cambiar de ruta
watch(() => route.path, () => {
  isMenuOpen.value = false
})

async function handleLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  try {
    await authStore.logout()
    router.push('/')
  } catch {
    router.push('/')
  } finally {
    isLoggingOut.value = false
    isMenuOpen.value = false
  }
}

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-retro-black border-b-4 border-neon-cyan shadow-[0_8px_16px_rgba(0,0,0,0.5)] overflow-hidden">
    <!-- Scanlines -->
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-0"></div>
    
    <!-- Ambient glow line (bottom) -->
    <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-60"></div>

    <nav class="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4">

      <!-- ── Logo & System Branding ── -->
      <div class="flex items-center gap-6">
        <RouterLink
          to="/"
          class="font-display text-2xl sm:text-3xl font-black uppercase tracking-widest gh-title-gradient leading-none hover:brightness-110 transition-all flex items-center gap-2"
        >
          GameHub<span class="animate-blink text-pink-500/80 select-none">_</span>
        </RouterLink>
      </div>

      <!-- ── Desktop Nav Links (Hidden on Mobile) ── -->
      <div class="hidden md:flex items-center gap-2 lg:gap-4 flex-wrap justify-end">
        <!-- Juegos -->
        <RouterLink
          to="/"
          class="nav-link"
          :class="{ 'nav-link--active': route.path === '/' }"
        >
          <Icon icon="lucide:layout-grid" class="text-sm" />
          <span>Juegos</span>
        </RouterLink>

        <!-- Tienda -->
        <RouterLink
          to="/store"
          class="nav-link nav-link--yellow"
          :class="{ 'nav-link--active-yellow': route.path === '/store' }"
        >
          <Icon icon="lucide:shopping-bag" class="text-sm" />
          <span>Tienda</span>
        </RouterLink>

        <!-- Logros (solo si autenticado) -->
        <RouterLink
          v-if="authStore.isLoggedIn"
          to="/achievements"
          class="nav-link"
          :class="{ 'nav-link--active': route.path === '/achievements' }"
        >
          <Icon icon="lucide:award" class="text-sm" />
          <span>Logros</span>
        </RouterLink>

        <!-- ── Separador vertical ── -->
        <div class="h-8 w-px bg-white/10 mx-1"></div>

        <!-- Logged IN -->
        <template v-if="authStore.isLoggedIn">
          <RouterLink
            to="/profile"
            class="nav-link"
            :class="{ 'nav-link--active': route.path === '/profile' }"
          >
            <div class="size-4 bg-white/10 flex items-center justify-center border border-white/20">
               <Icon icon="lucide:user" class="text-xs" />
            </div>
            <span>Perfil</span>
          </RouterLink>

          <button
            class="nav-link nav-link--danger group"
            :disabled="isLoggingOut"
            @click="handleLogout"
          >
            <Icon :icon="isLoggingOut ? 'svg-spinners:ring-resize' : 'lucide:log-out'" class="text-sm" />
            <span>{{ isLoggingOut ? 'Saliendo…' : 'Salir' }}</span>
          </button>
        </template>

        <!-- Logged OUT -->
        <template v-else>
          <RouterLink to="/login" class="nav-link">
            <Icon icon="lucide:log-in" class="text-sm" />
            <span>Entrar</span>
          </RouterLink>

          <RouterLink to="/register" class="nav-link nav-link--cta">
            <Icon icon="lucide:user-plus" class="text-sm" />
            <span>Unirse</span>
          </RouterLink>
        </template>
      </div>

      <!-- ── Mobile Toggle ── -->
      <button 
        class="md:hidden flex items-center justify-center size-10 border-2 border-neon-cyan/50 text-neon-cyan hover:bg-neon-cyan/10 transition-colors shadow-[3px_3px_0px_#000] active:translate-x-0.5 active:translate-y-0.5"
        @click="toggleMenu"
      >
        <Icon :icon="isMenuOpen ? 'lucide:x' : 'lucide:menu'" class="text-2xl" />
      </button>
    </nav>

    <!-- ── Mobile Menu Overlay ── -->
    <Transition name="slide">
      <div v-if="isMenuOpen" class="fixed inset-0 z-[60] md:hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="isMenuOpen = false"></div>
        
        <!-- Drawer -->
        <div class="absolute top-0 right-0 h-full w-[280px] bg-retro-black border-l-4 border-neon-cyan shadow-[-10px_0_30px_rgba(0,0,0,0.8)] overflow-hidden flex flex-col">
          <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-0"></div>
          
          <!-- Header del menu -->
          <div class="relative z-10 p-6 border-b-2 border-white/5 flex items-center justify-between">
            <span class="font-pixel text-xs text-neon-cyan uppercase tracking-[0.2em]">System Menu</span>
            <button @click="isMenuOpen = false" class="text-white/40 hover:text-white transition-colors">
              <Icon icon="lucide:x" class="text-xl" />
            </button>
          </div>

          <!-- Links del menu -->
          <div class="relative z-10 flex-1 overflow-y-auto p-4 flex flex-col gap-3">
            <RouterLink to="/" class="mobile-nav-link" :class="{ 'mobile-nav-link--active': route.path === '/' }">
              <Icon icon="lucide:layout-grid" />
              <span>Juegos</span>
            </RouterLink>

            <RouterLink to="/store" class="mobile-nav-link mobile-nav-link--yellow" :class="{ 'mobile-nav-link--active-yellow': route.path === '/store' }">
              <Icon icon="lucide:shopping-bag" />
              <span>Tienda</span>
            </RouterLink>

            <RouterLink v-if="authStore.isLoggedIn" to="/achievements" class="mobile-nav-link" :class="{ 'mobile-nav-link--active': route.path === '/achievements' }">
              <Icon icon="lucide:award" />
              <span>Logros</span>
            </RouterLink>

            <div class="h-px bg-white/5 my-2"></div>

            <template v-if="authStore.isLoggedIn">
              <RouterLink to="/profile" class="mobile-nav-link" :class="{ 'mobile-nav-link--active': route.path === '/profile' }">
                <Icon icon="lucide:user" />
                <span>Perfil</span>
              </RouterLink>

              <button class="mobile-nav-link mobile-nav-link--danger" @click="handleLogout" :disabled="isLoggingOut">
                <Icon :icon="isLoggingOut ? 'svg-spinners:ring-resize' : 'lucide:log-out'" />
                <span>{{ isLoggingOut ? 'Cerrando sesión...' : 'Salir' }}</span>
              </button>
            </template>

            <template v-else>
              <RouterLink to="/login" class="mobile-nav-link">
                <Icon icon="lucide:log-in" />
                <span>Entrar</span>
              </RouterLink>

              <RouterLink to="/register" class="mobile-nav-link mobile-nav-link--cta">
                <Icon icon="lucide:user-plus" />
                <span>Registrarse</span>
              </RouterLink>
            </template>
          </div>

          <!-- Footer del menu -->
          <div class="relative z-10 p-6 bg-black/20 border-t-2 border-white/5">
             <div class="flex items-center gap-2 mb-4">
                <div class="size-2 bg-neon-green animate-pulse"></div>
                <span class="font-pixel text-[10px] text-white/30 uppercase tracking-widest">Server Online</span>
             </div>
             <p class="font-pixel text-[9px] text-white/10 uppercase">GameHub v2.4.0_rev3</p>
          </div>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
@reference "../../style.css";

/* ── Base nav-link ──────────────────────────────────────────── */
.nav-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-family: var(--font-display);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-retro-white);
  background: transparent;
  border: 2px solid rgba(255, 255, 255, 0.12);
  box-shadow: 3px 3px 0px #000;
  transition: transform 0.1s, box-shadow 0.1s, background 0.15s, color 0.15s, border-color 0.15s;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
}
.nav-link:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--color-neon-cyan);
  color: var(--color-neon-cyan);
  transform: translate(2px, 2px);
  box-shadow: 1px 1px 0px #000;
}
.nav-link:active {
  transform: translate(3px, 3px);
  box-shadow: none;
}
.nav-link:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* ── Active state ── */
.nav-link--active {
  background: var(--color-neon-cyan);
  border-color: #000;
  color: #000 !important;
  box-shadow: 3px 3px 0px #000;
}

/* ── Yellow (Tienda) ── */
.nav-link--yellow {
  border-color: rgba(255, 242, 0, 0.35);
  color: var(--color-neon-yellow);
}
.nav-link--yellow:hover {
  border-color: var(--color-neon-yellow);
  background: rgba(255, 242, 0, 0.08);
}
.nav-link--active-yellow {
  background: var(--color-neon-yellow);
  border-color: #000;
  color: #000 !important;
}

/* ── Danger (Salir) ── */
.nav-link--danger {
  border-color: rgba(255, 45, 85, 0.4);
  color: var(--color-neon-pink);
}
.nav-link--danger:hover {
  border-color: var(--color-neon-pink);
  background: rgba(255, 45, 85, 0.1);
}

/* ── CTA (Registro) ── */
.nav-link--cta {
  background: var(--color-neon-cyan);
  border-color: #000;
  color: #000 !important;
}

/* ── Mobile Nav Links ── */
.mobile-nav-link {
  @apply flex items-center gap-4 p-4 font-display text-sm font-bold uppercase tracking-widest text-white/70 border-2 border-white/5 transition-all;
  box-shadow: 4px 4px 0px #000;
}
.mobile-nav-link:hover {
  @apply text-neon-cyan border-neon-cyan/50 bg-neon-cyan/5 translate-x-1 translate-y-1;
  box-shadow: 2px 2px 0px #000;
}
.mobile-nav-link--active {
  @apply bg-neon-cyan text-black border-black/20;
}
.mobile-nav-link--yellow {
  @apply text-neon-yellow/70 border-neon-yellow/10;
}
.mobile-nav-link--yellow:hover {
  @apply text-neon-yellow border-neon-yellow/50 bg-neon-yellow/5;
}
.mobile-nav-link--active-yellow {
  @apply bg-neon-yellow text-black border-black/20;
}
.mobile-nav-link--danger {
  @apply text-neon-pink/70 border-neon-pink/10;
}
.mobile-nav-link--danger:hover {
  @apply text-neon-pink border-neon-pink/50 bg-neon-pink/5;
}
.mobile-nav-link--cta {
  @apply bg-neon-cyan text-black border-black/20;
}

/* ── Animations ── */
.slide-enter-active, .slide-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
.animate-blink {
  animation: blink 1s step-end infinite;
}
</style>

