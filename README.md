# 📚 Internara - Enterprise Internship Management System

[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.7-FB70A9?style=for-the-badge&logo=livewire)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-4.1-38B2AC?style=for-the-badge&logo=tailwind-css)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-0.14.0-blue?style=for-the-badge)](app_info.json)

**Internara** is an enterprise-grade **Internship Management System** architected as a sophisticated **Modular Monolith**. Built to bridge the gap between educational institutions and industry partners, it delivers a secure, scalable, and highly maintainable digital ecosystem compliant with Indonesian education standards (Dapodik integration-ready).

**📖 [Full Documentation](docs/README.md)** — Installation, Getting Started, and Architecture Guides

---

## 🎯 Overview

Internara manages the complete internship lifecycle across multiple stakeholders:
- **Students**: Track progress, submit journals, complete assessments
- **Mentors** (Industry): Evaluate performance, guide development, provide feedback
- **Teachers** (Educational): Design assessments, grade outcomes, issue credentials
- **Administrators**: Configure system, manage institutions, oversee compliance

---

## 🏛️ Philosophy: The 3S Doctrine

Every line of code in **Internara** adheres to three immutable pillars:

### 1. 🔐 Secure (S1) — Absolute Data Integrity

**Field-Level Encryption**
- Sensitive PII (National IDs, addresses) encrypted at database layer using AES-256
- Protected under `Profile` module with mandatory encryption middleware

**Enumeration Protection**
- All public-facing entities use UUID v4 instead of sequential IDs
- Prevents enumeration attacks and unauthorized data discovery via `HasUuid` trait

**Auditability & Compliance**
- Every critical state transition recorded via `spatie/laravel-activitylog`
- Tamper-evident audit trails for regulatory compliance
- PII masking in all system logs

**Access Control**
- Strict RBAC (Role-Based Access Control) via `spatie/laravel-permission`
- Mandatory authorization Policies on every domain resource
- Setup access protected by one-time `setup_token` and `RequireSetupAccess` middleware

---

### 2. 📖 Sustain (S2) — Code as Documentation

**Technical Excellence**
- Strictly adheres to **PSR-12** and **Laravel 12** idioms
- Enforces `declare(strict_types=1);` on every PHP file
- Static analysis via **Pint** (linting) with zero high-severity violations tolerance

**Test-Driven Development (TDD)**
- **90%+ behavioral coverage** required for all functional changes
- Comprehensive test suites: Arch, Unit, Feature, Browser
- Built on **Pest** (modern PHP testing framework)
- CI/CD gates ensure no regression

**Documentation Parity**
- All technical artifacts documented in **English** globally
- Code comments explain "why", not "what" (self-documenting code)
- Inline localization via `__('module::file.key')` — no hardcoded strings

---

### 3. ⚙️ Scalable (S3) — Evolutionary Architecture

**Domain Isolation**
- 29+ independent modules via `nwidart/laravel-modules`
- Strict domain boundaries prevent "Big Ball of Mud" anti-pattern
- **No physical foreign keys across modules** — UUID references only

**Loose Coupling**
- Modules interact through abstracted **Contracts** (interfaces)
- Service Container auto-discovery via `BindServiceProvider`
- Implementation swaps have zero impact on consumers

**Modular Evolution**
- Modules can be deployed, scaled, or refactored independently
- Shared kernel via `Core` and `Shared` modules
- Progressive enhancement without breaking existing contracts

---

## 🛠️ Architecture & Implementation

### The Modular Monolith Engine

Internara splits into **29+ independent business modules**, each a self-contained unit with:

