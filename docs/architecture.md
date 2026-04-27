# 🏗️ Architecture Guide

Deep dive into Internara's sophisticated modular monolith architecture, how components interact, and the design patterns that enable scalability without complexity.

---

## Overview

**Internara** is a **Modular Monolith** — a single application deployed as one unit, but internally organized as independent, loosely-coupled modules.

```
Traditional Monolith        Modular Monolith            Microservices
┌─────────────────────┐    ┌──────────────────────┐    ┌────────┐
│  Big Ball of Mud    │    │  Organized Modules   │    │Service1│
│ - Hard to test      │    │ - Clear boundaries   │    └────────┘
│ - Circular deps     │    │ - Testable units     │    ┌────────┐
│ - Tightly coupled   │    │ - Easy to scale      │    │Service2│
└─────────────────────┘    │ - Single deployment  │    └────────┘
                           └──────────────────────┘    ┌────────┐
                                                       │Service3│
                                                       └────────┘
```

**Why Modular Monolith?**
- Easier to develop than microservices (no distributed system complexity)
- Better organized than traditional monoliths (clear boundaries)
- Simpler deployment (single unit, single database)
- Natural boundaries for testing and refactoring

---

## System Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP/Route Layer                         │
│  (Laravel routing, middleware, HTTP handling)               │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                  UI Component Layer                         │
│  (Livewire components, forms, views)                        │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                             │
│  (Business logic, orchestration, validation)                │
│  ├─ Contracts (interfaces)                                  │
│  ├─ Services (implementations)                              │
│  └─ Repositories (data access)                              │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   Model Layer                               │
│  (Eloquent models, relationships, accessors/mutators)       │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   Database Layer                            │
│  (SQLite/PostgreSQL/MySQL, migrations)                      │
└─────────────────────────────────────────────────────────────┘
```

---

## Module Architecture

### Standard Module Structure

Every module follows a consistent directory structure:

```
modules/{ModuleName}/
│
├── src/
│   ├── Models/                    # Eloquent models (entities)
│   │   ├── Student.php
│   │   ├── Registration.php
│   │   └── ...
│   │
│   ├── Services/
│   │   ├── Contracts/             # Public API (interfaces)
│   │   │   ├── StudentService.php       (interface)
│   │   │   └── RegistrationService.php  (interface)
│   │   │
│   │   └── Implementations/        # Concrete implementations
│   │       ├── StudentService.php       (class)
│   │       └── RegistrationService.php  (class)
│   │
│   ├── Livewire/                  # Interactive UI components
│   │   ├── StudentManager.php      (data management)
│   │   ├── StudentForm.php         (form component)
│   │   ├── Forms/
│   │   │   └── StudentData.php     (form data class)
│   │   └── ...
│   │
│   ├── Http/
│   │   ├── Controllers/            # API endpoints (if needed)
│   │   └── Middleware/             # Module middleware
│   │
│   ├── Providers/
│   │   ├── ModuleServiceProvider.php
│   │   └── RouteServiceProvider.php
│   │
│   ├── Views/                      # Blade templates (if needed)
│   └── Routes/
│       └── web.php
│
├── tests/
│   ├── Unit/                       # Component logic tests
│   │   └── Services/StudentServiceTest.php
│   ├── Feature/                    # Business workflow tests
│   │   └── StudentRegistrationTest.php
│   ├── Browser/                    # UI tests (Dusk)
│   │   └── StudentManagerTest.php
│   └── Arch/                       # Architecture tests
│       └── DependencyTest.php
│
├── database/
│   ├── migrations/                 # Schema changes
│   │   └── 2026_04_22_create_students_table.php
│   ├── seeders/                    # Data seeders
│   │   └── StudentSeeder.php
│   └── factories/                  # Faker factories
│       └── StudentFactory.php
│
├── resources/
│   ├── css/                        # Module styles
│   │   └── student.css
│   ├── js/                         # Module scripts
│   │   └── student.js
│   └── lang/                       # Translations
│       ├── en/
│       │   └── student.php
│       └── id/
│           └── student.php
│
├── composer.json                   # Module dependencies
├── Module.php                      # Module configuration
└── README.md                       # Module documentation
```

### Module Directory (`modules/`)

Internara contains 29+ modules organized by domain:

**Identity & Access**: Auth, User, Profile, Permission
**Lifecycle**: Internship, Setup, Student, Mentor, Teacher
**Monitoring**: Journal, Attendance, Schedule
**Academic**: Assessment, Assignment, School, Department, Guidance
**Operations**: Report, Notification, Log, Setting, Media
**Infrastructure**: Core, Shared, UI, Status, Exception, Admin, Support

---

## Technical Installation vs. Business Setup

Internara distinguishes between technical system initialization and business-level configuration to ensure a secure and resilient deployment lifecycle.

### 1. Technical Installation (Support Module)
Handled by the `Support` module, this phase focuses on infrastructure readiness and initial environment hygiene via the Command Line Interface (CLI).
- **SystemInstaller**: Automates `.env` creation, app key generation, and storage linking.
- **InstallationAuditor**: Performs deep-system audits of PHP extensions, file permissions, and database connectivity.
- **Command**: `php artisan app:install`

### 2. Business Configuration (Setup Module)
Handled by the `Setup` module, this phase focuses on the application-level data required for operations via the Web interface.
- **AppSetupService**: Manages the state and invariants of the multi-step configuration wizard.
- **Wizard**: Guided setup for School identity, Admin accounts, Departments, and Internship programs.
- **Lockdown**: Automatic route protection and token invalidation upon completion.

---

## The Auto-Binding Engine

### How Dependency Injection Works

The **BindServiceProvider** automates service registration—no manual provider configuration needed.

```php
// Step 1: Define interface
// modules/Student/src/Services/Contracts/StudentService.php
namespace Modules\Student\Services\Contracts;

