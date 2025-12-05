/* games/spider-web/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 5;
    let startTime = Date.now();
    let currentShape = null;

    // NEW: Track mistakes
    let sessionMistakes = 0;

    // --- SMART SHUFFLE DECK ---
    let availableShapes = [];

    const shapeData = [
        { name: 'square',   gap: 'games/spider-web/images/web-square-gap.png',   filled: 'games/spider-web/images/web-square-filled.png' },
        { name: 'circle',   gap: 'games/spider-web/images/web-circle-gap.png',   filled: 'games/spider-web/images/web-circle-filled.png' },
        { name: 'triangle', gap: 'games/spider-web/images/web-triangle-gap.png', filled: 'games/spider-web/images/web-triangle-filled.png' },
        { name: 'star',     gap: 'games/spider-web/images/web-star-gap.png',     filled: 'games/spider-web/images/web-star-filled.png' },
        { name: 'hexagon',  gap: 'games/spider-web/images/web-hexagon-gap.png',  filled: 'games/spider-web/images/web-hexagon-filled.png' }
    ];

    const allShapes = ['square', 'circle', 'triangle', 'star', 'hexagon'];

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof GameBridge !== 'undefined') {
            GameBridge.setupGame({
                instructions: window.LANG.game_spider_web_instr_text,
                speakInstruction: window.LANG.game_spider_web_instr_speak,
                levels: [
                    { id: 1, label: window.LANG.game_spider_web_level1 }
                ],
                onStart: (level) => {
                    score = 0;
                    questionsAnswered = 0;
                    sessionMistakes = 0; // Reset
                    startTime = Date.now();
                    GameBridge.updateScore(score);
                    availableShapes = [];
                    loadLevel();
                }
            });
        }
    });

    function loadLevel() {
        const messageEl = document.getElementById('message');
        const webDisplay = document.getElementById('spider-web-display');
        const container = document.getElementById('shape-choices-container');

        messageEl.innerText = "";
        container.innerHTML = "";
        container.style.pointerEvents = 'auto';

        // --- SMART SHUFFLE LOGIC ---
        if (availableShapes.length === 0) {
            availableShapes = [...shapeData];
        }

        const randIndex = Math.floor(Math.random() * availableShapes.length);
        currentShape = availableShapes[randIndex];
        availableShapes.splice(randIndex, 1);

        webDisplay.style.backgroundImage = `url('${currentShape.gap}')`;
        GameBridge.speak("Find the " + currentShape.name);

        let choices = [currentShape.name];
        while (choices.length < 3) {
            const randomShape = allShapes[Math.floor(Math.random() * allShapes.length)];
            if (!choices.includes(randomShape)) {
                choices.push(randomShape);
            }
        }

        choices.sort(() => Math.random() - 0.5);

        choices.forEach(shapeName => {
            const btn = document.createElement('div');
            btn.className = 'shape-btn';
            btn.innerHTML = `<img src="games/spider-web/images/shape-${shapeName}.png" alt="${shapeName}">`;
            btn.onclick = () => checkAnswer(shapeName, btn);
            container.appendChild(btn);
        });
    }

    function checkAnswer(selectedShape, btnElement) {
        const messageEl = document.getElementById('message');
        const webDisplay = document.getElementById('spider-web-display');
        const container = document.getElementById('shape-choices-container');

        if (selectedShape === currentShape.name) {
            score += 20;
            questionsAnswered++;
            GameBridge.updateScore(score);

            webDisplay.style.backgroundImage = `url('${currentShape.filled}')`;
            messageEl.innerText = window.LANG.correct_long;
            messageEl.style.color = "var(--primary-btn)";

            container.style.pointerEvents = 'none';
            btnElement.style.borderColor = "var(--primary-btn)";
            btnElement.style.transform = "scale(1.1)";

            GameBridge.celebrate(window.LANG.game_spider_web_found + " " + currentShape.name + "!");

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                GameBridge.saveScore({
                    score: score,
                    duration: Math.floor((Date.now() - startTime) / 1000),
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(loadLevel, 2000);
            }

        } else {
            sessionMistakes++; // Track mistake
            messageEl.innerText = window.LANG.try_again;
            messageEl.style.color = "var(--danger-btn)";
            btnElement.style.opacity = "0.5";
            GameBridge.speak(window.LANG.try_again);
        }
    }

    window.explainRules = function() {
        GameBridge.speak(window.LANG.game_spider_web_instr_speak);
    };
})();
