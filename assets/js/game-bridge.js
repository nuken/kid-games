/* assets/js/game-bridge.js */
window.GameBridge = (function() {
    const API_PATH = 'api/save_score.php';
    let currentStreak = 0; // NEW: Track streaks locally

    // --- Audio Objects ---
    const sounds = {
        correct: new Audio('assets/sounds/correct.mp3'),
        wrong:   new Audio('assets/sounds/wrong.mp3')
    };

    /**
     * CONFETTI LOGIC (Optimized for Apple Devices)
     * Uses translate3d to force GPU hardware acceleration.
     */
    window.playConfettiEffect = function() {
        // 1. CHECK USER PREFERENCE
        if (window.gameConfig && window.gameConfig.confetti === false) {
            console.log("Confetti disabled by parent.");
            return; // Stop here
        }

        let theme = 'default';
        if (window.gameConfig && window.gameConfig.themePath) {
            if (window.gameConfig.themePath.includes('princess')) theme = 'princess';
            else if (window.gameConfig.themePath.includes('space')) theme = 'space';
        } else {
            const links = document.querySelectorAll('link[rel="stylesheet"]');
            links.forEach(link => {
                if (link.href.includes('princess.css')) theme = 'princess';
                if (link.href.includes('space.css')) theme = 'space';
            });
        }

        let emojis = [];
        if (theme === 'princess') {
            emojis = ['👑', '💖', '✨', '🌸', '🦄', '🏰'];
        } else if (theme === 'space') {
            emojis = ['⭐', '🌟', '🚀', '🪐', '☄️', '🌑'];
        } else {
            emojis = ['🎊', '🎉', '🎈', '🟦', '🔴', '🔺', '✨'];
        }

        const container = document.createElement('div');
        Object.assign(container.style, {
            position: 'fixed', top: '0', left: '0',
            width: '100%', height: '100%',
            pointerEvents: 'none', zIndex: '9999'
        });
        document.body.appendChild(container);

        for(let i=0; i<50; i++) {
            const el = document.createElement('div');
            el.innerText = emojis[Math.floor(Math.random() * emojis.length)];
            
            // Random start position
            const startX = Math.random() * 100; // vw
            const startRotation = Math.random() * 360;
            
            Object.assign(el.style, {
                position: 'absolute',
                left: startX + 'vw',
                top: '-10vh', // Start slightly above screen
                fontSize: (Math.random() * 20 + 20) + 'px',
                // Initial State: No vertical movement yet, just rotation
                transform: `translate3d(0, 0, 0) rotate(${startRotation}deg)`,
                // Apple Fix: Hint to the browser to prepare for changes
                willChange: 'transform' 
            });

            const duration = Math.random() * 2 + 3;
            
            // We only animate 'transform' now, which handles BOTH position (fall) and rotation (tumble)
            // 'ease-in' makes it start slow and speed up (gravity effect)
            el.style.transition = `transform ${duration}s ease-in`;

            container.appendChild(el);

            // Trigger the animation in the next frame
            requestAnimationFrame(() => {
                // Force a tiny delay to ensure the browser registers the start state
                setTimeout(() => {
                    const endRotation = startRotation + (Math.random() * 360 + 360);
                    // Fall down 120vh (to ensure it clears the screen) while rotating
                    el.style.transform = `translate3d(0, 120vh, 0) rotate(${endRotation}deg)`;
                }, 50); 
            });
        }

        // Cleanup
        setTimeout(() => {
            if(document.body.contains(container)) {
                document.body.removeChild(container);
            }
        }, 6000);
    };

    /**
     * BADGE MODAL LOGIC (New Styling)
     * UPDATED: Accepts an optional callback to stay in the game instead of redirecting
     */
   function showBadgeModal(badges, onCloseCallback) {
        // 1. Create Styles (if missing)
        const styleId = 'badge-modal-style';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.innerHTML = `
                .badge-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 10000; /* Below Video (10001) */
                    display: flex; align-items: center; justify-content: center;
                    backdrop-filter: blur(5px);
                }
                .badge-card {
                    background: white; padding: 40px;
                    border-radius: 25px; text-align: center;
                    border: 6px solid #f1c40f;
                    box-shadow: 0 0 50px rgba(241, 196, 15, 0.6);
                    font-family: 'Comic Neue', sans-serif;
                    max-width: 90%; width: 320px;
                    animation: badgeBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
                }
                @keyframes badgeBounce {
                    0% { transform: scale(0); opacity: 0; }
                    60% { transform: scale(1.1); opacity: 1; }
                    100% { transform: scale(1); }
                }
                .badge-icon-lg { font-size: 100px; margin: 10px 0; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.2)); }
                .badge-title { color: #f1c40f; font-size: 28px; margin: 10px 0; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
                .badge-desc { color: #555; font-size: 18px; margin-bottom: 30px; font-weight: bold; }
                .badge-btn {
                    background: #2ecc71; color: white; border: none;
                    padding: 15px 40px; font-size: 20px; border-radius: 50px;
                    cursor: pointer; font-weight: bold;
                    box-shadow: 0 6px 0 #219150; transition: transform 0.1s;
                }
                .badge-btn:hover { transform: scale(1.05); background: #27ae60; }
            `;
            document.head.appendChild(style);
        }

        // 2. Prepare Data
        // The "Real" Badge Content (Hidden at first)
        let badgeHtml = '';
        badges.forEach(b => {
            badgeHtml += `
                <div class="badge-icon-lg flash-reveal">${b.icon}</div>
                <div class="badge-title flash-reveal">${b.name}</div>
                <div class="badge-desc">Mission Complete!</div>
            `;
        });

        // The "Gift Box" Content (Shown first)
        const giftHtml = `
            <div id="gift-box-trigger" class="gift-box">🎁</div>
            <div id="gift-text" class="badge-title" style="color:#e74c3c;">You found a gift!</div>
            <div class="badge-desc">Tap to open!</div>
        `;

        // 3. Render Overlay
        const overlay = document.createElement('div');
        overlay.className = 'badge-overlay';
        overlay.innerHTML = `
            <div class="badge-card" id="badge-card-container">
                ${giftHtml}
            </div>
        `;

        document.body.appendChild(overlay);

        // 4. Handle "Unboxing"
        const trigger = document.getElementById('gift-box-trigger');
        if(trigger) {
            trigger.onclick = function() {
                // Play Sound
                sounds.correct.play().catch(()=>{});
                
                // Swap Content
                const container = document.getElementById('badge-card-container');
                // Added ID to button for click handler
                container.innerHTML = badgeHtml + `<button id="badge-close-btn" class="badge-btn">Awesome!</button>`;
                
                // Fire Confetti
                if(window.playConfettiEffect) window.playConfettiEffect();

                // Handle Close Click
                document.getElementById('badge-close-btn').onclick = function() {
                    if (onCloseCallback) {
                        // User wants to stay in the game (Quest Logic)
                        document.body.removeChild(overlay);
                        onCloseCallback();
                    } else {
                        // Default behavior: Redirect to menu
                        window.location.href = 'index.php';
                    }
                };
            };
        }
    }

    return {
        init: function() {
            sounds.correct.load();
            sounds.wrong.load();
        },

        setupGame: function(config) {
            // (Keep existing setupGame logic...)
            const overlay = document.getElementById('system-overlay');
            const desc = document.getElementById('overlay-desc');
            const levels = document.getElementById('level-select');

            if(config.instructions && desc) desc.innerText = config.instructions;

            if(levels) {
                levels.innerHTML = '';
                if (config.levels) {
                    config.levels.forEach(lvl => {
                        const btn = document.createElement('button');
                        btn.className = `btn-level`;
                        btn.innerHTML = `${lvl.label}`;
                        btn.onclick = () => {
                            if(overlay) overlay.style.display = 'none';
                            if(window.speakText) window.speakText(config.speakInstruction || config.instructions);
                            config.onStart(lvl.id);
                        };
                        levels.appendChild(btn);
                    });
                }
            }
        },

        // --- NEW: STREAK LOGIC ---
        handleCorrect: function() {
            currentStreak++;
            this.updateStreakVisuals();
            this.playAudio('correct');
        },
        
        // --- NEW: STREAK LOGIC (SILENT) ---
        handleCorrectSilent: function() {
            currentStreak++;
            this.updateStreakVisuals();
        },

        handleWrong: function() {
            currentStreak = 0;
            this.updateStreakVisuals();
            this.playAudio('wrong');
        },

        updateStreakVisuals: function() {
            const body = document.body;
            if (currentStreak >= 3) {
                if (!body.classList.contains('on-fire')) {
                    body.classList.add('on-fire');
                    if(window.speakText) window.speakText("You are on fire!");
                }
            } else {
                body.classList.remove('on-fire');
            }
        },
        // -------------------------

        updateScore: function(val) {
            const el = document.getElementById('score-display');
            if(el) el.innerText = val;
        },

        playAudio: function(key) {
            if (sounds[key]) {
                const s = sounds[key].cloneNode();
                s.play().catch(e => {});
            }
        },
        
        stopSpeech: function() {
             if (window.stopSpeech) window.stopSpeech();
        },

        speak: function(text, arg2, arg3) {
            if (window.speakText) window.speakText(text, arg2, arg3);
        },
		
		speakNow: function(text, arg2, arg3) {
            if (window.speakNow) window.speakNow(text, arg2, arg3);
        },

        // --- UPDATED CELEBRATE (With Smart Redirect) ---
        celebrate: function(text, videoUrl) {
            // Audio/Visuals
            this.playAudio('correct');
            if (window.playConfettiEffect) window.playConfettiEffect();
            if (text && window.speakText) window.speakText(text);

            if (videoUrl) {
                const videoOverlay = document.createElement('div');
                videoOverlay.id = 'video-reward-overlay'; // ID for easy finding
                Object.assign(videoOverlay.style, {
                    position: 'fixed', top: '0', left: '0', width: '100%', height: '100%',
                    backgroundColor: 'rgba(0,0,0,0.9)', zIndex: '10001', // Higher than badge
                    display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center'
                });

                videoOverlay.innerHTML = `
                    <video id="reward-video" width="90%" style="max-width: 600px; aspect-ratio: 16/9; background: black; border-radius: 20px; border: 5px solid #f1c40f;" autoplay>
                        <source src="${videoUrl}" type="video/mp4">
                    </video>
                    <button id="close-video" style="margin-top: 20px; padding: 15px 40px; font-size: 20px; border-radius: 50px; background: #2ecc71; color: white; border: none; cursor: pointer; font-weight: bold;">
                        Great Job! ➡
                    </button>
                `;

                document.body.appendChild(videoOverlay);

                // FIX: "Smart" Close Button
                document.getElementById('close-video').onclick = () => {
                    document.body.removeChild(videoOverlay);
                    
                    // CHECK: Is a badge/gift waiting underneath?
                    // We look for the badge overlay class
                    const badgeWaiting = document.querySelector('.badge-overlay');
                    
                    if (!badgeWaiting) {
                        // No badge? Okay, go to menu
                        window.location.href = "index.php";
                    }
                    // If badgeWaiting is true, we do nothing. 
                    // The video disappears, revealing the Gift Box underneath!
                };
            }
        },

        saveScore: function(data) {
            if (!window.gameConfig) return;

            const payload = {
                user_id: window.gameConfig.userId,
                game_id: window.gameConfig.gameId,
                score: data.score,
                duration: data.duration,
                mistakes: data.mistakes || 0
            };

            fetch(API_PATH, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(result => {
                // CHECK IF BADGE WAS EARNED
                if (result.status === 'success' && result.new_badges && result.new_badges.length > 0) {
                    // SHOW MYSTERY GIFT
                    // If noRedirect is requested, we pass an empty callback () => {} to keep them in game
                    // Otherwise pass null, which defaults to redirect
                    const callback = data.noRedirect ? () => {} : null;
                    showBadgeModal(result.new_badges, callback);
                } else {
                    // NO BADGE
                    // Only redirect if:
                    // 1. NO video is playing
                    // 2. AND we are NOT specifically asked to stay in the game (noRedirect)
                    if (!document.getElementById('video-reward-overlay') && !data.noRedirect) {
                        setTimeout(() => window.location.href = "index.php", 2000);
                    }
                }
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();