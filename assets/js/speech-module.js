/* assets/js/speech-module.js */
(function() {
    // --- STATE MANAGEMENT ---
    let voiceList = [];
    let preferredVoice = null;
    let speechQueue = [];       // Queue to hold pending messages
    let isSpeaking = false;     // Flag to track status
    let activeUtterance = null; // Global reference to prevent Garbage Collection
    let hasUserInteracted = false;
    let watchdogTimer = null;   // NEW: Safety timer for stuck audio

    // --- CRITICAL FIX: RESET ENGINE ON LOAD ---
    // Android often gets stuck in "speaking" state between page navigations.
    // We force a cancel immediately to clear any debris from the previous page.
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }

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
        // Safety check: If browser says it's not speaking but we think it is, reset.
        if (!window.speechSynthesis.speaking && isSpeaking) {
            isSpeaking = false;
        }

        if (isSpeaking || speechQueue.length === 0) return;

        isSpeaking = true;
        const currentItem = speechQueue.shift(); // Get next message

        // General "Wake Up" Fix for all browsers (Android/Windows)
        if (window.speechSynthesis.paused) {
            window.speechSynthesis.resume(); 
        }

        const utterance = new SpeechSynthesisUtterance(currentItem.text);
        
        // --- BROWSER TWEAKS ---
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
        
        // NEW: Watchdog Timer
        // If 'onend' doesn't fire within 8 seconds, we assume it crashed and reset.
        utterance.onstart = function() {
            clearTimeout(watchdogTimer);
            watchdogTimer = setTimeout(() => {
                console.warn("TTS Watchdog: Speech timed out. Resetting engine.");
                window.speechSynthesis.cancel();
                isSpeaking = false;
                activeUtterance = null;
                processQueue(); // Try the next item
            }, 8000); 
        };

        utterance.onend = function() {
            clearTimeout(watchdogTimer); // Clear safety timer
            isSpeaking = false;
            activeUtterance = null; // Release memory
            if (currentItem.callback) currentItem.callback();
            
            // Artificial delay between sentences
            setTimeout(processQueue, 250);
        };

        utterance.onerror = function(e) {
            clearTimeout(watchdogTimer);
            console.warn("TTS Error:", e);
            
            // On Android, an error often means we need to hard reset the engine
            window.speechSynthesis.cancel();
            
            isSpeaking = false;
            activeUtterance = null;
            setTimeout(processQueue, 250);
        };

        // --- GC FIX ---
        activeUtterance = utterance; 

        window.speechSynthesis.speak(utterance);
    }

    // --- PUBLIC API ---
    window.speakText = function(text, onEndCallback, forceInterrupt = false) {
        if (voiceList.length === 0) loadVoices();

        if (forceInterrupt) {
            window.speechSynthesis.cancel();
            speechQueue = [];
            isSpeaking = false;
            clearTimeout(watchdogTimer);
        }

        speechQueue.push({ text: text, callback: onEndCallback });
        processQueue();
    };

    // --- AUDIO UNLOCKER ---
    window.unlockAudio = function() {
        if (hasUserInteracted) return;
        hasUserInteracted = true;
        
        // Just waking up the engine
        if (window.speechSynthesis) {
            window.speechSynthesis.resume();
        }
    };

    // --- CLEANUP ON EXIT (Crucial for Android) ---
    // When the user leaves the page (e.g. Back button or Link), kill the audio.
    window.addEventListener('beforeunload', () => {
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
    });

    // Attach unlocker to global clicks
    document.addEventListener('click', window.unlockAudio, { once: true });
    document.addEventListener('touchstart', window.unlockAudio, { once: true });

})();