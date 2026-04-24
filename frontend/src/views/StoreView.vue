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
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-10"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-neon-cyan/5 via-transparent to-neon-pink/5 pointer-events-none"></div>

    <!-- CART TOGGLE BUTTON -->
    <button 
      @click="isCartOpen = !isCartOpen"
      class="fixed bottom-8 right-8 z-[60] size-16 bg-neon-yellow text-black shadow-[6px_6px_0px_#000] border-2 border-black flex items-center justify-center transition-all hover:scale-110 active:scale-95 group"
    >
       <Icon icon="lucide:shopping-cart" class="text-3xl group-hover:animate-bounce" />
       <div v-if="cartCount > 0" class="absolute -top-1 -right-1 size-6 bg-neon-pink text-white text-[10px] font-black flex items-center justify-center border-2 border-retro-deep animate-pulse">
         {{ cartCount }}
       </div>
    </button>

    <!-- CART SIDEBAR -->
    <Transition name="slide">
      <aside v-if="isCartOpen" class="fixed inset-y-0 right-0 w-full sm:w-[400px] bg-black border-l-4 border-neon-cyan z-[70] shadow-[-12px_0_0_#000] flex flex-col">
         <header class="p-6 border-b-2 border-neon-cyan/20 flex items-center justify-between bg-retro-dark">
            <h2 class="font-display text-xl font-black text-white uppercase tracking-widest">Carrito_De_Compra</h2>
            <button @click="isCartOpen = false" class="text-white/40 hover:text-white transition-colors text-2xl">✕</button>
         </header>

         <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scroll">
            <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center opacity-20 text-center">
               <Icon icon="lucide:shopping-cart" class="text-6xl mb-4" />
               <p class="font-pixel text-xs uppercase tracking-widest">Carrito_Vacio</p>
            </div>
            
            <div 
              v-for="item in cart" 
              :key="item.id" 
              class="gh-glass bg-white/5 border-white/5 p-4 flex gap-4 items-center group relative overflow-hidden"
            >
               <div class="size-12 shrink-0 flex items-center justify-center text-2xl border border-white/10 bg-black/40" :class="`text-${item.color}`">
                 <Icon :icon="item.icon" />
               </div>
               <div class="flex-1 min-w-0">
                  <h4 class="font-display text-xs font-black text-white uppercase truncate">{{ item.name }}</h4>
                  <p class="font-pixel text-[10px] text-white/40 uppercase">{{ item.price.toFixed(2) }} € / UNIDAD</p>
               </div>
               <div class="flex flex-col items-center gap-1">
                  <div class="flex items-center gap-2 bg-black/60 border border-white/10 px-2 py-1">
                     <button @click="removeFromCart(item.id)" class="text-white/40 hover:text-neon-pink">－</button>
                     <span class="font-display text-xs font-bold text-white w-4 text-center">{{ item.quantity }}</span>
                     <button @click="addToCart(item)" class="text-white/40 hover:text-neon-cyan">＋</button>
                  </div>
               </div>
            </div>
         </div>

         <footer v-if="cart.length > 0" class="p-6 border-t-2 border-neon-cyan/20 bg-retro-dark space-y-4">
            <div class="flex justify-between items-end">
               <span class="font-pixel text-[10px] text-white/40 uppercase tracking-widest">TOTAL_ESTIMADO</span>
               <span class="font-display text-3xl font-black text-neon-yellow">{{ cartTotal.toFixed(2) }} €</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
               <button @click="clearCart" class="py-3 border-2 border-white/10 font-pixel text-[10px] text-white/40 hover:bg-white/5 uppercase transition-all">Limpiar</button>
               <button 
                 @click="startPayment" 
                 class="py-3 bg-neon-cyan text-black font-display text-xs font-black uppercase tracking-widest shadow-[4px_4px_0_#000] border-2 border-black hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all"
               >
                 Proceder_al_Pago
               </button>
            </div>
         </footer>
      </aside>
    </Transition>

    <div class="max-w-6xl w-full mx-auto relative z-20">
      
      <!-- HEADER -->
      <header class="text-center mb-16">
        <div class="flex items-center justify-center gap-4 mb-4">
           <div class="h-px w-16 bg-neon-cyan"></div>
           <p class="font-pixel text-neon-cyan text-sm tracking-[0.4em] uppercase">MERCADO_NEGRO_DIGITAL</p>
           <div class="h-px w-16 bg-neon-cyan"></div>
        </div>
        <h1 class="font-display text-5xl sm:text-6xl font-black text-white mb-6 gh-title-glow tracking-[-0.02em] uppercase">
          Arsenal Global
        </h1>
        <p class="font-sans text-sm font-medium uppercase text-white/50 max-w-2xl mx-auto leading-relaxed">
          Adquiere mejoras y habilidades limitadas para tus sistemas. Las cargas se sincronizarán directamente con tu inventario local.
        </p>
      </header>

      <!-- CATALOG -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
        
        <!-- PAYMENT METHODS MODAL -->
        <div v-if="showPaymentMethods" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-xl p-4">
           <div class="max-w-md w-full bg-retro-dark border-4 border-neon-cyan p-8 shadow-[12px_12px_0px_#000] relative overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-tr from-neon-cyan/5 to-transparent pointer-events-none"></div>
              
              <button @click="showPaymentMethods = false" class="absolute top-6 right-6 text-white/40 hover:text-white transition-colors">✕</button>

              <header class="text-center mb-8">
                 <p class="font-pixel text-neon-cyan text-[10px] uppercase tracking-[0.4em] mb-2">Checkout_Terminal</p>
                 <h3 class="font-display text-2xl font-black text-white uppercase tracking-tighter">Selecciona_Metodo</h3>
              </header>

              <div class="grid grid-cols-1 gap-4">
                 <button 
                   v-for="method in paymentMethods" 
                   :key="method.id"
                   @click="checkout(method)"
                   class="flex items-center gap-5 p-4 bg-black border-2 border-white/10 hover:border-neon-cyan hover:bg-neon-cyan/5 transition-all text-left group"
                 >
                    <div class="size-12 shrink-0 flex items-center justify-center group-hover:scale-110 transition-transform">
                       <Icon v-if="method.icon" :icon="method.icon" class="text-3xl" />
                       <div v-else-if="method.customSvg" v-html="method.customSvg" class="size-10"></div>
                    </div>
                    <span class="font-display text-sm font-black text-white uppercase tracking-widest">{{ method.name }}</span>
                    <span class="ml-auto opacity-0 group-hover:opacity-100 text-neon-cyan">>></span>
                 </button>
              </div>

              <div class="mt-8 pt-6 border-t-2 border-neon-cyan/20 flex justify-between items-center">
                 <span class="font-pixel text-[10px] text-white/30 uppercase">Importe_Total</span>
                 <span class="font-display text-xl font-black text-neon-yellow">{{ cartTotal.toFixed(2) }} €</span>
              </div>
           </div>
        </div>

        <!-- PROCESSING OVERLAY -->
        <div v-if="isProcessing" class="fixed inset-0 z-[110] flex items-center justify-center bg-black/90 backdrop-blur-md">
          <div class="flex flex-col items-center p-12 bg-black border-4 border-neon-cyan shadow-[0_0_100px_rgba(0,242,255,0.2)]">
            <div class="size-20 border-4 border-neon-cyan border-t-transparent animate-spin mb-8"></div>
            <p class="font-pixel text-neon-cyan text-xl animate-pulse tracking-[0.4em] uppercase">PROCESANDO_PAGO</p>
            <p v-if="selectedMethod" class="font-sans text-xs text-white/60 mt-2 uppercase tracking-widest">Via {{ selectedMethod.name }}</p>
            <div class="w-64 h-2 bg-retro-dark mt-8 overflow-hidden border border-white/10">
               <div class="h-full bg-neon-cyan animate-[loading_2s_ease-in-out_infinite]"></div>
            </div>
            <p class="font-sans text-[10px] text-white/30 mt-4 uppercase tracking-[0.2em]">Cifrando transacción de terminal...</p>
          </div>
        </div>

        <!-- SUCCESS TOAST -->
        <Transition name="bounce">
          <div v-if="purchaseSuccess" class="fixed top-24 left-1/2 -translate-x-1/2 z-[120]">
             <div class="gh-panel p-6 px-10 border-4 border-neon-green bg-black shadow-[8px_8px_0_#000] flex items-center gap-6">
                <div class="size-14 bg-neon-green/20 text-neon-green flex items-center justify-center text-3xl border-2 border-neon-green shadow-[0_0_20px_rgba(34,197,94,0.2)]">
                   <Icon icon="lucide:check" />
                </div>
                <div class="flex flex-col">
                  <span class="font-pixel text-xs text-neon-green uppercase tracking-[0.3em] mb-1">TRANSACCIÓN_COMPLETADA</span>
                  <span class="font-display text-lg font-black text-white uppercase">Inventario actualizado con éxito.</span>
                </div>
             </div>
          </div>
        </Transition>

        <div 
          v-for="item in items" 
          :key="item.id"
          class="gh-panel border-4 border-retro-black bg-black p-6 sm:p-8 flex flex-col sm:flex-row gap-6 transition-all duration-300 hover:border-neon-cyan shadow-[8px_8px_0_#000] hover:shadow-[12px_12px_0_#000] group relative overflow-hidden"
        >
          <!-- Hover Effect Background -->
          <div class="absolute inset-0 bg-gradient-to-tr opacity-0 group-hover:opacity-10 transition-opacity duration-500 pointer-events-none" :class="`from-${item.color} to-transparent`"></div>

          <!-- Icon Area -->
          <div class="shrink-0 flex flex-col items-center justify-center gap-3">
             <div 
               class="size-24 border-4 shadow-2xl relative overflow-hidden flex items-center justify-center text-5xl transform group-hover:scale-105 transition-transform"
               :class="`border-${item.color}/50 bg-retro-dark`"
             >
                <div class="absolute inset-0 bg-gradient-to-tr from-black/60 to-transparent z-0"></div>
                <Icon :icon="item.icon" class="relative z-10" :class="`text-${item.color}`" />
             </div>
             <div class="bg-black px-3 py-1 border-2 border-white/10 shadow-[2px_2px_0_#000]">
                <span class="font-pixel text-[10px] uppercase text-white/60 tracking-widest">{{ item.game }}</span>
             </div>
          </div>

          <!-- Content Area -->
          <div class="flex-1 flex flex-col justify-between">
             <div>
                <div class="flex justify-between items-start mb-2">
                   <h3 class="font-display text-2xl font-black text-white uppercase transition-colors" :class="`group-hover:text-${item.color}`">{{ item.name }}</h3>
                </div>
                <div class="flex items-center gap-3 mb-4">
                  <span class="font-pixel text-xs px-2 py-1 bg-retro-dark border-2 border-white/5 shadow-[2px_2px_0_#000]" :class="`text-${item.color}`">
                    {{ item.uses }} USOS POR CARGA
                  </span>
                </div>
                <p class="font-sans text-sm font-bold uppercase text-white/50 leading-relaxed mb-6">
                   {{ item.description }}
                </p>
             </div>

             <div class="flex items-center justify-between mt-auto">
                <div class="flex flex-col">
                   <span class="font-pixel text-[10px] text-white/40 uppercase tracking-widest mb-1">PRECIO</span>
                   <span class="font-display text-xl font-black text-neon-yellow">{{ item.price.toFixed(2) }} €</span>
                </div>

                <button 
                  @click="addToCart(item)"
                  class="px-6 py-3 bg-retro-dark border-2 border-white/10 font-display text-[10px] font-black uppercase tracking-widest shadow-[4px_4px_0_#000] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0_#000] active:translate-x-0 active:translate-y-0 active:shadow-none transition-all flex items-center gap-2"
                  :class="`hover:border-${item.color} hover:text-${item.color}`"
                >
                  <Icon icon="lucide:shopping-cart" />
                  Añadir_al_Carrito
                </button>
             </div>
          </div>

          <!-- Inventory Status Badge -->
          <div class="absolute top-4 right-4 text-right">
             <span class="font-pixel text-[10px] text-white/30 uppercase tracking-widest block mb-1">EN INVENTARIO</span>
             <span class="font-display text-lg font-bold" :class="inventory.items[item.id] > 0 ? 'text-neon-cyan' : 'text-white/10'">
               {{ inventory.items[item.id] || 0 }}
             </span>
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
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 242, 255, 0.3); }
</style>
