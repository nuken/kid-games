/* assets/js/game-bridge.js */

// ==========================================
// 1. ZzFX Micro Library (Embedded)
// ==========================================
// This allows ZzFX to work in all games without editing every view.php
let zzfxV=.3; // Volume
let zzfxX=new (window.AudioContext||webkitAudioContext); // Audio Context
let zzfx=(p,k=.05,b=220,e=0,r=0,t=.1,q=0,D=1,u=0,y=0,v=0,z=0,l=0,E=0,A=0,F=0,c=0,w=1,m=0,B=0,N=0)=>{let M=Math,d=2*M.PI,R=44100,G=u*=500*d/R/R,C=b*=(1-k+2*k*M.random(k=[]))*d/R,g=0,H=0,a=0,n=1,I=0,J=0,f=0,h=N<0?-1:1,x=d*h*N*2/R,L=M.cos(x),Z=M.sin,K=Z(x)/4,O=1+K,X=-2*L/O,Y=(1-K)/O,P=(1+h*L)/2/O,Q=-(h+L)/O,S=P,T=0,U=0,V=0,W=0;e=R*e+9;m*=R;r*=R;t*=R;c*=R;y*=500*d/R**3;A*=d/R;v*=d/R;z*=R;l=R*l|0;p*=zzfxV;for(h=e+m+r+t+c|0;a<h;k[a++]=f*p)++J%(100*F|0)||(f=q?1<q?2<q?3<q?4<q?(g/d%1<D/2)*2-1:Z(g**3):M.max(M.min(M.tan(g),1),-1):1-(2*g/d%2+2)%2:1-4*M.abs(M.round(g/d)-g/d):Z(g),f=(l?1-B+B*Z(d*a/l):1)*(4<q?s:(f<0?-1:1)*M.abs(f)**D)*(a<e?a/e:a<e+m?1-(a-e)/m*(1-w):a<e+m+r?w:a<h-c?(h-a-c)/t*w:0),f=c?f/2+(c>a?0:(a<h-c?1:(h-a)/c)*k[a-c|0]/2/p):f,N?f=W=S*T+Q*(T=U)+P*(U=f)-Y*V-X*(V=W):0),x=(b+=u+=y)*M.cos(A*H++),g+=x+x*E*Z(a**5),n&&++n>z&&(b+=v,C+=v,n=0),!l||++I%l||(b=C,u=G,n=n||1);X=zzfxX,p=X.createBuffer(1,h,R);p.getChannelData(0).set(k);b=X.createBufferSource();b.buffer=p;b.connect(X.destination);b.start();return b};
// ==========================================

