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
    router.push('/profile')
  } catch (error) {
    if (error.response?.data?.message) {
      errorMsg.value = error.response.data.message
    } else {
      errorMsg.value = 'REG_ERR // Data integrity check failed.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="mx-auto flex w-full max-w-7xl justify-center px-4 py-16 lg:py-32 relative z-10">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none -z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(255,45,85,0.05),transparent_70%)] pointer-events-none -z-10"></div>

    <div class="relative w-full max-w-lg p-12 sm:p-16 border-4 border-retro-black bg-black shadow-[24px_24px_0px_#000] overflow-hidden">
      <!-- Corner Ornaments -->
      <div class="absolute -top-1 -left-1 size-12 border-t-4 border-l-4 border-neon-pink"></div>
      <div class="absolute -bottom-1 -right-1 size-12 border-b-4 border-r-4 border-neon-pink"></div>
      
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,45,85,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,45,85,0.02)_1px,transparent_1px)] bg-[size:40px_40px]"></div>

      <header class="relative z-10 mb-12">
        <p class="font-pixel text-[10px] font-black uppercase tracking-[0.5em] text-neon-yellow mb-4 border-b-2 border-white/5 pb-2 inline-block">REG_MODULE_PROTOCOL_V1.2</p>
        <h1 class="text-6xl font-display font-black uppercase tracking-tighter text-white leading-none gh-title-glow">Registro_ID</h1>
      </header>
      
      <div v-if="errorMsg" class="relative z-10 mb-10 border-l-4 border-neon-pink bg-neon-pink/10 p-5 font-pixel text-xs text-neon-pink uppercase blink shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">
        DATA_ERR: {{ errorMsg }}
      </div>
      
      <form class="relative z-10 space-y-8" @submit.prevent="submitRegister">
        <div class="space-y-3">
          <label class="block font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/40">IDENTIDAD [USERNAME]</label>
          <div class="relative group">
             <div class="absolute left-0 top-0 h-full w-[2px] bg-white/10 group-focus-within:bg-neon-pink transition-colors"></div>
             <input v-model="form.name" type="text" required placeholder="User_01" class="w-full bg-white/5 border border-white/5 px-6 py-4 font-sans font-bold text-white outline-none transition focus:bg-white/10 focus:border-white/20" />
          </div>
        </div>
        <div class="space-y-3">
          <label class="block font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/40">PROTOCOL [EMAIL]</label>
          <div class="relative group">
             <div class="absolute left-0 top-0 h-full w-[2px] bg-white/10 group-focus-within:bg-neon-pink transition-colors"></div>
             <input v-model="form.email" type="email" required placeholder="name@domain.node" class="w-full bg-white/5 border border-white/5 px-6 py-4 font-sans font-bold text-white outline-none transition focus:bg-white/10 focus:border-white/20" />
          </div>
        </div>
        <div class="space-y-3">
          <label class="block font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/40">CRYPT_KEY [PASSWORD]</label>
          <div class="relative group">
             <div class="absolute left-0 top-0 h-full w-[2px] bg-white/10 group-focus-within:bg-neon-pink transition-colors"></div>
             <input v-model="form.password" type="password" required placeholder="********" class="w-full bg-white/5 border border-white/5 px-6 py-4 font-sans font-bold text-white outline-none transition focus:bg-white/10 focus:border-white/20" />
          </div>
        </div>
        <div class="space-y-3">
          <label class="block font-pixel text-[10px] font-black uppercase tracking-[0.4em] text-white/40">RE_CRYPT [CONFIRM_PASS]</label>
          <div class="relative group">
             <div class="absolute left-0 top-0 h-full w-[2px] bg-white/10 group-focus-within:bg-neon-pink transition-colors"></div>
             <input v-model="form.password_confirmation" type="password" required placeholder="********" class="w-full bg-white/5 border border-white/5 px-6 py-4 font-sans font-bold text-white outline-none transition focus:bg-white/10 focus:border-white/20" />
          </div>
        </div>
        
        <button 
          type="submit" 
          :disabled="isLoading"
          class="w-full py-6 bg-neon-pink text-black font-display text-lg font-black uppercase tracking-widest shadow-[8px_8px_0px_#000] hover:translate-x-[-4px] hover:translate-y-[-4px] hover:shadow-[12px_12px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center justify-center gap-4 group"
        >
          <Icon v-if="isLoading" icon="lucide:loader-2" class="animate-spin text-2xl" />
          <Icon v-else icon="lucide:user-plus" class="text-2xl group-hover:scale-110 transition-transform" />
          {{ isLoading ? 'SYNCHRONIZING...' : 'INICIALIZAR_CUENTA' }}
        </button>
      </form>

      <div class="relative z-10 mt-12 pt-8 border-t border-white/5 flex flex-col items-center gap-4">
        <p class="font-sans font-bold text-xs text-white/30 uppercase tracking-widest">¿YA_ERES_OPERADOR?</p>
        <RouterLink to="/login" class="w-full py-4 border-2 border-white/10 text-center font-display text-xs font-black text-white hover:bg-white/5 hover:border-white/20 transition-all uppercase tracking-[0.2em]">Conectar_Terminal_Existente</RouterLink>
      </div>
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
