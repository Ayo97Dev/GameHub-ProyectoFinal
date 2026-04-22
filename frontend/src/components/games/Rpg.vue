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
      <div v-if="!showModal" class="absolute inset-0 z-[100] flex items-center justify-center bg-[#070707]">
        <!-- Flickering Shadow Overlay -->
        <div class="absolute inset-0 opacity-30 animate-flicker pointer-events-none bg-[radial-gradient(circle_at_50%_50%,rgba(60,40,20,0.4),transparent_70%)]"></div>
        
        <div class="relative z-10 flex flex-col items-center gap-10 text-center px-6">
          <div class="space-y-3">
            <span class="text-[#8c2d1f] font-fantasy text-sm tracking-[0.5em] uppercase opacity-50 animate-pulse">A GameHub Dark Chronicle</span>
            <h1 class="text-6xl md:text-8xl font-fantasy text-[#b8a38a] drop-shadow-[0_10px_20px_rgba(0,0,0,1)] tracking-tighter uppercase">Dungeon RPG</h1>
            <div class="h-1 w-32 mx-auto bg-gradient-to-r from-transparent via-[#8c2d1f] to-transparent"></div>
          </div>
          
          <button 
            @click="showModal = true" 
            class="group relative px-16 py-6 transition-all duration-500 transform hover:scale-105 active:scale-95"
          >
            <!-- Border Layers for "Engraved" feel -->
            <div class="absolute inset-0 bg-[#1a1a1a] border-4 border-[#3c2a1a] shadow-[10px_10px_30px_rgba(0,0,0,0.8)]"></div>
            <div class="absolute inset-1 border-2 border-[#8c2d1f]/30"></div>
            <span class="relative z-10 font-fantasy text-3xl text-[#b8a38a] group-hover:text-white uppercase tracking-[0.2em] transition-colors">Entrar</span>
          </button>

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
          
          <!-- EXIT SEAL (Wax Seal Style Button) - Pinned to absolute top-right of the viewport -->
          <button 
            @click="showExitConfirm = true" 
            class="fixed top-4 right-4 z-[100050] size-14 flex items-center justify-center group transition-transform hover:rotate-12 active:scale-90"
            title="Abandonar Mazmorra"
          >
            <div class="absolute inset-0 bg-[#8c2d1f] rounded-full shadow-[0_4px_12px_rgba(0,0,0,1)] border-4 border-[#5c1a11] group-hover:bg-[#a63626] transition-colors"></div>
            <div class="absolute inset-1.5 border-2 border-dashed border-black/30 rounded-full"></div>
            <Icon icon="lucide:skull" class="relative z-10 text-black text-2xl pointer-events-none" />
          </button>

          <!-- Main Parchment Frame -->
          <div class="relative w-full h-full flex flex-col bg-[#24211e] shadow-inner overflow-hidden border-[16px] border-double border-[#3c2a1a]">
            
            <!-- CORNER ORNAMENTS (SVG) - Slightly smaller and more transparent to avoid clutter -->
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

            <!-- CSS-Only Parchment Texture Overlay - Lightened and cleaned up -->
            <div class="absolute inset-0 pointer-events-none opacity-50 mix-blend-multiply bg-[radial-gradient(circle,#fdf0dc_0%,#e8d3b9_100%)]"></div>
            <div class="absolute inset-0 pointer-events-none opacity-20 bg-[url('https://www.transparenttextures.com/patterns/natural-paper.png')]"></div>
            
            <!-- Atmospheric Vignette - Softened to opacity 0.25 -->
            <div class="absolute inset-0 pointer-events-none z-40 bg-[radial-gradient(circle,transparent_50%,rgba(0,0,0,0.3)_130%)]"></div>

            <!-- HEADER: Ancient Archive (Increased pr-48 for safety) -->
            <header class="relative z-[60] p-4 sm:p-5 pr-48 lg:pr-64 flex flex-col md:flex-row items-center justify-between gap-6 border-b-4 border-double border-[#3c2a1a] bg-black/40 backdrop-blur-sm">
              <div class="flex items-center gap-6">
                <div class="size-14 bg-[#0a0a0a] flex items-center justify-center text-[#8c2d1f] text-3xl border-4 border-[#3c2a1a] shadow-2xl iron-shadow transform -rotate-3">
                  <Icon icon="lucide:swords" />
                </div>
                <div>
                  <h2 class="font-fantasy text-2xl text-[#b8a38a] uppercase tracking-wide drop-shadow-md">Cronista del Abismo</h2>
                  <div class="flex items-center gap-3 text-[#b8a38a]/70 font-fantasy text-[10px] uppercase tracking-[0.2em]">
                    <span class="animate-pulse text-[#8c2d1f]">●</span>
                    <span class="">Profundidad: Sótano Maldito</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-8">
                <!-- MAP TRACKER (Soul Stones with Path) -->
                <div class="relative flex gap-4 p-2.5 bg-black/30 border border-[#3c2a1a] shadow-inner items-center min-w-[240px] justify-center">
                  <!-- Ethereal Path Line -->
                  <div class="absolute top-1/2 left-8 right-8 h-0.5 bg-[#3c2a1a]/30 -translate-y-1/2 z-0"></div>
                  <div 
                    class="absolute top-1/2 left-8 h-0.5 bg-gradient-to-r from-[#8c2d1f] to-white/40 -translate-y-1/2 z-0 transition-all duration-1000"
                    :style="{ width: (currentRoomIndex / (run.map.length-1) * 88) + '%' }"
                  ></div>

                  <div
                    v-for="(room, idx) in run.map"
                    :key="room.id"
                    class="size-4.5 rounded-full border-2 transition-all duration-700 flex items-center justify-center relative z-10"
                    :class="idx === currentRoomIndex
                      ? 'bg-white border-white shadow-[0_0_15px_rgba(255,255,255,0.8)] scale-125'
                      : (idx < currentRoomIndex ? 'bg-[#8c2d1f] border-[#5c1a11] shadow-[0_0_8px_rgba(140,45,31,0.4)]' : 'bg-black/80 border-[#3c2a1a]')"
                  >
                  </div>
                </div>
                
                <div class="flex gap-3">
                    <button @click="startNewRun" class="group relative px-6 py-2 transition-all">
                        <div class="absolute inset-0 bg-[#0a0a0a] border border-[#3c2a1a] group-hover:bg-black group-hover:border-[#b8a38a]/40 transition-colors"></div>
                        <span class="relative z-10 font-fantasy text-[10px] uppercase text-[#b8a38a] group-hover:text-white">Reiniciar</span>
                    </button>
                    <button @click="saveRun" class="group relative px-6 py-2 overflow-hidden transition-all shadow-lg">
                        <div class="absolute inset-0 bg-[#8c2d1f] border border-white/10 group-hover:scale-105 transition-transform"></div>
                        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/black-paper.png')] opacity-20"></div>
                        <span class="relative z-10 font-fantasy text-[10px] uppercase text-white tracking-widest">Sellar Gesta</span>
                    </button>
                </div>
              </div>
            </header>

            <!-- MAIN DUNGEON INTERFACE - Reduced padding to p-6 -->
            <main class="flex-1 flex flex-col lg:flex-row min-h-0 relative z-20 gap-8 p-6 lg:p-8 overflow-auto custom-scroll">
              
              <!-- COMBAT ZONE -->
              <div class="flex-1 flex flex-col gap-6 lg:max-w-5xl mx-auto w-full h-full min-h-0">
                
                <!-- STAGE VIEW - flex-1 with min-h-0 to avoid push -->
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
                      <div class="flex flex-col items-center" :class="{ 'animate-shake': hitHero }">
                         <div class="relative group">
                            <!-- Turn Aura -->
                            <div v-if="turn === 'hero' && phase === 'combat'" class="absolute inset-0 bg-[#b8a38a]/5 blur-[60px] rounded-full scale-150 animate-pulse-slow"></div>
                            
                            <div 
                              class="size-48 sm:size-60 bg-[#0a0a0a] border-[8px] border-double border-[#3c2a1a] flex items-center justify-center transition-all duration-700 transform relative z-10 overflow-hidden shadow-[inset_0_0_40px_rgba(0,0,0,1)]"
                              :class="{ 'border-[#b8a38a]/30 shadow-[0_0_40px_rgba(184,163,138,0.1),inset_0_0_40px_rgba(0,0,0,1)]': turn === 'hero' && phase === 'combat' }"
                            >
                               <Icon icon="lucide:shield-check" class="size-36 text-[#b8a38a]/20 drop-shadow-md" />
                               <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            </div>
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-8 py-2 bg-[#1a1a1a] text-[#b8a38a] font-fantasy text-xs uppercase tracking-[0.3em] shadow-2xl border-2 border-[#3c2a1a] z-20">EL ELEGIDO</div>
                         </div>
                         
                         <div class="w-full mt-10 space-y-4 max-w-[240px]">
                            <!-- HP VIAL -->
                            <div class="space-y-1">
                               <div class="flex justify-between font-fantasy text-[9px] text-[#b8a38a]/60 uppercase tracking-widest">
                                  <span>VITALIDAD</span>
                                  <span class="text-[#8c2d1f] font-bold">{{ hero.hp }} / {{ hero.maxHp }}</span>
                               </div>
                               <div class="h-4 w-full bg-black/60 border-2 border-[#3c2a1a] p-0.5 shadow-inner relative">
                                  <div 
                                    class="h-full bg-gradient-to-r from-[#5c1a11] via-[#8c2d1f] to-[#b91c1c] transition-all duration-1000 relative overflow-hidden" 
                                    :style="{ width: (hero.hp/hero.maxHp*100) + '%' }"
                                  >
                                     <div class="absolute inset-y-0 left-0 w-full animate-liquid-bubble opacity-30 bg-gradient-to-t from-white/20 to-transparent"></div>
                                  </div>
                               </div>
                            </div>
                            <!-- SPIRIT VIAL -->
                            <div class="space-y-1">
                               <div class="flex justify-between font-fantasy text-[9px] text-[#b8a38a]/60 uppercase tracking-widest">
                                  <span>CORDURA</span>
                                  <span class="text-[#3b82f6] font-bold">{{ 100 - hero.stress }}%</span>
                               </div>
                               <div class="h-4 w-full bg-black/60 border-2 border-[#3c2a1a] p-0.5 shadow-inner relative">
                                  <div 
                                    class="h-full bg-gradient-to-r from-[#172554] via-[#1e3a8a] to-[#3b82f6] transition-all duration-1000 relative overflow-hidden" 
                                    :style="{ width: ( (100 - hero.stress) ) + '%' }"
                                  >
                                     <div class="absolute inset-y-0 left-0 w-full animate-liquid-bubble-slow opacity-20 bg-gradient-to-t from-white/10 to-transparent"></div>
                                  </div>
                               </div>
                            </div>
                         </div>
                      </div>

                      <!-- ENEMY AVATAR -->
                      <div class="flex flex-col items-center" :class="{ 'animate-shake': hitEnemy }">
                         <div class="relative">
                            <div v-if="turn === 'enemy' && phase === 'combat'" class="absolute inset-0 bg-red-950/20 blur-[60px] rounded-full scale-150 animate-pulse"></div>
                            
                            <div 
                              class="size-48 sm:size-60 bg-black/80 border-[8px] border-double border-red-950/40 flex items-center justify-center transition-all duration-700 transform relative z-10 shadow-[inset_0_0_50px_rgba(0,0,0,1)]"
                              :class="{ 'border-red-900 shadow-[0_0_40px_rgba(153,27,27,0.1),inset_0_0_50px_rgba(0,0,0,1)]': turn === 'enemy' && phase === 'combat' }"
                            >
                               <Icon icon="lucide:skull" class="size-36 text-red-950/10" />
                            </div>
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-8 py-2 bg-[#1a0505] text-red-700 font-fantasy text-xs uppercase tracking-[0.3em] shadow-2xl border-2 border-red-950 z-20">{{ enemy.name }}</div>
                         </div>

                         <div class="w-full mt-10 max-w-[240px]">
                            <div class="flex justify-between font-fantasy text-[9px] text-red-900/60 uppercase tracking-widest">
                               <span>MALICIA</span>
                               <span class="font-bold">{{ enemy.hp }} / {{ enemy.maxHp }}</span>
                            </div>
                            <div class="h-4 w-full bg-black/60 border-2 border-red-950/40 p-0.5 shadow-inner relative">
                               <div 
                                 class="h-full bg-gradient-to-r from-[#450a0a] via-[#7f1d1d] to-[#991b1b] transition-all duration-1000" 
                                 :style="{ width: (enemy.hp/enemy.maxHp*100) + '%' }"
                               ></div>
                            </div>
                            <p class="font-fantasy text-[9px] text-[#b8a38a]/20 text-center mt-4 uppercase tracking-[0.4em] italic leading-none">PROLE • NIVEL {{ enemy.level }}</p>
                         </div>
                      </div>
                   </div>
                </div>

                <!-- RUNIC COMMAND BAR - Reduced height and padding -->
                <div class="p-4 bg-black/40 border-8 border-double border-[#3c2a1a] shadow-xl relative overflow-hidden mt-auto">
                   <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] opacity-10 pointer-events-none"></div>
                   
                   <div class="flex justify-between items-center mb-4 pb-3 border-b border-[#3c2a1a]/40 relative z-10">
                        <p class="font-fantasy text-base text-[#b8a38a]/70 uppercase tracking-[0.3em]">Invocaciones Arcanas</p>
                        <button
                            class="group relative px-8 py-2 transition-all disabled:opacity-20"
                            :disabled="isLoading || !canAdvanceRoom"
                            @click="advanceRoom"
                          >
                             <div class="absolute inset-0 bg-[#3c2a1a] border border-[#b8a38a]/10 group-hover:bg-[#4d3621]"></div>
                             <span class="relative z-10 font-fantasy text-[10px] uppercase text-[#b8a38a]">Descender</span>
                          </button>
                   </div>
                   
                   <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 relative z-10">
                      <button
                        v-for="skill in heroSkills"
                        :key="skill.id"
                        class="group relative flex flex-col items-center justify-center p-3.5 border-2 transition-all duration-500 transform overflow-hidden"
                        :class="canUseSkill(skill) 
                          ? 'border-[#3c2a1a] bg-[#1a1a17] hover:border-[#b8a38a]/30 hover:bg-[#221a1a] hover:-translate-y-1 cursor-pointer shadow-lg' 
                          : 'border-[#0a0a0a] bg-black opacity-30 cursor-not-allowed grayscale '"
                        @click="useSkill(skill.id)"
                      >
                          <span class="font-fantasy text-[13px] mb-0.5 text-center group-hover:text-white transition-colors tracking-widest">{{ skill.name }}</span>
                          <p class="font-serif text-[8px] text-center italic text-[#b8a38a]/30 leading-tight group-hover:text-[#b8a38a]/60">{{ skill.description }}</p>
                          
                          <div v-if="skillCooldownRemaining(skill.id) > 0" class="absolute inset-0 bg-black/70 flex items-center justify-center backdrop-blur-[1px]">
                             <span class="text-white font-fantasy text-xl">{{ skillCooldownRemaining(skill.id) }}</span>
                          </div>
                      </button>
                   </div>
                </div>

              </div>

              <!-- CHRONICLE ASIDE -->
              <aside class="w-full lg:w-[320px] flex flex-col gap-6">
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
                    <Icon icon="lucide:coins" class="text-[#b8a38a] text-2xl" :class="{ 'animate-bounce text-amber-500': goldFlash }" />
                    <span class="font-fantasy text-[#b8a38a] text-2xl tracking-tighter">{{ run.gold }}</span>
                  </div>
                </div>
              </aside>
            </main>
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
import gameEngine from '../../lib/gameEngineService'

const GAME_SLUG = 'rpg'
const isLoading = ref(false); const error = ref(null); const sessionId = ref(null); const log = ref([]); 
const showModal = ref(false); const showExitConfirm = ref(false);
const goldFlash = ref(false); const hitHero = ref(false); const hitEnemy = ref(false);

function pushLog(m) { log.value.unshift(m.toUpperCase()); if (log.value.length > 50) log.value.length = 50 }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min }
function clone(obj) { return JSON.parse(JSON.stringify(obj)) }

