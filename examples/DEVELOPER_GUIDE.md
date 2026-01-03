
### 1. New File: `examples/DEVELOPER_GUIDE.md`

Save this file in your new `examples/` folder.

```markdown
# 🕹️ Developer Guide: Creating a New Game

This guide explains how to add a new game to the Kids Game Hub and documents the `GameBridge` API options.

## 📂 Game Structure

To create a new game, create a folder in `/games/my-new-game/`. It must contain at least three files:

1.  **view.php**: The HTML structure of your game.
2.  **style.css**: The visual styling.
3.  **game.js**: The logic that interacts with the system.

## 🌉 The GameBridge API

The platform provides a global `GameBridge` object to handle scoring, audio, and state.

### 1. Initialization

Call `setupGame` inside your `DOMContentLoaded` event.

```javascript
GameBridge.setupGame({
    // Text shown on the start overlay
    instructions: "Find all the red circles!", 
    
    // Optional: Spoken instructions when the game starts
    speakInstruction: "Touch the red circles.", 
    
    // Define difficulty levels (buttons appear on start screen)
    levels: [
        { id: 1, label: "Easy" },
        { id: 2, label: "Hard" }
    ],
    
    // Callback when user clicks a level
    onStart: function(level) {
        startGame(level);
    }
});

```

### 2. Gameplay Feedback

Use these to trigger global sounds and visual streak effects (like the "On Fire" effect).

* **`GameBridge.handleCorrect()`**: Plays a "ding" sound and increases the visual streak counter.
* **`GameBridge.handleCorrectSilent()`**: Increases streak counter but plays no sound (useful if your game has its own specific voice-overs).
* **`GameBridge.handleWrong()`**: Plays a "buzz" sound and resets the streak to zero.

### 3. Audio & Text-to-Speech

* **`GameBridge.speak(text)`**: Adds text to the speech queue.
* **`GameBridge.speakNow(text)`**: Interrupts current speech to speak immediately.
* **`GameBridge.playAudio(key)`**: Plays system sounds. Keys: `'correct'`, `'wrong'`.

### 4. Winning & Scoring

When the game is finished, you must save the score to award badges.

```javascript
GameBridge.saveScore({
    score: 100,              // 0-100
    duration: 45,            // Time taken in seconds
    mistakes: 2,             // Number of errors made
    noRedirect: false        // Set true to keep user in game (e.g., Free Play mode)
});

```

### 5. Celebration

Trigger the victory animations.

```javascript
// Basic Confetti & Speech
GameBridge.celebrate("You did it!");

// Reward Video (Overlay)
GameBridge.celebrate("Great Job!", "assets/videos/dance.mp4");

```

---

## 📝 Example: Simple Clicker Game (`game.js`)

```javascript
(function() {
    let score = 0;
    let startTime = Date.now();

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Click the button 10 times!",
            levels: [{ id: 1, label: "Start" }],
            onStart: (level) => {
                score = 0;
                startTime = Date.now();
                renderGame();
            }
        });
    });

    function renderGame() {
        const btn = document.getElementById('click-me');
        btn.onclick = () => {
            score += 10;
            
            // 1. Feedback
            GameBridge.handleCorrect();
            
            // 2. Win Condition
            if (score >= 100) {
                GameBridge.celebrate("You won!");
                
                // 3. Save Data
                GameBridge.saveScore({
                    score: 100,
                    duration: (Date.now() - startTime) / 1000,
                    mistakes: 0
                });
            }
        };
    }
})();

```

```

