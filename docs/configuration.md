# Configuration

Internara uses a **three-tier configuration system** to balance performance, security, and flexibility.

## 1. The Three-Tier System

### Tier 1: `config()` - Infrastructure
- **Purpose**: Static, code-level defaults and infrastructure settings.
- **Source**: `config/*.php` and `.env`.
- **Usage**: Use for settings that rarely change (e.g., database connections, cache drivers, encryption keys).
- **Access**: `config('file.key')`

### Tier 2: `setting()` - Business Rules
- **Purpose**: Dynamic, user-configurable values stored in the database.
- **Source**: `settings` table.
- **Usage**: Use for branding (logo, colors), feature flags, and institutional policies (e.g., attendance thresholds).
- **Access**: `setting('key')`

### Tier 3: `AppInfo` - Metadata (SSoT)
- **Purpose**: Immutable application identity.
- **Source**: `composer.json`.
- **Usage**: Use for app version, author, and license information.
- **Access**: `AppInfo::get('key')`

---

## 2. Decision Matrix

| Use Case | Tier |
|---|---|
| **Database Credentials** | `config()` (Infrastructure) |
| **Site Logo & Title** | `setting()` (Business) |
| **Attendance Start Time** | `setting()` (Business) |
| **App Version** | `AppInfo` (Metadata) |
| **Debug Mode** | `config()` (Security) |

---

## 3. Performance & Caching

- **Settings**: Database settings are cached **forever** until updated. Use `Settings::forget('key')` to invalidate.
- **Config**: Laravel configuration should be cached in production using `php artisan config:cache`.
- **Metadata**: `AppInfo` is memoized during the request lifecycle to minimize file reads.
