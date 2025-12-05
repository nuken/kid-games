<div id="game-area">
    <button class="help-btn" onclick="explainRules()">🔊 Help</button>

    <div id="legend-card" onclick="explainRules()">
        <div style="font-size:12px; margin-bottom:5px; opacity:0.8;">LEGEND</div>
        <div class="legend-row legend-even">
            <div class="legend-text">EVEN</div>
            <div class="legend-dots"><div class="mini-dot"></div><div class="mini-dot"></div></div>
        </div>
        <div class="legend-row legend-odd">
            <div class="legend-text">ODD</div>
            <div class="legend-dots"><div class="mini-dot"></div><div class="mini-dot red"></div></div>
        </div>
    </div>

    <div id="conveyor"></div>
    <div id="number-box" onclick="showHint()">?</div>
    <div id="visual-hint"></div>
    <div id="message"></div>

    <div class="bin" id="bin-odd" onclick="checkAnswer('odd')">ODD<br>(Lonely)</div>
    <div class="bin" id="bin-even" onclick="checkAnswer('even')">EVEN<br>(Buddies)</div>
</div>