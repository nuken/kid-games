/* games/cosmic-signal/game.js */

let score = 0;
let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;
let currentCorrectAnswer = "";

const USER_ID = window.gameData ? window.gameData.userId : 1;
const GAME_ID = 4; // Cosmic Signal

// DOM Elements
const screenText = document.getElementById('question-text');
const signalScreen = document.getElementById('signal-screen');
const controls = document.getElementById('frequency-controls');
const message = document.getElementById('message');
const scoreDisplay = document.getElementById('score');

// --- DATA: EXPANDED 1ST GRADE SIGHT WORDS ---
const sightWords = [
    "the", "of", "and", "a", "to", "in", "is", "you", "that", "it",
    "he", "was", "for", "on", "are", "as", "with", "his", "they", "at",
    "be", "this", "have", "from", "or", "one", "had", "by", "word", "but",
    "not", "what", "all", "were", "we", "when", "your", "can", "said", "there",
    "use", "an", "each", "which", "she", "do", "how", "their", "if", "will",
    "up", "other", "about", "out", "many", "then", "them", "these", "so",
    "some", "her", "would", "make", "like", "him", "into", "time", "has",
    "look", "two", "more", "write", "go", "see", "number", "no", "way",
    "could", "people", "my", "than", "first", "water", "been", "called",
    "who", "oil", "its", "now", "find", "long", "down", "day", "did", "get",
    "come", "made", "may", "part", "over", "new", "sound", "take", "only",
    "little", "work", "know", "place", "year", "live", "me", "back", "give",
    "most", "very", "after", "thing", "our", "just", "name", "good", "sentence",
    "man", "think", "say", "great", "where", "help", "through", "much",
    "before", "line", "right", "too", "mean", "old", "any", "same", "tell",
    "boy", "follow", "came", "want", "show", "also", "around", "form",
    "three", "small", "set", "put", "end", "does", "another", "well",
    "large", "must", "big", "even", "such", "because", "turn", "here",
    "why", "ask", "went", "men", "read", "need", "land", "different",
    "home", "us", "move", "try", "kind", "hand", "picture", "again",
    "change", "off", "play", "spell", "air", "away", "animal", "house",
    "point", "page", "letter", "mother", "answer", "found", "study",
    "still", "learn", "should", "America", "world"
];

