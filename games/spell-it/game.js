/* games/spell-it/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let startTime = Date.now();
    let currentLevel = 1;
    let currentWordLetters = [];
    let availableWords = []; 

    const words = [
        { w: 'cat', i: 'games/spell-it/images/cat.jpg' }, 
        { w: 'dog', i: 'games/spell-it/images/dog.jpg' },
        { w: 'sun', i: 'games/spell-it/images/sun.jpg' }, 
        { w: 'bed', i: 'games/spell-it/images/bed.jpg' },
        { w: 'boy', i: 'games/spell-it/images/boy.jpg' }, 
        { w: 'girl', i: 'games/spell-it/images/girl.jpg' },
        { w: 'cow', i: 'games/spell-it/images/cow.jpg' }, 
        { w: 'mouse', i: 'games/spell-it/images/mouse.jpg' }
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
                loadLevel();
            }
        });
    });

    window.loadLevel = function() {
        if (availableWords.length === 0) availableWords = [...words];
        
        const randIndex = Math.floor(Math.random() * availableWords.length);
        const data = availableWords[randIndex];
        availableWords.splice(randIndex, 1);

        const img = document.getElementById('word-image');
        img.src = data.i;
        img.classList.remove('shake');
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
            
            // Check win
            const remaining = document.querySelectorAll('.blank:not(.filled)').length;
            if (remaining === 0) {
                score += 10;
                questionsAnswered++;
                GameBridge.updateScore(score);
                GameBridge.celebrate(window.LANG.correct_short + " " + currentWordLetters.join(''));
                document.getElementById('next-btn').classList.remove('hidden');

                if (questionsAnswered >= QUESTIONS_TO_WIN) {
                    GameBridge.saveScore({ score: score, duration: Math.floor((Date.now() - startTime)/1000) });
                }
            }
        } else {
            btn.style.background = '#ffcccc';
            setTimeout(() => btn.style.background = '', 500);
            document.getElementById('word-image').classList.add('shake');
            setTimeout(() => document.getElementById('word-image').classList.remove('shake'), 500);
            GameBridge.speak(window.LANG.try_again);
        }
    }
})();