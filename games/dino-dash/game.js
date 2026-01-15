/* games/dino-dash/game.js */
(function() {
    // --- STATE ---
    let playerPos = 0;
    let cpuPos = 0;
    const GOAL = 90; // Finish line percentage
    const PLAYER_STEP = 10;
    const CPU_STEP = 4; // Dino is slower but steady
    
    let currentLevel = 1; // 1=Math, 2=Reading, 3=Science
    let cpuTimer = null;
    let isGameOver = false;
    let currentAnswer = null;

    // --- SMART DECKS (Prevents repeats) ---
    let readingDeck = [];
    let scienceDeck = [];
    
    // --- DATASETS ---
    const SCIENCE_QA = [
        { q: "Which planet is Red?", a: "Mars", bad: ["Earth", "Venus"] },
        { q: "What do bees make?", a: "Honey", bad: ["Milk", "Jam"] },
        { q: "What falls from clouds?", a: "Rain", bad: ["Dirt", "Sand"] },
        { q: "Is the sun hot or cold?", a: "Hot", bad: ["Cold", "Freezing"] },
        { q: "How many legs has a spider?", a: "8", bad: ["6", "4"] },
        { q: "What does a cow drink?", a: "Water", bad: ["Milk", "Juice"] },
        { q: "Which animal is the fastest?", a: "Cheetah", bad: ["Turtle", "Sloth"] },
        { q: "What do plants need?", a: "Sunlight", bad: ["Candy", "Pizza"] },
        { q: "What is ice made of?", a: "Water", bad: ["Air", "Glass"] }
    ];

    const READING_WORDS = ["Cat", "Dog", "Run", "Jump", "Play", "Ball", "Fish", "Book", "Tree", "Car", "Blue", "Red", "Stop", "Go", "Mom", "Dad"];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Answer fast to win the race! Don't let the Dino catch you!",
            levels: [
                { id: 1, label: "Math Race" },
                { id: 2, label: "Reading Dash" },
                { id: 3, label: "Science Sprint" }
            ],
            onStart: (level) => {
                currentLevel = level;
                startGame();
            }
        });
    });

    function startGame() {
        // Reset State
        playerPos = 2;
        cpuPos = 2;
        isGameOver = false;
        
        // Reset Decks
        readingDeck = [];
        scienceDeck = [];
        
        updateVisuals();
        
        // Start Dino AI (Moves every 3 seconds)
        if (cpuTimer) clearInterval(cpuTimer);
        cpuTimer = setInterval(() => {
            if(!isGameOver) moveCPU();
        }, 3000);

        nextQuestion();
    }

    // --- HELPER: Fisher-Yates Shuffle ---
    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    function nextQuestion() {
        if (isGameOver) return;

        const qDisplay = document.getElementById('question-display');
        const grid = document.getElementById('options-grid');
        grid.innerHTML = '';

        let questionText = "";
        let correctOption = "";
        let distractors = [];

        // --- SUBJECT LOGIC ---
        if (currentLevel === 1) { 
            // MATH (Dynamic Generation)
            const a = Math.floor(Math.random() * 10) + 1;
            const b = Math.floor(Math.random() * 10) + 1;
            const isPlus = Math.random() > 0.5;
            
            if (isPlus) {
                questionText = `${a} + ${b} = ?`;
                correctOption = a + b;
            } else {
                // Ensure positive result for subtraction
                const big = Math.max(a, b);
                const small = Math.min(a, b);
                questionText = `${big} - ${small} = ?`;
                correctOption = big - small;
            }
            // Math Distractors
            distractors = [correctOption + 1, correctOption - 1, correctOption + 2];
            GameBridge.speak(questionText.replace('-', 'minus').replace('+', 'plus'));

        } else if (currentLevel === 2) {
            // READING (Smart Deck)
            if (readingDeck.length === 0) {
                readingDeck = [...READING_WORDS];
                shuffleArray(readingDeck);
            }
            
            // Pop the unique word
            correctOption = readingDeck.pop();
            questionText = `Find the word: "${correctOption}"`;
            
            // Generate Random Distractors (These can repeat, that's fine)
            while(distractors.length < 2) {
                let w = READING_WORDS[Math.floor(Math.random() * READING_WORDS.length)];
                if(w !== correctOption && !distractors.includes(w)) distractors.push(w);
            }
            GameBridge.speak("Find " + correctOption);

        } else {
            // SCIENCE (Smart Deck)
            if (scienceDeck.length === 0) {
                scienceDeck = [...SCIENCE_QA];
                shuffleArray(scienceDeck);
            }
            
            const item = scienceDeck.pop();
            questionText = item.q;
            correctOption = item.a;
            distractors = [...item.bad];
            GameBridge.speak(item.q);
        }

        // Render Question
        qDisplay.innerText = questionText;
        currentAnswer = correctOption;

        // Render Options
        let options = [correctOption, ...distractors];
        // Clean duplicates if math gen created them
        options = [...new Set(options)]; 
        options.sort(() => Math.random() - 0.5); // Shuffle button positions

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'answer-btn';
            btn.innerText = opt;
            btn.onclick = () => checkAnswer(opt);
            grid.appendChild(btn);
        });
    }

    function checkAnswer(selected) {
        // 1. STOP SPEECH IMMEDIATELY to clear audio channel
        GameBridge.stopSpeech();

        if (isGameOver) return;

        if (selected == currentAnswer) {
            // CORRECT: Move Player
            GameBridge.handleCorrectSilent();
            playerPos += PLAYER_STEP;
            updateVisuals();
            checkWin();
            if (!isGameOver) nextQuestion();
        } else {
            // WRONG: Penalty!
            GameBridge.handleWrong();
            GameBridge.speak("Oh no! The Dino is gaining!");
            
            // DINO MOVES ON MISTAKE
            moveCPU(); 
            
            // Shake effect
            const dash = document.getElementById('dashboard');
            dash.style.backgroundColor = "#ffcdd2";
            setTimeout(() => dash.style.backgroundColor = "#fff", 300);
        }
    }

    function moveCPU() {
        if (isGameOver) return;
        cpuPos += CPU_STEP;
        // Random chance for Dino to "roar" (speed boost)
        if (Math.random() > 0.8) cpuPos += 5; 
        
        updateVisuals();
        checkWin();
    }

    function updateVisuals() {
        // Cap at 90% so they don't drive off screen
        const pLimit = Math.min(playerPos, 90);
        const cLimit = Math.min(cpuPos, 90);
        
        document.getElementById('player-car').style.left = pLimit + '%';
        document.getElementById('cpu-dino').style.left = cLimit + '%';
    }

    function checkWin() {
        if (playerPos >= GOAL) {
            gameOver(true);
        } else if (cpuPos >= GOAL) {
            gameOver(false);
        }
    }

    function gameOver(playerWon) {
        isGameOver = true;
        clearInterval(cpuTimer);
        
        const msg = document.getElementById('message-overlay');
        msg.style.display = 'block';

        if (playerWon) {
            msg.innerText = "🏆 YOU WON! 🏆";
            msg.style.color = "gold";
            GameBridge.celebrate("Amazing! You beat the Dino!");
            
            // Save Score
            GameBridge.saveScore({
                score: 100,
                duration: 60, // Arbitrary for race mode
                mistakes: 0
            });
        } else {
            msg.innerText = "🦖 DINO WINS!";
            msg.style.color = "#e74c3c";
            GameBridge.speak("The Dino was too fast! Try again.");
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Answer questions correctly to drive your car. If you miss one, the Dino moves faster!");
    };
})();