<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useInventoryStore } from '../stores/inventory'

const inventory = useInventoryStore()

const items = [
  { 
    id: 'td_purge', 
    name: 'Rayo Destructor', 
    game: 'Tower Defense', 
    uses: 5, 
    price: 2.50, 
    description: 'Ejecuta una descarga masiva que daña a todas las entidades activas.', 
    icon: 'lucide:zap',
    color: 'neon-pink'
  },
  { 
    id: 'td_emp', 
    name: 'Pulso EMP', 
    game: 'Tower Defense', 
    uses: 3, 
    price: 1.00, 
    description: 'Paraliza a todas las unidades enemigas temporalmente con un pulso electromagnético.', 
    icon: 'lucide:waves',
    color: 'neon-cyan'
  },
  { 
    id: 'td_overclock', 
    name: 'Sobrecarga', 
    game: 'Tower Defense', 
    uses: 3, 
    price: 1.50, 
    description: 'Frecuencia Crítica: Duplica la cadencia de fuego de todas las defensas.', 
    icon: 'lucide:flame',
    color: 'neon-yellow'
  },
  { 
    id: 'clicker_autoclick', 
    name: 'Auto-click x10s', 
    game: 'Clicker', 
    uses: 3, 
    price: 0.99, 
    description: 'Automatiza tus clics a máxima velocidad durante 10 segundos.', 
    icon: 'lucide:bot',
    color: 'neon-cyan'
  }
]

const cart = ref([])
const isCartOpen = ref(false)
const isProcessing = ref(false)
const purchaseSuccess = ref(false)
const showPaymentMethods = ref(false)
const selectedMethod = ref(null)

const paymentMethods = [
  { 
    id: 'card', 
    name: 'Tarjeta de Crédito', 
    icon: 'logos:visa'
  },
  { 
    id: 'paypal', 
    name: 'PayPal', 
    icon: 'logos:paypal'
  },
  { 
    id: 'bizum', 
    name: 'Bizum', 
    customSvg: `<svg viewBox="0 0 24 24" fill="#30B9C4" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10"/><path d="M10 16V8C10 8 13 8 13 10.5C13 13 10 13 10 13C10 13 14 13 14 15.5C14 18 10 18 10 18" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>`
  }
]

const cartTotal = computed(() => cart.value.reduce((total, item) => total + (item.price * item.quantity), 0))
const cartCount = computed(() => cart.value.reduce((count, item) => count + item.quantity, 0))

const addToCart = (item) => {
  const existing = cart.value.find(i => i.id === item.id)
  if (existing) {
    existing.quantity++
  } else {
    cart.value.push({ ...item, quantity: 1 })
  }
}

const removeFromCart = (itemId) => {
  const index = cart.value.findIndex(i => i.id === itemId)
  if (index !== -1) {
    if (cart.value[index].quantity > 1) {
      cart.value[index].quantity--
    } else {
      cart.value.splice(index, 1)
    }
  }
}

const clearCart = () => {
  cart.value = []
}

const startPayment = () => {
  if (cart.value.length === 0) return
  showPaymentMethods.value = true
}

const checkout = async (method) => {
  if (isProcessing.value) return
  selectedMethod.value = method
  showPaymentMethods.value = false
  isProcessing.value = true
  purchaseSuccess.value = false

  // Simulate payment processing
  await new Promise(resolve => setTimeout(resolve, 2000))

  // Update inventory for all items in cart
  cart.value.forEach(item => {
    inventory.addItems(item.id, item.uses * item.quantity)
  })
  
  isProcessing.value = false
  purchaseSuccess.value = true
  cart.value = []
  isCartOpen.value = false
  selectedMethod.value = null

  // Clear success message after 4 seconds
  setTimeout(() => {
    purchaseSuccess.value = false
  }, 4000)
}
</script>