// --- DATA: SENTENCES (Context Clues) ---
const sentences = [
    { text: "The cat sat on the ___.", answer: "mat", options: ["mat", "car", "sky", "run"] },
    { text: "I like to ___ pizza.", answer: "eat", options: ["eat", "fly", "jump", "blue"] },
    { text: "The sky is ___.", answer: "blue", options: ["blue", "green", "loud", "soft"] },
    { text: "We go to ___ every day.", answer: "school", options: ["school", "moon", "fish", "tree"] },
    { text: "Can you ___ me?", answer: "help", options: ["help", "run", "sad", "big"] },
    { text: "The dog can ___ fast.", answer: "run", options: ["run", "sit", "red", "hot"] },
    { text: "She has a big ___.", answer: "smile", options: ["smile", "run", "is", "the"] },
    { text: "He is my best ___.", answer: "friend", options: ["friend", "car", "tree", "sky"] },
    { text: "Look at the ___ stars.", answer: "bright", options: ["bright", "loud", "run", "eat"] },
    { text: "I am ___ years old.", answer: "seven", options: ["seven", "blue", "cat", "run"] },
    { text: "We play ___ school.", answer: "after", options: ["after", "cat", "red", "run"] },
    { text: "Can we play ___?", answer: "again", options: ["again", "blue", "stop", "car"] },
    { text: "Do you have ___ gum?", answer: "any", options: ["any", "dog", "sky", "big"] },
    { text: "Go ___ your mom.", answer: "ask", options: ["ask", "run", "fly", "sit"] },
    { text: "I ___ run very fast.", answer: "could", options: ["could", "red", "box", "sun"] },
    { text: "I like ___ color.", answer: "every", options: ["every", "stop", "car", "top"] },
    { text: "Birds can ___ high.", answer: "fly", options: ["fly", "swim", "eat", "run"] },
    { text: "This gift is ___ me.", answer: "from", options: ["from", "cat", "dog", "run"] },
    { text: "Please ___ it to me.", answer: "give", options: ["give", "stop", "red", "fly"] },
    { text: "Where are you ___?", answer: "going", options: ["going", "blue", "car", "sit"] },
    { text: "I like ___ dress.", answer: "her", options: ["her", "him", "he", "she"] },
    { text: "I played with ___.", answer: "him", options: ["him", "now", "sit", "hand"] },
    { text: "That is ___ ball.", answer: "his", options: ["his", "me", "I", "we"] },
    { text: "___ are you today?", answer: "How", options: ["How", "Who", "What", "Why"] },
    { text: "I ___ got home.", answer: "just", options: ["just", "run", "fly", "red"] },
    { text: "I ___ the answer.", answer: "know", options: ["know", "no", "now", "not"] },
    { text: "Please ___ me go.", answer: "let", options: ["let", "run", "fly", "sit"] },
    { text: "I ___ in a house.", answer: "live", options: ["live", "love", "like", "look"] },
    { text: "___ I have some?", answer: "May", options: ["May", "Can", "Will", "Do"] },
    { text: "A cup ___ milk.", answer: "of", options: ["of", "off", "on", "in"] },
    { text: "How ___ are you?", answer: "old", options: ["old", "cold", "hold", "gold"] },
    { text: "___ upon a time.", answer: "Once", options: ["Once", "One", "On", "Only"] },
    { text: "Please ___ the door.", answer: "open", options: ["open", "run", "happy", "lock"] },
    { text: "Jump ___ the log.", answer: "over", options: ["over", "under", "in", "on"] },
    { text: "___ it away.", answer: "Put", options: ["Put", "Pat", "Pet", "Pot"] },
    { text: "The ball is ___.", answer: "round", options: ["round", "square", "flat", "long"] },
    { text: "I want ___ cake.", answer: "some", options: ["some", "come", "same", "sum"] },
    { text: "Please ___ talking.", answer: "stop", options: ["stop", "go", "run", "fly"] },
    { text: "___ my hand.", answer: "Take", options: ["Take", "Make", "Bake", "Lake"] },
    { text: "___ you mom.", answer: "Thank", options: ["Thank", "Think", "Tank", "Sank"] },
    { text: "I play with ___.", answer: "them", options: ["them", "then", "they", "that"] },
    { text: "We ate ___ slept.", answer: "then", options: ["then", "them", "when", "ten"] },
    { text: "I ___ it is red.", answer: "think", options: ["think", "thank", "thing", "thin"] },
    { text: "Let's go for a ___.", answer: "walk", options: ["walk", "talk", "cat", "fly"] },
    { text: "We ___ happy.", answer: "were", options: ["were", "where", "we", "was"] },
    { text: "___ is lunch?", answer: "When", options: ["When", "Then", "Where", "Who"] }
];

// --- LOGIC: Smart Shuffle (No Repeats) ---
let availableWords = [];
let availableSentences = [];

// --- 1. GAME INITIALIZATION ---
function initGame(level = 1) {
    difficulty = level;
    document.getElementById('start-overlay').style.display = 'none';
    if (window.voiceList && window.voiceList.length === 0) window.loadVoices();

    // REMOVED: Automatic "Cadet Mode..." speech. Now starts silently until button press.

    startTime = Date.now();
    questionsAnswered = 0;
    score = 0;
    scoreDisplay.innerText = score;

    // RESET THE DECKS
    availableWords = [...sightWords];
    availableSentences = [...sentences];

    spawnQuestion();
}

function playInstructions() {
    if (difficulty === 1) {
        window.speakText("Look at the word on the screen. Remember it. Then click the matching button.");
    } else {
        window.speakText("Read the sentence and click the word that fits in the blank.");
    }
}

