/* games/cosmic-calc/game.js */
(function() {
    let score = 0;
    let mistakes = 0;
    let questionCount = 0;
    let currentLevel = 1;
    let startTime = Date.now();
    let problemDeck = [];

    // 2nd Grade: 2s, 5s, 10s
    // 3rd Grade: 3s, 4s, 6s
    // Master: 7s, 8s, 9s, 11s, 12s
    const LEVELS = {
        1: [2, 5, 10],
        2: [3, 4, 6],
        3: [7, 8, 9, 11, 12]
    };

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Solve the math problems to launch the rocket!",
            levels: [
                { id: 1, label: "Starters (2s, 5s, 10s)" },
                { id: 2, label: "Pro (3s, 4s, 6s)" },
                { id: 3, label: "Master (7s, 8s, 9s)" }
            ],
            onStart: (lvl) => {
                currentLevel = lvl;
                buildMathDeck(); // Build the deck for this level
                startGame();
            }
        });
    });

    // --- MATH DECK LOGIC ---
    function buildMathDeck() {
        problemDeck = [];
        const factors = LEVELS[currentLevel];

        // Generate every combination of (Factor x 2..10)
        factors.forEach(numA => {
            // We multiply by 2 through 10 (or 12 for masters)
            let maxMult = (currentLevel === 3) ? 12 : 10;

            for(let i=2; i<=maxMult; i++) {
                problemDeck.push({ a: numA, b: i });
            }
        });

        // Shuffle
        for (let i = problemDeck.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [problemDeck[i], problemDeck[j]] = [problemDeck[j], problemDeck[i]];
        }
    }

    function drawProblem() {
        if (problemDeck.length === 0) {
            buildMathDeck(); // Refill if they play a VERY long game
        }
        return problemDeck.pop();
    }
    // ----------------------

    function startGame() {
        score = 0;
        mistakes = 0;
        questionCount = 0;
        startTime = Date.now();
        nextQuestion();
    }

    function nextQuestion() {
        // 1. Win Condition: 10 Questions
        if (questionCount >= 10) {
            finishGame();
            return;
        }

        // 2. Draw from Deck
        const p = drawProblem();
        const numA = p.a;
        const numB = p.b;
        const answer = numA * numB;

        // 3. Display
        const qText = `${numA} x ${numB} = ?`;
        document.getElementById('question-display').innerText = qText;

        // --- NEW: Visual Hint ---
        drawVisualHint(numA, numB);
        // ------------------------

        // Speak using API
        GameBridge.speakNow(`What is ${numA} times ${numB}?`);

        // 4. Generate Options (1 Correct + 2 Wrong)
        let options = [answer];
        while (options.length < 3) {
            // Smart distractors: Answer +/- the factor (e.g. 5x5=25, fake=20 or 30)
            // Or completely random close number
            let coinFlip = Math.random() > 0.5;
            let fake;

            if (coinFlip) {
                // Off by one multiple (e.g. 5x4 or 5x6)
                fake = answer + (Math.random() > 0.5 ? numA : -numA);
            } else {
                // Randomly close number
                fake = answer + Math.floor(Math.random() * 10) - 5;
            }

            if (fake > 0 && !options.includes(fake)) {
                options.push(fake);
            }
        }

        // Shuffle Options
        options.sort(() => Math.random() - 0.5);

        // Render Buttons
        const grid = document.getElementById('options-grid');
        grid.innerHTML = '';
        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'math-btn';
            btn.innerText = opt;
            btn.onclick = () => handleAnswer(opt, answer);
            grid.appendChild(btn);
        });
    }

    function handleAnswer(selected, correct) {
        if (selected === correct) {
            GameBridge.handleCorrect();
            score += 10;
            questionCount++;
            nextQuestion();
        } else {
            GameBridge.handleWrong();
            mistakes++;
        }
    }

    function finishGame() {
        const rocket = document.getElementById('rocket');
        if(rocket) rocket.classList.add('blasting-off');

        GameBridge.celebrate("Mission Accomplished! Blast off!");

        setTimeout(() => {
            GameBridge.saveScore({
                score: 100,
                duration: Math.floor((Date.now() - startTime) / 1000),
                mistakes: mistakes
            });
        }, 1500);
    }

    // --- NEW HELPER FUNCTION: Draws the visual star array ---
    function drawVisualHint(rows, cols) {
        const container = document.getElementById('visual-hint-container');
        if (!container) return;

        container.innerHTML = '';

        // We want 'rows' number of rows
        for(let r = 0; r < rows; r++) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'calc-row';

            // We want 'cols' number of items in each row
            for(let c = 0; c < cols; c++) {
                const star = document.createElement('span');
                star.className = 'calc-star';
                star.innerText = '⭐';

                // Add staggered animation
                star.style.animation = `popIn 0.3s ease-out ${ (r*cols + c) * 0.05 }s backwards`;

                rowDiv.appendChild(star);
            }
            container.appendChild(rowDiv);
        }
    }

})();
