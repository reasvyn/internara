# 🏛️ The 3S Doctrine: Philosophy & Principles

The **3S Doctrine** is the philosophical foundation of **Internara**. Every design decision, architectural pattern, and line of code is governed by three immutable pillars that ensure the system's longevity, reliability, and maintainability.

---

## Overview

The 3S Doctrine represents three orthogonal but complementary concerns:

| Pillar | Focus | Benefit |
| :--- | :--- | :--- |
| 🔐 **Secure (S1)** | Data integrity, confidentiality, auditability | Trust and compliance |
| 📖 **Sustain (S2)** | Code quality, TDD, documentation | Long-term maintainability |
| ⚙️ **Scalable (S3)** | Modular design, loose coupling | Evolutionary growth |

---

## 🔐 Secure (S1) — Absolute Data Integrity

### Why Security First?

Internara manages sensitive educational and personal data for institutions and students. Security is not an afterthought—it's embedded in every layer of the architecture.

**Core Principle**: *Data confidentiality, integrity, and auditability must be guaranteed by design, not configuration.*

### S1 Implementation

#### 1. Field-Level Encryption

**What**: PII (Personally Identifiable Information) encrypted at the database layer

**How**:
- AES-256 encryption for sensitive fields
- Implemented in `Profile` module
- Transparent encryption/decryption via Eloquent mutators
- Keys stored securely in `.env`

**Examples of Encrypted Fields**:
- National ID (NIK)
- Home address
- Contact information
- Bank account numbers

**Code Pattern**:
```php
class Profile extends Model
{
    protected $encrypted = ['nik', 'address', 'phone'];
    
    // Encryption/decryption happens automatically
    $profile->nik = '3201234567890123'; // Encrypted on save
    echo $profile->nik;                 // Decrypted on read
}
```

**Why This Matters**: Even if the database is compromised, PII remains protected.

---

#### 2. Enumeration Protection

**What**: All public-facing entities use UUIDs instead of sequential integers

**Why**: Prevents ID enumeration attacks and unauthorized data discovery

**How**:
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Internship extends Model
{
    use HasUuids;  // Automatic UUID generation
}

// Generated: 550e8400-e29b-41d4-a716-446655440000
// Instead of: 1, 2, 3, 4...
```

**Security Impact**:
- Attackers cannot guess valid IDs
- No sequential pattern to exploit
- API endpoints become harder to enumerate
- Data discovery becomes significantly more difficult

**Applied to**:
- All user-facing models
- All API endpoints
- All public routes

---

#### 3. Auditability & Compliance

**What**: Every critical state change is automatically logged

**How**: `spatie/laravel-activitylog` package
```php
use Spatie\Activitylog\Traits\LogsActivity;

class Internship extends Model
{
    use LogsActivity;
    
    protected static $logAttributes = ['status', 'mentor_id', 'start_date'];
    protected static $logOnlyDirty = true;
}

// Automatically logs:
// - Who changed it
// - What changed
// - When it changed
// - Old vs. new values
```

**Audit Trail Benefits**:
- Complete history of all changes
- Tamper-evident records (timestamps)
- Regulatory compliance (for Dapodik, Ministry of Education)
- Forensic analysis capability
- PII masking in logs (automatic)

**Example Log Entry**:
```json
{
  "model": "Internship",
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "action": "updated",
  "causer": "admin@school.com",
  "changed": {
    "status": ["placed", "active"],
    "mentor_id": [null, "550e8400-e29b-41d4-a716-446655440001"]
  },
  "created_at": "2026-04-22T17:29:26Z"
}
```

---

#### 4. Access Control (RBAC)

**What**: Strict Role-Based Access Control via `spatie/laravel-permission`

**How**:
- Every resource protected by a Policy
- Every action requires explicit permission
- Roles organized hierarchically

**Example Policy**:
```php
class InternshipPolicy
{
    public function view(User $user, Internship $internship): bool
    {
        return $user->can('internship.view') &&
               $user->institution_id === $internship->institution_id;
    }

    public function update(User $user, Internship $internship): bool
    {
        return $user->can('internship.update') &&
               $user->id === $internship->created_by;
    }
}

