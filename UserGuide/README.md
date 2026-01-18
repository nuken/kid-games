# 📘 Nuken LMS: User Guide

Welcome to the complete user guide for Nuken LMS. This document covers the initial setup, administrative configuration, parent onboarding, and day-to-day management of student accounts.

---

## 🛠️ Phase 1: Installation & Setup

Before users can start playing, the Administrator must initialize the system.

### 1. Running the Installer
Navigate to `http://your-server-address/install.php` in your web browser. You will be presented with the system installer.

### 2. Creating the Admin Account
You must define three critical pieces of information. **Security is important here**, as the Admin has full access to the system.

* **Admin Username**: The name you will use to identify yourself (e.g., "Admin", "Teacher").
* **Main Password**: This is the password used to log in to the dashboard.
* **Admin Secret PIN**: A numeric PIN (e.g., `9999`) used to verify sensitive actions or access protected areas within the Admin Dashboard.

> **⚠️ Security Note:** Choose a strong Main Password. Anyone with access to the Admin account can see all student data and modify system files.

### 3. Finalizing Install
Click **Install Now**.
* The system will create the necessary database tables.
* It will attempt to **auto-delete** the `install.php` file for security.
* **Verify:** If the system reports that it could not delete the file, you **must** manually delete `install.php` from your server immediately.

---

## ⚙️ Phase 2: Configuration (Admin)

Once logged in as an Admin, you should configure the "Invite Code" to allow parents to register.

1.  Log in with your Admin credentials.
2.  Navigate to **Settings** (⚙️ icon in the top navigation).
3.  **Update Invite Code**:
    * Enter a secure phrase or code (e.g., `FamilyFun2024`).
    * Click **Save Settings**.
    * Share this code *only* with the parents (or use it yourself if you are the parent) to create the main family account.

---

## 👨‍👩‍👧‍👦 Phase 3: Parent Registration

Parents do not need Admin intervention to create their accounts if they have the **Invite Code**.

1.  Go to the login screen.
2.  Click **"Parent or Admin Login"**.
3.  Click the link: **"✨ New Family? Register Here"**.
4.  **Fill in the Registration Form**:
    * **Invite Code**: Enter the code set by the Admin in Phase 2.
    * **Parent Name**: (e.g., "Mom", "Dad").
    * **Create Password**: Choose a **strong password**. This account controls student access and settings.
5.  Click **Create Account**.

---

## 🎮 Phase 4: Parent Dashboard Controls

The Parent Dashboard is the command center for managing children. Access it by logging in with your Parent account.

### ➕ Adding a Child
1.  Locate the **"Add a Child"** panel on the left side.
2.  **Name**: Enter the child's display name.
3.  **PIN Code**: Create a simple PIN (e.g., `1234`) for the child. They will type this to log in.
4.  **Grade Level**: Select the starting difficulty level.
5.  Click **Add Child**.

### ⚙️ Managing Student Settings
Select a child from the dropdown menu to edit their specific settings:

* **Grade Level**: Adjust the difficulty of games (e.g., moving from Kindergarten to 1st Grade).
* **Theme**: Change the visual style of the LMS (e.g., Space Commander, Fairy Tale).
* **Avatar**: Choose a fun emoji avatar for the student.
* **Messaging**: Toggle **"Allow Messaging"** to enable or disable the internal email system for that child.
* **Confetti**: Toggle victory animations.

### 📊 Monitoring Progress
* **Stats Overview**: View total missions completed, minutes played, and average score.
* **Game Performance**: See a breakdown of specific games to identify where the student excels or needs practice.
* **Report Card**: Click **"View Full Report Card"** for a detailed, printable summary.

### 📬 Messaging
You can send encouraging emojis to your child's inbox.
1.  Select the child.
2.  In the "Last 5 Messages" box, click an emoji (👋, ❤️, 🌟, 👍, 👊).
3.  The child will receive this in their "Messenger Box" on their dashboard.

---

## 🔐 Login & Security Features

### Student Login
1.  Students see a grid of Avatars on the main page.
2.  They click their **Avatar**.
3.  They enter their **PIN Code** on the number pad.

### "Remember Me"
On both the Student and Parent login screens, checking **"Stay Signed In"** will verify the device for 30 days.
* **Convenience**: Great for home tablets so kids don't have to type their PIN constantly.
* **Security Warning**: Do not use this feature on public or shared computers.

### Account Lockout
To prevent guessing, if a PIN or Password is entered incorrectly **5 times**, the account will be locked for **15 minutes**.

---

## 🛠️ Admin Capabilities

Admins have a "Super View" of the system via the top navigation bar:

* **👥 Users**: View/Edit ALL users (Parents and Students). You can manually reset passwords here if a parent forgets theirs.
* **🎮 Games**: Enable/Disable specific games or change their grade level requirements.
* **🏆 Badges**: Create or edit the achievement badges students earn.
* **⚙️ Settings**: View system health, server time, and update the global Invite Code.