function makeHero() {
  return {
    name: 'Cruzado', level: 1, hp: 30, maxHp: 30, stress: 0, maxStress: 100,
    atkMin: 5, atkMax: 9, prot: 0, guard: false,
    skillCooldowns: { smite: 0, defend: 0, inspire: 0, holy_lance: 0 }
  }
}

const heroSkills = [
  { id: 'smite', name: 'GOLPE ARCANO', cooldown: 0, description: 'Un impacto de energía pura.' },
  { id: 'defend', name: 'ESCUDO RÚNICO', cooldown: 1, description: 'Barrera mágica protectora.' },
  { id: 'inspire', name: 'PLEGARIA', cooldown: 2, description: 'Restaura vitalidad y calma.' },
  { id: 'holy_lance', name: 'LANZA DIVINA', cooldown: 3, description: 'Una brecha de luz ancestral.' }
]

function makeEnemy(depth = 0) {
  const names = ['Brigante', 'Aullador', 'Necrófago', 'Cultista']
  const name = names[depth % names.length]; const level = 1 + Math.floor(depth / 3); const maxHp = 20 + (depth * 4)
  return { name, level, hp: maxHp, maxHp, atkMin: 4 + depth, atkMax: 7 + depth }
}

function generateMap(len = 8) {
  const m = []; for (let i = 0; i < len; i++) { let type = 'combat'; if (i !== 0 && i !== len - 1 && i % 3 === 2) type = 'camp'; if (i === len - 1) type = 'boss'; m.push({ id: `rm_${Date.now()}_${i}`, type }) }
  return m
}