```
modules/{ModuleName}/
├── src/
│   ├── Models/              # Eloquent models (domain entities)
│   ├── Services/
│   │   └── Contracts/       # Public API (interfaces)
│   │   └── {Service}.php    # Implementation (auto-discovered)
│   ├── Livewire/            # Interactive UI components
│   │   ├── RecordManager.php    # Data management components
│   │   └── Forms/           # Form data classes
│   ├── Providers/           # Module service provider
│   ├── Views/               # Blade templates (if needed)
│   ├── Http/
│   │   └── Controllers/     # API/HTTP endpoints (if needed)
│   └── Routes/              # Modular route definitions
├── tests/
│   ├── Unit/                # Logical component tests
│   ├── Feature/             # Business flow tests
│   ├── Browser/             # Livewire/UI tests (Dusk)
│   └── Arch/                # Architecture compliance tests
├── database/
│   ├── migrations/          # Module-specific schema
│   ├── seeders/             # Module data factories
│   └── factories/           # Faker data factories
├── resources/
│   ├── css/                 # Module-scoped styles
│   ├── js/                  # Module-scoped scripts
│   └── lang/                # i18n translations
├── composer.json            # Module dependencies
└── Module.php               # Module configuration
```

### The Auto-Binding Engine (`BindServiceProvider`)

The architectural innovation enabling loose coupling:

```php
// Scans modules/*/src/Contracts for interfaces
// Automatically derives & binds implementations

// Example:
// Interface: Modules\Internship\Services\Contracts\InternshipService
// Auto-discovered: Modules\Internship\Services\InternshipService
// Auto-bound: Container->bind(InternshipService::class, InternshipService::class)
```

**How it works:**
1. Discovers all interfaces in `*/src/Services/Contracts` and `*/src/Contracts`
2. Derives concrete class via configured naming patterns:
   - `{{root}}\Services\{{short}}Service`
   - `{{root}}\Services\{{short}}`
   - And fallback patterns (repos, actions)
3. Validates interface & class exist, then registers binding
4. Supports contextual bindings for complex scenarios

**Benefits:**
- Eliminates thousands of lines of manual provider configuration
- Enforces **Dependency Inversion Principle** at scale
- Enables true modular architecture

---

### Asset Orchestration (Vite + Module Loader)

Custom `vite-module-loader.js` dynamically discovers and compiles:
- CSS from `modules/*/resources/css`
- JavaScript from `modules/*/resources/js`
- Blade views from `modules/*/resources/views`

Result: Modules remain **truly portable and independent**.

---

### Livewire Record Manager Pattern

All data-management components extend `RecordManager`, not bare `Component`:

```php
class StudentManager extends RecordManager
{
    protected string $viewPermission = 'student.view';

    public function boot(StudentService $service): void
    {
        $this->service = $service;  // Auto-injected
    }

    public function initialize(): void
    {
        $this->searchable = ['name', 'email'];
        $this->perPage = 15;
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('common.name'), 'sortable' => true],
            // ...
        ];
    }
}
```

**Features:**
- Built-in pagination, search, sorting, filtering
- Automatic authorization checks via `$viewPermission`
- Shared dropdown data caching via `Cache::remember()`
- Computed properties via `#[Computed]` attribute

---

## 🏗️ Technical Stack

| Layer | Technology | Version | Purpose |
| :--- | :--- | :--- | :--- |
| **Framework** | Laravel | 12.0+ | Application core, routing, ORM |
| **Runtime** | PHP | 8.4+ | Server-side runtime with property hooks |
| **Interactivity** | Livewire | 3.7+ | Reactive components without JavaScript |
| **Templating** | Blade | 12.0+ | Server-side HTML templating |
| **Styling** | Tailwind CSS | 4.1+ | Utility-first CSS framework |
| **UI Components** | Mary UI + DaisyUI | 2.4 / 5.5 | Pre-built accessible components |
| **Module System** | nwidart/laravel-modules | 12.0+ | Modular architecture |
| **Permissions** | spatie/laravel-permission | 6.24+ | RBAC implementation |
| **Audit Logging** | spatie/laravel-activitylog | 4.10+ | State change tracking |
| **Media Handling** | spatie/laravel-medialibrary | 11.17+ | Cloud-ready file management |
| **Testing** | Pest + PHPUnit | 4.3+ / 12.5+ | Test framework |
| **Browser Testing** | Laravel Dusk | 8.3+ | End-to-end testing |
| **Code Quality** | Pint | 1.26+ | PSR-12 linting |
| **Formatting** | Prettier | 3.8+ | Code formatting (PHP/Blade/JS) |
| **Database** | SQLite/PostgreSQL/MySQL | Latest | Supports multiple engines |
| **Build Tool** | Vite | 7.3+ | Asset bundling & HMR |
| **Testing Tools** | PHPStan | 2.1+ | Static analysis |

