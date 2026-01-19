<div id="game-board" style="max-width: 800px; margin: 0 auto;">

    <div class="mode-selector" style="margin-bottom: 20px; display: flex; justify-content: center; gap: 20px;">
        <button id="btn-free" class="btn btn-lg btn-primary" onclick="setGameMode('free')">🎹 Free Play</button>
        <button id="btn-game" class="btn btn-lg btn-secondary" onclick="setGameMode('game')">🎓 Maestro Mode</button>
    </div>

    <div class="control-panel" style="margin-bottom: 20px; text-align: center;">
        <div class="btn-group">
            <button class="btn btn-sm btn-outline" onclick="setWaveform('sine')">☁️ Flute</button>
            <button class="btn btn-sm btn-outline" onclick="setWaveform('square')">🤖 Robot</button>
            <button class="btn btn-sm btn-outline" onclick="setWaveform('sawtooth')">🎺 Brass</button>
            <button class="btn btn-sm btn-outline" onclick="setWaveform('triangle')">🎮 8-Bit</button>
        </div>
    </div>

    <div id="game-status" style="display:none; margin-bottom: 10px; font-size: 1.2rem; font-weight: bold; color: #2ecc71;">
        Level: <span id="level-indicator">1</span>
        <button id="btn-start-round" class="btn btn-success" style="margin-left: 15px;" onclick="startMaestroRound()">▶ Start Round</button>
    </div>

    <div id="piano-container">
        </div>

    <div id="message" style="margin-top: 20px; font-size: 1.5em; min-height: 40px; color: #555;">Pick a mode to start!</div>
</div>