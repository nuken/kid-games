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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>My Sticker Book</title>

    <link rel="stylesheet" href="<?php echo auto_version('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo auto_version('assets/css/sticker.css'); ?>">

    <script>
    let zzfxV=.3; // Volume
    let zzfxX=new (window.AudioContext||webkitAudioContext); // Audio Context
    let zzfx=(p,k=.05,b=220,e=0,r=0,t=.1,q=0,D=1,u=0,y=0,v=0,z=0,l=0,E=0,A=0,F=0,c=0,w=1,m=0,B=0,N=0)=>{let M=Math,d=2*M.PI,R=44100,G=u*=500*d/R/R,C=b*=(1-k+2*k*M.random(k=[]))*d/R,g=0,H=0,a=0,n=1,I=0,J=0,f=0,h=N<0?-1:1,x=d*h*N*2/R,L=M.cos(x),Z=M.sin,K=Z(x)/4,O=1+K,X=-2*L/O,Y=(1-K)/O,P=(1+h*L)/2/O,Q=-(h+L)/O,S=P,T=0,U=0,V=0,W=0;e=R*e+9;m*=R;r*=R;t*=R;c*=R;y*=500*d/R**3;A*=d/R;v*=d/R;z*=R;l=R*l|0;p*=zzfxV;for(h=e+m+r+t+c|0;a<h;k[a++]=f*p)++J%(100*F|0)||(f=q?1<q?2<q?3<q?4<q?(g/d%1<D/2)*2-1:Z(g**3):M.max(M.min(M.tan(g),1),-1):1-(2*g/d%2+2)%2:1-4*M.abs(M.round(g/d)-g/d):Z(g),f=(l?1-B+B*Z(d*a/l):1)*(4<q?s:(f<0?-1:1)*M.abs(f)**D)*(a<e?a/e:a<e+m?1-(a-e)/m*(1-w):a<e+m+r?w:a<h-c?(h-a-c)/t*w:0),f=c?f/2+(c>a?0:(a<h-c?1:(h-a)/c)*k[a-c|0]/2/p):f,N?f=W=S*T+Q*(T=U)+P*(U=f)-Y*V-X*(V=W):0),x=(b+=u+=y)*M.cos(A*H++),g+=x+x*E*Z(a**5),n&&++n>z&&(b+=v,C+=v,n=0),!l||++I%l||(b=C,u=G,n=n||1);X=zzfxX,p=X.createBuffer(1,h,R);p.getChannelData(0).set(k);b=X.createBufferSource();b.buffer=p;b.connect(X.destination);b.start();return b};
    </script>
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

    // --- AUDIO SYSTEM (Uses ZzFX Embedded above) ---
    const sfx = {
        pop:     [1,,500,.02,.02,.05,1,1.59,-6.98,4.97], // Short, snappy pop
        // FIXED: Crumple sound now has frequency (50) instead of 0
        crumple: [1.5,,50,.05,.2,.4,4,3,-5,,,,-0.1],
        sparkle: [1,,1300,.02,.2,.2,1,2,5,,,,.1]
    };

    function playSound(name) {
        // 1. Initialize AudioContext if suspended (user interaction check)
        if (typeof zzfxX !== 'undefined' && zzfxX.state === 'suspended') {
             zzfxX.resume();
        }
        // 2. Play sound using embedded zzfx function
        if (sfx[name] && typeof zzfx !== 'undefined') {
            zzfx(...sfx[name]);
        }
    }

    // --- GAME LOGIC ---
    function startGame() {
        playSound('pop');
        const overlay = document.getElementById('start-overlay');
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 500);
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