interface StudentService
{
    public function findByEmail(string $email): ?Student;
    public function create(array $data): Student;
}

// Step 2: Implement interface
// modules/Student/src/Services/StudentService.php
namespace Modules\Student\Services;

class StudentService implements StudentService  // ← Implements
{
    public function findByEmail(string $email): ?Student
    {
        return Student::where('email', $email)->first();
    }

    public function create(array $data): Student
    {
        return Student::create($data);
    }
}

// Step 3: Auto-binding (BindServiceProvider)
// Scans modules/Student/src/Services/Contracts
// Finds StudentService interface
// Derives StudentService implementation
// Registers: Container->bind(StudentService::class, StudentService::class)

// Step 4: Use via dependency injection
// modules/Student/Livewire/StudentManager.php
class StudentManager extends RecordManager
{
    public function boot(StudentService $service): void  // ← Auto-injected
    {
        $this->service = $service;  // Works! No manual registration
    }
}
```

### Naming Patterns (Fallback Order)

If interface is `StudentService`, concrete class lookup:

1. `Modules\Student\Services\StudentService` ✅ (found)
2. `Modules\Student\Services\StudentService` (empty pattern)
3. `Modules\Student\Repositories\EloquentStudentRepository`
4. Custom patterns from `config/bindings.php`

---

## Livewire Record Manager Pattern

All data-management components extend **RecordManager**, providing common CRUD functionality.

```php
use Modules\UI\Livewire\RecordManager;
use Modules\Student\Services\Contracts\StudentService;

class StudentManager extends RecordManager
{
    // 1. Service is auto-injected
    public function boot(StudentService $service): void
    {
        $this->service = $service;
    }

    // 2. Configure UI
    public function initialize(): void
    {
        $this->searchable = ['name', 'email', 'nis'];
        $this->perPage = 15;
        $this->sortBy = ['name' => 'asc'];
    }

    // 3. Define table columns
    protected function getTableHeaders(): array
    {
        return [
            [
                'key' => 'name',
                'label' => __('student::ui.name'),
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'email',
                'label' => __('common.email'),
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'created_at',
                'label' => __('common.created_at'),
                'sortable' => true,
            ],
        ];
    }

    // 4. Built-in features:
    // - Pagination (via $perPage)
    // - Search (via $searchable)
    // - Sorting (via columns)
    // - Authorization (via $viewPermission)
    // - Caching (via #[Computed])
}
```

### Built-in Features

```php
// Pagination
$this->perPage = 15;  // Records per page

// Searching
$this->searchable = ['name', 'email'];
// User types in search box → filters

// Sorting
$this->sortBy = ['name' => 'asc'];
// User clicks column header → sorts

