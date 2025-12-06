<div id="game-board">
    <button class="help-btn" onclick="explainRules()">🔊 Help</button>

    <div id="windshield">
        <div id="sky"></div>
        <div id="scenery"></div>
        
        <div id="road">
            <div class="lane-marker"></div>
            <div id="player-car">🚗</div>
        </div>
        
        <div id="traffic-light-container">
            <div class="traffic-light">
                <div id="light-red" class="bulb active"></div>
                <div id="light-yellow" class="bulb"></div>
                <div id="light-green" class="bulb"></div>
            </div>
        </div>
    </div>

    <div id="ui-panel">
        <div id="question-box">
            <div id="subject-tag">READY</div>
            <div id="question-text">Loading...</div>
        </div>
        <div id="controls-area"></div>
    </div>
    
    <div id="message"></div>
</div>