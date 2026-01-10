# 🛠️ Nuken LMS Developer Guide

Welcome! Nuken LMS is designed to be extensible. You can add new educational modules using standard HTML, CSS, and our powerful `GameBridge` JavaScript API.

The platform handles the "boring stuff" (User Auth, Database Syncing, CSRF Protection, Sound Effects, Text-to-Speech) so you can focus entirely on the game logic.

---

## 📂 1. Module Structure

To create a new game, create a folder in `/games/your-game-name/`. The platform automatically detects folders here.

**Directory Tree:**
```text
/games/
  └── /my-cool-game/
       ├── view.php        (Required: HTML structure)
       ├── style.css       (Required: Visuals)
       ├── game.js         (Required: Logic)
       ├── thumbnail.png   (Optional: Preview image)
       └── /assets/        (Optional: Images/Sounds)

```

### 📄 The 3 Core Files

#### 1. `view.php`

This file contains **only** the HTML specific to your game.

* **DO NOT** include `<html>`, `<head>`, or `<body>` tags. The LMS wraps your content automatically.
* **DO NOT** load `game.js` or `style.css` manually. The LMS loads them for you.

```html
<div id="game-container">
    <div id="question-text">Target: A</div>
    <div id="grid" class="options-grid"></div>
</div>

```

#### 2. `style.css`

Standard CSS. Scoping your classes (e.g., `.mygame-card`) is recommended to avoid conflicts.

#### 3. `game.js`

Your logic file. It **must** wrap code in a closure or event listener to avoid global scope pollution.

---

## 🌉 2. The GameBridge API

`GameBridge` is the global object that connects your game to the LMS core.

### A. Initialization

Call `setupGame` inside `DOMContentLoaded` to register your game.

```javascript
document.addEventListener('DOMContentLoaded', () => {
    GameBridge.setupGame({
        // Text displayed on the start overlay
        instructions: "Find all the red circles!", 
        
        // Optional: Spoken aloud when start screen appears
        speakInstruction: "Can you find the red circles?", 
        
        // Define difficulty levels (creates buttons on start screen)
        levels: [
            { id: 1, label: "Practice" },
            { id: 2, label: "Challenge" }
        ],
        
        // Callback when user clicks a level
        onStart: function(level) {
            // level will be 1 or 2
            startGame(level);
        }
    });
});

```

### B. Feedback & Streaks

The LMS tracks "Streaks" (consecutive correct answers) to trigger the "On Fire" visual effect.

| Method | Description |
| --- | --- |
| `GameBridge.handleCorrect()` | Plays "Ding" sound + Increments Streak + Updates UI. |
| `GameBridge.handleCorrectSilent()` | Increments Streak + Updates UI *without* sound. (Use this if your game plays its own specific success audio). |
| `GameBridge.handleWrong()` | Plays "Buzz" sound + Resets Streak to 0. |

### C. Audio & Text-to-Speech (TTS)

The LMS uses the browser's native SpeechSynthesis API.

* **`GameBridge.speak("Good job!")`**
* Adds text to the queue. Speaks naturally after current sentence finishes.


* **`GameBridge.speakNow("Stop!")`**
* **Interrupts** current speech immediately. Essential for fast-paced games where feedback must be instant.


* **`GameBridge.stopSpeech()`**
* Cancels all audio immediately.



### D. Scoring & Saving

When a game session ends, call `saveScore`. The Bridge automatically handles User ID, CSRF tokens, and Database connections.

```javascript
GameBridge.saveScore({
    score: 100,              // Integer: 0 to 100
    duration: 45,            // Integer: Seconds played
    mistakes: 2,             // Integer: Used for Teacher Reports
    
    // Optional: Free Play Mode
    // true  = Saves data/badges but KEEPS user in the game.
    // false = (Default) Redirects user to the Main Menu.
    noRedirect: false        
});

```

> **💡 Badge Logic:** You do not need to code badge logic in JS. If the server detects the score meets criteria defined in the **Admin Panel**, the LMS will automatically pause the game and show the "New Badge Unlocked" modal overlay.

### E. Celebration

Trigger confetti or video rewards.

```javascript
// Simple Confetti
GameBridge.celebrate("You did it!");

// Video Reward (e.g., for finishing a hard level)
GameBridge.celebrate("Amazing!", "assets/videos/dance_party.mp4");

```

---

## 🎨 3. Theming & Accessibility

The LMS supports themes (Default, Princess, Space). The `<body>` tag receives a class matching the active theme.

**CSS Example:**

```css
/* Default Style */
.card { background: #eee; }

/* Princess Theme Override */
body.theme-princess .card { 
    background: pink; 
    border: 2px solid gold;
    border-radius: 50%; /* Make cards round */
}

/* Space Theme Override */
body.theme-space .card { 
    background: #000033; 
    color: white; 
}

```

**JavaScript Theme Detection:**
You generally don't need this, but if you must load dynamic assets:

```javascript
if (document.body.classList.contains('theme-space')) {
    loadSpaceAliens();
}

```

---

## ⚡ 4. Quick Start Template

Copy this into `games/my-new-game/game.js`:

```javascript
(function() {
    let score = 0;
    let mistakes = 0;
    let startTime = Date.now();

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Click the correct answer!",
            levels: [{ id: 1, label: "Start Game" }],
            onStart: (level) => startGame()
        });
    });

    function startGame() {
        score = 0;
        mistakes = 0;
        startTime = Date.now();
        renderGame();
    }

    function renderGame() {
        // TODO: Build your UI here
    }

    window.handleUserClick = function(isCorrect) {
        if (isCorrect) {
            GameBridge.handleCorrect();
            // GameBridge.speakNow("Correct!"); 
            score += 10;
            if (score >= 100) endGame();
        } else {
            GameBridge.handleWrong();
            mistakes++;
        }
    };

    function endGame() {
        GameBridge.celebrate("Mission Complete!");
        GameBridge.saveScore({
            score: 100,
            duration: Math.floor((Date.now() - startTime) / 1000),
            mistakes: mistakes
        });
    }
})();

```

```
