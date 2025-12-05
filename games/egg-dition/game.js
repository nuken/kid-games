(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentAnswer = 0;
    let sessionMistakes = 0;

    // --- Smart Decks ---
    // Level 1: Numbers 1-10
    let deckLvl1 = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    
    // Level 2: Simple Math Facts (Sums <= 10, then <= 20)
    // We generate these dynamically to save space
    
    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Count the eggs or solve the math!",
            levels: [
                { id: 1, label: "Counting (Pre-K)" },
                { id: 2, label: "Addition (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                // Reset Deck 1
                deckLvl1 = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
                nextQuestion();
            }
        });
    });

    function nextQuestion() {
        const visualContainer = document.getElementById('math-visuals');
        const optionsContainer = document.getElementById('answer-options');
        const msg = document.getElementById('message');
        
        visualContainer.innerHTML = '';
        optionsContainer.innerHTML = '';
        msg.innerText = '';

        if (difficulty === 1) {
            // --- LEVEL 1: COUNTING ---
            if (deckLvl1.length === 0) deckLvl1 = [1,2,3,4,5,6,7,8,9,10];
            const idx = Math.floor(Math.random() * deckLvl1.length);
            currentAnswer = deckLvl1[idx];
            deckLvl1.splice(idx, 1);

            // Draw Eggs
            for(let i=0; i<currentAnswer; i++) {
                visualContainer.innerHTML += '<span class="egg">🥚</span>';
            }
            
            GameBridge.speak("How many eggs?");
            msg.innerText = "Count the eggs!";

        } else {
            // --- LEVEL 2: ADDITION ---
            // Randomly generate sums between 2 and 12 (easy) or up to 20 (later)
            const numA = Math.floor(Math.random() * 6) + 1; 
            const numB = Math.floor(Math.random() * 6) + 1;
            currentAnswer = numA + numB;

            // Visuals:  🥚🥚 + 🥚🥚🥚
            let html = '';
            for(let i=0; i<numA; i++) html += '🥚';
            html += '<span class="math-sign">+</span>';
            for(let i=0; i<numB; i++) html += '🥚';
            
            visualContainer.innerHTML = html;
            GameBridge.speak(numA + " plus " + numB + " is?");
            msg.innerText = `${numA} + ${numB} = ?`;
        }

        // --- GENERATE CHOICES ---
        let choices = [currentAnswer];
        while (choices.length < 3) {
            // Generate smart distractors (close to the real answer)
            let offset = Math.floor(Math.random() * 5) - 2; // -2 to +2
            let fake = currentAnswer + offset;
            if (fake > 0 && !choices.includes(fake)) choices.push(fake);
        }
        choices.sort(() => Math.random() - 0.5);

        choices.forEach(num => {
            const btn = document.createElement('button');
            btn.className = 'number-btn';
            btn.innerText = num;
            btn.onclick = () => checkAnswer(num);
            optionsContainer.appendChild(btn);
        });
    }

    function checkAnswer(selected) {
        if (selected === currentAnswer) {
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);
            GameBridge.celebrate("Excellent!");

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                GameBridge.saveScore({
                    score: score,
                    duration: 0, 
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(nextQuestion, 1500);
            }
        } else {
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            GameBridge.speak("Try again.");
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Count the eggs and click the matching number.");
    };
})();