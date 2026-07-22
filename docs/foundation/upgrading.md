# Upgrading Internara

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite from end-user guide to developer reference; merge from `docs/guide/05-upgrading-from-previous.md`

## Description

Reference for upgrading an Internara installation between versions. Covers the standard upgrade
procedure, rollback strategy, version numbering, and major version considerations.

---

## 1. Problem Statements

### PS-1 — Safe Upgrades

Schools run production data on Internara. Upgrades must preserve data integrity, support rollback,
and provide clear feedback when something fails.

### PS-2 — Version Compatibility

Different upgrade paths (patch, minor, major) have different risk profiles. The upgrade procedure
must handle all three while warning about breaking changes.

---

## 2. Pre-Upgrade Checklist

| Step | Action | Command |
| ---- | ------ | ------- |
| 1 | Check current version | `php artisan about \| grep version` or `grep '"version"' composer.json` |
| 2 | Read release notes | Check CHANGELOG.md / GitHub releases for breaking changes |
| 3 | Backup database | See §3 below |
| 4 | Backup uploaded files | `tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/app/` |
| 5 | Backup environment | `cp .env .env.backup-$(date +%Y%m%d)` |

---

## 3. Backup Before Upgrade

### Option A — Built-in Backup

```bash
php artisan system:backup --type=both --force
```

Stored in `storage/app/backup/`, recorded in database.

### Option B — Manual Backup

```bash
# MySQL
mysqldump -u root -p internara > backup-$(date +%Y%m%d).sql

# PostgreSQL
pg_dump internara > backup-$(date +%Y%m%d).sql

# SQLite
cp database/database.sqlite backup-$(date +%Y%m%d).sqlite

# Files
tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/app/
cp .env .env.backup-$(date +%Y%m%d)
```

---

## 4. Standard Upgrade Procedure

```bash
# 1. Maintenance mode
php artisan down --render="errors::maintenance"

# 2. Pull latest code
git pull origin main
# Or extract latest ZIP over existing installation

# 3. Update dependencies
composer install --no-interaction --optimize-autoloader --no-dev
npm install && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# 6. Restart queue workers
php artisan queue:restart

# 7. Verify
php artisan system:health

# 8. Take site live
php artisan up
```

---

## 5. Rollback Procedure

If the upgrade fails:

```bash
# 1. Restore previous code
git checkout <previous-tag>

# 2. Restore database
mysql -u root -p internara < backup-$(date +%Y%m%d).sql

# 3. Restore files
tar -xzf storage-backup-$(date +%Y%m%d).tar.gz

# 4. Rollback migrations (if needed)
php artisan migrate:rollback --force

# 5. Rebuild
npm install && npm run build
php artisan optimize:clear
php artisan optimize

# 6. Bring site live
php artisan up
```

---

## 6. Upgrade Troubleshooting

| Problem | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| Migration fails | DB user lacks permissions | Grant `ALTER` and `CREATE` privileges |
| White screen after upgrade | Cached config conflicts | `php artisan optimize:clear` |
| "Class not found" | Dependencies not updated | `composer install --no-interaction` |
| Assets broken | Frontend not rebuilt | `npm install && npm run build` |
| Features not working | Migration not run | `php artisan migrate --force` |
| Queue jobs failing | Worker running old code | `php artisan queue:restart` |

---

## 7. Version Numbering

Internara follows [Semantic Versioning](https://semver.org/):

| Change | Example | Impact |
| ------ | ------- | ------ |
| **Major** | 0.x → 1.0 | Breaking changes, manual steps required |
| **Minor** | 1.0 → 1.1 | New features, no breaking changes |
| **Patch** | 1.0.0 → 1.0.1 | Bug fixes, safe to upgrade |

Current version: **0.14.0** (development preview).

---

## 8. Major Version Upgrades

Major version upgrades (e.g., 0.x → 1.0) may include:

- Database schema changes not auto-migrated
- `.env` configuration changes — diff against new `.env.example`
- Removed features — check changelog
- New prerequisites — updated PHP version, new extensions

Major version releases include a dedicated upgrade guide in the release notes.

---

## 9. Staying Up to Date

| Action | Command |
| ------ | ------- |
| Check for outdated packages | `composer outdated` |
| Watch repository | GitHub watch → Releases only |
| Test in staging first | Clone production data → upgrade → verify → repeat on production |

---

## Quick References

- `docs/specs/installation.md` — Installation feature specification
- [Installation](installation.md) — Initial setup
- [System Health](system-health.md) — Health check reference
- [System Observability](system-observability.md) — Backup management
- GitHub Releases: `https://github.com/reasvyn/internara/releases`
