# Installation

## Requirements

- PHP 8.4 or higher
- Node.js 20 or higher
- Composer and npm
- Database: SQLite (default), MySQL 8+, MariaDB, or PostgreSQL 14+

## Steps

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

The `setup:install` command checks your environment, sets up the database, and generates a one-time URL. Open that URL in your browser to complete the setup wizard.

If the system detects an existing installation, use `--force` to reinstall.

## Creating an Admin Account

After the setup wizard, create your first super administrator:

```bash
php artisan setup:super-admin
```

Follow the prompts to enter an email, name, and password.

## Recovery

If you lose access to all administrator accounts:

```bash
php artisan setup:recover-admin
```

This lets you reset an existing account or create a new one. Console access to the server is required.

## Resetting Setup

To reset the setup state and run the wizard again:

```bash
php artisan setup:reset
```

This clears the installation state without removing any data.

## Available Commands

Run `php artisan list` to see all available commands. Key setup-related commands include:

| Command | Purpose |
|---|---|
| `setup:install` | Check environment, setup database, generate setup URL |
| `setup:super-admin` | Create a super administrator account |
| `setup:recover-admin` | Recover access to admin accounts |
| `setup:reset` | Reset setup state, allow re-running wizard |
| `system:health` | Check system health and readiness (also accessible as `setup:health` for convenience) |
| `admin:promote` | Promote an existing user to admin role |
| `cleanup` | Run system cleanup tasks |
