/* games/balloon-pop/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let level1State = { total: 0, toPop: 0, popped: 0 };
    let level2Answer = 0;
    let sessionMistakes = 0;
    
    // TIME TRACKING START
    let startTime = Date.now();

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Click the balloons to solve the math!",
            levels: [
                { id: 1, label: "Click & Count (Pre-K)" },
                { id: 2, label: "Subtraction (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                
                // RESET TIMER
                startTime = Date.now();

                document.getElementById('balloon-area').innerHTML = '';
                document.getElementById('number-pad').style.display = 'none';
                nextRound();
            }
        });
    });

    function nextRound() {
        const cloud = document.getElementById('question-text');
        const area = document.getElementById('balloon-area');
        const pad = document.getElementById('number-pad');
        
        area.innerHTML = '';
        pad.style.display = 'none';
        pad.innerHTML = '';
        
        cloud.innerText = "";

        if (difficulty === 1) {
            // --- LEVEL 1 ---
            let startCount = Math.floor(Math.random() * 5) + 3; 
            let takeAway = Math.floor(Math.random() * (startCount - 1)) + 1; 
            
            level1State = { total: startCount, toPop: takeAway, popped: 0 };
            
            cloud.innerText = "Click " + takeAway; 
            GameBridge.speak("Click " + takeAway + " balloons.");

            for(let i=0; i<startCount; i++) {
                spawnBalloon(area, false, null); 
            }

        } else {
            // --- LEVEL 2 ---
            let a = Math.floor(Math.random() * 11) + 5; 
            let b = Math.floor(Math.random() * 5) + 1;  
            level2Answer = a - b;

            cloud.innerText = `${a} - ${b} = ?`;
            GameBridge.speak(`${a} minus ${b}`);

            let answers = [level2Answer];
            while(answers.length < 3) {
                let fake = level2Answer + Math.floor(Math.random() * 5) - 2;
                if(fake >= 0 && !answers.includes(fake)) answers.push(fake);
            }
            
            answers.sort(() => Math.random() - 0.5);
            
            answers.forEach((num, i) => {
                setTimeout(() => spawnBalloon(area, true, num), i * 1500);
            });
        }
    }

    function spawnBalloon(container, isMoving, numberValue) {
        const el = document.createElement('div');
        el.className = 'balloon';
        
        const hues = [0, 40, 100, 200, 280]; 
        const hue = hues[Math.floor(Math.random() * hues.length)];
        el.style.background = `radial-gradient(circle at 30% 30%, hsl(${hue}, 80%, 70%), hsl(${hue}, 80%, 50%))`;

        el.style.left = (Math.random() * 70 + 10) + '%';
        
        if (!isMoving) {
            el.style.bottom = (Math.random() * 30 + 10) + '%'; 
        } else {
            el.classList.add('float-up');
            el.innerText = numberValue; 
        }

        el.onclick = (e) => {
            e.stopPropagation(); 
            if (el.classList.contains('pop-anim')) return;

            if (difficulty === 1) {
                if (level1State.popped < level1State.toPop) {
                    popVisual(el);
                    level1State.popped++;
                    
                    if (level1State.popped === level1State.toPop) {
                        GameBridge.speak("How many are left?");
                        document.getElementById('question-text').innerText = "How many left?";
                        showNumberPad(level1State.total - level1State.toPop);
                    } else {
                        let remaining = level1State.toPop - level1State.popped;
                        if(remaining > 0) GameBridge.speak(remaining.toString());
                    }
                }
            } else {
                if (numberValue === level2Answer) {
                    popVisual(el);
                    handleWin();
                } else {
                    sessionMistakes++;
                    GameBridge.playAudio('wrong');
                    el.style.opacity = '0.5';
                }
            }
        };

        container.appendChild(el);
    }

    function popVisual(el) {
        GameBridge.playAudio('correct'); 
        el.classList.add('pop-anim');
        setTimeout(() => el.remove(), 200);
    }

    function showNumberPad(correctAnswer) {
        const pad = document.getElementById('number-pad');
        pad.style.display = 'flex';
        
        let opts = [correctAnswer];
        while(opts.length < 3) {
            let f = Math.floor(Math.random() * 5) + 1;
            if(!opts.includes(f)) opts.push(f);
        }
        opts.sort(() => Math.random() - 0.5);

        opts.forEach(num => {
            const btn = document.createElement('button');
            btn.className = 'num-btn';
            btn.innerText = num;
            btn.onclick = () => {
                if (num === correctAnswer) handleWin();
                else {
                    sessionMistakes++;
                    GameBridge.playAudio('wrong');
                }
            };
            pad.appendChild(btn);
        });
    }

    function handleWin() {
        score += 10;
        questionsAnswered++;
        GameBridge.updateScore(score);
        GameBridge.celebrate("Correct!");

        if (questionsAnswered >= QUESTIONS_TO_WIN) {
            // CALCULATE DURATION
            GameBridge.saveScore({
                score: score,
                duration: Math.floor((Date.now() - startTime) / 1000),
                mistakes: sessionMistakes
            });
        } else {
            setTimeout(nextRound, 1500);
        }
    }
    
    window.explainRules = function() {
        GameBridge.speak("Click the balloons to solve the math!");
    }
})();