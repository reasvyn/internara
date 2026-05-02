# Infrastructure and Dependencies: Internara

This document provides a comprehensive overview of the technical stack, infrastructure requirements, and dependencies for the `internara` project.

## 1. Core Runtime
- **PHP**: `^8.4`
- **Framework**: Laravel `^13.0`
- **Frontend Engine**: Livewire `^4.3`
- **Node.js**: Required for frontend asset bundling (Vite).
- **Package Managers**: 
  - `composer` for PHP dependencies.
  - `npm` or `pnpm` for JavaScript dependencies.

## 2. Technology Stack (The "MARY" Stack)
The project utilizes a modern Laravel stack focused on developer velocity and a "No-JS" (Livewire-heavy) approach:
- **UI Components**: [Mary UI](https://mary-ui.com/) `^2.4`
- **CSS Framework**: Tailwind CSS `^4.2` (via `@tailwindcss/vite`)
- **UI Library**: DaisyUI `^5.5`
- **Icons**: Blade Tabler Icons `^3.36`

## 3. Primary Backend Dependencies (Spatie Ecosystem & Others)
The project leverages high-quality industry-standard packages:
- **Access Control**: `spatie/laravel-permission` (Roles and Permissions)
- **Media Management**: `spatie/laravel-medialibrary`
- **Audit Trails**: `spatie/laravel-activitylog`
- **State Management**: `spatie/laravel-model-states` ^2.14 (Tracking model transitions for InternshipRegistration, SupervisionLog, RequirementSubmission, OfficialDocument)
- **Modular Structure**: `nwidart/laravel-modules` & `mhmiton/laravel-modules-livewire` — legacy, pending removal. See section "Known Issues" below.
- **Flash Notifications**: `php-flasher/flasher-laravel` (Temporary user feedback messages)
- **Security**: `spatie/laravel-honeypot` (Spam protection)

### PHPFlasher Details
- **Package**: `php-flasher/flasher-laravel`
- **Version**: Latest stable
- **Purpose**: Provides elegant flash messages (success, error, warning, info) with toastr-style notifications
- **Theme**: Emerald (configured in `config/flasher.php`)
- **Integration**: 
  - Backend: `flash()` helper function
  - Frontend: `@flasher_render` Blade directive in `resources/views/components/layouts/base.blade.php`
  - Assets: Automatically included via config (minified JS/CSS)
- **Features**:
  - Multiple message types (success, error, warning, info)
  - Auto-dismiss with configurable timeout (default: 5000ms)
  - Position control (bottom-right, top-center, etc.)
  - Dark mode support (`'darkMode' => true`)
  - Session-based flash message mapping (`flash_bag` config)
- **Usage**: See `docs/notification.md` section 7 for complete documentation

## 4. Utilities and Tools
- **PDF Generation**: `barryvdh/laravel-dompdf`
- **Localization**: `laravel-lang/lang` (Multi-language support)
- **QR Codes**: `simplesoftwareio/simple-qrcode`
- **Console Utilities**: `laravel/tinker`

## 5. Development and Testing
- **Testing Framework**: [Pest PHP](https://pestphp.com/) `^4.2`
- **Code Style**: Laravel Pint (PHP) & Prettier (JS/Blade/PHP)
- **Static Analysis**: PHPStan `^2.1` (Level 8, config in `phpstan.neon`)
- **Debugging**: Laravel Pail, Laravel Boost, Mockery, Faker.
- **Environment**: Laravel Sail (Docker-based development environment).

### 5.1 Quality Assurance Infrastructure
The project includes comprehensive quality tooling:

#### Code Quality Tests (`tests/Quality/`)
- **CodeStabilityTest**: Hardcoded paths, SQL injection, silent failures
- **PerformanceTest**: N+1 queries, missing pagination, inefficient checks
- **SecurityTest**: Mass assignment, input validation, sensitive data exposure

#### Architectural Tests (`tests/Arch/`)
Split by concern into focused files:
- `GlobalCodingStandardsTest.php` — Strict types, clean code
- `Layers/LayerSeparationTest.php` — Layer dependency rules
- `Models/ModelStandardsTest.php` — UUIDs, business rules
- `Actions/ActionStandardsTest.php` — Stateless actions, execute() method
- `Controllers/ControllerStandardsTest.php` — Thin controllers
- `OptionalLayers/*Test.php` — Repositories, Events, Listeners
- `Requests/RequestStandardsTest.php` — FormRequest validation
- `Services/ServiceStandardsTest.php` — Infrastructure services

#### Composer Scripts for Quality
```bash
composer quality          # Quick check: lint + static analysis + arch tests
composer quality:full     # Full check: format + strict analysis + coverage
composer test:coverage   # Run tests with 80% coverage requirement
composer test:arch       # Run only architectural tests
composer analyse          # PHPStan level 8
composer analyse:strict  # PHPStan level max
```

## 6. Frontend Infrastructure
- **Bundler**: Vite `^7.3`
- **HTTP Client**: Axios
- **Image Processing**: Cropperjs
- **Plugins**: `@tailwindcss/vite`, `prettier-plugin-blade`, `@prettier/plugin-php`.

## 7. Configuration Summary
- **Activity Log**: `config/activitylog.php`
- **Permissions**: `config/permission.php`
- **Media Library**: `config/media-library.php`
- **Livewire**: `config/livewire.php`
- **Flasher**: `config/flasher.php` — PHPFlasher toastr notifications configuration
  - Theme: Emerald
  - Timeout: 5000ms
  - Position: bottom-right
  - Dark mode: enabled
  - Assets: `/vendor/flasher/` (JS/CSS)

> **Note**: `config/modules.php` and `config/modules-livewire.php` are legacy and will be removed with the modules.

## 8. CI/CD Pipeline (`.github/workflows/ci.yml`)
Automated quality checks via GitHub Actions:

| Job | Purpose | Tools |
|-----|---------|-------|
| **quality** | Code style & static analysis | Pint, PHPStan |
| **architecture** | Layer separation enforcement | Pest Arch Tests |
| **tests** | Feature & Unit tests with coverage | Pest PHP (min 80%) |
| **security** | Vulnerability scanning | Trivy |
| **summary** | Overall status check | — |

All jobs must pass before merging to `main` or `develop` branches.

## 9. Known Issues
- **Legacy modules**: `modules/` directory contains unused code from the pre-MVC architecture. `app/Console/Kernel.php` still references a module class, which causes fatal errors when running tests. Resolution: module autoloading has been disabled in `config/modules.php` and related configs.

## 10. Requirements for Deployment
- **Web Server**: Nginx or Apache (Laravel compatible).
- **Database**: MySQL, PostgreSQL, or SQLite (SQLite is mentioned in `post-create-project-cmd`).
- **Cache/Queue**: Redis or Database driver (Standard Laravel drivers).
- **File Storage**: Local or S3-compatible (Spatie Media Library requirement).

## 11. Project Structure (Enhanced Layered Architecture)
```
app/
├── Actions/           # Stateless use cases (orchestration layer)
├── Models/            # Rich models with business rules
├── Http/
│   ├── Controllers/   # Thin controllers (API endpoints)
│   ├── Requests/      # Form Request validation classes
│   └── Middleware/
├── Livewire/          # Stateful UI components (Web only)
├── Policies/          # Authorization rules
├── Events/            # Domain events for side effects
├── Listeners/         # Event handlers (notifications, audit, emails)
├── Repositories/      # Optional: Complex query abstraction
├── Services/          # Infrastructure services (technical concerns)
├── Data/              # DTOs for data transfer
├── Enums/             # Fixed business statuses
└── Support/           # Cross-cutting concerns
```

See `docs/architecture.md` for detailed layer guidelines and anti-patterns.
