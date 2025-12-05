<div id="shop-counter">
    <div id="item-display">🚀</div> 
    <div id="price-tag"><span id="target-price">0</span></div>
</div>

<div id="message"></div>

<div id="payment-slot">
    <span id="current-total">0</span>
</div>

<div id="wallet">
    <div class="coin dollar" onclick="addCoin(100)">$1.00</div>
    <div class="coin quarter" onclick="addCoin(25)">25¢</div>
    <div class="coin dime" onclick="addCoin(10)">10¢</div>
    <div class="coin nickel" onclick="addCoin(5)">5¢</div>
    <div class="coin penny" onclick="addCoin(1)">1¢</div>
</div>

<div id="controls">
    <button class="btn btn-reset" onclick="resetCoins()">Clear</button>
    <button class="btn btn-buy" onclick="checkPurchase()">BUY PART</button>
</div>