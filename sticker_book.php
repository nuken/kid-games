<?php
// sticker_book.php
require_once 'includes/header.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

// Generate CSRF token if needed later
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// 1. Fetch badges (Stickers)
$stmt = $pdo->prepare("
    SELECT b.id, b.name, b.icon
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    GROUP BY b.id
");
$stmt->execute([$user_id]);
$my_badges = $stmt->fetchAll();

// 2. Scene Config
$scenes = [
    'space' => 'assets/images/scenes/space_scene.jpg',
    'fairy' => 'assets/images/scenes/fairy_scene.jpg',
    'ocean' => 'assets/images/scenes/ocean_scene.jpg'
];
$current_scene = $_GET['scene'] ?? 'space';
$bg_image = $scenes[$current_scene] ?? $scenes['space'];

function auto_version($file) {
    if (file_exists($file)) return $file . '?v=' . filemtime($file);
    return $file;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>My Sticker Book</title>
    
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/sticker.css'); ?>">

    <style>
        #sticker-canvas {
            background-image: url('<?php echo $bg_image; ?>');
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
            <button class="scene-btn btn-clear" onclick="openClearModal()">🗑️</button>
        </div>

        <div id="sticker-drawer">
            <?php if (count($my_badges) > 0): ?>
                <?php foreach ($my_badges as $badge): ?>
                    <div class="drawer-item" 
                        onmousedown="startMouseDrag(event, '<?php echo $badge['icon']; ?>')" 
                        ontouchstart="initTouchDrag(event, '<?php echo $badge['icon']; ?>')">
                        <?php echo $badge['icon']; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color: white; font-weight: bold; font-size: 1.2em;">Play games to earn stickers!</div>
            <?php endif; ?>
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
    // --- CONFIG ---
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

    // --- GAME LOGIC ---
    function startGame() {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        // Force silent sound to wake up iOS audio
        try {
            const buffer = audioCtx.createBuffer(1, 1, 22050);
            const source = audioCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(audioCtx.destination);
            source.start(0);
        } catch(e) {}

        playSound('pop');
        const overlay = document.getElementById('start-overlay');
        overlay.style.opacity = '0';
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
        } catch(e) {}
    }

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