<div id="book-stage">

    <div id="word-hunt-overlay" class="game-overlay">
        <h2>🕵️ Word Hunt!</h2>
        <p>Find these words before we read:</p>
        <div id="word-hunt-grid"></div>
    </div>

    <div id="quiz-overlay" class="game-overlay" style="display:none;">
        <h2>🧠 Memory Check!</h2>
        <p id="quiz-question"></p>
        <div id="quiz-options"></div>
    </div>

    <div class="progress-container">
        <div id="progress-fill" class="progress-fill"></div>
    </div>

    <div class="book-container">
        <div class="page-side illustration-side" onclick="interactWithImage()">
            <div class="image-frame">
                <img id="story-image" src="" alt="Story Image">
                <div class="tap-hint">👆 Tap me!</div>
            </div>
        </div>

        <div class="page-side text-side">
            <div id="story-text"></div>
        </div>
    </div>

    <div class="controls-bar">
        <button id="btn-prev" class="nav-btn" onclick="prevPage()">⬅ Back</button>
        <button id="btn-speak" class="action-btn" onclick="readPageKaraoke()">🔊 Read to Me</button>
        <button id="btn-next" class="nav-btn" onclick="nextPage()">Next ➡</button>
    </div>
</div>
