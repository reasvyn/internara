# System Installation

Internara provides two installation paths: CLI for technical initialization and a web-based wizard for configuration.

---

## CLI Installation

```bash
php artisan setup:install
```

### Options
- `--force`: Bypass confirmation (WARNING: wipes database)

### What It Does
1. Runs `migrate:fresh` — wipes and rebuilds all tables
2. Runs database seeders
3. Sets system metadata in `settings` table (`app_version`, `installed_at`)
4. Logs `system_installed` audit event
5. Creates storage symlink (`storage:link`)
6. Clears application cache
7. Generates a setup token for web wizard continuation

### Output
Generates a signed URL with token to continue the web-based setup wizard.

---

## Web Setup Wizard

**Route**: `/setup?setup_token=...`  
**Component**: `App\Livewire\Setup\SetupWizard`

### 6-Step Flow
| Step | Purpose |
|------|---------|
| Welcome | Pre-flight system audit (PHP version, extensions, writability, DB connectivity) |
| School | School profile data (name, code, address, email) |
| Account | Super admin user creation |
| Department | First academic department |
| Internship | First internship program |
| Finalize | Verification checklist, then lock the installation |

### Lock File Gate

After finalization, `SetupService::finalize()` creates a lock file at `storage/app/.installed` containing:
```json
{
    "installed_at": "<ISO 8601 timestamp>",
    "version": "<app version>"
}
```

**Purpose**: Prevents access to setup routes after installation is complete.

**How it works**:
1. `ProtectSetupRoute` middleware checks `SetupService::isInstalled()` — redirects to login if installed
2. `SetupWizard::mount()` double-checks the lock file independently
3. `SetupInstallCommand` checks the lock file before allowing `--force` reinstall

**To reset**: Run `php artisan setup:reset` or delete `storage/app/.installed` manually.

---

## Post-Installation State

- Database schema is fully initialized
- Super admin user is created with full RBAC access
- School, department, and internship records exist
- `settings` table has `setup_completed = true`
- Lock file prevents unauthorized re-access to setup routes
- Laravel Pulse is active at `/pulse`

---

## Security (S1)

- Installation requires manual confirmation (non-testing environments)
- Database operations are wrapped in a transaction (atomicity)
- Setup routes are token-protected with rate limiting (20 attempts/minute/IP)
- Setup tokens expire after 24 hours
- Lock file provides defense-in-depth (checked by both middleware and component)
- Every installation event is logged with environment context (IP, User Agent)

---

*Last Updated: April 30, 2026*
