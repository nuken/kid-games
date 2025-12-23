<?php
// sticker_book.php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// 1. Fetch badges
$stmt = $pdo->prepare("
    SELECT b.id, b.name, b.icon
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    GROUP BY b.id
");
$stmt->execute([$user_id]);
$my_badges = $stmt->fetchAll();

$scenes = [
    'space' => 'assets/images/scenes/space_scene.jpg',
    'fairy' => 'assets/images/scenes/fairy_scene.jpg',
    'ocean' => 'assets/images/scenes/ocean_scene.jpg'
];
$current_scene = $_GET['scene'] ?? 'space';
$bg_image = $scenes[$current_scene] ?? $scenes['space'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>My Sticker Book</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* --- IOS FIXES START --- */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        body { 
            margin: 0; 
            overflow: hidden; 
            position: fixed;
            width: 100%; height: 100%;
            touch-action: none; 
            font-family: 'Comic Neue', sans-serif; 
            background: #333; 
            -webkit-user-select: none;
            user-select: none;
        }

        /* --- START OVERLAY (NEW) --- */
        #start-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 10000;
            display: flex; flex-direction: column; 
            align-items: center; justify-content: center;
            backdrop-filter: blur(8px);
        }
        
        .start-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border: 5px solid white;
            color: white; font-size: 40px; font-weight: 900;
            padding: 20px 60px; border-radius: 60px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(46, 204, 113, 0.6);
            animation: pulse 1.5s infinite;
            text-transform: uppercase; letter-spacing: 2px;
        }
        
        .start-btn:active { transform: scale(0.95); animation: none; }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 20px rgba(46, 204, 113, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        /* --- GAME STYLES --- */
        #sticker-canvas {
            width: 100vw; height: 100vh;
            background: url('<?php echo $bg_image; ?>') no-repeat center center;
            background-size: cover;
            position: relative;
            transition: background 0.5s ease;
            z-index: 1;
        }

        #ui-layer {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 10;
        }

        .top-controls {
            pointer-events: auto; position: absolute; top: 20px; right: 20px;
            display: flex; gap: 10px;
        }

        .scene-btn {
            padding: 12px; background: white; border: 3px solid var(--star-gold);
            border-radius: 15px; cursor: pointer; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .scene-btn:hover { transform: scale(1.1); }
        .scene-btn.active { background: var(--star-gold); transform: scale(1.1); }

        #sticker-drawer {
            pointer-events: auto; 
            position: fixed; bottom: 0; left: 0; width: 100%;
            height: 110px; 
            background: rgba(0,0,0,0.8);
            display: flex; align-items: center; gap: 20px; padding: 0 20px;
            border-top: 4px solid var(--star-gold);
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
        }
        
        @media (pointer: coarse) { #sticker-drawer::-webkit-scrollbar { display: none; } }

        .drawer-item {
            font-size: 60px; cursor: pointer; 
            user-select: none; -webkit-user-select: none;
            transition: transform 0.2s; 
            flex-shrink: 0; 
        }
        .drawer-item:active { transform: scale(0.9); }
        .drawer-spacer { min-width: 50px; height: 1px; }

        .placed-sticker {
            position: absolute; font-size: 80px; cursor: grab;
            user-select: none; -webkit-user-select: none; 
            transform: translate(-50%, -50%);
            z-index: 5; 
            touch-action: none;
            transition: transform 0.1s;
        }
        .placed-sticker:active { cursor: grabbing; }
        .dragging { z-index: 9999 !important; pointer-events: none; }

        .sticker-bounce { animation: popBounce 0.4s; }
        @keyframes popBounce {
            0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.3); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(1); }
        }

        .back-btn {
            pointer-events: auto; position: absolute; top: 20px; left: 20px;
            padding: 12px 25px; background: var(--planet-red);
            color: white; border-radius: 30px; text-decoration: none; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* --- CUSTOM MODAL --- */
        #custom-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000;
            justify-content: center; align-items: center; pointer-events: auto;
            backdrop-filter: blur(5px);
        }

        #custom-modal {
            background: white; padding: 30px; border-radius: 25px;
            border: 6px solid var(--planet-red); text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 90%; width: 400px;
        }
        @keyframes popIn {
            0% { transform: scale(0) rotate(-10deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        .modal-title { font-size: 36px; color: #333; margin-bottom: 10px; font-weight: 900; }
        .modal-desc { font-size: 20px; color: #666; margin-bottom: 25px; }
        .modal-btns { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }

        .modal-btn {
            padding: 15px 25px; border-radius: 50px; font-size: 20px; border: none; cursor: pointer;
            font-weight: bold; color: white; transition: transform 0.1s;
            font-family: 'Comic Neue', cursive; display: flex; align-items: center; gap: 10px;
            box-shadow: 0 6px 0 rgba(0,0,0,0.2);
        }
        .modal-btn:active { transform: translateY(6px); box-shadow: none; }
        .btn-yes { background: #e74c3c; }
        .btn-no { background: #2ecc71; }

        @media (orientation: portrait) {
            #sticker-canvas {
                background-size: auto 100vh;
                background-position: left bottom;
                overflow-x: auto; overflow-y: hidden;
                width: 177vh; max-width: none;
            }
            #sticker-canvas::-webkit-scrollbar { display: none; }
        }
    </style>
</head>
<body>

    <div id="start-overlay">
        <h1 style="color:white; text-shadow: 0 5px 10px rgba(0,0,0,0.5); margin-bottom: 40px; font-size: 40px;">Ready to Play?</h1>
        <button class="start-btn" onclick="startGame()">Let's Go! 🚀</button>
    </div>

    <div id="sticker-canvas"></div>

    <div id="ui-layer">
        <a href="index.php" class="back-btn">⬅ Back</a>

        <div class="top-controls">
            <button class="scene-btn <?php echo $current_scene=='space'?'active':''; ?>" onclick="switchScene('space')">🚀</button>
            <button class="scene-btn <?php echo $current_scene=='fairy'?'active':''; ?>" onclick="switchScene('fairy')">🏰</button>
            <button class="scene-btn <?php echo $current_scene=='ocean'?'active':''; ?>" onclick="switchScene('ocean')">🐠</button>
            <button class="scene-btn" style="background:#e74c3c; border-color:#c0392b;" onclick="openClearModal()">🗑️</button>
        </div>

        <div id="sticker-drawer">
            <?php foreach ($my_badges as $badge): ?>
                <div class="drawer-item" 
                     onmousedown="startMouseDrag(event, '<?php echo $badge['icon']; ?>')" 
                     ontouchstart="initTouchDrag(event, '<?php echo $badge['icon']; ?>')">
                    <?php echo $badge['icon']; ?>
                </div>
            <?php endforeach; ?>
            <div class="drawer-spacer"></div>
        </div>

        <div id="custom-modal-overlay">
            <div id="custom-modal">
                <div class="modal-title">Start Over? 😲</div>
                <div class="modal-desc">Do you want to clear your sticker page?</div>
                <div class="modal-btns">
                    <button class="modal-btn btn-no" onclick="closeModal()">Keep It! 🛡️</button>
                    <button class="modal-btn btn-yes" onclick="confirmClear()">Wipe It! 💣</button>
                </div>
            </div>
        </div>
    </div>

<script>
    let activeSticker = null;
    const canvas = document.getElementById('sticker-canvas');
    const drawer = document.getElementById('sticker-drawer');
    const SAVE_KEY = `sticker_save_<?php echo $user_id; ?>_<?php echo $current_scene; ?>`;
    const modal = document.getElementById('custom-modal-overlay');

    // --- AUDIO SYSTEM ---
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    let audioCtx = new AudioContext();
    const soundBuffers = {};

    async function loadSound(name, url) {
        try {
            const response = await fetch(url);
            const arrayBuffer = await response.arrayBuffer();
            const audioBuffer = await audioCtx.decodeAudioData(arrayBuffer);
            soundBuffers[name] = audioBuffer;
        } catch (e) {
            console.warn(`Audio Load Error (${name}):`, e);
        }
    }

    loadSound('pop', 'assets/sounds/pop.mp3');
    loadSound('crumple', 'assets/sounds/crumple.mp3');
    loadSound('sparkle', 'assets/sounds/sparkle.mp3');

    // --- NEW: START GAME FUNCTION ---
    // This is called by the "Let's Go" button. 
    // Since it's a direct click, it is GUARANTEED to unlock audio.
    function startGame() {
        // 1. Resume Context
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().then(() => {
                console.log("Audio Context Resumed!");
            });
        }

        // 2. Play a silent buffer to force the engine to wake up (Double Safety)
        try {
            const buffer = audioCtx.createBuffer(1, 1, 22050);
            const source = audioCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(audioCtx.destination);
            source.start(0);
        } catch(e) {}

        // 3. Play a real sound so the user knows it worked
        playSound('pop');

        // 4. Hide the overlay
        const overlay = document.getElementById('start-overlay');
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.5s';
        setTimeout(() => overlay.remove(), 500);
    }

    function playSound(name) {
        if (!soundBuffers[name] || !audioCtx) return;
        if (audioCtx.state === 'suspended') audioCtx.resume();

        try {
            const source = audioCtx.createBufferSource();
            source.buffer = soundBuffers[name];
            source.connect(audioCtx.destination);
            source.start(0);
        } catch(e) {
            console.error("Play Error:", e);
        }
    }

    // --- STANDARD APP LOGIC ---

    window.onload = loadStickers;

    // IOS FIX: Prevent background scrolling
    document.body.addEventListener('touchmove', function(e) {
        if (!e.target.closest('#sticker-drawer') && !e.target.closest('.modal-btn')) {
            e.preventDefault();
        }
    }, { passive: false });

    drawer.addEventListener('wheel', (e) => {
        if (e.deltaY !== 0) {
            e.preventDefault();
            drawer.scrollLeft += e.deltaY;
        }
    });

    function switchScene(sceneName) {
        playSound('sparkle');
        setTimeout(() => { window.location.href = `?scene=${sceneName}`; }, 300);
    }

    // --- MODAL LOGIC ---
    function openClearModal() {
        modal.style.display = 'flex';
        playSound('pop');
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function confirmClear() {
        closeModal();
        playSound('crumple');
        canvas.innerHTML = '';
        localStorage.removeItem(SAVE_KEY);
    }

    // --- DRAG LOGIC ---
    function startMouseDrag(e, icon) {
        e.preventDefault();
        createSticker(icon, e.clientX, e.clientY);
    }

    let touchStartX = 0, touchStartY = 0, touchIcon = '', isDraggingFromDrawer = false;

    function initTouchDrag(e, icon) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        touchIcon = icon;
        isDraggingFromDrawer = false;
        
        document.addEventListener('touchmove', decideGesture, { passive: false });
        document.addEventListener('touchend', cleanUpTouch, { once: true });
    }

    function decideGesture(e) {
        if (isDraggingFromDrawer) return;

        const moveX = e.touches[0].clientX;
        const moveY = e.touches[0].clientY;
        const diffX = Math.abs(moveX - touchStartX);
        const diffY = Math.abs(moveY - touchStartY);

        // Horizontal -> Scroll
        if (diffX > diffY) {
            document.removeEventListener('touchmove', decideGesture);
            return;
        }

        // Vertical -> Drag Sticker
        if (diffY > 10 && diffY > diffX) {
            e.preventDefault();
            isDraggingFromDrawer = true;
            createSticker(touchIcon, moveX, moveY);
            document.removeEventListener('touchmove', decideGesture);
        }
    }

    function cleanUpTouch() {
        document.removeEventListener('touchmove', decideGesture);
    }

    // --- CORE MOVING FUNCTIONS ---

    function createSticker(icon, clientX, clientY) {
        const sticker = document.createElement('div');
        sticker.className = 'placed-sticker dragging'; 
        sticker.innerHTML = icon;
        canvas.appendChild(sticker);
        activeSticker = sticker;
        updateStickerPosition(clientX, clientY);
    }

    function moveSticker(e) {
        if (!activeSticker) return;
        if(e.cancelable) e.preventDefault(); 
        
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        updateStickerPosition(clientX, clientY);
    }

    function updateStickerPosition(clientX, clientY) {
        const rect = canvas.getBoundingClientRect();
        const canvasX = (clientX - rect.left) + canvas.scrollLeft;
        const canvasY = (clientY - rect.top) + canvas.scrollTop;
        activeSticker.style.left = canvasX + 'px';
        activeSticker.style.top = canvasY + 'px';
    }

    function endDrag() {
        if (!activeSticker) return;

        activeSticker.classList.remove('dragging');
        
        playSound('pop');

        activeSticker.classList.remove('sticker-bounce');
        void activeSticker.offsetWidth; 
        activeSticker.classList.add('sticker-bounce');

        activeSticker = null;
        saveStickers();
    }

    function saveStickers() {
        const stickers = [];
        document.querySelectorAll('.placed-sticker').forEach(s => {
            stickers.push({
                icon: s.innerHTML,
                left: s.style.left,
                top: s.style.top
            });
        });
        localStorage.setItem(SAVE_KEY, JSON.stringify(stickers));
    }

    function loadStickers() {
        const saved = localStorage.getItem(SAVE_KEY);
        if (!saved) return;
        JSON.parse(saved).forEach(data => {
            const s = document.createElement('div');
            s.className = 'placed-sticker sticker-bounce';
            s.innerHTML = data.icon;
            s.style.left = data.left;
            s.style.top = data.top;
            canvas.appendChild(s);
        });
    }

    // --- EVENT LISTENERS ---
    window.addEventListener('mousemove', moveSticker);
    window.addEventListener('mouseup', endDrag);
    window.addEventListener('touchmove', moveSticker, { passive: false });
    window.addEventListener('touchend', endDrag);

    const pickUp = (e) => {
        if (e.target.classList.contains('placed-sticker')) {
            e.stopPropagation(); 
            if(e.type === 'touchstart') e.preventDefault();
            
            activeSticker = e.target;
            activeSticker.classList.add('dragging');
            activeSticker.classList.remove('sticker-bounce');
        }
    };

    canvas.addEventListener('mousedown', pickUp);
    canvas.addEventListener('touchstart', pickUp, { passive: false });

</script>
</body>
</html>