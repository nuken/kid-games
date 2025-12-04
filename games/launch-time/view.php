<div id="mission-board">
    <h2>Launch Time:</h2>
    <div id="target-time">--:--</div>
</div>

<div id="message"></div>

<div id="clock-face">
    <div class="center-dot"></div>
    <div class="hand hand-hour" id="hour-hand"></div>
    <div class="hand hand-minute" id="min-hand"></div>
    </div>

<div id="controls">
    <div class="control-group">
        <button class="btn-ctrl" onclick="changeHour(1)">+ 1 Hour</button>
        <button class="btn-ctrl" onclick="changeHour(-1)">- 1 Hour</button>
    </div>
    <div class="control-group">
        <button class="btn-ctrl btn-min" onclick="changeMinute(5)">+ 5 Min</button>
        <button class="btn-ctrl btn-min" onclick="changeMinute(-5)">- 5 Min</button>
    </div>
    <div class="control-group" id="fine-controls" style="display:none;">
        <button class="btn-ctrl btn-fine" onclick="changeMinute(1)">+ 1 Min</button>
        <button class="btn-ctrl btn-fine" onclick="changeMinute(-1)">- 1 Min</button>
    </div>
</div>

<button id="btn-launch" onclick="checkTime()">LAUNCH! 🚀</button>