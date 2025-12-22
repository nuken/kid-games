/* games/red-light/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let currentAnswer = null;
    let sessionMistakes = 0;
    let startTime = Date.now();

    // --- 1. MASTER DATA ---
    const masterData = {
        math: [
            { q: "2 + 2 = ?", a: "4", opt: ["3", "4", "5"] },
            { q: "5 + 5 = ?", a: "10", opt: ["8", "10", "9"] },
            { q: "3 + 1 = ?", a: "4", opt: ["2", "4", "6"] },
            { q: "6 - 1 = ?", a: "5", opt: ["5", "7", "4"] },
            { q: "10 - 5 = ?", a: "5", opt: ["2", "5", "10"] },
            { q: "Which is bigger?", a: "9", opt: ["3", "9", "5"] },
            { q: "Which is smaller?", a: "1", opt: ["1", "10", "5"] },
            { q: "Count: 🍎🍎🍎", a: "3", opt: ["2", "3", "4"] },
            { q: "Count: 🚗🚗", a: "2", opt: ["1", "2", "3"] },
            { q: "4 + 4 = ?", a: "8", opt: ["6", "8", "10"] },
            { q: "Shape with 3 sides?", a: "Triangle", opt: ["Circle", "Square", "Triangle"] },
            { q: "Shape with 4 sides?", a: "Square", opt: ["Square", "Circle", "Star"] }
        ],
        reading: [
            { q: "Starts with 'A'", a: "Apple", opt: ["Ball", "Cat", "Apple"] },
            { q: "Starts with 'B'", a: "Ball", opt: ["Ball", "Ant", "Dog"] },
            { q: "Starts with 'C'", a: "Cat", opt: ["Pig", "Cat", "Sun"] },
            { q: "Rhymes with 'Cat'", a: "Hat", opt: ["Dog", "Hat", "Pig"] },
            { q: "Rhymes with 'Sun'", a: "Run", opt: ["Run", "Car", "Bed"] },
            { q: "Opposite of 'Hot'", a: "Cold", opt: ["Cold", "Sun", "Fire"] },
            { q: "Opposite of 'Up'", a: "Down", opt: ["Left", "Down", "Big"] },
            { q: "Opposite of 'Big'", a: "Small", opt: ["Small", "Red", "Tall"] },
            { q: "Find the Vowel", a: "A", opt: ["B", "A", "Z"] },
            { q: "Find the Vowel", a: "O", opt: ["K", "T", "O"] },
            { q: "Read: THE", a: "The", opt: ["The", "And", "Is"] },
            { q: "Read: AND", a: "And", opt: ["The", "And", "You"] }
        ],
        colors: [
            { q: "Color of the Sun", a: "Yellow", opt: ["Blue", "Yellow", "Red"] },
            { q: "Color of Grass", a: "Green", opt: ["Green", "Purple", "Pink"] },
            { q: "Color of the Sky", a: "Blue", opt: ["Blue", "Green", "Orange"] },
            { q: "Color of an Apple", a: "Red", opt: ["Red", "White", "Black"] },
            { q: "Color of a Carrot", a: "Orange", opt: ["Purple", "Orange", "Blue"] },
            { q: "Color of Grapes", a: "Purple", opt: ["Red", "Purple", "Yellow"] },
            { q: "Color of Snow", a: "White", opt: ["Black", "White", "Green"] },
            { q: "Color of Night", a: "Black", opt: ["White", "Black", "Yellow"] },
            { q: "Mix Red + Blue", a: "Purple", opt: ["Orange", "Purple", "Green"] },
            { q: "Mix Red + Yellow", a: "Orange", opt: ["Purple", "Orange", "Blue"] },
            { q: "Mix Blue + Yellow", a: "Green", opt: ["Orange", "Green", "Purple"] }
        ],
        science: [
            { q: "Which one flies?", a: "Bird", opt: ["Dog", "Bird", "Fish"] },
            { q: "Which one swims?", a: "Fish", opt: ["Cat", "Bird", "Fish"] },
            { q: "Where do birds live?", a: "Nest", opt: ["Nest", "Cave", "Water"] },
            { q: "What do plants need?", a: "Sun", opt: ["Pizza", "Sun", "Toys"] },
            { q: "Which is a season?", a: "Summer", opt: ["Monday", "Summer", "March"] },
            { q: "Which is cold?", a: "Ice", opt: ["Fire", "Sun", "Ice"] },
            { q: "Which is hot?", a: "Fire", opt: ["Snow", "Ice", "Fire"] },
            { q: "Baby Dog is a...", a: "Puppy", opt: ["Kitten", "Puppy", "Cub"] },
            { q: "Baby Cat is a...", a: "Kitten", opt: ["Kitten", "Puppy", "Calf"] },
            { q: "We smell with our...", a: "Nose", opt: ["Eyes", "Nose", "Ears"] },
            { q: "We see with our...", a: "Eyes", opt: ["Hands", "Eyes", "Feet"] }
        ]
    };

    // --- 2. ACTIVE DECKS (For Smart Shuffle) ---
    let availableDecks = {
        math: [],
        reading: [],
        colors: [],
        science: []
    };

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Answer the question to turn the light GREEN!",
            levels: [
                { id: 1, label: "Start Engines" } 
            ],
            onStart: (level) => {
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                
                // Initialize Empty Decks
                resetDecks();
                
                nextIntersection();
            }
        });
    });

    function resetDecks() {
        // Clone master data into active decks
        for (const [key, value] of Object.entries(masterData)) {
            availableDecks[key] = [...value];
        }
    }

    function nextIntersection() {
        // 1. Reset Lights to RED
        setLight('red');
        document.getElementById('road').classList.remove('moving');
        
        // 2. Pick a Random Subject
        const subjects = ['math', 'reading', 'colors', 'science'];
        const subj = subjects[Math.floor(Math.random() * subjects.length)];
        
        // 3. SMART SHUFFLE LOGIC
        // Use the deck. If empty, refill it.
        if (availableDecks[subj].length === 0) {
            availableDecks[subj] = [...masterData[subj]];
        }
        
        // Pick random index from remaining cards
        const deckIndex = Math.floor(Math.random() * availableDecks[subj].length);
        const qData = availableDecks[subj][deckIndex];
        
        // Remove it so it doesn't repeat immediately
        availableDecks[subj].splice(deckIndex, 1);

        currentAnswer = qData.a;

        // 4. Update UI
        const tag = document.getElementById('subject-tag');
        tag.innerText = subj.toUpperCase();
        tag.className = 'badge-' + subj; 

        document.getElementById('question-text').innerText = qData.q;

        // FIX: Improve pronunciation for Math symbols
        let speakText = qData.q;
        if (subj === 'math') {
            speakText = speakText
                .replace(/-/g, " minus ")
                .replace(/\+/g, " plus ")
                .replace(/=/g, " equals ");
        }
        
        GameBridge.speak(speakText);

        // 5. Render Buttons
        const controls = document.getElementById('controls-area');
        controls.innerHTML = '';
        
        // Shuffle options visually
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
            // --- GREEN LIGHT ---
            setLight('green');
            GameBridge.handleCorrect();
            GameBridge.speak("Green Light!");
            
            // Animations
            document.getElementById('road').classList.add('moving');
            document.getElementById('player-car').classList.add('zoom-effect');
            setTimeout(() => document.getElementById('player-car').classList.remove('zoom-effect'), 1000);
            
            // Hide buttons briefly
            document.getElementById('controls-area').innerHTML = '<div style="color:#2ecc71; font-size:30px; font-weight:bold;">GO! GO! GO!</div>';

            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);

            setTimeout(() => {
                if (questionsAnswered >= QUESTIONS_TO_WIN) {
                    finishGame();
                } else {
                    setLight('yellow');
                    setTimeout(nextIntersection, 1500);
                }
            }, 3000);

        } else {
            // --- RED LIGHT ---
            sessionMistakes++;
            GameBridge.handleWrong();
            btn.style.opacity = "0.5";
            btn.disabled = true;
            GameBridge.speak("Try again.");
        }
    }

    function setLight(color) {
        document.querySelectorAll('.bulb').forEach(b => b.classList.remove('active'));
        document.getElementById('light-' + color).classList.add('active');
    }

    function finishGame() {
        // --- UPDATED: Use GameBridge for video ending ---
        const videoPath = "assets/videos/traffic_reward.mp4";

        GameBridge.celebrate("You finished the race!", videoPath);

        GameBridge.saveScore({
            score: score,
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: sessionMistakes,
            noRedirect: false // Keep user on screen while video plays
        });
    }

    window.explainRules = function() {
        GameBridge.speak("The light is red. Answer the question to make the car go!");
    };
})();
