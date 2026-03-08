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
    <div class="w-full max-w-md rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 p-6 shadow-xl shadow-slate-200/50 dark:shadow-black/20 sm:p-8 backdrop-blur-md transition-colors">
      <h1 class="text-2xl font-bold text-slate-800 dark:text-white transition-colors">Crear cuenta</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 transition-colors">Regístrate para guardar partidas y logros.</p>

      <div v-if="errorMsg" class="mt-4 rounded-md bg-red-50 dark:bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-500 border border-red-200 dark:border-red-500/20 transition-colors">
        {{ errorMsg }}
      </div>
      
      <form class="mt-6 space-y-4" @submit.prevent="submitRegister">
        <div>
          <label class="mb-1 block text-sm text-slate-600 dark:text-slate-300 transition-colors">Usuario</label>
          <input v-model="form.name" type="text" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600 dark:text-slate-300 transition-colors">Email</label>
          <input v-model="form.email" type="email" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600 dark:text-slate-300 transition-colors">Contraseña</label>
          <input v-model="form.password" type="password" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600 dark:text-slate-300 transition-colors">Confirmar Contraseña</label>
          <input v-model="form.password_confirmation" type="password" required class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-zinc-950 px-3 py-2 text-slate-800 dark:text-slate-100 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:focus:border-violet-400 dark:focus:ring-violet-400/20" />
        </div>
        <BaseButton type="submit" class="w-full" :disabled="isLoading">
          <span v-if="isLoading">Registrando...</span>
          <span v-else>Registrarme</span>
        </BaseButton>
      </form>

      <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
        ¿Ya tienes cuenta?
        <RouterLink to="/login" class="font-medium text-violet-600 dark:text-cyan-400 hover:underline">Inicia sesión</RouterLink>
      </p>
    </div>
  </section>
</template>
