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
  <section class="mx-auto w-full max-w-5xl px-4 py-16">
    <header class="mb-10 p-6 border-[4px] border-retro-black dark:border-neon-pink shadow-[8px_8px_0px_#09090b] dark:shadow-[8px_8px_0px_#f472b6] relative overflow-hidden bg-retro-cream dark:bg-black">
      <div class="gh-scanlines absolute inset-0 opacity-20 pointer-events-none"></div>
      <p class="relative z-10 font-pixel text-xs font-bold uppercase tracking-widest text-neon-blue dark:text-neon-cyan border-b-2 border-retro-black dark:border-neon-pink pb-1 inline-block">SYSTEM_ARCHIVE</p>
      <h1 class="relative z-10 mt-3 text-4xl font-display font-black uppercase text-retro-black dark:text-retro-white">ACHIEVEMENTS</h1>
      <p class="relative z-10 mt-4 bg-retro-black text-white dark:bg-neon-pink dark:text-black font-pixel text-sm px-3 py-1 uppercase inline-block">
        UNLOCKED: {{ achievementStore.achievements.filter(a => a.unlocked).length }} / {{ achievementStore.achievements.length }}
      </p>
    </header>

    <div v-if="achievementStore.isLoading && achievementStore.achievements.length === 0" class="flex justify-center py-16 text-retro-black dark:text-neon-pink font-pixel text-2xl uppercase blink gh-panel">
      SYNCING_DATA...
    </div>

    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="a in achievementStore.achievements"
        :key="a.id"
        class="gh-panel relative p-5 transition-all outline-none"
        :class="[RARITY_STYLES[a.rarity]?.classes ?? RARITY_STYLES.common.classes, !a.unlocked && 'opacity-50 grayscale border-dashed shadow-none']"
      >
        <!-- Icono o emoji -->
        <div class="mb-4 flex items-start gap-4">
          <div class="size-12 shrink-0 border-[3px] border-retro-black dark:border-retro-white bg-white dark:bg-black flex items-center justify-center text-2xl shadow-[4px_4px_0px_#09090b] dark:shadow-[4px_4px_0px_#fafafa]">
            {{ a.icon_url ?? '🏅' }}
          </div>
          <div class="flex-1 min-w-0 pt-1">
            <p class="font-display font-black text-lg uppercase text-retro-black dark:text-retro-white leading-none mb-2">{{ a.title }}</p>
            <p class="font-sans text-[10px] font-bold uppercase text-retro-black dark:text-retro-white">{{ a.description }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between border-t-2 border-retro-black dark:border-retro-white border-dashed pt-3 mt-3">
          <span class="font-pixel text-[10px] uppercase font-bold tracking-widest px-1 bg-retro-black text-white dark:bg-white dark:text-black">
            {{ RARITY_STYLES[a.rarity]?.label ?? a.rarity }}
          </span>
          <span class="font-pixel text-[10px] font-bold text-neon-blue dark:text-neon-yellow border-b border-retro-black dark:border-neon-yellow">
            +{{ a.points_reward }} PTS
          </span>
        </div>

        <div v-if="a.unlocked" class="mt-3 font-pixel text-[10px] uppercase text-neon-blue dark:text-neon-cyan">
          [ACQUIRED]: {{ a.earned_at ? new Date(a.earned_at).toLocaleDateString() : 'ONLINE' }}
        </div>

        <!-- Candado si no está desbloqueado -->
        <div v-else class="absolute right-3 top-3 text-retro-black dark:text-retro-white font-pixel text-xl">🔒</div>
      </div>

      <div v-if="achievementStore.achievements.length === 0" class="col-span-full gh-panel p-8 text-center text-retro-black dark:text-neon-cyan font-pixel text-xl uppercase">
        NO_ACHIEVEMENTS_REGISTERED
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
