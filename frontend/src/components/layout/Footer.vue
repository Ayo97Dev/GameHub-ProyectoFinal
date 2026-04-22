<template>
  <footer class="bg-black border-t-4 border-retro-black p-10 relative overflow-hidden">
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
    
    <div class="mx-auto flex w-full max-w-[100rem] flex-col md:flex-row items-center justify-between gap-10 relative z-10">
      <div class="flex flex-col items-center md:items-start gap-4">
        <div class="flex items-center gap-4">
           <div class="size-6 bg-white/10 border border-white/20"></div>
           <p class="font-pixel text-[10px] text-white/40 uppercase tracking-[0.5em]">© 2026 GAMEHUB_OS_V2 // NODE_MASTER_ACTIVE</p>
        </div>
        <p class="font-display font-black text-4xl uppercase tracking-tighter text-white/10 group-hover:text-white/20 transition-colors">
          PLAY HARD // LEVEL UP_
        </p>
      </div>

      <div class="flex flex-col items-center md:items-end gap-6">
         <div class="flex gap-4">
            <div v-for="i in 4" :key="i" class="size-2" :class="i === 1 ? 'bg-neon-cyan' : i === 2 ? 'bg-neon-pink' : i === 3 ? 'bg-neon-yellow' : 'bg-neon-green'"></div>
         </div>
         <div class="flex items-center gap-6">
            <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">ENCRYPTED_CONNECTION</span>
            <div class="h-[2px] w-12 bg-white/5"></div>
            <span class="font-pixel text-[9px] text-neon-cyan uppercase tracking-widest animate-pulse">UP_TIME: 100%</span>
         </div>
      </div>
    </div>
  </footer>
</template>
