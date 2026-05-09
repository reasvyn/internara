# Installation Guide

## Requirements

- PHP 8.4 or higher
- Node.js 20+
- Database: SQLite (default), MySQL 8+, or PostgreSQL 14+
- Composer and Node.js

## Installation

Run the installation command from your project directory:

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

This command checks your server environment, sets up the database, and generates a one-time URL. Open that URL in your browser to complete the setup wizard.

If the system detects an existing installation, add `--force` to reinstall.

## Creating an Administrator Account

After completing the setup wizard, create your first administrator account:

```bash
php artisan setup:super-admin
```

Follow the prompts to enter an email, name, and password.

## Emergency Access Recovery

If you lose access to all administrator accounts, use the recovery command:

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

## Available Setup Commands

| Command | Purpose |
|---|---|
| `setup:install` | Check environment, setup DB, generate setup URL |
| `setup:super-admin` | Create a super administrator account |
| `setup:recover-admin` | Recover access to admin accounts |
| `setup:reset` | Reset setup state, allow re-running wizard |
| `setup:health` | Check system health and readiness |

All commands support `--no-interaction` for automated deployments.