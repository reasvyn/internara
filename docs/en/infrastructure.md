# Infrastructure

## Runtime Requirements

- PHP 8.4 or higher
- Node.js 20 or higher
- Database: SQLite (default), MySQL 8+, MariaDB, or PostgreSQL 14+
- Composer and npm

## Backend Packages

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Role-based access control with team support |
| `spatie/laravel-medialibrary` | File attachments (School, Submission models) |
| `spatie/laravel-activitylog` | Model change tracking and audit trail |
| `spatie/laravel-model-status` | Polymorphic status tracking on User model |
| `spatie/laravel-honeypot` | Spam protection |
| `barryvdh/laravel-dompdf` | PDF generation |
| `php-flasher/flasher-laravel` | Flash messages |
| `robsontenorio/mary` | maryUI Blade component library |
| `secondnetwork/blade-tabler-icons` | Tabler icon set |
| `simplesoftwareio/simple-qrcode` | QR code generation |
| `laravel-lang/lang` | Multi-language support (ID/EN) |
| `laravel/reverb` | WebSocket broadcasting for real-time features |

## Frontend Stack

| Tool | Purpose |
|---|---|
| Vite 7 | Asset bundler |
| TailwindCSS 4 | CSS framework (CSS-first configuration) |
| DaisyUI 5 | UI component themes |
| Alpine.js | Client-side interactivity (bundled with Livewire) |
| Cropper.js | Image cropping |
| Prettier | Code formatting (JS, Blade, PHP) |

## Development Tools

| Tool | Purpose |
|---|---|
| Pest 4 | Testing framework |
| PHPStan 2 | Static analysis (`--level=max`) |
| Laravel Pint | PHP code style |
| Laravel Reverb | WebSocket server |
| Laravel Sail | Docker-based development |
| Laravel Boost | MCP server for IDE integration |
| Laravel Pail | Real-time log viewer |

## Composer Scripts

Run `composer.json` scripts for common tasks. The minimum coverage threshold and available commands are defined in the `scripts` section of `composer.json`.

## CI/CD

GitHub Actions runs on every pull request: code quality (Pint, PHPStan), architecture tests, Pest with 80% coverage, and Trivy security scan.
