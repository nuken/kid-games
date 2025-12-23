/* games/wild-world/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentTarget = null;
    let sessionMistakes = 0;
    
    // TIME TRACKING START
    let startTime = Date.now();

    let deckLvl1 = [];
    let deckLvl2 = [];

    // --- DATA ---
    const level1Data = [
        { name: 'Lion',      icon: '🦁' },
        { name: 'Tiger',     icon: '🐯' },
        { name: 'Elephant',  icon: '🐘' },
        { name: 'Monkey',    icon: '🐒' },
        { name: 'Dog',       icon: '🐶' },
        { name: 'Cat',       icon: '🐱' },
        { name: 'Cow',       icon: '🐮' },
        { name: 'Pig',       icon: '🐷' },
        { name: 'Frog',      icon: '🐸' },
        { name: 'Snake',     icon: '🐍' },
        { name: 'Bear',      icon: '🐻' },
        { name: 'Rabbit',    icon: '🐰' },
        { name: 'Mouse',     icon: '🐭' },
        { name: 'Chicken',   icon: '🐔' },
        { name: 'Duck',      icon: '🦆' },
        { name: 'Owl',       icon: '🦉' },
        { name: 'Bee',       icon: '🐝' },
        { name: 'Fish',      icon: '🐟' },
        { name: 'Butterfly', icon: '🦋' },
        { name: 'Turtle',    icon: '🐢' }
    ];

    const level2Data = [
        { q: 'Who lives in the Ocean?', ans: 'Dolphin', icon: '🐬', type: 'Ocean' },
        { q: 'Who lives in the Ocean?', ans: 'Shark',   icon: '🦈', type: 'Ocean' },
        { q: 'Who lives in the Ocean?', ans: 'Whale',   icon: '🐳', type: 'Ocean' },
        { q: 'Who lives in the Ocean?', ans: 'Octopus', icon: '🐙', type: 'Ocean' },
        { q: 'Who lives in the Ocean?', ans: 'Crab',    icon: '🦀', type: 'Ocean' },
        { q: 'Who lives on the Farm?',  ans: 'Horse',   icon: '🐴', type: 'Farm' },
        { q: 'Who lives on the Farm?',  ans: 'Sheep',   icon: '🐑', type: 'Farm' },
        { q: 'Who lives on the Farm?',  ans: 'Rooster', icon: '🐓', type: 'Farm' },
        { q: 'Who lives on the Farm?',  ans: 'Goat',    icon: '🐐', type: 'Farm' },
        { q: 'Find the Jungle animal',  ans: 'Gorilla', icon: '🦍', type: 'Jungle' },
        { q: 'Find the Jungle animal',  ans: 'Parrot',  icon: '🦜', type: 'Jungle' },
        { q: 'Find the Jungle animal',  ans: 'Leopard', icon: '🐆', type: 'Jungle' },
        { q: 'Find the Insect',         ans: 'Ant',     icon: '🐜', type: 'Insect' },
        { q: 'Find the Insect',         ans: 'Ladybug', icon: '🐞', type: 'Insect' },
        { q: 'Find the Insect',         ans: 'Spider',  icon: '🕷️', type: 'Insect' },
        { q: 'Who lives in the Cold?',  ans: 'Penguin', icon: '🐧', type: 'Ice' },
        { q: 'Who lives in the Cold?',  ans: 'Polar Bear',icon:'🐻‍❄️',type: 'Ice' },
        { q: 'Find the Reptile',        ans: 'Crocodile',icon:'🐊', type: 'Reptile' },
        { q: 'Find the Reptile',        ans: 'Lizard',  icon: '🦎', type: 'Reptile' },
        { q: 'Find the Dinosaur',       ans: 'T-Rex',   icon: '🦖', type: 'Dino' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Listen to the guide and find the animal!",
            levels: [
                { id: 1, label: "Find Animals (Pre-K)" },
                { id: 2, label: "Habitats (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                
                // RESET TIMER
                startTime = Date.now();
                
                GameBridge.updateScore(0);
                deckLvl1 = [];
                deckLvl2 = [];
                nextRound();
            }
        });
    });

    function nextRound() {
        const sign = document.getElementById('question-text');
        const floor = document.getElementById('jungle-floor');
        const msg = document.getElementById('message');
        
        floor.innerHTML = '';
        msg.innerText = '';

        if (difficulty === 1) {
            // --- LEVEL 1 ---
            if (deckLvl1.length === 0) deckLvl1 = [...level1Data];
            const idx = Math.floor(Math.random() * deckLvl1.length);
            currentTarget = deckLvl1[idx];
            deckLvl1.splice(idx, 1);

            sign.innerText = "Find: " + currentTarget.name;
            GameBridge.speakNow("Find the " + currentTarget.name);

            let options = [currentTarget];
            while(options.length < 3) {
                let r = level1Data[Math.floor(Math.random() * level1Data.length)];
                if(!options.includes(r)) options.push(r);
            }
            renderOptions(options);

        } else {
            // --- LEVEL 2 ---
            if (deckLvl2.length === 0) deckLvl2 = [...level2Data];
            const idx = Math.floor(Math.random() * deckLvl2.length);
            currentTarget = deckLvl2[idx];
            deckLvl2.splice(idx, 1); 

            sign.innerText = currentTarget.q;
            GameBridge.speakNow(currentTarget.q);

            let options = [currentTarget];
            while(options.length < 3) {
                let r = level2Data[Math.floor(Math.random() * level2Data.length)];
                if(!options.includes(r) && r.type !== currentTarget.type) {
                    options.push(r);
                }
            }
            renderOptions(options);
        }
    }

    function renderOptions(options) {
        const floor = document.getElementById('jungle-floor');
        options.sort(() => Math.random() - 0.5);

        options.forEach(item => {
            const card = document.createElement('div');
            card.className = 'animal-card';
            
            let displayName = (difficulty === 1) ? item.name : item.ans;
            
            card.innerHTML = `
                <div class="animal-icon">${item.icon}</div>
                <div class="animal-name">${displayName}</div>
            `;
            
            card.onclick = () => checkAnswer(item, card);
            floor.appendChild(card);
        });
    }

    function checkAnswer(selected, card) {
        if (selected === currentTarget) {
            GameBridge.handleCorrectSilent();
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);

            card.style.background = "#c8e6c9";

            // FIX 1: Pass 'null' so it doesn't speak "Correct!" immediately.
            // This lets the "You are on fire!" message wait in the buffer
            // and merge seamlessly with the next animal prompt.
            GameBridge.celebrate(null);

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                GameBridge.saveScore({
                    score: score,
                    // FIX 2: Fixed duration math (was dividing by 400, should be 1000 for seconds)
                    duration: Math.floor((Date.now() - startTime) / 1000),
                    mistakes: sessionMistakes
                });
            } else {
                // The 400ms delay now works perfectly with the buffer
                setTimeout(nextRound, 400);
            }
        } else {
            sessionMistakes++;
            GameBridge.handleWrong();
            card.style.opacity = '0.5';

            // Use speakNow to interrupt any previous speech
            GameBridge.speakNow("Try again!");
            document.getElementById('message').innerText = "Try again!";
        }
    }

    window.explainRules = function() {
        GameBridge.speakNow("Read the sign and click the matching animal.");
    }
})();
