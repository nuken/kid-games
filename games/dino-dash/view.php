/* games/dino-dash/view.php */
<div id="game-area">
    <button class="help-btn" onclick="explainRules()">🔊 Help</button>

    <div id="track-container">
        <div class="finish-line">🏁</div>
        
        <div class="lane" id="lane-player">
            <div class="racer" id="player-car">🏎️</div>
        </div>

        <div class="lane" id="lane-cpu">
            <div class="racer" id="cpu-dino">🦖</div>
        </div>
    </div>

    <div id="dashboard">
        <div id="question-display">Loading...</div>
        <div id="options-grid"></div>
    </div>
    
    <div id="message-overlay"></div>
</div>