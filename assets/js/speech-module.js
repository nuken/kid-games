/* assets/js/speech-module.js */
(function() {
    // --- STATE MANAGEMENT ---
    let voiceList = [];
    let preferredVoice = null;
    let speechQueue = [];       // Queue to hold pending messages
    let isSpeaking = false;     // Flag to track status
    let activeUtterance = null; // Global reference to prevent Garbage Collection
    let hasUserInteracted = false;

    // --- VOICE LOADING ---
    function loadVoices() {
        // If we already have voices, don't churn
        if (voiceList.length > 0) return;

        voiceList = window.speechSynthesis.getVoices();
        
        if (voiceList.length > 0) {
            // 1. Try to find a saved preference
            const savedName = localStorage.getItem('klh_preferred_voice');
            if (savedName) {
                preferredVoice = voiceList.find(v => v.name === savedName);
            }

            // 2. Fallback: Look for high-quality English voices
            if (!preferredVoice) {
                const qualityKeywords = ['Natural', 'Premium', 'Google US English', 'Samantha'];
                preferredVoice = voiceList.find(v => 
                    v.lang.startsWith('en') && 
                    qualityKeywords.some(keyword => v.name.includes(keyword))
                );
            }

            // 3. Fallback: Any US English voice
            if (!preferredVoice) {
                preferredVoice = voiceList.find(v => v.lang === 'en-US');
            }

            // 4. Last Resort: First available voice
            if (!preferredVoice) {
                preferredVoice = voiceList[0];
            }
            
            console.log("TTS System: Voice loaded ->", preferredVoice ? preferredVoice.name : "None");
        }
    }

    // Initialize voices immediately and on change
    loadVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }

    // --- QUEUE PROCESSOR ---
    function processQueue() {
        if (isSpeaking || speechQueue.length === 0) return;

        isSpeaking = true;
        const currentItem = speechQueue.shift(); // Get next message

        // Windows Chrome Bug Fix: "Wake up" the engine with a silent pause
        if (navigator.platform.indexOf('Win') > -1) {
            window.speechSynthesis.resume(); 
        }

        const utterance = new SpeechSynthesisUtterance(currentItem.text);
        
        // --- BROWSER TWEAKS ---
        // iOS tends to handle pitch differently; a slight tweak helps realism
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (isIOS) {
            utterance.rate = 1.0; 
            utterance.pitch = 1.1; 
        } else {
            utterance.rate = 1.0; 
            utterance.pitch = 1.0; 
        }

        if (preferredVoice) {
            utterance.voice = preferredVoice;
        }

        // --- EVENT HANDLERS ---
        utterance.onend = function() {
            isSpeaking = false;
            activeUtterance = null; // Release memory
            if (currentItem.callback) currentItem.callback();
            
            // Artificial delay between sentences so they don't blend too much
            setTimeout(processQueue, 250);
        };

        utterance.onerror = function(e) {
            console.warn("TTS Error:", e);
            isSpeaking = false;
            activeUtterance = null;
            setTimeout(processQueue, 250);
        };

        // --- GC FIX ---
        // We MUST assign this to a global/persistent variable or Chrome 
        // might delete the object while it is speaking.
        activeUtterance = utterance; 

        window.speechSynthesis.speak(utterance);
    }

    // --- PUBLIC API ---
    window.speakText = function(text, onEndCallback, forceInterrupt = false) {
        // Ensure voices are loaded before trying to speak
        if (voiceList.length === 0) loadVoices();

        // Optional: Force Interrupt (good for Reset buttons or new Game starts)
        if (forceInterrupt) {
            window.speechSynthesis.cancel();
            speechQueue = [];
            isSpeaking = false;
        }

        // Add to queue
        speechQueue.push({ text: text, callback: onEndCallback });
        
        // Try to process
        processQueue();
    };

    // --- AUDIO UNLOCKER ---
    // Mobile browsers require a user interaction to play the first audio.
    // Call this on the first click anywhere on the page.
    window.unlockAudio = function() {
        if (hasUserInteracted) return;
        hasUserInteracted = true;
        
        const buffer = window.speechSynthesis;
        const utter = new SpeechSynthesisUtterance("");
        utter.volume = 0;
        buffer.speak(utter);
    };

    // Attach unlocker to global clicks
    document.addEventListener('click', window.unlockAudio, { once: true });
    document.addEventListener('touchstart', window.unlockAudio, { once: true });

})();