const run = ref({ map: generateMap(), roomIndex: 0, hero: makeHero(), enemy: makeEnemy(0), gold: 0 })
const phase = ref('idle'); const turn = ref('hero')

const currentRoomIndex = computed(() => run.value.roomIndex ?? 0)
const hero = computed(() => run.value.hero); const enemy = computed(() => run.value.enemy)

function resetRunLocal() { error.value = null; log.value = []; run.value = { map: generateMap(), roomIndex: 0, hero: makeHero(), enemy: makeEnemy(0), gold: 0 }; phase.value = 'room'; turn.value = 'hero'; pushLog('Entrando en la mazmorra olvidada...'); enterRoom() }
function enterRoom() {
  const room = run.value.map[currentRoomIndex.value]; hero.value.guard = false
  if (room.type === 'camp') {
    phase.value = 'room'; const h = randInt(8, 12); hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + h); const s = randInt(12, 20); hero.value.stress = Math.max(0, hero.value.stress - s); pushLog(`Campamento: +${h} hp / -${s}% estrés.`)
  } else { run.value.enemy = makeEnemy(room.type === 'boss' ? currentRoomIndex.value + 3 : currentRoomIndex.value); phase.value = 'combat'; turn.value = 'hero'; pushLog(`¡Una presencia oscura! Es un ${enemy.value.name}.`) }
}

