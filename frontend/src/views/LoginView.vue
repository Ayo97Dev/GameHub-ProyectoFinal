<script setup>
import { reactive, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import BaseButton from '../components/ui/BaseButton.vue'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})
const errorMsg = ref(null)
const isLoading = ref(false)

async function submitLogin() {
  errorMsg.value = null
  isLoading.value = true
  try {
    await authStore.login(form)
    router.push('/profile')
  } catch (error) {
    if (error.response?.data?.message) {
      errorMsg.value = error.response.data.message
    } else {
      errorMsg.value = 'AUTH_ERR // Verifica tus credenciales.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="mx-auto flex w-full max-w-7xl justify-center px-4 py-16 lg:py-24">
    <div class="gh-panel relative w-full max-w-md p-6 sm:p-10 border-[4px] border-neon-cyan shadow-[8px_8px_0px_#000] bg-black">
      <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none"></div>

      <p class="relative z-10 font-pixel text-[10px] font-bold uppercase tracking-widest text-neon-yellow mb-2 border-b-2 border-neon-cyan pb-1 inline-block">AUTH_MODULE</p>
      <h1 class="relative z-10 mt-2 text-4xl font-display font-black uppercase tracking-widest text-retro-white">LOGIN</h1>
      
      <div v-if="errorMsg" class="relative z-10 mt-6 border-[3px] border-neon-pink bg-black p-3 font-pixel text-xs text-neon-pink uppercase blink shadow-[4px_4px_0px_#000]">
        ERR: {{ errorMsg }}
      </div>
      
      <form class="relative z-10 mt-8 space-y-6" @submit.prevent="submitLogin">
        <div>
          <label class="mb-2 block font-pixel text-xs font-bold uppercase tracking-widest text-retro-white">USER_ID [EMAIL]</label>
          <input v-model="form.email" type="email" required class="w-full border-4 border-neon-cyan bg-retro-dark px-4 py-3 font-sans font-bold text-retro-white outline-none transition focus:shadow-[inset_4px_4px_0px_#00f2ff]" />
        </div>
        <div>
          <label class="mb-2 block font-pixel text-xs font-bold uppercase tracking-widest text-retro-white">PASSWORD</label>
          <input v-model="form.password" type="password" required class="w-full border-4 border-neon-cyan bg-retro-dark px-4 py-3 font-sans font-bold text-retro-white outline-none transition focus:shadow-[inset_4px_4px_0px_#00f2ff]" />
        </div>
        <BaseButton type="submit" class="w-full mt-4 py-4 text-xl">
          <Icon :icon="isLoading ? 'lucide:loader-2' : 'lucide:log-in'" class="mr-2" :class="{ 'animate-spin': isLoading }" />
          {{ isLoading ? 'CONNECTING...' : 'LOGIN' }}
        </BaseButton>
      </form>

      <p class="relative z-10 mt-8 text-center font-sans font-bold text-sm text-retro-white">
        NO ACCOUNT? 
        <RouterLink to="/register" class="font-pixel text-neon-pink underline underline-offset-4 ml-2">REGISTER_NOW</RouterLink>
      </p>
    </div>
  </section>
</template>

<style scoped>
.blink {
  animation: blink 1s step-start infinite;
}
@keyframes blink {
  50% { opacity: 0; }
}
</style>
