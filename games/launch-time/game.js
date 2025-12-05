/* games/launch-time/game.js */
let currentHour = 12;
let currentMin = 0;
let targetHour = 0;
let targetMin = 0;
let score = 0;
let questionsAnswered = 0;
const totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize via Bridge
    GameBridge.setupGame({
        instructions: window.LANG.game_launch_time_instr_text,
        speakInstruction: window.LANG.game_launch_time_instr_speak,
        levels: [
            { id: 1, label: window.LANG.game_launch_time_level1 },
            { id: 2, label: window.LANG.game_launch_time_level2 }
        ],
        onStart: (level) => {
            difficulty = level;
            document.getElementById('fine-controls').style.display = (level === 2) ? 'flex' : 'none';
            startTime = Date.now();
            spawnTime(false);
        }
    });

    // 2. Generate Markers
    const face = document.getElementById('clock-face');
    for(let i=1; i<=12; i++) {
        let mk = document.createElement('div');
        mk.className = 'marker';
        mk.innerText = i;
        mk.style.transform = `translate(-50%, -50%) rotate(${i*30}deg) translate(0, -95px) rotate(-${i*30}deg)`;
        face.appendChild(mk);
    }
});

function spawnTime(silent) {
    document.getElementById('message').innerText = "";
    
    if (difficulty === 2) {
        targetHour = Math.floor(Math.random() * 12) + 1;
        targetMin = Math.floor(Math.random() * 60);
    } else {
        if (questionsAnswered < 3) {
            targetHour = Math.floor(Math.random() * 12) + 1;
            targetMin = 0;
        } else {
            targetHour = Math.floor(Math.random() * 12) + 1;
            targetMin = Math.floor(Math.random() * 12) * 5;
        }
    }

    let minString = targetMin < 10 ? "0" + targetMin : targetMin;
    document.getElementById('target-time').innerText = targetHour + ":" + minString;

    currentHour = 12; currentMin = 0;
    updateClockVisuals();

    if (!silent) {
        let timeText = (targetMin === 0) ? targetHour + " O'Clock" : targetHour + " " + targetMin;
        // Use lang variable for "Set clock to" if available, else construct it
        GameBridge.speak(window.LANG.game_launch_time_speak_set + " " + timeText); //
    }
}

function changeHour(amt) {
    currentHour += amt;
    if (currentHour > 12) currentHour = 1;
    if (currentHour < 1) currentHour = 12;
    updateClockVisuals();
    GameBridge.speak(currentHour + " O'Clock");
}

function changeMinute(amt) {
    currentMin += amt;
    if (currentMin >= 60) { currentMin = 0; changeHour(1); }
    else if (currentMin < 0) { currentMin = 55; changeHour(-1); }
    updateClockVisuals();
    GameBridge.speak(currentMin === 0 ? "O'Clock" : currentMin);
}

function updateClockVisuals() {
    let minDeg = currentMin * 6;
    let hourDeg = (currentHour * 30) + (currentMin * 0.5);
    document.getElementById('min-hand').style.transform = `translateX(-50%) rotate(${minDeg}deg)`;
    document.getElementById('hour-hand').style.transform = `translateX(-50%) rotate(${hourDeg}deg)`;
}

function checkTime() {
    if (currentHour === targetHour && currentMin === targetMin) {
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);
        GameBridge.celebrate(window.LANG.game_launch_time_success);
        
        if (questionsAnswered >= totalQuestions) {
            GameBridge.saveScore({ score: score, duration: Math.floor((Date.now() - startTime)/1000) });
        } else {
            setTimeout(() => spawnTime(false), 1500);
        }
    } else {
        document.getElementById('message').innerText = window.LANG.game_launch_time_check;
        GameBridge.speak(window.LANG.try_again);
    }
}