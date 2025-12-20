/* games/cat-rat-reader/game.js */
(function() {
    // --- VARIABLES ---
    let currentPage = 0;
    let currentStoryData = [];
    let isReading = false;
    let currentActiveLevel = 'level1'; // Track level for video ending
    
    // --- SMART ENCOURAGEMENT SHUFFLE ---
    const imagePhrases = [
        "Great picture!", 
        "Look at that!", 
        "What do you see?", 
        "Keep reading!",
        "You are doing great!",
        "Nice job!",
        "Wow, look at the colors!"
    ];
    
    let phraseDeck = [];

    function shuffleDeck() {
        phraseDeck = [...imagePhrases];
        for (let i = phraseDeck.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [phraseDeck[i], phraseDeck[j]] = [phraseDeck[j], phraseDeck[i]];
        }
    }

    // --- STORY LIBRARY ---
    const library = {
        'level1': [
            { image: "games/cat-rat-reader/images/image_0.png", text: "Look at the cat.\nThe cat is fat." },
            { image: "games/cat-rat-reader/images/image_1.png", text: "The cat has a hat.\nHe likes that hat." },
            { image: "games/cat-rat-reader/images/image_2.png", text: "The cat sat.\nHe sat on the mat." },
            { image: "games/cat-rat-reader/images/image_3.png", text: "Look at the rat.\nThe rat sees the cat." },
            { image: "games/cat-rat-reader/images/image_4.png", text: "They nap on the mat.\nJust like that!" }
        ],
        'level2': [
            { image: "games/cat-rat-reader/images/dog_1.png", text: "See the dog.\nThe dog is big." },
            { image: "games/cat-rat-reader/images/dog_2.png", text: "The dog has a ball.\nHe likes the ball." },
            { image: "games/cat-rat-reader/images/dog_3.png", text: "The dog sees a pig.\nThe pig is pink." },
            { image: "games/cat-rat-reader/images/dog_4.png", text: "They run in the sun.\nRun dog run!" },
            { image: "games/cat-rat-reader/images/dog_5.png", text: "The pig and dog sit.\nThey are friends." }
        ]
    };

    // --- INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', () => {
        const instructionsText = "Let's read a story! Click words to hear them.";
        shuffleDeck();

        GameBridge.setupGame({
            instructions: instructionsText,
            speakInstruction: " ",
            levels: [
                { id: 'level1', label: 'The Cat & Rat 🐱' },
                { id: 'level2', label: 'The Dog & Friends 🐶' }
            ],
            onStart: (levelId) => {
                currentActiveLevel = levelId; // Save level ID for the video
                currentStoryData = library[levelId] || library['level1'];
                currentPage = 0;
                renderPage();
            }
        });

        setTimeout(() => GameBridge.speak(instructionsText), 500);
    });

    // --- RENDER PAGE ---
    window.renderPage = function() {
        const page = currentStoryData[currentPage];
        isReading = false;

        // 1. Progress Bar
        const progressPct = ((currentPage + 1) / currentStoryData.length) * 100;
        const pFill = document.getElementById('progress-fill');
        if (pFill) pFill.style.width = `${progressPct}%`;

        // 2. Image Animation
        const imgEl = document.getElementById('story-image');
        if (imgEl) {
            imgEl.src = page.image;
            imgEl.style.transform = "scale(0.9)";
            setTimeout(() => imgEl.style.transform = "scale(1)", 150);
        }

        // 3. Text Processing
        const textContainer = document.getElementById('story-text');
        if (textContainer) {
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
                        GameBridge.speak(cleanWord);
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
        if (btnPrev) {
            btnPrev.disabled = (currentPage === 0);
            btnPrev.style.opacity = (currentPage === 0) ? 0.5 : 1;
        }
        if (btnNext) {
            btnNext.innerText = (currentPage === currentStoryData.length - 1) ? "Finish! 🏆" : "Next ➡";
        }
    }

    // --- KARAOKE FEATURE ---
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
            const textToSpeak = span.dataset.clean;

            span.classList.add('word-reading');

            GameBridge.speak(textToSpeak, () => {
                span.classList.remove('word-reading');
                index++;
                setTimeout(speakNextWord, 50);
            });
        }
        speakNextWord();
    };

    window.readPage = window.readPageKaraoke;

    // --- INTERACTIVE IMAGE ---
    window.interactWithImage = function() {
        if (phraseDeck.length === 0) shuffleDeck();
        const smartPhrase = phraseDeck.pop();
        
        GameBridge.speak(smartPhrase);
        
        const imgFrame = document.querySelector('.image-frame');
        if(imgFrame) {
            imgFrame.style.transform = "rotate(5deg) scale(1.05)";
            setTimeout(() => imgFrame.style.transform = "rotate(-2deg) scale(1)", 300);
        }
    };

    // --- NAVIGATION & VIDEO ENDING ---
    window.nextPage = function() {
        isReading = false; 
        
        if (currentPage < currentStoryData.length - 1) {
            currentPage++;
            renderPage();
            GameBridge.playAudio('correct');
        } else {
            // Pick the video based on the active level
            let videoPath = '';
            
            if (currentActiveLevel === 'level1') {
                videoPath = 'assets/videos/cat_rat_end.mp4';
            } else if (currentActiveLevel === 'level2') {
                videoPath = 'assets/videos/dog_friends_end.mp4';
            }

            // Play the video reward!
            GameBridge.celebrate("The End! Great Reading!", videoPath);
            GameBridge.saveScore({ score: 100, duration: 0, mistakes: 0 });
        }
    };

    window.prevPage = function() {
        isReading = false;
        if (currentPage > 0) {
            currentPage--;
            renderPage();
        }
    };
})();