/* games/shape-detective/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentAnswer = null;
    let startTime = Date.now();
    let sessionMistakes = 0;

    let questionDeck = [];

    const shapes = [
        { name: "Circle",   icon: "🔴" },
        { name: "Square",   icon: "🟥" },
        { name: "Triangle", icon: "🔺" },
        { name: "Star",     icon: "⭐" },
        { name: "Heart",    icon: "❤️" },
        { name: "Diamond",  icon: "🔷" },
        { name: "Rectangle",icon: "🟧" }
    ];

    const objects = [
        // Circles
        { obj: "⚽", shape: "🔴", name: "Ball" },
        { obj: "🍩", shape: "🔴", name: "Donut" },
        { obj: "🍪", shape: "🔴", name: "Cookie" },
        { obj: "🍊", shape: "🔴", name: "Orange" },
        { obj: "🕰️", shape: "🔴", name: "Clock" },

        // Squares
        { obj: "📦", shape: "🟥", name: "Box" },
        { obj: "🎁", shape: "🟥", name: "Gift" },
        { obj: "🖼️", shape: "🟥", name: "Frame" },
        { obj: "💾", shape: "🟥", name: "Disk" },
        { obj: "🥪", shape: "🟥", name: "Sandwich" },

        // Triangles
        { obj: "🍕", shape: "🔺", name: "Pizza" },
        { obj: "⛺", shape: "🔺", name: "Tent" },
        { obj: "🍦", shape: "🔺", name: "Ice Cream" },
        { obj: "📐", shape: "🔺", name: "Ruler" },

        // Rectangles (These map to the stretched 🟧)
        { obj: "🚪", shape: "🟧", name: "Door" },
        { obj: "📱", shape: "🟧", name: "Phone" },
        { obj: "💵", shape: "🟧", name: "Money" },
        { obj: "🚌", shape: "🟧", name: "Bus" },

        // Hearts & Stars
        { obj: "💌", shape: "❤️", name: "Love Letter" },
        { obj: "🌟", shape: "⭐", name: "Sparkle" }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Be a Detective! Find the matching shape.",
            levels: [
                { id: 1, label: "Learn Shapes" },
                { id: 2, label: "Real World" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                questionDeck = [];
                nextRound();
            }
        });
    });

    function nextRound() {
        const clueText = document.getElementById('question-text');
        const clueIconDiv = document.getElementById('clue-display');
        const container = document.getElementById('options-container');
        document.getElementById('message').innerText = "";

        container.innerHTML = '';

        if (difficulty === 1) {
            // --- LEVEL 1: SHAPES ---
            if (questionDeck.length === 0) {
                questionDeck = [...shapes];
                shuffleArray(questionDeck);
            }

            const target = questionDeck.pop();
            currentAnswer = target.icon;

            clueText.innerText = target.name;
            clueIconDiv.innerText = "❓";
            GameBridge.speak("Find the " + target.name);

            let opts = [target];
            while (opts.length < 3) {
                let r = shapes[Math.floor(Math.random() * shapes.length)];
                if (!opts.includes(r)) opts.push(r);
            }
            shuffleArray(opts);
            renderButtons(opts.map(o => o.icon));

        } else {
            // --- LEVEL 2: OBJECTS ---
            if (questionDeck.length === 0) {
                questionDeck = [...objects];
                shuffleArray(questionDeck);
            }

            const puzzle = questionDeck.pop();
            currentAnswer = puzzle.shape;

            clueText.innerText = "What shape is this?";
            clueIconDiv.innerHTML = puzzle.obj;
            GameBridge.speak("What shape is a " + puzzle.name + "?");

            let opts = [puzzle.shape];
            while (opts.length < 3) {
                let r = shapes[Math.floor(Math.random() * shapes.length)];
                if (!opts.includes(r.icon)) opts.push(r.icon);
            }
            shuffleArray(opts);
            renderButtons(opts);
        }
    }

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    function renderButtons(emojiList) {
        const container = document.getElementById('options-container');
        emojiList.forEach(emoji => {
            const btn = document.createElement('button');
            btn.className = 'shape-btn';

            if (emoji === "🟧") {
                btn.innerHTML = '<span class="stretch">🟧</span>';
            } else {
                btn.innerText = emoji;
            }

            btn.onclick = () => checkAnswer(emoji, btn);
            container.appendChild(btn);
        });
    }

    function checkAnswer(selected, btn) {
        if (selected === currentAnswer) {
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);

            btn.style.backgroundColor = "#d4edda";
            btn.style.borderColor = "#28a745";
            GameBridge.handleCorrect();

            // Show result in Magnifying Glass
            const clueIconDiv = document.getElementById('clue-display');
            if(difficulty === 1) {
                if (currentAnswer === "🟧") {
                    clueIconDiv.innerHTML = '<span class="stretch">🟧</span>';
                } else {
                    clueIconDiv.innerText = currentAnswer;
                }
            }

            // CHANGED: Video win logic
            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                GameBridge.celebrate("Case Closed! Great job Detective!", "assets/videos/shape_win.mp4");
                
                GameBridge.saveScore({
                    score: score,
                    duration: Math.floor((Date.now() - startTime) / 1000),
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(nextRound, 1000);
            }
        } else {
            sessionMistakes++;
            GameBridge.handleWrong();
            btn.style.opacity = "0.5";
            btn.disabled = true;
            GameBridge.speak("Try again.");
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Look at the clue and click the matching shape.");
    };
})();