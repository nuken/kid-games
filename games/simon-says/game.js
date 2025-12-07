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

    // --- EXPANDED DATA BANKS ---
    const level1Items = [
        { id: 'red-tri',      name: 'Red Triangle',    icon: '🔺', tags: ['red', 'triangle'] },
        { id: 'blue-square',  name: 'Blue Square',     icon: '🟦', tags: ['blue', 'square'] },
        { id: 'green-circle', name: 'Green Circle',    icon: '🟢', tags: ['green', 'circle'] },
        { id: 'yellow-star',  name: 'Yellow Star',     icon: '⭐', tags: ['yellow', 'star'] },
        { id: 'purple-heart', name: 'Purple Heart',    icon: '💜', tags: ['purple', 'heart'] },
        { id: 'orange-diamond', name: 'Orange Diamond', icon: '🔶', tags: ['orange', 'diamond'] },
        // NEW ITEMS
        { id: 'black-square', name: 'Black Square',    icon: '⬛', tags: ['black', 'square'] },
        { id: 'white-circle', name: 'White Circle',    icon: '⚪', tags: ['white', 'circle'] },
        { id: 'brown-heart',  name: 'Brown Heart',     icon: '🤎', tags: ['brown', 'heart'] },
        { id: 'blue-circle',  name: 'Blue Circle',     icon: '🔵', tags: ['blue', 'circle'] }
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
        { id: 'fish',   name: 'Fish',   icon: '🐟', tags: ['animal', 'swim', 'water', 'blue'] },
        // NEW ITEMS
        { id: 'moon',   name: 'Moon',   icon: '🌙', tags: ['night', 'sky', 'sleep', 'space'] },
        { id: 'rocket', name: 'Rocket', icon: '🚀', tags: ['space', 'fly', 'fast', 'fire'] },
        { id: 'burger', name: 'Burger', icon: '🍔', tags: ['food', 'eat', 'lunch', 'meat'] },
        { id: 'cake',   name: 'Cake',   icon: '🎂', tags: ['food', 'birthday', 'sweet', 'eat'] },
        { id: 'bear',   name: 'Bear',   icon: '🐻', tags: ['animal', 'wild', 'brown', 'fur'] },
        { id: 'frog',   name: 'Frog',   icon: '🐸', tags: ['animal', 'green', 'jump', 'pond'] },
        { id: 'train',  name: 'Train',  icon: '🚂', tags: ['vehicle', 'tracks', 'choo choo', 'ride'] },
        { id: 'book',   name: 'Book',   icon: '📚', tags: ['read', 'school', 'story', 'paper'] },
        { id: 'ball',   name: 'Ball',   icon: '⚽', tags: ['play', 'sport', 'kick', 'round'] },
        { id: 'robot',  name: 'Robot',  icon: '🤖', tags: ['metal', 'beep', 'toy', 'machine'] }
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

        // 1. SMART SHUFFLE
        if (currentDeck.length === 0) {
            currentDeck = [...masterList]; 
            if(difficulty === 1) deckLvl1 = currentDeck; 
            else deckLvl2 = currentDeck;
        }

        const deckIndex = Math.floor(Math.random() * currentDeck.length);
        const targetItem = currentDeck[deckIndex];
        currentDeck.splice(deckIndex, 1);

        // 2. Pick Distractors
        let options = [targetItem];
        while (options.length < 3) {
            let r = masterList[Math.floor(Math.random() * masterList.length)];
            if (!options.some(o => o.id === r.id)) {
                options.push(r);
            }
        }
        
        options.sort(() => Math.random() - 0.5);
        
        // Render Board
        options.forEach(item => {
            const btn = document.createElement('button');
            btn.className = 'grid-item';
            btn.innerHTML = `<div class="icon">${item.icon}</div>`;
            btn.onclick = () => checkClick(item);
            grid.appendChild(btn);
        });

        // 3. Command Type
        const isSimon = Math.random() > 0.4; 
        currentCommand = { type: isSimon ? 'valid' : 'fake', target: targetItem };

        // 4. Generate Clue
        let commandText = "";
        if (difficulty === 1) {
            commandText = `click the ${targetItem.name}`;
        } else {
            // Find unique tag
            const uniqueTags = targetItem.tags.filter(tag => {
                return !options.some(other => other.id !== targetItem.id && other.tags.includes(tag));
            });

            let clue = (uniqueTags.length > 0) ? uniqueTags[Math.floor(Math.random() * uniqueTags.length)] : targetItem.name.toLowerCase();
            const article = ['a','e','i','o','u'].includes(clue[0]) ? 'an' : 'a';
            commandText = `click ${article} ${clue}`;
        }

        let fullSpeech = isSimon ? "Simon says " + commandText : commandText;
        let visualText = isSimon ? `"${fullSpeech}."` : `"${commandText}."`;

        bubble.innerText = visualText;
        robot.classList.add('talking');
        GameBridge.speak(fullSpeech, () => robot.classList.remove('talking'));
    }

    window.checkClick = function(item) {
        if (currentCommand.type === 'valid') {
            if (item.id === currentCommand.target.id) handleSuccess();
            else handleFail("Wrong item!");
        } else {
            handleFail("Simon didn't say!");
        }
    };

    window.checkFreeze = function() {
        if (currentCommand.type === 'fake') handleSuccess();
        else handleFail("Simon said do it!");
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