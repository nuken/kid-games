/* games/rocket-shop/game.js */

let currentTotal = 0;
let targetPrice = 0;
let score = 0;
let questionsAnswered = 0;
let totalQuestions = 10;
let startTime = Date.now();
let difficulty = 1;

const USER_ID = window.gameData ? window.gameData.userId : 1;
const GAME_ID = 2; // Rocket Shop
const items = ["🚀", "⛽", "🔧", "⚙️", "🔋", "🛰️", "🛸"];

const priceDisplay = document.getElementById('target-price');
const totalDisplay = document.getElementById('current-total');
const itemDisplay = document.getElementById('item-display');
const message = document.getElementById('message');

// --- HELPER FUNCTIONS ---
function formatMoney(cents) {
    if (cents >= 100) {
        let dollars = Math.floor(cents / 100);
        let remainingCents = cents % 100;
        let centString = remainingCents < 10 ? "0" + remainingCents : remainingCents;
        return "$" + dollars + "." + centString;
    } else {
        return cents + "¢";
    }
}

function speakMoney(cents) {
    if (cents >= 100) {
        let dollars = Math.floor(cents / 100);
        let remainingCents = cents % 100;
        let text = dollars + " dollar" + (dollars > 1 ? "s" : "");
        if (remainingCents > 0) {
            text += " and " + remainingCents + " cent" + (remainingCents > 1 ? "s" : "");
        }
        return text;
    } else {
        return cents + " cent" + (cents !== 1 ? "s" : "");
    }
}

// --- 1. GAME INITIALIZATION ---
function initGame(level = 1) {
    difficulty = level;
    document.getElementById('start-overlay').style.display = 'none';
    if (window.voiceList && window.voiceList.length === 0) window.loadVoices();

    if (difficulty === 2) {
        window.speakText("Commander Mode. Prices are higher. Use the dollar bill!");
        // Show dollar bill
        document.querySelector('.coin.dollar').style.display = 'flex';
    } else {
        window.speakText("Welcome to Rocket Shop. Look at the red price tag. Click the coins to put money in the slot.");
        // Hide dollar bill in Level 1
        document.querySelector('.coin.dollar').style.display = 'none';
    }

    startTime = Date.now();

    // Reset game
    questionsAnswered = 0;
    score = 0;
    document.getElementById('score').innerText = score;
    spawnItem(false);
}

function playInstructions() {
    window.speakText("Welcome to Rocket Shop. Look at the red price tag. Click the coins to put money in the slot until it matches the price.");
}

// --- 2. MAIN GAME LOOP ---
function spawnItem(isSilent = false) {
    currentTotal = 0;
    updateTotalDisplay();
    message.innerText = "";
    document.body.style.backgroundColor = "var(--space-blue)";

    itemDisplay.innerText = items[Math.floor(Math.random() * items.length)];

    // Difficulty Logic
    if (difficulty === 2) {
        // LEVEL 2: Prices between 100 (1.00) and 500 (5.00)
        // Ensure it's a multiple of 5 to be fair/clean
        targetPrice = Math.floor(Math.random() * 80) * 5 + 100;
    } else {
        // LEVEL 1: Standard prices < 100
        if (questionsAnswered < 3) {
            targetPrice = Math.floor(Math.random() * 9) * 10 + 10;
        } else if (questionsAnswered < 6) {
            targetPrice = Math.floor(Math.random() * 18) * 5 + 5;
        } else {
            targetPrice = Math.floor(Math.random() * 98) + 1;
        }
    }

    priceDisplay.innerText = formatMoney(targetPrice);

    // Only speak if we are NOT silent (Silent used for background load)
    if (!isSilent) {
        window.speakText("Find " + speakMoney(targetPrice));
    }
}

function addCoin(amount) {
    currentTotal += amount;
    updateTotalDisplay();

    // Speak the TOTAL amount now, not just the coin value
    window.speakText(speakMoney(currentTotal));
}

function resetCoins() {
    currentTotal = 0;
    updateTotalDisplay();
    message.innerText = "";
    window.speakText("Cleared.");
}

function updateTotalDisplay() {
    // ALWAYS SHOW TOTAL NOW
    totalDisplay.innerText = formatMoney(currentTotal);

    if (currentTotal > targetPrice) {
        if (difficulty === 1) totalDisplay.style.color = "#e74c3c";
        message.innerText = "Too much!";
        message.style.color = "#e74c3c";
    } else if (currentTotal === targetPrice) {
        if (difficulty === 1) totalDisplay.style.color = "#2ecc71";
        message.innerText = "Perfect!";
        message.style.color = "#2ecc71";
    } else {
        totalDisplay.style.color = "var(--star-gold)";
        message.innerText = "";
    }
}

function checkPurchase() {
    if (currentTotal === targetPrice) {
        processWin();
    } else if (currentTotal > targetPrice) {
        // Shake animation
        document.getElementById('payment-slot').style.transform = "translateX(10px)";
        setTimeout(() => document.getElementById('payment-slot').style.transform = "translateX(0)", 100);
        window.speakText("That is too much money! Press Clear.");
    } else {
        message.innerText = "Not enough money yet!";
        window.speakText("Not enough money yet.");
    }
}

function processWin() {
    score += 10;
    questionsAnswered++;
    document.getElementById('score').innerText = score;

    message.innerText = "SOLD!";
    document.body.style.backgroundColor = "var(--nebula-green)";

    const praise = ["Sold!", "Great job!", "Ka-ching!", "Excellent!"];
    window.speakText(praise[Math.floor(Math.random() * praise.length)]);

    if (window.playConfettiEffect) window.playConfettiEffect();

    if (questionsAnswered >= totalQuestions) {
        endGame();
    } else {
        setTimeout(() => spawnItem(false), 1500);
    }
}

function endGame() {
    document.body.innerHTML = "<h1>SHOP CLOSED!</h1><h2>Great Job!</h2><p>Saving...</p>";

    let duration = Math.floor((Date.now() - startTime) / 1000);

    fetch('../../api/save_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            game_id: GAME_ID,
            score: score,
            duration: duration
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.new_badges && data.new_badges.length > 0) {
                let badgeNames = data.new_badges.map(b => b.icon + " " + b.name).join("\n");
                alert("🌟 MISSION PATCH EARNED! 🌟\n\n" + badgeNames);
            }
            setTimeout(() => window.location.href = "../../index.php", 2000);
        })
        .catch(error => console.error('Error:', error));
}

// Wait for user to select level
// spawnItem(true);