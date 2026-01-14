/* games/memory-match/game.js */
(function() {
    // Game State
    let difficulty = 1;
    let cards = [];
    let hasFlippedCard = false;
    let lockBoard = false;
    let firstCard, secondCard;
    let matchesFound = 0;
    let moves = 0;
    let startTime = Date.now();
    let sessionMistakes = 0;

    // --- DATA SETS ---
    // Animals reused from Wild World assets
    const animalData = ['🦁','🐯','🐘','🐒','🐶','🐱','🐸','🐼','🐰','🐔'];

    // Standard Shape Emojis
    const shapeData = ['🟥','🔵','🔺','⭐','🔷','💜','🔶','⬛','🟢','🤍'];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Memorize the cards, then find the pairs!",
            levels: [
                { id: 1, label: "Animals" },
                { id: 2, label: "Shapes" }
            ],
            onStart: (level) => {
                difficulty = level;
                resetGame();
            }
        });
    });

    function resetGame() {
        const grid = document.getElementById('memory-grid');
        grid.innerHTML = '';

        matchesFound = 0;
        moves = 0;
        sessionMistakes = 0;
        startTime = Date.now();
        hasFlippedCard = false;
        // Lock the board initially for the preview phase
        lockBoard = true;
        firstCard = null;
        secondCard = null;

        updateStats();

        // 1. Select Data based on Level
        const sourceData = (difficulty === 1) ? animalData : shapeData;

        // 2. Create Pairs (8 pairs for 16 cards)
        const gameItems = sourceData.slice(0, 8);
        cards = [...gameItems, ...gameItems];

        // 3. Shuffle
        cards.sort(() => Math.random() - 0.5);

        // 4. Deal Cards
        cards.forEach(item => {
            const cardElement = createCard(item);

            // FEATURE ADDITION: Reveal immediately
            cardElement.classList.add('flipped');

            grid.appendChild(cardElement);
        });

        GameBridge.speak("Memorize the cards!");

        // 5. Hide cards after 3 seconds and start game
        setTimeout(() => {
            const allCards = document.querySelectorAll('.memory-card');
            allCards.forEach(card => {
                card.classList.remove('flipped');
            });

            lockBoard = false; // Unlock for gameplay
            GameBridge.speak("Go!");
        }, 3000); // 3000ms = 3 seconds
    }

    function createCard(content) {
        const card = document.createElement('div');
        card.classList.add('memory-card');
        card.dataset.content = content;

        // Front Face (Hidden Content)
        const front = document.createElement('div');
        front.classList.add('front-face');
        front.innerText = content;

        // Back Face (Visible Pattern)
        const back = document.createElement('div');
        back.classList.add('back-face');
        back.innerText = '?';

        card.appendChild(front);
        card.appendChild(back);

        card.addEventListener('click', flipCard);
        return card;
    }

    function flipCard() {
        if (lockBoard) return;
        if (this === firstCard) return;

        this.classList.add('flipped');

        // Play sound
        GameBridge.speakNow(this.dataset.content);

        if (!hasFlippedCard) {
            // First click
            hasFlippedCard = true;
            firstCard = this;
            return;
        }

        // Second click
        secondCard = this;
        incrementMove();
        checkForMatch();
    }

    function checkForMatch() {
        let isMatch = firstCard.dataset.content === secondCard.dataset.content;

        if (isMatch) {
            disableCards();
        } else {
            unflipCards();
        }
    }

    function disableCards() {
        // Match Found
        GameBridge.handleCorrectSilent();
        matchesFound++;
        updateStats();

        // Keep them flipped, then fade out
        setTimeout(() => {
            firstCard.classList.add('matched');
            secondCard.classList.add('matched');
            resetBoard();
            checkWinCondition();
        }, 500);
    }

    function unflipCards() {
        lockBoard = true;
        sessionMistakes++;
        GameBridge.handleWrong();

        setTimeout(() => {
            firstCard.classList.remove('flipped');
            secondCard.classList.remove('flipped');
            resetBoard();
        }, 1200);
    }

    function resetBoard() {
        [hasFlippedCard, lockBoard] = [false, false];
        [firstCard, secondCard] = [null, null];
    }

    function incrementMove() {
        moves++;
        updateStats();
    }

    function updateStats() {
        document.getElementById('move-count').innerText = moves;
        document.getElementById('match-count').innerText = matchesFound;
    }

    function checkWinCondition() {
        if (matchesFound === 8) {
            const duration = Math.floor((Date.now() - startTime) / 1000);

            GameBridge.celebrate("You found all the pairs!");

            GameBridge.saveScore({
                score: 100,
                duration: duration,
                mistakes: sessionMistakes
            });
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Tap two cards to find a match.");
    };
})();