function tickCooldowns() { const cds = hero.value.skillCooldowns ?? {}; for (const k of Object.keys(cds)) cds[k] = Math.max(Number(cds[k] ?? 0) - 1, 0); hero.value.skillCooldowns = cds }
function setCooldown(id) { const s = heroSkills.find(sk => sk.id === id); if (!s) return; const cds = hero.value.skillCooldowns ?? {}; cds[id] = s.cooldown; hero.value.skillCooldowns = cds }
function skillCooldownRemaining(id) { return Math.max(Number((hero.value.skillCooldowns ?? {})[id] ?? 0), 0) }
function canUseSkill(s) { return phase.value === 'combat' && turn.value === 'hero' && hero.value.hp > 0 && enemy.value.hp > 0 && skillCooldownRemaining(s.id) === 0 }
function exitGame() { showModal.value = false; showExitConfirm.value = false; }

function endCombatIfNeeded() {
  if (enemy.value.hp <= 0) { 
    phase.value = 'victory'; 
    const r = randInt(10, 20); 
    run.value.gold += r; 
    triggerGoldFlash();
    pushLog(`¡Victoria! Has obtenido ${r} monedas de oro.`); 
    return true 
  }
  if (hero.value.hp <= 0 || hero.stress >= hero.maxStress) { phase.value = 'defeat'; pushLog('Has sucumbido ante la oscuridad...'); return true }; return false
}

