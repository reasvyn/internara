# Infrastructure & Dependencies

## Runtime

- PHP 8.4+, Laravel 13, Livewire 4
- Database: SQLite (default), MySQL 8+, PostgreSQL 14+
- Queue: `database` driver (default)

## Backend Packages

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | RBAC with team support |
| `spatie/laravel-medialibrary` | File attachments (School, Submission models) |
| `spatie/laravel-activitylog` | Model change tracking |
| `spatie/laravel-model-status` | Status tracking (User model) |
| `spatie/laravel-model-states` | **Installed but unused** — Entities handle state |
| `spatie/laravel-honeypot` | Spam protection |
| `barryvdh/laravel-dompdf` | PDF generation |
| `php-flasher/flasher-laravel` | Flash messages |
| `laravel-lang/lang` | Multi-language (ID/EN) |
| `robsontenorio/mary` | maryUI Blade components |
| `secondnetwork/blade-tabler-icons` | Icon set |
| `simplesoftwareio/simple-qrcode` | QR code generation |

## Frontend

| Package | Purpose |
|---|---|
| Vite 7 | Bundler |
| TailwindCSS 4 | CSS framework (CSS-first config) |
| DaisyUI 5 | Component theme |
| Alpine.js | Client behavior (via Livewire) |
| Cropper.js | Image upload/crop |
| Prettier | JS/Blade/PHP formatting |

## Development Tools

| Tool | Purpose |
|---|---|
| Pest 4 | Testing framework |
| PHPStan 2 | Static analysis (level 8) |
| Laravel Pint | PHP code style |
| Laravel Sail | Docker development |
| Laravel Boost | MCP server for IDE integration |
| Laravel Pail | Real-time log viewer |

## Composer Scripts

```bash
composer dev             # Start: server, queue, logs, vite
composer test            # All tests
composer test:coverage   # Tests with 80% min coverage
composer test:arch       # Architecture tests only
composer test:feature    # Feature tests only
composer test:unit       # Unit tests only
composer quality         # lint + analyse + arch
composer quality:full    # format + strict analyse + coverage
composer format          # Pint + Prettier
composer lint            # Pint check + Prettier check
```

## CI/CD

GitHub Actions runs on every PR: quality (Pint, PHPStan), architecture tests, Pest with 80% coverage, and Trivy security scan. All jobs must pass before merging.