// Authorization
protected string $viewPermission = 'student.view';
// Automatically checks authorization

// Caching dropdowns
#[Computed]
public function statuses(): Collection
{
    return Cache::remember('dropdowns:statuses', 300, fn () =>
        Status::all(['id', 'name'])
    );
}

// Custom actions
public function export(): void
{
    // Custom business logic
}
```

---

## Data Flow: Request to Response

### Example: Creating a Student

```
1. HTTP Request
   POST /students
   ↓
2. Route Handler
   routes/student/web.php
   └─ Route::post('/students', StudentManager::class)->name('student.create')
   ↓
3. Livewire Component (StudentManager)
   modules/Student/src/Livewire/StudentManager.php
   ├─ Receives form data
   └─ Calls $this->service->create($data)
   ↓
4. Service Layer
   modules/Student/src/Services/StudentService.php
   ├─ Validates data
   ├─ Applies business logic
   ├─ Calls repository
   └─ Logs action (audit trail)
   ↓
5. Repository/Model Layer
   modules/Student/src/Models/Student.php
   ├─ Encrypts sensitive fields (PII)
   ├─ Generates UUID
   ├─ Inserts into database
   └─ Fires events
   ↓
6. Database
   INSERT INTO students (id, name, email, ...)
   ↓
7. Activity Log (spatie/laravel-activitylog)
   Created by: admin@school.com
   Changed: name, email, ...
   ↓
8. Response
   JSON success or validation errors
   ↓
9. UI Updates
   Livewire real-time update (no page reload)
```

---

## Cross-Module Communication

### Pattern: Service Dependencies

Modules communicate **only through service contracts**, never by importing models or database tables.

```php
// ❌ WRONG - Direct model import (tight coupling)
// modules/Journal/src/Services/JournalService.php
namespace Modules\Journal\Services;
use Modules\Student\Models\Student;  // ❌ Cross-module import

class JournalService
{
    public function createForStudent(string $studentId): Journal
    {
        $student = Student::findOrFail($studentId);  // ❌ Direct query
        // ...
    }
}

// ✅ CORRECT - Service dependency (loose coupling)
// modules/Journal/src/Services/JournalService.php
namespace Modules\Journal\Services;
use Modules\Student\Services\Contracts\StudentService;  // ✅ Service contract

class JournalService
{
    public function __construct(
        private StudentService $studentService  // ✅ Injected service
    ) {}

    public function createForStudent(string $studentId): Journal
    {
        $student = $this->studentService->findById($studentId);  // ✅ Via service
        // ...
    }
}
```

### Benefits

- If Student service changes, Journal service doesn't break
- Student service can be swapped for testing
- No database coupling between modules
- Clear dependency direction (can visualize call graph)

---

## Event-Driven Architecture

Modules communicate asynchronously via **events**, not direct calls.

```php
// modules/Student/src/Models/Student.php
class Student extends Model
{
    use Dispatchable;

    protected $dispatchesEvents = [
        'created' => StudentCreated::class,
        'updated' => StudentUpdated::class,
        'deleted' => StudentDeleted::class,
    ];
}

// When student is created, event fires automatically:
// StudentCreated event
// └─ Listeners can react without Student service knowing

// modules/Notification/src/Listeners/SendWelcomeEmail.php
class SendWelcomeEmail
{
    public function handle(StudentCreated $event): void
    {
        // Send welcome email
        // Student service doesn't know this happens
    }
}

// modules/Journal/src/Listeners/CreateInitialJournal.php
class CreateInitialJournal
{
    public function handle(StudentCreated $event): void
    {
        // Create first journal entry
        // Student service doesn't know this happens
    }
}
```

**Advantage**: Adding new listeners doesn't modify Student service (Open/Closed Principle)

---

## Asset Orchestration

### Vite Module Loader

Custom `vite-module-loader.js` discovers and compiles assets from modules:

```javascript
// vite.config.js
import moduleLoader from './vite-module-loader.js'

export default defineConfig({
    plugins: [
        laravel({
            input: moduleLoader.discoverAssets(),  // Auto-discovers
        }),
    ],
})

