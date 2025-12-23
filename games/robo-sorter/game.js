/* games/robo-sorter/game.js */
let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
let currentNumber = 0;
let score = 0;
let difficulty = 1;

// NEW: Track mistakes
let sessionMistakes = 0;

const box = document.getElementById('number-box');
const visualHint = document.getElementById('visual-hint');
const message = document.getElementById('message');

document.addEventListener('DOMContentLoaded', () => {
    if(!box) return;

    GameBridge.setupGame({
        instructions: window.LANG.game_robo_sorter_instr_text,
        speakInstruction: window.LANG.game_robo_sorter_instr_speak,
        levels: [
            { id: 1, label: window.LANG.game_robo_sorter_level1 },
            { id: 2, label: window.LANG.game_robo_sorter_level2 }
        ],
        onStart: (level) => {
            difficulty = level;
            score = 0;
            questionsAnswered = 0;
            sessionMistakes = 0; // Reset
            startTime = Date.now();
            spawnNumber(false);
        }
    });
});

function spawnNumber(silent) {
    box.style.transition = 'none';
    box.style.top = '110px';
    message.style.display = 'none';

    let max = (questionsAnswered >= 5) ? 99 : 20;
    currentNumber = Math.floor(Math.random() * max) + 1;
    box.innerText = currentNumber;

    showHint();

    if (!silent) GameBridge.speakNow(currentNumber.toString());

    // Trigger Reflow
    void box.offsetWidth;

    // Start Animation
    box.style.transition = 'top 10s linear';
    box.style.top = '85%';
}

function showHint() {
    visualHint.innerHTML = '';
    if (difficulty === 2) return;

    for (let i = 0; i < currentNumber; i++) {
        let dot = document.createElement('div');
        dot.className = 'dot';
        if (i === currentNumber - 1 && currentNumber % 2 !== 0) {
            dot.classList.add('lonely');
        }
        visualHint.appendChild(dot);
    }
}

function checkAnswer(type) {
    let isEven = (currentNumber % 2 === 0);
    let isCorrect = (type === 'even' && isEven) || (type === 'odd' && !isEven);

    if (isCorrect) {
		GameBridge.handleCorrect();
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);

        message.innerText = window.LANG.correct_short;
        message.style.color = "var(--primary-btn)";
        message.style.display = "block";
        GameBridge.celebrate(window.LANG.correct_short);

        if (questionsAnswered >= totalQuestions) {
            GameBridge.saveScore({
                score: score,
                duration: Math.floor((Date.now() - startTime)/1000),
                mistakes: sessionMistakes
            });
        } else {
            setTimeout(() => spawnNumber(false), 1500);
        }
    } else {
        sessionMistakes++; // Track mistake
        GameBridge.handleWrong();
        message.innerText = window.LANG.try_again;
        message.style.color = "var(--danger-btn)";
        message.style.display = "block";

        // Pause box
        let currentTop = getComputedStyle(box).top;
        box.style.transition = 'none';
        box.style.top = currentTop;

        GameBridge.speakNow(window.LANG.oops + " " + window.LANG.try_again);
    }
}

function explainRules() {
    GameBridge.speakNow(window.LANG.game_robo_sorter_rule_explain);
}
