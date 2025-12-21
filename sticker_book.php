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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Sticker Book</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { margin: 0; overflow: hidden; touch-action: none; font-family: 'Comic Neue', sans-serif; background: #333; }

        #sticker-canvas {
            width: 100vw; height: 100vh;
            background: url('<?php echo $bg_image; ?>') no-repeat center center;
            background-size: cover;
            position: relative;
            transition: background 0.5s ease;
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
        }
        
        /* Mobile scrollbar hidden */
        @media (pointer: coarse) {
            #sticker-drawer::-webkit-scrollbar { display: none; }
        }

        /* Desktop scrollbar */
        #sticker-drawer::-webkit-scrollbar { height: 8px; }
        #sticker-drawer::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        #sticker-drawer::-webkit-scrollbar-thumb { background: var(--star-gold); border-radius: 4px; }

        .drawer-item {
            font-size: 60px; cursor: pointer; user-select: none;
            transition: transform 0.2s; 
            -webkit-tap-highlight-color: transparent;
            flex-shrink: 0; 
        }
        .drawer-item:active { transform: scale(0.9); }

        /* Spacer to insure last item is reachable */
        .drawer-spacer {
            min-width: 50px; height: 1px;
        }

        .placed-sticker {
            position: absolute; font-size: 80px; cursor: grab;
            user-select: none; transform: translate(-50%, -50%);
            z-index: 5; touch-action: none;
            transition: transform 0.1s;
        }
        .placed-sticker:active { cursor: grabbing; }
        
        .dragging {
            z-index: 9999 !important;
            pointer-events: none; 
        }

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

    <div id="sticker-canvas"></div>

    <div id="ui-layer">
        <a href="index.php" class="back-btn">⬅ Back</a>

        <div class="top-controls">
            <button class="scene-btn <?php echo $current_scene=='space'?'active':''; ?>" onclick="switchScene('space')">🚀</button>
            <button class="scene-btn <?php echo $current_scene=='fairy'?'active':''; ?>" onclick="switchScene('fairy')">🏰</button>
            <button class="scene-btn <?php echo $current_scene=='ocean'?'active':''; ?>" onclick="switchScene('ocean')">🐠</button>
            <button class="scene-btn" style="background:#e74c3c; border-color:#c0392b;" onclick="clearCanvas()">🗑️</button>
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
    </div>

<script>
    let activeSticker = null;
    const canvas = document.getElementById('sticker-canvas');
    const drawer = document.getElementById('sticker-drawer');
    const SAVE_KEY = `sticker_save_<?php echo $user_id; ?>_<?php echo $current_scene; ?>`;

    // 1. Sounds
    const sounds = {
        pop: new Audio('assets/sounds/pop.mp3'),
        crumple: new Audio('assets/sounds/crumple.mp3'),
        sparkle: new Audio('assets/sounds/sparkle.mp3')
    };
    document.addEventListener('click', () => { if(sounds.pop.context) sounds.pop.context.resume(); }, {once:true});

    window.onload = loadStickers;

    // 2. Desktop Mouse Wheel Scroll
    drawer.addEventListener('wheel', (e) => {
        if (e.deltaY !== 0) {
            e.preventDefault();
            drawer.scrollLeft += e.deltaY;
        }
    });

    function switchScene(sceneName) {
        sounds.sparkle.currentTime = 0;
        sounds.sparkle.play().catch(e => {});
        setTimeout(() => { window.location.href = `?scene=${sceneName}`; }, 300);
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

        // Scroll
        if (diffX > diffY) {
            document.removeEventListener('touchmove', decideGesture);
            return;
        }

        // Drag
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
        
        // Just play pop sound and animate
        sounds.pop.currentTime = 0;
        sounds.pop.play().catch(e=>{});

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

    function clearCanvas() {
        if(confirm("Do you want to clear your sticker page?")) {
            sounds.crumple.play().catch(e=>{});
            canvas.innerHTML = '';
            localStorage.removeItem(SAVE_KEY);
        }
    }

    window.addEventListener('mousemove', moveSticker);
    window.addEventListener('mouseup', endDrag);
    window.addEventListener('touchmove', moveSticker, { passive: false });
    window.addEventListener('touchend', endDrag);

    const pickUp = (e) => {
        if (e.target.classList.contains('placed-sticker')) {
            activeSticker = e.target;
            activeSticker.classList.add('dragging');
            activeSticker.classList.remove('sticker-bounce');
        }
    };

    canvas.addEventListener('mousedown', pickUp);
    canvas.addEventListener('touchstart', pickUp);

</script>
</body>
</html>