<script setup>
import { onMounted } from 'vue'
import { useAchievementStore } from '../stores/achievement'

const achievementStore = useAchievementStore()

const RARITY_STYLES = {
  common:    { label: 'COMMON',    classes: 'border-retro-black bg-white dark:bg-black dark:border-retro-white' },
  uncommon:  { label: 'UNCOMMON',  classes: 'border-neon-cyan/50 bg-neon-cyan/5 dark:bg-neon-cyan/10 dark:border-neon-cyan' },
  rare:      { label: 'RARE',      classes: 'border-neon-blue/50 bg-neon-blue/5 dark:bg-neon-blue/10 dark:border-neon-blue' },
  epic:      { label: 'EPIC',      classes: 'border-neon-pink/50 bg-neon-pink/5 dark:bg-neon-pink/10 dark:border-neon-pink' },
  legendary: { label: 'LEGENDARY', classes: 'border-neon-yellow/50 bg-neon-yellow/5 dark:bg-neon-yellow/10 dark:border-neon-yellow' },
}

onMounted(() => {
  achievementStore.fetchAchievements()
})
</script>

<template>
  <section class="mx-auto w-full max-w-6xl px-4 py-16 relative z-10 space-y-16">
    <!-- AMBIENT EFFECTS -->
    <div class="gh-scanlines fixed inset-0 opacity-[0.15] pointer-events-none -z-10"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(255,45,85,0.03),transparent_70%)] pointer-events-none -z-10"></div>

    <header class="p-10 border-4 border-retro-black bg-black shadow-[16px_16px_0px_#000] relative overflow-hidden">
      <!-- Corner Ornaments -->
      <div class="absolute -top-1 -left-1 size-8 border-t-4 border-l-4 border-neon-pink"></div>
      <div class="absolute -bottom-1 -right-1 size-8 border-b-4 border-r-4 border-neon-pink"></div>

      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,45,85,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,45,85,0.02)_1px,transparent_1px)] bg-[size:40px_40px]"></div>

      <p class="relative z-10 font-pixel text-xs font-black uppercase tracking-[0.5em] text-neon-cyan border-b-2 border-white/5 pb-2 inline-block">SISTEMA_LOGROS_V1.0</p>
      <h1 class="relative z-10 mt-6 text-6xl font-display font-black uppercase text-white tracking-tighter leading-none gh-title-glow">Logros_Desbloqueados</h1>
      <div class="relative z-10 mt-8 flex flex-wrap gap-4">
         <div class="bg-neon-pink text-black font-pixel text-sm font-black px-6 py-2 uppercase tracking-widest shadow-[4px_4px_0px_rgba(0,0,0,0.5)]">
           SINC: {{ achievementStore.achievements.filter(a => a.unlocked).length }} / {{ achievementStore.achievements.length }}
         </div>
         <div class="bg-white/5 border border-white/10 px-6 py-2 font-pixel text-[10px] text-white/40 uppercase tracking-[0.3em] flex items-center">
            Módulo_De_Recompensas_Activo
         </div>
      </div>
    </header>

    <div v-if="achievementStore.isLoading && achievementStore.achievements.length === 0" class="flex flex-col items-center justify-center py-24 bg-retro-black/40 border-4 border-dashed border-white/5 space-y-6">
      <div class="relative size-16">
         <div class="absolute inset-0 border-4 border-neon-pink/20"></div>
         <div class="absolute inset-0 border-t-4 border-neon-pink animate-spin"></div>
      </div>
      <p class="text-neon-pink font-pixel text-xl uppercase tracking-[0.5em] blink">SYNCING_DATA_FROM_CLOUD_NODE...</p>
    </div>

    <div v-else class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="a in achievementStore.achievements"
        :key="a.id"
        class="bg-black border-4 p-8 transition-all relative overflow-hidden group shadow-[12px_12px_0px_#000]"
        :class="[RARITY_STYLES[a.rarity]?.classes ?? RARITY_STYLES.common.classes, !a.unlocked && 'opacity-30 grayscale border-dashed shadow-none']"
      >
        <!-- Tech overlay for unlocked -->
        <div v-if="a.unlocked" class="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent pointer-events-none"></div>

        <div class="mb-6 flex items-start gap-6 relative z-10">
          <div class="size-16 shrink-0 border-2 border-white/10 bg-black flex items-center justify-center text-3xl shadow-[6px_6px_0px_#000] group-hover:scale-105 transition-transform"
               :class="a.unlocked ? 'border-white/20' : 'border-white/5'">
            {{ a.icon_url ?? '🏅' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-display font-black text-xl uppercase text-white tracking-tighter leading-none mb-2 group-hover:text-neon-cyan transition-colors">{{ a.title }}</p>
            <p class="font-sans text-[11px] font-bold uppercase text-white/30 leading-relaxed">{{ a.description }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between border-t-2 border-white/5 border-dashed pt-4 mt-4 relative z-10">
          <span class="font-pixel text-[10px] uppercase font-black tracking-widest px-3 py-1 bg-black border border-white/10"
                :class="a.unlocked ? 'text-white' : 'text-white/20'">
            {{ RARITY_STYLES[a.rarity]?.label ?? a.rarity }}
          </span>
          <span class="font-display text-sm font-black text-neon-yellow tracking-tighter">
            +{{ a.points_reward }} PTS
          </span>
        </div>

        <div v-if="a.unlocked" class="mt-4 font-pixel text-[10px] uppercase text-neon-cyan/50 tracking-[0.2em]">
           >> [REGISTRO]: {{ a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'ONLINE' }}
        </div>

        <!-- Lock icon with blocky background -->
        <div v-else class="absolute right-4 top-4 size-10 bg-white/5 flex items-center justify-center text-white/20 border border-white/5">
           <Icon icon="lucide:lock" class="text-xl" />
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
