/* games/sight-word-reader/game.js */
(function() {
    // --- VARIABLES ---
    let currentPage = 0;
    let currentStoryData = [];
    let currentQuiz = null;
    let currentSightWords = [];
    let isReading = false;
    let currentActiveLevel = 'level1';

    // Track Pre-Roll Progress
    let wordsFoundCount = 0;

    // --- STORY LIBRARY ---
    const library = {
        'level1': {
            pages: [
                { image: "games/sight-word-reader/images/bird_1.png", text: "Once there was a little bird." },
                { image: "games/sight-word-reader/images/bird_2.png", text: "She wanted to fly over the trees." },
                { image: "games/sight-word-reader/images/bird_3.png", text: "She asked her mom,\nHow do I fly?" },
                { image: "games/sight-word-reader/images/bird_4.png", text: "Just open your wings and go!" },
                { image: "games/sight-word-reader/images/bird_5.png", text: "She flew over the garden.\nShe was happy." }
            ],
            sightWords: ["Bird", "Fly", "Happy", "Wings"],
            quiz: {
                question: "Who wanted to fly?",
                answers: ["The Bird", "A Cat", "The Dog"],
                correct: "The Bird"
            }
        },
        'level2': {
            pages: [
                { image: "games/sight-word-reader/images/box_1.png", text: "I found an old box in the dirt." },
                { image: "games/sight-word-reader/images/box_2.png", text: "It was very cold and green." },
                { image: "games/sight-word-reader/images/box_3.png", text: "I did not have the right key." },
                { image: "games/sight-word-reader/images/box_4.png", text: "I made a wish upon a star." },
                { image: "games/sight-word-reader/images/box_5.png", text: "Then I found the key under a rock!" }
            ],
            sightWords: ["Box", "Key", "Star", "Rock"],
            quiz: {
                question: "What was under the rock?",
                answers: ["A Bug", "The Key", "An Egg"],
                correct: "The Key"
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "First, find the special words!",
            levels: [
                { id: 'level1', label: 'The Little Bird 🐦 (Gr 1)' },
                { id: 'level2', label: 'The Magic Box 📦 (Gr 2)' }
            ],
            onStart: (levelId) => {
                currentActiveLevel = levelId;
                const data = library[levelId] || library['level1'];

                currentStoryData = data.pages;
                currentSightWords = data.sightWords;
                currentQuiz = data.quiz;

                // Start with Word Hunt
                startWordHunt();
            }
        });
    });

    // --- PHASE 1: WORD HUNT ---
    function startWordHunt() {
        // Show Overlay
        const overlay = document.getElementById('word-hunt-overlay');
        const quizOverlay = document.getElementById('quiz-overlay');

        if (overlay) overlay.style.display = 'flex';
        if (quizOverlay) quizOverlay.style.display = 'none';

        const grid = document.getElementById('word-hunt-grid');
        grid.innerHTML = '';
        wordsFoundCount = 0;

        GameBridge.speak("Tap the words to hear them!");

        // Generate Buttons
        currentSightWords.forEach(word => {
            const btn = document.createElement('button');
            btn.className = 'hunt-word-btn';
            btn.innerText = word;

            btn.onclick = () => {
                // FIX 1: Prevent clicking already found words
                if (btn.classList.contains('found')) return;

                btn.classList.add('found');
                btn.innerText = word + " ✅";
                wordsFoundCount++;

                const isWin = wordsFoundCount >= currentSightWords.length;

                // FIX 2: Chain the audio using the callback
                // Speak the word FIRST. When that finishes, check if we won.
                GameBridge.speakNow(word, () => {
                    if (isWin) {
                        GameBridge.speak("Good job! Let's read.", () => {
                            setTimeout(() => {
                                if(overlay) overlay.style.display = 'none';
                                startStory();
                            }, 1000);
                        });
                    }
                });
            };
            grid.appendChild(btn);
        });
    }

    // --- PHASE 2: STORY READER ---
    function startStory() {
        currentPage = 0;
        renderPage();
        setTimeout(() => GameBridge.speak("Read the story! Tap words to hear them."), 500);
    }

    window.renderPage = function() {
        const page = currentStoryData[currentPage];
        isReading = false;

        // Update Progress Bar
        const progressPct = ((currentPage + 1) / currentStoryData.length) * 100;
        const pFill = document.getElementById('progress-fill');
        if(pFill) pFill.style.width = `${progressPct}%`;

        // Update Image
        const imgEl = document.getElementById('story-image');
        if(imgEl) {
            imgEl.src = page.image;
            imgEl.style.transform = "scale(0.9)";
            setTimeout(() => imgEl.style.transform = "scale(1)", 150);
        }

        // Render Text
        const textContainer = document.getElementById('story-text');
        if(textContainer) {
            textContainer.innerHTML = '';

            let globalWordIndex = 0;
            const lines = page.text.split('\n');

            lines.forEach(lineText => {
                const lineDiv = document.createElement('div');
                const words = lineText.split(' ');

                words.forEach(word => {
                    const span = document.createElement('span');
                    span.innerText = word + ' ';
                    span.className = 'word-interactive';
                    span.id = `word-${globalWordIndex}`;

                    const cleanWord = word.replace(/[.,!?'"]/g, "");
                    span.dataset.clean = cleanWord;

                    span.onclick = () => {
                        span.style.transform = "scale(1.3)";
                        setTimeout(() => span.style.transform = "scale(1.1)", 200);
                        GameBridge.speakNow(cleanWord);
                    };

                    lineDiv.appendChild(span);
                    globalWordIndex++;
                });
                textContainer.appendChild(lineDiv);
            });
        }
        updateButtons();
    };

    function updateButtons() {
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');

        if(btnPrev) {
            btnPrev.disabled = (currentPage === 0);
            btnPrev.style.opacity = (currentPage === 0) ? 0.5 : 1;
        }
        if(btnNext) {
            btnNext.innerText = (currentPage === currentStoryData.length - 1) ? "Finish! 🏆" : "Next ➡";
        }
    }

    window.readPageKaraoke = function() {
        if (isReading) return;
        isReading = true;

        const page = currentStoryData[currentPage];
        const wordSpans = document.querySelectorAll('.word-interactive');
        let index = 0;

        function speakNextWord() {
            if (index >= wordSpans.length || currentPage !== currentStoryData.indexOf(page)) {
                isReading = false;
                return;
            }
            const span = wordSpans[index];
            span.classList.add('word-reading');
            GameBridge.speak(span.dataset.clean, () => {
                span.classList.remove('word-reading');
                index++;
                setTimeout(speakNextWord, 50);
            });
        }
        speakNextWord();
    };

    window.nextPage = function() {
        isReading = false;
        if (currentPage < currentStoryData.length - 1) {
            currentPage++;
            renderPage();
            GameBridge.playAudio('correct');
        } else {
            startQuiz();
        }
    };

    window.prevPage = function() {
        isReading = false;
        if (currentPage > 0) {
            currentPage--;
            renderPage();
        }
    };

    // --- PHASE 3: COMPREHENSION CHECK ---
    function startQuiz() {
        const quizOverlay = document.getElementById('quiz-overlay');
        const qText = document.getElementById('quiz-question');
        const qOpts = document.getElementById('quiz-options');

        if(qText) qText.innerText = currentQuiz.question;
        if(qOpts) qOpts.innerHTML = '';

        if(quizOverlay) {
            quizOverlay.style.display = 'flex';
            GameBridge.speak(currentQuiz.question);
        }

        let opts = [...currentQuiz.answers].sort(() => Math.random() - 0.5);

        opts.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'quiz-btn';
            btn.innerText = opt;
            btn.onclick = () => {
                if (opt === currentQuiz.correct) {
                    GameBridge.celebrate("You got it!", getEndVideo());
                    GameBridge.saveScore({ score: 100, duration: 0, mistakes: 0 });
                    if(quizOverlay) quizOverlay.style.display = 'none';
                } else {
                    GameBridge.speakNow("Try again.");
                    btn.style.opacity = 0.5;
                }
            };
            qOpts.appendChild(btn);
        });
    }

    function getEndVideo() {
        if (currentActiveLevel === 'level1') return 'assets/videos/bird_end.mp4';
        if (currentActiveLevel === 'level2') return 'assets/videos/box_end.mp4';
        return '';
    }

    window.interactWithImage = function() {
        const phrases = ["Great reading!", "Look at that!", "Wow!"];
        const phrase = phrases[Math.floor(Math.random() * phrases.length)];
        GameBridge.speakNow(phrase);
    };

})();
