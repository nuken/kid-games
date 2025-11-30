/* games/launch-time/game.js */

let currentHour = 12;
let currentMin = 0;
let targetHour = 0;
let targetMin = 0;

let score = 0;
let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;

const USER_ID = window.gameData ? window.gameData.userId : 1;
const GAME_ID = 3; // Launch Time

const hourHand = document.getElementById('hour-hand');
const minHand = document.getElementById('min-hand');
const targetDisplay = document.getElementById('target-time');
const message = document.getElementById('message');

// --- 1. GAME INITIALIZATION ---
function initGame(level = 1) {
    difficulty = level;
    document.getElementById('start-overlay').style.display = 'none';
    if (window.voiceList && window.voiceList.length === 0) window.loadVoices();

    if (difficulty === 2) {
        document.getElementById('fine-controls').style.display = 'flex';
        window.speakText("Commander Mode. Precision required. Set the exact minute.");
    } else {
        document.getElementById('fine-controls').style.display = 'none';
        playInstructions();
    }

    startTime = Date.now();

    // Reset game
    questionsAnswered = 0;
    score = 0;
    document.getElementById('score').innerText = score;
    spawnTime(false);
}

function playInstructions() {
    window.speakText("Launch Director! Look at the orange box. Move the clock hands to match that time so we can blast off.");
}

// --- 2. MAIN GAME LOOP ---
function spawnTime(isSilent = false) {
    message.innerText = "";
    document.body.style.backgroundColor = "var(--space-blue)";

    // Difficulty Logic
    if (difficulty === 2) {
        // LEVEL 2: Random Minute (0-59)
        targetHour = Math.floor(Math.random() * 12) + 1;
        targetMin = Math.floor(Math.random() * 60);
    } else {
        // LEVEL 1: 5-Minute Intervals
        if (questionsAnswered < 3) {
            targetHour = Math.floor(Math.random() * 12) + 1;
            targetMin = 0;
        } else if (questionsAnswered < 6) {
            targetHour = Math.floor(Math.random() * 12) + 1;
            targetMin = Math.random() < 0.5 ? 0 : 30;
        } else {
            targetHour = Math.floor(Math.random() * 12) + 1;
            targetMin = Math.floor(Math.random() * 12) * 5;
        }
    }

    let minString = targetMin < 10 ? "0" + targetMin : targetMin;
    targetDisplay.innerText = targetHour + ":" + minString;

    currentHour = 12;
    currentMin = 0;
    updateClockVisuals();

    if (!isSilent) {
        speakTargetTime();
    }
}

function speakTargetTime() {
    let timeSpeak = "";
    if (targetMin === 0) {
        timeSpeak = targetHour + " O'Clock";
    } else if (targetMin < 10) {
        timeSpeak = targetHour + " oh " + targetMin;
    } else {
        timeSpeak = targetHour + " " + targetMin;
    }
    window.speakText("Set the clock to " + timeSpeak);
}

function changeHour(amount) {
    currentHour += amount;
    if (currentHour > 12) currentHour = 1;
    if (currentHour < 1) currentHour = 12;
    updateClockVisuals();
    window.speakText(currentHour + " O'Clock");
}

function changeMinute(amount) {
    currentMin += amount;
    if (currentMin >= 60) {
        currentMin = 0;
        changeHour(1);
    } else if (currentMin < 0) {
        currentMin = 55;
        changeHour(-1);
    }
    updateClockVisuals();

    let m = currentMin === 0 ? "O'Clock" : currentMin;
    window.speakText(m.toString());
}

function updateClockVisuals() {
    let minDeg = currentMin * 6;
    let hourDeg = (currentHour * 30) + (currentMin * 0.5);

    minHand.style.transform = `translateX(-50%) rotate(${minDeg}deg)`;
    hourHand.style.transform = `translateX(-50%) rotate(${hourDeg}deg)`;
}

function checkTime() {
    if (currentHour === targetHour && currentMin === targetMin) {
        processWin();
    } else {
        message.innerText = "Check your instruments!";
        message.style.color = "#e74c3c";

        document.getElementById('clock-face').style.transform = "translateX(10px)";
        setTimeout(() => document.getElementById('clock-face').style.transform = "translateX(0)", 100);

        window.speakText("Not quite. Check your hands.");
    }
}

function processWin() {
    score += 10;
    questionsAnswered++;
    document.getElementById('score').innerText = score;

    message.innerText = "LIFT OFF!";
    message.style.color = "#2ecc71";
    document.body.style.backgroundColor = "var(--nebula-green)";

    window.speakText("Blast off! Good job.");

    if (window.playConfettiEffect) window.playConfettiEffect();

    if (questionsAnswered >= totalQuestions) {
        endGame();
    } else {
        setTimeout(() => spawnTime(false), 1500);
    }
}

function endGame() {
    document.body.innerHTML = "<h1>ORBIT ACHIEVED!</h1><p>Saving Mission Data...</p>";

    let duration = Math.floor((Date.now() - startTime) / 1000);

    fetch('../../api/save_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            game_id: GAME_ID,
            score: score,
            duration: duration
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.new_badges && data.new_badges.length > 0) {
                let badgeNames = data.new_badges.map(b => b.icon + " " + b.name).join("\n");
                alert("🌟 MISSION PATCH EARNED! 🌟\n\n" + badgeNames);
            }
            setTimeout(() => window.location.href = "../../index.php", 2000);
        })
        .catch(error => console.error('Error:', error));
}

// Wait for user to select level
// spawnTime(true);