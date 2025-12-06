/* games/red-light/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentAnswer = null;
    let sessionMistakes = 0;
    let startTime = Date.now();

    // --- DATA: MIXED SUBJECTS ---
    const questionBank = {
        math: [
            { q: "2 + 2 = ?", a: "4", opt: ["3", "4", "5"] },
            { q: "5 - 1 = ?", a: "4", opt: ["4", "6", "5"] },
            { q: "3 + 3 = ?", a: "6", opt: ["5", "6", "9"] },
            { q: "Which is bigger?", a: "10", opt: ["10", "2", "5"] }
        ],
        reading: [
            { q: "Starts with 'A'", a: "Apple", opt: ["Ball", "Cat", "Apple"] },
            { q: "Rhymes with 'Cat'", a: "Hat", opt: ["Dog", "Hat", "Pig"] },
            { q: "Opposite of 'Hot'", a: "Cold", opt: ["Cold", "Sun", "Fire"] },
            { q: "Find the Vowel", a: "E", opt: ["B", "E", "Z"] }
        ],
        colors: [
            { q: "Color of the Sun", a: "Yellow", opt: ["Blue", "Yellow", "Red"] },
            { q: "Color of Grass", a: "Green", opt: ["Green", "Purple", "Pink"] },
            { q: "Mix Red + Blue", a: "Purple", opt: ["Orange", "Purple", "Brown"] },
            { q: "Color of a Strawberry", a: "Red", opt: ["Red", "White", "Black"] }
        ],
        science: [
            { q: "Which one flies?", a: "Bird", opt: ["Dog", "Bird", "Fish"] },
            { q: "Where do fish live?", a: "Water", opt: ["Tree", "Water", "Sky"] },
            { q: "What do plants need?", a: "Sun", opt: ["Pizza", "Sun", "Toys"] },
            { q: "Which is a season?", a: "Summer", opt: ["Monday", "Summer", "March"] }
        ]
    };

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Answer the question to turn the light GREEN!",
            levels: [
                { id: 1, label: "Cruising (Pre-K/K)" },
                { id: 2, label: "Speedway (1st/2nd)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                nextIntersection();
            }
        });
    });

    function nextIntersection() {
        // 1. Reset Lights to RED
        setLight('red');
        document.getElementById('road').classList.remove('moving');
        document.getElementById('scenery').classList.remove('moving');
        
        // 2. Pick a Random Subject
        const subjects = ['math', 'reading', 'colors', 'science'];
        const subj = subjects[Math.floor(Math.random() * subjects.length)];
        
        // 3. Get Question Data (In a real app, you'd filter by difficulty here)
        const pool = questionBank[subj];
        const qData = pool[Math.floor(Math.random() * pool.length)];
        currentAnswer = qData.a;

        // 4. Update UI
        const tag = document.getElementById('subject-tag');
        tag.innerText = subj.toUpperCase() + " ZONE";
        tag.className = 'badge-' + subj; // For coloring

        document.getElementById('question-text').innerText = qData.q;
        GameBridge.speak(qData.q);

        // 5. Render Buttons
        const controls = document.getElementById('controls-area');
        controls.innerHTML = '';
        
        // Shuffle options
        let options = [...qData.opt];
        options.sort(() => Math.random() - 0.5);

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'dash-btn';
            btn.innerText = opt;
            btn.onclick = () => checkAnswer(opt, btn);
            controls.appendChild(btn);
        });
    }

    function checkAnswer(val, btn) {
        if (val === currentAnswer) {
            // --- GREEN LIGHT SUCCESS ---
            setLight('green');
            GameBridge.playAudio('correct');
            GameBridge.speak("Green Light! Go!");
            
            // Animate road
            document.getElementById('road').classList.add('moving');
            document.getElementById('scenery').classList.add('moving');
            
            // Clear buttons to show view
            document.getElementById('controls-area').innerHTML = '<div style="color:white; font-size:30px; padding:20px;">VROOM! 💨</div>';

            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);

            // Wait 3 seconds of "driving" then stop at next light
            setTimeout(() => {
                if (questionsAnswered >= QUESTIONS_TO_WIN) {
                    finishGame();
                } else {
                    setLight('yellow'); // Transition
                    setTimeout(nextIntersection, 1500);
                }
            }, 3000);

        } else {
            // --- RED LIGHT MISTAKE ---
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            btn.style.opacity = "0.5";
            btn.disabled = true;
            GameBridge.speak("Still Red. Try again.");
        }
    }

    function setLight(color) {
        document.querySelectorAll('.bulb').forEach(b => b.classList.remove('active'));
        document.getElementById('light-' + color).classList.add('active');
    }

    function finishGame() {
        GameBridge.celebrate("You reached the finish line!");
        GameBridge.saveScore({
            score: score,
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: sessionMistakes
        });
    }

    window.explainRules = function() {
        GameBridge.speak("The light is red. Answer the question correctly to turn it green and drive!");
    };
})();