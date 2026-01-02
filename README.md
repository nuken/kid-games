# 🎮 Kids Game Hub (Nuken Kid Games)

A self-hosted, safe, and ad-free educational gaming platform for children. This project allows parents to manage child accounts, track progress, and provide a curated list of educational games (Math, Reading, Logic, Creativity).

## ✨ Features

### 👶 For Kids (Students)
* **Game Library:** Access to educational games organized by subject.
* **Progress Tracking:** Earn scores and see personal bests.
* **Badge System:** Unlock achievements (e.g., "Math Whiz", "First Game") for playing and mastering games.
* **Themes:** Fun, customizable interfaces (Space Commander, Fairy Tale, etc.).
* **Safe Environment:** No ads, external links, or tracking.

### 👤 For Parents
* **Dashboard:** Manage multiple child accounts from a single parent login.
* **Report Cards:** View detailed stats, playtime, and learning progress for each child.
* **Customization:** Set grade levels and themes for each child.
* **Security:** Simple PIN-based login for kids; password/PIN protection for parents.

### 🛡️ Admin Panel
* **User Management:** Create, edit, and delete users (Students, Parents, Admins).
* **Game Management:** Add new games, upload icons, and set grade level requirements.
* **Badge Manager:** Create and edit custom achievement badges.
* **System Settings:** Manage global configurations.

---

## 🛠️ Technology Stack

* **Backend:** PHP (7.4+)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, Vanilla JavaScript
* **Server:** Apache or Caddy (Recommended for self-hosting)

---

## 🚀 Installation

### Option 1: Docker (Recommended)
*Perfect for home servers (Intel N100, Raspberry Pi, etc.)*

1.  **Clone the repository** to your server.
2.  **Configure Environment:**
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * Update `DB_HOST` to `db` (if using the compose service name).
3.  **Start Containers:**
    ```bash
    docker-compose up -d
    ```
4.  **Run Installer:**
    * Navigate to `http://your-server-ip/install.php`.
    * Create your Super Admin account.
    * **Delete `install.php`** immediately after success.

### Option 2: Manual / Shared Hosting
1.  **Upload Files:** Upload the entire project folder to your web server (e.g., `public_html`).
2.  **Database Setup:**
    * Create a MySQL database.
    * Rename `includes/config.sample.php` to `includes/config.php` and enter your database credentials.
3.  **Run Installer:**
    * Navigate to `yoursite.com/install.php` in your browser.
    * Fill in the Admin Username and PINs.
    * Click **Install**.
4.  **Cleanup:** Delete `install.php` for security.

---

## 📂 Project Structure

```text
/
├── admin/            # Admin control panel (Users, Games, Badges)
├── api/              # AJAX endpoints for saving scores/progress
├── assets/           # CSS, Images, Sounds, Shared JS
├── games/            # Individual game folders (each contains its own assets)
├── includes/         # Database connection, config, auth checks
├── install.php       # Database setup script (Delete after use)
└── index.php         # Main dashboard
