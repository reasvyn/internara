# Infrastructure and Dependencies: Internara

This document provides a comprehensive overview of the technical stack, infrastructure requirements, and dependencies for the `internara` project.

## 1. Core Runtime
- **PHP**: `^8.4`
- **Framework**: Laravel `^12.0`
- **Frontend Engine**: Livewire `^3.7` & Livewire Volt `^1.10`
- **Node.js**: Required for frontend asset bundling (Vite).
- **Package Managers**: 
  - `composer` for PHP dependencies.
  - `npm` or `pnpm` for JavaScript dependencies.

## 2. Technology Stack (The "MARY" Stack)
The project utilizes a modern Laravel stack focused on developer velocity and a "No-JS" (Livewire-heavy) approach:
- **UI Components**: [Mary UI](https://mary-ui.com/) `^2.4`
- **CSS Framework**: Tailwind CSS `^4.2` (via `@tailwindcss/vite`)
- **UI Library**: DaisyUI `^5.5`
- **Icons**: 
  - Blade Tabler Icons `^3.36`
  - Blade MDI Icons `^1.1`

## 3. Primary Backend Dependencies (Spatie Ecosystem & Others)
The project leverages high-quality industry-standard packages:
- **Access Control**: `spatie/laravel-permission` (Roles and Permissions)
- **Media Management**: `spatie/laravel-medialibrary`
- **Audit Trails**: `spatie/laravel-activitylog`
- **State Management**: `spatie/laravel-model-status` (Tracking model transitions)
- **Modular Structure**: `nwidart/laravel-modules` & `mhmiton/laravel-modules-livewire` (Note: Currently being phased out in favor of standardized MVC).
- **Notifications**: `php-flasher/flasher-laravel`
- **Security**: `spatie/laravel-honeypot` (Spam protection)

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
- `GlobalCodingStandardsTest.php` - Strict types, clean code
- `Layers/LayerSeparationTest.php` - Layer dependency rules
- `Models/ModelStandardsTest.php` - UUIDs, business rules
- `Actions/ActionStandardsTest.php` - Stateless actions, execute() method
- `Controllers/ControllerStandardsTest.php` - Thin controllers
- `OptionalLayers/*Test.php` - Repositories, Events, Listeners
- `Requests/RequestStandardsTest.php` - FormRequest validation
- `Services/ServiceStandardsTest.php` - Infrastructure services

**Total**: 53 tests (142 assertions) enforcing 3S Doctrine compliance.

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

## 7. Configuration Summary (config/)
- **Activity Log**: Configured in `config/activitylog.php`.
- **Permissions**: Configured in `config/permission.php`.
- **Media Library**: Configured in `config/media-library.php`.
- **Modules**: Configuration for modular structure in `config/modules.php`.
- **Livewire**: Custom settings in `config/livewire.php` and `config/modules-livewire.php`.
- **Flasher**: Integration for flash messages in `config/flasher.php`.

## 8. CI/CD Pipeline (`.github/workflows/ci.yml`)
Automated quality checks via GitHub Actions:

| Job | Purpose | Tools |
|-----|---------|-------|
| **quality** | Code style & static analysis | Pint, PHPStan |
| **architecture** | Layer separation enforcement | Pest Arch Tests |
| **tests** | Feature & Unit tests with coverage | Pest PHP (min 80%) |
| **security** | Vulnerability scanning | Trivy |
| **summary** | Overall status check | - |

All jobs must pass before merging to `main` or `develop` branches.

## 9. Requirements for Deployment
- **Web Server**: Nginx or Apache (Laravel compatible).
- **Database**: MySQL, PostgreSQL, or SQLite (SQLite is mentioned in `post-create-project-cmd`).
- **Cache/Queue**: Redis or Database driver (Standard Laravel drivers).
- **File Storage**: Local or S3-compatible (Spatie Media Library requirement).

## 10. Project Structure (Enhanced Layered Architecture)
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
