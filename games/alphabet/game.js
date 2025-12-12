/* games/alphabet/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 26; // Full alphabet for badge
    let difficulty = 1;
    let startTime = Date.now();

    // State
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    let lettersToFind = [];
    let currentTarget = null;
    let caseMode = 'upper'; // 'upper' or 'lower'
    let speechMode = 'letter'; // 'letter' or 'word'
    let sessionMistakes = 0;

    const EXAMPLE_WORDS = {
        'A': 'Apple', 'B': 'Boy', 'C': 'Cat', 'D': 'Dog', 'E': 'Egg',
        'F': 'Fish', 'G': 'Goat', 'H': 'Hat', 'I': 'Igloo', 'J': 'Jar',
        'K': 'Kite', 'L': 'Lion', 'M': 'Moon', 'N': 'Nest', 'O': 'Octopus',
        'P': 'Pig', 'Q': 'Queen', 'R': 'Ring', 'S': 'Sun', 'T': 'Turtle',
        'U': 'Umbrella', 'V': 'Volcano', 'W': 'Watch', 'X': 'X-ray', 'Y': 'Yo-yo', 'Z': 'Zebra'
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Setup toggle buttons
        document.getElementById('case-toggle-btn').onclick = toggleCase;
        document.getElementById('speech-toggle-btn').onclick = toggleSpeech;

        GameBridge.setupGame({
            instructions: "Touch the letters to hear them!",
            levels: [
                { id: 1, label: "Explore (Free Play)" },
                { id: 2, label: "Challenge (Find It)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();

                renderGrid();

                if (difficulty === 2) {
                    lettersToFind = [...ALPHABET];
                    document.getElementById('alphabet-prompt').style.display = 'block';
                    pickNextTarget();
                } else {
                    document.getElementById('alphabet-prompt').style.display = 'none';
                    GameBridge.speak("Touch any letter.");
                }
            }
        });
    });

    function renderGrid() {
        const container = document.getElementById('alphabet-container');
        container.innerHTML = '';

        ALPHABET.forEach(char => {
            const btn = document.createElement('div');
            btn.className = 'letter-box';
            btn.dataset.char = char;
            btn.innerText = (caseMode === 'upper') ? char : char.toLowerCase();

            // Random color on init for fun look
            // btn.style.color = getRandomColor();

            btn.onclick = () => handleInput(char, btn);
            container.appendChild(btn);
        });
    }

    function handleInput(char, btn) {
        // --- LEVEL 1: EXPLORE ---
        if (difficulty === 1) {
            playLetterSound(char);
            highlight(btn);
            return;
        }

        // --- LEVEL 2: CHALLENGE ---
        if (char === currentTarget) {
            // Correct
            score += 10; // Simple scoring
            questionsAnswered++;

            highlight(btn);
            btn.classList.add('found');
            GameBridge.playAudio('correct');

            // Remove from pool
            lettersToFind = lettersToFind.filter(c => c !== char);

            if (lettersToFind.length === 0) {
                // WIN CONDITION
                GameBridge.saveScore({
                    score: score + 50, // Bonus
                    duration: Math.floor((Date.now() - startTime) / 1000),
                    mistakes: sessionMistakes
                });
                GameBridge.celebrate("You found the whole alphabet!");
            } else {
                pickNextTarget();
            }
        } else {
            // Wrong
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            btn.classList.add('shake');
            setTimeout(() => btn.classList.remove('shake'), 500);
        }
    }

    function pickNextTarget() {
        // Pick random from remaining
        const idx = Math.floor(Math.random() * lettersToFind.length);
        currentTarget = lettersToFind[idx];

        const promptText = `Find ${currentTarget}`;
        document.getElementById('alphabet-prompt').innerText = promptText;
        GameBridge.speak(promptText);
    }

    function highlight(btn) {
        // Visual feedback
        const colors = ['#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFF9C4', '#E1BEE7'];
        btn.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
    }

    function playLetterSound(char) {
        let text = (caseMode === 'upper' ? char : char.toLowerCase());
        if (speechMode === 'word') {
            text += ". " + EXAMPLE_WORDS[char];
        }
        GameBridge.speak(text);
    }

    // Toggles
    function toggleCase() {
        caseMode = (caseMode === 'upper') ? 'lower' : 'upper';
        document.getElementById('case-toggle-btn').innerText = (caseMode === 'upper') ? "a / A" : "A / a";
        renderGrid(); // Re-render text

        // Re-apply 'found' state if in level 2
        if (difficulty === 2) {
            const all = document.querySelectorAll('.letter-box');
            all.forEach(b => {
                if (!lettersToFind.includes(b.dataset.char)) b.classList.add('found');
            });
        }
    }

    function toggleSpeech() {
        speechMode = (speechMode === 'letter') ? 'word' : 'letter';
        document.getElementById('speech-toggle-btn').innerText = (speechMode === 'letter') ? "Word / Letter" : "Letter / Word";
    }

    window.explainRules = function() {
        GameBridge.speak(difficulty === 1 ? "Touch letters to hear them." : "Listen and find the matching letter.");
    };
})();