// In controller/Livewire:
$this->authorize('update', $internship);  // Automatic check
```

**Built-in Roles**:
- **Super Admin**: Full system access
- **Admin**: Institution-level admin
- **Teacher**: Academic oversight
- **Mentor**: Industry-side mentoring
- **Student**: Internee access

---

#### 5. Setup Access Security

**What**: Installation wizard protected by one-time token

**How**:
```php
// After setup completion:
$app_installed = true;  // Locked state

// Subsequent access attempts:
// RequireSetupAccess middleware blocks all setup routes (404)
if ($setupService->isAppInstalled() && $this->isSetupRoute($request)) {
    return abort(404);  // Setup routes disappear
}
```

**Emergency Reset** (for security testing only):
```bash
php artisan app:setup-reset
```

---

### S1 Design Patterns

✅ **Fail-Secure** — Default to deny, whitelist what's allowed
✅ **Defense-in-Depth** — Multiple layers (encryption, RBAC, audit)
✅ **Least Privilege** — Users get minimum permissions needed
✅ **Auditability** — Everything is logged and traceable
✅ **Data Isolation** — Strict module boundaries prevent leakage

---

## 📖 Sustain (S2) — Code as Documentation

### Why Code Quality Matters?

Code written today must be understood and modified by developers years later. The system must evolve without degradation.

**Core Principle**: *Code is the primary communication medium. Writing testable, well-typed, properly documented code is not optional—it's essential.*

### S2 Implementation

#### 1. Technical Excellence (PSR-12)

**What**: Strict adherence to **PSR-12 (PHP Standard Recommendation)**

**Enforced via `Pint`** (Laravel's code formatter):
```bash
composer lint    # Check PSR-12 compliance
composer format  # Auto-fix violations
```

**Key Rules**:
- 4-space indentation
- No trailing whitespace
- Unix line endings (LF)
- One statement per line
- Proper spacing around operators

**Pint Configuration** (pint.json):
```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "single_quote": true,
        "strict_comparison": true,
        "trailing_comma_in_multiline": true
    }
}
```

---

#### 2. Strict Types

**What**: `declare(strict_types=1);` on every PHP file

**Required First Line**:
```php
<?php

declare(strict_types=1);

namespace App\Services;

// ... rest of file
```

**Why**:
```php
// With strict_types=1
function processAge(int $age): string
{
    // "25 years" → TypeError (not accepted)
    // 25 → OK
    return "$age years";
}

// Without strict_types=1
// Both "25 years" and 25 accepted (loose comparison)
```

**Benefits**:
- Type coercion prevented
- Bugs caught at development time
- Better IDE support and autocomplete
- Self-documenting code

---

#### 3. Test-Driven Development (TDD)

**What**: 90%+ behavioral coverage required for all functional changes

**Framework**: **Pest** (modern, expressive PHP testing)

**Test Categories**:
- **Unit Tests**: Component logic in isolation
- **Feature Tests**: Business workflows and integration
- **Architecture Tests**: Design compliance (circular dependencies, etc.)
- **Browser Tests**: Livewire UI interactions via Dusk

**Example Test**:
```php
it('creates internship with valid data', function () {
    $data = [
        'name' => 'Summer Internship 2026',
        'duration' => 3,
        'start_date' => '2026-06-01',
    ];

    $internship = Internship::create($data);

    expect($internship)->toHaveAttributes($data);
    expect($internship->status)->toBe('draft');
});
```

**Testing Requirements**:
- ✅ Every new feature must have tests
- ✅ Bug fixes must include regression tests
- ✅ 90%+ coverage minimum
- ✅ All tests must pass before merge

---

#### 4. Documentation Parity

**What**: Code and documentation stay synchronized

**Guidelines**:
- Code comments explain **WHY**, not **WHAT**
- Self-documenting code (good naming, clear structure)
- All user-facing strings use localization: `__('module::key')`
- No hardcoded strings in code

**Example**:
```php
// ❌ BAD - Obvious comment
public function save()
{
    // Save to database
    $this->model->save();
}

// ✅ GOOD - Explains why
public function save()
{
    // We refresh timestamps before saving to ensure audit trail accuracy
    // See: requirements/REQ-SEC-001 (Auditability)
    $this->model->touch();
    $this->model->save();
}
```

**Localization Example**:
```php
// ❌ BAD - Hardcoded string
echo "Hello, " . $user->name;

// ✅ GOOD - Localized
echo __('common.greeting', ['name' => $user->name]);

// In resources/lang/en/common.php
'greeting' => 'Hello, {name}'

