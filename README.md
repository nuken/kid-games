# 🎮 Kids Game Hub (Nuken Kid Games)

A self-hosted, safe, and ad-free educational gaming platform for children. This project allows parents to manage child accounts, track progress, and provide a curated list of educational games.

## ✨ Features

* **For Kids:** Educational games, progress tracking, badge achievements, and fun themes.
* **For Parents:** Dashboard, report cards, and difficulty settings.
* **Safe:** No ads, external links, or tracking.

---

## 🚀 Installation Options

### Option 1: Docker (Recommended)

This is the easiest method for home servers (Intel N100, Raspberry Pi, Synology).

1.  **Prepare Files:**
    * Clone this repository.
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * In `config.php`, change the `DB_HOST` to `db`.
2.  **Start Services:**
    Run the compose file to start the Web (Apache/PHP) and Database (MariaDB) containers.
    ```bash
    docker-compose up -d
    ```
3.  **Run Installer:**
    * Open your browser to `http://localhost:8080/install.php` (or your server IP).
    * Follow the prompts to create the Super Admin account.
    * **Important:** Delete `install.php` after installation is complete.

### Option 2: Manual / Shared Hosting

Use this method for cPanel, standard web hosting, or XAMPP/MAMP.

1.  **Upload Files:**
    * Upload the entire project folder to your web server (e.g., `public_html`).
2.  **Database Configuration:**
    * Create a MySQL/MariaDB database and user via your hosting control panel.
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit `includes/config.php` and enter your database credentials (Host, Name, User, Password).
3.  **Run Installer:**
    * Navigate to `yoursite.com/install.php`.
    * Enter your desired Admin PINs and click **Install**.
4.  **Cleanup:**
    * Delete `install.php` to secure your site.

---

## 🧩 Developing New Games

Want to add your own games to the hub? The platform provides a built-in Javascript API (`GameBridge`) that handles scoring, text-to-speech, and rewards.

* **[📖 Read the Developer Guide](examples/DEVELOPER_GUIDE.md)**: Learn how to use `setupGame`, `saveScore`, and `celebrate`.
* **[📂 View Example Structure](examples/)**: See how game folders are organized.

### Quick Game Structure

Every game lives in `games/game-name/` and requires:
* `view.php`: The HTML interface.
* `game.js`: Logic using `GameBridge`.
* `style.css`: Custom styles.

---

## 🛠️ Technology Stack

* **Backend:** PHP (7.4+)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, Vanilla JS (No frameworks required)
