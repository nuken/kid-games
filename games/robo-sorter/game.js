/* games/robo-sorter/game.js */

let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
const USER_ID = window.gameData ? window.gameData.userId : 1;
const GAME_ID = 1;

let currentNumber = 0;
let score = 0;
let difficulty = 1;
let combo = 0;

// DOM Elements
const box = document.getElementById('number-box');
const message = document.getElementById('message');
const comboText = document.getElementById('combo-text');
const visualHint = document.getElementById('visual-hint');

// --- 1. GAME INITIALIZATION ---
function initGame(level = 1) {
    difficulty = level;
    document.getElementById('start-overlay').style.display = 'none';

    if (window.voiceList && window.voiceList.length === 0) window.loadVoices();

    if (difficulty === 2) {
        window.speakText("Commander Mode. No hints allowed. Good luck!");
    } else {
        window.speakText("Welcome to Robo Sorter. Sort the numbers into Odd or Even bins.");
    }

    startTime = Date.now();

    // Reset game
    questionsAnswered = 0;
    score = 0;
    combo = 0;
    document.getElementById('score').innerText = score;
    spawnNumber(false);
}

// --- 2. SHOW HINT (Visual Dots) ---
function showHint() {
    visualHint.innerHTML = ''; // Clear previous dots

    // LEVEL 2: NO HINTS
    if (difficulty === 2) {
        visualHint.innerHTML = '<div style="color:white; font-size:20px; font-weight:bold; margin-top:10px;">[CLASSIFIED]</div>';
        return;
    }

    // LEVEL 1: SHOW DOTS
    for (let i = 0; i < currentNumber; i++) {
        let dot = document.createElement('div');
        dot.className = 'dot';
        // If it's the last dot and number is odd, make it red (lonely)
        if (i === currentNumber - 1 && currentNumber % 2 !== 0) {
            dot.classList.add('lonely');
        }
        visualHint.appendChild(dot);
    }
}

// --- 3. EXPLAIN RULES (Legend Click) ---
function explainRules() {
    const card = document.getElementById('legend-card');
    if (card) {
        card.style.transform = "scale(1.1)";
        setTimeout(() => card.style.transform = "scale(1)", 200);
    }
    window.speakText("Here is the rule. Even numbers always have a buddy. Odd numbers always have one lonely red dot.");
}

// --- 4. CHECK ANSWER ---
function checkAnswer(type) {
    let isEven = (currentNumber % 2 === 0);
    let isCorrect = false;

    if (type === 'even' && isEven) isCorrect = true;
    if (type === 'odd' && !isEven) isCorrect = true;

    if (isCorrect) {
        processWin();
    } else {
        processFail();
    }
}

// --- 5. MAIN GAME LOOP ---
function spawnNumber(isSilent = false) {
    // Reset Position & Animation
    box.style.transition = 'none';
    box.style.top = '20px';
    message.style.display = 'none';

    // Difficulty Logic
    let max = (questionsAnswered >= 5) ? 99 : 20;
    currentNumber = Math.floor(Math.random() * max) + 1;

    box.innerText = currentNumber;

    // Show dots IMMEDIATELY
    showHint();

    // TTS: Read number (Only if we are not in silent mode)
    if (!isSilent) {
        window.speakText(currentNumber.toString());
    }

    // Trigger Reflow
    void box.offsetWidth;

    // Start Moving Down
    box.style.transition = 'top 10s linear';
    box.style.top = '85%';
}

function processWin() {
    combo++; // Increase combo
    let points = 10 + (combo * 2); // Bonus points for combo
    score += points;
    questionsAnswered++;

    document.getElementById('score').innerText = score;
    message.innerText = "CORRECT!";
    message.style.color = "#2ecc71";
    message.style.display = "block";

    // Combo Feedback
    if (combo > 1) {
        comboText.innerText = `COMBO x${combo}! (+${points} pts)`;
        comboText.style.animation = "pulse 0.5s ease-in-out";
    } else {
        comboText.innerText = "";
    }

    const praise = ["Great job!", "You got it!", "Awesome!", "Correct!"];
    const randomPraise = praise[Math.floor(Math.random() * praise.length)];
    window.speakText(randomPraise);

    if (window.playConfettiEffect) window.playConfettiEffect();

    if (questionsAnswered >= totalQuestions) {
        endGame();
    } else {
        setTimeout(() => spawnNumber(false), 1500);
    }
}

function processFail() {
    combo = 0; // Reset combo
    comboText.innerText = "";

    message.innerText = "Try Again!";
    message.style.color = "#e74c3c";
    message.style.display = "block";

    // PAUSE the box
    let currentTop = getComputedStyle(box).top;
    box.style.transition = 'none';
    box.style.top = currentTop;

    if (currentNumber % 2 === 0) {
        window.speakText("Oops! Look closely at the dots. They all have a partner.");
    } else {
        window.speakText("Try again! Look for the red lonely dot.");
    }
}

function endGame() {
    document.getElementById('game-area').innerHTML = "<h1>MISSION COMPLETE!</h1><p>Saving your data...</p>";

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
            setTimeout(() => {
                window.location.href = "../../index.php";
            }, 2000);
        })
        .catch(error => console.error('Error:', error));
}

function playInstructions() {
    window.speakText("Welcome to Robo Sorter. Sort the numbers into Odd or Even bins.");
}

// Start SILENTLY on page load (Game is hidden behind overlay)
// Wait for user to select level
// spawnNumber(true);