<template>
  <div class="store-view min-h-[calc(100vh-64px)] bg-retro-deep text-retro-white font-sans relative overflow-hidden flex flex-col py-10 px-4">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(0,242,255,0.05),transparent_70%)] pointer-events-none"></div>

    <!-- CART TOGGLE BUTTON -->
    <button 
      @click="isCartOpen = !isCartOpen"
      class="fixed bottom-8 right-8 z-[60] size-16 bg-neon-yellow text-black shadow-[8px_8px_0px_#000] flex items-center justify-center transition-all hover:translate-x-[-4px] hover:translate-y-[-4px] hover:shadow-[12px_12px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none group"
    >
       <Icon icon="lucide:shopping-cart" class="text-3xl" />
       <div v-if="cartCount > 0" class="absolute -top-1 -right-1 size-6 bg-neon-pink text-white text-[10px] font-black flex items-center justify-center border-2 border-retro-black animate-pulse">
         {{ cartCount }}
       </div>
    </button>

    <!-- CART SIDEBAR -->
    <Transition name="slide">
      <aside v-if="isCartOpen" class="fixed inset-y-0 right-0 w-full sm:w-[450px] bg-retro-black/95 backdrop-blur-2xl border-l-4 border-retro-black z-[70] shadow-[-20px_0_50px_rgba(0,0,0,0.5)] flex flex-col">
         <header class="p-8 border-b border-white/5 flex items-center justify-between bg-black/40">
            <div class="flex flex-col">
               <span class="font-pixel text-[10px] text-neon-cyan tracking-[0.4em] uppercase mb-1">TERMINAL_PEDIDO</span>
               <h2 class="font-display text-2xl font-black text-white uppercase tracking-tighter">Carrito_De_Compra</h2>
            </div>
            <button @click="isCartOpen = false" class="size-10 flex items-center justify-center border border-white/10 text-white/40 hover:text-white hover:border-white/20 transition-all">✕</button>
         </header>

         <div class="flex-1 overflow-y-auto p-8 space-y-6 custom-scroll">
            <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center opacity-10 text-center">
               <Icon icon="lucide:shopping-cart" class="text-8xl mb-6" />
               <p class="font-pixel text-sm uppercase tracking-[0.5em]">SISTEMA_VACÍO</p>
            </div>
            
            <div 
              v-for="item in cart" 
              :key="item.id" 
              class="bg-white/5 border border-white/5 p-5 flex gap-5 items-center group relative overflow-hidden"
            >
               <div class="size-14 shrink-0 flex items-center justify-center text-3xl border-2 border-white/5 bg-black/40" :class="`text-${item.color}`">
                 <Icon :icon="item.icon" />
               </div>
               <div class="flex-1 min-w-0">
                  <h4 class="font-display text-sm font-black text-white uppercase truncate">{{ item.name }}</h4>
                  <p class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">{{ item.price.toFixed(2) }} € / UNIDAD</p>
               </div>
               <div class="flex flex-col items-end gap-2">
                  <div class="flex items-center bg-black/60 border border-white/10 p-1">
                     <button @click="removeFromCart(item.id)" class="size-7 flex items-center justify-center text-white/40 hover:text-neon-pink hover:bg-neon-pink/10 transition-colors">－</button>
                     <span class="font-display text-xs font-bold text-white w-8 text-center">{{ item.quantity }}</span>
                     <button @click="addToCart(item)" class="size-7 flex items-center justify-center text-white/40 hover:text-neon-cyan hover:bg-neon-cyan/10 transition-colors">＋</button>
                  </div>
               </div>
            </div>
         </div>

         <footer v-if="cart.length > 0" class="p-8 border-t border-white/5 bg-black/40 space-y-6">
            <div class="flex justify-between items-end">
               <div class="flex flex-col">
                  <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.4em] mb-1">TOTAL_RECURSOS</span>
                  <span class="font-display text-4xl font-black text-neon-yellow tracking-tighter">{{ cartTotal.toFixed(2) }} €</span>
               </div>
            </div>
            <div class="grid grid-cols-1 gap-4">
               <button 
                 @click="startPayment" 
                 class="w-full py-5 bg-neon-cyan text-black font-display text-sm font-black uppercase tracking-widest shadow-[6px_6px_0px_#000] hover:translate-x-[-3px] hover:translate-y-[-3px] hover:shadow-[9px_9px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center justify-center gap-3"
               >
                 <Icon icon="lucide:credit-card" class="text-xl" />
                 PROCESAR_TRANSACCIÓN
               </button>
               <button @click="clearCart" class="py-3 border border-white/5 font-pixel text-[10px] text-white/30 hover:text-white hover:bg-white/5 uppercase transition-all tracking-[0.3em]">Abortar_Operación</button>
            </div>
         </footer>
      </aside>
    </Transition>

    <div class="max-w-7xl w-full mx-auto relative z-20">
      
      <!-- HEADER -->
      <header class="text-center mb-24 relative">
        <div class="absolute top-1/2 left-0 w-full h-px bg-gradient-to-r from-transparent via-white/5 to-transparent -z-10"></div>
        <div class="bg-retro-deep inline-block px-12 relative z-10">
           <div class="flex items-center justify-center gap-6 mb-6">
              <div class="h-[2px] w-12 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
              <p class="font-pixel text-neon-cyan text-sm tracking-[0.5em] uppercase animate-pulse">SISTEMA_SUMINISTROS_ACTIVO</p>
              <div class="h-[2px] w-12 bg-neon-cyan shadow-[0_0_8px_#00f2ff]"></div>
           </div>
           <h1 class="font-display text-6xl sm:text-8xl font-black text-white mb-6 gh-title-glow tracking-tighter uppercase leading-[0.8]">
             Arsenal Global
           </h1>
           <p class="font-sans text-xs font-bold uppercase text-white/30 tracking-[0.2em] max-w-xl mx-auto leading-relaxed">
             ADQUIERE MEJORAS Y HABILIDADES LIMITADAS PARA TUS SISTEMAS. LAS CARGAS SE SINCRONIZARÁN DIRECTAMENTE CON TU INVENTARIO DE TERMINAL.
           </p>
        </div>
      </header>

      <!-- CATALOG -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 relative">
        
        <!-- PAYMENT METHODS MODAL -->
        <div v-if="showPaymentMethods" class="fixed inset-0 z-[100] flex items-center justify-center bg-retro-deep/95 backdrop-blur-md p-4">
           <div class="max-w-md w-full bg-black border-4 border-retro-black p-10 shadow-[24px_24px_0px_#000] relative overflow-hidden">
              <div class="absolute top-0 left-0 w-full h-1 bg-neon-cyan"></div>
              <div class="absolute -top-1 -left-1 size-6 border-t-4 border-l-4 border-neon-cyan"></div>
              
              <button @click="showPaymentMethods = false" class="absolute top-6 right-6 size-10 flex items-center justify-center border border-white/10 text-white/40 hover:text-white transition-all">✕</button>

              <header class="text-center mb-10">
                 <p class="font-pixel text-neon-cyan text-[10px] uppercase tracking-[0.5em] mb-3">TERMINAL_PAGO_V2.0</p>
                 <h3 class="font-display text-3xl font-black text-white uppercase tracking-tighter">MÉTODO_AUTORIZACIÓN</h3>
              </header>

              <div class="grid grid-cols-1 gap-4">
                 <button 
                   v-for="method in paymentMethods" 
                   :key="method.id"
                   @click="checkout(method)"
                   class="flex items-center gap-6 p-5 bg-white/5 border border-white/5 hover:border-neon-cyan hover:bg-neon-cyan/5 transition-all text-left group relative overflow-hidden"
                 >
                    <div class="size-14 shrink-0 flex items-center justify-center group-hover:scale-110 transition-transform bg-black/40 border border-white/5">
                       <Icon v-if="method.icon" :icon="method.icon" class="text-4xl" />
                       <div v-else-if="method.customSvg" v-html="method.customSvg" class="size-10"></div>
                    </div>
                    <span class="font-display text-sm font-black text-white uppercase tracking-widest">{{ method.name }}</span>
                    <Icon icon="lucide:chevron-right" class="ml-auto text-white/20 group-hover:text-neon-cyan group-hover:translate-x-1 transition-all" />
                 </button>
              </div>

              <div class="mt-10 pt-8 border-t border-white/5 flex flex-col items-center">
                 <span class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.4em] mb-2">CRÉDITOS_A_TRANSFERIR</span>
                 <span class="font-display text-3xl font-black text-neon-yellow tracking-tighter">{{ cartTotal.toFixed(2) }} €</span>
              </div>
           </div>
        </div>

        <!-- PROCESSING OVERLAY -->
        <div v-if="isProcessing" class="fixed inset-0 z-[110] flex items-center justify-center bg-retro-deep/90 backdrop-blur-sm">
          <div class="flex flex-col items-center p-16 bg-black border-4 border-neon-cyan shadow-[0_0_100px_rgba(0,242,255,0.2)]">
            <div class="relative size-24 mb-10">
               <div class="absolute inset-0 border-4 border-neon-cyan/20"></div>
               <div class="absolute inset-0 border-t-4 border-neon-cyan animate-spin"></div>
            </div>
            <p class="font-pixel text-neon-cyan text-2xl animate-pulse tracking-[0.6em] uppercase mb-4">CIFRANDO_PAGO</p>
            <p v-if="selectedMethod" class="font-display text-xs text-white/40 uppercase tracking-widest">GATEWAY: {{ selectedMethod.name }}</p>
            <div class="w-80 h-1 bg-white/5 mt-12 overflow-hidden relative">
               <div class="absolute inset-y-0 left-0 bg-neon-cyan w-1/3 animate-[loading_1.5s_linear_infinite]"></div>
            </div>
            <p class="font-pixel text-[10px] text-white/20 mt-6 uppercase tracking-[0.4em]">SYNCING_WITH_BANK_NODE_741...</p>
          </div>
        </div>

        <!-- SUCCESS TOAST -->
        <Transition name="bounce">
          <div v-if="purchaseSuccess" class="fixed top-32 left-1/2 -translate-x-1/2 z-[120]">
             <div class="p-8 px-12 border-l-8 border-neon-green bg-black shadow-[24px_24px_0px_#000] flex items-center gap-8 relative overflow-hidden">
                <div class="absolute -top-1 -right-1 size-6 border-t-4 border-r-4 border-neon-green"></div>
                <div class="size-16 bg-neon-green/10 text-neon-green flex items-center justify-center text-4xl border border-neon-green/30">
                  ✓
                </div>
                <div class="flex flex-col">
                  <span class="font-pixel text-[10px] text-neon-green uppercase tracking-[0.5em] mb-2">AUTORIZACIÓN_CONCEDIDA</span>
                  <span class="font-display text-xl font-black text-white uppercase tracking-tighter">INVENTARIO_ACTUALIZADO</span>
                </div>
             </div>
          </div>
        </Transition>

        <div 
          v-for="item in items" 
          :key="item.id"
          class="bg-black border-4 border-retro-black p-8 sm:p-10 flex flex-col sm:flex-row gap-10 transition-all duration-500 hover:border-white/10 hover:shadow-[16px_16px_0px_#000] shadow-[12px_12px_0px_#000] group relative overflow-hidden"
        >
          <!-- Corner Bracket -->
          <div class="absolute -top-1 -left-1 size-6 border-t-2 border-l-2 border-white/10 group-hover:border-neon-cyan transition-colors"></div>

          <!-- Icon Area -->
          <div class="shrink-0 flex flex-col items-center gap-4">
             <div 
               class="size-32 border-2 relative overflow-hidden flex items-center justify-center text-6xl transition-all duration-500"
               :class="`border-${item.color}/30 bg-${item.color}/5 group-hover:bg-${item.color}/20 group-hover:scale-105`"
             >
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:10px_10px]"></div>
                <Icon :icon="item.icon" class="relative z-10" />
             </div>
             <div class="bg-retro-black/80 px-4 py-1.5 border border-white/5 relative">
                <span class="font-pixel text-[10px] uppercase text-white/40 tracking-[0.3em]">{{ item.game }}</span>
             </div>
          </div>

          <!-- Content Area -->
          <div class="flex-1 flex flex-col justify-between py-2">
             <div>
                <div class="flex justify-between items-start mb-4">
                   <h3 class="font-display text-4xl font-black text-white uppercase tracking-tighter leading-none group-hover:text-neon-cyan transition-colors">{{ item.name }}</h3>
                </div>
                <div class="flex items-center gap-3 mb-6">
                  <span class="font-pixel text-[10px] px-3 py-1 bg-white/5 border border-white/5 uppercase tracking-[0.2em]" :class="`text-${item.color}`">
                    {{ item.uses }} CARGAS_DISPONIBLES
                  </span>
                </div>
                <p class="font-sans text-[11px] font-bold uppercase text-white/30 leading-relaxed mb-8 border-l-2 border-white/5 pl-6">
                   {{ item.description }}
                </p>
             </div>

             <div class="flex flex-wrap items-center justify-between gap-6 mt-auto">
                <div class="flex flex-col">
                   <span class="font-pixel text-[10px] text-white/20 uppercase tracking-[0.4em] mb-1">VALOR_MERCADO</span>
                   <span class="font-display text-2xl font-black text-neon-yellow tracking-tighter">{{ item.price.toFixed(2) }} €</span>
                </div>

                <button 
                  @click="addToCart(item)"
                  class="flex-1 sm:flex-none px-8 py-4 bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-widest shadow-[6px_6px_0px_#000] hover:translate-x-[-3px] hover:translate-y-[-3px] hover:shadow-[9px_9px_0px_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center justify-center gap-3"
                >
                  <Icon icon="lucide:shopping-bag" class="text-lg" />
                  ADQUIRIR
                </button>
             </div>
          </div>

          <!-- Inventory Status Badge -->
          <div class="absolute top-6 right-6 text-right">
             <span class="font-pixel text-[9px] text-white/20 uppercase tracking-[0.4em] block mb-1">STOCK_LOCAL</span>
             <div class="flex items-center justify-end gap-2">
                <span class="font-display text-2xl font-black" :class="inventory.items[item.id] > 0 ? 'text-neon-green' : 'text-white/10'">
                  {{ inventory.items[item.id] || 0 }}
                </span>
                <div v-if="inventory.items[item.id] > 0" class="size-2 bg-neon-green shadow-[0_0_8px_#22c55e]"></div>
             </div>
          </div>
        </div>

      </div>
    </div>
  </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.gh-glass {
  backdrop-filter: blur(10px);
}

.slide-enter-active, .slide-leave-active {
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}

.bounce-enter-active {
  animation: bounce-in 0.5s;
}
.bounce-leave-active {
  animation: bounce-in 0.5s reverse;
}
@keyframes bounce-in {
  0% { transform: translate(-50%, -100%); opacity: 0; }
  60% { transform: translate(-50%, 10%); opacity: 1; }
  100% { transform: translate(-50%, 0); }
}

@keyframes loading {
  0% { transform: translateX(-100%); }
  50% { transform: translateX(0); }
  100% { transform: translateX(100%); }
}

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 242, 255, 0.3); }
</style>
