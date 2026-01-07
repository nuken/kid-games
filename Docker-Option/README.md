###  Docker Install

Perfect for home labs using Portainer or Docker Compose.

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/nuken/kid-games.git](https://github.com/nuken/kid-games.git)
    cd kid-games
    ```

2.  **Move Docker Files**
    The Docker configuration files are stored in a subdirectory. You must move them to the main project folder for Docker Compose to work correctly.
    ```bash
    mv Docker-Option/* .
    ```

3.  **Configure Environment**
    * Rename `includes/config.sample.php` to `includes/config.php`.
    * Edit `includes/config.php` and change the `DB_HOST` to `db`.

4.  **Start Services**
    The included `docker-compose.yml` sets up Apache/PHP on port **8080** and MariaDB.
    ```bash
    docker-compose up -d
    ```

5.  **Run the Installer**
    * Go to `http://localhost:8080/install.php` (or your server IP).
    * Follow the prompts to create the **Super Admin** account.
    * **Security:** The database creates a default user `kid_user` with password `kid_password`. You can change these in `docker-compose.yml` if exposed to the web.
    * **Important:** Delete `install.php` after installation is complete.
