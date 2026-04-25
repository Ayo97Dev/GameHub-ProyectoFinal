<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'

const authStore = useAuthStore()
const gameStore = useGameStore()
const router    = useRouter()
const isLoggingOut = ref(false)

onMounted(() => {
  if (!gameStore.hasFetched || gameStore.games.length === 0) {
    gameStore.fetchGames()
  }
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
  }
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-retro-black border-b-4 border-neon-cyan shadow-[0_4px_0px_#000]">
    <!-- Scanline sutil en la navbar -->
    <div class="gh-scanlines absolute inset-0 opacity-5 pointer-events-none z-0"></div>

    <nav class="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3">

      <!-- ── Logo ── -->
      <div class="flex items-center gap-3 shrink-0">
        <RouterLink
          to="/"
          class="font-display text-2xl font-black uppercase tracking-widest gh-title-gradient hover:opacity-80 transition-opacity"
        >
          GameHub
        </RouterLink>
        <span class="font-pixel text-[10px] uppercase tracking-widest text-neon-pink border-2 border-neon-pink px-2 py-0.5 shadow-[2px_2px_0px_#000]">
          ARCADE
        </span>
      </div>

      <!-- ── Nav Links ── -->
      <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">

        <!-- Juegos -->
        <RouterLink
          to="/"
          class="nav-link"
          :class="{ 'nav-link--active': $route.path === '/' }"
        >
          <Icon icon="lucide:layout-grid" />
          <span>Juegos</span>
        </RouterLink>

        <!-- Tienda -->
        <RouterLink
          to="/store"
          class="nav-link nav-link--yellow"
          :class="{ 'nav-link--active-yellow': $route.path === '/store' }"
        >
          <Icon icon="lucide:shopping-bag" />
          <span>Tienda</span>
        </RouterLink>

        <!-- Logros (solo si autenticado) -->
        <RouterLink
          v-if="authStore.isLoggedIn"
          to="/achievements"
          class="nav-link"
          :class="{ 'nav-link--active': $route.path === '/achievements' }"
        >
          <Icon icon="lucide:award" />
          <span class="hidden sm:inline">Logros</span>
        </RouterLink>

        <!-- ── Separador vertical ── -->
        <div class="h-6 w-px bg-white/10 mx-1"></div>

        <!-- Logged IN -->
        <template v-if="authStore.isLoggedIn">
          <RouterLink
            to="/profile"
            class="nav-link"
            :class="{ 'nav-link--active': $route.path === '/profile' }"
          >
            <Icon icon="lucide:user" />
            <span class="hidden sm:inline">Perfil</span>
          </RouterLink>

          <button
            class="nav-link nav-link--danger"
            :disabled="isLoggingOut"
            @click="handleLogout"
          >
            <Icon :icon="isLoggingOut ? 'lucide:loader-2' : 'lucide:log-out'" :class="{ 'animate-spin': isLoggingOut }" />
            <span class="hidden sm:inline">{{ isLoggingOut ? 'Saliendo…' : 'Salir' }}</span>
          </button>
        </template>

        <!-- Logged OUT -->
        <template v-else>
          <RouterLink to="/login" class="nav-link">
            <Icon icon="lucide:log-in" />
            <span>Entrar</span>
          </RouterLink>

          <RouterLink to="/register" class="nav-link nav-link--cta">
            <Icon icon="lucide:user-plus" />
            <span>Registro</span>
          </RouterLink>
        </template>

      </div>
    </nav>
  </header>
</template>

<style scoped>
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
.nav-link--active:hover {
  background: var(--color-neon-cyan);
  color: #000;
  border-color: #000;
}

/* ── Yellow (Tienda) ── */
.nav-link--yellow {
  border-color: rgba(255, 242, 0, 0.35);
  color: var(--color-neon-yellow);
}
.nav-link--yellow:hover {
  border-color: var(--color-neon-yellow);
  color: var(--color-neon-yellow);
  background: rgba(255, 242, 0, 0.08);
}

.nav-link--active-yellow {
  background: var(--color-neon-yellow);
  border-color: #000;
  color: #000 !important;
}
.nav-link--active-yellow:hover {
  background: var(--color-neon-yellow);
  color: #000;
}

/* ── Danger (Salir) ── */
.nav-link--danger {
  border-color: rgba(255, 45, 85, 0.4);
  color: var(--color-neon-pink);
}
.nav-link--danger:hover {
  border-color: var(--color-neon-pink);
  color: var(--color-neon-pink);
  background: rgba(255, 45, 85, 0.1);
}

/* ── CTA (Registro) ── */
.nav-link--cta {
  background: var(--color-neon-cyan);
  border-color: #000;
  color: #000 !important;
  box-shadow: 3px 3px 0px #000;
}
.nav-link--cta:hover {
  background: var(--color-neon-cyan);
  color: #000;
  filter: brightness(1.1);
  transform: translate(2px, 2px);
  box-shadow: 1px 1px 0px #000;
}
</style>
