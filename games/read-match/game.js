/* games/read-match/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let startTime = Date.now();
    let currentLevel = 1;
    let availableSentences = [];
    let sessionMistakes = 0;

    const data = [
        // Original Items (Images)
        { image: 'games/read-match/images/cat.jpg', correct: 'The cat sits.', foils: ['The dog runs.', 'The bird flies.', 'The fish swims.'] },
        { image: 'games/read-match/images/dog.jpg', correct: 'The dog is happy.', foils: ['The cat is orange.', 'The fish swims.', 'The bird sings.'] },
        { image: 'games/read-match/images/sun.jpg', correct: 'The sun is hot.', foils: ['The moon is cold.', 'The bed is soft.', 'The stars shine.'] },
        { image: 'games/read-match/images/boy.jpg', correct: 'The boy waves.', foils: ['The girl smiles.', 'The cat runs.', 'The dog jumps.'] },
        { image: 'games/read-match/images/cow.jpg', correct: 'The cow says moo.', foils: ['The pig says oink.', 'The horse runs.', 'The duck quacks.'] },
        { image: 'games/read-match/images/bed.jpg', correct: 'The bed is soft.', foils: ['The rock is hard.', 'The chair is red.', 'The sun is hot.'] },
        { image: 'games/read-match/images/girl.jpg', correct: 'The girl smiles.', foils: ['The boy jumps.', 'The dog sleeps.', 'The cat plays.'] },
        { image: 'games/read-match/images/mouse.jpg', correct: 'The mouse is small.', foils: ['The elephant is big.', 'The lion roars.', 'The cat sits.'] },

        // NEW Emoji Items
        { image: '🍎', correct: 'The apple is red.', foils: ['The banana is yellow.', 'The grape is purple.', 'The sky is blue.'] },
        { image: '🚗', correct: 'The car is fast.', foils: ['The turtle is slow.', 'The rock sits.', 'The tree is green.'] },
        { image: '✈️', correct: 'The plane flies high.', foils: ['The boat floats.', 'The car drives.', 'The bike rolls.'] },
        { image: '🐟', correct: 'The fish swims.', foils: ['The bird flies.', 'The cat walks.', 'The dog barks.'] },
        { image: '🕷️', correct: 'The spider has 8 legs.', foils: ['The bird has 2 legs.', 'The dog has 4 legs.', 'The snake has 0 legs.'] },
        { image: '🌧️', correct: 'It is raining.', foils: ['The sun is shining.', 'It is snowing.', 'The wind blows.'] },
        { image: '🎂', correct: 'Happy Birthday!', foils: ['Good morning!', 'Good night.', 'Hello friend.'] },
        { image: '🦁', correct: 'The lion is king.', foils: ['The mouse is small.', 'The cat meows.', 'The dog plays.'] },
        { image: '🐸', correct: 'The frog is green.', foils: ['The pig is pink.', 'The sky is blue.', 'The sun is yellow.'] },
        { image: '🦉', correct: 'The owl sleeps all day.', foils: ['The rooster crows.', 'The dog runs.', 'The cat plays.'] },
        { image: '🚲', correct: 'I ride my bike.', foils: ['I drive a car.', 'I fly a plane.', 'I sail a boat.'] },
        { image: '🚀', correct: 'The rocket goes up.', foils: ['The rock stays down.', 'The car goes left.', 'The fish goes down.'] },
        { image: '🏰', correct: 'The princess lives here.', foils: ['The farmer lives here.', 'The bear sleeps here.', 'The fish swims here.'] },
        { image: '🦖', correct: 'The dino is huge.', foils: ['The ant is tiny.', 'The cat is small.', 'The dog is fast.'] }
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

        const imgEl = document.getElementById('word-image');
        const emojiEl = document.getElementById('emoji-display');

        // Logic: Check if path contains dot or slash
        if (item.image.includes('/') || item.image.includes('.')) {
            imgEl.src = item.image;
            imgEl.style.display = 'block';
            if(emojiEl) emojiEl.style.display = 'none';
        } else {
            if(emojiEl) {
                emojiEl.innerText = item.image;
                emojiEl.style.display = 'flex';
            }
            imgEl.style.display = 'none';
        }
        
        const nextBtn = document.getElementById('next-btn');
        if(nextBtn) nextBtn.classList.add('hidden');

        const list = document.getElementById('sentence-list');
        list.innerHTML = '';
        list.style.pointerEvents = 'auto';

        let choices = [item.correct];
        const numFoils = (currentLevel === 1) ? 1 : 3;
        item.foils.forEach(f => { if(choices.length <= numFoils) choices.push(f); });

        choices.sort(() => Math.random() - 0.5).forEach(txt => {
            const btn = document.createElement('div');
            btn.className = 'sentence-card';
            btn.innerHTML = `<span>${txt}</span> <span class="audio-icon">🔊</span>`;

            btn.querySelector('.audio-icon').onclick = (e) => {
                e.stopPropagation();
                GameBridge.speakNow(txt);
            };

            btn.onclick = () => {
                if (txt === item.correct) {
					GameBridge.handleCorrect();
                    btn.classList.add('correct');
                    score += 10;
                    questionsAnswered++;
                    GameBridge.updateScore(score);

                    // --- FIX START ---
                    // 1. Cut off instructions and speak "Correct" immediately
                    GameBridge.speakNow(window.LANG.game_read_match_correct);
                    
                    // 2. Call celebrate with NO text (null) so it doesn't double-speak
                    //    This still handles the confetti and sound effects.
                    GameBridge.celebrate(null);
                    // --- FIX END ---

                    list.style.pointerEvents = 'none';

                    if (questionsAnswered >= QUESTIONS_TO_WIN) {
                        GameBridge.saveScore({
                            score: score,
                            duration: Math.floor((Date.now() - startTime)/1000),
                            mistakes: sessionMistakes
                        });
                    } else {
                        setTimeout(loadLevel, 1500);
                    }
                } else {
                    sessionMistakes++;
                    GameBridge.handleWrong();
                    btn.classList.add('wrong');
                    
                    // --- FIX START ---
                    // Use speakNow so "Try Again" cuts off the instructions
                    GameBridge.speakNow(window.LANG.try_again);
                    // --- FIX END ---
                    
                    setTimeout(() => btn.classList.remove('wrong'), 500);
                }
            };
            list.appendChild(btn);
        });
    };
})();