<template>
  <section class="h-full flex flex-col bg-[#0a0a0a] text-[#b8a38a] font-rpg-body relative overflow-hidden">
    <!-- Dynamic Font Import -->
    <component is="style">
      @import url('https://fonts.googleapis.com/css2?family=MedievalSharp&family=Bitter:wght@400;700&display=swap');
      .font-fantasy { font-family: 'MedievalSharp', cursive; }
      .font-rpg-body { font-family: 'Bitter', serif; }
    </component>

    <!-- INITIAL SPLASH SCREEN (Grim Tome Style) -->
    <Transition name="fade-grim">
      <div v-if="phase === 'idle'" class="absolute inset-0 z-[100] flex items-center justify-center bg-[#070707]">
        <!-- Flickering Shadow Overlay -->
        <div class="absolute inset-0 opacity-30 animate-flicker pointer-events-none bg-[radial-gradient(circle_at_50%_50%,rgba(60,40,20,0.4),transparent_70%)]"></div>
        
        <div class="relative z-10 flex flex-col items-center gap-10 text-center px-6">
          <div class="space-y-3">
            <span class="text-[#8c2d1f] font-fantasy text-sm tracking-[0.5em] uppercase opacity-50 animate-pulse">A GameHub Dark Chronicle</span>
            <h1 class="text-6xl md:text-7xl font-fantasy text-[#b8a38a] drop-shadow-[0_10px_20px_rgba(0,0,0,1)] tracking-tighter uppercase leading-none">Descenso al Abismo</h1>
            <div class="h-1 w-32 mx-auto bg-gradient-to-r from-transparent via-[#8c2d1f] to-transparent"></div>
          </div>
          
          <div class="flex flex-col gap-4">
            <button 
              @click="startNewRun" 
              class="group relative px-12 py-6 transition-all duration-500 transform hover:scale-105 active:scale-95"
            >
              <div class="absolute inset-0 bg-[#1a1a1a] border-4 border-[#3c2a1a] shadow-[10px_10px_30px_rgba(0,0,0,0.8)]"></div>
              <div class="absolute inset-1 border-2 border-[#8c2d1f]/30"></div>
              <span class="relative z-10 font-fantasy text-3xl text-[#b8a38a] group-hover:text-white uppercase tracking-[0.2em] transition-colors">Nueva Gesta</span>
            </button>

            <button 
              v-if="hasSave && saveSummary"
              @click="loadRun" 
              class="group relative px-12 py-4 transition-all duration-500 transform hover:scale-105 active:scale-95 overflow-hidden"
            >
              <div class="absolute inset-0 bg-[#3c2a1a] border-2 border-[#8c2d1f]/40 shadow-lg"></div>
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
              <div class="relative z-10 flex flex-col items-center">
                <span class="font-fantasy text-xl text-[#b8a38a] group-hover:text-white uppercase tracking-widest transition-colors">Continuar Gesta</span>
                <span class="text-[10px] text-amber-600 font-fantasy uppercase tracking-wider mt-1 opacity-80">
                  {{ saveSummary.className }} • Nivel {{ saveSummary.level }} • Piso {{ saveSummary.floor }}
                </span>
              </div>
            </button>
          </div>

          <p class="text-[#5c4a3a] font-serif italic text-lg max-w-md leading-relaxed">
            "Donde la luz se desvanece y la cordura se quiebra, comienza tu verdadera gesta."
          </p>
        </div>
      </div>
    </Transition>

    <!-- FULLSCREEN DUNGEON OVERLAY (TELEPORTED TO BODY) -->
    <Teleport to="body">
      <Transition name="modal-grim">
        <div v-if="showModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black overflow-hidden select-none">
          
          <!-- EXIT SEAL -->
          <button 
            @click="showExitConfirm = true" 
            class="fixed top-4 right-4 z-[100050] size-14 flex items-center justify-center group transition-transform hover:rotate-12 active:scale-90"
          >
            <div class="absolute inset-0 bg-[#8c2d1f] rounded-full shadow-[0_4px_12px_rgba(0,0,0,1)] border-4 border-[#5c1a11] group-hover:bg-[#a63626] transition-colors"></div>
            <div class="absolute inset-1.5 border-2 border-dashed border-black/30 rounded-full"></div>
            <Icon icon="game-icons:death-skull" class="relative z-10 text-black text-2xl pointer-events-none" />
          </button>

          <!-- Main Parchment Frame -->
          <div class="relative w-full h-full flex flex-col bg-[#24211e] shadow-inner border-[16px] border-double border-[#3c2a1a] z-10">
            
            <!-- CORNER ORNAMENTS -->
            <div class="absolute top-0 left-0 size-24 pointer-events-none z-50 text-[#3c2a1a] opacity-40 mix-blend-multiply">
              <svg viewBox="0 0 100 100" class="fill-current"><path d="M0 0h100v12H12v88H0V0z"/></svg>
            </div>
            <div class="absolute top-0 right-0 size-24 pointer-events-none z-50 text-[#3c2a1a] opacity-40 mix-blend-multiply transform rotate-90">
              <svg viewBox="0 0 100 100" class="fill-current"><path d="M0 0h100v12H12v88H0V0z"/></svg>
            </div>
            <div class="absolute bottom-0 left-0 size-24 pointer-events-none z-50 text-[#3c2a1a] opacity-40 mix-blend-multiply transform -rotate-90">
              <svg viewBox="0 0 100 100" class="fill-current"><path d="M0 0h100v12H12v88H0V0z"/></svg>
            </div>
            <div class="absolute bottom-0 right-0 size-24 pointer-events-none z-50 text-[#3c2a1a] opacity-40 mix-blend-multiply transform rotate-180">
              <svg viewBox="0 0 100 100" class="fill-current"><path d="M0 0h100v12H12v88H0V0z"/></svg>
            </div>

            <!-- Texture Overlays -->
            <div class="absolute inset-0 pointer-events-none opacity-50 mix-blend-multiply bg-[radial-gradient(circle,#fdf0dc_0%,#e8d3b9_100%)]"></div>
            <div class="absolute inset-0 pointer-events-none opacity-20 bg-[url('https://www.transparenttextures.com/patterns/natural-paper.png')]"></div>
            <div class="absolute inset-0 pointer-events-none z-40 bg-[radial-gradient(circle,transparent_50%,rgba(0,0,0,0.3)_130%)]"></div>

            <!-- HEADER -->
            <header class="relative z-[60] p-4 sm:p-5 pr-48 lg:pr-64 flex flex-col md:flex-row items-center justify-between gap-6 border-b-4 border-double border-[#3c2a1a] bg-black/40 backdrop-blur-sm">
              <div class="flex items-center gap-6">
                <div class="size-14 bg-[#0a0a0a] flex items-center justify-center text-[#8c2d1f] text-3xl border-4 border-[#3c2a1a] shadow-2xl iron-shadow transform -rotate-3">
                  <Icon icon="game-icons:crossed-swords" />
                </div>
                <div>
                  <h2 class="font-fantasy text-2xl text-[#b8a38a] uppercase tracking-wide drop-shadow-md">Descenso al Abismo</h2>
                  <div class="flex items-center gap-3 text-[#b8a38a]/70 font-fantasy text-[10px] uppercase tracking-[0.2em]">
                    <span class="animate-pulse text-[#8c2d1f]">●</span>
                    <span v-if="hero">{{ hero.className }} • Nivel {{ hero.level }} • PISO {{ run.floor }}</span>
                    <span v-else>Seleccionando Destino</span>
                  </div>
                  
                  <!-- XP BAR IN HEADER -->
                  <div v-if="hero" class="w-full max-w-[240px] mt-2 group/xp relative">
                    <div class="h-1.5 w-full bg-black/60 border border-[#3c2a1a]/40 p-0.5 shadow-inner relative overflow-hidden">
                      <div 
                        class="h-full bg-gradient-to-r from-amber-900 via-amber-600 to-amber-400 transition-all duration-1000 relative z-10" 
                        :style="{ width: (hero.exp/hero.nextLevelExp*100) + '%' }"
                      ></div>
                      <!-- Gloss effect -->
                      <div class="absolute inset-0 bg-white/5 z-20 pointer-events-none"></div>
                    </div>
                    
                    <!-- XP Tooltip on hover -->
                    <div class="absolute top-full left-0 mt-1 px-2 py-0.5 bg-black/90 border border-[#3c2a1a] text-[8px] text-amber-500 font-fantasy uppercase tracking-tighter opacity-0 group-hover/xp:opacity-100 transition-opacity z-50">
                      XP: {{ Math.floor(hero.exp) }} / {{ hero.nextLevelExp }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-8">
                <!-- MAP TRACKER -->
                <div v-if="hero" class="relative flex gap-4 p-2.5 bg-black/30 border border-[#3c2a1a] shadow-inner items-center min-w-[240px] justify-center">
                  <div class="absolute top-1/2 left-8 right-8 h-0.5 bg-[#3c2a1a]/30 -translate-y-1/2 z-0"></div>
                  <div 
                    class="absolute top-1/2 left-8 h-0.5 bg-gradient-to-r from-[#8c2d1f] to-white/40 -translate-y-1/2 z-0 transition-all duration-1000"
                    :style="{ width: ((run.roomInFloor - 1) / 2 * 100) + '%' }"
                  ></div>

                  <div
                    v-for="idx in 3"
                    :key="idx"
                    class="size-4.5 rounded-full border-2 transition-all duration-700 flex items-center justify-center relative z-10"
                    :class="idx === run.roomInFloor
                      ? 'bg-white border-white shadow-[0_0_15px_rgba(255,255,255,0.8)] scale-125'
                      : (idx < run.roomInFloor ? 'bg-[#8c2d1f] border-[#5c1a11] shadow-[0_0_8px_rgba(140,45,31,0.4)]' : 'bg-black/80 border-[#3c2a1a]')"
                  >
                    <Icon v-if="idx === 3" :icon="run.floor % 10 === 0 ? 'game-icons:death-skull' : 'game-icons:doorway'" class="text-[8px]" :class="idx <= run.roomInFloor ? 'text-black' : 'text-[#3c2a1a]'" />
                  </div>
                </div>
                
                <div class="flex gap-3">
                    <button @click="phase = 'classSelect'" class="group relative px-6 py-2 transition-all">
                        <div class="absolute inset-0 bg-[#0a0a0a] border border-[#3c2a1a] group-hover:bg-black group-hover:border-[#b8a38a]/40 transition-colors"></div>
                        <span class="relative z-10 font-fantasy text-[10px] uppercase text-[#b8a38a] group-hover:text-white">Cambiar Senda</span>
                    </button>
                    <button @click="saveRun" class="group relative px-6 py-2 overflow-hidden transition-all shadow-lg">
                        <div class="absolute inset-0 bg-[#8c2d1f] border border-white/10 group-hover:scale-105 transition-transform"></div>
                        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/black-paper.png')] opacity-20"></div>
                        <span class="relative z-10 font-fantasy text-[10px] uppercase text-white tracking-widest">Sellar Gesta</span>
                    </button>
                </div>
              </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 relative z-[70] flex flex-col lg:flex-row min-h-0 gap-8 p-6 lg:p-8 overflow-visible">
              
              <!-- CLASS SELECTION SCREEN -->
              <div v-if="phase === 'classSelect'" class="absolute inset-0 z-[100] bg-[#1a1714] p-8 overflow-auto custom-scroll flex flex-col items-center">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] pointer-events-none"></div>
                <h2 class="text-4xl font-fantasy text-[#b8a38a] mb-12 uppercase tracking-[0.2em] relative z-10">Escoge tu Senda</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-7xl w-full pb-20 relative z-10">
                  <div 
                    v-for="c in classes" 
                    :key="c.id"
                    @click="unlockedClasses.includes(c.id) && selectClass(c.id)"
                    class="group relative p-6 bg-[#0a0a0a] border-4 border-[#3c2a1a] transition-all flex flex-col gap-4 transform hover:-translate-y-2"
                    :class="unlockedClasses.includes(c.id) ? 'hover:border-[#8c2d1f] cursor-pointer' : 'opacity-60 grayscale cursor-not-allowed'"
                  >
                    <div class="flex items-center justify-between border-b border-[#3c2a1a] pb-2">
                      <span class="font-fantasy text-xl text-[#b8a38a] group-hover:text-white uppercase">{{ c.name }}</span>
                      <div class="flex flex-col items-end">
                        <span class="text-[10px] text-[#8c2d1f] font-fantasy uppercase px-2 py-0.5 bg-black border border-[#8c2d1f]/30">{{ c.role }}</span>
                        <span v-if="!unlockedClasses.includes(c.id)" class="text-[9px] text-amber-500 font-fantasy mt-1 flex items-center gap-1">
                          <Icon icon="game-icons:padlock" class="size-3" /> Tienda Global
                        </span>
                      </div>
                    </div>
                    <p class="font-serif text-sm italic text-[#b8a38a]/60 leading-tight h-12 overflow-hidden">{{ c.description }}</p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[9px] font-fantasy text-[#b8a38a]/40 uppercase">
                      <div class="flex justify-between border-b border-[#3c2a1a]/40 pb-0.5"><span>Vida</span><span class="text-red-500">{{ c.stats.hp }}</span></div>
                      <div class="flex justify-between border-b border-[#3c2a1a]/40 pb-0.5"><span>Maná</span><span class="text-blue-500">{{ c.stats.mp }}</span></div>
                      <div class="flex justify-between"><span>Ataque</span><span class="text-amber-500">{{ c.stats.attack }}</span></div>
                      <div class="flex justify-between"><span>P. Mágico</span><span class="text-blue-400">{{ c.stats.magicAttack }}</span></div>
                      <div class="flex justify-between"><span>Defensa</span><span class="text-slate-400">{{ c.stats.defense }}</span></div>
                      <div class="flex justify-between"><span>D. Mágica</span><span class="text-purple-400">{{ c.stats.magicDefense }}</span></div>
                      <div class="flex justify-between"><span>Agilidad</span><span class="text-green-400">{{ c.stats.speed }}</span></div>
                      <div class="flex justify-between"><span>Rec. Maná</span><span class="text-blue-300">+{{ c.stats.manaRegen }}</span></div>
                    </div>
                    <div v-if="unlockedClasses.includes(c.id)" class="absolute inset-0 bg-[#8c2d1f]/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                  </div>
                </div>
                <button @click="phase = 'idle'" class="mt-8 px-10 py-3 bg-[#3c2a1a] text-[#b8a38a] font-fantasy uppercase tracking-widest hover:bg-[#4d3621]">Regresar</button>
              </div>


              <!-- EVENT OVERLAY -->
              <div v-if="phase === 'event' && currentEvent" class="absolute inset-0 z-[150] bg-black/90 backdrop-blur-md flex items-center justify-center p-8">
                 <div class="max-w-2xl w-full bg-[#1a1714] border-[10px] border-double border-[#3c2a1a] p-12 text-center space-y-8 relative shadow-[0_0_100px_rgba(0,0,0,1)]">
                    <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] pointer-events-none"></div>
                    <h3 class="text-3xl font-fantasy text-amber-600 uppercase tracking-[0.3em]">{{ currentEvent.title }}</h3>
                    <p class="text-[#b8a38a] font-serif italic text-lg leading-relaxed">{{ currentEvent.text }}</p>
                    <div class="flex flex-col gap-4 pt-6">
                        <button 
                            v-for="choice in currentEvent.choices" 
                            :key="choice.text"
                            :disabled="!checkChoiceCondition(choice)"
                            @click="handleEventChoice(choice)"
                            class="group relative py-4 bg-[#0a0a0a] border-2 border-[#3c2a1a] hover:border-[#8c2d1f] transition-all disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed"
                        >
                            <span class="relative z-10 font-fantasy text-[#b8a38a] group-hover:text-white uppercase tracking-widest">{{ choice.text }}</span>
                            <span v-if="!checkChoiceCondition(choice)" class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-red-500 font-fantasy">Recursos Insuficientes</span>
                        </button>
                    </div>
                 </div>
              </div>

              <!-- COMBAT ZONE -->
              <div v-if="hero && phase !== 'classSelect'" class="flex-1 flex flex-col gap-6 lg:max-w-5xl mx-auto w-full h-full min-h-0">
                <div class="flex-1 flex flex-col relative min-h-0 justify-center">
                   <div class="flex justify-between items-center mb-6 pb-4 border-b-2 border-dashed border-[#3c2a1a]/40">
                      <div class="flex items-center gap-4">
                         <span v-if="phase === 'combat'" class="font-fantasy bg-[#5c1a11]/40 text-red-500 px-4 py-1.5 border border-red-900/40 animate-pulse text-[10px] uppercase">Conflicto</span>
                         <p class="font-fantasy text-base text-[#b8a38a]/50 uppercase tracking-widest">{{ phase === 'combat' ? 'Crueldad del Destino' : 'Silencio en las Sombras' }}</p>
                      </div>
                      <div v-if="phase === 'combat'" class="font-fantasy text-[#8c2d1f] text-[10px] italic bg-black/10 px-6 py-1.5 border-l-4 border-[#8c2d1f] uppercase tracking-widest">
                         {{ turn === 'hero' ? 'Turno del Elegido' : 'La Oscuridad Acecha' }}
                      </div>
                   </div>

                   <div class="grid grid-cols-1 sm:grid-cols-2 gap-16 items-center px-6 relative flex-1 min-h-0">
                      <!-- HERO AVATAR -->
                      <div v-if="hero" class="flex flex-col items-center" :class="{ 'animate-shake': hitHero }">
                         <div class="relative group">
                            <div v-if="turn === 'hero' && phase === 'combat'" class="absolute inset-0 bg-[#b8a38a]/5 blur-[60px] rounded-full scale-150 animate-pulse-slow"></div>
                            <div 
                              class="size-48 sm:size-60 bg-[#0a0a0a] border-[8px] border-double border-[#3c2a1a] flex items-center justify-center transition-all duration-700 transform relative z-10 overflow-hidden shadow-[inset_0_0_40px_rgba(0,0,0,1)]"
                              :class="{ 'border-[#b8a38a]/30 shadow-[0_0_40px_rgba(184,163,138,0.1),inset_0_0_40px_rgba(0,0,0,1)]': turn === 'hero' && phase === 'combat' }"
                            >
                               <Icon icon="game-icons:shield-echoes" class="size-36 text-[#b8a38a]/20 drop-shadow-md" />
                               <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            </div>
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-8 py-2 bg-[#1a1a1a] text-[#b8a38a] font-fantasy text-xs uppercase tracking-[0.3em] shadow-2xl border-2 border-[#3c2a1a] z-20">{{ hero.className }}</div>
                             
                             <!-- STATS TOOLTIP (Right side) -->
                             <div class="absolute left-full top-0 ml-8 w-64 bg-[#0a0a0a] border-2 border-[#3c2a1a] p-5 pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-300 z-[1000] shadow-[0_0_40px_rgba(0,0,0,0.9)] -translate-x-4 group-hover:translate-x-0">
                               <p class="font-fantasy text-xs text-[#b8a38a] border-b border-[#3c2a1a]/40 pb-2 mb-4 uppercase tracking-[0.2em] text-center">Atributos del Héroe</p>
                               <div class="grid grid-cols-1 gap-y-2.5 font-fantasy text-[10px] uppercase">
                                 <div class="flex justify-between items-center"><span class="text-white/40">Poder de Ataque</span><span class="text-amber-500 font-bold text-base">{{ hero.attack }}</span></div>
                                 <div class="flex justify-between items-center"><span class="text-white/40">Defensa Física</span><span class="text-slate-400 font-bold text-base">{{ hero.defense }}</span></div>
                                 <div class="flex justify-between items-center"><span class="text-white/40">Poder Arcano</span><span class="text-blue-400 font-bold text-base">{{ hero.magicAttack }}</span></div>
                                 <div class="flex justify-between items-center"><span class="text-white/40">Defensa Mágica</span><span class="text-purple-400 font-bold text-base">{{ hero.magicDefense }}</span></div>
                                 <div class="flex justify-between items-center"><span class="text-white/40">Agilidad</span><span class="text-green-400 font-bold text-base">{{ hero.speed }}</span></div>
                                 <div class="flex justify-between items-center">
                                   <span class="text-white/40">Prob. Crítico</span>
                                   <div class="flex items-center gap-2">
                                     <span class="text-red-400 font-bold text-base">{{ hero.critRate + Math.floor(hero.speed / 2) }}%</span>
                                     <span class="text-[8px] text-[#b8a38a]/30">({{ hero.critRate }} + {{ Math.floor(hero.speed / 2) }})</span>
                                   </div>
                                 </div>
                                 <div class="flex justify-between items-center">
                                   <span class="text-white/40">Evasión</span>
                                   <div class="flex items-center gap-2">
                                     <span class="text-sky-400 font-bold text-base">{{ hero.evasion + Math.floor(hero.speed / 2) }}%</span>
                                     <span class="text-[8px] text-[#b8a38a]/30">({{ hero.evasion }} + {{ Math.floor(hero.speed / 2) }})</span>
                                   </div>
                                 </div>
                                 <div class="flex justify-between items-center"><span class="text-white/40">Flujo de Maná</span><span class="text-blue-300 font-bold text-base">+{{ hero.manaRegen }}</span></div>
                               </div>
                               <!-- Arrow pointing left -->
                               <div class="absolute right-full top-10 -translate-y-1/2 border-8 border-transparent border-r-[#3c2a1a]"></div>
                             </div>
                         </div>
                         
                         <div class="w-full mt-10 space-y-3 max-w-[240px]">
                            <!-- HP VIAL -->
                            <div class="space-y-1">
                               <div class="flex justify-between font-fantasy text-[9px] text-[#b8a38a]/60 uppercase tracking-widest">
                                  <span>VITALIDAD</span>
                                  <span class="text-[#8c2d1f] font-bold">{{ Math.floor(hero.hp) }} / {{ hero.maxHp }}</span>
                               </div>
                               <div class="h-3 w-full bg-black/60 border-2 border-[#3c2a1a] p-0.5 shadow-inner relative">
                                  <div class="h-full bg-gradient-to-r from-[#5c1a11] via-[#8c2d1f] to-[#b91c1c] transition-all duration-1000 relative overflow-hidden" :style="{ width: (hero.hp/hero.maxHp*100) + '%' }">
                                     <div class="absolute inset-y-0 left-0 w-full animate-liquid-bubble opacity-30 bg-gradient-to-t from-white/20 to-transparent"></div>
                                  </div>
                               </div>
                            </div>
                            <!-- MP VIAL -->
                            <div class="space-y-1">
                               <div class="flex justify-between font-fantasy text-[9px] text-[#b8a38a]/60 uppercase tracking-widest">
                                  <span>MANÁ</span>
                                  <span class="text-blue-500 font-bold">{{ Math.floor(hero.mp) }} / {{ hero.maxMp }}</span>
                               </div>
                               <div class="h-3 w-full bg-black/60 border-2 border-[#3c2a1a] p-0.5 shadow-inner relative">
                                  <div class="h-full bg-gradient-to-r from-[#172554] via-[#1e3a8a] to-[#3b82f6] transition-all duration-1000 relative overflow-hidden" :style="{ width: (hero.mp/hero.maxMp*100) + '%' }">
                                     <div class="absolute inset-y-0 left-0 w-full animate-liquid-bubble-slow opacity-20 bg-gradient-to-t from-white/10 to-transparent"></div>
                                  </div>
                               </div>
                            </div>
                         </div>

                         <!-- STATUS ICONS -->
                         <div class="flex flex-wrap gap-2 mt-4 justify-center">
                            <div 
                              v-for="s in hero.statuses" 
                              :key="s.id"
                              class="group/status relative size-8 bg-black/40 border border-[#3c2a1a] flex items-center justify-center rounded-sm"
                            >
                               <Icon :icon="getStatusIcon(s.id)" class="text-lg" :class="getStatusColor(s.id)" />
                               <span class="absolute -top-2 -right-2 size-4 bg-[#8c2d1f] text-[8px] flex items-center justify-center rounded-full text-white font-fantasy border border-[#5c1a11]">{{ s.duration }}</span>
                               
                               <!-- Status Tooltip -->
                               <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-32 bg-[#0a0a0a] border border-[#3c2a1a] p-2 pointer-events-none opacity-0 group-hover/status:opacity-100 transition-all z-[200] shadow-xl">
                                  <p class="font-fantasy text-[9px] uppercase" :class="getStatusColor(s.id)">{{ getStatusTitle(s.id) }}</p>
                                  <p class="text-[7px] text-[#b8a38a]/60 leading-tight">Activo por {{ s.duration }} turnos.</p>
                               </div>
                            </div>
                         </div>
                      </div>

                      <!-- ENEMY AVATAR -->
                      <div v-if="enemy" class="flex flex-col items-center group" :class="{ 'animate-shake': hitEnemy }">
                         <div class="relative">
                            <div v-if="turn === 'enemy' && phase === 'combat'" class="absolute inset-0 bg-red-950/20 blur-[60px] rounded-full scale-150 animate-pulse"></div>
                            <div 
                              class="size-48 sm:size-60 bg-black/80 border-[8px] border-double border-red-950/40 flex items-center justify-center transition-all duration-700 transform relative z-10 shadow-[inset_0_0_50px_rgba(0,0,0,1)]"
                              :class="{ 'border-red-900 shadow-[0_0_40px_rgba(153,27,27,0.1),inset_0_0_50px_rgba(0,0,0,1)]': turn === 'enemy' && phase === 'combat' }"
                            >
                               <Icon icon="game-icons:death-skull" class="size-36 text-red-950/10" />
                            </div>
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-8 py-2 bg-[#1a0505] text-red-700 font-fantasy text-xs uppercase tracking-[0.3em] shadow-2xl border-2 border-red-950 z-20">{{ enemy.name }}</div>

                             <!-- STATS TOOLTIP (Left side) -->
                             <div class="absolute right-full top-0 mr-8 w-64 bg-[#0a0505] border-2 border-red-950/40 p-5 pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-300 z-[1000] shadow-[0_0_40px_rgba(0,0,0,0.9)] translate-x-4 group-hover:translate-x-0">
                               <p class="font-fantasy text-xs text-red-700 border-b border-red-950/30 pb-2 mb-4 uppercase tracking-[0.2em] text-center">Esencia de la Prole</p>
                               <div class="grid grid-cols-1 gap-y-2.5 font-fantasy text-[10px] uppercase">
                                 <div class="flex justify-between items-center text-red-900/60"><span>Fuerza Bruta</span><span class="text-red-700 font-bold text-base">{{ enemy.attack }}</span></div>
                                 <div class="flex justify-between items-center text-red-900/60"><span>Caparazón</span><span class="text-red-700 font-bold text-base">{{ enemy.defense }}</span></div>
                                 <div class="flex justify-between items-center text-red-900/60"><span>Presencia Maligna</span><span class="text-red-700 font-bold text-base">{{ enemy.magicAttack }}</span></div>
                                 <div class="flex justify-between items-center text-red-900/60"><span>Voluntad Oscura</span><span class="text-red-700 font-bold text-base">{{ enemy.magicDefense }}</span></div>
                               </div>
                               <!-- Arrow pointing right -->
                               <div class="absolute left-full top-10 -translate-y-1/2 border-8 border-transparent border-l-red-950/40"></div>
                             </div>
                         </div>

                         <div class="w-full mt-10 max-w-[240px]">
                            <div class="flex justify-between font-fantasy text-[9px] text-red-900/60 uppercase tracking-widest">
                               <span>MALICIA</span>
                               <span class="font-bold">{{ Math.floor(enemy.hp) }} / {{ enemy.maxHp }}</span>
                            </div>
                            <div class="h-4 w-full bg-black/60 border-2 border-red-950/40 p-0.5 shadow-inner relative">
                               <div 
                                 class="h-full bg-gradient-to-r from-[#450a0a] via-[#7f1d1d] to-[#991b1b] transition-all duration-1000" 
                                 :style="{ width: (enemy.hp/enemy.maxHp*100) + '%' }"
                               ></div>
                            </div>
                            <p class="font-fantasy text-[9px] text-[#b8a38a]/20 text-center mt-4 uppercase tracking-[0.4em] italic leading-none">PROLE • NIVEL {{ enemy.level }}</p>
                         </div>

                         <!-- STATUS ICONS -->
                         <div class="flex flex-wrap gap-2 mt-4 justify-center">
                            <div 
                              v-for="s in enemy.statuses" 
                              :key="s.id"
                              class="group/status relative size-8 bg-black/40 border border-red-950/40 flex items-center justify-center rounded-sm"
                            >
                               <Icon :icon="getStatusIcon(s.id)" class="text-lg" :class="getStatusColor(s.id)" />
                               <span class="absolute -top-2 -right-2 size-4 bg-red-950 text-[8px] flex items-center justify-center rounded-full text-white font-fantasy border border-red-900">{{ s.duration }}</span>
                               
                               <!-- Status Tooltip -->
                               <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-32 bg-[#0a0505] border border-red-950/40 p-2 pointer-events-none opacity-0 group-hover/status:opacity-100 transition-all z-[200] shadow-xl">
                                  <p class="font-fantasy text-[9px] uppercase" :class="getStatusColor(s.id)">{{ getStatusTitle(s.id) }}</p>
                                  <p class="text-[7px] text-[#b8a38a]/60 leading-tight">Activo por {{ s.duration }} turnos.</p>
                               </div>
                            </div>
                         </div>
                      </div>
                   </div>
                </div>

                <!-- COMMAND BAR -->
                <div class="p-4 bg-black/40 border-8 border-double border-[#3c2a1a] shadow-xl relative mt-auto">
                   <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] opacity-10 pointer-events-none"></div>
                   
                   <div class="flex justify-between items-center mb-4 pb-3 border-b border-[#3c2a1a]/40 relative z-10">
                        <p class="font-fantasy text-base text-[#b8a38a]/70 uppercase tracking-[0.3em]">Invocaciones Arcanas</p>
                        <button
                            class="group relative px-8 py-2 transition-all disabled:opacity-20"
                            :disabled="isLoading || !canAdvanceRoom"
                            @click="advanceRoom"
                          >
                             <div class="absolute inset-0 bg-[#3c2a1a] border border-[#b8a38a]/10 group-hover:bg-[#4d3621]"></div>
                             <span class="relative z-10 font-fantasy text-[10px] uppercase text-[#b8a38a]">
                               {{ phase === 'staircase' ? 'Descender al Piso ' + (run.floor + 1) : 'Continuar' }}
                             </span>
                          </button>
                   </div>
                   
                   <div class="flex flex-wrap gap-3 relative z-10">
                      <button
                        v-for="skill in heroSkills"
                        class="group relative flex-1 min-w-[140px] flex flex-col items-center justify-center p-3 border-2 transition-all duration-500 transform"
                        :class="canUseSkill(skill) 
                          ? 'border-[#3c2a1a] bg-[#1a1a17] hover:border-[#b8a38a]/30 hover:bg-[#221a1a] cursor-pointer shadow-lg' 
                          : 'border-[#0a0a0a] bg-black opacity-30 cursor-not-allowed grayscale '"
                        @click="useSkill(skill.id)"
                      >
                          <div class="flex justify-between w-full mb-0.5">
                            <span class="font-fantasy text-[11px] text-center group-hover:text-white transition-colors tracking-widest">{{ skill.name }}</span>
                            <span v-if="skill.mpCost" class="text-[9px] text-blue-500">{{ skill.mpCost }} MP</span>
                          </div>
                          <p class="font-serif text-[8px] text-center italic text-[#b8a38a]/30 leading-tight group-hover:text-[#b8a38a]/60">{{ skill.description }}</p>
                          
                          <div v-if="skillCooldownRemaining(hero, skill.id) > 0" class="absolute inset-0 bg-black/70 flex items-center justify-center backdrop-blur-[1px]">
                             <span class="text-white font-fantasy text-xl">{{ skillCooldownRemaining(hero, skill.id) }}</span>
                          </div>

                          <!-- SKILL TOOLTIP -->
                          <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-4 w-64 bg-[#0a0a0a] border-2 border-[#3c2a1a] p-5 pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-300 z-[1000] shadow-[0_0_50px_rgba(0,0,0,0.9)] translate-y-4 group-hover:translate-y-0">
                            <div class="border-b border-[#3c2a1a]/40 pb-3 mb-3 text-center">
                              <p class="font-fantasy text-sm text-[#b8a38a] uppercase tracking-[0.2em]">{{ skill.name }}</p>
                              <div class="flex justify-center gap-4 mt-1 text-[9px] font-fantasy uppercase text-[#b8a38a]/40">
                                <span v-if="skill.power">Poder: {{ skill.power }}</span>
                                <span v-if="skill.mpCost">Coste: {{ skill.mpCost }} MP</span>
                                <span v-if="skill.cooldown">Carga: {{ skill.cooldown }}T</span>
                              </div>
                            </div>
                            
                            <p class="font-serif text-[10px] text-[#b8a38a]/70 italic leading-relaxed text-center mb-4">{{ skill.description }}</p>

                            <!-- Status Effect Info -->
                            <div v-if="skill.statusEffect" class="bg-black/40 border border-[#3c2a1a]/60 p-3 rounded-sm space-y-2">
                               <div class="flex items-center gap-2">
                                  <Icon :icon="getStatusIcon(skill.statusEffect.id)" class="text-sm" :class="getStatusColor(skill.statusEffect.id)" />
                                  <span class="font-fantasy text-[9px] uppercase tracking-wider" :class="getStatusColor(skill.statusEffect.id)">{{ getStatusTitle(skill.statusEffect.id) }}</span>
                               </div>
                               <div class="flex justify-between text-[8px] font-fantasy uppercase text-[#b8a38a]/40">
                                  <span>Probabilidad: {{ skill.statusEffect.chance }}%</span>
                                  <span>Duración: {{ skill.statusEffect.duration }}T</span>
                                </div>
                            </div>
                            
                            <!-- Arrow -->
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-[#3c2a1a]"></div>
                          </div>
                      </button>
                   </div>
                </div>
              </div>

              <!-- CHRONICLE ASIDE -->
              <aside v-if="hero && phase !== 'classSelect'" class="w-full lg:w-[320px] flex flex-col gap-6 relative z-[70]">
                <!-- INVENTORY -->
                <div class="p-4 bg-black/60 border-4 border-[#3c2a1a] shadow-inner flex flex-col gap-3 relative">
                  <p class="font-fantasy text-xs text-[#b8a38a]/80 border-b border-[#3c2a1a]/40 pb-1 uppercase tracking-widest">Alforja</p>
                  <div class="grid grid-cols-3 gap-2">
                    <div 
                      v-for="(item, idx) in 6" :key="idx"
                      class="aspect-square border-2 bg-black/40 flex items-center justify-center group relative overflow-visible transition-all duration-300"
                      :class="hero?.inventory[idx] ? getRarityColor(hero.inventory[idx].rarity) : 'border-[#3c2a1a] opacity-40'"
                      @click="hero?.inventory[idx] && useItem(idx)"
                    >
                      <template v-if="hero?.inventory[idx]">
                        <Icon :icon="getItemIcon(hero.inventory[idx])" class="text-[#b8a38a] text-xl group-hover:scale-110 transition-transform" />
                        
                        <!-- ADVANCED TOOLTIP (Left side) -->
                        <div class="absolute right-full top-0 mr-4 w-56 bg-[#0a0a0a] border-2 border-[#3c2a1a] p-3 pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-300 z-[1000] shadow-[0_0_20px_rgba(0,0,0,0.8)] -translate-x-2 group-hover:translate-x-0">
                          <div class="flex justify-between items-start mb-2 border-b border-[#3c2a1a]/40 pb-1">
                            <span class="text-[10px] text-white uppercase font-fantasy leading-none">{{ hero.inventory[idx].name }}</span>
                            <span 
                              class="text-[7px] uppercase font-fantasy px-1 border border-current leading-none"
                              :class="getRarityColor(hero.inventory[idx].rarity)"
                            >
                              {{ hero.inventory[idx].rarity }}
                            </span>
                          </div>
                          <p class="text-[9px] text-[#b8a38a]/70 font-serif italic leading-tight mb-2">{{ hero.inventory[idx].description }}</p>
                          <div class="text-[7px] text-[#8c2d1f] uppercase font-fantasy text-center pt-1 border-t border-[#3c2a1a]/20 animate-pulse">
                            Click para usar
                          </div>
                          
                          <!-- Tooltip arrow (pointing right) -->
                          <div class="absolute left-full top-4 -translate-y-1/2 border-8 border-transparent border-l-[#3c2a1a]"></div>
                        </div>
                      </template>
                      <span v-else class="text-[8px] text-[#3c2a1a] font-fantasy">Vacío</span>
                    </div>
                  </div>
                </div>

                <div class="flex-1 p-6 bg-black/60 border-8 border-double border-[#3c2a1a] shadow-inner flex flex-col min-h-0 relative">
                   <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/old-wall.png')] opacity-10 pointer-events-none"></div>
                   <p class="font-fantasy text-xl text-[#b8a38a]/80 mb-4 pb-2 border-b border-[#3c2a1a]/40 relative z-10 uppercase tracking-widest">Cronicón</p>
                   <div class="flex-1 overflow-auto space-y-4 font-serif text-[11px] custom-scroll pr-3 italic leading-relaxed relative z-10">
                      <TransitionGroup name="log-grim">
                        <div v-for="(line, idx) in log" :key="idx" class="flex gap-3 items-start py-2 border-b border-[#3c2a1a]/10 transition-all" :class="idx === 0 ? 'text-[#b8a38a] font-bold opacity-100 scale-105 origin-left' : 'text-[#b8a38a]/40'">
                           <span class="text-[#8c2d1f] mt-1 shrink-0 text-[10px]">✦</span>
                           <span class="tracking-tight uppercase">{{ line }}</span>
                        </div>
                      </TransitionGroup>
                   </div>
                </div>

                <!-- Footer Loot / Stats -->
                <div 
                  class="p-4 bg-black border-y-4 border-[#3c2a1a] shadow-xl flex items-center justify-between transition-all duration-500 relative overflow-hidden"
                  :class="{ 'border-amber-600 shadow-[0_0_30px_rgba(245,158,11,0.2)]': goldFlash }"
                >
                  <span class="relative z-10 font-fantasy text-[#b8a38a]/30 text-[10px] uppercase tracking-[0.4em]">Botín Arcano</span>
                  <div class="relative z-10 flex items-center gap-3">
                    <Icon icon="game-icons:coins" class="text-[#b8a38a] text-2xl" :class="{ 'animate-bounce text-amber-500': goldFlash }" />
                    <span class="font-fantasy text-[#b8a38a] text-2xl tracking-tighter">{{ run.gold }}</span>
                  </div>
                </div>
              </aside>

            <!-- PATH SELECTION OVERLAY -->
            <div v-if="phase === 'pathSelect'" class="absolute inset-0 z-[150] bg-black/80 backdrop-blur-sm flex items-center justify-center p-8">
              <div class="max-w-4xl w-full text-center space-y-12">
                <div class="space-y-2">
                  <h3 class="text-4xl font-fantasy text-[#b8a38a] uppercase tracking-[0.4em]">Elige tu Senda</h3>
                  <p class="text-[#b8a38a]/40 font-serif italic">"Dos caminos se abren ante ti, pero solo uno lleva a la gloria."</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                  <button 
                    v-for="(choice, idx) in pathChoices" 
                    :key="idx"
                    @click="selectPath(choice.type)"
                    class="group relative h-80 flex flex-col items-center justify-center gap-6 transition-all transform hover:scale-105"
                  >
                    <!-- Door Visual -->
                    <div class="absolute inset-0 bg-[#1a1714] border-4 border-[#3c2a1a] group-hover:border-[#b8a38a]/40 shadow-2xl transition-all"></div>
                    <div class="absolute inset-2 border border-[#3c2a1a]/40"></div>
                    
                    <!-- Icon -->
                    <div class="relative z-10 size-24 bg-black/40 border-2 border-[#3c2a1a] flex items-center justify-center text-[#b8a38a] group-hover:text-white transition-colors">
                      <Icon :icon="choice.icon" class="text-5xl" />
                    </div>
                    
                    <div class="relative z-10 space-y-2">
                      <p class="font-fantasy text-2xl text-[#b8a38a] uppercase tracking-widest group-hover:text-white">{{ choice.title }}</p>
                      <p class="text-xs font-serif italic text-[#b8a38a]/40 max-w-[200px] mx-auto">{{ choice.description }}</p>
                    </div>

                    <!-- Hover effect glow -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#8c2d1f]/0 to-[#8c2d1f]/0 group-hover:from-[#8c2d1f]/10 transition-all"></div>
                  </button>
                </div>
              </div>
            </div>

            <!-- DEFEAT OVERLAY -->
            <div v-if="phase === 'defeat'" class="absolute inset-0 z-[1000] bg-black/95 backdrop-blur-xl flex items-center justify-center p-8">
              <div class="max-w-2xl w-full text-center space-y-10 relative">
                <!-- Visual Decoration -->
                <div class="absolute inset-0 bg-[#8c2d1f]/5 blur-[120px] rounded-full animate-pulse-slow"></div>
                
                <div class="relative z-10 space-y-6">
                  <div class="size-24 bg-red-950/20 mx-auto rounded-full flex items-center justify-center text-5xl text-red-700 border-4 border-red-900 shadow-[0_0_50px_rgba(140,45,31,0.5)]">
                    <Icon icon="game-icons:death-skull" />
                  </div>
                  <h3 class="text-5xl font-fantasy text-red-700 uppercase tracking-[0.5em] leading-tight">La Oscuridad te ha Reclamado</h3>
<p class="text-[#b8a38a]/40 font-serif italic text-lg leading-relaxed max-w-lg mx-auto">
                    "Tus huesos se unirán a los miles que ya adornan estos pasillos. El Abismo no olvida, pero tampoco perdona."
                  </p>
                </div>

                <div class="flex flex-col gap-4 relative z-10 font-fantasy">
                  <button 
                    @click="startNewGesta" 
                    class="w-full py-4 bg-[#8c2d1f] text-white text-xl uppercase tracking-[0.3em] hover:bg-red-800 transition-all border-2 border-[#5c1a11] shadow-[0_10px_40px_rgba(140,45,31,0.3)]"
                  >
                    Nueva Gesta
                  </button>
                  <button 
                    @click="exitGame" 
                    class="w-full py-4 bg-[#1a1a1a] text-[#b8a38a]/60 text-lg uppercase tracking-[0.3em] hover:text-[#b8a38a] hover:bg-[#222] transition-all border-2 border-[#3c2a1a]"
                  >
                    Rendirse al Vacío
                  </button>
                </div>
              </div>
            </div>

            <!-- SHOP OVERLAY -->
            <div v-if="phase === 'shop'" class="absolute inset-0 z-[150] bg-black/90 backdrop-blur-md flex items-center justify-center p-8">
              <div class="max-w-4xl w-full bg-[#1a1714] border-[10px] border-double border-[#3c2a1a] p-8 md:p-12 text-center space-y-8 relative">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] pointer-events-none"></div>
                <div class="flex items-center justify-center gap-4 mb-4">
                   <Icon icon="game-icons:shop" class="text-4xl text-amber-600" />
                   <h3 class="text-4xl font-fantasy text-[#b8a38a] uppercase tracking-[0.3em]">Mercader Errante</h3>
                </div>
                <p class="text-[#b8a38a]/60 font-serif italic text-lg leading-relaxed">"Mis mercancías son raras, mis precios... justos para quien valora su vida."</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6">
                  <div 
                    v-for="(item, idx) in shopItems" 
                    :key="idx"
                    class="group relative p-6 bg-[#0a0a0a] border-4 transition-all flex flex-col items-center gap-4"
                    :class="[
                      getRarityColor(item.rarity),
                      run.gold < item.price ? 'opacity-40 grayscale' : 'hover:scale-[1.02] cursor-pointer'
                    ]"
                  >
                    <Icon :icon="getItemIcon(item)" class="text-4xl text-[#b8a38a] group-hover:scale-110 transition-transform" />
                    <div>
                      <p class="font-fantasy text-sm text-white uppercase">{{ item.name }}</p>
                      <p class="text-[10px] font-serif italic text-[#b8a38a]/40 line-clamp-2 h-8">{{ item.description }}</p>
                    </div>
                    <div class="mt-auto pt-4 border-t border-[#3c2a1a] w-full flex flex-col gap-3">
                      <div class="flex items-center justify-center gap-2 text-amber-500 font-fantasy text-lg">
                        <Icon icon="game-icons:coins" />
                        <span>{{ item.price }}</span>
                      </div>
                      <button 
                        @click="buyItem(idx)"
                        :disabled="run.gold < item.price"
                        class="py-2 bg-amber-900/20 border border-amber-900/40 text-amber-500 font-fantasy text-[10px] uppercase tracking-widest hover:bg-amber-600 hover:text-black transition-all disabled:opacity-20"
                      >
                        Adquirir
                      </button>
                    </div>
                  </div>
                </div>
                
                <div class="pt-10">
                  <button 
                    @click="phase = 'victory'"
                    class="px-12 py-3 bg-[#3c2a1a] text-[#b8a38a] font-fantasy uppercase tracking-[0.3em] hover:bg-[#4d3621] border-2 border-transparent hover:border-[#b8a38a]/20"
                  >
                    Continuar Viaje
                  </button>
                </div>
              </div>
            </div>
          </main>
            <!-- BOSS VICTORY MODAL -->
            <Transition name="modal-grim">
              <div v-if="showBossVictory" class="fixed inset-0 z-[100060] bg-black/95 backdrop-blur-xl flex items-center justify-center p-8">
                <div class="max-w-2xl w-full bg-[#1a1714] border-[12px] border-double border-amber-900/40 p-12 text-center space-y-10 relative">
                  <!-- Visual Decoration -->
                  <div class="absolute inset-0 bg-amber-600/5 blur-[120px] rounded-full animate-pulse-slow"></div>
                  <div class="absolute -top-12 left-1/2 -translate-x-1/2 size-24 bg-[#0a0a0a] border-4 border-amber-600 rounded-full flex items-center justify-center text-5xl text-amber-500 shadow-[0_0_50px_rgba(245,158,11,0.3)]">
                    <Icon icon="game-icons:trophy" />
                  </div>

                  <div class="space-y-4 pt-6">
                    <h3 class="text-4xl font-fantasy text-amber-500 uppercase tracking-[0.3em]">¡GRAN VICTORIA!</h3>
                    <p class="text-[#b8a38a]/80 font-serif italic text-lg leading-relaxed">
                      "El Guardián del Abismo ha caído bajo tu acero. Las sombras retroceden ante tu inquebrantable voluntad."
                    </p>
                  </div>

                  <div class="grid grid-cols-2 gap-6 bg-black/40 border border-amber-900/20 p-6 rounded-sm">
                    <div class="space-y-1">
                      <p class="text-[10px] text-amber-500/40 uppercase font-fantasy">Oro Recuperado</p>
                      <p class="text-2xl text-amber-500 font-fantasy">+{{ bossRewards.gold }}</p>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] text-blue-500/40 uppercase font-fantasy">Esencia de XP</p>
                      <p class="text-2xl text-blue-500 font-fantasy">+{{ bossRewards.xp }}</p>
                    </div>
                    <div v-if="bossRewards.item" class="col-span-2 pt-4 border-t border-amber-900/20">
                      <p class="text-[10px] uppercase font-fantasy mb-3" :class="getRarityColor(bossRewards.item.rarity)">{{ bossRewards.item.rarity === 'legendary' ? 'Reliquia Divina Hallada' : 'Botín Épico Obtenido' }}</p>
                      <div class="flex items-center justify-center gap-4 p-4 border-2 bg-black/60 shadow-2xl" :class="getRarityColor(bossRewards.item.rarity)">
                        <Icon :icon="getItemIcon(bossRewards.item)" class="text-3xl" />
                        <span class="font-fantasy text-xl uppercase tracking-widest">{{ bossRewards.item.name }}</span>
                      </div>
                    </div>
                  </div>

                  <button 
                    @click="showBossVictory = false" 
                    class="w-full py-4 bg-amber-900/20 border-2 border-amber-600 text-amber-500 text-xl uppercase tracking-[0.3em] hover:bg-amber-600 hover:text-black cursor-pointer transition-all shadow-[0_0_30px_rgba(245,158,11,0.1)] relative z-[100]"
                  >
                    Reclamar Destino
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- EXIT CONFIRMATION OVERLAY -->
          <Transition name="fade-grim">
            <div v-if="showExitConfirm" class="absolute inset-0 z-[100020] flex items-center justify-center bg-black/98 backdrop-blur-xl p-6">
              <div class="max-w-md w-full p-10 bg-[#0a0a0a] border-[10px] border-double border-[#8c2d1f] shadow-[0_0_120px_rgba(140,45,31,0.5)] text-center space-y-8 relative">
                <div class="size-14 bg-red-950/20 mx-auto rounded-full flex items-center justify-center text-3xl text-red-600 border-2 border-red-900 mb-2 animate-pulse">!</div>
                <h3 class="font-fantasy text-2xl text-[#b8a38a] uppercase tracking-widest">¿Rendirse al Vacío?</h3>
                <p class="text-[#b8a38a]/40 font-serif italic text-sm leading-relaxed">
                  Toda esperanza y el botín recolectado se perderán en la penumbra. ¿Deseas realmente claudicar?
                </p>
                <div class="flex gap-4 pt-4 font-fantasy">
                  <button 
                    @click="showExitConfirm = false" 
                    class="flex-1 py-3 bg-[#1a1a1a] text-[#b8a38a] text-lg uppercase tracking-widest hover:bg-[#222] border-2 border-[#3c2a1a] transition-all"
                  >
                    Resistir
                  </button>
                  <button 
                    @click="exitGame" 
                    class="flex-1 py-3 bg-[#8c2d1f] text-white text-lg uppercase tracking-widest hover:bg-red-800 border-2 border-[#5c1a11] transition-all shadow-xl"
                  >
                    Rendirse
                  </button>
                </div>
              </div>
            </div>
          </Transition>

        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useInventoryStore } from '../../stores/inventory'
import gameEngine from '../../lib/gameEngineService'
import classesData from '../../assets/json/classes.json'
import enemiesData from '../../assets/json/enemies.json'
import itemsData from '../../assets/json/items.json'
import playerSkillsData from '../../assets/json/skills_player.json'
import enemySkillsData from '../../assets/json/skills_enemy.json'
import eventsData from '../../assets/json/events.json'
import bossesData from '../../assets/json/bosses.json'

const GAME_SLUG = 'descenso-al-abismo'
const isLoading = ref(false); const error = ref(null); const sessionId = ref(null); const log = ref([]); 
const showModal = ref(false); const showExitConfirm = ref(false);
const goldFlash = ref(false); const hitHero = ref(false); const hitEnemy = ref(false);
const hasSave = ref(false);
const saveSummary = ref(null);
const playStartTime = ref(null);
const totalTimePlayed = ref(0);

const classes = classesData.classes
const allEnemies = enemiesData.enemies
const allItems = itemsData.items
const playerSkills = playerSkillsData.skills
const enemySkills = enemySkillsData.skills
const events = eventsData
const allBosses = bossesData.bosses

function pushLog(m) { log.value.unshift(m.toUpperCase()); if (log.value.length > 50) log.value.length = 50 }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min }
function clone(obj) { return JSON.parse(JSON.stringify(obj)) }

