# 🎮 Kids Game Hub (Nuken Kid Games)

![License](https://img.shields.io/badge/license-MIT-blue.svg) ![PHP](https://img.shields.io/badge/php-7.4%2B-purple) ![Docker](https://img.shields.io/badge/docker-ready-blue)

A self-hosted, safe, and ad-free educational gaming platform for children. This project allows parents to manage child accounts, track progress, and provide a curated list of educational games.

Designed to run on home servers (Intel N100, Raspberry Pi, Synology) or standard web hosting.

## 📋 Table of Contents
- [Features](#features)
- [Included Games](#included-games)
- [Installation](#installation)
- [Game Development](#game-development)
- [Technology Stack](#technology-stack)

---

## <a id="features"></a>✨ Features

### 🛡️ For Parents
* **Self-Hosted & Safe:** Zero ads, no external tracking, and no outbound links.
* **Parent Dashboard:** Create and manage child accounts easily.
* **Report Cards:** Track progress, mistakes, and time spent on each game.
* **Theming:** Customize the interface with built-in themes like **Space**, **Princess**, and **Default**.

### 🕹️ For Kids
* **Progress Tracking:** Earn badges and "streak" rewards (e.g., "On Fire" visual effects).
* **Voice Feedback:** Built-in Text-to-Speech engine guides the child through games.
* **Fun & Educational:** Games cover math, reading, pattern recognition, and reflexes.

---

## <a id="included-games"></a>🎲 Included Games

The platform comes pre-loaded with a variety of educational titles:

| Category | Games |
| :--- | :--- |
| **Literacy** | Alphabet, Sight Word Reader, Read Match, Spell It, Cat-Rat Reader |
| **Math & Logic** | Egg-dition (Math), Robo Sorter, Pattern Train, Shape Detective |
| **Creativity** | Coloring Book, Color Mix, Rocket Shop |
| **Reflexes** | Balloon Pop, Red Light, Cosmic Signal, Lava Bridge |
| **Memory** | Simon Says, Spider Web, Fiesta Pinata |

---

## <a id="installation"></a>🚀 Installation

### Option 1: Docker (Recommended)

Perfect for home labs using Portainer or Docker Compose.

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/nuken/kid-games.git](https://github.com/nuken/kid-games.git)
    cd kid-games
    ```

2.  **Configure Environment**
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit `includes/config.php` and change the `DB_HOST` to `db`.

3.  **Start Services**
    The included `docker-compose.yml` sets up Apache/PHP on port **8080** and MariaDB.
    ```bash
    docker-compose up -d
    ```

4.  **Run the Installer**
    * Go to `http://localhost:8080/install.php` (or your server IP).
    * Follow the prompts to create the **Super Admin** account.
    * **Security:** The database creates a default user `kid_user` with password `kid_password`. You can change these in `docker-compose.yml` if exposed to the web.
    * **Important:** Delete `install.php` after installation is complete.

### Option 2: Standard Hosting (cPanel / LAMP)

1.  **Upload:** Upload files to your `public_html` folder.
2.  **Database:** Create a MySQL/MariaDB database and user in your control panel.
3.  **Config:** * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit it with your database credentials.
4.  **Install:** Visit `yoursite.com/install.php` to complete setup.

---

## <a id="game-development"></a>🧩 Game Development

Want to add your own games? The platform exposes a powerful Javascript API called **GameBridge** that handles the complex logic for you.

* **[📖 Read the full Developer Guide](examples/DEVELOPER_GUIDE.md)**

### Key API Features
* **Scoring:** `GameBridge.saveScore({ score: 100, mistakes: 0 })` handles database syncing automatically.
* **Audio:** `GameBridge.speak("Find the red circle")` uses the browser's TTS engine.
* **Feedback:** `GameBridge.handleCorrect()` triggers global "ding" sounds and visual streaks.

### Quick Start
Every game resides in `games/your-game-name/` and requires just three files:
* `view.php` (The HTML interface)
* `game.js` (Logic using `GameBridge.setupGame({...})`)
* `style.css` (Visuals)

---

## <a id="technology-stack"></a>🛠️ Technology Stack

* **Backend:** PHP 7.4+ (No frameworks, lightweight)
* **Database:** MariaDB / MySQL
* **Frontend:** Vanilla Javascript & HTML5
* **Containerization:** Docker & Docker Compose
