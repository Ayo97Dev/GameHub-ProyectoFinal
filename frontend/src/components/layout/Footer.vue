<template>
  <footer class="relative border-t-4 border-neon-cyan bg-retro-black overflow-hidden">
    <!-- Scanlines -->
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-0"></div>
    <!-- Ambient glow line -->
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-60"></div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 items-start text-center md:text-left">

        <!-- COL 1: Logo + creators + copyright -->
        <div class="flex flex-col gap-6 items-center md:items-start">
          <div class="flex flex-col gap-1">
            <p class="font-display text-3xl font-black uppercase tracking-widest gh-title-gradient">
              GameHub
            </p>
            <p class="font-pixel text-[10px] text-white/20 uppercase tracking-[0.3em]">
              Hub de Juegos Arcade
            </p>
          </div>

          <div class="space-y-3 w-full max-w-[280px] md:max-w-none">
             <div class="flex items-center gap-2 justify-center md:justify-start">
                <div class="h-[2px] w-6 bg-neon-cyan/50"></div>
                <span class="font-pixel text-[9px] text-white/30 uppercase tracking-[0.2em]">Desarrollado por</span>
             </div>
             <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-3 group justify-center md:justify-start">
                   <div class="size-1.5 bg-neon-cyan/30 group-hover:bg-neon-cyan transition-colors"></div>
                   <span class="font-display text-xs font-black text-white/60 group-hover:text-white uppercase tracking-tight transition-colors">Ayoze U. Méndez Fumero</span>
                </div>
                <div class="flex items-center gap-3 group justify-center md:justify-start">
                   <div class="size-1.5 bg-neon-cyan/30 group-hover:bg-neon-cyan transition-colors"></div>
                   <span class="font-display text-xs font-black text-white/60 group-hover:text-white uppercase tracking-tight transition-colors">Pedro Damián Barrera Fernández</span>
                </div>
             </div>
          </div>
          
          <p class="font-pixel text-[9px] text-white/10 uppercase tracking-widest mt-2">
            © 2026 // Proyecto Final DAW
          </p>
        </div>

        <!-- COL 2: Slogan central -->
        <div class="flex flex-col items-center justify-center h-full gap-1 py-8 md:py-0 border-y md:border-y-0 border-white/5 order-first lg:order-none md:col-span-2 lg:col-span-1">
          <div class="h-px w-16 bg-neon-pink/50 mb-3"></div>
          <p class="font-display font-black text-2xl uppercase tracking-[0.2em] text-neon-pink gh-title-glow">
            PLAY HARD
          </p>
          <p class="font-display font-black text-2xl uppercase tracking-[0.2em] text-neon-cyan gh-title-glow">
            LEVEL UP<span class="animate-blink">_</span>
          </p>
          <div class="h-px w-16 bg-neon-cyan/50 mt-3"></div>
        </div>

        <!-- COL 3: Legal & Manifest -->
        <div class="flex flex-col gap-6 md:items-end md:col-span-2 lg:col-span-1">
           <div class="flex items-center gap-3 justify-center md:justify-end">
              <span class="font-pixel text-[10px] text-neon-cyan uppercase tracking-[0.3em]">Información Legal</span>
              <div class="size-2 bg-neon-cyan animate-pulse shadow-[0_0_8px_#00f2ff]"></div>
           </div>
           
           <div class="flex flex-col gap-3 w-full md:max-w-[320px] lg:w-auto lg:min-w-[240px]">
              <button class="group flex items-center justify-between gap-4 p-3 bg-white/5 border-l-4 border-white/10 hover:border-neon-cyan hover:bg-neon-cyan/10 transition-all duration-300 shadow-[4px_4px_0px_rgba(0,0,0,0.3)] hover:translate-x-[-2px] hover:translate-y-[-2px]">
                 <span class="font-pixel text-[9px] text-white/30 group-hover:text-white uppercase">RGPD</span>
                 <span class="font-display text-xs font-black text-white/60 group-hover:text-neon-cyan uppercase tracking-widest">Protección de Datos</span>
              </button>
              
              <button class="group flex items-center justify-between gap-4 p-3 bg-white/5 border-l-4 border-white/10 hover:border-neon-pink hover:bg-neon-pink/10 transition-all duration-300 shadow-[4px_4px_0px_rgba(0,0,0,0.3)] hover:translate-x-[-2px] hover:translate-y-[-2px]">
                 <span class="font-pixel text-[9px] text-white/30 group-hover:text-white uppercase">Terms</span>
                 <span class="font-display text-xs font-black text-white/60 group-hover:text-neon-pink uppercase tracking-widest">Términos de Uso</span>
              </button>
              
              <button class="group flex items-center justify-between gap-4 p-3 bg-white/5 border-l-4 border-white/10 hover:border-neon-yellow hover:bg-neon-yellow/10 transition-all duration-300 shadow-[4px_4px_0px_rgba(0,0,0,0.3)] hover:translate-x-[-2px] hover:translate-y-[-2px]">
                 <span class="font-pixel text-[9px] text-white/30 group-hover:text-white uppercase">Priv</span>
                 <span class="font-display text-xs font-black text-white/60 group-hover:text-neon-yellow uppercase tracking-widest">Privacidad</span>
              </button>
           </div>
        </div>

      </div>
    </div>
  </footer>
</template>

<style scoped>
@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
.animate-blink {
  animation: blink 1s step-end infinite;
}
</style>