// PERSISTENCE & META-PROGRESSION
const inventoryStore = useInventoryStore()
const totalGold = ref(parseInt(localStorage.getItem('rpg_total_gold') || '0'))

const unlockedClasses = computed(() => {
  const defaults = ["warrior", "paladin", "rogue", "mage", "hunter", "cleric"]
  const purchased = []
  if (inventoryStore.hasItem('rpg_class_necromancer')) purchased.push('necromancer')
  if (inventoryStore.hasItem('rpg_class_berserker')) purchased.push('berserker')
  if (inventoryStore.hasItem('rpg_class_archmage')) purchased.push('archmage')
  if (inventoryStore.hasItem('rpg_class_assassin')) purchased.push('assassin')
  return [...defaults, ...purchased]
})

function saveMeta() {
  localStorage.setItem('rpg_total_gold', totalGold.value.toString())
}

function makeHero(classId = 'warrior') {
  const c = classes.find(cl => cl.id === classId) || classes[0]
  const skills = playerSkills.filter(s => c.skills.includes(s.id))
  return {
    classId: c.id, className: c.name, level: 1, 
    exp: 0, nextLevelExp: 100,
    hp: c.stats.hp, maxHp: c.stats.hp, 
    mp: c.stats.mp, maxMp: c.stats.mp,
    manaRegen: c.stats.manaRegen || 0,
    attack: c.stats.attack, defense: c.stats.defense,
    magicAttack: c.stats.magicAttack, magicDefense: c.stats.magicDefense,
    speed: c.stats.speed, critRate: c.stats.critRate, evasion: c.stats.evasion,
    guard: false, skillCooldowns: {}, skills: skills.map(s => s.id),
    inventory: [],
    statuses: []
  }
}