// In resources/lang/id/common.php
'greeting' => 'Halo, {name}'
```

---

### S2 Design Patterns

✅ **Type Safety** — Declare strict types, use explicit type hints
✅ **Automated Testing** — Tests verify behavior, catch regressions
✅ **Self-Documentation** — Code naming and structure convey intent
✅ **Localization** — User-facing content is internationalized
✅ **Documentation Alignment** — Docs and code stay in sync

---

## ⚙️ Scalable (S3) — Evolutionary Architecture

### Why Modularity Matters?

Large monoliths become unmaintainable as they grow. Internara is designed to evolve without the "Big Ball of Mud" anti-pattern.

**Core Principle**: *Modules are independent, composable units. Changes in one module should not impact others.*

### S3 Implementation

#### 1. Domain Isolation (nwidart/laravel-modules)

**What**: 29+ independent modules, each with its own:
- Models
- Services
- Controllers
- Views
- Tests
- Migrations
- Configuration

**Module Structure**:
```
modules/Internship/
├── src/
│   ├── Models/
│   ├── Services/Contracts/      ← Public API (interfaces)
│   ├── Services/                 ← Implementation (auto-bound)
│   ├── Livewire/
│   ├── Http/Controllers/
│   └── Providers/
├── tests/
├── database/migrations/
├── resources/lang/
└── Module.php
```

**Why This Works**:
- Each module is independently testable
- No circular dependencies possible (enforced by tests)
- Clear module boundaries prevent "Big Ball of Mud"
- Modules can be deployed or refactored independently

---

#### 2. Loose Coupling via Contracts

**What**: Modules interact through abstracted interfaces, not implementations

**Contract Pattern**:
```php
// modules/Internship/src/Services/Contracts/InternshipService.php
namespace Modules\Internship\Services\Contracts;

interface InternshipService
{
    public function create(array $data): Internship;
    public function update(Internship $internship, array $data): void;
}

// modules/Internship/src/Services/InternshipService.php
class InternshipService implements InternshipService
{
    // Implementation
}

// Usage in another module (e.g., Student module)
class StudentService
{
    public function __construct(
        private InternshipService $internshipService  // Interface, not concrete
    ) {}

    public function enroll(Student $student): void
    {
        // Works regardless of InternshipService implementation
        $this->internshipService->create([...]);
    }
}
```

**Benefits**:
- Dependency Inversion Principle (depend on abstractions, not concretions)
- Easy to swap implementations (testing, feature flags)
- No module imports across boundaries
- Clear public API per module

---

#### 3. Auto-Binding Engine (BindServiceProvider)

**What**: Automatically discovers and binds interfaces to implementations

**How**:
```php
// Scans modules/*/src/Services/Contracts for interfaces
// Derives implementations via naming patterns
// Registers in Laravel Service Container

// Example:
// Interface: Modules\Internship\Services\Contracts\InternshipService
// Implementation: Modules\Internship\Services\InternshipService
// Auto-bound: Container->bind(InternshipService::class, InternshipService::class)
```

**Naming Patterns** (config/bindings.php):
1. `{{root}}\Services\{{short}}Service`
2. `{{root}}\Services\{{short}}`
3. `{{root}}\Repositories\Eloquent{{short}}Repository`
4. Fallback patterns (actions, repositories)

**Result**: Zero manual service provider configuration needed

---

#### 4. No Cross-Module Foreign Keys

**What**: Modules reference each other via UUIDs, not database foreign keys

**Why**:
- Prevents tight coupling at the database layer
- Allows modules to be deployed independently
- Simplifies database migrations (no coordination needed)
- Natural isolation boundary

**Example**:
```php
// ❌ BAD - Physical foreign key across modules
// modules/Internship/database/migrations/create_internships_table.php
Schema::create('internships', function (Blueprint $table) {
    $table->uuid('mentor_id');
    $table->foreign('mentor_id')  // ❌ Cross-module FK
        ->references('id')
        ->on('users');  // users table in Mentor module
});

// ✅ GOOD - UUID reference without FK
// modules/Internship/database/migrations/create_internships_table.php
Schema::create('internships', function (Blueprint $table) {
    $table->uuid('mentor_id');  // ✅ Just a column
    // No foreign key constraint
});

// When needed, retrieve via service:
class InternshipService
{
    public function __construct(
        private MentorService $mentorService  // Service dependency
    ) {}

