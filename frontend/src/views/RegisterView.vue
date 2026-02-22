<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../components/ui/BaseButton.vue'

const router = useRouter()

const form = reactive({
  username: '',
  email: '',
  password: '',
})

function submitRegister() {
  router.push('/login')
}
</script>

<template>
  <section class="mx-auto flex w-full max-w-7xl justify-center px-4 py-10">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20 sm:p-8">
      <h1 class="text-2xl font-bold text-white">Crear cuenta</h1>
      <p class="mt-1 text-sm text-slate-400">Regístrate para guardar partidas y logros.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submitRegister">
        <div>
          <label class="mb-1 block text-sm text-slate-300">Usuario</label>
          <input v-model="form.username" type="text" required class="w-full rounded-md border border-slate-700 bg-zinc-950 px-3 py-2 text-slate-100 outline-none transition focus:border-violet-400" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-300">Email</label>
          <input v-model="form.email" type="email" required class="w-full rounded-md border border-slate-700 bg-zinc-950 px-3 py-2 text-slate-100 outline-none transition focus:border-violet-400" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-300">Contraseña</label>
          <input v-model="form.password" type="password" required class="w-full rounded-md border border-slate-700 bg-zinc-950 px-3 py-2 text-slate-100 outline-none transition focus:border-violet-400" />
        </div>
        <BaseButton type="submit" class="w-full">Registrarme</BaseButton>
      </form>
    </div>
  </section>
</template>