function gainExp(amount) {
  if (!hero.value) return
  hero.value.exp += amount
  pushLog(`Ganaste ${amount} XP.`)
  if (hero.value.exp >= hero.value.nextLevelExp) {
    levelUp()
  }
}

function levelUp() {
  const h = hero.value
  const c = classes.find(cl => cl.id === h.classId)
  h.level++
  h.exp -= h.nextLevelExp
  h.nextLevelExp = Math.floor(h.nextLevelExp * 1.3)
  
  // Stats increase
  const lu = c.levelUp
  h.maxHp += lu.hp; h.hp = h.maxHp
  h.maxMp += lu.mp; h.mp = h.maxMp
  h.attack += lu.attack
  h.defense += lu.defense
  h.magicAttack += lu.magicAttack
  h.magicDefense += lu.magicDefense
  h.speed += lu.speed
  
  pushLog(`¡NIVEL AUMENTADO! Ahora eres nivel ${h.level}.`)
}

function makeEnemy(floor = 1, isBoss = false) {
  const tier = floor >= 20 ? 'high' : (floor >= 10 ? 'mid' : 'low')
  const lowTier = ['rat', 'goblin', 'kobold', 'skeleton', 'zombie']
  const midTier = ['wolf', 'panther', 'ghoul', 'dullahan', 'orc', 'bear', 'centaur', 'gnoll']
  const highTier = ['dragon', 'lich', 'demon', 'minotaur', 'giant', 'vampire', 'naga', 'hydra']
  
  let pool = isBoss ? allBosses : allEnemies.filter(e => {
    if (tier === 'low') return lowTier.includes(e.id)
    if (tier === 'mid') return midTier.includes(e.id)
    return highTier.includes(e.id)
  })
  
  if (pool.length === 0) pool = isBoss ? [allBosses[0]] : [allEnemies[0]]
  const base = pool[randInt(0, pool.length - 1)]
  const level = hero.value?.level || 1
  
  const scale = (val, up, lv) => {
    // Pure scaling based on levelUp from JSON
    return Math.floor((val + (up * (lv - 1))) * (isBoss ? 1.6 : 1.0))
  }
  
  return { 
    id: base.id, name: base.name + (isBoss ? " (JEFE)" : ""), level, 
    hp: scale(base.stats.hp, base.levelUp.hp, level), 
    maxHp: scale(base.stats.hp, base.levelUp.hp, level), 
    mp: scale(base.stats.mp, base.levelUp.mp, level), 
    maxMp: scale(base.stats.mp, base.levelUp.mp, level),
    attack: scale(base.stats.attack, base.levelUp.attack, level), 
    defense: scale(base.stats.defense, base.levelUp.defense, level),
    magicAttack: scale(base.stats.magicAttack, base.levelUp.magicAttack, level),
    magicDefense: scale(base.stats.magicDefense, base.levelUp.magicDefense, level),
    critRate: base.stats.critRate || 5,
    evasion: base.stats.evasion || 5,
    skills: base.skills,
    skillCooldowns: {},
    isBoss,
    statuses: []
  }
}

