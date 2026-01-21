# Installation Guide: Getting Started with Internara

Welcome to Internara! This guide is designed to help you—whether you are a school administrator, an institution IT staff member, or a curious user—set up the Internara platform on your local machine or server.

---

## 🛠 1. System Requirements

Before you begin, ensure your environment meets the following requirements:

- **PHP 8.4 or higher**: The primary engine that runs the application.
- **Composer**: A tool used to manage the system's internal components.
- **Node.js & NPM**: Required for building and displaying the user interface.
- **Database**: Internara supports SQLite (easiest for testing), MySQL, or PostgreSQL.

---

## 🚀 2. Step-by-Step Installation

### Step 1: Obtain the Application Files
Download the application code to your computer:
```bash
git clone https://github.com/reasnov/internara.git
cd internara
```

### Step 2: Install System Components
Run these commands to install all necessary background tools:
```bash
composer install
npm install
```

### Step 3: Setup Application Settings
Create your configuration file and generate a secure access key:
```bash
cp .env.example .env
php artisan key:generate
```
*Note: You can customize your application name and database connection inside the `.env` file.*

### Step 4: Prepare the Database
Run this command to create the data tables and set up initial roles (like the Admin account):
```bash
php artisan migrate --seed
```

### Step 5: Build the Interface
Compile the visual elements so the application looks and works correctly:
```bash
npm run build
```

---

## ✅ 3. Final Verification

Once finished, you can start the application with:
```bash
php artisan serve
```
Open your browser and go to `http://localhost:8000`. If you see the Internara welcome page, your installation was successful!

---

_Need further assistance? Contact your IT team or visit our GitHub page to report any issues._
