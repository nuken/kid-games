/* games/spell-it/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let startTime = Date.now();
    let currentLevel = 1;
    let currentWordLetters = [];
    let availableWords = [];
    let sessionMistakes = 0;

    // --- EXPANDED DATA ---
    const words = [
        // Original Images
        { w: 'cat', i: 'games/spell-it/images/cat.jpg' },
        { w: 'dog', i: 'games/spell-it/images/dog.jpg' },
        { w: 'sun', i: 'games/spell-it/images/sun.jpg' },
        { w: 'bed', i: 'games/spell-it/images/bed.jpg' },
        { w: 'boy', i: 'games/spell-it/images/boy.jpg' },
        { w: 'girl', i: 'games/spell-it/images/girl.jpg' },
        { w: 'cow', i: 'games/spell-it/images/cow.jpg' },
        { w: 'mouse', i: 'games/spell-it/images/mouse.jpg' },
        
        // Emoji Words (Animals)
        { w: 'pig', i: '🐷' }, { w: 'bee', i: '🐝' }, { w: 'ant', i: '🐜' },
        { w: 'fox', i: '🦊' }, { w: 'owl', i: '🦉' }, { w: 'bat', i: '🦇' },
        { w: 'duck', i: '🦆' }, { w: 'fish', i: '🐟' }, { w: 'frog', i: '🐸' },
        { w: 'crab', i: '🦀' }, { w: 'lion', i: '🦁' }, { w: 'bear', i: '🐻' },
        { w: 'worm', i: '🪱' }, { w: 'bird', i: '🐦' }, { w: 'wolf', i: '🐺' },
        
        // Emoji Words (Objects)
        { w: 'bus', i: '🚌' }, { w: 'car', i: '🚗' }, { w: 'bed', i: '🛏️' },
        { w: 'box', i: '📦' }, { w: 'map', i: '🗺️' }, { w: 'cup', i: '☕' },
        { w: 'hat', i: '🎩' }, { w: 'pen', i: '🖊️' }, { w: 'key', i: '🔑' },
        { w: 'gem', i: '💎' }, { w: 'bag', i: '🎒' }, { w: 'fan', i: '💨' },
        { w: 'net', i: '🥅' }, { w: 'axe', i: '🪓' }, { w: 'pot', i: '🍲' },

        // Emoji Words (Food & Nature)
        { w: 'egg', i: '🥚' }, { w: 'jam', i: '🍯' }, { w: 'nut', i: '🥜' },
        { w: 'pie', i: '🥧' }, { w: 'ice', i: '🧊' }, { w: 'sky', i: '☁️' },
        { w: 'sea', i: '🌊' }, { w: 'tree', i: '🌳' }, { w: 'rose', i: '🌹' },
        { w: 'moon', i: '🌙' }, { w: 'star', i: '⭐' }, { w: 'fire', i: '🔥' },
        
        // Emoji Words (4 Letters)
        { w: 'ball', i: '⚽' }, { w: 'book', i: '📖' }, { w: 'cake', i: '🎂' },
        { w: 'door', i: '🚪' }, { w: 'drum', i: '🥁' }, { w: 'kite', i: '🪁' },
        { w: 'lamp', i: '💡' }, { w: 'milk', i: '🥛' }, { w: 'nest', i: '🪺' },
        { w: 'ring', i: '💍' }, { w: 'shoe', i: '👟' }, { w: 'sock', i: '🧦' },
        { w: 'tent', i: '⛺' }, { w: 'bike', i: '🚲' }, { w: 'ship', i: '🚢' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: window.LANG.game_spell_it_instr_text,
            speakInstruction: window.LANG.game_spell_it_instr_speak,
            levels: [
                { id: 1, label: window.LANG.game_spell_it_level1 },
                { id: 2, label: window.LANG.game_spell_it_level2 }
            ],
            onStart: (level) => {
                currentLevel = level;
                startTime = Date.now();
                availableWords = [...words];
                sessionMistakes = 0;
                loadLevel();
            }
        });
    });

    window.loadLevel = function() {
        if (availableWords.length === 0) availableWords = [...words];

        const randIndex = Math.floor(Math.random() * availableWords.length);
        const data = availableWords[randIndex];
        availableWords.splice(randIndex, 1);

        const imgEl = document.getElementById('word-image');
        const emojiEl = document.getElementById('emoji-display');
        const visualContainer = document.getElementById('visual-container');

        // Logic: Check if it's a file path (contains slash or dot) or Emoji
        if (data.i.includes('/') || data.i.includes('.')) {
            imgEl.src = data.i;
            imgEl.style.display = 'block';
            if(emojiEl) emojiEl.style.display = 'none';
        } else {
            if(emojiEl) {
                emojiEl.innerText = data.i;
                emojiEl.style.display = 'flex';
            }
            imgEl.style.display = 'none';
        }
        
        // Remove shake from container
        if(visualContainer) visualContainer.classList.remove('shake');

        document.getElementById('next-btn').classList.add('hidden');
        GameBridge.speak(data.w);

        currentWordLetters = data.w.split('');
        const blankContainer = document.getElementById('word-blanks');
        const choiceContainer = document.getElementById('letter-choices');

        blankContainer.innerHTML = '';
        choiceContainer.innerHTML = '';

        currentWordLetters.forEach(() => {
            const el = document.createElement('div');
            el.className = 'blank';
            blankContainer.appendChild(el);
        });

        let choices = [...currentWordLetters];
        if (currentLevel === 2) {
            const alphabet = 'abcdefghijklmnopqrstuvwxyz';
            for(let i=0; i<3; i++) choices.push(alphabet[Math.floor(Math.random()*26)]);
        }

        choices.sort(() => Math.random() - 0.5);

        choices.forEach(char => {
            const btn = document.createElement('button');
            btn.className = 'letter-button';
            btn.textContent = char.toUpperCase();
            btn.onclick = (e) => handleLetterClick(char, e.target);
            choiceContainer.appendChild(btn);
        });
    };

    function handleLetterClick(char, btn) {
        const blanks = document.querySelectorAll('.blank');
        let targetBlank = null;
        let targetIndex = -1;

        for(let i=0; i<blanks.length; i++) {
            if(!blanks[i].classList.contains('filled')) {
                targetBlank = blanks[i];
                targetIndex = i;
                break;
            }
        }

        if (!targetBlank) return;

        if (char === currentWordLetters[targetIndex]) {
            targetBlank.textContent = char.toUpperCase();
            targetBlank.classList.add('filled');
            btn.style.visibility = 'hidden';

            const remaining = document.querySelectorAll('.blank:not(.filled)').length;
            if (remaining === 0) {
                score += 10;
                questionsAnswered++;
                GameBridge.updateScore(score);

                // CHECK WIN CONDITION FIRST
                if (questionsAnswered >= QUESTIONS_TO_WIN) {
                    // CHANGE: Add your video filename here
                    GameBridge.celebrate("You are a Spelling Star!", "assets/videos/spell_win.mp4");
                    
                    GameBridge.saveScore({
                        score: score,
                        duration: Math.floor((Date.now() - startTime)/1000),
                        mistakes: sessionMistakes
                    });
                } else {
                    // NORMAL ROUND WIN
                    GameBridge.celebrate(window.LANG.correct_short + " " + currentWordLetters.join(''));
                    document.getElementById('next-btn').classList.remove('hidden');
                }
            }
        } else {
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            btn.style.background = '#ffcccc';
            setTimeout(() => btn.style.background = '', 500);
            
            const visualContainer = document.getElementById('visual-container');
            if(visualContainer) {
                visualContainer.classList.add('shake');
                setTimeout(() => visualContainer.classList.remove('shake'), 500);
            }
            GameBridge.speak(window.LANG.try_again);
        }
    }
})();