const run = ref({ floor: 1, roomInFloor: 1, currentRoom: null, hero: null, enemy: null, gold: 0 })
const phase = ref('idle'); const turn = ref('hero');
const hero = computed(() => run.value.hero); const enemy = computed(() => run.value.enemy)
const heroSkills = computed(() => playerSkills.filter(s => hero.value?.skills?.includes(s.id)))

const currentEvent = ref(null)
const shopItems = ref([])
const pathChoices = ref([])
const showBossVictory = ref(false)
const bossRewards = ref({ gold: 0, xp: 0, item: null })

function selectClass(cid) {
  run.value.hero = makeHero(cid)
  phase.value = 'room'
  pushLog(`Elegida clase: ${hero.value.className}.`)
  resetRunLocal()
}

function startNewGesta() {
  run.value.hero = null
  resetRunLocal()
}

function resetRunLocal() { 
  error.value = null; log.value = []; 
  if (!run.value.hero) { phase.value = 'classSelect'; return }
  run.value.floor = 1; run.value.roomInFloor = 1; run.value.gold = 0;
  phase.value = 'room'; turn.value = 'hero'; 
  pushLog('Entrando en el Abismo Infinito...'); 
  enterRoom() 
}

function enterRoom(forcedType = null) {
  const r = run.value
  if (!hero.value) return; hero.value.guard = false
  
  if (r.roomInFloor === 3) {
    if (r.floor % 10 === 0) {
      r.enemy = makeEnemy(r.floor, true)
      phase.value = 'combat'
      pushLog(`¡UN GUARDIÁN DEL ABISMO! Es ${enemy.value.name}.`)
    } else {
      phase.value = 'staircase'
      pushLog(`Has encontrado las escaleras hacia el piso ${r.floor + 1}.`)
    }
    return
  }

  // Determine type
  let type = forcedType
  if (!type) {
    const rnd = Math.random()
    if (rnd < 0.6) type = 'combat'
    else if (rnd < 0.75) type = 'camp'
    else if (rnd < 0.85) type = 'treasure'
    else type = 'event'
  }

  // Execute type
  if (type === 'combat') {
    r.enemy = makeEnemy(r.floor)
    phase.value = 'combat'
    pushLog(`Piso ${r.floor}: Aparece un ${enemy.value.name}.`)
  } else if (type === 'camp') {
    phase.value = 'camp'
    const h = randInt(20, 40); hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + h)
    pushLog(`Descanso: Recuperas ${h} HP.`)
  } else if (type === 'treasure') {
    phase.value = 'treasure'
    pushLog(`¡Has encontrado un cofre olvidado!`)
    const gold = randInt(20, 50); r.gold += gold; triggerGoldFlash()
    if (Math.random() > 0.5) addItem(allItems[randInt(0, allItems.length - 1)].id)
  } else if (type === 'event') {
    phase.value = 'event'
    currentEvent.value = events[randInt(0, events.length - 1)]
    pushLog(`Un suceso extraño... ${currentEvent.value.title}.`)
  } else if (type === 'shop') {
    phase.value = 'shop'
    shopItems.value = []
    for (let i = 0; i < 3; i++) {
      shopItems.value.push(clone(allItems[randInt(0, allItems.length - 1)]))
    }
    pushLog(`Has encontrado a un Mercader Errante.`)
  }
}

