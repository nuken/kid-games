/* games/rocket-shop/game.js */
let currentTotal = 0;
let targetPrice = 0;
let score = 0;
let questionsAnswered = 0;
const totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;
const items = ["🚀", "⛽", "🔧", "⚙️", "🔋", "🛰️", "🛸"];

document.addEventListener('DOMContentLoaded', () => {
    GameBridge.setupGame({
        instructions: "Welcome to Rocket Shop. Match coins to the price tag.",
        levels: [
            { id: 1, label: "Cadet (Cents)" },
            { id: 2, label: "Commander (Dollars)" }
        ],
        onStart: (level) => {
            difficulty = level;
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
    if(!silent) GameBridge.speak("Price is " + formatMoney(targetPrice));
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
        GameBridge.celebrate("Sold!");
        
        if (questionsAnswered >= totalQuestions) {
            GameBridge.saveScore({ score: score, duration: Math.floor((Date.now() - startTime)/1000) });
        } else {
            setTimeout(() => spawnItem(false), 1500);
        }
    } else if (currentTotal > targetPrice) {
        GameBridge.speak("Too much!");
    } else {
        GameBridge.speak("Not enough.");
    }
}