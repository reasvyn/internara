# Blueprint 01: System Requirements

## PHP Version

**Minimum:** PHP 8.4.0

The codebase depends on modern PHP features that are unavailable in earlier versions:
constructor property promotion, `readonly` classes, property hooks, typed class
constants, `array_find`, and `mb_trim`. Running on PHP 8.3 or below will produce
parse errors.

## Required PHP Extensions

The following extensions are checked by `config/setup.php` and validated by
`php artisan system:health`:

| Extension | Purpose |
|---|---|
| `ext-bcmath` | Arbitrary-precision arithmetic (grade/score calculations) |
| `ext-ctype` | Character validation |
| `ext-curl` | HTTP requests (remote media downloads, webhooks, API calls) |
| `ext-fileinfo` | MIME type detection for uploaded files |
| `ext-gd` | Image processing (avatars, thumbnails, WebP conversion) |
| `ext-intl` | Internationalization (locale-aware string operations) |
| `ext-mbstring` | Multibyte string ops (names, slugs, localization) |
| `ext-openssl` | Encryption, HTTPS, signed URLs |
| `ext-pdo` | Database abstraction layer |
| `ext-tokenizer` | Blade template rendering |
| `ext-xml` | Feed generation, RSS |
| `ext-zip` | File compression and archive handling |

**Built-in PHP extensions** (compiled by default, no separate install needed):
`ext-json`, `ext-filter`, `ext-hash`, `ext-session`, `ext-zlib`.

**Database-specific drivers** (install the one matching your database):
`ext-pdo_sqlite` (dev default), `ext-pdo_mysql` (MySQL), `ext-pdo_pgsql` (PostgreSQL).

## Recommended PHP Extensions

These are checked by `php artisan system:health`:

| Extension | Benefit |
|---|---|
| `ext-pcntl` | Process control for queue worker signals |
| `ext-posix` | POSIX system calls for process management |
| `ext-redis` | High-performance caching, session storage, and queue |

Additional extensions useful in production but not checked automatically:

| Extension | Benefit |
|---|---|
| `ext-opcache` | Caches compiled PHP bytecode in memory (essential for production) |
| `ext-sockets` | Required by Laravel Reverb for WebSocket connections |
| `ext-imagick` | ImageMagick — higher quality image conversions than GD |
| `ext-vips` | Higher-performance alternative for large-scale image processing |

## Verifying Requirements

The health check command validates all requirements automatically:

```bash
php artisan system:health
```

This checks PHP version, required extensions, recommended extensions, memory
limit, database connectivity, migration status, storage permissions, disk space,
queue status, cache, application key, storage link, and maintenance mode.

Use the JSON flag for machine-readable output:

```bash
php artisan system:health --json
```

## References

- `composer.json` — PHP version constraint and extension requirements
- `config/setup.php` — `requirements.extensions` and `requirements.recommended_extensions` lists
- `app/Domain/Core/Console/Commands/HealthCommand.php` — runtime validation
- `app/Domain/Setup/Services/EnvironmentAuditor.php` — setup wizard audit
