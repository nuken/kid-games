/* games/coloring/game.js */
(function() {
    const imageFiles = ['1.jpg', '2.jpg', '3.jpg', '4.jpg', '5.jpg', '6.jpg', '7.jpg', '8.jpg', '9.jpg', '10.jpg'];
    const colors = ['#000000', '#575757', '#ffffff', '#DC2323', '#FF9233', '#FFEE33', '#1D6914', '#2A4BD7', '#8126C0', '#FFCDF3', '#814A19', '#A0522D', '#FF69B4', '#00FFFF', '#008080'];

    let stage, layer;
    let currentTool = 'brush';
    let currentColor = '#DC2323';
    let currentSize = 10;
    let isDrawing = false;
    let currentLine;
    let currentImageNode = null;
    let historyStack = [];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Pick a color and draw!",
            levels: [{ id: 1, label: "Start Coloring" }],
            onStart: initCanvas
        });
    });

    function initCanvas() {
        const container = document.getElementById('canvas-wrapper');
        if (stage) stage.destroy();

        // 1. Initialize Stage
        stage = new Konva.Stage({
            container: 'canvas-wrapper',
            width: container.clientWidth,
            height: container.clientHeight
        });

        layer = new Konva.Layer();
        stage.add(layer);

        setupUI();

        // 2. Load Image
        loadImage('1.jpg');

        // 3. Force Resize Check (Fixes "pushed to right" issue on load)
        setTimeout(() => {
            const width = container.clientWidth;
            const height = container.clientHeight;
            stage.width(width);
            stage.height(height);
            if(currentImageNode) fitImage(currentImageNode);
        }, 100);

        // --- DRAWING HANDLERS ---
        stage.on('mousedown touchstart', (e) => {
            if (currentTool !== 'brush') return;
            isDrawing = true;
            const pos = stage.getPointerPosition();
            currentLine = new Konva.Line({
                stroke: currentColor,
                strokeWidth: parseInt(currentSize),
                globalCompositeOperation: 'source-over',
                lineCap: 'round', lineJoin: 'round',
                points: [pos.x, pos.y, pos.x, pos.y]
            });
            layer.add(currentLine);
        });

        stage.on('mousemove touchmove', (e) => {
            if (!isDrawing || currentTool !== 'brush') return;
            e.evt.preventDefault();
            const pos = stage.getPointerPosition();
            const newPoints = currentLine.points().concat([pos.x, pos.y]);
            currentLine.points(newPoints);
            layer.batchDraw();
        });

        stage.on('mouseup touchend', () => {
            if (isDrawing) {
                isDrawing = false;
                historyStack.push({ type: 'brush', node: currentLine });
            }
        });

        // Flood Fill Handler
        stage.on('click tap', (e) => {
            if (currentTool !== 'fill' || !currentImageNode) return;
            const pos = stage.getPointerPosition();
            const relativeX = (pos.x - currentImageNode.x()) / currentImageNode.width();
            const relativeY = (pos.y - currentImageNode.y()) / currentImageNode.height();

            if (relativeX >= 0 && relativeX <= 1 && relativeY >= 0 && relativeY <= 1) {
                const sourceCanvas = currentImageNode.image();
                const ctx = sourceCanvas.getContext('2d');
                const beforeImageData = ctx.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height);

                const pixelX = Math.floor(relativeX * sourceCanvas.width);
                const pixelY = Math.floor(relativeY * sourceCanvas.height);
                const fillColorRgb = hexToRgb(currentColor);

                floodFill(pixelX, pixelY, fillColorRgb, ctx);
                layer.batchDraw();
                historyStack.push({ type: 'fill', imageData: beforeImageData });
            }
        });

        // Responsive Resize
        const resizeObserver = new ResizeObserver(() => {
            if (!stage || !container) return;
            stage.width(container.clientWidth);
            stage.height(container.clientHeight);
            if(currentImageNode) fitImage(currentImageNode);
        });
        resizeObserver.observe(container);
    }

    function fitImage(imgNode) {
        const stageW = stage.width();
        const stageH = stage.height();

        // Use 95% of available space to keep it nicely centered with a border
        const ratio = Math.min(stageW / imgNode.width(), stageH / imgNode.height()) * 0.95;

        imgNode.scale({ x: ratio, y: ratio });
        imgNode.x((stageW - (imgNode.width() * ratio)) / 2);
        imgNode.y((stageH - (imgNode.height() * ratio)) / 2);
        layer.batchDraw();
    }

    function setupUI() {
        const p = document.getElementById('color-palette');
        p.innerHTML = '';

        colors.forEach((c, idx) => {
            let d = document.createElement('div');
            d.className = 'color-swatch';
            if(idx === 3) d.classList.add('active');
            d.style.backgroundColor = c;
            d.onclick = () => {
                currentColor = c;
                document.querySelectorAll('.color-swatch').forEach(x => x.classList.remove('active'));
                d.classList.add('active');
            };
            p.appendChild(d);
        });

        const brushBtn = document.getElementById('brush-btn');
        const fillBtn = document.getElementById('fill-btn');

        brushBtn.onclick = () => {
            currentTool = 'brush';
            brushBtn.classList.add('active');
            fillBtn.classList.remove('active');
        };

        fillBtn.onclick = () => {
            currentTool = 'fill';
            fillBtn.classList.add('active');
            brushBtn.classList.remove('active');
        };

        document.getElementById('brush-size').oninput = (e) => currentSize = e.target.value;

        document.getElementById('undo-btn').onclick = () => {
            if (historyStack.length === 0) return;
            const lastAction = historyStack.pop();
            if (lastAction.type === 'brush') {
                lastAction.node.destroy();
                layer.batchDraw();
            } else if (lastAction.type === 'fill') {
                const ctx = currentImageNode.image().getContext('2d');
                ctx.putImageData(lastAction.imageData, 0, 0);
                layer.batchDraw();
            }
        };

        document.getElementById('clear-btn').onclick = () => {
            if(confirm('Clear all colors?')) {
                layer.destroyChildren();
                historyStack = [];
                const activeThumb = document.querySelector('.thumb.active');
                const fname = activeThumb ? activeThumb.src.split('/').pop() : '1.jpg';
                loadImage(fname);
            }
        };

        document.getElementById('save-btn').onclick = () => {
            // Save against white background
            const uri = stage.toDataURL({ pixelRatio: 2, mimeType: "image/jpeg", quality: 0.9 });
            const link = document.createElement('a');
            link.download = 'my-art.jpg';
            link.href = uri;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        const sel = document.getElementById('image-selector');
        sel.innerHTML = '';
        imageFiles.forEach((f, idx) => {
            let img = document.createElement('img');
            img.src = 'games/coloring/images/thumbs/' + f;
            img.className = 'thumb';
            if(idx === 0) img.classList.add('active');
            img.onclick = () => {
                document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
                img.classList.add('active');
                loadImage(f);
            };
            sel.appendChild(img);
        });
    }

    function loadImage(filename) {
        layer.destroyChildren();
        historyStack = [];

        Konva.Image.fromURL('games/coloring/images/' + filename, (imgNode) => {
            const img = imgNode.image();
            const offscreenCanvas = document.createElement('canvas');
            offscreenCanvas.width = img.naturalWidth || img.width;
            offscreenCanvas.height = img.naturalHeight || img.height;
            const ctx = offscreenCanvas.getContext('2d');

            // Fill background white so JPGs aren't transparent
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, offscreenCanvas.width, offscreenCanvas.height);
            ctx.drawImage(img, 0, 0);

            imgNode.image(offscreenCanvas);
            currentImageNode = imgNode;

            fitImage(imgNode);

            layer.add(imgNode);
            layer.batchDraw();
        });
    }

    function floodFill(startX, startY, fillColorRgb, ctx) {
        const { width, height } = ctx.canvas;
        const imageData = ctx.getImageData(0, 0, width, height);
        const { data } = imageData;
        const blackColor = [0, 0, 0];
        const startIndex = (startY * width + startX) * 4;
        const startColor = [data[startIndex], data[startIndex + 1], data[startIndex + 2]];

        if (colorsMatch(data, startIndex, blackColor, 60)) return; // Increased tolerance for lines
        if (colorsMatch(data, startIndex, fillColorRgb, 10)) return;

        const stack = [[startX, startY]];
        const fillColor = [...fillColorRgb, 255];

        while (stack.length > 0) {
            const [x, y] = stack.pop();
            if (x < 0 || x >= width || y < 0 || y >= height) continue;

            const idx = (y * width + x) * 4;
            if (!colorsMatch(data, idx, startColor, 35)) continue;
            if (data[idx] === fillColor[0] && data[idx+1] === fillColor[1] && data[idx+2] === fillColor[2]) continue;

            data[idx] = fillColor[0]; data[idx+1] = fillColor[1]; data[idx+2] = fillColor[2]; data[idx+3] = 255;
            stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1]);
        }
        ctx.putImageData(imageData, 0, 0);
    }

    function colorsMatch(data, index, color, tolerance) {
        return Math.abs(data[index] - color[0]) <= tolerance &&
               Math.abs(data[index + 1] - color[1]) <= tolerance &&
               Math.abs(data[index + 2] - color[2]) <= tolerance;
    }

    function hexToRgb(hex) {
        let r = 0, g = 0, b = 0;
        if (hex.length == 7) {
            r = parseInt("0x" + hex.slice(1, 3));
            g = parseInt("0x" + hex.slice(3, 5));
            b = parseInt("0x" + hex.slice(5, 7));
        }
        return [r, g, b];
    }
})();
