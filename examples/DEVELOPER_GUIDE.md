# 🛠️ Developer Guide: Creating a New Game

Welcome to the **Kids Game Hub** developer guide! This platform is designed to be extensible. You can easily add new educational games using standard HTML, CSS, and our powerful `GameBridge` JavaScript API.

## 📂 Game Structure

To create a new game, create a folder in `/games/your-game-name/`. The platform automatically detects folders here.
Your folder **must** contain these three files:

| File | Purpose |
| :--- | :--- |
| **`view.php`** | The HTML markup for your game. (Just the game container; headers/scripts are auto-loaded). |
| **`style.css`** | Custom visual styling for your game elements. |
| **`game.js`** | The game logic. This is where you interact with `GameBridge`. |

> **Tip:** You can also add an `images/` or `sounds/` folder within your game directory to keep assets organized.

---

## 🌉 The GameBridge API

The platform provides a global `GameBridge` object to handle the "heavy lifting" like scoring, database syncing, text-to-speech, and reward animations.

### 1. Initialization (`setupGame`)

Call this method inside your `DOMContentLoaded` event listener to register your game.

```javascript
GameBridge.setupGame({
    // Text displayed on the initial start overlay
    instructions: "Find all the red circles!", 
    
    // Optional: Text spoken aloud when the start overlay appears
    speakInstruction: "Can you find the red circles?", 
    
    // Define difficulty levels (creates buttons on the start screen)
    levels: [
        { id: 1, label: "Easy" },
        { id: 2, label: "Hard" }
    ],
    
    // Callback function when the user clicks a level button
    onStart: function(level) {
        // level will be 1 or 2 based on the ID above
        startGame(level);
    }
});

```

### 2. Gameplay Feedback

Use these methods to provide immediate feedback to the child. They handle audio cues and visual "Streak" animations (like the "On Fire" effect).

* **`GameBridge.handleCorrect()`**
* Plays a positive "Ding" sound.
* Increments the internal streak counter.


* **`GameBridge.handleCorrectSilent()`**
* Increments the streak counter *without* playing a sound.
* *Use Case:* You are playing your own specific voice-over or sound effect but still want the "On Fire" visual benefits.


* **`GameBridge.handleWrong()`**
* Plays a "Buzz" sound.
* Resets the streak counter to zero.



### 3. Audio & Text-to-Speech (TTS)

The platform uses the browser's native synthesis engine.

* **`GameBridge.speak(text)`**
* Adds text to the speech queue. Will wait for previous sentences to finish.


* **`GameBridge.speakNow(text)`**
* **Interrupts** any current speech and speaks immediately.
* *Use Case:* User tapped an item and needs instant feedback.


* **`GameBridge.stopSpeech()`**
* Cancels all current and queued speech immediately.


* **`GameBridge.playAudio(key)`**
* Plays system sounds. Available keys: `'correct'`, `'wrong'`.



### 4. Winning & Scoring

When the game ends (or a major milestone is reached), you **must** save the score to trigger badges and database tracking.

```javascript
GameBridge.saveScore({
    score: 100,              // Integrity: 0 to 100
    duration: 45,            // Time taken in seconds
    mistakes: 2,             // Number of errors made (affects Report Card)
    
    // Optional: Keep user in the game?
    // true  = "Free Play" mode. Saves data/badges but lets them keep playing.
    // false = (Default) Redirects user to the Main Menu after saving.
    noRedirect: false        
});

```

### 5. Celebration

Trigger the victory animations. This includes confetti and optional video rewards.

```javascript
// Level 1: Confetti & Speech
GameBridge.celebrate("You did it!");

// Level 2: Reward Video Overlay
// Pass a video URL as the second argument to show a popup player
GameBridge.celebrate("Great Job!", "assets/videos/dance.mp4");

```

---

## 🎨 Theming Support

The platform automatically adjusts visuals (like Confetti emojis) based on the active theme.

To support themes in your game's CSS, you can use the global theme classes added to the `<body>` (e.g., `.theme-princess`, `.theme-space`).

**Example `style.css`:**

```css
.my-game-button {
    background: blue;
}

/* Princess Theme Overrides */
body.theme-princess .my-game-button {
    background: pink;
    border: 2px solid gold;
}

```

---

## ⚡ Quick Start Template

Copy this into your `game.js` to get started immediately.

```javascript
(function() {
    // --- Variables ---
    let score = 0;
    let mistakes = 0;
    let startTime = Date.now();
    let currentLevel = 1;

    // --- Initialization ---
    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Tap the correct shapes!",
            levels: [
                { id: 1, label: "Practice" },
                { id: 2, label: "Challenge" }
            ],
            onStart: (level) => {
                currentLevel = level;
                startGame();
            }
        });
    });

    // --- Game Logic ---
    function startGame() {
        score = 0;
        mistakes = 0;
        startTime = Date.now();
        // TODO: Render your game elements here
    }

    function handleInput(isCorrect) {
        if (isCorrect) {
            // 1. Feedback
            GameBridge.handleCorrect();
            score += 10;

            // 2. Win Condition
            if (score >= 100) {
                finishGame();
            }
        } else {
            // 1. Feedback
            GameBridge.handleWrong();
            mistakes++;
        }
    }

    function finishGame() {
        // 1. Celebrate
        GameBridge.celebrate("You are a winner!");

        // 2. Save
        GameBridge.saveScore({
            score: 100,
            duration: (Date.now() - startTime) / 1000,
            mistakes: mistakes
        });
    }
})();

