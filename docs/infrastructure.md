# Infrastructure & Dependencies

## Runtime

- PHP 8.4+
- Laravel 13
- Livewire 4
- SQLite (default), MySQL 8+, PostgreSQL 14+

## Frontend

- Vite 7 (bundler)
- TailwindCSS 4 + DaisyUI 5 (styling)
- maryUI 2 (Blade components)
- Alpine.js (client behavior)
- Axios (HTTP client)
- Cropperjs (image processing)

## Backend Packages

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Roles and permissions (RBAC) |
| `spatie/laravel-medialibrary` | File attachments with collections and conversions |
| `spatie/laravel-activitylog` | Model change tracking |
| `spatie/laravel-model-states` | State machine transitions |
| `spatie/laravel-model-status` | Status tracking for complex entities |
| `spatie/laravel-honeypot` | Spam protection |
| `barryvdh/laravel-dompdf` | PDF generation |
| `php-flasher/flasher-laravel` | Flash messages (Emerald theme) |
| `laravel-lang/lang` | Multi-language support |
| `simplesoftwareio/simple-qrcode` | QR code generation |
| `robsontenorio/mary` | maryUI Blade component library |
| `secondnetwork/blade-tabler-icons` | Tabler icon set |

## Development Tools

| Tool | Purpose |
|---|---|
| Pest 4 | Testing framework |
| PHPStan 2 | Static analysis (level 8) |
| Laravel Pint | PHP code style |
| Prettier | JS/Blade/PHP formatting |
| Laravel Sail | Docker development |
| Laravel Boost | MCP server for IDE integration |
| Laravel Pail | Real-time log viewer |

## Composer Scripts

```bash
composer dev             # Start: server, queue, logs, vite
composer test            # Run all tests
composer test:coverage   # Tests with 80% minimum coverage
composer test:arch       # Architectural tests only
composer quality         # Quick: lint + analyse + arch
composer quality:full    # Full: format + strict analyse + coverage
composer analyse         # PHPStan level 8
composer analyse:strict  # PHPStan level max
composer format          # Pint + Prettier
composer lint            # Pint check + Prettier check
```

## CI/CD

GitHub Actions (`.github/workflows/ci.yml`) runs on every PR:

| Job | Tools |
|---|---|
| quality | Pint, PHPStan |
| architecture | Pest arch tests |
| tests | Pest (80% min coverage) |
| security | Trivy vulnerability scan |

All jobs must pass before merging to `main` or `develop`.
