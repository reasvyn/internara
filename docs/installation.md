# System Installation Documentation

## Overview
Internara provides a streamlined, automated installation process via the Artisan CLI. This process handles the technical initialization of the database, system settings, and core infrastructure.

## CLI Usage
To install or reset the system, run the following command from the project root:

```bash
php artisan app:install
```

### Options:
- `--force`: Bypasses the confirmation prompt and forces the installation (WARNING: This will wipe your database).

## Installation Workflow
The installation is orchestrated by the stateless `App\Actions\Setup\InstallSystemAction` and performs the following tasks in order:

1. **Database Migration**: Wipes the database and runs all migrations using UUID-based primary keys.
2. **Data Seeding**: Populates the database with foundational data and system-level records.
3. **System Configuration**: Persists critical settings to the `settings` table:
   - `app_installed`: Set to `true`.
   - `app_version`: Captured from `app_info.json`.
   - `installed_at`: Timestamp of completion.
4. **Audit Logging**: Records a `system_installed` event in the `audit_logs` table for forensic tracking.
5. **Storage Integration**: Automatically creates the public storage symlink (`storage:link`).
6. **System Optimization**: Clears and warms up the application cache.

## Post-Installation State
After a successful installation:
- The database schema is fully initialized.
- System resources (Cache, Queue, Notifications tables) are ready.
- **Laravel Pulse** is active and monitoring system health.
- The application identity is established based on `app_info.json`.

## Security Considerations (S1)
- The installation command requires manual confirmation in non-testing environments.
- All database operations are wrapped in a transaction to ensure atomicity.
- Every installation event is logged with the user's environment context (IP, Agent).
