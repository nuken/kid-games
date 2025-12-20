/* games/rocket-shop/game.js */
let currentTotal = 0;
let targetPrice = 0;
let score = 0;
let questionsAnswered = 0;
const totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;
const items = ["🚀", "⛽", "🔧", "⚙️", "🔋", "🛰️", "🛸"];

// NEW: Track mistakes
let sessionMistakes = 0;

document.addEventListener('DOMContentLoaded', () => {
    GameBridge.setupGame({
        instructions: window.LANG.game_rocket_shop_instr_text,
        levels: [
            { id: 1, label: window.LANG.game_rocket_shop_level1 },
            { id: 2, label: window.LANG.game_rocket_shop_level2 }
        ],
        onStart: (level) => {
            difficulty = level;
            score = 0;
            questionsAnswered = 0;
            sessionMistakes = 0; // Reset
            document.querySelector('.coin.dollar').style.display = (level === 2) ? 'flex' : 'none';
            startTime = Date.now();
            spawnItem(false);
        }
    });
});

function formatMoney(cents) {
    if (cents >= 100) {
        let d = Math.floor(cents / 100);
        let c = cents % 100;
        return "$" + d + "." + (c < 10 ? "0" + c : c);
    }
    return cents + "¢";
}

function spawnItem(silent) {
    currentTotal = 0;
    document.getElementById('current-total').innerText = formatMoney(currentTotal);
    document.getElementById('message').innerText = "";
    document.getElementById('item-display').innerText = items[Math.floor(Math.random() * items.length)];

    if (difficulty === 2) {
        targetPrice = Math.floor(Math.random() * 80) * 5 + 100;
    } else {
        targetPrice = Math.floor(Math.random() * 90) + 5;
    }

    document.getElementById('target-price').innerText = formatMoney(targetPrice);
    if(!silent) GameBridge.speak(window.LANG.game_rocket_shop_price_is + " " + formatMoney(targetPrice));
}

function addCoin(amt) {
    currentTotal += amt;
    document.getElementById('current-total').innerText = formatMoney(currentTotal);
}

function resetCoins() {
    currentTotal = 0;
    document.getElementById('current-total').innerText = formatMoney(currentTotal);
}

function checkPurchase() {
    if (currentTotal === targetPrice) {
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);

        // CHECK IF GAME IS OVER
        if (questionsAnswered >= totalQuestions) {
            // CHANGE: Add your video filename here
            GameBridge.celebrate("Mission Accomplished! You bought all the parts!", "assets/videos/rocket_win.mp4");
            
            GameBridge.saveScore({
                score: score,
                duration: Math.floor((Date.now() - startTime)/1000),
                mistakes: sessionMistakes
            });
        } else {
            // NORMAL ROUND WIN
            GameBridge.celebrate(window.LANG.game_rocket_shop_sold);
            setTimeout(() => spawnItem(false), 1500);
        }
    } else if (currentTotal > targetPrice) {
        sessionMistakes++; // Overpaid is a mistake
        GameBridge.speak(window.LANG.game_rocket_shop_too_much);
    } else {
        sessionMistakes++; // Underpaid is a mistake
        GameBridge.speak(window.LANG.game_rocket_shop_not_enough);
    }
}