    public function assignMentor(Internship $internship, string $mentorId): void
    {
        // Validate via service, not database constraint
        $mentor = $this->mentorService->findById($mentorId);
        $internship->mentor_id = $mentor->id;
        $internship->save();
    }
}
```

---

#### 5. Progressive Enhancement

**What**: New features and modules can be added without breaking existing contracts

**Pattern**:
```php
// v1.0 - Original contract
interface InternshipService
{
    public function create(array $data): Internship;
}

// v1.1 - New method added (backward compatible)
interface InternshipService
{
    public function create(array $data): Internship;
    public function createWithValidation(array $data): Internship;  // ← New
}

// v2.0 - Breaking change (major version bump)
interface InternshipService
{
    public function createWithValidation(array $data): Internship;
    // create() removed ← Breaking change
}
```

---

### S3 Design Patterns

✅ **Domain Isolation** — Clear module boundaries, no "Big Ball of Mud"
✅ **Contract-Based** — Interfaces define public APIs
✅ **No Cross-Module FKs** — Database isolation from module isolation
✅ **Auto-Discovery** — Bindings discovered, not configured
✅ **Backward Compatibility** — New features don't break old code

---

## Integration: How 3S Works Together

```
Secure (S1): WHO can do WHAT?
  ↓
  Policies & permissions control access
  Audit logs track everything
  Encryption protects data

Sustain (S2): HOW to write code correctly?
  ↓
  Types ensure correctness
  Tests verify behavior
  Documentation explains why

Scalable (S3): HOW to grow without breaking?
  ↓
  Modules stay independent
  Contracts define boundaries
  Changes stay localized
```

**These three pillars are **mutually reinforcing**:
- Modularity (S3) makes testing (S2) easier
- Tests (S2) ensure security (S1) isn't broken by refactoring
- Auditing (S1) works across modules because of clear boundaries (S3)

---

## Applying 3S Doctrine in Practice

### Code Review Checklist

When reviewing any PR, ask:

**🔐 Security**
- [ ] Sensitive data encrypted?
- [ ] Access controlled via Policy?
- [ ] Change is auditable?
- [ ] No sequential IDs exposed?

**📖 Sustainability**
- [ ] `declare(strict_types=1);` present?
- [ ] Tests included (90%+ coverage)?
- [ ] No hardcoded strings?
- [ ] Code comments explain WHY?

**⚙️ Scalability**
- [ ] No cross-module FK introduced?
- [ ] Contracts used correctly?
- [ ] Backward compatible?
- [ ] Module boundaries respected?

If any answer is "no," request changes.

---

## Violations & Recovery

### What Happens When 3S is Violated?

**Example: Adding Sequential IDs (S1 Violation)**
```php
// ❌ Violates S1 (Enumeration Protection)
class User extends Model
{
    // No HasUuid trait, uses sequential ID
}

// Impact: API endpoints become enumerable
GET /api/users/1, /api/users/2, /api/users/3  ← Easy to guess
```

**Recovery Process**:
1. Architect identifies violation in code review
2. Feature is blocked until violation is fixed
3. Developer must implement proper S3 compliance
4. Security review before merge

---

## Evolution of 3S Doctrine

The 3S Doctrine evolves as best practices mature:

| Version | Focus |
| :--- | :--- |
| **v0.x** | Foundation: Encryption, RBAC, TDD, modules |
| **v1.0** | Stability: Performance optimization, advanced patterns |
| **v2.0** | Expansion: Multi-tenancy, advanced analytics, AI integration |

Each version maintains 3S integrity.

---

## Summary

The **3S Doctrine** is not just philosophy—it's the structural foundation of Internara:

- **🔐 Secure**: Every line of code protects data integrity
- **📖 Sustain**: Every feature is tested, documented, clearly written
- **⚙️ Scalable**: Every module is independent, loosely coupled

This is why Internara can grow from serving one school to thousands without becoming unmaintainable.

---

## Further Reading

- [Architecture Guide](architecture.md) — Modular monolith implementation
- [Standards Guide](standards.md) — Code quality and conventions
- [Testing Guide](testing.md) — TDD practices and test structure
- [Contributing Guide](../CONTRIBUTING.md) — How to contribute while respecting 3S

---

*The 3S Doctrine: Making education technology that lasts.* 🎓
