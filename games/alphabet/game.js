/* games/alphabet/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    let difficulty = 1;
    let startTime = Date.now();

    // State
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    let lettersToFind = [];
    let currentTarget = null;
    let caseMode = 'upper';
    let speechMode = 'letter';
    let sessionMistakes = 0;
    
    // Timer for Level 1
    let freePlayTimer = null;
    let hasWonFreePlay = false;
    const SECONDS_TO_WIN = 60;

    const EXAMPLE_WORDS = {
        'A': 'Apple', 'B': 'Boy', 'C': 'Cat', 'D': 'Dog', 'E': 'Egg',
        'F': 'Fish', 'G': 'Goat', 'H': 'Hat', 'I': 'Igloo', 'J': 'Jar',
        'K': 'Kite', 'L': 'Lion', 'M': 'Moon', 'N': 'Nest', 'O': 'Octopus',
        'P': 'Pig', 'Q': 'Queen', 'R': 'Ring', 'S': 'Sun', 'T': 'Turtle',
        'U': 'Umbrella', 'V': 'Volcano', 'W': 'Watch', 'X': 'X-ray', 'Y': 'Yo-yo', 'Z': 'Zebra'
    };

    document.addEventListener('DOMContentLoaded', () => {
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
                
                // Clear old timers
                if(freePlayTimer) clearInterval(freePlayTimer);
                hasWonFreePlay = false;

                renderGrid();

                const speechBtn = document.getElementById('speech-toggle-btn');
                const prompt = document.getElementById('alphabet-prompt');

                if (difficulty === 2) {
                    // --- LEVEL 2 ---
                    lettersToFind = [...ALPHABET];
                    prompt.style.display = 'block';
                    if(speechBtn) speechBtn.style.display = 'none';
                    pickNextTarget();
                } else {
                    // --- LEVEL 1 (Free Play) ---
                    prompt.style.display = 'none';
                    if(speechBtn) speechBtn.style.display = 'inline-block';
                    GameBridge.speak("Touch any letter.");
                    
                    // Timer for Quest Completion (1 minute)
                    freePlayTimer = setInterval(() => {
                        if (!hasWonFreePlay && (Date.now() - startTime > SECONDS_TO_WIN * 1000)) {
                            hasWonFreePlay = true;
                            clearInterval(freePlayTimer);
                            
                            GameBridge.saveScore({ 
                                score: 100, 
                                duration: SECONDS_TO_WIN, 
                                mistakes: 0,
                                noRedirect: true // <--- Stay in game!
                            });
                            
                            GameBridge.celebrate("Mission Complete! Keep exploring!");
                        }
                    }, 1000);
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
            score += 10;
            questionsAnswered++;
            highlight(btn);
            btn.classList.add('found');
            GameBridge.handleCorrect();
            lettersToFind = lettersToFind.filter(c => c !== char);

            if (lettersToFind.length === 0) {
                // WIN CONDITION (Standard redirect)
                GameBridge.saveScore({
                    score: 100,
                    duration: Math.floor((Date.now() - startTime) / 1000),
                    mistakes: sessionMistakes
                });
                GameBridge.celebrate("You found the whole alphabet!");
            } else {
                pickNextTarget();
            }
        } else {
            sessionMistakes++;
           GameBridge.handleWrong();
            btn.classList.add('shake');
            setTimeout(() => btn.classList.remove('shake'), 500);
        }
    }

    function pickNextTarget() {
        const idx = Math.floor(Math.random() * lettersToFind.length);
        currentTarget = lettersToFind[idx];
        const promptText = `Find ${currentTarget}`;
        document.getElementById('alphabet-prompt').innerText = promptText;
        GameBridge.speak(promptText);
    }

    function highlight(btn) {
        const colors = ['#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFF9C4', '#E1BEE7'];
        btn.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
    }

    function playLetterSound(char) {
		GameBridge.stopSpeech();
        let text = (caseMode === 'upper' ? char : char.toLowerCase());
        if (speechMode === 'word') {
            text += ". " + EXAMPLE_WORDS[char];
        }
        GameBridge.speak(text);
    }

    function toggleCase() {
        caseMode = (caseMode === 'upper') ? 'lower' : 'upper';
        document.getElementById('case-toggle-btn').innerText = (caseMode === 'upper') ? "a / A" : "A / a";
        renderGrid();
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