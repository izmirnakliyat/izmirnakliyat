// MY Nakliyat - Eğitici Oyunlar JavaScript
class EducationalGames {
    constructor() {
        this.currentGame = null;
        this.gameScore = 0;
        this.gameTimer = null;
        this.init();
    }

    init() {
        console.log('Eğitici oyunlar yüklendi');
        this.bindGameCards();
    }

    bindGameCards() {
        const gameCards = document.querySelectorAll('.game-card');
        gameCards.forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                const gameType = card.dataset.game;
                if (gameType) {
                    this.openGame(gameType);
                }
            });
        });
    }

    openGame(gameType) {
        this.currentGame = gameType;
        this.gameScore = 0;
        
        // Modal oluştur
        this.createGameModal(gameType);
        
        // Oyunu başlat
        this.startGame(gameType);
    }

    createGameModal(gameType) {
        // Mevcut modal varsa kaldır
        const existingModal = document.getElementById('gameModal');
        if (existingModal) {
            existingModal.remove();
        }

        const gameTitles = {
            'memory': '🧠 Hafıza Kartları',
            'colors': '🎨 Renk Eşleştirme',
            'numbers': '🔢 Sayı Sayma',
            'shapes': '⭐ Şekil Eşleştirme',
            'letters': '📝 Harf Öğrenme',
            'puzzle': '🧩 Puzzle'
        };

        const modalHTML = `
            <div id="gameModal" class="game-modal">
                <div class="game-modal-content">
                    <div class="game-modal-header">
                        <h2 class="game-modal-title">${gameTitles[gameType]}</h2>
                        <button class="close-modal" onclick="games.closeGame()">&times;</button>
                    </div>
                    <div class="game-modal-body" id="modalBody">
                        <div class="game-container">
                            <div class="game-instructions" id="gameInstructions"></div>
                            <div class="game-score">Skor: <span id="gameScore">0</span></div>
                            <div class="game-area" id="gameArea"></div>
                            <div class="game-controls">
                                <button onclick="games.resetGame()">Yeniden Başla</button>
                                <button onclick="games.closeGame()">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Modal stillerini ekle
        this.addModalStyles();
        
        // Modalı göster
        document.getElementById('gameModal').style.display = 'block';
        
        // Modal dışına tıklandığında kapat
        document.getElementById('gameModal').addEventListener('click', (e) => {
            if (e.target.id === 'gameModal') {
                this.closeGame();
            }
        });
    }

    addModalStyles() {
        if (!document.getElementById('gameModalStyles')) {
            const styles = `
                <style id="gameModalStyles">
                    .game-modal {
                        display: none;
                        position: fixed;
                        z-index: 1000;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0,0,0,0.8);
                        backdrop-filter: blur(5px);
                    }
                    
                    .game-modal-content {
                        background-color: white;
                        margin: 5% auto;
                        padding: 0;
                        border-radius: 20px;
                        width: 90%;
                        max-width: 800px;
                        max-height: 80vh;
                        overflow: hidden;
                        position: relative;
                    }
                    
                    .game-modal-header {
                        background: linear-gradient(135deg, #667eea, #764ba2);
                        color: white;
                        padding: 20px 30px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    
                    .game-modal-title {
                        font-size: 1.5rem;
                        font-weight: bold;
                        margin: 0;
                    }
                    
                    .close-modal {
                        color: white;
                        font-size: 2rem;
                        font-weight: bold;
                        cursor: pointer;
                        background: none;
                        border: none;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .close-modal:hover {
                        opacity: 0.7;
                    }
                    
                    .game-modal-body {
                        padding: 30px;
                        max-height: 60vh;
                        overflow-y: auto;
                    }
                    
                    .game-container {
                        text-align: center;
                        padding: 20px;
                    }
                    
                    .game-instructions {
                        background: #f8f9fa;
                        padding: 20px;
                        border-radius: 10px;
                        margin-bottom: 20px;
                        text-align: left;
                    }
                    
                    .game-instructions h3 {
                        color: #333;
                        margin-bottom: 10px;
                    }
                    
                    .game-instructions ul {
                        margin: 0;
                        padding-left: 20px;
                    }
                    
                    .game-instructions li {
                        margin-bottom: 5px;
                        color: #666;
                    }
                    
                    .game-area {
                        background: white;
                        border: 2px solid #e9ecef;
                        border-radius: 15px;
                        padding: 30px;
                        margin: 20px 0;
                        min-height: 300px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-direction: column;
                    }
                    
                    .game-controls {
                        margin-top: 20px;
                    }
                    
                    .game-controls button {
                        background: linear-gradient(135deg, #667eea, #764ba2);
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 20px;
                        margin: 0 10px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.3s ease;
                    }
                    
                    .game-controls button:hover {
                        transform: scale(1.05);
                        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                    }
                    
                    .game-score {
                        font-size: 1.2rem;
                        font-weight: bold;
                        color: #333;
                        margin: 10px 0;
                    }
                    
                    @media (max-width: 768px) {
                        .game-modal-content {
                            width: 95%;
                            margin: 10% auto;
                        }
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }
    }

    startGame(gameType) {
        switch(gameType) {
            case 'memory':
                this.startMemoryGame();
                break;
            case 'colors':
                this.startColorGame();
                break;
            case 'numbers':
                this.startNumberGame();
                break;
            case 'shapes':
                this.startShapeGame();
                break;
            case 'letters':
                this.startLetterGame();
                break;
            case 'puzzle':
                this.startPuzzleGame();
                break;
        }
    }

    closeGame() {
        const modal = document.getElementById('gameModal');
        if (modal) {
            modal.remove();
        }
        
        if (this.gameTimer) {
            clearInterval(this.gameTimer);
            this.gameTimer = null;
        }
        
        this.currentGame = null;
        this.gameScore = 0;
    }

    resetGame() {
        if (this.currentGame) {
            this.startGame(this.currentGame);
        }
    }

    updateScore() {
        const scoreElement = document.getElementById('gameScore');
        if (scoreElement) {
            scoreElement.textContent = this.gameScore;
        }
    }

    // Hafıza Oyunu
    startMemoryGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Kartlara tıklayarak onları çevir</li>
                <li>Aynı sembollere sahip iki kartı bul</li>
                <li>Tüm çiftleri bulmaya çalış</li>
                <li>En az hamle ile tamamlamaya çalış!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        const symbols = ['🐶', '🐱', '🐰', '🐼', '🐨', '🐯', '🦁', '🐸'];
        const cards = [...symbols, ...symbols].sort(() => Math.random() - 0.5);
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <div class="memory-game" id="memoryGame" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; max-width: 400px; margin: 0 auto;"></div>
        `;
        
        const memoryGame = document.getElementById('memoryGame');
        memoryGame.innerHTML = '';
        
        cards.forEach((symbol, index) => {
            const card = document.createElement('div');
            card.className = 'memory-card';
            card.style.cssText = `
                width: 80px;
                height: 80px;
                background: #667eea;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 2rem;
                color: white;
                transition: all 0.3s ease;
            `;
            card.dataset.index = index;
            card.dataset.symbol = symbol;
            card.addEventListener('click', () => this.flipMemoryCard(index));
            memoryGame.appendChild(card);
        });
        
        this.memoryCards = cards;
        this.flippedCards = [];
        this.matchedPairs = 0;
        this.gameScore = 0;
        this.updateScore();
    }

    flipMemoryCard(index) {
        const card = document.querySelector(`[data-index="${index}"]`);
        if (this.flippedCards.length === 2 || card.classList.contains('flipped') || card.classList.contains('matched')) {
            return;
        }
        
        card.classList.add('flipped');
        card.textContent = card.dataset.symbol;
        card.style.background = '#4ecdc4';
        this.flippedCards.push({index, symbol: card.dataset.symbol});
        
        if (this.flippedCards.length === 2) {
            setTimeout(() => this.checkMemoryMatch(), 1000);
        }
    }

    checkMemoryMatch() {
        const [card1, card2] = this.flippedCards;
        const card1Element = document.querySelector(`[data-index="${card1.index}"]`);
        const card2Element = document.querySelector(`[data-index="${card2.index}"]`);
        
        if (card1.symbol === card2.symbol) {
            card1Element.classList.add('matched');
            card2Element.classList.add('matched');
            card1Element.style.background = '#96ceb4';
            card2Element.style.background = '#96ceb4';
            card1Element.style.cursor = 'default';
            card2Element.style.cursor = 'default';
            this.matchedPairs++;
            this.gameScore += 10;
            
            if (this.matchedPairs === this.memoryCards.length / 2) {
                setTimeout(() => {
                    alert(`Tebrikler! Oyunu ${this.gameScore} puanla tamamladınız!`);
                }, 500);
            }
        } else {
            card1Element.classList.remove('flipped');
            card2Element.classList.remove('flipped');
            card1Element.textContent = '';
            card2Element.textContent = '';
            card1Element.style.background = '#667eea';
            card2Element.style.background = '#667eea';
        }
        
        this.flippedCards = [];
        this.updateScore();
    }

    // Renk Eşleştirme Oyunu
    startColorGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Ekranda gösterilen rengi bul</li>
                <li>Doğru rengi seçerek puan kazan</li>
                <li>Renk isimlerini öğren</li>
                <li>Hızlı ve doğru olmaya çalış!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        this.colors = [
            {name: 'Kırmızı', value: '#ff0000'},
            {name: 'Mavi', value: '#0000ff'},
            {name: 'Yeşil', value: '#00ff00'},
            {name: 'Sarı', value: '#ffff00'},
            {name: 'Turuncu', value: '#ffa500'},
            {name: 'Mor', value: '#800080'},
            {name: 'Pembe', value: '#ffc0cb'},
            {name: 'Kahverengi', value: '#a52a2a'}
        ];
        
        this.gameScore = 0;
        this.updateScore();
        this.generateColorQuestion();
    }

    generateColorQuestion() {
        const randomColor = this.colors[Math.floor(Math.random() * this.colors.length)];
        this.currentColor = randomColor;
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <div id="colorTarget" style="width: 100px; height: 100px; margin: 20px auto; border-radius: 50%; border: 3px solid #333; background-color: ${randomColor.value};"></div>
            <h3 id="colorName" style="text-align: center; margin: 20px 0;">${randomColor.name}</h3>
            <div class="color-game" id="colorOptions" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 20px 0;"></div>
        `;
        
        const optionsContainer = document.getElementById('colorOptions');
        optionsContainer.innerHTML = '';
        
        // Rastgele 4 renk seç (doğru renk dahil)
        const shuffledColors = [...this.colors].sort(() => Math.random() - 0.5).slice(0, 4);
        if (!shuffledColors.includes(randomColor)) {
            shuffledColors[3] = randomColor;
        }
        
        shuffledColors.sort(() => Math.random() - 0.5).forEach(color => {
            const option = document.createElement('div');
            option.className = 'color-option';
            option.style.cssText = `
                width: 60px;
                height: 60px;
                border-radius: 50%;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.3s ease;
                background-color: ${color.value};
            `;
            option.addEventListener('click', () => this.selectColor(color));
            optionsContainer.appendChild(option);
        });
    }

    selectColor(selectedColor) {
        if (selectedColor.name === this.currentColor.name) {
            this.gameScore += 10;
            alert('Doğru! +10 puan');
        } else {
            alert(`Yanlış! Doğru renk: ${this.currentColor.name}`);
        }
        
        this.updateScore();
        setTimeout(() => this.generateColorQuestion(), 1000);
    }

    // Sayı Sayma Oyunu
    startNumberGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Ekranda gösterilen sayıyı bul</li>
                <li>Doğru sayıyı seçerek puan kazan</li>
                <li>Sayıları öğren ve tanı</li>
                <li>Hızlı ve doğru olmaya çalış!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        this.gameScore = 0;
        this.updateScore();
        this.generateNumberQuestion();
    }

    generateNumberQuestion() {
        this.currentNumber = Math.floor(Math.random() * 9) + 1;
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <h2 id="targetNumber" style="font-size: 4rem; margin: 20px 0; color: #667eea;">${this.currentNumber}</h2>
            <div class="number-game" id="numberOptions" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; max-width: 300px; margin: 0 auto;"></div>
        `;
        
        const optionsContainer = document.getElementById('numberOptions');
        optionsContainer.innerHTML = '';
        
        // Rastgele 9 sayı oluştur (doğru sayı dahil)
        const numbers = [];
        for (let i = 1; i <= 9; i++) {
            if (i !== this.currentNumber) {
                numbers.push(i);
            }
        }
        numbers.sort(() => Math.random() - 0.5);
        numbers.splice(8, 0, this.currentNumber);
        
        numbers.forEach(num => {
            const button = document.createElement('button');
            button.className = 'number-button';
            button.style.cssText = `
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                border-radius: 15px;
                font-size: 2rem;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s ease;
            `;
            button.textContent = num;
            button.addEventListener('click', () => this.selectNumber(num));
            optionsContainer.appendChild(button);
        });
    }

    selectNumber(selectedNumber) {
        const buttons = document.querySelectorAll('.number-button');
        buttons.forEach(btn => {
            if (parseInt(btn.textContent) === selectedNumber) {
                if (selectedNumber === this.currentNumber) {
                    btn.style.background = 'linear-gradient(135deg, #96ceb4, #4ecdc4)';
                    this.gameScore += 10;
                    setTimeout(() => {
                        alert('Doğru! +10 puan');
                    }, 300);
                } else {
                    btn.style.background = 'linear-gradient(135deg, #ff6b6b, #ee5a24)';
                    setTimeout(() => {
                        alert(`Yanlış! Doğru sayı: ${this.currentNumber}`);
                    }, 300);
                }
            }
        });
        
        this.updateScore();
        setTimeout(() => {
            buttons.forEach(btn => {
                btn.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
            });
            this.generateNumberQuestion();
        }, 1000);
    }

    // Şekil Eşleştirme Oyunu
    startShapeGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Ekranda gösterilen şekli bul</li>
                <li>Doğru şekli seçerek puan kazan</li>
                <li>Geometrik şekilleri öğren</li>
                <li>Görsel algını geliştir!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        this.shapes = [
            {name: 'Yıldız', symbol: '⭐'},
            {name: 'Kalp', symbol: '❤️'},
            {name: 'Daire', symbol: '⭕'},
            {name: 'Kare', symbol: '⬜'},
            {name: 'Üçgen', symbol: '🔺'},
            {name: 'Ay', symbol: '🌙'},
            {name: 'Güneş', symbol: '☀️'},
            {name: 'Bulut', symbol: '☁️'}
        ];
        
        this.gameScore = 0;
        this.updateScore();
        this.generateShapeQuestion();
    }

    generateShapeQuestion() {
        const randomShape = this.shapes[Math.floor(Math.random() * this.shapes.length)];
        this.currentShape = randomShape;
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <div id="targetShape" style="font-size: 4rem; margin: 20px 0; text-align: center;">${randomShape.symbol}</div>
            <h3 id="shapeName" style="text-align: center; margin: 20px 0;">${randomShape.name}</h3>
            <div class="color-game" id="shapeOptions" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 20px 0;"></div>
        `;
        
        const optionsContainer = document.getElementById('shapeOptions');
        optionsContainer.innerHTML = '';
        
        // Rastgele 4 şekil seç (doğru şekil dahil)
        const shuffledShapes = [...this.shapes].sort(() => Math.random() - 0.5).slice(0, 4);
        if (!shuffledShapes.includes(randomShape)) {
            shuffledShapes[3] = randomShape;
        }
        
        shuffledShapes.sort(() => Math.random() - 0.5).forEach(shape => {
            const option = document.createElement('div');
            option.className = 'color-option';
            option.style.cssText = `
                width: 60px;
                height: 60px;
                border-radius: 50%;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.3s ease;
                font-size: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
            `;
            option.textContent = shape.symbol;
            option.addEventListener('click', () => this.selectShape(shape));
            optionsContainer.appendChild(option);
        });
    }

    selectShape(selectedShape) {
        if (selectedShape.name === this.currentShape.name) {
            this.gameScore += 10;
            alert('Doğru! +10 puan');
        } else {
            alert(`Yanlış! Doğru şekil: ${this.currentShape.name}`);
        }
        
        this.updateScore();
        setTimeout(() => this.generateShapeQuestion(), 1000);
    }

    // Harf Öğrenme Oyunu
    startLetterGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Ekranda gösterilen harfi bul</li>
                <li>Doğru harfi seçerek puan kazan</li>
                <li>Alfabenin harflerini öğren</li>
                <li>Okuma yazma becerilerini geliştir!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        this.letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
        this.gameScore = 0;
        this.updateScore();
        this.generateLetterQuestion();
    }

    generateLetterQuestion() {
        this.currentLetter = this.letters[Math.floor(Math.random() * this.letters.length)];
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <h2 id="targetLetter" style="font-size: 4rem; margin: 20px 0; color: #667eea; font-family: Arial, sans-serif;">${this.currentLetter}</h2>
            <div class="color-game" id="letterOptions" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 20px 0;"></div>
        `;
        
        const optionsContainer = document.getElementById('letterOptions');
        optionsContainer.innerHTML = '';
        
        // Rastgele 4 harf seç (doğru harf dahil)
        const shuffledLetters = [...this.letters].sort(() => Math.random() - 0.5).slice(0, 4);
        if (!shuffledLetters.includes(this.currentLetter)) {
            shuffledLetters[3] = this.currentLetter;
        }
        
        shuffledLetters.sort(() => Math.random() - 0.5).forEach(letter => {
            const option = document.createElement('div');
            option.className = 'color-option';
            option.style.cssText = `
                width: 60px;
                height: 60px;
                border-radius: 50%;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.3s ease;
                font-size: 2rem;
                font-family: Arial, sans-serif;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
            `;
            option.textContent = letter;
            option.addEventListener('click', () => this.selectLetter(letter));
            optionsContainer.appendChild(option);
        });
    }

    selectLetter(selectedLetter) {
        if (selectedLetter === this.currentLetter) {
            this.gameScore += 10;
            alert('Doğru! +10 puan');
        } else {
            alert(`Yanlış! Doğru harf: ${this.currentLetter}`);
        }
        
        this.updateScore();
        setTimeout(() => this.generateLetterQuestion(), 1000);
    }

    // Puzzle Oyunu
    startPuzzleGame() {
        const instructions = `
            <h3>Nasıl Oynanır?</h3>
            <ul>
                <li>Parçaları doğru sırayla yerleştir</li>
                <li>Resmi tamamlamaya çalış</li>
                <li>Problem çözme becerilerini geliştir</li>
                <li>Mantık ve düşünme yeteneklerini güçlendir!</li>
            </ul>
        `;
        
        document.getElementById('gameInstructions').innerHTML = instructions;
        
        this.gameScore = 0;
        this.updateScore();
        
        const gameArea = document.getElementById('gameArea');
        gameArea.innerHTML = `
            <div id="puzzleContainer" style="width: 300px; height: 300px; margin: 0 auto; border: 2px solid #333; position: relative; display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(3, 1fr);"></div>
        `;
        
        this.createPuzzle();
    }

    createPuzzle() {
        const container = document.getElementById('puzzleContainer');
        container.innerHTML = '';
        
        // Puzzle parçalarını oluştur (1-8 + boş)
        const pieces = ['1', '2', '3', '4', '5', '6', '7', '8', ''];
        pieces.sort(() => Math.random() - 0.5);
        
        for (let i = 0; i < 9; i++) {
            const piece = document.createElement('div');
            piece.style.cssText = `
                border: 1px solid #333;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                font-weight: bold;
                cursor: pointer;
                background-color: ${pieces[i] === '' ? '#f0f0f0' : '#667eea'};
                color: white;
                transition: all 0.3s ease;
            `;
            piece.textContent = pieces[i];
            piece.dataset.index = i;
            piece.dataset.value = pieces[i];
            
            if (pieces[i] !== '') {
                piece.addEventListener('click', () => this.movePuzzlePiece(i));
            }
            
            container.appendChild(piece);
        }
    }

    movePuzzlePiece(index) {
        const pieces = document.querySelectorAll('#puzzleContainer > div');
        const emptyIndex = Array.from(pieces).findIndex(p => p.dataset.value === '');
        
        // Sadece boş alana bitişik parçalar hareket edebilir
        const canMove = (
            (index === emptyIndex - 1 && index % 3 !== 2) || // Sağ
            (index === emptyIndex + 1 && index % 3 !== 0) || // Sol
            (index === emptyIndex - 3) || // Alt
            (index === emptyIndex + 3) // Üst
        );
        
        if (canMove) {
            // Parçaları değiştir
            const tempValue = pieces[index].dataset.value;
            const tempText = pieces[index].textContent;
            const tempBg = pieces[index].style.backgroundColor;
            
            pieces[index].dataset.value = '';
            pieces[index].textContent = '';
            pieces[index].style.backgroundColor = '#f0f0f0';
            pieces[index].removeEventListener('click', () => this.movePuzzlePiece(index));
            
            pieces[emptyIndex].dataset.value = tempValue;
            pieces[emptyIndex].textContent = tempText;
            pieces[emptyIndex].style.backgroundColor = tempBg;
            pieces[emptyIndex].addEventListener('click', () => this.movePuzzlePiece(emptyIndex));
            
            this.gameScore += 1;
            this.updateScore();
            
            // Puzzle tamamlandı mı kontrol et
            this.checkPuzzleComplete();
        }
    }

    checkPuzzleComplete() {
        const pieces = document.querySelectorAll('#puzzleContainer > div');
        const values = Array.from(pieces).map(p => p.dataset.value);
        const correctOrder = ['1', '2', '3', '4', '5', '6', '7', '8', ''];
        
        if (JSON.stringify(values) === JSON.stringify(correctOrder)) {
            setTimeout(() => {
                alert(`Tebrikler! Puzzle'ı ${this.gameScore} hamlede tamamladınız!`);
            }, 500);
        }
    }
}

// Oyunları başlat
document.addEventListener('DOMContentLoaded', function() {
    window.games = new EducationalGames();
}); 