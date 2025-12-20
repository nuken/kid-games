<?php
// sticker_book.php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// 1. Fetch the badges this child has earned from the database
$stmt = $pdo->prepare("
    SELECT b.id, b.name, b.icon
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    GROUP BY b.id
");
$stmt->execute([$user_id]);
$my_badges = $stmt->fetchAll();

// 2. Define your resized scenes (1920x1080)
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
            background-size: cover; /* Ensures your 1920x1080 image fills the screen */
            position: relative;
        }

        /* Buttons and Drawer Layer */
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
        .scene-btn.active { background: var(--star-gold); transform: scale(1.1); }

        #sticker-drawer {
            pointer-events: auto; position: fixed; bottom: 0; left: 0; width: 100%;
            height: 110px; background: rgba(0,0,0,0.8);
            display: flex; align-items: center; gap: 20px; padding: 0 20px;
            overflow-x: auto; border-top: 4px solid var(--star-gold);
        }

        .drawer-item {
            font-size: 60px; cursor: pointer; user-select: none;
            transition: transform 0.2s; filter: drop-shadow(0 0 5px rgba(255,255,255,0.3));
        }

        /* The actual sticker on the map */
        .placed-sticker {
            position: absolute; font-size: 80px; cursor: move;
            user-select: none; transform: translate(-50%, -50%);
            z-index: 5; touch-action: none;
        }

        .back-btn {
            pointer-events: auto; position: absolute; top: 20px; left: 20px;
            padding: 12px 25px; background: var(--planet-red);
            color: white; border-radius: 30px; text-decoration: none; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* Default for Desktop/Landscape Tablet (No scrolling) */
#sticker-canvas {
    width: 100vw; height: 100vh;
    background-size: cover;
    background-position: center bottom;
    overflow: hidden; /* No scrolling */
}

/* TRICK: Mobile Portrait Mode - Enable Panorama Swiping */
@media (orientation: portrait) {
    #sticker-canvas {
        /* Force the image to be full height, but let width be whatever it needs to be */
        background-size: auto 100vh;
        background-position: left bottom;

        /* Allow the kid to swipe left/right */
        overflow-x: auto;
        overflow-y: hidden;

        /* Make the "canvas" physically wider so they can drop stickers off-screen */
        width: 177vh; /* 16:9 ratio (1.77 * height) */
        max-width: none;
    }

    /* Hide the scrollbar so it looks cleaner */
    #sticker-canvas::-webkit-scrollbar { display: none; }
}
    </style>
</head>
<body>

    <div id="sticker-canvas"></div>

    <div id="ui-layer">
        <a href="index.php" class="back-btn">⬅ Back</a>

        <div class="top-controls">
            <button class="scene-btn <?php echo $current_scene=='space'?'active':''; ?>" onclick="window.location.href='?scene=space'">🚀</button>
            <button class="scene-btn <?php echo $current_scene=='fairy'?'active':''; ?>" onclick="window.location.href='?scene=fairy'">🏰</button>
            <button class="scene-btn <?php echo $current_scene=='ocean'?'active':''; ?>" onclick="window.location.href='?scene=ocean'">🐠</button>
            <button class="scene-btn" style="background:#e74c3c; border-color:#c0392b;" onclick="clearCanvas()">🗑️</button>
        </div>

        <div id="sticker-drawer">
            <?php foreach ($my_badges as $badge): ?>
                <div class="drawer-item" onmousedown="startDrag(event, '<?php echo $badge['icon']; ?>')" ontouchstart="startDrag(event, '<?php echo $badge['icon']; ?>')">
                    <?php echo $badge['icon']; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    let activeSticker = null;
    const canvas = document.getElementById('sticker-canvas');
    // Unique key for this specific child and scene
    const SAVE_KEY = `sticker_save_<?php echo $user_id; ?>_<?php echo $current_scene; ?>`;

    // 1. Load stickers when the page opens
    window.onload = loadStickers;

    function startDrag(e, icon) {
        e.preventDefault();
        const sticker = document.createElement('div');
        sticker.className = 'placed-sticker';
        sticker.innerHTML = icon;
        canvas.appendChild(sticker);

        activeSticker = sticker;
        moveSticker(e);
    }

    function moveSticker(e) {
        if (!activeSticker) return;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        activeSticker.style.left = clientX + 'px';
        activeSticker.style.top = clientY + 'px';
    }

    function endDrag() {
        if (activeSticker) {
            activeSticker = null;
            saveStickers(); // Save every time a sticker is dropped
        }
    }

    // 2. SAVE Logic: Capture every sticker's icon and position
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

    // 3. LOAD Logic: Recreate the stickers from memory
    function loadStickers() {
        const saved = localStorage.getItem(SAVE_KEY);
        if (!saved) return;

        const stickers = JSON.parse(saved);
        stickers.forEach(data => {
            const s = document.createElement('div');
            s.className = 'placed-sticker';
            s.innerHTML = data.icon;
            s.style.left = data.left;
            s.style.top = data.top;
            canvas.appendChild(s);
        });
    }

    function clearCanvas() {
        if(confirm("Do you want to clear your sticker page?")) {
            canvas.innerHTML = '';
            localStorage.removeItem(SAVE_KEY); // Clear memory too
        }
    }

    // Standard Listeners
    window.addEventListener('mousemove', moveSticker);
    window.addEventListener('mouseup', endDrag);
    window.addEventListener('touchmove', moveSticker, { passive: false });
    window.addEventListener('touchend', endDrag);

    canvas.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('placed-sticker')) activeSticker = e.target;
    });
    canvas.addEventListener('touchstart', (e) => {
        if (e.target.classList.contains('placed-sticker')) activeSticker = e.target;
    });
</script>
</body>
</html>