function triggerGoldFlash() { goldFlash.value = true; setTimeout(() => { goldFlash.value = false }, 1000) }
function triggerHitHero() { hitHero.value = true; setTimeout(() => { hitHero.value = false }, 500) }
function triggerHitEnemy() { hitEnemy.value = true; setTimeout(() => { hitEnemy.value = false }, 500) }

function enemyTurn() {
  if (phase.value !== 'combat' || endCombatIfNeeded()) return
  const dmg = randInt(enemy.value.atkMin, enemy.value.atkMax); const tk = Math.max(dmg - (hero.value.guard ? Math.floor(dmg*0.5) : 0), 0); hero.value.hp = Math.max(0, hero.value.hp - tk); const s = randInt(5, 10); hero.value.stress = Math.min(hero.value.maxStress, hero.value.stress + s)
  triggerHitHero();
  pushLog(`El ${enemy.value.name} ataca: -${tk} Vida / +${s}% Terror.`); hero.value.guard = false; if (!endCombatIfNeeded()) { tickCooldowns(); turn.value = 'hero' }
}

function useSkill(sid) {
  const s = heroSkills.find(sk => sk.id === sid); if (!sid || !canUseSkill(s)) return; error.value = null
  if (sid === 'smite') { 
    const d = randInt(hero.value.atkMin, hero.value.atkMax); 
    enemy.value.hp = Math.max(0, enemy.value.hp - d); 
    triggerHitEnemy();
    pushLog(`Golpe Arcano: -${d} Vida.`) 
  }
  else if (sid === 'defend') { hero.value.guard = true; const r = randInt(3, 6); hero.value.stress = Math.max(0, hero.value.stress - r); pushLog(`Escudo Rúnico: activado / -${r}% Terror.`) }
  else if (sid === 'inspire') { const h = randInt(6, 10); hero.value.hp = Math.min(hero.value.maxHp, hero.value.hp + h); const r = randInt(15, 25); hero.value.stress = Math.max(0, hero.value.stress - r); pushLog(`Plegaria: +${h} Vida / -${r}% Terror.`) }
  else if (sid === 'holy_lance') { 
    const d = randInt(hero.value.atkMin+4, hero.value.atkMax+8); 
    enemy.value.hp = Math.max(0, enemy.value.hp-d); 
    triggerHitEnemy();
    pushLog(`Lanza Divina: -${d} Vida.`) 
  }
  setCooldown(sid); if (endCombatIfNeeded()) return; turn.value = 'enemy'; setTimeout(enemyTurn, 600)
}

