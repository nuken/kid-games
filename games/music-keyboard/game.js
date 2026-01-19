(function() {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    let currentWaveform = 'triangle'; 
    let masterVolume = 0.5; 

    // --- State ---
    let mode = 'free'; 
    let currentSong = [];
    let songStep = 0;

    // --- Single Octave Data ---
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

    const SONGS = {
        'twinkle': { name: "Twinkle Twinkle", notes: ["C", "C", "G", "G", "A", "A", "G", "F", "F", "E", "E", "D", "D", "C"] },
        'mary': { name: "Mary Had a Little Lamb", notes: ["E", "D", "C", "D", "E", "E", "E", "D", "D", "D", "E", "G", "G"] },
        'shark': { name: "Baby Shark", notes: ["D", "E", "G", "G", "G", "G", "G", "G", "D", "E", "G", "G", "G", "G", "G", "G"] },
        'happy': { name: "Happy Birthday", notes: ["C", "C", "D", "C", "F", "E", "C", "C", "D", "C", "G", "F"] }
    };

    document.addEventListener('DOMContentLoaded', () => {
        if(typeof GameBridge !== 'undefined') {
            GameBridge.setupGame({
                instructions: "Play the piano! Follow the lights to learn songs.",
                levels: [{ id: 1, label: "Keyboard" }],
                onStart: () => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    buildPiano();
                }
            });
        } else {
            buildPiano();
        }

        window.startSong = function(songKey) {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            
            if (!songKey) {
                mode = 'free';
                showMessage("Free Play Mode");
                clearHints();
                return;
            }

            mode = 'song';
            currentSong = SONGS[songKey].notes;
            songStep = 0;
            showMessage(`Let's play: ${SONGS[songKey].name}`);
            highlightNextNote();
        };

        window.setWaveform = function(type) {
            currentWaveform = type;
        };

        window.setVolume = function(val) {
            masterVolume = parseFloat(val);
        };
    });

    function buildPiano() {
        const container = document.getElementById('piano-container');
        container.innerHTML = ''; 
        
        const whiteKeyWidth = 60; 
        const blackKeyWidth = 40;
        let whiteKeyCount = 0;

        notes.forEach((n) => {
            const key = document.createElement('div');
            key.id = 'key-' + n.note;
            
            const press = (e) => {
                if(e) e.preventDefault();
                handleInput(n, key);
            };
            key.onmousedown = press;
            key.ontouchstart = press;

            if (n.type === 'white') {
                key.className = 'key';
                key.innerText = n.note.replace('2', ''); 
                container.appendChild(key);
                whiteKeyCount++;
            } else {
                key.className = 'key sharp';
                const leftPos = (whiteKeyCount * whiteKeyWidth) - (blackKeyWidth / 2);
                key.style.left = leftPos + 'px';
                container.appendChild(key);
            }
        });
    }

    function handleInput(noteObj, keyElem) {
        if (audioCtx.state === 'suspended') audioCtx.resume();

        playNote(noteObj.freq);
        visualPress(keyElem);

        if (mode === 'song') {
            const targetNote = currentSong[songStep];
            if (noteObj.note === targetNote) {
                songStep++;
                if (songStep >= currentSong.length) {
                    if(typeof GameBridge !== 'undefined') {
                        GameBridge.playAudio('correct');
                        GameBridge.celebrate("Song Complete!");
                        GameBridge.saveScore({ score: 100, noRedirect: true });
                    }
                    showMessage("You did it! 🎉");
                    mode = 'free';
                    document.getElementById('song-select').value = "";
                    clearHints();
                } else {
                    highlightNextNote();
                }
            }
        } else {
            showMessage(`Note: ${noteObj.note.replace('2','')}`);
        }
    }

    // --- UPDATED HINT LOGIC ---
    function highlightNextNote() {
        // 1. Clear previous hints
        clearHints();
        
        const nextNote = currentSong[songStep];
        const key = document.getElementById('key-' + nextNote);
        
        if (key) {
            // 2. Generate a random "Neon" color
            // Using HSL: Random Hue, 100% Saturation, 50% Lightness ensures it's always bright
            const hue = Math.floor(Math.random() * 360);
            const color = `hsl(${hue}, 100%, 50%)`;

            // 3. Apply color and class
            key.style.backgroundColor = color;
            key.style.boxShadow = `0 0 20px ${color}`; // Glowing effect
            key.classList.add('hint');
        }
    }

    function clearHints() {
        document.querySelectorAll('.hint').forEach(k => {
            k.classList.remove('hint');
            k.style.backgroundColor = ''; // Remove inline color to reset to black/white
            k.style.boxShadow = '';
        });
    }
    // ---------------------------

    function showMessage(text) {
        const msg = document.getElementById('message');
        if(msg) msg.innerText = text;
    }

    function visualPress(key) {
        // Remove hint properties immediately so we see the "press" color
        key.classList.remove('hint');
        key.style.backgroundColor = '';
        key.style.boxShadow = '';

        // Flash white/bright to show contact
        key.style.background = '#fff'; 
        key.style.transform = 'translateY(4px)'; 
        key.classList.add('active');

        if (key.dataset.timerId) {
            clearTimeout(key.dataset.timerId);
        }

        const timerId = setTimeout(() => {
            key.style.background = ''; 
            key.style.transform = '';  
            key.classList.remove('active');
            delete key.dataset.timerId;
        }, 300);

        key.dataset.timerId = timerId;
    }

    function playNote(freq) {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = currentWaveform;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);

        const now = audioCtx.currentTime;
        gain.gain.setValueAtTime(0, now);
        
        // Attack
        gain.gain.linearRampToValueAtTime(masterVolume, now + 0.05);
        
        // Decay
        gain.gain.exponentialRampToValueAtTime(0.001, now + 1.0);

        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(now + 1.0);
    }
})();