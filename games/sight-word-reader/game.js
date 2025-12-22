/* games/sight-word-reader/game.js */
(function() {
    // --- VARIABLES ---
    let currentPage = 0;
    let currentStoryData = [];
    let isReading = false; // Prevent double clicks while reading
    let currentActiveLevel = 'level1'; // Tracks which story we are on (for the video ending)
    
    // --- SMART ENCOURAGEMENT SHUFFLE ---
    // Phrases to say when the child clicks the main picture
    const imagePhrases = [
        "You are a great reader!", 
        "Look at that picture!", 
        "What is happening here?", 
        "Keep going!",
        "Sight words are fun!",
        "Nice job!",
        "Wow! Look at that!"
    ];
    
    let phraseDeck = [];

    // Helper: Shuffles the phrases so they don't repeat until used up
    function shuffleDeck() {
        phraseDeck = [...imagePhrases];
        for (let i = phraseDeck.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [phraseDeck[i], phraseDeck[j]] = [phraseDeck[j], phraseDeck[i]];
        }
    }

    // --- STORY LIBRARY ---
    // Contains the text and image paths for each level
    const library = {
        'level1': [
            { image: "games/sight-word-reader/images/bird_1.png", text: "Once there was a little bird." },
            { image: "games/sight-word-reader/images/bird_2.png", text: "She wanted to fly over the trees." },
            { image: "games/sight-word-reader/images/bird_3.png", text: "She asked her mom,\nHow do I fly?" },
            { image: "games/sight-word-reader/images/bird_4.png", text: "Just open your wings and go!" },
            { image: "games/sight-word-reader/images/bird_5.png", text: "She flew over the garden.\nShe was happy." }
        ],
        'level2': [
            { image: "games/sight-word-reader/images/box_1.png", text: "I found an old box in the dirt." },
            { image: "games/sight-word-reader/images/box_2.png", text: "It was very cold and green." },
            { image: "games/sight-word-reader/images/box_3.png", text: "I did not have the right key." },
            { image: "games/sight-word-reader/images/box_4.png", text: "I made a wish upon a star." },
            { image: "games/sight-word-reader/images/box_5.png", text: "Then I found the key under a rock!" }
        ]
    };

    // --- INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', () => {
        const instructionsText = "Read the story! Tap words to hear them.";
        shuffleDeck(); // Init the shuffle deck

        GameBridge.setupGame({
            instructions: instructionsText,
            speakInstruction: " ", // We manually speak below to time it better
            levels: [
                { id: 'level1', label: 'The Little Bird 🐦 (Gr 1)' },
                { id: 'level2', label: 'The Magic Box 📦 (Gr 2)' }
            ],
            onStart: (levelId) => {
                // Save the level ID so we know which video to play at the end
                currentActiveLevel = levelId;
                
                // Load the correct story data
                currentStoryData = library[levelId] || library['level1'];
                currentPage = 0;
                renderPage();
            }
        });

        // Delay the instruction speech slightly so it doesn't clash with the page load
        setTimeout(() => GameBridge.speak(instructionsText), 500);
    });

    // --- MAIN RENDER FUNCTION ---
    window.renderPage = function() {
        const page = currentStoryData[currentPage];
        isReading = false; // Reset reading state on page turn

        // 1. Update Progress Bar
        const progressPct = ((currentPage + 1) / currentStoryData.length) * 100;
        const pFill = document.getElementById('progress-fill');
        if (pFill) pFill.style.width = `${progressPct}%`;

        // 2. Update Image with Animation
        const imgEl = document.getElementById('story-image');
        if (imgEl) {
            imgEl.src = page.image;
            // Small "pop" animation
            imgEl.style.transform = "scale(0.9)";
            setTimeout(() => imgEl.style.transform = "scale(1)", 150);
        }

        // 3. Process Text into Interactive Spans
        const textContainer = document.getElementById('story-text');
        if (textContainer) {
            textContainer.innerHTML = '';
            
            let globalWordIndex = 0;
            const lines = page.text.split('\n'); // Preserve line breaks
            
            lines.forEach(lineText => {
                const lineDiv = document.createElement('div');
                const words = lineText.split(' ');
                
                words.forEach(word => {
                    const span = document.createElement('span');
                    span.innerText = word + ' '; 
                    span.className = 'word-interactive';
                    span.id = `word-${globalWordIndex}`; 
                    
                    // Clean punctuation for TTS (e.g., "fly?" -> "fly")
                    const cleanWord = word.replace(/[.,!?'"]/g, "");
                    span.dataset.clean = cleanWord; 
                    
                    // Click to speak individual word
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

    // --- BUTTON STATE UPDATE ---
    function updateButtons() {
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        
        if (btnPrev) {
            btnPrev.disabled = (currentPage === 0);
            btnPrev.style.opacity = (currentPage === 0) ? 0.5 : 1;
        }
        if (btnNext) {
            // Change button text on the last page
            btnNext.innerText = (currentPage === currentStoryData.length - 1) ? "Finish! 🏆" : "Next ➡";
        }
    }

    // --- KARAOKE READING FEATURE ---
    // Highlights words one by one as they are spoken
    window.readPageKaraoke = function() {
        if (isReading) return; // Don't start if already reading
        isReading = true;

        const page = currentStoryData[currentPage];
        const wordSpans = document.querySelectorAll('.word-interactive');
        let index = 0;

        function speakNextWord() {
            // Stop if finished or if the user turned the page
            if (index >= wordSpans.length || currentPage !== currentStoryData.indexOf(page)) {
                isReading = false;
                return;
            }

            const span = wordSpans[index];
            const textToSpeak = span.dataset.clean;

            // Highlight word
            span.classList.add('word-reading');

            // Speak word with callback to trigger next one
            GameBridge.speak(textToSpeak, () => {
                span.classList.remove('word-reading');
                index++;
                // Small delay for natural pacing
                setTimeout(speakNextWord, 50);
            });
        }
        speakNextWord();
    };

    // Compatibility Alias: Allows the button to call readPage() OR readPageKaraoke()
    window.readPage = window.readPageKaraoke;

    // --- INTERACTIVE IMAGE FEATURE ---
    // Says a random encouragement phrase when image is clicked
    window.interactWithImage = function() {
        if (phraseDeck.length === 0) shuffleDeck(); // Refill deck if empty
        const smartPhrase = phraseDeck.pop();
        
        GameBridge.speakNow(smartPhrase);
        
        // Visual bounce effect
        const imgFrame = document.querySelector('.image-frame');
        if(imgFrame) {
            imgFrame.style.transform = "rotate(5deg) scale(1.05)";
            setTimeout(() => imgFrame.style.transform = "rotate(-2deg) scale(1)", 300);
        }
    };

    // --- NAVIGATION ---
    window.nextPage = function() {
        isReading = false; // Stop reading if page changes
        
        if (currentPage < currentStoryData.length - 1) {
            currentPage++;
            renderPage();
            GameBridge.playAudio('correct');
        } else {
            // --- END OF STORY LOGIC ---
            // Choose the correct video based on the active level
            let videoPath = '';
            
            if (currentActiveLevel === 'level1') {
                videoPath = 'assets/videos/bird_end.mp4';
            } else if (currentActiveLevel === 'level2') {
                videoPath = 'assets/videos/box_end.mp4';
            }

            // Trigger celebration with the video
            GameBridge.celebrate("Amazing! You red the whole story!", videoPath);
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