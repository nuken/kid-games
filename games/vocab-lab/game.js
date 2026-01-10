/* games/vocab-lab/game.js */
(function() {
    let score = 0;
    let mistakes = 0;
    let questionCount = 0;
    let currentMode = 'synonym'; 
    let startTime = Date.now();
    
    // The "Deck" for the current session
    let questionDeck = [];

    // --- EXPANDED DATASETS ---
    const DATA = {
        synonym: [
            { q: "Happy", a: "Joyful", fake: ["Sad", "Angry"] },
            { q: "Big", a: "Huge", fake: ["Tiny", "Soft"] },
            { q: "Fast", a: "Quick", fake: ["Slow", "Lazy"] },
            { q: "Scared", a: "Afraid", fake: ["Brave", "Calm"] },
            { q: "Smart", a: "Clever", fake: ["Silly", "Cold"] },
            { q: "Start", a: "Begin", fake: ["End", "Stop"] },
            { q: "Loud", a: "Noisy", fake: ["Quiet", "Small"] },
            { q: "Look", a: "See", fake: ["Hear", "Touch"] },
            { q: "Listen", a: "Hear", fake: ["Smell", "Run"] },
            { q: "Jump", a: "Leap", fake: ["Sleep", "Sit"] },
            { q: "Stone", a: "Rock", fake: ["Paper", "Water"] },
            { q: "Gift", a: "Present", fake: ["Box", "Toy"] },
            { q: "Small", a: "Tiny", fake: ["Huge", "Tall"] },
            { q: "Mad", a: "Angry", fake: ["Glad", "Nice"] },
            { q: "Run", a: "Sprint", fake: ["Walk", "Crawl"] },
            { q: "Beautiful", a: "Pretty", fake: ["Ugly", "Messy"] },
            { q: "Easy", a: "Simple", fake: ["Hard", "Rough"] },
            { q: "Finish", a: "End", fake: ["Start", "Go"] }
        ],
        antonym: [
            { q: "Hot", a: "Cold", fake: ["Warm", "Red"] },
            { q: "Up", a: "Down", fake: ["Left", "High"] },
            { q: "Hard", a: "Soft", fake: ["Solid", "Rock"] },
            { q: "Dark", a: "Light", fake: ["Night", "Black"] },
            { q: "Empty", a: "Full", fake: ["Gone", "Done"] },
            { q: "Wet", a: "Dry", fake: ["Water", "Soaked"] },
            { q: "Friend", a: "Enemy", fake: ["Pal", "Buddy"] },
            { q: "Clean", a: "Dirty", fake: ["Neat", "New"] },
            { q: "Open", a: "Closed", fake: ["Wide", "Locked"] },
            { q: "Stop", a: "Go", fake: ["Wait", "Slow"] },
            { q: "Asleep", a: "Awake", fake: ["Tired", "Nap"] },
            { q: "Win", a: "Lose", fake: ["Game", "Play"] },
            { q: "Love", a: "Hate", fake: ["Like", "Hug"] },
            { q: "Give", a: "Take", fake: ["Share", "Keep"] },
            { q: "Old", a: "New", fake: ["Age", "Year"] },
            { q: "Rich", a: "Poor", fake: ["Money", "Gold"] },
            { q: "Top", a: "Bottom", fake: ["Side", "Over"] },
            { q: "Left", a: "Right", fake: ["Wrong", "Up"] }
        ],
        definition: [
            { q: "To walk quietly", a: "Tiptoe", fake: ["Stomp", "Run"] },
            { q: "A place to read books", a: "Library", fake: ["Park", "Kitchen"] },
            { q: "Frozen water", a: "Ice", fake: ["Steam", "Rain"] },
            { q: "Yellow fruit", a: "Banana", fake: ["Apple", "Grape"] },
            { q: "Opposite of night", a: "Day", fake: ["Moon", "Dark"] },
            { q: "Very tired", a: "Exhausted", fake: ["Hyper", "Awake"] },
            { q: "A baby cat", a: "Kitten", fake: ["Puppy", "Cub"] },
            { q: "A baby dog", a: "Puppy", fake: ["Kitten", "Calf"] },
            { q: "What you wear on feet", a: "Shoes", fake: ["Gloves", "Hats"] },
            { q: "Used to write", a: "Pencil", fake: ["Spoon", "Key"] },
            { q: "Red fruit", a: "Apple", fake: ["Banana", "Lime"] },
            { q: "Orange vegetable", a: "Carrot", fake: ["Pea", "Corn"] },
            { q: "King's hat", a: "Crown", fake: ["Cap", "Helmet"] },
            { q: "It flies in the sky", a: "Airplane", fake: ["Car", "Boat"] },
            { q: "Number after nine", a: "Ten", fake: ["Eight", "Six"] }
        ]
    };

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Match the words in the lab!",
            levels: [
                { id: 'synonym', label: "Synonyms (Same)" },
                { id: 'antonym', label: "Antonyms (Opposite)" },
                { id: 'definition', label: "Definitions" }
            ],
            onStart: (modeId) => {
                currentMode = modeId;
                buildDeck(); // Shuffle cards for this mode
                startGame();
            }
        });
    });

    // --- DECK LOGIC ---
    function buildDeck() {
        // Clone the data array so we don't mess up the original
        let cards = [...DATA[currentMode]];
        
        // Fisher-Yates Shuffle
        for (let i = cards.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [cards[i], cards[j]] = [cards[j], cards[i]];
        }
        
        questionDeck = cards;
    }

    function drawCard() {
        if (questionDeck.length === 0) {
            buildDeck(); // Reshuffle if we ran out
        }
        return questionDeck.pop();
    }
    // ------------------

    function startGame() {
        score = 0;
        mistakes = 0;
        questionCount = 0;
        startTime = Date.now();
        nextQuestion();
    }

    function nextQuestion() {
        // Increased round length slightly since we have more data
        if (questionCount >= 8) { 
            finishGame();
            return;
        }

        // 1. Draw from Smart Deck
        const item = drawCard();

        // 2. Set Labels
        let labelText = "Find the SYNONYM for:";
        if (currentMode === 'antonym') labelText = "Find the OPPOSITE of:";
        if (currentMode === 'definition') labelText = "What matches this?";

        document.getElementById('clue-label').innerText = labelText;
        document.getElementById('clue-text').innerText = item.q;

        // TTS Hook
        if (currentMode === 'definition') {
            GameBridge.speakNow(item.q);
        } else {
            GameBridge.speakNow(labelText + " " + item.q);
        }

        // 3. Prepare Options
        let options = [item.a, ...item.fake];
        options.sort(() => Math.random() - 0.5);

        // 4. Render Beakers
        const shelf = document.getElementById('shelf');
        shelf.innerHTML = '';
        
        const colors = ['liquid-red', 'liquid-green', 'liquid-purple'];

        options.forEach((opt, index) => {
            const btn = document.createElement('div');
            // Cycle through colors
            btn.className = `beaker-btn ${colors[index % colors.length]}`; 
            btn.innerText = opt;
            btn.onclick = () => handleAnswer(opt, item.a);
            shelf.appendChild(btn);
        });
    }

    function handleAnswer(selected, correct) {
        if (selected === correct) {
            GameBridge.handleCorrect();
            score += 12.5; // 8 questions * 12.5 = 100
            questionCount++;
            setTimeout(nextQuestion, 1000); 
        } else {
            GameBridge.handleWrong();
            mistakes++;
        }
    }

    function finishGame() {
        GameBridge.celebrate("Excellent Experiment!");
        
        GameBridge.saveScore({
            score: Math.min(100, Math.ceil(score)),
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: mistakes
        });
    }
})();