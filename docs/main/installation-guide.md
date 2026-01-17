# Installation Guide

This guide provides step-by-step instructions to set up the Internara application for local
development.

---

## 1. Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP:** Version 8.2 or higher
- **Composer:** Latest stable version
- **Node.js & NPM:** Latest LTS version
- **Git:** Latest stable version
- **Database:** MySQL 8+, PostgreSQL, or SQLite
- **Web Server:** Nginx or Apache (optional, for local development)

### Key Third-Party Packages

This project relies on several key packages that are automatically installed via Composer:

- [nwidart/laravel-modules](https://nwidart.com/laravel-modules/v11/introduction)
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [robsontenorio/mary](https://mary-ui.com/)

## 2. Setup Instructions

Follow these steps to get Internara up and running on your local machine:

### Step 1: Clone the Repository

Clone the project repository from your version control system:

```bash
git clone <repository-url>
cd internara
```

### Step 2: Install PHP Dependencies

Install the Composer dependencies:

```bash
composer install
```

### Step 3: Install JavaScript Dependencies

Install the NPM dependencies and build the assets:

```bash
npm install
npm run build
```

### Step 4: Environment Configuration

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

Then, open the `.env` file and configure your database connection and other environment variables.

```dotenv
# .env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara_db
DB_USERNAME=root
DB_PASSWORD=

# You may also want to configure your APP_URL
APP_URL=http://localhost:8000
```

### Step 5: Database Setup

Run the database migrations and seed the database with initial data. This will create all necessary
tables and populate them with default roles, permissions, and possibly some dummy data.

```bash
php artisan migrate --seed
```

### Step 6: Link Storage

Create a symbolic link for the storage directory:

```bash
php artisan storage:link
```

### Step 7: Start the Development Server

Serve the application using Laravel Artisan and start the Vite development server for hot module
reloading:

```bash
php artisan serve &
npm run dev
```

The application should now be accessible at `http://localhost:8000` (or the `APP_URL` you
configured).

---

## 3. Initial Application Setup

After installation, navigate to the application in your browser. If a setup wizard is available,
follow its instructions. Otherwise, you may need to manually create an initial admin user or run a
specific seeder if not already covered by `migrate --seed`.

_(Further instructions for initial admin user creation or setup wizard will go here if applicable)_

---

**Navigation**

[← Previous: Table of Contents](table-of-contents.md) |
[Next: Architecture Guide →](architecture-guide.md)
