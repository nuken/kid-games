/* games/simon-says/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentCommand = {}; 
    let sessionMistakes = 0;
    let startTime = Date.now();

    // --- SMART SHUFFLE DECKS ---
    let deckLvl1 = [];
    let deckLvl2 = [];

    // --- DATA BANKS ---
    const level1Items = [
        { id: 'red-tri',      name: 'Red Triangle',    icon: '🔺', tags: ['red', 'triangle'] },
        { id: 'blue-square',  name: 'Blue Square',     icon: '🟦', tags: ['blue', 'square'] },
        { id: 'green-circle', name: 'Green Circle',    icon: '🟢', tags: ['green', 'circle'] },
        { id: 'yellow-star',  name: 'Yellow Star',     icon: '⭐', tags: ['yellow', 'star'] },
        { id: 'purple-heart', name: 'Purple Heart',    icon: '💜', tags: ['purple', 'heart'] },
        { id: 'orange-diamond', name: 'Orange Diamond', icon: '🔶', tags: ['orange', 'diamond'] }
    ];

    const level2Items = [
        { id: 'cat',    name: 'Cat',    icon: '🐱', tags: ['animal', 'pet', 'meow', 'whiskers'] },
        { id: 'dog',    name: 'Dog',    icon: '🐶', tags: ['animal', 'pet', 'bark', 'puppy'] },
        { id: 'car',    name: 'Car',    icon: '🚗', tags: ['vehicle', 'wheels', 'fast', 'drive'] },
        { id: 'plane',  name: 'Plane',  icon: '✈️', tags: ['vehicle', 'wings', 'fly', 'sky'] },
        { id: 'apple',  name: 'Apple',  icon: '🍎', tags: ['food', 'fruit', 'red', 'healthy'] },
        { id: 'pizza',  name: 'Pizza',  icon: '🍕', tags: ['food', 'hot', 'cheese', 'slice'] },
        { id: 'sun',    name: 'Sun',    icon: '☀️', tags: ['hot', 'sky', 'yellow', 'star'] },
        { id: 'snow',   name: 'Snowman',icon: '⛄', tags: ['cold', 'winter', 'white', 'ice'] },
        { id: 'flower', name: 'Flower', icon: '🌻', tags: ['plant', 'grow', 'garden', 'yellow'] },
        { id: 'fish',   name: 'Fish',   icon: '🐟', tags: ['animal', 'swim', 'water', 'blue'] }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "If Simon says it, do it! If not, hit FREEZE!",
            levels: [
                { id: 1, label: "Shapes & Colors (Pre-K)" },
                { id: 2, label: "Words & Logic (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                
                // Reset Decks
                deckLvl1 = [];
                deckLvl2 = [];
                
                nextCommand();
            }
        });
    });

    function nextCommand() {
        const grid = document.getElementById('options-grid');
        const bubble = document.getElementById('speech-bubble');
        const robot = document.getElementById('robot-avatar');
        
        grid.innerHTML = '';
        robot.classList.remove('talking', 'happy', 'mad');
        
        const masterList = (difficulty === 1) ? level1Items : level2Items;
        let currentDeck = (difficulty === 1) ? deckLvl1 : deckLvl2;

        // 1. SMART SHUFFLE: Pick a unique TARGET from the deck
        if (currentDeck.length === 0) {
            currentDeck = [...masterList]; // Refill if empty
            // Update global reference
            if(difficulty === 1) deckLvl1 = currentDeck; 
            else deckLvl2 = currentDeck;
        }

        const deckIndex = Math.floor(Math.random() * currentDeck.length);
        const targetItem = currentDeck[deckIndex];
        
        // Remove from deck so it doesn't repeat soon
        currentDeck.splice(deckIndex, 1);

        // 2. Pick 2 Distractors (Must not be the target)
        let options = [targetItem];
        while (options.length < 3) {
            let r = masterList[Math.floor(Math.random() * masterList.length)];
            // Prevent duplicates in the visual grid
            if (!options.some(o => o.id === r.id)) {
                options.push(r);
            }
        }
        
        // Shuffle the buttons visually so target isn't always first
        options.sort(() => Math.random() - 0.5);
        
        // Render Board
        options.forEach(item => {
            const btn = document.createElement('button');
            btn.className = 'grid-item';
            btn.innerHTML = `<div class="icon">${item.icon}</div>`;
            btn.onclick = () => checkClick(item);
            grid.appendChild(btn);
        });

        // 3. Decide Command Type (Valid vs Fake)
        const isSimon = Math.random() > 0.4; 
        
        currentCommand = {
            type: isSimon ? 'valid' : 'fake',
            target: targetItem
        };

        // 4. GENERATE SMART CLUE
        let commandText = "";
        
        if (difficulty === 1) {
            commandText = `click the ${targetItem.name}`;
        } else {
            // Find a UNIQUE tag compared to the *currently displayed* distractors
            const uniqueTags = targetItem.tags.filter(tag => {
                const collision = options.some(other => 
                    other.id !== targetItem.id && other.tags.includes(tag)
                );
                return !collision; 
            });

            let clue = "";
            if (uniqueTags.length > 0) {
                clue = uniqueTags[Math.floor(Math.random() * uniqueTags.length)];
            } else {
                clue = targetItem.name.toLowerCase();
            }

            const article = ['a','e','i','o','u'].includes(clue[0]) ? 'an' : 'a';
            commandText = `click ${article} ${clue}`;
        }

        // 5. Output
        let fullSpeech = isSimon ? "Simon says " + commandText : commandText;
        let visualText = isSimon ? `"${fullSpeech}."` : `"${commandText}."`;

        bubble.innerText = visualText;
        robot.classList.add('talking');
        GameBridge.speak(fullSpeech, () => {
            robot.classList.remove('talking');
        });
    }

    // Handle Item Clicks
    window.checkClick = function(item) {
        if (currentCommand.type === 'valid') {
            if (item.id === currentCommand.target.id) {
                handleSuccess();
            } else {
                handleFail("Wrong item!");
            }
        } else {
            handleFail("Simon didn't say!");
        }
    };

    // Handle Freeze Button
    window.checkFreeze = function() {
        if (currentCommand.type === 'fake') {
            handleSuccess();
        } else {
            handleFail("Simon said do it!");
        }
    };

    function handleSuccess() {
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);
        
        const robot = document.getElementById('robot-avatar');
        robot.classList.add('happy');
        robot.innerText = "🤖👍"; 
        setTimeout(() => robot.innerText = "🤖", 1000);

        GameBridge.playAudio('correct');
        
        if (questionsAnswered >= QUESTIONS_TO_WIN) {
            GameBridge.saveScore({
                score: score,
                duration: Math.floor((Date.now() - startTime) / 1000),
                mistakes: sessionMistakes
            });
        } else {
            setTimeout(nextCommand, 1500);
        }
    }

    function handleFail(reason) {
        sessionMistakes++;
        GameBridge.playAudio('wrong');
        
        const robot = document.getElementById('robot-avatar');
        robot.classList.add('mad');
        robot.innerText = "🤖⚡"; 
        setTimeout(() => robot.innerText = "🤖", 1000);

        GameBridge.speak(reason);
    }

    window.explainRules = function() {
        GameBridge.speak("Only click the pictures if I say Simon Says. If I don't say it, click Freeze!");
    };
})();