function generatePathChoices() {
  const choices = []
  const types = ['combat', 'camp', 'treasure', 'event']
  
  // If it's a shop floor, ensure one choice is a shop
  const isShopFloor = run.value.floor % 10 === 5 && run.value.roomInFloor === 1
  
  for (let i = 0; i < 2; i++) {
    let type = types[randInt(0, types.length - 1)]
    if (isShopFloor && i === 1) type = 'shop'
    
    choices.push({
      type,
      title: getPathTitle(type),
      icon: getPathIcon(type),
      description: getPathDesc(type)
    })
  }
  return choices
}

function getPathTitle(t) {
  return { combat: 'Conflicto', camp: 'Refugio', treasure: 'Botín', event: 'Misterio', shop: 'Mercader' }[t]
}
function getPathIcon(t) {
  return { combat: 'game-icons:crossed-swords', camp: 'game-icons:campfire', treasure: 'game-icons:open-treasure-chest', event: 'game-icons:star-swirl', shop: 'game-icons:shop' }[t]
}
function getPathDesc(t) {
  return { 
    combat: 'Un enemigo acecha en las sombras.', 
    camp: 'Un lugar seguro para restañar heridas.', 
    treasure: 'Brillos metálicos entre los escombros.', 
    event: 'Una presencia extraña te observa.',
    shop: 'Intercambia tu oro por reliquias.'
  }[t]
}

