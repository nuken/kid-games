/*
 * =================================================================
 * MASTER SPEECH & VISUALS MODULE (No Music)
 * Includes: TTS Fixes, Audio Unlocking, and Particle Effects
 * =================================================================
 */

// --- PART 1: ROBUST SPEECH SYNTHESIS LOGIC ---

window.voiceList = [];
window.preferredVoice = null;

/**
 * Loads voices and picks the best "human-sounding" one for the platform.
 */
window.loadVoices = function() {
    if (window.voiceList.length > 0) return;
    
    window.voiceList = window.speechSynthesis.getVoices();

    if (window.voiceList.length > 0) {
        window.preferredVoice = null; 

        // 1. Saved Preference
        const savedName = localStorage.getItem('klh_preferred_voice');
        if (savedName) {
            window.preferredVoice = window.voiceList.find(v => v.name === savedName);
        }

        // 2. Windows "Natural" Voices
        if (!window.preferredVoice) {
            const winHighQuality = ['Natural', 'Online', 'Google US English'];
            window.preferredVoice = window.voiceList.find(v => 
                v.lang.startsWith('en') && 
                winHighQuality.some(keyword => v.name.includes(keyword))
            );
        }

        // 3. iOS High-Quality Favorites
        if (!window.preferredVoice) {
            const iosFavorites = ['Samantha', 'Daniel', 'Karen', 'Moira', 'Rishi', 'Tessa'];
            window.preferredVoice = window.voiceList.find(v => 
                v.lang.startsWith('en') && iosFavorites.includes(v.name)
            );
        }

        // 4. iOS "Enhanced" / "Siri"
        if (!window.preferredVoice) {
            window.preferredVoice = window.voiceList.find(v => 
                v.lang.startsWith('en') && 
                (v.name.includes('Enhanced') || v.name.includes('Siri'))
            );
        }

        // 5. Fallbacks
        if (!window.preferredVoice) {
            window.preferredVoice = window.voiceList.find(v => v.lang === 'en-US' && v.default);
        }
        if (!window.preferredVoice) {
            window.preferredVoice = window.voiceList.find(v => v.lang === 'en-US');
        }
    }
};

window.loadVoices();
window.speechSynthesis.onvoiceschanged = window.loadVoices;

/**
 * Speaks text using the best available voice.
 */
window.speakText = function(text, onEndCallback) {
    window.speechSynthesis.cancel();

    // --- 1. BROWSER FIXES ---
    if (window.voiceList.length === 0) window.loadVoices();

    // Windows Wake-Up Primer
    if (navigator.platform.indexOf('Win') > -1) {
        const primer = new SpeechSynthesisUtterance("_");
        primer.volume = 0.01; 
        primer.rate = 10;
        window.speechSynthesis.speak(primer);
    }

    const utterance = new SpeechSynthesisUtterance(text);

    // iOS Pitch/Rate Adjustments
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (isIOS) {
        utterance.rate = 1.05; 
        utterance.pitch = 1.1; 
    } else {
        utterance.rate = 0.9; 
        utterance.pitch = 1.0;
    }

    // Voice Selection
    if (window.preferredVoice) {
        utterance.voice = window.preferredVoice;
        utterance.lang = window.preferredVoice.lang; 
    } else {
        utterance.lang = 'en-US';
    }

    // --- 2. CALLBACKS ---
    if (onEndCallback) {
        utterance.onend = onEndCallback;
        utterance.onerror = onEndCallback; // Safety fallback
    }

    window.speechSynthesis.speak(utterance);
};


// --- PART 2: AUDIO UNLOCKER (Required for Mobile) ---
;(function globalUnlockSpeech() {
  async function resumeAudio() {
    try {
      const context = (window.__unlockAudioContext && window.__unlockAudioContext.context) || new (window.AudioContext || window.webkitAudioContext)();
      if (context.state === 'suspended') await context.resume();
      const buffer = context.createBuffer(1, 1, context.sampleRate);
      const src = context.createBufferSource();
      src.buffer = buffer;
      src.connect(context.destination);
      src.start(0);
      return true;
    } catch (e) { return false; }
  }

  function speakUnlockUtterance() {
    return new Promise((resolve) => {
      if (!('speechSynthesis' in window)) { resolve(false); return; }
      const utter = new SpeechSynthesisUtterance(' ');
      utter.volume = 0;
      utter.onend = () => resolve(true);
      window.speechSynthesis.speak(utter);
      setTimeout(() => resolve(true), 500);
    });
  }

  window.unlockSpeechIfNeeded = function() {
    return new Promise((resolve) => {
      const tryUnlock = async () => {
        await resumeAudio();
        await speakUnlockUtterance();
        document.removeEventListener('click', tryUnlock);
        document.removeEventListener('touchstart', tryUnlock);
        resolve(true);
      };
      document.addEventListener('click', tryUnlock);
      document.addEventListener('touchstart', tryUnlock);
    });
  };
})();


// --- PART 3: VISUAL EFFECTS (Confetti & Bursts) ---
window.playConfettiEffect = function() {
    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    let w = window.innerWidth, h = window.innerHeight;
    canvas.width = w; canvas.height = h;
    const parts = [];
    const colors = ['#f44336','#2196F3','#4CAF50','#FFEB3B'];
    for(let i=0; i<100; i++) parts.push({
        x: Math.random()*w, y: Math.random()*h-h, vx: Math.random()*2-1, vy: Math.random()*3+2,
        color: colors[Math.floor(Math.random()*colors.length)], size: Math.random()*10+5
    });
    let start = Date.now();
    function loop() {
        if(Date.now()-start > 5000) { canvas.remove(); return; }
        ctx.clearRect(0,0,w,h);
        parts.forEach(p=>{
            p.x+=p.vx; p.y+=p.vy;
            ctx.fillStyle=p.color; ctx.fillRect(p.x,p.y,p.size,p.size);
            if(p.y>h) p.y=-20;
        });
        requestAnimationFrame(loop);
    }
    loop();
};

window.playBurstEffect = function(el) {
    if (!el) return; // Safety check
    const rect = el.getBoundingClientRect();
    const x = rect.left + rect.width/2;
    const y = rect.top + rect.height/2;
    for(let i=0; i<20; i++) {
        const p = document.createElement('div');
        p.className = 'burst-particle'; 
        p.style.cssText = `position:fixed;left:${x}px;top:${y}px;z-index:1000;font-size:20px;pointer-events:none`;
        p.innerHTML = ['★','●','▲'][Math.floor(Math.random()*3)];
        p.style.color = ['#f44336','#2196F3','#4CAF50'][Math.floor(Math.random()*3)];
        document.body.appendChild(p);
        
        const angle = Math.random()*Math.PI*2;
        const dist = 50 + Math.random()*50;
        const dx = Math.cos(angle)*dist;
        const dy = Math.sin(angle)*dist;
        
        p.animate([
            {transform: 'translate(0,0) scale(0.5)', opacity:1},
            {transform: `translate(${dx}px, ${dy}px) scale(1.2)`, opacity:0}
        ], {duration: 800, easing: 'ease-out'}).onfinish = () => p.remove();
    }
};