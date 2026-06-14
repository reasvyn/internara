# Chapter 5: Upgrading Internara

> **Last updated:** 2026-06-14

This chapter explains how to upgrade your Internara installation from one version to the next.
Whether you're applying a patch, a feature release, or a major version, the process follows the
same steps.

---

## 5.1 Before You Start

### Check Your Current Version

```bash
# Show installed version
php artisan about | grep "Internara\|version"

# Or check composer.json
grep '"version"' composer.json
```

### Read the Release Notes

Each release includes a changelog. Always read it before upgrading — it lists:

- **New features** — what was added
- **Bug fixes** — what was resolved
- **Breaking changes** — things you need to adjust manually
- **Deprecations** — features that will be removed in a future version

### Backup Everything

**Always back up before upgrading.** Use one of these methods:

#### Option A — Built-in Backup Command

If your system has backups configured (see [Chapter 4: System Health](04-system-health-and-troubleshooting.md)):

```bash
# Run a manual full backup
php artisan system:backup --type=both --force
```

This creates a backup through the system's backup manager, stored in `storage/app/backup/`
and recorded in the database.

#### Option B — Manual Backup

```bash
# 1. Database dump
# For MySQL:
mysqldump -u root -p internara > backup-$(date +%Y%m%d).sql

# For PostgreSQL:
pg_dump internara > backup-$(date +%Y%m%d).sql

# For SQLite (just copy the file):
cp database/database.sqlite backup-$(date +%Y%m%d).sqlite

# 2. Uploaded files
tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/app/

# 3. Environment config
cp .env .env.backup-$(date +%Y%m%d)
```

---

## 5.2 Standard Upgrade Procedure

### Step 1 — Put the Site in Maintenance Mode

```bash
php artisan down --render="errors::maintenance"
```

This shows a maintenance page to visitors while you work.

### Step 2 — Get the Latest Code

```bash
# If installed via git:
git pull origin main

# If installed via ZIP download:
# Download the latest release and extract it over your existing installation
```

### Step 3 — Update Dependencies

```bash
composer install --no-interaction --optimize-autoloader --no-dev

npm install && npm run build
```

### Step 4 — Run Database Migrations

```bash
php artisan migrate --force
```

This applies any new database changes. Existing data is preserved.

### Step 5 — Clear & Refresh Caches

```bash
php artisan optimize:clear
php artisan optimize
```

### Step 6 — Restart the Queue Worker

If you run a queue worker (for emails, PDF generation, etc.):

```bash
php artisan queue:restart
```

### Step 7 — Verify

```bash
php artisan system:health

# Take the site live again
php artisan up
```

---

## 5.3 What Can Go Wrong

| Problem | Likely Cause | Solution |
|---|---|---|
| Migration fails | Database user lacks permissions | Grant `ALTER` and `CREATE` privileges |
| White screen after upgrade | Cached config conflicts with new code | `php artisan optimize:clear` |
| "Class not found" | Dependencies not updated | `composer install --no-interaction` |
| Assets look broken | Frontend not rebuilt | `npm install && npm run build` |
| Features not working | Migration not run | `php artisan migrate --force` |
| Queue jobs failing | Worker running old code | `php artisan queue:restart` |

### Rolling Back

If something goes wrong and you need to revert:

```bash
# 1. Restore the previous code (via git or re-extract old files)
git checkout <previous-tag>

# 2. Restore the database
# MySQL:
mysql -u root -p internara < backup-$(date +%Y%m%d).sql

# 3. Restore files
tar -xzf storage-backup-$(date +%Y%m%d).tar.gz

# 4. Re-run migration rollback if needed
php artisan migrate:rollback --force

# 5. Rebuild assets and caches
npm install && npm run build
php artisan optimize:clear
php artisan optimize

# 6. Bring the site back up
php artisan up
```

---

## 5.4 Version Numbering

Internara follows [Semantic Versioning](https://semver.org/):

| Change | Example | When |
|---|---|---|
| **Major** | 1.0.0 → 2.0.0 | Breaking changes that require manual steps |
| **Minor** | 1.0.0 → 1.1.0 | New features, no breaking changes |
| **Patch** | 1.0.0 → 1.0.1 | Bug fixes and small improvements |

Current version: **0.1.0** (development preview — not yet stable).

---

## 5.5 Major Version Upgrades

Major version upgrades (e.g., 0.1.0 → 1.0.0) may include:

- **Database schema changes** that are not automatically migrated
- **Configuration file changes** — compare your `.env` with the new `.env.example`
- **Removed features** — check the changelog for features that were dropped
- **New requirements** — updated PHP version, new extensions, different database versions

The release notes for a major version will always include a dedicated upgrade guide with
step-by-step instructions.

---

## 5.6 Staying Up to Date

### Subscribe to Releases

Watch the repository on GitHub to receive notifications about new releases.

### Check for Updates

```bash
composer outdated
```

### Test Upgrades in Staging

Before upgrading your live site:

1. Clone your production data to a test environment
2. Run the upgrade procedure there
3. Verify everything works
4. Then repeat on production

---

---

**← Previous:** [Chapter 4: System Health & Troubleshooting](04-system-health-and-troubleshooting.md)
**Next →** [Back to Manual Index](00-guide-index.md)