function selectPath(type) {
  run.value.roomInFloor++
  phase.value = 'room'
  enterRoom(type)
}

function applyStatus(target, statusId, duration) {
  if (!target || !target.statuses) return
  const existing = target.statuses.find(s => s.id === statusId)
  if (existing) {
    existing.duration = Math.max(existing.duration, duration)
  } else {
    target.statuses.push({ id: statusId, duration })
  }
  pushLog(`Efecto aplicado: ${getStatusTitle(statusId)}`)
}

function processStatuses(unit) {
  if (!unit || !unit.statuses) return
  let skipTurn = false

  for (let i = unit.statuses.length - 1; i >= 0; i--) {
    const s = unit.statuses[i]
    
    // Apply damage effects
    if (s.id === 'bleed') {
      const dmg = Math.floor(unit.maxHp * 0.05)
      unit.hp = Math.max(0, unit.hp - dmg)
      pushLog(`${unit.name} sangra por ${dmg}.`)
    } else if (s.id === 'poison') {
      const dmg = 10
      unit.hp = Math.max(0, unit.hp - dmg)
      pushLog(`${unit.name} sufre por veneno: ${dmg}.`)
    } else if (s.id === 'burn') {
      const dmg = 15
      unit.hp = Math.max(0, unit.hp - dmg)
      pushLog(`${unit.name} sufre por quemaduras: ${dmg}.`)
    } else if (s.id === 'stun') {
      skipTurn = true
      pushLog(`${unit.name} está aturdido y pierde el turno.`)
    } else if (s.id === 'regeneration') {
      const heal = Math.floor(unit.maxHp * 0.05)
      unit.hp = Math.min(unit.maxHp, unit.hp + heal)
      pushLog(`${unit.name} se regenera: +${heal} HP.`)
    }

    s.duration--
    if (s.duration <= 0) {
      unit.statuses.splice(i, 1)
      pushLog(`${unit.name} se ha librado de ${getStatusTitle(s.id)}.`)
    }
  }

  return skipTurn
}

function getStatusTitle(id) {
  return { 
    bleed: 'Sangrado', poison: 'Veneno', stun: 'Aturdimiento', weakness: 'Debilidad', 
    vulnerability: 'Vulnerabilidad', burn: 'Quemadura',
    regeneration: 'Regeneración', might: 'Poderío', shield: 'Escudo', haste: 'Celeridad', ward: 'Protección Arcana'
  }[id]
}
function getStatusIcon(id) {
  return { 
    bleed: 'game-icons:bleeding-wound', poison: 'game-icons:poison-gas', stun: 'game-icons:lightning-arc', 
    weakness: 'game-icons:kneeling', vulnerability: 'game-icons:broken-shield', burn: 'game-icons:flame',
    regeneration: 'game-icons:heart-beats', might: 'game-icons:crossed-swords', shield: 'game-icons:shield', 
    haste: 'game-icons:whirlwind', ward: 'game-icons:magic-swirl'
  }[id]
}
function getStatusColor(id) {
  return { 
    bleed: 'text-red-500', poison: 'text-green-500', stun: 'text-amber-400', weakness: 'text-slate-500', 
    vulnerability: 'text-purple-500', burn: 'text-orange-500',
    regeneration: 'text-pink-500', might: 'text-red-400', shield: 'text-blue-400', 
    haste: 'text-cyan-400', ward: 'text-indigo-400'
  }[id]
}

function canUseSkill(s) { 
  return phase.value === 'combat' && turn.value === 'hero' && 
         hero.value.hp > 0 && enemy.value.hp > 0 && 
         hero.value.mp >= (s.mpCost || 0) &&
         (hero.value.skillCooldowns[s.id] || 0) <= 0
}

