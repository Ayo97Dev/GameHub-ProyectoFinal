<template>
  <aside class="w-full bg-black/40 backdrop-blur-3xl border border-white/5 flex flex-col shrink-0 shadow-2xl  overflow-hidden mb-12 lg:max-w-4xl">
     
     <!-- Absolute top: Prestige Area (Aspirational Hero Header) -->
     <div class="p-6 border-b border-white/5 bg-gradient-to-b from-white/5 to-transparent shrink-0">
        <div class="p-0.5  bg-gradient-to-r from-neon-pink/10 to-neon-fuchsia/10">
           <button 
             @click="clicker.prestige()"
             :disabled="clicker.balance < clicker.prestigeRequiredBalance"
             class="w-full py-5 rounded-[10px] bg-black/60 border border-white/5 flex flex-col items-center justify-center transition-all group"
             :class="clicker.balance >= clicker.prestigeRequiredBalance ? 'hover:bg-gradient-to-r hover:from-neon-pink hover:to-neon-fuchsia border-transparent hover:scale-[1.02] shadow-[0_0_30px_rgba(255,45,85,0.3)]' : 'opacity-60 grayscale cursor-not-allowed'"
           >
              <span class="font-display text-base font-black text-white uppercase tracking-widest group-hover:text-white transition-colors" :class="clicker.balance >= clicker.prestigeRequiredBalance ? 'text-neon-pink' : ''">REINICIO_DEL_SISTEMA</span>
              <span class="font-pixel text-xs mt-1 text-white/50 uppercase tracking-[0.2em] group-hover:text-white/90">CLAVE: {{ formatNumber(clicker.prestigeRequiredBalance) }} CR</span>
           </button>
        </div>
     </div>

     <div class="p-4 sm:p-6 border-b border-white/5 bg-black/20 flex justify-between items-center shrink-0">
        <h3 class="font-display text-sm font-black text-white uppercase tracking-widest">MÓDULOS_DEL_SISTEMA</h3>
        <p class="font-pixel text-xs text-white/40 uppercase tracking-widest hidden sm:block">TIEMPO_DE_SESIÓN: {{ formatSessionDuration(sessionElapsedSeconds) }}</p>
     </div>

     <!-- Scrollable Dropdown/Accordion Area -->
     <div class="flex-1 overflow-y-auto overflow-x-hidden custom-scroll p-4 sm:p-5">
        
        <div v-for="tier in tiers" :key="tier.id" class="mb-4 last:mb-0">
           <!-- Accordion Header -->
           <button 
             @click="toggleTier(tier.id)"
             class="w-full flex items-center justify-between p-3  border border-white/5 bg-white/5 hover:bg-white/10 transition-colors group"
           >
              <span class="font-pixel text-xs font-bold tracking-[0.2em]" :class="`text-${tier.color}`">{{ tier.label }}</span>
              <span class="font-display text-xs opacity-40 group-hover:opacity-100 transition-opacity">
                 {{ openTiers.includes(tier.id) ? '[-]' : '[+]' }}
              </span>
           </button>

           <!-- Accordion Content -->
           <Transition name="accordion">
             <div v-show="openTiers.includes(tier.id)" class="pt-3">
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <button 
                    v-for="upgrade in upgrades.filter(u => u.tier === tier.id)"
                    :key="upgrade.id"
                    @click="clicker.buyUpgrade(upgrade.id)"
                    class="group relative flex items-center p-3  border transition-all duration-300 text-left overflow-hidden bg-[linear-gradient(135deg,rgba(255,255,255,0.03)_0%,transparent_100%)]"
                    :class="clicker.balance >= clicker.upgradeCost(upgrade.id) 
                      ? 'border-white/5 hover:border-neon-cyan/40 hover:bg-white/10 hover:shadow-[0_0_15px_rgba(0,242,255,0.1)]' 
                      : 'border-transparent opacity-40 cursor-not-allowed grayscale'"
                  >
                     <div class="size-10 rounded shadow-inner bg-black/50 border border-white/5 flex items-center justify-center text-xl mr-3 shrink-0 group-hover:scale-110 transition-transform">
                        <Icon :icon="upgrade.icon" class="text-neon-cyan" />
                     </div>
                     
                     <div class="flex-1 min-w-0 pr-1">
                        <div class="flex justify-between items-baseline mb-0.5">
                           <p class="font-display text-xs font-black text-white uppercase truncate group-hover:text-neon-cyan transition-colors leading-tight">{{ upgrade.name }}</p>
                           <span class="font-pixel text-xs text-white/40 group-hover:text-white/80">LVL_{{ clicker.upgrades[upgrade.id] || 0 }}</span>
                        </div>
                        <p class="font-sans text-xs font-bold text-white/30 uppercase mb-2 truncate">{{ upgrade.description }}</p>
                        
                        <!-- Progress Bar inline -->
                        <div class="flex items-center gap-2">
                           <div class="flex-1 h-0.5 bg-black/60 overflow-hidden rounded">
                              <div 
                                class="h-full transition-all duration-500"
                                :class="tier.barColor"
                                :style="{ width: Math.min(clicker.balance / clicker.upgradeCost(upgrade.id) * 100, 100) + '%' }"
                              ></div>
                           </div>
                           <span class="font-pixel text-xs font-bold shrink-0" :class="clicker.balance >= clicker.upgradeCost(upgrade.id) ? 'text-neon-yellow drop-shadow-[0_0_4px_rgba(254,240,138,0.5)]' : 'text-white/20'">
                              {{ formatNumber(clicker.upgradeCost(upgrade.id)) }}
                           </span>
                        </div>
                     </div>
                  </button>
               </div>
             </div>
           </Transition>
        </div>

     </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useClickerStore } from '../../../stores/games/clicker'

defineProps({
  sessionElapsedSeconds: { type: Number, required: true },
  tiers: { type: Array, required: true },
  upgrades: { type: Array, required: true }
})

const clicker = useClickerStore()
const openTiers = ref([1]) // Default to having T1 open

function toggleTier(tierId) {
  if (openTiers.value.includes(tierId)) {
    openTiers.value = openTiers.value.filter(id => id !== tierId)
  } else {
    openTiers.value.push(tierId)
  }
}

function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(2) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return Math.floor(n).toString()
}

function formatSessionDuration(s) { 
  const total = Math.max(Math.floor(s), 0); 
  return `${Math.floor((total % 3600) / 60).toString().padStart(2, '0')}:${(total % 60).toString().padStart(2, '0')}` 
}
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 242, 255, 0.3); }

.accordion-enter-active, .accordion-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-height: 800px; overflow: hidden; }
.accordion-enter-from, .accordion-leave-to { max-height: 0; opacity: 0; }
</style>
