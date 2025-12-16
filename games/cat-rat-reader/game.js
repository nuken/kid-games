/* games/cat-rat-reader/game.js */
(function() {
    let currentPage = 0;

    // Data extracted from your index.html
    const storyData = [
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
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Read the story! Tap any word to hear it.",
            // --- FIX: Added 'levels' to create the Start button ---
            levels: [
                { id: 'start', label: 'Start Reading 📖' }
            ],
            onStart: () => {
                currentPage = 0;
                renderPage();
            }
        });
    });

    window.renderPage = function() {
        const page = storyData[currentPage];
        
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
        if (btnNext) btnNext.innerText = (currentPage === storyData.length - 1) ? "Finish" : "Next ➡";
    };

    window.nextPage = function() {
        if (currentPage < storyData.length - 1) {
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
        GameBridge.speak(storyData[currentPage].text);
    };
})();