// --- 2. SPAWN QUESTION ---
function spawnQuestion() {
    controls.innerHTML = ""; // Clear buttons
    message.innerText = "";
    screenText.style.color = "var(--hologram-cyan)";
    signalScreen.style.cursor = "pointer"; 

    if (difficulty === 1) {
        // --- LEVEL 1 LOGIC ---
        // 1. Check if deck is empty, if so, reshuffle
        if (availableWords.length === 0) {
            availableWords = [...sightWords];
        }

        // 2. Pick random INDEX from available
        const randIndex = Math.floor(Math.random() * availableWords.length);
        
        // 3. Set answer and REMOVE from deck (Smart Shuffle)
        currentCorrectAnswer = availableWords[randIndex];
        availableWords.splice(randIndex, 1);

        // A. Show the word BIG
        screenText.innerText = currentCorrectAnswer;
        
        // B. Speak it
        window.speakText(currentCorrectAnswer);

        // C. Wait 1.5s, then Hide it and Show Options
        setTimeout(() => {
            screenText.innerText = "🔍 FIND IT"; // Visual Cue
            
            let options = [currentCorrectAnswer];
            // Fill distractors from the FULL list (okay to repeat distractors)
            while (options.length < 3) {
                let w = sightWords[Math.floor(Math.random() * sightWords.length)];
                if (!options.includes(w)) options.push(w);
            }
            createButtons(shuffleArray(options));
        }, 3000);

    } else {
        // --- LEVEL 2 LOGIC ---
        // 1. Check if deck is empty, if so, reshuffle
        if (availableSentences.length === 0) {
            availableSentences = [...sentences];
        }

        // 2. Pick random INDEX
        const randIndex = Math.floor(Math.random() * availableSentences.length);
        
        // 3. Get Question and REMOVE from deck
        let q = availableSentences[randIndex];
        availableSentences.splice(randIndex, 1);
        
        currentCorrectAnswer = q.answer;
        screenText.innerText = q.text;

        // Speak the sentence with "blank"
        let spokenText = q.text.replace("___", "blank");
        setTimeout(() => window.speakText(spokenText), 1000);

        createButtons(shuffleArray(q.options));
    }
}

function createButtons(options) {
    options.forEach(opt => {
        let btn = document.createElement('button');
        btn.className = 'freq-btn';
        btn.innerText = opt;
        btn.onclick = () => checkAnswer(opt, btn);
        controls.appendChild(btn);
    });
}

// --- 3. CHECK ANSWER ---
function checkAnswer(selected, btnElement) {
    signalScreen.style.cursor = "default"; 
    
    if (selected === currentCorrectAnswer) {
        // CORRECT
        score += 10;
        scoreDisplay.innerText = score;
        message.innerText = "SIGNAL LOCKED!";
        message.style.color = "#2ecc71";
        btnElement.style.background = "#27ae60";
        btnElement.style.borderColor = "#2ecc71";

        if (difficulty === 2) {
            // Show full sentence
            screenText.innerText = screenText.innerText.replace("___", currentCorrectAnswer);
            window.speakText(screenText.innerText); 
        } else {
            // Show the word again
            screenText.innerText = currentCorrectAnswer;
            window.speakText("Correct! " + currentCorrectAnswer);
        }

        if (window.playConfettiEffect) window.playConfettiEffect();

        questionsAnswered++;
        if (questionsAnswered >= totalQuestions) {
            setTimeout(endGame, 2000);
        } else {
            setTimeout(spawnQuestion, 2000);
        }

    } else {
        // INCORRECT
        message.innerText = "SIGNAL LOST!";
        message.style.color = "#e74c3c";
        btnElement.style.background = "#c0392b";
        btnElement.style.borderColor = "#e74c3c";
        window.speakText("Try again.");
        signalScreen.style.cursor = "pointer"; 
    }
}

// --- REPEAT AUDIO LISTENER ---
if (signalScreen) {
    signalScreen.addEventListener('click', () => {
        if (!currentCorrectAnswer || message.innerText === "SIGNAL LOCKED!") return;

        // Visual Feedback (Flash White)
        signalScreen.animate([
            { borderColor: 'var(--hologram-cyan)' },
            { borderColor: '#ffffff' },
            { borderColor: 'var(--hologram-cyan)' }
        ], { duration: 300 });

        if (difficulty === 1) {
            window.speakText(currentCorrectAnswer);
        } else {
            let text = screenText.innerText.replace("___", "blank");
            window.speakText(text);
        }
    });
}

// --- UTILS ---
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function endGame() {
    document.body.innerHTML = "<h1>TRANSMISSION COMPLETE!</h1><p>Saving Data...</p>";

    let duration = Math.floor((Date.now() - startTime) / 1000);

    fetch('../../api/save_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            game_id: GAME_ID,
            score: score,
            duration: duration
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.new_badges && data.new_badges.length > 0) {
                let badgeNames = data.new_badges.map(b => b.icon + " " + b.name).join("\n");
                alert("🌟 MISSION PATCH EARNED! 🌟\n\n" + badgeNames);
            }
            setTimeout(() => window.location.href = "../../index.php", 2000);
        })
        .catch(error => console.error('Error:', error));
}