window.GameBridge = (function() {
    const API_PATH = 'api/save_score.php';
    let currentStreak = 0;

    // Audio Buffer Variables
    let pendingStreakMessage = null;
    let pendingStreakTimer = null;

    // --- ZzFX SOUND DEFINITIONS ---
    const sounds = {
        // "Ding" - High pitch sine wave with sustain
        correct: [2,,498,.02,.01,.06,1,1.3,15,-28,,,,,,,,.74,,,688],

        // "Buzz" - Low pitch saw/noise with dissonance
        wrong:   [1.9,,150,.05,.2,.2,,3.1,-5,,-50,,-0.1,,,,,,,,1]
    };

    /**
     * CONFETTI LOGIC
     */
    window.playConfettiEffect = function() {
        if (window.gameConfig && window.gameConfig.confetti === false) {
            console.log("Confetti disabled.");
            return;
        }

        let theme = 'default';
        if (window.gameConfig && window.gameConfig.themePath) {
            if (window.gameConfig.themePath.includes('princess')) theme = 'princess';
            else if (window.gameConfig.themePath.includes('space')) theme = 'space';
        }

        let emojis = [];
        if (theme === 'princess') emojis = ['👑', '💖', '✨', '🌸', '🦄', '🏰'];
        else if (theme === 'space') emojis = ['⭐', '🌟', '🚀', '🪐', '☄️', '🌑'];
        else emojis = ['🎊', '🎉', '🎈', '🟦', '🔴', '🔺', '✨'];

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

            const startX = Math.random() * 100;
            const startRotation = Math.random() * 360;

            Object.assign(el.style, {
                position: 'absolute',
                left: startX + 'vw',
                top: '-10vh',
                fontSize: (Math.random() * 20 + 20) + 'px',
                transform: `translate3d(0, 0, 0) rotate(${startRotation}deg)`,
                willChange: 'transform'
            });

            const duration = Math.random() * 2 + 3;
            el.style.transition = `transform ${duration}s ease-in`;

            container.appendChild(el);

            requestAnimationFrame(() => {
                setTimeout(() => {
                    const endRotation = startRotation + (Math.random() * 360 + 360);
                    el.style.transform = `translate3d(0, 120vh, 0) rotate(${endRotation}deg)`;
                }, 50);
            });
        }

        setTimeout(() => {
            if(document.body.contains(container)) document.body.removeChild(container);
        }, 6000);
    };

    /**
     * BADGE MODAL LOGIC
     */
   function showBadgeModal(badges, onCloseCallback) {
        const styleId = 'badge-modal-style';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.innerHTML = `
                .badge-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 10000;
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

        let badgeHtml = '';
        badges.forEach(b => {
            badgeHtml += `
                <div class="badge-icon-lg flash-reveal">${b.icon}</div>
                <div class="badge-title flash-reveal">${b.name}</div>
                <div class="badge-desc">Mission Complete!</div>
            `;
        });

        const giftHtml = `
            <div id="gift-box-trigger" class="gift-box">🎁</div>
            <div id="gift-text" class="badge-title" style="color:#e74c3c;">You found a gift!</div>
            <div class="badge-desc">Tap to open!</div>
        `;

        const overlay = document.createElement('div');
        overlay.className = 'badge-overlay';
        overlay.innerHTML = `<div class="badge-card" id="badge-card-container">${giftHtml}</div>`;

        document.body.appendChild(overlay);

        let autoOpenTimer = null;
        const startGiftTimer = () => {
            if (autoOpenTimer) return;
            autoOpenTimer = setTimeout(() => {
                const t = document.getElementById('gift-box-trigger');
                if (t) t.click();
            }, 5000);
        };

        overlay.startGiftTimer = startGiftTimer;

        if (document.getElementById('video-reward-overlay')) {
            console.log("Video is playing. Pausing badge timer.");
        } else {
            startGiftTimer();
        }

        const trigger = document.getElementById('gift-box-trigger');
        if(trigger) {
            trigger.onclick = function() {
                if(autoOpenTimer) clearTimeout(autoOpenTimer);

                // --- ZzFX UPDATE START ---
                if (typeof zzfx !== 'undefined' && sounds.correct) {
                    zzfx(...sounds.correct);
                }
                // --- ZzFX UPDATE END ---

                const container = document.getElementById('badge-card-container');
                container.innerHTML = badgeHtml + `<button id="badge-close-btn" class="badge-btn">Awesome!</button>`;

                if(window.playConfettiEffect) window.playConfettiEffect();

                let autoCloseTimer = setTimeout(() => {
                    const c = document.getElementById('badge-close-btn');
                    if (c) c.click();
                }, 5000);

                document.getElementById('badge-close-btn').onclick = function() {
                    clearTimeout(autoCloseTimer);
                    if (onCloseCallback) {
                        document.body.removeChild(overlay);
                        onCloseCallback();
                    } else {
                        window.location.href = 'index.php';
                    }
                };
            };
        }
    }

    return {
        init: function() {
             // Initialized via embedded code
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

        handleCorrect: function() {
            currentStreak++;
            this.updateStreakVisuals();
            this.playAudio('correct');
        },

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
                    pendingStreakMessage = "You are on fire!";

                    if(pendingStreakTimer) clearTimeout(pendingStreakTimer);
                    pendingStreakTimer = setTimeout(() => {
                        if(pendingStreakMessage && window.speakText) {
                            window.speakText(pendingStreakMessage);
                            pendingStreakMessage = null;
                        }
                    }, 500);
                }
            } else {
                body.classList.remove('on-fire');
            }
        },

        updateScore: function(val) {
            const el = document.getElementById('score-display');
            if(el) el.innerText = val;
        },

        playAudio: function(key) {
            // --- ZzFX UPDATE START ---
            if (sounds[key] && typeof zzfx !== 'undefined') {
                // Ensure audio context is running (fixes mobile silence)
                if (typeof zzfxX !== 'undefined' && zzfxX.state === 'suspended') {
                    zzfxX.resume();
                }
                zzfx(...sounds[key]);
            }
            // --- ZzFX UPDATE END ---
        },

        stopSpeech: function() {
             if (window.stopSpeech) window.stopSpeech();
        },

        speak: function(text, arg2, arg3) {
            if (pendingStreakMessage) {
                text = pendingStreakMessage + ". " + text;
                pendingStreakMessage = null;
                if(pendingStreakTimer) clearTimeout(pendingStreakTimer);
            }
            if (window.speakText) window.speakText(text, arg2, arg3);
        },

		speakNow: function(text, arg2, arg3) {
            if (pendingStreakMessage) {
                text = pendingStreakMessage + ". " + text;
                pendingStreakMessage = null;
                if(pendingStreakTimer) clearTimeout(pendingStreakTimer);
            }
            if (window.speakNow) window.speakNow(text, arg2, arg3);
        },

        celebrate: function(text, videoUrl) {
            this.playAudio('correct');
            if (window.playConfettiEffect) window.playConfettiEffect();
            if (text) this.speak(text);

            if (videoUrl) {
                const videoOverlay = document.createElement('div');
                videoOverlay.id = 'video-reward-overlay';
                Object.assign(videoOverlay.style, {
                    position: 'fixed', top: '0', left: '0', width: '100%', height: '100%',
                    backgroundColor: 'rgba(0,0,0,0.9)', zIndex: '10001',
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

                const closeVideoAndProceed = () => {
                     if(!document.body.contains(videoOverlay)) return;
                     document.body.removeChild(videoOverlay);

                     const badgeOverlay = document.querySelector('.badge-overlay');
                     if (badgeOverlay && badgeOverlay.startGiftTimer) {
                         badgeOverlay.startGiftTimer();
                     } else {
                         window.location.href = "index.php";
                     }
                };

                const vid = document.getElementById('reward-video');
                if (vid) {
                    vid.onended = function() { setTimeout(closeVideoAndProceed, 2000); };
                }
                document.getElementById('close-video').onclick = closeVideoAndProceed;
            }
        },

        // --- UPDATED SAVE SCORE WITH CSRF ---
        saveScore: function(data) {
            if (!window.gameConfig) return;

            const payload = {
                user_id: window.gameConfig.userId,
                game_id: window.gameConfig.gameId,
                csrf_token: window.gameConfig.csrfToken, // CSRF ADDED HERE
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
                if (result.status === 'success' && result.new_badges && result.new_badges.length > 0) {
                    const callback = data.noRedirect ? () => {} : null;
                    showBadgeModal(result.new_badges, callback);
                } else {
                    if (!document.getElementById('video-reward-overlay') && !data.noRedirect) {
                        setTimeout(() => window.location.href = "index.php", 2000);
                    }
                }
            })
            .catch(error => console.error("Save Error:", error));
        }
    };
})();
