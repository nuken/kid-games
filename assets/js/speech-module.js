/*
 * =================================================================
 * MASTER SPEECH & VISUALS MODULE
 * Includes: TTS Fixes, Audio Unlocking, and Reliable Space Confetti
 * =================================================================
 */

// --- PART 1: ROBUST SPEECH SYNTHESIS LOGIC ---

window.voiceList = [];
window.preferredVoice = null;

window.loadVoices = function() {
    if (window.voiceList.length > 0) return;
    window.voiceList = window.speechSynthesis.getVoices();

    if (window.voiceList.length > 0) {
        window.preferredVoice = null; 
        const savedName = localStorage.getItem('klh_preferred_voice');
        if (savedName) {
            window.preferredVoice = window.voiceList.find(v => v.name === savedName);
        }
        if (!window.preferredVoice) {
            const winHighQuality = ['Natural', 'Online', 'Google US English'];
            window.preferredVoice = window.voiceList.find(v => 
                v.lang.startsWith('en') && 
                winHighQuality.some(keyword => v.name.includes(keyword))
            );
        }
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

window.speakText = function(text, onEndCallback) {
    window.speechSynthesis.cancel();
    if (window.voiceList.length === 0) window.loadVoices();

    if (navigator.platform.indexOf('Win') > -1) {
        const primer = new SpeechSynthesisUtterance("_");
        primer.volume = 0.01; 
        primer.rate = 10;
        window.speechSynthesis.speak(primer);
    }

    const utterance = new SpeechSynthesisUtterance(text);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    
    if (isIOS) {
        utterance.rate = 1.05; 
        utterance.pitch = 1.1; 
    } else {
        utterance.rate = 0.95; 
        utterance.pitch = 1.1; 
    }

    if (window.preferredVoice) {
        utterance.voice = window.preferredVoice;
        utterance.lang = window.preferredVoice.lang; 
    }

    if (onEndCallback) {
        utterance.onend = onEndCallback;
        utterance.onerror = onEndCallback;
    }

    window.speechSynthesis.speak(utterance);
};


// --- PART 2: AUDIO UNLOCKER ---
;(function globalUnlockSpeech() {
  window.unlockSpeechIfNeeded = function() {
    return new Promise((resolve) => {
      if (!('speechSynthesis' in window)) { resolve(false); return; }
      const utter = new SpeechSynthesisUtterance(' ');
      utter.volume = 0;
      utter.onend = () => resolve(true);
      window.speechSynthesis.speak(utter);
      setTimeout(() => resolve(true), 500);
    });
  };
})();


// --- PART 3: RELIABLE SPACE CONFETTI ---
window.playConfettiEffect = function() {
    // 1. Get or Create the Shared Container (Never Destroy it)
    let container = document.getElementById('global-confetti-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'global-confetti-container';
        container.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;overflow:hidden;';
        document.body.appendChild(container);
    }

    const spaceIcons = ['🚀', '⭐', '🤖', '🪐', '⚙️', '🛸', '✨'];
    const particleCount = 40; 

    for (let i = 0; i < particleCount; i++) {
        const el = document.createElement('div');
        el.innerText = spaceIcons[Math.floor(Math.random() * spaceIcons.length)];
        
        const startX = Math.random() * 100; 
        const size = Math.random() * 20 + 25; 

        el.style.cssText = `
            position: absolute;
            top: -70px; /* Start slightly higher to ensure off-screen */
            left: ${startX}%;
            font-size: ${size}px;
            will-change: transform;
            opacity: 1;
        `;
        
        container.appendChild(el);

        // Animation Settings
        const duration = Math.random() * 3000 + 3000; // 3s - 6s
        const rotation = Math.random() * 360 - 180;   
        const drift = Math.random() * 150 - 75;       

        // Use Web Animations API
        const anim = el.animate([
            { transform: `translate3d(0, 0, 0) rotate(0deg)`, opacity: 1 },
            { transform: `translate3d(${drift}px, ${window.innerHeight + 100}px, 0) rotate(${rotation}deg)`, opacity: 0 }
        ], {
            duration: duration,
            easing: 'ease-in', // 'ease-in' simulates gravity acceleration better
            delay: Math.random() * 500 
        });

        // Cleanup ONLY this specific particle when it finishes
        anim.onfinish = () => {
            el.remove();
        };
    }
    // We NO LONGER remove the container, preventing the "blocking" issue.
};

window.playBurstEffect = function(el) {
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    for(let i=0; i<15; i++) {
        const p = document.createElement('div');
        p.innerText = '⭐';
        p.style.cssText = `
            position: fixed;
            left: ${centerX}px;
            top: ${centerY}px;
            z-index: 1000;
            font-size: 20px;
            pointer-events: none;
            will-change: transform, opacity;
        `;
        document.body.appendChild(p);
        
        const angle = Math.random() * Math.PI * 2;
        const velocity = 50 + Math.random() * 50; 
        const tx = Math.cos(angle) * velocity;
        const ty = Math.sin(angle) * velocity;

        const anim = p.animate([
            { transform: 'translate(0,0) scale(0.5)', opacity: 1 },
            { transform: `translate(${tx}px, ${ty}px) scale(1.2)`, opacity: 0 }
        ], {
            duration: 1000,
            easing: 'ease-out'
        });

        anim.onfinish = () => p.remove();
    }
};