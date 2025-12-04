/* assets/js/game-bridge.js */
const GameBridge = (function() {
    const API_PATH = 'api/save_score.php'; 

    return {
        init: function() {
            console.log("System: GameBridge Online");
        },

        setupGame: function(config) {
            const overlay = document.getElementById('system-overlay');
            const desc = document.getElementById('overlay-desc');
            const levels = document.getElementById('level-select');
            
            // Get Language from Config (or default to English)
            const L = window.gameConfig && window.gameConfig.lang ? window.gameConfig.lang : {
                rank_easy: 'Cadet', rank_hard: 'Commander'
            };

            // Set Title/Desc from Lang file if available
            if(window.gameConfig.lang) {
                // You can add an ID to the H1 in play.php to target it if you want to change the title too
                if(desc) desc.innerText = window.gameConfig.lang.level_select_desc;
            }

            levels.innerHTML = '';
            if (config.levels) {
                config.levels.forEach(lvl => {
                    const btn = document.createElement('button');
                    btn.className = `btn-level`; 
                    
                    // SMART REPLACEMENT:
                    // If the game asks for "Cadet", give them the theme's "Easy Rank" (Page/Cadet)
                    // If the game asks for "Commander", give them "Hard Rank" (Royal/Commander)
                    let text = lvl.label;
                    text = text.replace('Cadet', L.rank_easy);
                    text = text.replace('Commander', L.rank_hard);
                    text = text.replace('Apprentice', L.rank_easy);
                    text = text.replace('Master', L.rank_hard);
                    
                    btn.innerHTML = text;
                    
                    btn.onclick = () => {
                        overlay.style.display = 'none';
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
                    
                    // Use Translated Alert Header
                    const alertTitle = window.gameConfig.lang ? window.gameConfig.lang.patch_earned : "MISSION PATCH EARNED!";
                    alert("🌟 " + alertTitle + " 🌟\n\n" + badgeMsg);
                }
                setTimeout(() => window.location.href = "index.php", 2000);
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();