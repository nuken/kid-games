/* games/read-match/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let startTime = Date.now();
    let currentLevel = 1;
    let availableSentences = [];

    // Track mistakes
    let sessionMistakes = 0;

    const data = [
        // Original Items
        { 
            image: 'games/read-match/images/cat.jpg', 
            correct: 'The cat sits.', 
            foils: ['The dog runs.', 'The bird flies.', 'The fish swims.'] 
        },
        { 
            image: 'games/read-match/images/dog.jpg', 
            correct: 'The dog is happy.', 
            foils: ['The cat is orange.', 'The fish swims.', 'The bird sings.'] 
        },
        { 
            image: 'games/read-match/images/sun.jpg', 
            correct: 'The sun is hot.', 
            foils: ['The moon is cold.', 'The bed is soft.', 'The stars shine.'] 
        },
        { 
            image: 'games/read-match/images/boy.jpg', 
            correct: 'The boy waves.', 
            foils: ['The girl smiles.', 'The cat runs.', 'The dog jumps.'] 
        },
        
        // New Items
        { 
            image: 'games/read-match/images/cow.jpg', 
            correct: 'The cow says moo.', 
            foils: ['The pig says oink.', 'The horse runs.', 'The duck quacks.'] 
        },
        { 
            image: 'games/read-match/images/bed.jpg', 
            correct: 'The bed is soft.', 
            foils: ['The rock is hard.', 'The chair is red.', 'The sun is hot.'] 
        },
        { 
            image: 'games/read-match/images/girl.jpg', 
            correct: 'The girl smiles.', 
            foils: ['The boy jumps.', 'The dog sleeps.', 'The cat plays.'] 
        },
        { 
            image: 'games/read-match/images/mouse.jpg', 
            correct: 'The mouse is small.', 
            foils: ['The elephant is big.', 'The lion roars.', 'The cat sits.'] 
        }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: window.LANG.game_read_match_instr_text,
            levels: [
                { id: 1, label: window.LANG.game_read_match_level1 },
                { id: 2, label: window.LANG.game_read_match_level2 }
            ],
            onStart: (level) => {
                currentLevel = level;
                startTime = Date.now();
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                availableSentences = [...data];
                loadLevel();
            }
        });
    });

    window.loadLevel = function() {
        if (availableSentences.length === 0) availableSentences = [...data];

        const idx = Math.floor(Math.random() * availableSentences.length);
        const item = availableSentences[idx];
        availableSentences.splice(idx, 1);

        document.getElementById('word-image').src = item.image;
        
        // CHANGED: Ensure the next button stays hidden (just in case)
        const nextBtn = document.getElementById('next-btn');
        if(nextBtn) nextBtn.classList.add('hidden');

        const list = document.getElementById('sentence-list');
        list.innerHTML = '';
        list.style.pointerEvents = 'auto'; // Re-enable clicking

        // Prepare Choices
        let choices = [item.correct];
        const numFoils = (currentLevel === 1) ? 1 : 3;

        item.foils.forEach(f => { 
            if(choices.length <= numFoils) choices.push(f); 
        });

        choices.sort(() => Math.random() - 0.5).forEach(txt => {
            const btn = document.createElement('div');
            btn.className = 'sentence-card';
            btn.innerHTML = `<span>${txt}</span> <span class="audio-icon">🔊</span>`;

            btn.querySelector('.audio-icon').onclick = (e) => {
                e.stopPropagation();
                GameBridge.speak(txt);
            };

            btn.onclick = () => {
                if (txt === item.correct) {
                    // Correct Answer
                    btn.classList.add('correct');
                    score += 10;
                    questionsAnswered++;
                    GameBridge.updateScore(score);
                    GameBridge.celebrate(window.LANG.game_read_match_correct);
                    
                    // CHANGED: Disable clicks immediately so they don't double-click
                    list.style.pointerEvents = 'none';

                    if (questionsAnswered >= QUESTIONS_TO_WIN) {
                        GameBridge.saveScore({
                            score: score,
                            duration: Math.floor((Date.now() - startTime)/1000),
                            mistakes: sessionMistakes
                        });
                    } else {
                        // CHANGED: Auto-advance after 1.5 seconds instead of showing button
                        setTimeout(loadLevel, 1500);
                    }
                } else {
                    // Wrong Answer
                    sessionMistakes++;
                    GameBridge.playAudio('wrong');
                    btn.classList.add('wrong');
                    GameBridge.speak(window.LANG.try_again);
                    setTimeout(() => btn.classList.remove('wrong'), 500);
                }
            };
            list.appendChild(btn);
        });
    };
})();