---

## 📊 Module Directory (29+ Modules)

### Core Domains

**Identity & Access**
- `Auth` — Multi-guard authentication (Student, Mentor, Admin)
- `User` — User management and account lifecycle
- `Profile` — PII storage (encrypted), personal data
- `Permission` — RBAC policies and role assignments

**Lifecycle Management**
- `Internship` — Placements, registrations, requirements
- `Setup` — Multi-step onboarding wizard with security gates
- `Student` — Student dashboards, progress tracking
- `Mentor` — Mentor dashboards, mentee management
- `Teacher` — Assessment, grading, and credential issuance

**Activity Monitoring**
- `Journal` — Daily logs, supervisor validation
- `Attendance` — Check-ins, geolocation tracking, reporting
- `Schedule` — Internship timeline and event scheduling

**Academic & Assessment**
- `Assessment` — Multi-stakeholder grading, rubrics, transcripts
- `Assignment` — Task definitions, submission tracking
- `School` — Educational institution setup and management
- `Department` — Organizational structure and scoping
- `Guidance` — Handbook management and distribution

**Operations & Support**
- `Report` — Analytics dashboards and export functionality
- `Notification` — Multi-channel alerts (email, SMS, in-app)
- `Log` — Activity audit trails and system events
- `Setting` — System-wide configuration management
- `Media` — File storage, processing, and retrieval

**Infrastructure**
- `Core` — Shared kernel, base classes, helpers
- `Shared` — Cross-module utilities and contracts
- `UI` — Design system, reusable components, styling
- `Status` — Shared status enumerations
- `Exception` — Custom exception classes
- `Admin` — Administrative dashboards and tooling
- `Support` — Help documentation and support tools

---

## 🔧 Conventions & Standards

### Code Style

**PHP**
- **Standard**: PSR-12 (enforced via Pint)
- **Strict Types**: `declare(strict_types=1);` on every file
- **Imports**: Alphabetically ordered (const, class, function)
- **Naming**: `camelCase` for methods, `PascalCase` for classes, `UPPER_SNAKE_CASE` for constants
- **Type Safety**: All method parameters and returns must be explicitly typed

**JavaScript / CSS**
- **Standard**: Prettier (100 char line width, single quotes, trailing commas)
- **Blade**: Blade-specific Prettier plugin for `.blade.php` formatting

**Database**
- **IDs**: Always UUID v4 via `HasUuid` trait
- **Timestamps**: Always include `timestamps()` (created_at, updated_at)
- **Localization**: Use `lang/` directory for all user-facing strings
- **No Cross-Module FKs**: UUID references only, no physical foreign keys

### File Structure

```
modules/{Domain}/
├── src/Models/{Model}.php
├── src/Services/Contracts/{Service}Contract.php
├── src/Services/{Service}.php
├── src/Livewire/{Component}.php
├── src/Livewire/Forms/{FormData}.php
├── tests/{Type}/{TestName}.php
├── database/migrations/{YYYY_MM_DD_hhmmss}_{description}.php
└── resources/lang/{locale}/{namespace}.php
```

### Class Patterns

**Service Classes**
```php
// Must extend BaseService or EloquentQuery
// Must implement corresponding Contract
// Constructor: dependency injection only (no static calls)
class InternshipService extends BaseService implements InternshipContract
{
    public function __construct(
        private InternshipRepository $repository,
        private EventDispatcher $events,
    ) {}

    public function create(array $data): Internship
    {
        // Implementation
    }
}
```

**Livewire Managers**
```php
// Must extend RecordManager (not bare Component)
// Must implement initialize() and getTableHeaders()
class StudentManager extends RecordManager
{
    protected string $viewPermission = 'student.view';
    
    public function initialize(): void { /* ... */ }
    protected function getTableHeaders(): array { /* ... */ }
}
```

