.help-btn {
    position: absolute; top: 10px; right: 10px;
    background: none; border: 2px solid white; color: white;
    padding: 5px 10px; border-radius: 15px; cursor: pointer;
}

#game-area {
    position: relative;
    height: 60vh; width: 100%; max-width: 800px;
    margin: 20px auto;
    background: #2f4f4f; /* Dark Slate Gray */
    border: 5px solid var(--panel-border, #000);
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    overflow: hidden;
}

#conveyor {
    position: absolute; top: 50px; left: 0; width: 100%; height: 10px; background: #000;
}

#number-box {
    position: absolute; top: 20px; left: 50%;
    transform: translateX(-50%);
    width: 80px; height: 80px;
    background: var(--star-gold);
    color: #000;
    font-size: 40px; font-weight: bold;
    display: flex; align-items: center; justify-content: center;
    border-radius: 5px; cursor: pointer;
    box-shadow: 0 4px #998100;
    transition: top 5s linear;
    z-index: 10;
}

#visual-hint {
    position: absolute; top: 120px; left: 50%;
    transform: translateX(-50%);
    width: 100px; display: flex; flex-wrap: wrap; justify-content: center;
    pointer-events: none;
}
.dot { width: 10px; height: 10px; background: cyan; border-radius: 50%; margin: 2px; box-shadow: 0 0 5px white; }
.dot.lonely { background: var(--danger-btn); }

.bin {
    position: absolute; bottom: 20px;
    width: 40%; height: 150px;
    border: 4px dashed #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: bold;
    opacity: 0.7; cursor: pointer; transition: 0.2s;
    border-radius: 10px; color: white;
}
.bin:hover { opacity: 1; background: rgba(255,255,255,0.1); transform: scale(1.02); }
#bin-odd { left: 5%; border-color: var(--danger-btn); color: var(--danger-btn); }
#bin-even { right: 5%; border-color: var(--primary-btn); color: var(--primary-btn); }

#message {
    position: absolute; top: 40%; width: 100%;
    font-size: 50px; font-weight: bold;
    text-shadow: 2px 2px 0 #000; display: none; z-index: 20;
    text-align: center;
}

/* Legend */
#legend-card {
    position: absolute; top: 60px; left: 20px; 
    background: var(--card-bg);
    border: 2px solid var(--star-gold);
    border-radius: 10px; padding: 10px; width: 120px;
    cursor: pointer; z-index: 50;
}
.legend-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 5px; padding: 5px; border-radius: 5px;
    font-size: 12px; font-weight: bold; color: white;
}
.legend-even { border: 1px solid var(--primary-btn); }
.legend-odd  { border: 1px solid var(--danger-btn); }
.mini-dot { width: 8px; height: 8px; border-radius: 50%; background: cyan; margin-left: 2px; display: inline-block; }
.mini-dot.red { background: var(--danger-btn); }