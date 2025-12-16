/* games/cat-rat-reader/game.js */
(function() {
    let currentPage = 0;
    let currentStoryData = [];

    // Define multiple stories (Levels)
    const library = {
        'level1': [
            {
                image: "games/cat-rat-reader/images/image_0.png",
                text: "Look at the cat.\nThe cat is fat."
            },
            {
                image: "games/cat-rat-reader/images/image_1.png",
                text: "The cat has a hat.\nHe likes that hat."
            },
            {
                image: "games/cat-rat-reader/images/image_2.png",
                text: "The cat sat.\nHe sat on the mat."
            },
            {
                image: "games/cat-rat-reader/images/image_3.png",
                text: "Look at the rat.\nThe rat sees the cat."
            },
            {
                image: "games/cat-rat-reader/images/image_4.png",
                text: "They nap on the mat.\nJust like that!"
            }
        ],
        'level2': [
            // Ensure you have uploaded the dog images for these to work
            {
                image: "games/cat-rat-reader/images/dog_1.png",
                text: "See the dog.\nThe dog is big."
            },
            {
                image: "games/cat-rat-reader/images/dog_2.png",
                text: "The dog has a ball.\nHe likes the ball."
            },
            {
                image: "games/cat-rat-reader/images/dog_3.png",
                text: "The dog sees a pig.\nThe pig is pink."
            },
            {
                image: "games/cat-rat-reader/images/dog_4.png",
                text: "They run in the sun.\nRun dog run!"
            },
            {
                image: "games/cat-rat-reader/images/dog_5.png",
                text: "The pig and dog sit.\nThey are friends."
            }
        ]
    };

    document.addEventListener('DOMContentLoaded', () => {
        const instructionsText = "Pick a story to read! Tap words to hear them.";

        GameBridge.setupGame({
            instructions: instructionsText,
            // 1. SILENCE THE CLICK: This prevents GameBridge from speaking when you click the button
            speakInstruction: " ", 
            
            levels: [
                { id: 'level1', label: 'The Cat & Rat 🐱' },
                { id: 'level2', label: 'The Dog & Friends 🐶' }
            ],
            onStart: (levelId) => {
                currentStoryData = library[levelId] || library['level1'];
                currentPage = 0;
                renderPage();
            }
        });

        // 2. SPEAK NOW: Trigger speech immediately so they hear it while choosing
        setTimeout(() => {
            GameBridge.speak(instructionsText);
        }, 500);
    });

    window.renderPage = function() {
        const page = currentStoryData[currentPage];
        
        // Update Image
        const imgEl = document.getElementById('story-image');
        if (imgEl) {
            imgEl.src = page.image;
        }

        // Process Text into clickable spans
        const textContainer = document.getElementById('story-text');
        if (textContainer) {
            textContainer.innerHTML = ''; // Clear old text

            // Split lines to keep structure
            const lines = page.text.split('\n');
            lines.forEach(lineText => {
                const lineDiv = document.createElement('div');
                
                // Split words
                const words = lineText.split(' ');
                words.forEach(word => {
                    const span = document.createElement('span');
                    span.innerText = word + ' ';
                    span.className = 'word-interactive';
                    
                    // Clean word for TTS (remove punctuation)
                    const speakWord = word.replace(/[.,!?'"]/g, "");
                    
                    span.onclick = () => {
                        GameBridge.speak(speakWord);
                        span.style.color = '#e74c3c'; // Highlight red briefly
                        setTimeout(() => span.style.color = '', 500);
                    };
                    
                    lineDiv.appendChild(span);
                });
                textContainer.appendChild(lineDiv);
            });
        }

        // Update Buttons
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        
        if (btnPrev) btnPrev.disabled = (currentPage === 0);
        
        if (btnNext) {
            btnNext.innerText = (currentPage === currentStoryData.length - 1) ? "Finish" : "Next ➡";
        }
    };

    window.nextPage = function() {
        if (currentPage < currentStoryData.length - 1) {
            currentPage++;
            renderPage();
            GameBridge.playAudio('correct'); // Page turn sound
        } else {
            GameBridge.celebrate("The End! Great Reading!");
            GameBridge.saveScore({ score: 100, duration: 0, mistakes: 0 });
        }
    };

    window.prevPage = function() {
        if (currentPage > 0) {
            currentPage--;
            renderPage();
        }
    };

    window.readPage = function() {
        GameBridge.speak(currentStoryData[currentPage].text);
    };
})();