**Models**
```php
// Must use HasUuid trait
// Must use timestamps()
// Must declare all attributes as typed properties
class Student extends Model
{
    use HasUuid;
    
    protected $fillable = ['name', 'email'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

---

## 🧪 Testing & Quality Assurance

### Test Structure

```
tests/
├── Pest.php                 # Test configuration & helpers
├── TestCase.php             # Base test case
├── DuskTestCase.php         # Browser testing base
├── Unit/                    # Logical component tests
├── Feature/                 # Business flow integration tests
├── Browser/                 # Livewire/UI tests (Dusk)
└── Arch/                    # Architecture compliance

modules/{Domain}/tests/      # Same structure per module
```

### Test Suites (PHPUnit Configuration)

| Suite | Path | Purpose |
| :--- | :--- | :--- |
| **Arch** | `tests/Arch/` + `modules/*/tests/Arch/` | Architecture compliance (no circular deps, etc.) |
| **Unit** | `tests/Unit/` + `modules/*/tests/Unit/` | Individual component logic |
| **Feature** | `tests/Feature/` + `modules/*/tests/Feature/` | Business workflows, integration |
| **Browser** | `tests/Browser/` + `modules/*/tests/Browser/` | Livewire components, UI interactions |

### Testing Requirements

- **Coverage**: 90%+ behavioral coverage for functional changes
- **Framework**: Pest (modern, expressive PHP testing)
- **Database**: SQLite in-memory for speed
- **Helpers**: Custom expectations and assertion methods in `tests/Pest.php`
- **No Hard Data**: Always use factories/seeders, never fixtures

### Quality Gates

```bash
# Full verification (required before merge)
composer test       # Run all test suites
composer lint       # Pint + Prettier checks
composer format     # Auto-format code

# Development workflow
npm run dev         # Watch mode: Laravel + Vite + Queue
composer dev        # All services: server, queue, logs, vite
```

---

## 🚀 Getting Started

> 📖 **For detailed installation and setup instructions**, see the [Installation Guide](docs/installation.md) and [Getting Started Guide](docs/getting_started.md).

### Prerequisites

- **PHP**: 8.4 or higher (`php -v`)
- **Node.js**: 20.x or higher (`node -v`)
- **Composer**: Latest (`composer --version`)
- **Git**: For cloning and version control

### Quick Start (5 minutes)

```bash
# 1. Clone the repository
git clone https://github.com/reasvyn/internara.git
cd internara

# 2. Automated setup (installs deps, generates key, migrates DB, builds assets)
composer setup

# 3. Start development (all services)
composer dev
```

**That's it!** Visit **http://localhost:8000** and follow the Setup Wizard.

### Development Workflow

```bash
# All services: Laravel server, queue, logs, Vite
composer dev

# In another terminal, run tests
composer test

