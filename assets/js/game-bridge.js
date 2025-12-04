/* assets/js/game-bridge.js */
const GameBridge = (function() {
    const API_PATH = 'api/save_score.php'; 

    return {
        init: function() {
            console.log("System: GameBridge Online");
        },

        /**
         * Standardizes Game Startup & Overlay
         */
        setupGame: function(config) {
            const overlay = document.getElementById('system-overlay');
            const desc = document.getElementById('overlay-desc');
            const levels = document.getElementById('level-select');

            if(config.instructions) desc.innerText = config.instructions;

            levels.innerHTML = '';
            if (config.levels) {
                config.levels.forEach(lvl => {
                    const btn = document.createElement('button');
                    btn.className = `btn-level`; 
                    btn.innerHTML = `${lvl.label}`;
                    btn.onclick = () => {
                        overlay.style.display = 'none';
                        // Auto-speak instructions if provided
                        if(window.speakText) window.speakText(config.speakInstruction || config.instructions);
                        config.onStart(lvl.id);
                    };
                    levels.appendChild(btn);
                });
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
            const payload = {
                user_id: window.gameConfig.userId,
                game_id: window.gameConfig.gameId,
                score: data.score,
                duration: data.duration
            };

            fetch(API_PATH, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success' && result.new_badges.length > 0) {
                    let badgeMsg = result.new_badges.map(b => b.icon + " " + b.name).join("\n");
                    alert("🌟 MISSION PATCH EARNED! 🌟\n\n" + badgeMsg);
                }
                setTimeout(() => window.location.href = "index.php", 2000);
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();