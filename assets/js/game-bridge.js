/* assets/js/game-bridge.js */
window.GameBridge = (function() {
    const API_PATH = 'api/save_score.php';

    /**
     * CONFETTI LOGIC
     */
    window.playConfettiEffect = function() {
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
            Object.assign(el.style, {
                position: 'absolute',
                left: Math.random() * 100 + 'vw',
                top: '-10vh',
                fontSize: (Math.random() * 20 + 20) + 'px',
                transform: `rotate(${Math.random() * 360}deg)`
            });

            const duration = Math.random() * 2 + 3;
            el.style.transition = `top ${duration}s ease-in, transform ${duration}s linear`;

            container.appendChild(el);

            setTimeout(() => {
                el.style.top = '110vh';
                el.style.transform = `rotate(${Math.random() * 360 + 360}deg)`;
            }, 100);
        }

        setTimeout(() => {
            if(document.body.contains(container)) {
                document.body.removeChild(container);
            }
        }, 6000);
    };

    return {
        init: function() {
            console.log("System: GameBridge Online");
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
                            // Auto-speak instructions if provided
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

        celebrate: function(text) {
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
                mistakes: data.mistakes || 0  // Modified to pass mistakes
            };

            fetch(API_PATH, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success' && result.new_badges && result.new_badges.length > 0) {
                    let badgeMsg = result.new_badges.map(b => b.icon + " " + b.name).join("\n");
                    // FIX: Alert blocks execution. Redirect immediately after OK is clicked.
                    alert("🌟 MISSION PATCH EARNED! 🌟\n\n" + badgeMsg);
                    window.location.href = "index.php";
                } else {
                    // FIX: If no alert, allow short delay for celebration effect, then redirect
                    setTimeout(() => window.location.href = "index.php", 2000);
                }
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();