# Check code quality
composer lint     # Check only
composer format   # Auto-format
```

### Next Steps

1. **[Installation Guide](docs/installation.md)** — Detailed setup, database configuration, production deployment
2. **[Getting Started Guide](docs/getting_started.md)** — Development workflow, testing, debugging
3. **[Documentation Index](docs/README.md)** — All documentation resources

---

## 🔐 Security

Internara implements **Security-by-Design** across every layer:

### Data Protection
- **Field Encryption**: AES-256 for PII (NIK, addresses)
- **UUID Enumeration**: No sequential IDs, prevents scraping
- **Audit Trails**: Every state change logged immutably
- **PII Masking**: Automatic redaction in logs

### Access Control
- **RBAC**: Role-based access via `spatie/laravel-permission`
- **Policies**: Mandatory authorization on domain resources
- **Setup Gate**: One-time token for installation, locked after

### Compliance
- **Dapodik Ready**: Data structures aligned with Indonesian education standards
- **Auditability**: Complete state history for regulatory inspection
- **Privacy**: Strict data isolation between institutions and users

### Vulnerability Reporting

⚠️ **Do NOT open public issues for security vulnerabilities.**

Report to: **[reasvyn@gmail.com](mailto:reasvyn@gmail.com)**

See [SECURITY.md](SECURITY.md) for full protocol.

---

## 📋 Contributing

We welcome high-quality contributions! Follow the [CONTRIBUTING.md](CONTRIBUTING.md) guidelines:

1. **Fork** the repository
2. **Create a branch**: `feature/module/description` or `fix/module/issue`
3. **Code**: Follow 3S Doctrine, write tests, enforce PSR-12
4. **Verify**: `composer test && composer lint`
5. **Commit**: Use Conventional Commits format
6. **Submit PR**: With clear description of what and why

### Required Checks (Before Merge)

- ✅ `composer test` passes (no failures)
- ✅ `composer lint` passes (no style violations)
- ✅ `declare(strict_types=1);` on all PHP files
- ✅ No hardcoded strings (use `__('module::key')`)
- ✅ New models use `HasUuid` and `timestamps()`
- ✅ New services implement Contract
- ✅ Livewire managers extend `RecordManager`
- ✅ Documentation updated for new modules/features

---

## 🏛️ Governance & Maintenance

- **Model**: Benevolent Dictatorship (Lead Maintainer: [@reasvyn](https://github.com/reasvyn))
- **Decisions**: Documented via Architectural Decision Records (ADRs)
- **Support**: Latest stable release only (see [versioning-policy.md](versioning-policy.md))
- **Communication**: GitHub Issues & Discussions (see [SUPPORT.md](SUPPORT.md))

See [GOVERNANCE.md](GOVERNANCE.md) and [MAINTAINERS.md](MAINTAINERS.md) for details.

---

## 📦 Version & Release

**Current Version**: `0.14.0` (Experimental)

**Strategy**: Semantic Versioning (SemVer 2.0.0)
- **MAJOR**: Breaking architectural changes
- **MINOR**: New features (backward-compatible)
- **PATCH**: Bug fixes and security patches

See [versioning-policy.md](versioning-policy.md) for detailed release lifecycle.

---

## 📄 License

Internara is open-source software licensed under the **MIT License**.

Copyright © 2025–2026 Reas Vyn

See [LICENSE](LICENSE) for full text.

---

## 🤝 Support & Contact

- **GitHub**: [github.com/reasvyn](https://github.com/reasvyn)
- **Email**: [reasvyn@gmail.com](mailto:reasvyn@gmail.com)
- **Issues**: [GitHub Issues](https://github.com/reasvyn/internara/issues)
- **Discussions**: [GitHub Discussions](https://github.com/reasvyn/internara/discussions)

---

## 🎓 Learn More

### 📖 Documentation
- **[Documentation Index](docs/README.md)** — Complete guide to all resources
- **[Getting Started Guide](docs/getting_started.md)** — Quick start & development workflow
- **[Installation Guide](docs/installation.md)** — Detailed setup & production deployment
- **[Philosophy Guide](docs/philosophy.md)** — 3S Doctrine and architectural principles
- **[Architecture Guide](docs/architecture.md)** — Modular monolith design & patterns
- **[Modules Catalog](docs/modules-catalog.md)** — All 29+ modules and their purposes
- **[Testing Guide](docs/testing.md)** — TDD practices with Pest framework
- **[Standards & Conventions](docs/standards.md)** — Code quality, naming, PSR-12

### 🤝 Contributing & Governance
- **[CONTRIBUTING.md](CONTRIBUTING.md)** — Contribution guidelines & code patterns
- **[GOVERNANCE.md](GOVERNANCE.md)** — Project governance and decision-making
- **[MAINTAINERS.md](MAINTAINERS.md)** — Core maintainers and responsibilities

### 🔐 Policy & Standards
- **[SECURITY.md](SECURITY.md)** — Security protocols and vulnerability reporting
- **[SUPPORT.md](SUPPORT.md)** — Getting help and support channels
- **[versioning-policy.md](versioning-policy.md)** — Release strategy and compatibility
- **[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)** — Community standards

---

*Internara — **Engineering the future of modular academic ecosystems.***

Built with 🛡️ Security, 📖 Sustainability, and ⚙️ Scalability.
