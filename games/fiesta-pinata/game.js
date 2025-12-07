/* games/fiesta-pinata/game.js */
(function() {
    let score = 0;
    let questionsAnswered = 0;
    const QUESTIONS_TO_WIN = 10;
    let difficulty = 1;
    let currentTarget = null;
    let sessionMistakes = 0;
    let startTime = Date.now();

    // --- SMART SHUFFLE DECKS ---
    let availableLevel1 = [];
    let availableLevel2 = [];

    // --- LEVEL 1 DATA: Basics (Colors, Numbers, Shapes) ---
    const level1Data = [
        // Colors
        { t: 'Rojo',      type: 'color', val: '#e74c3c' }, 
        { t: 'Azul',      type: 'color', val: '#3498db' }, 
        { t: 'Verde',     type: 'color', val: '#2ecc71' }, 
        { t: 'Amarillo',  type: 'color', val: '#f1c40f' }, 
        { t: 'Naranja',   type: 'color', val: '#e67e22' }, 
        { t: 'Morado',    type: 'color', val: '#9b59b6' }, 
        { t: 'Rosa',      type: 'color', val: '#ff9ff3' }, 
        { t: 'Negro',     type: 'color', val: '#2d3436' }, 
        { t: 'Blanco',    type: 'color', val: '#ffffff' }, 
        
        // Numbers (1-10)
        { t: 'Uno',       type: 'number', val: '1' },
        { t: 'Dos',       type: 'number', val: '2' },
        { t: 'Tres',      type: 'number', val: '3' },
        { t: 'Cuatro',    type: 'number', val: '4' },
        { t: 'Cinco',     type: 'number', val: '5' },
        { t: 'Seis',      type: 'number', val: '6' },
        { t: 'Siete',     type: 'number', val: '7' },
        { t: 'Ocho',      type: 'number', val: '8' },
        { t: 'Nueve',     type: 'number', val: '9' },
        { t: 'Diez',      type: 'number', val: '10' },

        // NEW: Shapes (Using Emojis as values)
        { t: 'Círculo',   type: 'shape', val: '🔴' },
        { t: 'Cuadrado',  type: 'shape', val: '🟦' },
        { t: 'Estrella',  type: 'shape', val: '⭐' },
        { t: 'Triángulo', type: 'shape', val: '🔺' },
        { t: 'Corazón',   type: 'shape', val: '💜' }
    ];

    // --- LEVEL 2 DATA: Vocabulary (Expanded) ---
    const level2Data = [
        // Greetings & Basics
        { eng: 'Hello',   span: 'Hola' },
        { eng: 'Goodbye', span: 'Adiós' },
        { eng: 'Please',  span: 'Por favor' },
        { eng: 'Thanks',  span: 'Gracias' },
        { eng: 'Friend',  span: 'Amigo' },
        { eng: 'Yes',     span: 'Sí' },
        { eng: 'No',      span: 'No' },

        // Family
        { eng: 'Family',  span: 'Familia' },
        { eng: 'Mother',  span: 'Madre' },
        { eng: 'Father',  span: 'Padre' },
        { eng: 'Brother', span: 'Hermano' },
        { eng: 'Sister',  span: 'Hermana' },
        { eng: 'Baby',    span: 'Bebé' },
        { eng: 'Grandma', span: 'Abuela' },
        { eng: 'Grandpa', span: 'Abuelo' },

        // Animals
        { eng: 'Cat',     span: 'Gato' },
        { eng: 'Dog',     span: 'Perro' },
        { eng: 'Fish',    span: 'Pez' },
        { eng: 'Bird',    span: 'Pájaro' },
        { eng: 'Cow',     span: 'Vaca' },
        { eng: 'Pig',     span: 'Cerdo' },
        { eng: 'Horse',   span: 'Caballo' },
        { eng: 'Chicken', span: 'Pollo' },

        // Body
        { eng: 'Hand',    span: 'Mano' },
        { eng: 'Foot',    span: 'Pie' },
        { eng: 'Head',    span: 'Cabeza' },
        { eng: 'Eye',     span: 'Ojo' },
        { eng: 'Nose',    span: 'Nariz' },
        { eng: 'Mouth',   span: 'Boca' },

        // Food
        { eng: 'Water',   span: 'Agua' },
        { eng: 'Milk',    span: 'Leche' },
        { eng: 'Bread',   span: 'Pan' },
        { eng: 'Apple',   span: 'Manzana' },
        { eng: 'Banana',  span: 'Plátano' },
        { eng: 'Cheese',  span: 'Queso' },
        { eng: 'Egg',     span: 'Huevo' },

        // Nature & World
        { eng: 'Sun',     span: 'Sol' },
        { eng: 'Moon',    span: 'Luna' },
        { eng: 'Star',    span: 'Estrella' },
        { eng: 'Tree',    span: 'Árbol' },
        { eng: 'Flower',  span: 'Flor' },
        { eng: 'House',   span: 'Casa' },
        { eng: 'School',  span: 'Escuela' },
        { eng: 'Book',    span: 'Libro' },
        { eng: 'Car',     span: 'Coche' },
        { eng: 'Ball',    span: 'Pelota' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        GameBridge.setupGame({
            instructions: "Click the correct answer to hit the Piñata!",
            levels: [
                { id: 1, label: "Basics (Pre-K)" },
                { id: 2, label: "Words (Grades 1-2)" }
            ],
            onStart: (level) => {
                difficulty = level;
                score = 0;
                questionsAnswered = 0;
                sessionMistakes = 0;
                startTime = Date.now();
                GameBridge.updateScore(0);
                
                availableLevel1 = [];
                availableLevel2 = [];
                
                nextQuestion();
            }
        });
    });

    function nextQuestion() {
        const container = document.getElementById('candy-container');
        container.innerHTML = '';
        
        let targetPool = (difficulty === 1) ? availableLevel1 : availableLevel2;
        const masterList = (difficulty === 1) ? level1Data : level2Data;

        if (targetPool.length === 0) {
            targetPool = [...masterList];
            if(difficulty === 1) availableLevel1 = targetPool;
            else availableLevel2 = targetPool;
        }

        const randIndex = Math.floor(Math.random() * targetPool.length);
        currentTarget = targetPool[randIndex];
        targetPool.splice(randIndex, 1);

        if (difficulty === 1) {
            // Level 1: "Find Rojo"
            GameBridge.speak(currentTarget.t);
            document.getElementById('message').innerText = "Find: " + currentTarget.t;
        } else {
            // Level 2: "Find the word for CAT"
            GameBridge.speak("Find the word for " + currentTarget.eng);
            document.getElementById('message').innerText = "Translate: " + currentTarget.eng;
        }

        // Generate Options
        let options = [currentTarget];
        while (options.length < 3) {
            let r = masterList[Math.floor(Math.random() * masterList.length)];
            // Avoid duplicates AND ensure Level 1 options are same type (don't mix colors with numbers)
            if (!options.includes(r)) {
                if (difficulty === 1) {
                    if (r.type === currentTarget.type) options.push(r);
                } else {
                    options.push(r);
                }
            }
        }
        
        options.sort(() => Math.random() - 0.5);

        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'candy-btn';
            
            if (difficulty === 1) {
                if(opt.type === 'color') {
                    btn.style.backgroundColor = opt.val;
                    if(opt.val === '#ffffff') btn.style.border = "3px solid #ccc";
                    btn.innerText = ""; 
                } else {
                    btn.innerText = opt.val; // Number or Emoji Shape
                }
            } else {
                btn.innerText = opt.span;
            }

            btn.onclick = () => checkAnswer(opt, btn);
            container.appendChild(btn);
        });
    }

    function checkAnswer(selected, btn) {
        if (selected === currentTarget) {
            score += 10;
            questionsAnswered++;
            GameBridge.updateScore(score);
            
            const pinata = document.getElementById('pinata');
            pinata.classList.add('hit-anim');
            setTimeout(() => pinata.classList.remove('hit-anim'), 500);

            GameBridge.celebrate("Muy bien!"); 

            if (questionsAnswered >= QUESTIONS_TO_WIN) {
                 GameBridge.saveScore({
                    score: score,
                    duration: Math.floor((Date.now() - startTime) / 1000), 
                    mistakes: sessionMistakes
                });
            } else {
                setTimeout(nextQuestion, 1500);
            }
        } else {
            sessionMistakes++;
            GameBridge.playAudio('wrong');
            GameBridge.speak("Try again");
            btn.style.opacity = "0.5";
        }
    }

    window.explainRules = function() {
        GameBridge.speak("Listen to the word and click the matching candy to break the piñata.");
    }
})();