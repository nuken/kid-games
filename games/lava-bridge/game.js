/* games/lava-bridge/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const TOTAL_STONES = 6; // Number of stones to cross
    let currentStep = 0; // 0 = start bank, 1..6 = stones, 7 = goal
    let difficulty = 1;
    let currentAnswer = 0;
    let sessionMistakes = 0;
    let startTime = Date.now();

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Answer correctly to build a bridge over the lava!",
            levels: [
                { id: 1, label: "Counting (Pre-K)" },
                { id: 2, label: "Addition (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                currentStep = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                
                // Reset Visuals
                setupBridge();
                movePlayerTo(0); // Start Bank
                nextQuestion();
            }
        });
    });

    function setupBridge() {
        const container = document.getElementById('bridge-span');
        container.innerHTML = '';
        // Create empty slots for stones
        for (let i = 0; i < TOTAL_STONES; i++) {
            let slot = document.createElement('div');
            slot.className = 'bridge-slot';
            slot.id = 'slot-' + (i + 1);
            container.appendChild(slot);
        }
    }

    function nextQuestion() {
        const problemEl = document.getElementById('math-problem');
        const optsEl = document.getElementById('options-container');
        const msg = document.getElementById('message');
        
        optsEl.innerHTML = '';
        msg.innerText = '';

        // --- Generate Question ---
        if (difficulty === 1) {
            // Level 1: Number Recognition / Counting
            currentAnswer = Math.floor(Math.random() * 10) + 1;
            problemEl.innerText = "Find number " + currentAnswer;
            GameBridge.speak("Find number " + currentAnswer);
        } else {
            // Level 2: Addition
            let a = Math.floor(Math.random() * 10) + 1;
            let b = Math.floor(Math.random() * 10) + 1;
            currentAnswer = a + b;
            problemEl.innerText = `${a} + ${b} = ?`;
            GameBridge.speak(`${a} plus ${b}`);
        }

        // --- Generate Choices ---
        let choices = [currentAnswer];
        while (choices.length < 3) {
            let fake = currentAnswer + Math.floor(Math.random() * 5) - 2;
            if (fake > 0 && !choices.includes(fake)) choices.push(fake);
        }
        choices.sort(() => Math.random() - 0.5);

        // --- Render Buttons ---
        choices.forEach(num => {
            const btn = document.createElement('button');
            btn.className = 'answer-btn';
            btn.innerText = num;
            btn.onclick = () => checkAnswer(num, btn);
            optsEl.appendChild(btn);
        });
    }

    function checkAnswer(val, btn) {
        if (val === currentAnswer) {
            // Correct
            GameBridge.playAudio('correct');
            btn.style.background = "#2ecc71";
            btn.style.color = "white";
            
            // Build the next stone
            addStone();
        } else {
            // Wrong
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            btn.style.opacity = "0.5";
            btn.disabled = true;
            GameBridge.speak("Oops! The lava is hot!");
        }
    }

    function addStone() {
        currentStep++;
        
        // 1. Visually add the stone
        const slot = document.getElementById('slot-' + currentStep);
        if (slot) {
            const stone = document.createElement('div');
            stone.className = 'stone pop-in';
            slot.appendChild(stone);
        }

        // 2. Move Player
        movePlayerTo(currentStep);

        // 3. Check Win
        if (currentStep >= TOTAL_STONES) {
            // Final hop to safe bank
            setTimeout(() => {
                movePlayerTo(TOTAL_STONES + 1); // Goal Bank
                finishGame();
            }, 600);
        } else {
            // Next Question
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);
            setTimeout(nextQuestion, 1000);
        }
    }

    function movePlayerTo(stepIndex) {
        const player = document.getElementById('player-char');
        const river = document.getElementById('lava-river');
        
        // Calculate position percentage
        // 0 = Start (5%), 7 = Goal (95%)
        // Steps 1-6 are distributed in between
        let percent = 5; 
        
        if (stepIndex > 0 && stepIndex <= TOTAL_STONES) {
            // Distribute stones between 20% and 80%
            let stepSize = 60 / (TOTAL_STONES - 1);
            percent = 20 + ((stepIndex - 1) * stepSize);
        } else if (stepIndex > TOTAL_STONES) {
            percent = 95; // Goal
        }

        player.style.left = percent + '%';
        player.classList.add('jump');
        setTimeout(() => player.classList.remove('jump'), 300);
    }

    function finishGame() {
        GameBridge.celebrate("You crossed the lava!");
        document.getElementById('message').innerText = "SAFE!";
        
        GameBridge.saveScore({
            score: score + 50, // Bonus for surviving
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: sessionMistakes
        });
    }

    window.explainRules = function() {
        GameBridge.speak("Answer the question to add a stone. Don't fall in the lava!");
    };
})();