// Auto-discovers:
// - modules/*/resources/css/*.css
// - modules/*/resources/js/*.js
// - modules/*/resources/views/**/*.blade.php
```

**Result**: Modules can include CSS/JS/Views without main app knowing

---

## Database Architecture

### Schema Per Module

Each module owns its database tables:

```
modules/Student/database/migrations/
├── 2026_01_01_create_students_table.php
├── 2026_01_15_add_enrollment_date_to_students_table.php
└── 2026_02_01_create_student_profiles_table.php

modules/Journal/database/migrations/
├── 2026_01_01_create_journals_table.php
└── 2026_02_01_add_keywords_to_journals_table.php
```

### No Cross-Module Foreign Keys

Modules reference each other via **UUIDs without constraints**:

```php
// ❌ WRONG - Foreign key constraint
Schema::create('journals', function (Blueprint $table) {
    $table->uuid('student_id');
    $table->foreign('student_id')
        ->references('id')
        ->on('students');  // ❌ Couples migrations
});

// ✅ CORRECT - UUID reference only
Schema::create('journals', function (Blueprint $table) {
    $table->uuid('student_id');  // ✅ Column only, no constraint
});
```

**Why**:
- Decouples migrations (modules can migrate independently)
- Avoids circular dependencies
- Allows modules to reference deleted rows (soft deletes)
- Supports module removal without cascade deletes

---

## Testing Architecture

### Test Pyramid

```
         ▲
        / \
       /   \ Browser Tests (5%)
      /     \ UI interactions, Dusk
     /───────\
    /         \ Feature Tests (30%)
   /           \ Business workflows, integration
  /─────────────\
 /               \ Unit Tests (65%)
/ ─ ─ ─ ─ ─ ─ ─ ─ \ Component logic, services, models
```

### Arch Tests Enforce Design

```php
// tests/Arch/DependencyTest.php
it('has no circular dependencies', function () {
    $modules = \Nwidart\Modules\Facades\Module::allEnabled();

    foreach ($modules as $module) {
        // Check for circular imports
        // Fail if A depends on B, B depends on A
    }
});

it('modules do not import models across boundaries', function () {
    // Scan source code
    // Find: use Modules\{Other}\Models\*;
    // Fail if found
});
```

---

## Configuration Management

### Environment-Specific Config

```
.env (local)
├─ APP_ENV=local
├─ DB_CONNECTION=sqlite
├─ QUEUE_CONNECTION=database
└─ CACHE_STORE=database

.env.staging
├─ APP_ENV=staging
├─ DB_CONNECTION=pgsql
├─ QUEUE_CONNECTION=redis
└─ CACHE_STORE=redis

.env.production
├─ APP_ENV=production
├─ DB_CONNECTION=pgsql
├─ QUEUE_CONNECTION=redis
└─ CACHE_STORE=redis
```

### Module-Specific Config

Each module can have config files:

```
modules/Student/config/student.php
└─ return [
        'fields' => ['name', 'email', 'nis'],
        'searchable' => ['name', 'email'],
    ]
```

---

## Deployment Architecture

### Single Deployment

Despite internal modularity, Internara deploys as **one unit**:

```
Production Server
├─ Single Laravel instance
├─ Single database
├─ All 29+ modules included
└─ Single entry point (public/index.php)
```

### Scaling Strategies

**Horizontal Scaling**:
```
Load Balancer
├─ Server 1 (Laravel + all modules)
├─ Server 2 (Laravel + all modules)
└─ Server 3 (Laravel + all modules)
    └─ Shared PostgreSQL database
    └─ Shared Redis cache
```

**Queue Separation** (optional):
```
Web Servers (Laravel)
└─ Handle HTTP requests

Queue Workers (Separate)
└─ artisan queue:work
```

---

## Summary

Internara's architecture achieves:

✅ **Modularity** — 29+ independent modules
✅ **Simplicity** — Single deployment, single database
✅ **Testability** — Clear boundaries enable testing
✅ **Scalability** — Can grow without architectural changes
✅ **Maintainability** — Service contracts prevent coupling
✅ **Evolution** — New modules/features don't break existing

---

## Further Reading

- [Philosophy Guide](philosophy.md) — 3S Doctrine principles
- [Modules Catalog](modules-catalog.md) — All modules and their purposes
- [Standards Guide](standards.md) — Code quality and conventions
- [Testing Guide](testing.md) — DDD practices and test structure

---

*Engineering the future of modular academic ecosystems.* 🏗️
