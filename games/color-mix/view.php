<div id="game-board">
    <button class="help-btn" onclick="explainRules()">🔊 Help</button>

    <div id="target-display">
        <div class="target-label">MAKE THIS:</div>
        <div id="target-swatch" class="swatch pulse"></div>
        <div id="target-name">Purple</div>
    </div>

    <div id="mixing-station">
        <div id="main-beaker">
            <div id="liquid"></div>
            <div class="bubbles">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <div id="shelf">
        </div>

    <div id="controls">
        <button id="btn-reset" onclick="emptyBeaker()">Empty 🗑️</button>
        <button id="btn-mix" onclick="checkMix()">Mix It! 🧪</button>
    </div>

    <div id="message"></div>
</div>
