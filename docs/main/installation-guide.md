# Installation Guide: Setting Up Internara

Welcome! This guide will walk you through the process of setting up a local development environment
for the Internara project. By following these steps, you'll have a fully functional Modular Monolith
running on your machine.

---

## 🛠 1. System Requirements

Ensure your machine meets the following prerequisites:

- **PHP 8.4+** (Required for latest Laravel features).
- **Composer** (PHP dependency manager).
- **Node.js & NPM** (For building the TALL stack assets).
- **SQLite, MySQL, or PostgreSQL** (SQLite is recommended for local development).

---

## 🚀 2. Step-by-Step Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/reasnov/internara.git
cd internara
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install Node.js Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

Create your local environment file and generate the application key.

```bash
cp .env.example .env
php artisan key:generate
```

_Note: If using SQLite, ensure you create the database file: `touch database/database.sqlite`._

### Step 5: Run Migrations & Seeders

This will set up the core schema and create the default roles/permissions.

```bash
php artisan migrate --seed
```

### Step 6: Build Assets

Compile the Tailwind CSS and Alpine.js assets.

```bash
npm run build
```

### Step 7: Start Development Servers

In two separate terminals, run the PHP server and the Vite dev server for hot-reloading.

```bash
php artisan serve
# and
npm run dev
```

---

## ✅ 3. Post-Installation Verification

1.  Open `http://localhost:8000` in your browser.
2.  Run `php artisan app:info` to verify that the application version and identity are correct.
3.  Check the `storage/logs/laravel.log` for any initialization errors.

---

_You are now ready to build! Refer to the **[Architecture Guide](architecture-guide.md)** to
understand how the system is structured before you start coding._
