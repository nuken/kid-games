let score = 0;
let questionsAnswered = 0;
const totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;
let currentCorrectAnswer = "";

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

let availableWords = [];
let availableSentences = [];

document.addEventListener('DOMContentLoaded', () => {
    const screen = document.getElementById('signal-screen');
    if(screen) {
        screen.onclick = () => {
            if(currentCorrectAnswer) GameBridge.speak(currentCorrectAnswer);
        };
    }

    GameBridge.setupGame({
        instructions: "Decode the signal! Listen and match the word.",
        speakInstruction: "Listen closely and decode the signal.",
        levels: [
            { id: 1, label: "Cadet (Sight Words)" },
            { id: 2, label: "Commander (Sentences)" }
        ],
        onStart: (level) => {
            difficulty = level;
            // Initialize decks
            availableWords = [...sightWords];
            availableSentences = [...sentences];
            startTime = Date.now();
            spawnQuestion();
        }
    });
});

function spawnQuestion() {
    const controls = document.getElementById('frequency-controls');
    const screenText = document.getElementById('question-text');
    controls.innerHTML = "";
    document.getElementById('message').innerText = "";

    if (difficulty === 1) {
        // --- LEVEL 1 LOGIC (Sight Words) ---
        if (availableWords.length === 0) availableWords = [...sightWords];
        
        const idx = Math.floor(Math.random() * availableWords.length);
        currentCorrectAnswer = availableWords[idx];
        availableWords.splice(idx, 1);

        screenText.innerText = currentCorrectAnswer;
        GameBridge.speak(currentCorrectAnswer);

        // Hide word after delay to test memory
        setTimeout(() => {
            screenText.innerText = "🔍 SIGNAL LOST";
            let opts = [currentCorrectAnswer];
            // Fill distractors
            while(opts.length < 3) {
                let w = sightWords[Math.floor(Math.random()*sightWords.length)];
                if(!opts.includes(w)) opts.push(w);
            }
            createButtons(opts.sort(() => Math.random()-0.5));
        }, 2000);

    } else {
        // --- LEVEL 2 LOGIC (Sentences) ---
        if (availableSentences.length === 0) availableSentences = [...sentences];
        
        const idx = Math.floor(Math.random() * availableSentences.length);
        const q = availableSentences[idx];
        availableSentences.splice(idx, 1);

        currentCorrectAnswer = q.answer;
        screenText.innerText = q.text;
        
        // Speak sentence with "blank"
        GameBridge.speak(q.text.replace("___", "blank"));
        
        createButtons(q.options.sort(() => Math.random()-0.5));
    }
}

function createButtons(opts) {
    const controls = document.getElementById('frequency-controls');
    opts.forEach(opt => {
        const btn = document.createElement('button');
        btn.className = 'freq-btn';
        btn.innerText = opt;
        btn.onclick = () => checkAnswer(opt, btn);
        controls.appendChild(btn);
    });
}

function checkAnswer(ans, btn) {
    if (ans === currentCorrectAnswer) {
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);
        
        // Show correct answer on screen
        if (difficulty === 1) {
            document.getElementById('question-text').innerText = currentCorrectAnswer;
        } else {
            // Replace the blank in the sentence
            const currentText = document.getElementById('question-text').innerText;
            document.getElementById('question-text').innerText = currentText.replace("___", currentCorrectAnswer);
        }
        
        btn.style.borderColor = "var(--primary-btn)";
        btn.style.color = "var(--primary-btn)";
        
        GameBridge.celebrate("Signal Locked!");
        
        if (questionsAnswered >= totalQuestions) {
            GameBridge.saveScore({ score: score, duration: Math.floor((Date.now() - startTime)/1000) });
        } else {
            setTimeout(spawnQuestion, 1500);
        }
    } else {
        btn.style.borderColor = "var(--danger-btn)";
        GameBridge.speak("Signal Lost. Try again.");
    }
}