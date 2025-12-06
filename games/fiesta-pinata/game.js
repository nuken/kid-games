/* games/fiesta-pinata/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentTarget = null;
    let sessionMistakes = 0;
    
    // TIME TRACKING: Initialize start time
    let startTime = Date.now();

    // --- SMART SHUFFLE DECKS ---
    // These arrays track what cards are left in the "deck" to prevent repeats
    let availableLevel1 = [];
    let availableLevel2 = [];

    // --- LEVEL 1 DATA: Colors & Numbers (20 Items) ---
    const level1Data = [
        // Colors
        { t: 'Rojo',      type: 'color', val: '#e74c3c' }, // Red
        { t: 'Azul',      type: 'color', val: '#3498db' }, // Blue
        { t: 'Verde',     type: 'color', val: '#2ecc71' }, // Green
        { t: 'Amarillo',  type: 'color', val: '#f1c40f' }, // Yellow
        { t: 'Naranja',   type: 'color', val: '#e67e22' }, // Orange
        { t: 'Morado',    type: 'color', val: '#9b59b6' }, // Purple
        { t: 'Rosa',      type: 'color', val: '#ff9ff3' }, // Pink
        { t: 'Negro',     type: 'color', val: '#2d3436' }, // Black
        { t: 'Blanco',    type: 'color', val: '#ffffff' }, // White
        { t: 'Gris',      type: 'color', val: '#95a5a6' }, // Gray
        
        // Numbers
        { t: 'Uno',       type: 'number', val: '1' },
        { t: 'Dos',       type: 'number', val: '2' },
        { t: 'Tres',      type: 'number', val: '3' },
        { t: 'Cuatro',    type: 'number', val: '4' },
        { t: 'Cinco',     type: 'number', val: '5' },
        { t: 'Seis',      type: 'number', val: '6' },
        { t: 'Siete',     type: 'number', val: '7' },
        { t: 'Ocho',      type: 'number', val: '8' },
        { t: 'Nueve',     type: 'number', val: '9' },
        { t: 'Diez',      type: 'number', val: '10' }
    ];

    // --- LEVEL 2 DATA: Sight Words (20 Items) ---
    const level2Data = [
        { eng: 'Hello',   span: 'Hola' },
        { eng: 'Goodbye', span: 'Adiós' },
        { eng: 'Cat',     span: 'Gato' },
        { eng: 'Dog',     span: 'Perro' },
        { eng: 'Friend',  span: 'Amigo' },
        { eng: 'Thanks',  span: 'Gracias' },
        { eng: 'House',   span: 'Casa' },
        { eng: 'School',  span: 'Escuela' },
        { eng: 'Water',   span: 'Agua' },
        { eng: 'Food',    span: 'Comida' },
        { eng: 'Family',  span: 'Familia' },
        { eng: 'Book',    span: 'Libro' },
        { eng: 'Happy',   span: 'Feliz' },
        { eng: 'Sun',     span: 'Sol' },
        { eng: 'Moon',    span: 'Luna' },
        { eng: 'Play',    span: 'Jugar' },
        { eng: 'Love',    span: 'Amor' },
        { eng: 'Please',  span: 'Por favor' },
        { eng: 'Brother', span: 'Hermano' },
        { eng: 'Sister',  span: 'Hermana' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Click the correct answer to hit the Piñata!",
            levels: [
                { id: 1, label: "Colors & Numbers (Pre-K)" },
                { id: 2, label: "Sight Words (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                
                // RESET TIMER & DECKS
                startTime = Date.now();
                GameBridge.updateScore(0);
                
                // Refill decks fresh on start
                availableLevel1 = [];
                availableLevel2 = [];
                
                nextQuestion();
            }
        });
    });

    function nextQuestion() {
        const container = document.getElementById('candy-container');
        container.innerHTML = '';
        
        // Determine which deck to use
        let targetPool = (difficulty === 1) ? availableLevel1 : availableLevel2;
        const masterList = (difficulty === 1) ? level1Data : level2Data;

        // --- SMART SHUFFLE LOGIC ---
        // If the deck is empty, refill it with a fresh copy of the master list
        if (targetPool.length === 0) {
            targetPool = [...masterList];
            // Update the global reference so it persists
            if(difficulty === 1) availableLevel1 = targetPool;
            else availableLevel2 = targetPool;
        }

        // Pick a random card from the remaining pool
        const randIndex = Math.floor(Math.random() * targetPool.length);
        currentTarget = targetPool[randIndex];
        
        // Remove it from the pool so it doesn't repeat until the deck is empty
        targetPool.splice(randIndex, 1);

        // --- DISPLAY PROMPTS ---
        if (difficulty === 1) {
            // Level 1: Speak Spanish ("Rojo!")
            GameBridge.speak(currentTarget.t);
            document.getElementById('message').innerText = "Find: " + currentTarget.t;
        } else {
            // Level 2: Speak English ("Find the word for... CAT")
            GameBridge.speak("Find the word for " + currentTarget.eng);
            document.getElementById('message').innerText = "Translate: " + currentTarget.eng;
        }

        // --- GENERATE OPTIONS ---
        // Start with the correct answer
        let options = [currentTarget];
        
        // Add 2 distractors from the MASTER list (we can reuse items as wrong answers)
        while (options.length < 3) {
            let r = masterList[Math.floor(Math.random() * masterList.length)];
            // Prevent duplicates in the options
            if (!options.includes(r)) options.push(r);
        }
        
        // Shuffle the buttons visually so the answer isn't always first
        options.sort(() => Math.random() - 0.5);

        // Render Buttons
        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'candy-btn';
            
            if (difficulty === 1) {
                if(opt.type === 'color') {
                    btn.style.backgroundColor = opt.val;
                    // Add a border for white/light colors so they are visible
                    if(opt.val === '#ffffff') btn.style.border = "3px solid #ccc";
                    btn.innerText = ""; 
                } else {
                    btn.innerText = opt.val; // Number
                }
            } else {
                btn.innerText = opt.span; // Spanish Word
            }

            btn.onclick = () => checkAnswer(opt, btn);
            container.appendChild(btn);
        });
    }

    function checkAnswer(selected, btn) {
        if (selected === currentTarget) {
            // CORRECT ANSWER
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);
            
            // Animate Piñata
            const pinata = document.getElementById('pinata');
            pinata.classList.add('hit-anim');
            setTimeout(() => pinata.classList.remove('hit-anim'), 500);

            // Celebration (Plays 'correct.mp3' via Bridge)
            GameBridge.celebrate("Muy bien!"); 

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                 // GAME OVER - SAVE SCORE
                 GameBridge.saveScore({
                    score: score,
                    duration: Math.floor((Date.now() - startTime) / 1000), 
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(nextQuestion, 1500);
            }
        } else {
            // WRONG ANSWER
            sessionMistakes++;
            
            GameBridge.playAudio('wrong');
            GameBridge.speak("Try again");
            btn.style.opacity = "0.5";
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Listen to the word and click the matching candy to break the piñata.");
    }
})();