function advanceRoom() {
  if (phase.value !== 'victory' && !(phase.value === 'room' && run.value.map[currentRoomIndex.value].type === 'camp')) return
  if (currentRoomIndex.value >= run.value.map.length - 1) { pushLog('Misión completada. ¡Has triunfado!'); phase.value = 'victory'; return }; run.value.roomIndex++; phase.value = 'room'; pushLog(`Avanzando en la mazmorra. Cámara ${currentRoomIndex.value + 1}.`); enterRoom()
}

const canAdvanceRoom = computed(() => phase.value === 'victory' || (phase.value === 'room' && run.value.map[currentRoomIndex.value].type === 'camp'))

async function startNewRun() { isLoading.value = true; try { const res = await gameEngine.play(GAME_SLUG, false); sessionId.value = res.session_id; applyLoadedState(res.game_state || {}); pushLog('Comienza una nueva gesta...') } catch (e) { resetRunLocal() } finally { isLoading.value = false } }
async function loadRun() { isLoading.value = true; try { const res = await gameEngine.play(GAME_SLUG, true); sessionId.value = res.session_id; if (res.game_state) { applyLoadedState(res.game_state); pushLog('Crónica recuperada.'); return } } catch (e) { error.value = 'Error.' } finally { isLoading.value = false } }
async function saveRun() { if (!sessionId.value) return; isLoading.value = true; try { const p = { session_id: sessionId.value, game_state: { map: clone(run.value.map), roomIndex: run.value.roomIndex, hero: clone(run.value.hero), enemy: clone(run.value.enemy), gold: run.value.gold, phase: phase.value, turn: turn.value, log: clone(log.value) } }; await gameEngine.save(GAME_SLUG, p); pushLog('Crónica guardada en los anales.') } catch (e) { error.value = 'Error.' } finally { isLoading.value = false } }

function applyLoadedState(s) { run.value = { map: s.map || generateMap(), roomIndex: s.roomIndex || 0, hero: s.hero || makeHero(), enemy: s.enemy || makeEnemy(0), gold: s.gold || 0 }; phase.value = s.phase || 'room'; turn.value = s.turn || 'hero'; log.value = s.log || [] }

onMounted(() => resetRunLocal())
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
</style>
