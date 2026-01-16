
# 🐳 Docker Deployment Guide

This guide covers how to deploy Nuken LMS using **Docker** and **Docker Compose**. This is the recommended method for home labs (Synology, Unraid, Raspberry Pi) as it isolates the application and database dependencies.

---

## 🚀 Quick Start

### 1. Clone & Prepare
Download the project code and enter the directory.
```bash
git clone https://github.com/nuken/kid-games.git
cd kid-games

```

### 2. Initialize Docker Files

The Docker configuration files live in a subdirectory to keep the root clean. Move them to the root so Docker can find them.

```bash
# Move Dockerfile and docker-compose.yml to the root
mv Docker-Option/* .

```

### 3. Database Configuration

You must tell the PHP application how to talk to the Docker database container.

1. **Rename Config:**
Rename `includes/config.sample.php` to `includes/config.php`.
2. **Edit Config:**
Open `includes/config.php` and update the database settings to match the defaults in `docker-compose.yml`:
* **Host:** `db` (This is the service name defined in docker-compose)
* **Database:** `kidgames`
* **User:** `kid_user`
* **Password:** `kid_password`



### 4. Start the Stack

Launch the containers in the background.

```bash
docker-compose up -d

```

> *Note: The first launch may take a minute while the database initializes.*

### 5. Run the Installer

1. Open your browser to `http://localhost:8080/install.php` (or your server's IP).
2. Follow the prompts to create your **Super Admin** account.
3. **Important:** Once installed, the script usually deletes itself. If not, delete `install.php` manually for security.

---

## ⚙️ Configuration Deep Dive

### Changing the Port

By default, the LMS runs on port **8080**. If this port is in use, edit `docker-compose.yml`:

```yaml
services:
  web:
    ports:
      - "9090:80"  # Change 8080 to 9090 (or any free port)

```

### Security: Changing Passwords

For production environments, you should change the default database passwords.

1. **Edit `docker-compose.yml**`: Update `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD`.
2. **Edit `includes/config.php**`: Update the password to match.
3. **Re-create Containers**:
```bash
docker-compose up -d --force-recreate

```



---

## 🛠️ Maintenance & Operations

### Viewing Logs

If something isn't working, check the logs for errors (e.g., PHP errors or Database connection issues).

```bash
# View logs for all services
docker-compose logs -f

# View logs for just the web server
docker-compose logs -f web

```

### Accessing the Container Shell

Sometimes you need to run commands inside the container (e.g., checking permissions).

```bash
docker exec -it kidgames-app /bin/bash

```

### Stopping the Server

To stop the application but keep your data intact:

```bash
docker-compose stop

```

To stop and remove containers (data in volumes is preserved):

```bash
docker-compose down

```

---

## 📂 Data Persistence

* **Database:** Your game data, user accounts, and progress are stored in a Docker Volume named `db_data`. This persists even if you delete the container.
* **Code:** The `docker-compose.yml` maps your current folder (`./`) to `/var/www/html`. This means any changes you make to the PHP files on your host machine are immediately reflected in the container (great for development!).

---

## ❓ Troubleshooting

**"Connection Refused" to Database**

* Ensure `DB_HOST` in `includes/config.php` is set to `db`, not `localhost`.
* Wait 30 seconds. The database container takes longer to start than the web server on the first run.

**Permission Errors**

* If images or uploads aren't working, ensuring the web server owns the files:
```bash
docker exec kidgames-app chown -R www-data:www-data /var/www/html

```
