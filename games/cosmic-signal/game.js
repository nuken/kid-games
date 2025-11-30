/* games/cosmic-signal/game.js */

let score = 0;
let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;
let currentCorrectAnswer = "";

const USER_ID = window.gameData ? window.gameData.userId : 1;
const GAME_ID = 4; // New ID for Cosmic Signal

// DOM Elements
const screenText = document.getElementById('question-text');
const controls = document.getElementById('frequency-controls');
const message = document.getElementById('message');
const scoreDisplay = document.getElementById('score');

// --- DATA: SIGHT WORDS & SENTENCES ---
const sightWords = [
    "the", "of", "and", "a", "to", "in", "is", "you", "that", "it",
    "he", "was", "for", "on", "are", "as", "with", "his", "they", "at",
    "be", "this", "have", "from", "or", "one", "had", "by", "word", "but",
    "not", "what", "all", "were", "we", "when", "your", "can", "said", "there",
    "use", "an", "each", "which", "she", "do", "how", "their", "if", "will"
];

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
    { text: "I am ___ years old.", answer: "seven", options: ["seven", "blue", "cat", "run"] }
];

// --- 1. GAME INITIALIZATION ---
function initGame(level = 1) {
    difficulty = level;
    document.getElementById('start-overlay').style.display = 'none';
    if (window.voiceList && window.voiceList.length === 0) window.loadVoices();

    if (difficulty === 2) {
        setTimeout(() => window.speakText("Commander Mode. Decode the message. Fill in the blank."), 1000);
    } else {
        setTimeout(() => window.speakText("Cadet Mode. Listen to the signal. Match the word."), 1000);
    }

    startTime = Date.now();
    questionsAnswered = 0;
    score = 0;
    scoreDisplay.innerText = score;

    spawnQuestion();
}

function playInstructions() {
    if (difficulty === 1) {
        window.speakText("Listen to the word I say, then click the matching button.");
    } else {
        window.speakText("Read the sentence and click the word that fits in the blank.");
    }
}

// --- 2. SPAWN QUESTION ---
function spawnQuestion() {
    controls.innerHTML = ""; // Clear buttons
    message.innerText = "";
    screenText.style.color = "var(--hologram-cyan)";

    if (difficulty === 1) {
        // LEVEL 1: WORD MATCH
        // Pick a random word
        currentCorrectAnswer = sightWords[Math.floor(Math.random() * sightWords.length)];

        // Generate distractors
        let options = [currentCorrectAnswer];
        while (options.length < 3) {
            let w = sightWords[Math.floor(Math.random() * sightWords.length)];
            if (!options.includes(w)) options.push(w);
        }
        options = shuffleArray(options);

        // Display "SIGNAL RECEIVED" or "LISTEN"
        screenText.innerText = "🔊 LISTEN";

        // Speak the word
        // Speak the word
        setTimeout(() => window.speakText(currentCorrectAnswer), 1000);

        createButtons(options);

    } else {
        // LEVEL 2: SENTENCE COMPLETION
        let q = sentences[Math.floor(Math.random() * sentences.length)];
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
    if (selected === currentCorrectAnswer) {
        // CORRECT
        score += 10;
        scoreDisplay.innerText = score;
        message.innerText = "SIGNAL LOCKED!";
        message.style.color = "#2ecc71";
        btnElement.style.background = "#27ae60";
        btnElement.style.borderColor = "#2ecc71";

        if (difficulty === 2) {
            // Fill in the blank visually
            screenText.innerText = screenText.innerText.replace("___", currentCorrectAnswer);
            window.speakText(screenText.innerText); // Read full sentence
        } else {
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
    }
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
