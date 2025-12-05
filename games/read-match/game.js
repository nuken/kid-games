/* games/read-match/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let startTime = Date.now();
    let currentLevel = 1;
    let availableSentences = [];

    // NEW: Track mistakes
    let sessionMistakes = 0;

    const data = [
        { image: 'games/read-match/images/cat.jpg', correct: 'The cat sits.', foils: ['The dog runs.', 'The bird flies.'] },
        { image: 'games/read-match/images/dog.jpg', correct: 'The dog is happy.', foils: ['The cat is orange.', 'The fish swims.'] },
        { image: 'games/read-match/images/sun.jpg', correct: 'The sun is hot.', foils: ['The moon is cold.', 'The bed is soft.'] },
        { image: 'games/read-match/images/boy.jpg', correct: 'The boy waves.', foils: ['The girl smiles.', 'The cat runs.'] }
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
                sessionMistakes = 0; // Reset mistakes
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
        document.getElementById('next-btn').classList.add('hidden');

        const list = document.getElementById('sentence-list');
        list.innerHTML = '';
        list.style.pointerEvents = 'auto';

        // Prepare Choices
        let choices = [item.correct];
        const numFoils = (currentLevel === 1) ? 1 : 3;

        item.foils.forEach(f => { if(choices.length <= numFoils) choices.push(f); });

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
                    document.getElementById('next-btn').classList.remove('hidden');
                    list.style.pointerEvents = 'none';

                    if (questionsAnswered >= QUESTIONS_TO_WIN) {
                        GameBridge.saveScore({
                            score: score,
                            duration: Math.floor((Date.now() - startTime)/1000),
                            mistakes: sessionMistakes // Send data
                        });
                    }
                } else {
                    // Wrong Answer
                    sessionMistakes++; // Increment counter
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
