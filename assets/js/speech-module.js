/* assets/js/speech-module.js */
(function() {
    let voiceList = [];
    let preferredVoice = null; // English default
    let speechQueue = [];
    let isSpeaking = false;
    let activeUtterance = null;
    let hasUserInteracted = false;
    let watchdogTimer = null;

    // --- RESET ON LOAD ---
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }

    function loadVoices() {
        // REMOVED: if (voiceList.length > 0) return; 
        // Android loads voices slowly. We MUST allow this to update 
        // whenever the browser tells us the list has changed.
        
        voiceList = window.speechSynthesis.getVoices();
        
        if (voiceList.length > 0) {
            // Find best English voice for default
            const savedName = localStorage.getItem('klh_preferred_voice');
            if (savedName) preferredVoice = voiceList.find(v => v.name === savedName);

            if (!preferredVoice) {
                const qualityKeywords = ['Natural', 'Premium', 'Google US English', 'Samantha'];
                preferredVoice = voiceList.find(v => 
                    v.lang.startsWith('en') && 
                    qualityKeywords.some(keyword => v.name.includes(keyword))
                );
            }
            if (!preferredVoice) preferredVoice = voiceList.find(v => v.lang === 'en-US');
            if (!preferredVoice) preferredVoice = voiceList[0];
            
            console.log("TTS: Voices Loaded. Count:", voiceList.length);
        }
    }

    // Initialize voices
    loadVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }

    function processQueue() {
        if (!window.speechSynthesis.speaking && isSpeaking) isSpeaking = false;
        if (isSpeaking || speechQueue.length === 0) return;

        isSpeaking = true;
        const currentItem = speechQueue.shift();

        if (window.speechSynthesis.paused) window.speechSynthesis.resume();

        const utterance = new SpeechSynthesisUtterance(currentItem.text);
        
        // --- CRITICAL ANDROID FIX ---
        // Android requires the .lang property to be set explicitly.
        // If we don't set this, it ignores the voice selection and uses the system default.
        if (currentItem.lang) {
            utterance.lang = currentItem.lang;
        }

        // --- SMART VOICE SELECTION ---
        let selectedVoice = preferredVoice;

        // Try to find a voice that matches the requested language
        if (currentItem.lang && !currentItem.lang.startsWith('en')) {
            // 1. Exact Match (e.g., 'es-MX')
            let foreignVoice = voiceList.find(v => v.lang === currentItem.lang);
            
            // 2. Loose Match (e.g., 'es')
            if (!foreignVoice) {
                foreignVoice = voiceList.find(v => v.lang.startsWith(currentItem.lang));
            }

            if (foreignVoice) {
                selectedVoice = foreignVoice;
            }
        }

        if (selectedVoice) utterance.voice = selectedVoice;

        // --- BROWSER TWEAKS ---
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        utterance.rate = isIOS ? 1.0 : 1.0;
        utterance.pitch = isIOS ? 1.1 : 1.0;

        // --- EVENT HANDLERS ---
        
        // 1. Watchdog
        utterance.onstart = function() {
            clearTimeout(watchdogTimer);
            watchdogTimer = setTimeout(() => {
                console.warn("TTS Watchdog: Speech timed out. Resetting.");
                window.speechSynthesis.cancel();
                isSpeaking = false;
                activeUtterance = null;
                processQueue();
            }, 8000); 
        };

        // 2. Success
        utterance.onend = function() {
            clearTimeout(watchdogTimer);
            isSpeaking = false;
            activeUtterance = null;
            if (currentItem.callback) currentItem.callback();
            setTimeout(processQueue, 250); 
        };

        // 3. Error
        utterance.onerror = function(e) {
            clearTimeout(watchdogTimer);
            console.warn("TTS Error:", e);
            window.speechSynthesis.cancel();
            isSpeaking = false;
            activeUtterance = null;
            setTimeout(processQueue, 250);
        };

        activeUtterance = utterance;
        window.speechSynthesis.speak(utterance);
    }

    // --- PUBLIC API ---
    window.speakText = function(text, arg2, arg3) {
        // Ensure we try to load voices if empty (Android cold start)
        if (voiceList.length === 0) loadVoices();

        let lang = 'en'; // Default to English
        let callback = null;

        if (typeof arg2 === 'string') {
            lang = arg2;
            callback = arg3;
        } else if (typeof arg2 === 'function') {
            callback = arg2;
        }

        speechQueue.push({ text: text, lang: lang, callback: callback });
        processQueue();
    };
	window.stopSpeech = function() {
        // 1. Cancel the browser's current speech
        if (window.speechSynthesis) window.speechSynthesis.cancel();
        
        // 2. Clear the queue so it doesn't say the next thing
        speechQueue = []; 
        isSpeaking = false;
        activeUtterance = null;
        clearTimeout(watchdogTimer);
    };

    window.unlockAudio = function() {
        if (hasUserInteracted) return;
        hasUserInteracted = true;
        if (window.speechSynthesis) window.speechSynthesis.resume();
    };

    window.addEventListener('beforeunload', () => {
        if (window.speechSynthesis) window.speechSynthesis.cancel();
    });

    document.addEventListener('click', window.unlockAudio, { once: true });
    document.addEventListener('touchstart', window.unlockAudio, { once: true });
})();