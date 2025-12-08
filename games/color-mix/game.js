/* games/color-mix/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 5;
    let startTime = Date.now();
    let currentLevel = 1;

    // Track mistakes
    let sessionMistakes = 0;

    // State
    let targetColorData = null;
    let currentIngredients = [];

    // --- SMART SHUFFLE DECK ---
    let availableRecipes = [];

    // --- COLOR RECIPES ---
    const recipesLevel1 = [
        { name: 'orange', hex: '#FF9800', mix: ['red', 'yellow'] },
        { name: 'green',  hex: '#4CAF50', mix: ['blue', 'yellow'] },
        { name: 'purple', hex: '#9C27B0', mix: ['red', 'blue'] }
    ];

    const recipesLevel2 = [
        { name: 'orange', hex: '#FF9800', mix: ['red', 'yellow'] },
        { name: 'green',  hex: '#4CAF50', mix: ['blue', 'yellow'] },
        { name: 'purple', hex: '#9C27B0', mix: ['red', 'blue'] },
        { name: 'pink',       hex: '#FFC0CB', mix: ['red', 'white'] },
        { name: 'sky blue',   hex: '#87CEEB', mix: ['blue', 'white'] },
        { name: 'gray',       hex: '#808080', mix: ['black', 'white'] },
        { name: 'dark red',   hex: '#8B0000', mix: ['red', 'black'] }
    ];

    const bottles = {
        'red':    '#F44336',
        'yellow': '#FFEB3B',
        'blue':   '#2196F3',
        'white':  '#FFFFFF',
        'black':  '#333333'
    };

    // --- INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof GameBridge !== 'undefined') {
            GameBridge.setupGame({
                instructions: window.LANG.game_color_mix_instr_text,
                speakInstruction: window.LANG.game_color_mix_instr_speak,
                levels: [
                    { id: 1, label: window.LANG.game_color_mix_level1 },
                    { id: 2, label: window.LANG.game_color_mix_level2 }
                ],
                onStart: (level) => {
                    currentLevel = level;
                    startTime = Date.now();
                    score = 0;
                    questionsAnswered = 0;
                    sessionMistakes = 0; // Reset
                    GameBridge.updateScore(score);
                    availableRecipes = [];
                    setupShelf();
                    loadLevel();
                }
            });
        }
    });

    function setupShelf() {
        const shelf = document.getElementById('shelf');
        shelf.innerHTML = '';

        let available = ['red', 'yellow', 'blue'];
        if (currentLevel === 2) {
            available.push('white', 'black');
        }

        available.forEach(colorKey => {
            const btn = document.createElement('button');
            btn.className = 'bottle-btn';
            btn.style.backgroundColor = bottles[colorKey];
            btn.title = colorKey;

            btn.style.color = (colorKey === 'white' || colorKey === 'yellow') ? '#333' : '#fff';
            btn.innerText = colorKey.toUpperCase();

            btn.onclick = () => addIngredient(colorKey);
            shelf.appendChild(btn);
        });
    }

    // --- GAME LOOP ---
    function loadLevel() {
        emptyBeakerUI();
        document.getElementById('message').innerText = "";

        if (availableRecipes.length === 0) {
            const masterList = (currentLevel === 1) ? recipesLevel1 : recipesLevel2;
            availableRecipes = [...masterList];
        }

        const randIndex = Math.floor(Math.random() * availableRecipes.length);
        targetColorData = availableRecipes[randIndex];
        availableRecipes.splice(randIndex, 1);

        const swatch = document.getElementById('target-swatch');
        swatch.style.backgroundColor = targetColorData.hex;
        document.getElementById('target-name').innerText = targetColorData.name;

        GameBridge.speak("Make " + targetColorData.name);
    }

    // --- INTERACTION ---
    window.addIngredient = function(color) {
        if (currentIngredients.length >= 2) {
            GameBridge.speak(window.LANG.game_color_mix_full);
            return;
        }

        if (currentIngredients.includes(color)) {
            GameBridge.speak("You already added " + color + ".");
            document.getElementById('message').innerText = "Already added!";
            document.getElementById('message').style.color = "#e74c3c";
            setTimeout(() => document.getElementById('message').innerText = "", 1000);
            return;
        }

        currentIngredients.push(color);
        updateBeakerVisuals();

        GameBridge.speak(color);
        document.getElementById('message').innerText = "";
    };

    window.emptyBeaker = function() {
        emptyBeakerUI();
        GameBridge.speak("Empty.");
    };

    // FIX: Added the Help Button Function
    window.explainRules = function() {
        GameBridge.speak("Look at the target color. Click the bottles to mix that color in the big beaker.");
    };

    function emptyBeakerUI() {
        currentIngredients = [];
        updateBeakerVisuals();
        document.getElementById('message').innerText = "";
    }

    function updateBeakerVisuals() {
        const liquid = document.getElementById('liquid');
        liquid.classList.remove('bubbles');

        if (currentIngredients.length === 0) {
            liquid.style.height = '0%';
            liquid.style.backgroundColor = 'transparent';
        } else if (currentIngredients.length === 1) {
            liquid.style.height = '50%';
            liquid.style.backgroundColor = bottles[currentIngredients[0]];
        } else {
            liquid.style.height = '90%';
            liquid.style.backgroundColor = bottles[currentIngredients[1]];
        }
    }

    window.checkMix = function() {
        if (currentIngredients.length < 2) {
            GameBridge.speak("You need two colors!");
            return;
        }

        const userMix = [...currentIngredients].sort().join('+');
        const targetMix = [...targetColorData.mix].sort().join('+');

        if (userMix === targetMix) {
            // CORRECT
            const liquid = document.getElementById('liquid');
            liquid.style.backgroundColor = targetColorData.hex;
            liquid.classList.add('bubbles');

            document.getElementById('message').innerText = window.LANG.game_color_mix_perfect;
            document.getElementById('message').style.color = "var(--primary-btn)";

            GameBridge.celebrate("You made " + targetColorData.name + "!");

            score += 20;
            questionsAnswered++;
            GameBridge.updateScore(score);

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                GameBridge.saveScore({
                    score: score,
                    duration: Math.floor((Date.now() - startTime)/1000),
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(loadLevel, 2000);
            }

        } else {
            // WRONG
            sessionMistakes++; // Track mistake
            GameBridge.playAudio('wrong');
            document.getElementById('message').innerText = window.LANG.oops;
            document.getElementById('message').style.color = "var(--danger-btn)";
            document.getElementById('liquid').style.backgroundColor = "#5d4037"; // Brown

            GameBridge.speak("That didn't make " + targetColorData.name + ". " + window.LANG.try_again);
            setTimeout(emptyBeakerUI, 2000);
        }
    };

})();

