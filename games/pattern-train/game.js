/* games/pattern-train/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentAnswer = null;
    let sessionMistakes = 0;
    let startTime = Date.now();

    // --- ASSET LIBRARIES ---
    const themePacks = {
        fruits: ['🍎', '🍌', '🍇', '🍊', '🍓', '🍍', '🍒', '🍉'],
        animals: ['🐶', '🐱', '🦁', '🐸', '🐵', '🐮', '🐷', '🐼'],
        vehicles: ['🚗', '🚕', '🚓', '🚑', '🚜', '🚌', '🚒', '🚙'],
        shapes: ['🔴', '🟦', '⭐', '🔺', '💜', '🔶', '🟢', '⬛'],
        sports: ['⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🎱', '🏓']
    };

    // --- NEW: SPEECH TRANSLATOR ---
    // Maps emojis to natural words so it doesn't say "Cat Face"
    const emojiNames = {
        // Fruits
        '🍎': 'Apple', '🍌': 'Banana', '🍇': 'Grapes', '🍊': 'Orange',
        '🍓': 'Strawberry', '🍍': 'Pineapple', '🍒': 'Cherries', '🍉': 'Watermelon',
        // Animals
        '🐶': 'Dog', '🐱': 'Cat', '🦁': 'Lion', '🐸': 'Frog',
        '🐵': 'Monkey', '🐮': 'Cow', '🐷': 'Pig', '🐼': 'Panda',
        // Vehicles
        '🚗': 'Red Car', '🚕': 'Taxi', '🚓': 'Police Car', '🚑': 'Ambulance',
        '🚜': 'Tractor', '🚌': 'Bus', '🚒': 'Fire Truck', '🚙': 'Blue Car',
        // Shapes
        '🔴': 'Red Circle', '🟦': 'Blue Square', '⭐': 'Star', '🔺': 'Triangle',
        '💜': 'Heart', '🔶': 'Diamond', '🟢': 'Green Circle', '⬛': 'Black Square',
        // Sports
        '⚽': 'Soccer', '🏀': 'Basketball', '🏈': 'Football', '⚾': 'Baseball',
        '🎾': 'Tennis', '🏐': 'Volleyball', '🎱': 'Pool Ball', '🏓': 'Ping Pong'
    };

    // --- PATTERN TEMPLATES ---
    const level1Patterns = [
        { seq: [0, 1, 0, 1, 0], name: "ABAB" },
        { seq: [0, 0, 1, 1, 0], name: "AABB" },
        { seq: [0, 1, 2, 0, 1], name: "ABC" }
    ];

    const level2Patterns = [
        { seq: [0, 1, 0, 2, 0], name: "ABAC" },
        { seq: [0, 1, 1, 0, 1], name: "ABBA" },
        { seq: [0, 0, 1, 0, 0], name: "AABA" },
        { seq: [1, 0, 1, 0, 1], name: "BABA" }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Look at the pattern. Which one comes next?",
            levels: [
                { id: 1, label: "Simple (Pre-K)" },
                { id: 2, label: "Complex (K-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                
                resetTrain();
                nextRound();
            }
        });
    });

    function resetTrain() {
        const train = document.getElementById('train-container');
        train.style.transition = 'none';
        train.style.transform = 'translateX(120%)'; 
    }

    function nextRound() {
        // 1. GENERATE PUZZLE
        const themeKeys = Object.keys(themePacks);
        const theme = themePacks[themeKeys[Math.floor(Math.random() * themeKeys.length)]];
        
        let items = [];
        let pool = [...theme];
        for(let i=0; i<3; i++) {
            let idx = Math.floor(Math.random() * pool.length);
            items.push(pool[idx]);
            pool.splice(idx, 1);
        }

        const templates = (difficulty === 1) ? level1Patterns : level2Patterns;
        const template = templates[Math.floor(Math.random() * templates.length)];
        
        const sequenceIndices = template.seq;
        const fullSequence = sequenceIndices.map(i => items[i]); 
        
        let nextIdx;
        if(template.name === "ABC") nextIdx = 2;
        else if(template.name === "ABAB") nextIdx = 1;
        else if(template.name === "AABB") nextIdx = 0;
        else if(template.name === "ABAC") nextIdx = 1;
        else if(template.name === "ABBA") nextIdx = 1;
        else if(template.name === "AABA") nextIdx = 1;
        else if(template.name === "BABA") nextIdx = 0;
        
        currentAnswer = items[nextIdx];

        // 2. RENDER TRAIN
        const train = document.getElementById('train-container');
        train.style.transition = 'none';
        train.style.transform = 'translateX(150%)';
        
        for(let i=0; i<5; i++) {
            const car = document.getElementById(`car-${i+1}`);
            if(car) car.innerText = fullSequence[i];
        }
        
        const targetCar = document.getElementById('car-target');
        targetCar.innerText = "?";
        targetCar.classList.add('mystery');
        targetCar.style.backgroundColor = "#34495e";

        setTimeout(() => {
            train.style.transition = 'transform 1s cubic-bezier(0.25, 1, 0.5, 1)';
            train.style.transform = 'translateX(0)';
        }, 50);

        // 3. GENERATE OPTIONS
        let choices = [currentAnswer];
        while(choices.length < 3) {
            let wrong = theme[Math.floor(Math.random() * theme.length)];
            if(!choices.includes(wrong)) choices.push(wrong);
        }
        choices.sort(() => Math.random() - 0.5);

        const optsContainer = document.getElementById('options-container');
        optsContainer.innerHTML = '';
        
        choices.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.innerText = opt;
            btn.onclick = () => checkAnswer(opt, btn);
            optsContainer.appendChild(btn);
        });

        // 4. AUDIO HINT (FIXED)
        setTimeout(() => {
            // Translate Emojis to Words using the map
            let speakSeq = fullSequence.map(e => emojiNames[e] || e).join(', ');
            GameBridge.speak("Complete the pattern. " + speakSeq + ", what comes next?");
        }, 1000);
    }

    function checkAnswer(val, btn) {
		GameBridge.stopSpeech();
        if (val === currentAnswer) {
           GameBridge.handleCorrect();
            // Speak the name of the correct item
            let name = emojiNames[val] || "That";
            GameBridge.speak(name + "! Correct!");
            
            const targetCar = document.getElementById('car-target');
            targetCar.classList.remove('mystery');
            targetCar.style.backgroundColor = "#2ecc71";
            targetCar.innerText = val;

            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);

            setTimeout(() => {
                const train = document.getElementById('train-container');
                train.style.transition = 'transform 1s ease-in';
                train.style.transform = 'translateX(-150%)'; 
                
                setTimeout(() => {
                    if (questionsAnswered >= QUESTIONS_TO_WIN) {
                        finishGame();
                    } else {
                        nextRound();
                    }
                }, 1000);
            }, 1000);

        } else {
            sessionMistakes++;
            GameBridge.handleWrong();
            btn.style.opacity = "0.5";
            btn.disabled = true;
            GameBridge.speak("Not that one. Try again.");
        }
    }

    function finishGame() {
        // CHANGE: Add your video filename here (e.g., 'assets/videos/train_win.mp4')
        GameBridge.celebrate("All aboard! Pattern complete!", "assets/videos/train_win.mp4");
        
        GameBridge.saveScore({
            score: score,
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: sessionMistakes
        });
    }

    window.explainRules = function() {
        GameBridge.speak("Look at the train cars. Click the picture that finishes the pattern!");
    };
})();