<button class="help-btn" onclick="var txt = document.getElementById('question-text').innerText; GameBridge.speak(txt.replace(/_+/g, 'blank'));">🔊</button>

<div id="signal-screen" title="Click to replay audio">
    <span id="question-text">WAITING...</span>
</div>

<div id="message"></div>

<div id="frequency-controls">
    </div>