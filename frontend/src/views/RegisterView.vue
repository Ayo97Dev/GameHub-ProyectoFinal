<script setup>
import { reactive, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import BaseButton from '../components/ui/BaseButton.vue'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})
const errorMsg = ref(null)
const isLoading = ref(false)

async function submitRegister() {
  errorMsg.value = null
  isLoading.value = true
  try {
    await authStore.register(form)
    router.push('/dashboard')
  } catch (error) {
    if (error.response?.data?.message) {
      errorMsg.value = error.response.data.message
    } else {
      errorMsg.value = 'Error al registrarte. Verifica los datos introducidos.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="mx-auto flex w-full max-w-7xl justify-center px-4 py-10">
    <div class="gh-panel relative w-full max-w-md overflow-hidden p-6 sm:p-8">
      <div class="pointer-events-none absolute -right-14 -top-14 h-36 w-36 rounded-full bg-cyan-300/30 dark:bg-cyan-500/20 blur-3xl transition-colors" />
      <div class="pointer-events-none absolute -left-10 bottom-0 h-32 w-32 rounded-full bg-violet-300/30 dark:bg-violet-500/20 blur-3xl transition-colors" />

      <p class="relative z-10 text-xs font-bold uppercase tracking-[0.24em] text-violet-600 dark:text-violet-300">Onboarding</p>
      <h1 class="relative z-10 mt-2 text-3xl font-black text-slate-800 dark:text-white transition-colors">Crear cuenta</h1>
      <p class="relative z-10 mt-1 text-sm text-slate-500 dark:text-slate-400 transition-colors">Regístrate para guardar partidas y logros.</p>

      <div v-if="errorMsg" class="mt-4 rounded-md bg-red-50 dark:bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-500 border border-red-200 dark:border-red-500/20 transition-colors">
        {{ errorMsg }}
      </div>
      
      <form class="relative z-10 mt-6 space-y-4" @submit.prevent="submitRegister">
        <div>
          <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300 transition-colors">Usuario</label>
          <input v-model="form.name" type="text" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300 transition-colors">Email</label>
          <input v-model="form.email" type="email" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300 transition-colors">Contraseña</label>
          <input v-model="form.password" type="password" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300 transition-colors">Confirmar Contraseña</label>
          <input v-model="form.password_confirmation" type="password" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <BaseButton type="submit" class="w-full" :disabled="isLoading">
          <span v-if="isLoading">Registrando...</span>
          <span v-else>Registrarme</span>
        </BaseButton>
      </form>

      <p class="relative z-10 mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
        ¿Ya tienes cuenta?
        <RouterLink to="/login" class="font-medium text-violet-600 dark:text-cyan-400 hover:underline">Inicia sesión</RouterLink>
      </p>
    </div>
  </section>
</template>
