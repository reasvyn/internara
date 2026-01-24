# Installation Guide: Getting Started with Internara

Welcome to Internara! This guide helps school administrators and IT staff set up the platform.

---

## 🛠 1. System Requirements

Ensure your environment meets these requirements (Specs Section 10):

- **PHP 8.4 or higher**: Primary engine.
- **Composer**: Dependency manager.
- **Node.js & NPM**: Required for building the TALL Stack interface.
- **Database**: SQLite, MySQL, or PostgreSQL.
- **Server**: Linux-based VPS recommended (Specs 10.4).

---

## 🚀 2. Step-by-Step Installation

### Step 1: Obtain Application Files

```bash
git clone https://github.com/reasnov/internara.git
cd internara
```

### Step 2: Install Components

```bash
composer install
npm install
```

### Step 3: Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

_Note: Configure your database and app settings here._

### Step 4: Prepare Database & Roles

```bash
php artisan migrate --seed
```

_This seeds the foundational **User Roles** (Instructor, Staff, etc.)._

### Step 5: Build Interface

```bash
npm run build
```

---

## ✅ 3. Final Verification

Start the application:

```bash
php artisan serve
```

Open `http://localhost:8000`. Success is achieved when you see the localized Internara welcome page.

---

_For technical support, refer to the **[Documentation Hub](main-documentation-overview.md)**._
