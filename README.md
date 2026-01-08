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
* **Theming:** Customize the interface with built-in themes: **Space Commander**, **Fairy Tale**, and **Default**.
* **Progressive Web App (PWA):** Installable on tablets and phones for a native app-like experience.

### 🕹️ For Kids
* **Progress Tracking:** Earn badges like "Word Wizard" and "Math Farmer", plus "streak" rewards (e.g., "On Fire" visual effects).
* **Voice Feedback:** Built-in Text-to-Speech engine guides the child through games.
* **Fun & Educational:** Games cover math, reading, pattern recognition, Spanish vocabulary, and reflexes.

---

## <a id="included-games"></a>🎲 Included Games

The platform comes pre-loaded with over 20 educational titles across various subjects:

| Category | Games |
| :--- | :--- |
| **Literacy & Language** | Alphabet Fun, Sight Word Adventures, Read & Match, Spell It!, Wild World (Animals), The Cat and Rat, Cosmic Signal (Reading), Fiesta Piñata (Spanish) |
| **Math & Shapes** | Egg-dition (Math), Robo-Sorter, Rocket Shop (Money), Launch Time (Clocks), Shape Detective, Number Tracing |
| **Logic & Memory** | Pattern Train, Spider Web, Robot Commander (Simon Says), Traffic Control (Red Light) |
| **Action & Reflexes** | Balloon Pop, Lava Bridge |
| **Creativity** | Coloring Book, Color Lab |

---

## <a id="installation"></a>🚀 Installation

You can install this project using Docker (recommended for home labs) or manually on a standard web host.

### Option A: Docker (Recommended)

Perfect for users with Portainer or Docker Compose.

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/nuken/kid-games.git
    cd kid-games
    ```

2.  **Configure Environment**
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit `includes/config.php`:
        * **Security:** Change the default database username and password to secure, custom values (do not use the defaults).
    * **Update Docker Compose:** Open `docker-compose.yml` and ensure the `MYSQL_USER` and `MYSQL_PASSWORD` variables match exactly what you set in `config.php`.

3.  **Start Services**
    ```bash
    docker-compose up -d
    ```
    The site will be available at `http://localhost:8080`.

4.  **Finish Setup**
    * Visit `http://localhost:8080/install.php`.
    * Follow the prompts to create your **Admin** account.

### Option B: Web Hosting (cPanel / LAMP)

1.  **Upload:** Upload all files to your server's `public_html` folder.
2.  **Database:** Create a MySQL/MariaDB database and user in your control panel.
3.  **Config:** * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit it with your database credentials (Host, User, Password, DB Name).
4.  **Install:** Visit `yoursite.com/install.php` to complete the setup.

> **⚠️ Security Note:** The `install.php` script attempts to delete itself after a successful installation. If it fails to do so due to permissions, please manually delete `install.php` from your server.

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

* **Backend:** PHP 7.4+ (No frameworks, lightweight) Tested on 8.4!
* **Database:** MariaDB / MySQL
* **Frontend:** Vanilla Javascript & HTML5
* **Containerization:** Docker & Docker Compose
