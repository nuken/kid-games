// --- Audio Setup ---
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
let currentWaveform = 'sine'; 

// --- Game State ---
let gameMode = 'free'; // 'free' or 'game'
let sequence = [];     // The computer's song
let userStep = 0;      // How far the user is in the sequence
let currentLevel = 1;
let isPlayingSequence = false;

const notes = [
    { note: 'C', freq: 261.63, type: 'white' },
    { note: 'C#', freq: 277.18, type: 'black' },
    { note: 'D', freq: 293.66, type: 'white' },
    { note: 'D#', freq: 311.13, type: 'black' },
    { note: 'E', freq: 329.63, type: 'white' },
    { note: 'F', freq: 349.23, type: 'white' },
    { note: 'F#', freq: 369.99, type: 'black' },
    { note: 'G', freq: 392.00, type: 'white' },
    { note: 'G#', freq: 415.30, type: 'black' },
    { note: 'A', freq: 440.00, type: 'white' },
    { note: 'A#', freq: 466.16, type: 'black' },
    { note: 'B', freq: 493.88, type: 'white' },
    { note: 'C2', freq: 523.25, type: 'white' }
];

// --- Initialization ---
const pianoContainer = document.getElementById('piano-container');

notes.forEach((n) => {
    const key = document.createElement('div');
    key.className = n.type === 'black' ? 'key sharp' : 'key';
    key.dataset.note = n.note;
    key.dataset.freq = n.freq;
    key.id = 'key-' + n.note; // Add ID for easy lookup
    
    if (n.type === 'white') key.innerText = n.note.replace('2', '');

    // Input Handlers
    const hitKey = () => handleInput(n);
    key.onmousedown = hitKey;
    key.ontouchstart = (e) => { e.preventDefault(); hitKey(); };

    pianoContainer.appendChild(key);
});

// --- Core Logic ---

function setGameMode(mode) {
    gameMode = mode;
    
    // UI Updates
    document.getElementById('btn-free').className = mode === 'free' ? 'btn btn-lg btn-primary' : 'btn btn-lg btn-secondary';
    document.getElementById('btn-game').className = mode === 'game' ? 'btn btn-lg btn-primary' : 'btn btn-lg btn-secondary';
    document.getElementById('game-status').style.display = mode === 'game' ? 'block' : 'none';
    
    if(mode === 'free') {
        showMessage("Play whatever you want!");
    } else {
        showMessage("Maestro Mode: Press 'Start Round'!");
        currentLevel = 1;
        updateLevelDisplay();
        sequence = [];
    }
}

function handleInput(noteObj) {
    if (isPlayingSequence) return; // Ignore clicks while computer is playing

    playNote(noteObj.freq, document.getElementById('key-' + noteObj.note));

    if (gameMode === 'game') {
        checkGameLogic(noteObj);
    }
}

function startMaestroRound() {
    // Generate a new note for the sequence
    const randomNote = notes[Math.floor(Math.random() * notes.length)];
    sequence.push(randomNote);
    
    userStep = 0;
    document.getElementById('btn-start-round').style.display = 'none'; // Hide button during round
    
    playSequence();
}

function playSequence() {
    isPlayingSequence = true;
    showMessage("Listen closely...");
    
    let i = 0;
    const interval = setInterval(() => {
        if (i >= sequence.length) {
            clearInterval(interval);
            isPlayingSequence = false;
            showMessage("Your turn!");
            return;
        }
        
        const note = sequence[i];
        playNote(note.freq, document.getElementById('key-' + note.note));
        i++;
    }, 1000); // 1 second gap between notes
}

function checkGameLogic(playedNote) {
    // Is the played note the correct one for this step?
    const targetNote = sequence[userStep];

    if (playedNote.note === targetNote.note) {
        userStep++;
        
        // Did they finish the whole sequence?
        if (userStep >= sequence.length) {
            GameBridge.playAudio('correct');
            showMessage("Great job! Next level.");
            currentLevel++;
            updateLevelDisplay();
            
            // Save score occasionally
            if(currentLevel % 5 === 0) {
                 GameBridge.saveScore({ score: currentLevel * 10, duration: 60 });
            }

            setTimeout(startMaestroRound, 1500);
        }
    } else {
        GameBridge.playAudio('wrong');
        showMessage("Oops! Try again from Level 1.");
        sequence = [];
        currentLevel = 1;
        updateLevelDisplay();
        document.getElementById('btn-start-round').style.display = 'inline-block';
    }
}

// --- Audio Engine ---
function playNote(freq, keyElement) {
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const osc = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    osc.type = currentWaveform;
    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);

    gainNode.gain.setValueAtTime(0.01, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.5, audioCtx.currentTime + 0.05);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);

    osc.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    osc.start();
    osc.stop(audioCtx.currentTime + 0.5);

    // Visuals
    if (keyElement) {
        keyElement.classList.add('active');
        setTimeout(() => keyElement.classList.remove('active'), 300);
    }
}

// --- Helpers ---
function showMessage(text) {
    document.getElementById('message').innerText = text;
}

function updateLevelDisplay() {
    document.getElementById('level-indicator').innerText = currentLevel;
}

// --- Helper Functions from GameBridge (if available) ---
function setWaveform(type) {
    currentWaveform = type;
}