function skillCooldownRemaining(unit, sid) { return unit?.skillCooldowns?.[sid] || 0 }

function getItemIcon(item) {
  if (item.type === 'potion') return 'game-icons:health-potion'
  if (item.type === 'weapon') return 'game-icons:broadsword'
  if (item.type === 'armor') return 'game-icons:breastplate'
  if (item.type === 'scroll') return 'game-icons:scroll-unfurled'
  return 'game-icons:crystal-cluster'
}

function getRarityColor(rarity) {
  switch (rarity) {
    case 'common': return 'text-slate-400 border-slate-500/30'
    case 'uncommon': return 'text-green-500 border-green-600/40 shadow-[inset_0_0_10px_rgba(34,197,94,0.1)]'
    case 'rare': return 'text-blue-500 border-blue-600/50 shadow-[inset_0_0_15px_rgba(59,130,246,0.15)]'
    case 'epic': return 'text-purple-500 border-purple-600/60 shadow-[inset_0_0_20px_rgba(168,85,247,0.2)]'
    case 'legendary': return 'text-amber-500 border-amber-500/80 shadow-[0_0_15px_rgba(245,158,11,0.2),inset_0_0_25px_rgba(245,158,11,0.3)] animate-pulse-rarity'
    default: return 'text-slate-400 border-[#3c2a1a]'
  }
}

function addItem(id) {
  if (hero.value.inventory.length >= 6) { pushLog('Inventario lleno.'); return }
  const item = allItems.find(it => it.id === id)
  if (item) { hero.value.inventory.push(clone(item)); pushLog(`Obtenido: ${item.name}.`) }
}

function useItem(idx) {
  const item = hero.value.inventory[idx]
  if (!item) return
  pushLog(`Usando ${item.name}...`)
  if (item.type === 'potion') {
    if (item.effect.hp) hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + item.effect.hp)
    if (item.effect.mp) hero.value.mp = Math.min(hero.value.maxMp, hero.value.mp + item.effect.mp)
  } else if (['weapon', 'armor', 'relic'].includes(item.type)) {
    // Apply any stat bonuses present in the effect object
    if (item.effect.attack) hero.value.attack += item.effect.attack
    if (item.effect.defense) hero.value.defense += item.effect.defense
    if (item.effect.magicAttack) hero.value.magicAttack += item.effect.magicAttack
    if (item.effect.magicDefense) hero.value.magicDefense += item.effect.magicDefense
    if (item.effect.speed) hero.value.speed += item.effect.speed
    if (item.effect.critRate) hero.value.critRate += item.effect.critRate
    if (item.effect.evasion) hero.value.evasion += item.effect.evasion
    if (item.effect.maxHp) { 
      hero.value.maxHp += item.effect.maxHp
      hero.value.hp += item.effect.maxHp 
    }
    if (item.effect.maxMp) { 
      hero.value.maxMp += item.effect.maxMp
      hero.value.mp += item.effect.maxMp 
    }
  } else if (item.type === 'scroll') {
    if (!hero.value.skills.includes(item.effect.skill)) hero.value.skills.push(item.effect.skill)
  }
  hero.value.inventory.splice(idx, 1)
}

function buyItem(idx) {
  const item = shopItems.value[idx]
  if (!item) return
  if (run.value.gold < item.price) { pushLog('No tienes suficiente oro.'); return }
  if (hero.value.inventory.length >= 6) { pushLog('Inventario lleno.'); return }
  
  run.value.gold -= item.price
  hero.value.inventory.push(clone(item))
  shopItems.value.splice(idx, 1)
  pushLog(`Comprado: ${item.name}.`)
}

function exitGame() { showModal.value = false; phase.value = 'idle'; showExitConfirm.value = false; }

function endCombatIfNeeded() {
  if (enemy.value.hp <= 0) { 
    phase.value = 'victory'; 
    const gold = randInt(15, 30) * (enemy.value.isBoss ? 5 : 1); 
    run.value.gold += gold; 
    totalGold.value += gold;
    saveMeta();
    triggerGoldFlash();
    
    const xp = (20 + (run.value.floor * 5)) * (enemy.value.isBoss ? 4 : 1)
    gainExp(xp)
    
    pushLog(`¡Victoria! Has obtenido ${gold} oro.`); 
    
    if (enemy.value.isBoss) {
      const relics = allItems.filter(i => i.type === 'relic')
      const relic = relics.length > 0 ? relics[randInt(0, relics.length - 1)] : null
      if (relic) addItem(relic.id)
      
      bossRewards.value = { gold, xp, item: relic }
      showBossVictory.value = true
    } else if (Math.random() > 0.7) {
      addItem(allItems[randInt(0, allItems.length - 1)].id)
    }
    return true 
  }
  if (hero.value.hp <= 0) { 
    phase.value = 'defeat'; 
    pushLog('Has sucumbido ante la oscuridad...'); 
    resetSave();
    return true 
  }; 
  return false
}

function triggerGoldFlash() { goldFlash.value = true; setTimeout(() => { goldFlash.value = false }, 1000) }
function triggerHitHero() { hitHero.value = true; setTimeout(() => { hitHero.value = false }, 500) }
function triggerHitEnemy() { hitEnemy.value = true; setTimeout(() => { hitEnemy.value = false }, 500) }

function enemyTurn() {
  if (phase.value !== 'combat' || endCombatIfNeeded()) return
  
  // Process statuses at start of turn
  const isStunned = processStatuses(enemy.value)
  if (isStunned) {
    if (endCombatIfNeeded()) return
    turn.value = 'hero'
    return
  }
  
  // Choose a skill or basic attack
  const skillId = enemy.value.skills[randInt(0, enemy.value.skills.length - 1)]
  const skill = enemySkills.find(s => s.id === skillId)
  
  const isMagic = skill && skill.type === 'magic'
  const attackerStat = isMagic ? enemy.value.magicAttack : enemy.value.attack
  const defenderStat = isMagic ? hero.value.magicDefense : hero.value.defense
  
  // Base damage calculation
  let dmg = randInt(attackerStat - 1, attackerStat + 1)
  let logMsg = `El ${enemy.value.name} lanza un ataque rápido: `
  
  // WEAKNESS / MIGHT CHECK
  if (enemy.value.statuses.some(st => st.id === 'weakness')) {
    dmg *= 0.75
  }
  if (enemy.value.statuses.some(st => st.id === 'might')) {
    dmg *= 1.25
  }

  if (skill) {
    const powerBonus = (skill.power || 0) * 1.5
    dmg = Math.floor(dmg + powerBonus)
    logMsg = `${enemy.value.name} usa ${skill.name}: `
  }

  const reduction = defenderStat + (hero.value.guard ? 10 : 0)
  let tk = Math.max(Math.floor(dmg - reduction), 1)
  
  // VULNERABILITY / SHIELD / WARD CHECK
  if (hero.value.statuses.some(st => st.id === 'vulnerability')) {
    tk = Math.floor(tk * 1.25)
  }
  if (hero.value.statuses.some(st => st.id === 'shield')) {
    tk = Math.floor(tk * 0.5)
  }
  if (skill && skill.damageType === 'magical' && hero.value.statuses.some(st => st.id === 'ward')) {
    tk = Math.floor(tk * 0.6)
  }

  // EVASION CHECK (Speed/Agility increases evasion)
  let heroEvasion = (hero.value.evasion || 0) + Math.floor(hero.value.speed / 2)
  if (hero.value.statuses.some(st => st.id === 'haste')) {
    heroEvasion += 15
  }

  if (Math.random() * 100 < heroEvasion) {
    pushLog(`¡EVADIDO! El ataque del ${enemy.value.name} no te alcanza.`);
    tk = 0;
  } else {
    // CRITICAL CHECK
    if (Math.random() * 100 < (enemy.value.critRate || 0)) {
      tk = Math.floor(tk * 1.5);
      logMsg = `¡GOLPE CRÍTICO! ${logMsg}`;
    }
    hero.value.hp = Math.max(0, hero.value.hp - tk)
    triggerHitHero();
    pushLog(`${logMsg}-${tk} Vida.`); 

    // APPLY STATUS EFFECT
    if (skill && skill.statusEffect && Math.random() * 100 < skill.statusEffect.chance) {
      const target = (skill.type === 'buff' || skill.type === 'heal' || skill.effect?.includes('buff') || skill.effect?.includes('heal')) ? enemy.value : hero.value
      applyStatus(target, skill.statusEffect.id, skill.statusEffect.duration)
    }
  }
  hero.value.guard = false; 
  if (!endCombatIfNeeded()) { 
    // MANA REGEN
    const regen = hero.value.manaRegen || 0
    hero.value.mp = Math.min(hero.value.maxMp, hero.value.mp + regen)
    if (regen > 0) pushLog(`Recuperas ${regen} de Maná.`)
    
    // COOLDOWN DECREMENT
    for (const k in hero.value.skillCooldowns) {
      if (hero.value.skillCooldowns[k] > 0) hero.value.skillCooldowns[k]--
    }
    
    turn.value = 'hero' 
  }
}

