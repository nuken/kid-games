/* assets/js/game-bridge.js */
window.GameBridge = (function() {
    const API_PATH = 'api/save_score.php';

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
     */
    function showBadgeModal(badges) {
        // 1. Create Styles
        const styleId = 'badge-modal-style';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.innerHTML = `
                .badge-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.85); z-index: 10000;
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
                    /* Elastic Bounce Animation */
                    animation: badgeBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
                }
                @keyframes badgeBounce {
                    0% { transform: scale(0); opacity: 0; }
                    60% { transform: scale(1.1); opacity: 1; }
                    80% { transform: scale(0.95); }
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
                .badge-btn:active { transform: scale(0.95) translateY(6px); box-shadow: none; }
            `;
            document.head.appendChild(style);
        }

        // 2. Create Elements
        const overlay = document.createElement('div');
        overlay.className = 'badge-overlay';

        // Support multiple badges if earned at once
        let contentHtml = '';
        badges.forEach(b => {
            contentHtml += `
                <div class="badge-icon-lg">${b.icon}</div>
                <div class="badge-title">${b.name}</div>
                <div class="badge-desc">Mission Complete!</div>
            `;
        });

        overlay.innerHTML = `
            <div class="badge-card">
                ${contentHtml}
                <button class="badge-btn" onclick="window.location.href='index.php'">Awesome!</button>
            </div>
        `;

        document.body.appendChild(overlay);
        
        // Extra celebration effect
        window.playConfettiEffect();
    }

    return {
        init: function() {
            console.log("System: GameBridge Online");
            sounds.correct.load();
            sounds.wrong.load();
        },

        setupGame: function(config) {
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

        updateScore: function(val) {
            const el = document.getElementById('score-display');
            if(el) el.innerText = val;
        },

        playAudio: function(key) {
            if (sounds[key]) {
                sounds[key].currentTime = 0;
                sounds[key].play().catch(e => console.warn("Audio play blocked:", e));
            }
        },

        celebrate: function(text) {
            this.playAudio('correct');
            if (window.playConfettiEffect) window.playConfettiEffect();
            if (text && window.speakText) window.speakText(text);
        },

        speak: function(text) {
            if (window.speakText) window.speakText(text);
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
                // CHANGED: Use new Custom Modal instead of Alert
                if (result.status === 'success' && result.new_badges && result.new_badges.length > 0) {
                    showBadgeModal(result.new_badges);
                } else {
                    setTimeout(() => window.location.href = "index.php", 2000);
                }
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();