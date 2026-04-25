<template>
  <footer class="relative border-t-4 border-neon-cyan bg-retro-black overflow-hidden">
    <!-- Scanlines -->
    <div class="gh-scanlines absolute inset-0 opacity-10 pointer-events-none z-0"></div>
    <!-- Ambient glow line -->
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-60"></div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-8">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 items-center">

        <!-- COL 1: Logo + copyright -->
        <div class="flex flex-col gap-2">
          <p class="font-display text-2xl font-black uppercase tracking-widest gh-title-gradient">
            GameHub
          </p>
          <p class="font-pixel text-[10px] text-white/30 uppercase tracking-[0.3em]">
            © 2026 // Sistema Arcade v2.0
          </p>
          <p class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">
            Todos los derechos reservados.
          </p>
        </div>

        <!-- COL 2: Slogan central -->
        <div class="flex flex-col items-center text-center gap-1">
          <div class="h-px w-12 bg-neon-pink mb-2"></div>
          <p class="font-display font-black text-lg uppercase tracking-widest text-neon-pink">
            PLAY HARD
          </p>
          <p class="font-display font-black text-lg uppercase tracking-widest text-neon-cyan">
            LEVEL UP_
          </p>
          <div class="h-px w-12 bg-neon-cyan mt-2"></div>
        </div>

        <!-- COL 3: HUD Status -->
        <div class="flex flex-col gap-2 sm:items-end">
          <div class="flex items-center gap-2">
            <div class="size-1.5 bg-neon-cyan animate-pulse shadow-[0_0_6px_#00f2ff]"></div>
            <span class="font-pixel text-[10px] text-neon-cyan uppercase tracking-widest">Servidor en línea</span>
          </div>
          <div class="gh-chip text-white/30">
            Latencia: <20ms
          </div>
          <div class="gh-chip text-neon-yellow">
            Juegos activos: 05
          </div>
        </div>

      </div>

      <!-- Bottom bar -->
      <div class="mt-8 pt-4 border-t-2 border-white/5 flex flex-wrap items-center justify-between gap-4">
        <p class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">
          Sistema GameHub // Todos los sistemas operativos
        </p>
        <div class="flex gap-4">
          <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">CLICKER</span>
          <span class="font-pixel text-[9px] text-white/10">|</span>
          <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">RPG</span>
          <span class="font-pixel text-[9px] text-white/10">|</span>
          <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">CONNECT4</span>
          <span class="font-pixel text-[9px] text-white/10">|</span>
          <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">QUIZ</span>
          <span class="font-pixel text-[9px] text-white/10">|</span>
          <span class="font-pixel text-[9px] text-white/20 uppercase tracking-widest">TD</span>
        </div>
      </div>
    </div>
  </footer>
</template>
