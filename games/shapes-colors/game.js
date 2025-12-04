(function() {
    // --- SCORING STATE ---
    let sessionScore = 0;
    let tasksCompleted = 0;
    const TASKS_TO_WIN = 5; // Puzzles take longer, so fewer tasks needed
    let startTime = Date.now();

    // --- AUDIO ---
    const correctSound = new Audio('sounds/correct.mp3');
    const wrongSound = new Audio('sounds/wrong.mp3');

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof GameBridge !== 'undefined') GameBridge.init();

        // Navigation
        const screens = document.querySelectorAll('.game-screen');
        const scoreDisplay = document.getElementById('session-score');

        function showScreen(screenId) {
            screens.forEach(s => s.classList.remove('visible'));
            document.getElementById(screenId).classList.add('visible');
            
            // Init Specific Games
            if (screenId === 'leaf-sort-game') startLeafSortGame();
            if (screenId === 'shape-web-game') startShapeGame();
            if (screenId === 'shape-puzzle-game') startShapePuzzleGame();
            if (screenId === 'mixing-game') startMixingGame();
        }

        // Button Listeners
        document.getElementById('start-leaf-sort-btn').onclick = () => showScreen('leaf-sort-game');
        document.getElementById('start-shape-web-btn').onclick = () => showScreen('shape-web-game');
        document.getElementById('start-shape-puzzle-btn').onclick = () => showScreen('shape-puzzle-game');
        document.getElementById('start-mixing-btn').onclick = () => showScreen('mixing-game');
        
        document.querySelectorAll('.back-btn').forEach(btn => {
            btn.onclick = () => showScreen('main-menu');
        });

        // --- SCORING FUNCTIONS ---
        function handleSuccess() {
            correctSound.currentTime = 0;
            correctSound.play().catch(e=>{});
            
            sessionScore += 20; // 20 points per complex task
            tasksCompleted++;
            if(scoreDisplay) scoreDisplay.innerText = sessionScore;

            if (tasksCompleted >= TASKS_TO_WIN) {
                setTimeout(endGameSession, 1000);
            }
        }

        function handleFailure() {
            wrongSound.currentTime = 0;
            wrongSound.play().catch(e=>{});
        }

        function endGameSession() {
            GameBridge.saveScore({
                gameId: (function(){ 
                    // Retrieve ID dynamically or hardcode if you know it from DB insert
                    // Assuming ID is 6 based on sequence, but safe to check DB
                    return 6; 
                })(), 
                score: sessionScore,
                duration: Math.floor((Date.now() - startTime) / 1000)
            });
            // Reset
            sessionScore = 0;
            tasksCompleted = 0;
            startTime = Date.now();
        }

        // ============================================================
        // GAME 1: LEAF SORT (Konva)
        // ============================================================
        const leafColors = ['green', 'red', 'yellow', 'brown'];
        const LEAF_IMAGES = {};
        let leafStage, leafLayer;
        let leafPieces = [], basketTargets = [];

        function loadLeafImages(callback) {
            let loaded = 0;
            leafColors.forEach(color => {
                if(LEAF_IMAGES[color]) {
                    loaded++;
                    if(loaded === 4) callback();
                } else {
                    const img = new Image();
                    img.src = `images/leaf-${color}.png`;
                    img.onload = () => {
                        LEAF_IMAGES[color] = img;
                        loaded++;
                        if(loaded === 4) callback();
                    };
                }
            });
        }

        function startLeafSortGame() {
            GameBridge.speak("Sort the leaves into the matching baskets.");
            loadLeafImages(() => {
                const container = document.getElementById('leaf-sort-canvas');
                if(!leafStage) {
                    leafStage = new Konva.Stage({ container: 'leaf-sort-canvas', width: container.clientWidth, height: container.clientHeight });
                    leafLayer = new Konva.Layer();
                    leafStage.add(leafLayer);
                }
                // Reset Layout
                leafStage.width(container.clientWidth);
                leafStage.height(container.clientHeight);
                loadLeafLevel();
            });
        }

        function loadLeafLevel() {
            leafLayer.destroyChildren();
            leafPieces = [];
            basketTargets = [];
            
            const w = leafStage.width();
            const h = leafStage.height();
            const basketSize = Math.min(w / 5, 100);
            const gap = (w - (basketSize * 4)) / 5;

            const basketColors = { 'green': '#4CAF50', 'red': '#F44336', 'yellow': '#FFEB3B', 'brown': '#795548' };

            leafColors.forEach((color, i) => {
                const basket = new Konva.Rect({
                    x: gap + (i * (basketSize + gap)),
                    y: h - basketSize - 20,
                    width: basketSize, height: basketSize,
                    stroke: basketColors[color], strokeWidth: 5, fill: 'white', cornerRadius: 10
                });
                basket.id(color);
                leafLayer.add(basket);
                basketTargets.push(basket);
            });

            // Add Leaves
            for(let i=0; i<8; i++) {
                const color = leafColors[Math.floor(Math.random()*4)];
                const size = 60;
                const leaf = new Konva.Image({
                    image: LEAF_IMAGES[color],
                    x: Math.random() * (w - size),
                    y: Math.random() * (h/2),
                    width: size, height: size,
                    draggable: true, id: color
                });
                
                leaf.on('dragend', (e) => {
                    const box = e.target.getClientRect();
                    const target = basketTargets.find(b => Konva.Util.haveIntersection(box, b.getClientRect()));
                    
                    if(target && target.id() === e.target.id()) {
                        // Correct
                        e.target.draggable(false);
                        e.target.to({ x: target.x()+20, y: target.y()+20, scaleX:0.5, scaleY:0.5, duration:0.2 });
                        checkLeafWin();
                    } else {
                        // Wrong
                        e.target.to({ x: Math.random()*(w-size), y: Math.random()*(h/2), duration:0.5 });
                        handleFailure();
                    }
                });
                leafLayer.add(leaf);
                leafPieces.push(leaf);
            }
            leafLayer.batchDraw();
        }

        function checkLeafWin() {
            if(leafPieces.every(l => !l.draggable())) {
                handleSuccess();
                GameBridge.celebrate("Clean sweep!");
                setTimeout(loadLeafLevel, 2000);
            }
        }

        // ============================================================
        // GAME 2: SHAPE WEB
        // ============================================================
        const shapeData = [
            { s: 'square', img: 'images/web-square-gap.png', fill: 'images/web-square-filled.png' },
            { s: 'circle', img: 'images/web-circle-gap.png', fill: 'images/web-circle-filled.png' },
            { s: 'triangle', img: 'images/web-triangle-gap.png', fill: 'images/web-triangle-filled.png' }
        ];
        let currentShapeIdx = 0;

        function startShapeGame() {
            loadShapeLevel();
        }

        function loadShapeLevel() {
            const data = shapeData[Math.floor(Math.random() * shapeData.length)];
            const web = document.getElementById('spider-web-display');
            web.style.backgroundImage = `url('${data.img}')`;
            
            const container = document.getElementById('shape-choices-container');
            container.innerHTML = '';
            
            GameBridge.speak("Find the " + data.s);

            const shapes = ['square', 'circle', 'triangle', 'star', 'hexagon'];
            shapes.sort(() => Math.random() - 0.5);

            shapes.forEach(shape => {
                const btn = document.createElement('div');
                btn.className = 'shape-btn';
                btn.innerHTML = `<img src="images/shape-${shape}.png">`;
                btn.onclick = () => {
                    if(shape === data.s) {
                        web.style.backgroundImage = `url('${data.fill}')`;
                        handleSuccess();
                        GameBridge.celebrate();
                        setTimeout(loadShapeLevel, 1500);
                    } else {
                        handleFailure();
                        btn.style.opacity = '0.5';
                    }
                };
                container.appendChild(btn);
            });
        }

        // ============================================================
        // GAME 3: PUZZLES (Konva)
        // ============================================================
        const puzzles = [
            { id: 'house', img: 'images/puzzle-house.png', parts: [
                { shape: 'triangle', x:0.5, y:0.35, color:'red', r:0 },
                { shape: 'square', x:0.5, y:0.65, color:'blue', r:0 } 
            ]}
        ];
        let puzzleStage, puzzleLayer;

        function startShapePuzzleGame() {
            const container = document.getElementById('shape-puzzle-canvas');
            if(!puzzleStage) {
                puzzleStage = new Konva.Stage({ container: 'shape-puzzle-canvas', width: container.clientWidth, height: container.clientHeight });
                puzzleLayer = new Konva.Layer();
                puzzleStage.add(puzzleLayer);
            }
            loadPuzzle();
        }

        function loadPuzzle() {
            puzzleLayer.destroyChildren();
            const data = puzzles[0]; // Simple example uses house only
            const w = puzzleStage.width();
            const h = puzzleStage.height();

            // Silhouette
            Konva.Image.fromURL(data.img, (img) => {
                img.setAttrs({
                    x: w/2 - 100, y: h/2 - 100, width: 200, height: 200, opacity: 0.3
                });
                puzzleLayer.add(img);
                puzzleLayer.draw();
                
                // Targets (Invisible hit zones based on silhouette pos)
                data.parts.forEach(p => {
                    const size = 80;
                    // Actual piece
                    const shape = createShape(p.shape, Math.random()*100, Math.random()*300, size, p.color);
                    shape.draggable(true);
                    
                    // Target position logic simplified for demo:
                    // In a real app, calculate relative to image. 
                    // Here we just define a "drop zone" roughly where it should go.
                    const targetX = img.x() + (img.width() * p.x) - (size/2); // Center it
                    const targetY = img.y() + (img.height() * p.y) - (size/2);

                    shape.on('dragend', () => {
                        const dist = Math.hypot(shape.x() - targetX, shape.y() - targetY);
                        if(dist < 50) {
                            shape.position({x: targetX, y: targetY});
                            shape.draggable(false);
                            correctSound.play();
                            checkPuzzleComplete();
                        } else {
                            wrongSound.play();
                        }
                    });
                    puzzleLayer.add(shape);
                });
            });
        }

        function createShape(type, x, y, size, color) {
            if(type === 'square') return new Konva.Rect({x, y, width:size, height:size, fill:color, stroke:'black'});
            if(type === 'triangle') return new Konva.RegularPolygon({x, y, sides:3, radius:size/1.5, fill:color, stroke:'black'});
            return new Konva.Circle({x, y, radius:size/2, fill:color});
        }

        function checkPuzzleComplete() {
            // If all shapes not draggable
            const shapes = puzzleLayer.getChildren(node => node.getClassName() !== 'Image');
            if(shapes.every(s => !s.draggable())) {
                handleSuccess();
                setTimeout(loadPuzzle, 2000);
            }
        }

        // ============================================================
        // GAME 4: COLOR MIXING
        // ============================================================
        function startMixingGame() {
            const container = document.getElementById('mixing-problem-container');
            const choices = document.getElementById('mixing-choices');
            
            const mixData = [
                {c1:'red', c2:'yellow', res:'orange', hex:'#FF9800'},
                {c1:'blue', c2:'yellow', res:'green', hex:'#4CAF50'},
                {c1:'red', c2:'white', res:'pink', hex:'#FFC0CB'}
            ];
            
            const q = mixData[Math.floor(Math.random()*mixData.length)];
            GameBridge.speak(`What do ${q.c1} and ${q.c2} make?`);

            container.innerHTML = `
                <div class="blob" style="background:${q.c1}"></div> 
                <span style="font-size:30px">+</span> 
                <div class="blob" style="background:${q.c2}"></div> 
                <span style="font-size:30px">=</span> 
                <div class="blob question">?</div>
            `;

            choices.innerHTML = '';
            const opts = [q.hex, '#800080', '#795548'].sort(()=>Math.random()-0.5);
            
            opts.forEach(color => {
                const btn = document.createElement('div');
                btn.className = 'blob choice';
                btn.style.background = color;
                btn.onclick = () => {
                    if(color === q.hex) {
                        document.querySelector('.question').innerText = '';
                        document.querySelector('.question').style.background = q.hex;
                        handleSuccess();
                        GameBridge.celebrate(q.res);
                        setTimeout(startMixingGame, 2000);
                    } else {
                        handleFailure();
                    }
                };
                choices.appendChild(btn);
            });
        }

    });
})();