function useSkill(sid) {
  const s = playerSkills.find(sk => sk.id === sid); if (!sid || !canUseSkill(s)) return; error.value = null
  
  hero.value.mp -= (s.mpCost || 0)
  if (s.cooldown > 0) hero.value.skillCooldowns[sid] = s.cooldown
  
  if (s.type === 'damage') {
    const baseAtk = s.damageType === 'magical' ? hero.value.magicAttack : hero.value.attack
    let baseDmg = (randInt(baseAtk - 1, baseAtk + 1) * 2) * (s.power / 100)
    
    // WEAKNESS / MIGHT CHECK
    if (hero.value.statuses.some(st => st.id === 'weakness')) {
      baseDmg *= 0.75
    }
    if (hero.value.statuses.some(st => st.id === 'might')) {
      baseDmg *= 1.25
    }

    const def = s.damageType === 'magical' ? enemy.value.magicDefense : enemy.value.defense
    let finalDmg = Math.max(Math.floor(baseDmg - (def * 0.5)), 1)
    
    // VULNERABILITY / SHIELD / WARD CHECK
    if (enemy.value.statuses.some(st => st.id === 'vulnerability')) {
      finalDmg = Math.floor(finalDmg * 1.25)
    }
    if (enemy.value.statuses.some(st => st.id === 'shield')) {
      finalDmg = Math.floor(finalDmg * 0.5)
    }
    if (s.damageType === 'magical' && enemy.value.statuses.some(st => st.id === 'ward')) {
      finalDmg = Math.floor(finalDmg * 0.6)
    }

    // EVASION CHECK (Haste increases evasion)
    let enemyEvasion = (enemy.value.evasion || 0)
    if (enemy.value.statuses.some(st => st.id === 'haste')) {
      enemyEvasion += 15
    }

    if (Math.random() * 100 < enemyEvasion) {
      pushLog(`¡FALLASTE! El ${enemy.value.name} ha evadido tu ataque.`);
    } else {
      // ... rest of the logic
      // CRITICAL CHECK
      // CRITICAL CHECK (Speed/Agility increases critical rate)
      const heroCrit = (hero.value.critRate || 0) + Math.floor(hero.value.speed / 2)
      if (Math.random() * 100 < heroCrit) {
        finalDmg = Math.floor(finalDmg * 2.0); // Player crits are x2
        pushLog(`¡CRÍTICO! ${s.name}: -${finalDmg} Vida.`);
      } else {
        pushLog(`${s.name}: -${finalDmg} Vida.`);
      }
      enemy.value.hp = Math.max(0, enemy.value.hp - finalDmg)
      triggerHitEnemy()

      // APPLY STATUS EFFECT
      if (s.statusEffect && Math.random() * 100 < s.statusEffect.chance) {
        const target = (s.type === 'buff' || s.type === 'heal') ? hero.value : enemy.value
        applyStatus(target, s.statusEffect.id, s.statusEffect.duration)
      }
    }
  } else if (s.type === 'heal') {
    const heal = Math.floor((hero.value.magicAttack * 2) * (s.power / 100))
    hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + heal)
    pushLog(`${s.name}: +${heal} Vida.`)
    
    if (s.statusEffect && Math.random() * 100 < s.statusEffect.chance) {
      applyStatus(hero.value, s.statusEffect.id, s.statusEffect.duration)
    }
  } else if (s.type === 'buff') {
    pushLog(`${s.name}: Refuerzas tus capacidades.`)
    if (s.statusEffect && Math.random() * 100 < s.statusEffect.chance) {
      applyStatus(hero.value, s.statusEffect.id, s.statusEffect.duration)
    }
  }
  
  if (endCombatIfNeeded()) return
  turn.value = 'enemy'
  
  // Process player statuses at end of turn (or start of next turn cycle)
  setTimeout(() => {
    const isStunned = processStatuses(hero.value)
    if (isStunned) {
      if (endCombatIfNeeded()) return
      // Skip enemy turn if hero is stunned? No, skip hero turn.
      // Wait, if hero is stunned, they already acted this turn. 
      // Roguelike logic: Stun usually skips the NEXT turn.
    }
    enemyTurn()
  }, 800)
}

function advanceRoom() {
  if (phase.value === 'staircase') {
    run.value.floor++
    run.value.roomInFloor = 1
    phase.value = 'room'
    saveRun() // Autosave on floor change
    enterRoom()
  } else if (run.value.roomInFloor === 1 || run.value.roomInFloor === 2) {
    // If we are in room 1, we select path for room 2
    // If we are in room 2, we advance to room 3 (Staircase/Boss)
    if (run.value.roomInFloor === 1) {
      pathChoices.value = generatePathChoices()
      phase.value = 'pathSelect'
    } else if (run.value.roomInFloor === 2) {
      run.value.roomInFloor++
      phase.value = 'room'
      enterRoom()
    } else {
      // Room 3 (Boss/Stairs)
      phase.value = 'staircase'
      pushLog(`Las escaleras se revelan ante ti.`)
    }
  } else if (phase.value === 'victory' && run.value.roomInFloor === 3) {
    phase.value = 'staircase'
    pushLog(`Tras derrotar al Guardián, el camino al siguiente piso queda libre.`)
  }
}

function handleEventChoice(choice) {
  let action = choice.action
  let message = action.message

  // Random outcomes
  if (action.type === 'random' && action.outcomes) {
    const rnd = Math.random() * 100
    let cumulative = 0
    for (const outcome of action.outcomes) {
      cumulative += outcome.chance
      if (rnd <= cumulative) {
        action = outcome
        message = outcome.message
        break
      }
    }
  }

  // Apply effects
  if (action.effects) {
    for (const effect of action.effects) {
      if (effect.type === 'stat') {
        const target = effect.target
        const val = effect.value
        
        if (target === 'gold') {
          run.value.gold = Math.max(0, run.value.gold + val)
          if (val > 0) triggerGoldFlash()
        } else if (hero.value[target] !== undefined) {
          if (target === 'hp') {
            hero.value.hp = Math.min(hero.value.maxHp, Math.max(effect.min || 0, hero.value.hp + val))
          } else if (target === 'mp') {
            hero.value.mp = Math.min(hero.value.maxMp, Math.max(0, hero.value.mp + val))
          } else {
            hero.value[target] += val
          }
        }
      } else if (effect.type === 'item') {
        for (let i = 0; i < (effect.count || 1); i++) {
          addItem(allItems[randInt(0, allItems.length - 1)].id)
        }
      }
    }
  }

  pushLog(message)
  currentEvent.value = null
  phase.value = 'victory'
}

function checkChoiceCondition(choice) {
  if (!choice.condition) return true
  const cond = choice.condition
  if (cond.type === 'stat') {
    if (cond.target === 'gold') return run.value.gold >= cond.min
    if (hero.value[cond.target] !== undefined) return hero.value[cond.target] >= cond.min
  }
  return true
}


const canAdvanceRoom = computed(() => ['victory', 'treasure', 'camp', 'staircase'].includes(phase.value))

async function startNewRun() { 
  try {
    const data = await gameEngine.play(GAME_SLUG, false)
    sessionId.value = data.session_id
    playStartTime.value = Date.now()
    phase.value = 'classSelect'
    showModal.value = true
  } catch (e) {
    error.value = "Error al iniciar sesión."
  }
}

async function checkSave() {
  try {
    const data = await gameEngine.load(GAME_SLUG)
    if (data && data.game_state && data.game_state.hero) {
      hasSave.value = true
      saveSummary.value = {
        className: data.game_state.hero.className,
        level: data.game_state.hero.level,
        floor: data.game_state.floor
      }
    } else {
      hasSave.value = false
      saveSummary.value = null
    }
  } catch (e) {
    hasSave.value = false
    saveSummary.value = null
  }
}

async function loadRun() { 
  isLoading.value = true
  try {
    const data = await gameEngine.play(GAME_SLUG, true)
    sessionId.value = data.session_id
    playStartTime.value = Date.now()
    totalTimePlayed.value = data.playtime || 0
    applyLoadedState(data.game_state)
    showModal.value = true
  } catch (e) {
    error.value = "Error al cargar la gesta."
  } finally {
    isLoading.value = false
  }
}

async function resetSave() {
  try {
    await gameEngine.reset(GAME_SLUG)
    hasSave.value = false
    saveSummary.value = null
  } catch (e) {
    console.error("Error reseteando guardado")
  }
}

async function saveRun() { 
  if (!sessionId.value) return; 
  isLoading.value = true; 
  const currentDuration = Math.floor((Date.now() - playStartTime.value) / 1000);
  try { 
    const p = { 
      session_id: sessionId.value, 
      score: run.value.floor,
      playtime: currentDuration,
      game_state: { 
        floor: run.value.floor, 
        roomInFloor: run.value.roomInFloor, 
        hero: clone(run.value.hero), 
        enemy: clone(run.value.enemy), 
        gold: run.value.gold, 
        phase: phase.value, 
        turn: turn.value, 
        log: clone(log.value) 
      } 
    }; 
    await gameEngine.save(GAME_SLUG, p); 
    pushLog('Crónica guardada en los anales.') 
    hasSave.value = true
    // Update start time to avoid double counting playtime in next save
    playStartTime.value = Date.now()
  } catch (e) { 
    error.value = 'Error al guardar.' 
  } finally { 
    isLoading.value = false 
  } 
}

function applyLoadedState(s) { 
  if (!s) return
  run.value = { 
    floor: s.floor || 1, 
    roomInFloor: s.roomInFloor || 1, 
    hero: s.hero || null, 
    enemy: s.enemy || null, 
    gold: s.gold || 0 
  }; 
  phase.value = s.phase || 'room'; 
  turn.value = s.turn || 'hero'; 
  log.value = s.log || [] 
}

onMounted(() => {
  checkSave()
  if (run.value.hero) phase.value = 'room'
  else phase.value = 'idle'
})
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 8px; }
.custom-scroll::-webkit-scrollbar-track { background: rgba(0,0,0,0.4); }
.custom-scroll::-webkit-scrollbar-thumb { background: #3c2a1a; border: 2px solid #1a1a1a; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: #8c2d1f; }

/* GRIM FADE */
.fade-grim-enter-active, .fade-grim-leave-active { transition: all 1s ease-in-out; }
.fade-grim-enter-from, .fade-grim-leave-to { opacity: 0; filter: blur(20px); }

/* MODAL GRIM UNFOLD */
.modal-grim-enter-active { animation: grim-unfold 1.2s cubic-bezier(0.23, 1, 0.32, 1); }
.modal-grim-leave-active { transition: all 0.6s ease-in; opacity: 0; filter: blur(10px); }

@keyframes grim-unfold {
  0% { transform: scaleY(0.001) scaleX(0); opacity: 0; }
  50% { transform: scaleY(0.001) scaleX(1); opacity: 0.5; }
  100% { transform: scaleY(1) scaleX(1); opacity: 1; }
}

@keyframes flicker {
  0%, 19%, 21%, 59%, 61%, 100% { opacity: 0.3; }
  20%, 60% { opacity: 0.1; }
}
.animate-flicker { animation: flicker 5s infinite; }

@keyframes liquid-bubble {
  0% { transform: translateY(100%); opacity: 0; }
  50% { opacity: 0.5; }
  100% { transform: translateY(-100%); opacity: 0; }
}
.animate-liquid-bubble { animation: liquid-bubble 4s infinite linear; }
.animate-liquid-bubble-slow { animation: liquid-bubble 8s infinite linear; }

@keyframes pulse-slow {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.1; transform: scale(1.1); }
}
.animate-pulse-slow { animation: pulse-slow 4s infinite; }

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px) rotate(-1deg); }
  75% { transform: translateX(10px) rotate(1deg); }
}
.animate-shake { animation: shake 0.2s 2 linear; }

/* CHRONICLE LOGS */
.log-grim-enter-active { transition: all 0.5s ease-out; }
.log-grim-enter-from { opacity: 0; transform: translateX(-10px); }

/* IRON SHADOW CUSTOM */
.iron-shadow {
  box-shadow: inset 0 0 20px rgba(0,0,0,0.8), 4px 4px 8px rgba(0,0,0,0.4);
}

@keyframes pulse-rarity {
  0%, 100% { border-color: rgba(245, 158, 11, 0.6); box-shadow: 0 0 10px rgba(245, 158, 11, 0.1), inset 0 0 15px rgba(245, 158, 11, 0.2); }
  50% { border-color: rgba(245, 158, 11, 1); box-shadow: 0 0 20px rgba(245, 158, 11, 0.3), inset 0 0 25px rgba(245, 158, 11, 0.4); }
}
.animate-pulse-rarity { animation: pulse-rarity 2s infinite ease-in-out; }
</style>
