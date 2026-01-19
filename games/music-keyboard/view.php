<div id="game-board" style="max-width: 850px; margin: 0 auto; user-select: none;">

    <div class="control-panel glass-panel" style="margin-bottom: 25px; padding: 15px; border-radius: 15px; background: rgba(255,255,255,0.9);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline" onclick="setWaveform('triangle')">🎹 Organ</button>
                    <button class="btn btn-sm btn-outline" onclick="setWaveform('square')">🤖 Robot</button>
                    <button class="btn btn-sm btn-outline" onclick="setWaveform('sawtooth')">🎺 Brass</button>
                </div>
                
                <div style="display: flex; align-items: center; gap: 5px; margin-left: 10px; background: #eee; padding: 5px 10px; border-radius: 20px;">
                    <span>🔊</span>
                    <input type="range" id="vol-slider" min="0" max="1" step="0.1" value="0.5" oninput="setVolume(this.value)" style="width: 80px; cursor: pointer;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <strong>Learn:</strong>
                <select id="song-select" class="form-select" onchange="startSong(this.value)" style="padding: 5px; border-radius: 5px;">
                    <option value="">-- Free Play --</option>
                    <option value="twinkle">Twinkle Twinkle</option>
                    <option value="mary">Mary Had a Little Lamb</option>
                    <option value="shark">Baby Shark</option>
                    <option value="happy">Happy Birthday</option>
                </select>
            </div>
        </div>
    </div>

    <div id="message" style="margin-bottom: 10px; font-size: 1.5em; font-weight: bold; color: #555; height: 30px;">
        Free Play Mode
    </div>

    <div id="piano-wrapper" style="position: relative; padding: 20px; background: #222; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div id="piano-container">
            </div>
    </div>

</div>