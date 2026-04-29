# 📋 Standards & Conventions Guide

Code quality standards, naming conventions, and implementation patterns for Internara.

---

## Overview

Internara enforces consistent code quality through:

- **Pint** — PHP code style (PSR-12)
- **Prettier** — JavaScript/CSS formatting
- **Architecture tests** — No circular dependencies
- **Strict types** — Type safety throughout
- **Localization** — No hardcoded strings
- **Documentation** — Comments explain _why_, not _what_

---

## PHP Code Style (PSR-12)

### Enforced Via Pint

```bash
# Check style violations
./vendor/bin/pint --test

# Auto-fix violations
./vendor/bin/pint
```

### Key Rules

**1. File Header and Strict Types**

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Models;

// ← strict_types=1 is mandatory on line 3
```

**2. Namespace and Use Statements**

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;
use Modules\Shared\Contracts\Identifiable;

// Use statements alphabetically sorted, grouped:
// 1. External packages (Illuminate, third-party)
// 2. Internal packages (Modules\*)
// 3. Blank line between groups
```

**3. Class Declaration**

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // Properties first
// Methods second (in logical order: public, protected, private)
}
```

**4. Method Declaration and Indentation**

```php
class StudentService
{
    public function findByEmail(string $email): ?Student
    {
        return Student::where('email', $email)->first();
    }

    // ✅ 4-space indentation
    // ✅ Type hints on all parameters
    // ✅ Return type always declared
}
```

**5. Conditional Formatting**

```php
// One-liner (if simple)
if ($condition) {
    return true;
}

// Multi-line (if complex)
if (
    $user->isAdmin()
    && $internship->isActive()
    && $student->isEligible()
) {
    return true;
}

// No if-else nesting beyond 2 levels (use early return)
public function process(Student $student): void
{
    if (! $student->isActive()) {
        return;  // Early return
    }

    if (! $student->hasProfile()) {
        return;  // Early return
    }

    // Main logic here (flat, readable)
}
```

**6. String Formatting**

```php
// Single quotes for plain strings
$name = 'John Doe';

// Double quotes for interpolation
$greeting = "Hello, {$name}";

// Concatenation with space-dot-space
$message = 'Hello, ' . $name . '!';

// Nowdoc/Heredoc for long strings
$sql = <<<SQL
SELECT * FROM students
WHERE active = true
SQL;
```

**7. Array Formatting**

```php
// Short arrays
$data = ['name' => 'John', 'email' => 'john@example.com'];

// Multi-line (trailing comma)
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '123456789',
];

// Function arguments (similar rule)
$student = Student::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

---

## Naming Conventions

### Constants

```php
// UPPER_SNAKE_CASE
const MAX_ATTEMPTS = 5;
const MIN_PASSWORD_LENGTH = 8;
const INTERNSHIP_DURATION_DAYS = 90;
```

### Variables and Properties

```php
// camelCase
$studentName = 'John Doe';
$totalScore = 85.5;
$isActive = true;

// Boolean prefixes (is*, has*, can*)
$isApproved = true;
$hasPermission = false;
$canDelete = $user->hasRole('admin');
```

### Functions and Methods

```php
// camelCase, verb-first for actions
public function findByEmail(string $email): ?Student { }
public function createStudent(array $data): Student { }
public function validate(array $data): bool { }
public function getGradeAverage(): float { }

// get* prefix for accessors
public function getTableHeaders(): array { }
public function getAuthUser(): User { }
```

### Classes

```php
// PascalCase
class Student {}
class StudentService {}
class StudentServiceTest {}

// Abstract classes (abstract prefix, optional)
abstract class BaseService {}

// Interface (Service suffix or Contract)
interface StudentService {}
interface StudentContract {}

// Enum
enum StudentStatus {}
enum InternshipPhase {}

// Trait (Able suffix or Has prefix)
trait HasUuid {}
trait IsEncryptable {}
```

### Directories and Files

```
// Directories: PascalCase or lowercase
modules/Student/
modules/student/  // Also acceptable

// PHP Files: Match class name
Student.php             // class Student
StudentService.php      // class StudentService
StudentServiceTest.php  // class StudentServiceTest

// Views: snake_case
student_profile.blade.php
student_listing.blade.php

// CSS/JS: kebab-case
student-profile.css
student-manager.js

// Tests: Match class + Test suffix
StudentServiceTest.php
StudentPolicyTest.php
```

---

## Class Patterns

### Model Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;
use Modules\School\Models\Department;

class Student extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'email', 'nis'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships (public methods)
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Accessors/Mutators (using Eloquent casts)
    // Scopes (optional, for complex queries)
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Custom methods (business logic)
    public function isEligible(): bool
    {
        return $this->is_active && $this->hasCompletedProfile();
    }
}
```

### Service Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Services;

use Illuminate\Pagination\Paginator;
use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService;

class StudentService implements StudentService
{
    // Constructor injection (Contracts, not classes)
    public function __construct(private StudentRepository $repository) {}

    // Public methods (implement interface)
    public function findById(string $id): ?Student
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Student
    {
        // Validate
        $validated = $this->validate($data);

        // Create
        $student = $this->repository->create($validated);

        // Log/Event
        activity()
            ->causedBy(auth()->user())
            ->performedOn($student)
            ->log('created');

        return $student;
    }

    public function paginate(int $perPage = 15): Paginator
    {
        return $this->repository->paginate($perPage);
    }

    // Protected methods (helpers)
    protected function validate(array $data): array
    {
        return validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students',
            'nis' => 'required|unique:students',
        ])->validate();
    }
}
```

### Service Contract Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Services\Contracts;

use Illuminate\Pagination\Paginator;
use Modules\Student\Models\Student;

interface StudentService
{
    public function findById(string $id): ?Student;

    public function create(array $data): Student;

    public function update(Student $student, array $data): void;

    public function delete(Student $student): void;

    public function paginate(int $perPage = 15): Paginator;
}
```

### Livewire Manager Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Livewire\WithPagination;
use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService;
use Modules\UI\Livewire\RecordManager;

class StudentManager extends RecordManager
{
    use WithPagination;

    public function boot(StudentService $service): void
    {
        $this->service = $service;
    }

    public function initialize(): void
    {
        $this->searchable = ['name', 'email', 'nis'];
        $this->sortBy = ['name' => 'asc'];
        $this->perPage = 15;
    }

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
                'label' => __('student::ui.email'),
                'sortable' => true,
            ],
        ];
    }
}
```

---

## Type Hints and Return Types

### Required Type Hints

```php
// ✅ All parameters must have type hints
public function findStudent(string $id): Student
{
    return Student::findOrFail($id);
}

// ✅ Return types required
public function getAge(): int
{
    return Carbon::now()->diffInYears($this->birth_date);
}

// ✅ Nullable types
public function findByEmail(string $email): ?Student
{
    return Student::where('email', $email)->first();
}

// ✅ Union types (PHP 8)
public function process(int|string $id): Student
{
    $id = is_string($id) ? decrypt($id) : $id;
    return Student::findOrFail($id);
}

// ✅ Collection types (using @return docblock if no type)
/**
 * @return Collection<int, Student>
 */
public function getStudents(): Collection
{
    return Student::all();
}
```

### Avoid

```php
// ❌ No type hints = error
public function find($id)  // Missing types
{
    return Student::find($id);
}

// ❌ Mixed types = error
public function create(mixed $data): mixed
{
    // Avoid 'mixed' without specific reason
}

// ❌ Any = error
/** @param any $value */
public function process($value): any { }
```

---

## Comments and Documentation

### Comment Style: WHY, Not WHAT

```php
// ❌ BAD - Explains what code does (obvious)
$count = 0; // Initialize count to 0
foreach ($students as $student) {
    if ($student->isActive()) {
        $count++; // Increment count
    }
}

// ✅ GOOD - Explains why
// We only count active students because inactive ones don't contribute to reporting
$activeCount = $students->filter(fn($s) => $s->isActive())->count();
```

### Doc Blocks (For Complex Logic)

```php
/**
 * Calculates final grade based on multiple assessment sources.
 *
 * Weights:
 * - School Assessment: 40%
 * - Mentor Evaluation: 40%
 * - Journal Quality: 20%
 *
 * @throws InvalidGradeException if weights don't sum to 100%
 *
 * @return float Final grade (0-100)
 */
public function calculateFinalGrade(Student $student): float
{
    $schoolScore = $this->getSchoolAssessment($student) * 0.4;
    $mentorScore = $this->getMentorEvaluation($student) * 0.4;
    $journalScore = $this->getJournalQuality($student) * 0.2;

    return $schoolScore + $mentorScore + $journalScore;
}
```

### No Inline Comments (Use Method Names Instead)

```php
// ❌ BAD
$validated = validator($data, [
    'email' => 'required|email|unique:users',  // Email must be unique
])->validate();

// ✅ GOOD - Method name is self-documenting
$validated = $this->validateEmail($data);

private function validateEmail(array $data): array
{
    return validator($data, [
        'email' => 'required|email|unique:users',
    ])->validate();
}
```

---

## Localization (No Hardcoded Strings)

### Translation Keys (Always)

```php
// ❌ BAD - Hardcoded string
echo 'Welcome to Internara';

// ✅ GOOD - Translation key
echo __('common.welcome');

// ✅ GOOD - With parameters
echo __('common.welcome_name', ['name' => $user->name]);
```

### Translation File Structure

```
resources/lang/
├── en/
│   ├── common.php          # Shared strings
│   ├── student.php         # Student module
│   ├── internship.php      # Internship module
│   └── ...
└── id/
    ├── common.php
    ├── student.php
    └── ...
```

### Translation File Format

```php
// resources/lang/en/student.php
<?php
declare(strict_types=1);

return [
    'name' => 'Student',
    'students' => 'Students',
    'email' => 'Email Address',
    'nis' => 'Student ID (NIS)',

    'messages' => [
        'created' => 'Student created successfully.',
        'updated' => 'Student updated successfully.',
        'deleted' => 'Student deleted successfully.',
    ],

    'validation' => [
        'email_required' => 'Email address is required.',
        'email_unique' => 'This email is already registered.',
        'nis_invalid' => 'Student ID format is invalid.',
    ],
];

```

### Using Translations

```php
// In Blade templates
<h1>{{ __('student.students') }}</h1>
<label>{{ __('student.email') }}</label>

// In PHP classes
$message = __('student.messages.created');
$error = __('student.validation.email_required');

// With parameters
$greeting = __('common.welcome_name', ['name' => $student->name]);
// Translation: 'Welcome, {name}!' → 'Welcome, John!'
```

---

## Database Standards

### Column Naming

```php
// snake_case
Schema::create('students', function (Blueprint $table) {
    $table->uuid('id'); // ✅ Primary key
    $table->string('first_name'); // ✅ snake_case
    $table->string('email')->unique();
    $table->string('national_id')->nullable();
    $table->uuid('department_id'); // ✅ Foreign key (no constraint)
    $table->timestamps(); // created_at, updated_at
    $table->softDeletes(); // deleted_at (optional)
});
```

### Migration Naming

```php
// 2026_04_22_120000_create_students_table.php
// Pattern: YYYY_MM_DD_HHMMSS_action_name_table.php

// ✅ Good names
create_students_table
add_email_to_students_table
drop_unused_column_from_students_table
create_student_profiles_table

// ❌ Avoid
fix_table
update_schema
change_stuff
```

### No Hardcoded Database Names

```php
// ❌ BAD
Schema::connection('specific_db')->table('students', ...);

// ✅ GOOD - Use environment config
Schema::table('students', ...);
// Config comes from .env: DB_CONNECTION=sqlite
```

---

## Validation Standards

### Custom Validation Rules

```php
// ✅ Reusable validation rule
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'nis' => ['required', new ValidNIS()],  // Custom rule
        'birth_date' => ['required', 'date', 'before:today'],
    ];
}

// Custom rule
class ValidNIS implements Rule
{
    public function passes($attribute, $value): bool
    {
        // NIS must be 9 digits
        return preg_match('/^\d{9}$/', $value) === 1;
    }

    public function message(): string
    {
        return __('student.validation.nis_invalid');
    }
}
```

### Validation Messages

```php
// ✅ Use translation keys
public function messages(): array
{
    return [
        'email.required' => __('student.validation.email_required'),
        'email.unique' => __('student.validation.email_unique'),
        'nis.required' => __('student.validation.nis_required'),
    ];
}
```

---

## JavaScript/CSS Standards

### Formatted via Prettier

```bash
# Check formatting
npx prettier --check resources/

# Auto-format
npx prettier --write resources/
```

### JavaScript Standards

```javascript
// ✅ Use const/let (not var)
const apiUrl = '/api/students'
let count = 0

// ✅ Camel case for variables
const studentEmail = 'john@example.com'

// ✅ Arrow functions
const getStudent = (id) => fetch(`/api/students/${id}`)

// ✅ Template literals
const message = `Welcome, ${name}!`

// ✅ Meaningful variable names
const isStudentActive = true // ✅
const x = true // ❌
```

### CSS Standards

```css
/* ✅ Use utility classes (Tailwind) */
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Students</h1>
</div>

/* ✅ BEM naming (if custom CSS needed) */
.student-card {
    /* Block */
}

.student-card__header {
    /* Element */
}

.student-card--active {
    /* Modifier */
}

/* ❌ Avoid !important */
.hide {
    display: none !important; /* ❌ Anti-pattern */
}

/* ✅ Use Tailwind or CSS variables */
.hide {
    display: none;
}
```

---

## Domain-Driven Design (DDD) Patterns

Internara follows a **Modular DDD** architecture. Logic is organized by domain building blocks.

### Value Object Pattern

Immutable objects defined by their attributes.

```php
<?php

declare(strict_types=1);

namespace Modules\Setup\Domain\ValueObjects;

final readonly class SetupToken
{
    private function __construct(public string $value) {}

    public static function fromRaw(string $value): self
    {
        return new self($value);
    }

    public function matches(string $other): bool
    {
        return hash_equals($this->value, $other);
    }
}
```

### Entity Pattern

Objects with a persistent identity.

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Domain\Models;

class Student
{
    public function __construct(public readonly string $id, public string $name) {}
}
```

### Aggregate Root Pattern

A cluster of domain objects treated as a single unit.

```php
<?php

declare(strict_types=1);

namespace Modules\Setup\Domain\Models;

class SetupProcess
{
    public function __construct(protected bool $isInstalled, protected array $steps = []) {}

    public function completeStep(string $step): void
    {
        $this->steps[$step] = true;
    }
}
```

---

## Testing Standards

Testing in Internara is centered around **Domain-Driven Design (DDD) Modular** principles. Tests are
used to validate domain invariants and correct behavior of domain building blocks.

### Test File Organization

```php
// ✅ Organized by concern
describe('StudentService', function () {
    describe('findByEmail', function () {
        it('returns student when found', function () {});
        it('returns null when not found', function () {});
    });

    describe('create', function () {
        it('creates student with valid data', function () {});
        it('fails with invalid email', function () {});
    });
});
```

### Test Naming

```php
// ✅ Clear, behavior-focused
it('allows admin to view student profile', function () { })
it('prevents student from viewing other profiles', function () { })

// ❌ Implementation-focused
it('checks if statement', function () { })
it('calls service method', function () { })
```

---

## Performance Standards

### Query Optimization

```php
// ❌ N+1 query problem
foreach ($students as $student) {
    echo $student->department->name; // 1 + N queries
}

// ✅ Eager loading
$students = Student::with('department')->get(); // 2 queries
foreach ($students as $student) {
    echo $student->department->name;
}
```

### Caching Standards

```php
// ✅ Cache expensive operations
$departments = Cache::remember(
    'dropdowns:departments',
    300,
    fn() => Department::all(['id', 'name']),
);

// ✅ Clear cache on updates
Department::create($data);
Cache::forget('dropdowns:departments');
```

---

## Git and Commits

### Commit Message Format

```
Type: Brief description

Optional longer explanation of why this change was made.

- Point 1
- Point 2

Fixes #123
Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
```

### Commit Types

```
feat:    New feature
fix:     Bug fix
refactor: Code reorganization without behavior change
perf:    Performance improvement
docs:    Documentation change
test:    Test additions/updates
style:   Code style changes (formatting, naming)
chore:   Dependency updates, config changes
```

### Example Commits

```
feat: add student registration endpoint

Implements student self-registration with email verification.
Also sends welcome email after registration.

- Create RegistrationRequest validator
- Add StudentRegistrationAction service
- Add WelcomeEmail notification
- Update routes with validation

Fixes #456
Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
```

---

## API Standards

### RESTful API Conventions

```php
// ✅ Resource naming (plural, kebab-case)
GET    /api/students           // List resources
POST   /api/students           // Create resource
GET    /api/students/{id}      // Show resource
PUT    /api/students/{id}      // Update resource
DELETE /api/students/{id}      // Delete resource

// ✅ Nested resources (when logically owned)
GET    /api/students/{id}/journals
POST   /api/students/{id}/journals

// ✅ Actions (non-CRUD) use verb as endpoint
POST   /api/students/{id}/approve
POST   /api/internships/{id}/complete
```

### API Response Format

```php
// ✅ Success response
return response()->json([
    'success' => true,
    'data' => $student,
    'message' => __('student.messages.created'),
], 201);

// ✅ Error response
return response()->json([
    'success' => false,
    'message' => __('student.validation.email_required'),
    'errors' => $validator->errors(),
], 422);
```

### API Authentication

```php
// ✅ Use Laravel Sanctum for SPA authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('students', StudentController::class);
});

// ✅ API tokens for external integrations
$token = $user->createToken('integration-token', ['read', 'write'])->plainTextToken;
```

---

## Module Structure Standards

### Directory Layout (Recommended)

```
modules/
└── Student/
    ├── Config/
    │   └── config.php
    ├── Console/
    │   └── Commands/
    ├── Database/
    │   ├── Factories/
    │   ├── Migrations/
    │   └── Seeders/
    ├── Domain/              # OPTIONAL - Only for modules with complex business logic
    │   ├── Models/        # Domain models (business logic, invariants)
    │   ├── ValueObjects/  # Immutable value objects
    │   └── Events/       # Domain events
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── Resources/
    ├── Livewire/           # Livewire components
    ├── Models/            # Eloquent models (persistence)
    ├── Services/
    │   ├── Contracts/   # Interfaces (recommended for all modules)
    │   └── Impl...       # Service implementations
    ├── Providers/
    │   └── StudentServiceProvider.php
    ├── Resources/
    │   └── views/
    ├── Routes/
    │   ├── api.php
    │   └── web.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    └── lang/
        ├── en/
        └── id/
```

**Note:** Domain layer is **optional**. Only add it when:
- Module has complex business logic that needs encapsulation
- Business rules require explicit invariants
- Domain models differ significantly from Eloquent models

**Simplicity Principle:** If a module only does CRUD with basic validation, skip the Domain layer. Complexity must be earned by demonstrated need (AGENTS.md Principle 3).

### Module Provider Registration

```php
<?php

declare(strict_types=1);

namespace Modules\Student\Providers;

use Illuminate\Support\ServiceProvider;

class StudentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Student\Services\Contracts\StudentService::class,
            \Modules\Student\Services\StudentService::class
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'student');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'student');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
```

---

## Security Standards

### Input Validation (Context-Aware)

```php
// Public API (strict validation)
class RegisterStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:students'],
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', new ValidNIS()],
            'birth_date' => ['required', 'date', 'before:today'],
        ];
    }
}

// Internal admin (lighter validation)
class UpdateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
            'value' => ['present'],
        ];
    }
}
```

### Authorization (Policies)

```php
// ✅ Policy for authorization
class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->id === $student->user_id;
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isAdmin() || 
               ($user->id === $student->user_id && $student->isEditable());
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin(); // Only admins can delete
    }
}

// Usage in controller
$this->authorize('view', $student);
```

### Data Protection

| Data Type | Protection Level | Example |
|----------|----------------|---------|
| **PII** (name, email, NIS, NIK) | Encrypt at rest, mask in logs | `Cast: 'nis' => 'encrypted'` |
| **Credentials** (passwords) | Hash with bcrypt/argon2 | `Hash::make($password)` |
| **Tokens** (API keys) | Never log, rotate regularly | `Laravel Sanctum` |
| **Public data** (modules, settings) | No special protection | Plain storage |

**Rules**:
- **Never** commit `.env` or secrets to version control
- **Never** expose PII in logs or error messages
- **Always** use HTTPS in production
- **Prefer** environment variables for configuration

---

## Error Handling Standards

### Explicit Failure (Not Paranoid)

```php
// ❌ Silent failure (bad)
try {
    $student->submitJournal($journal);
} catch (\Exception $e) {
    // Silently swallowed - BAD
}

// ✅ Balanced approach
try {
    return $student->submitJournal($journal);
} catch (ValidationException $e) {
    throw $e;  // Re-throw domain exceptions
} catch (\Exception $e) {
    Log::error('Journal submission failed', [
        'student_id' => $student->id,
        'error' => $e->getMessage(),
    ]);
    throw new DomainException(__('student.errors.submission_failed'), 0, $e);
}
```

### Custom Exception Classes

```php
// ✅ Domain-specific exceptions
class InvalidNISException extends DomainException {}
class JournalSubmissionClosedException extends DomainException {}
class StudentNotEligibleException extends DomainException {}

// Usage
throw new InvalidNISException(__('student.validation.nis_invalid'));
```

---

## Event & Listener Standards

### Domain Events

```php
// ✅ Event definition
class StudentRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly string $password
    ) {}
}

// ✅ Event dispatch
event(new StudentRegistered($student, $password));

// ✅ Listener
class SendWelcomeEmail
{
    public function handle(StudentRegistered $event): void
    {
        Notification::send(
            $event->student,
            new WelcomeNotification($event->password)
        );
    }
}
```

### EventServiceProvider Registration

```php
protected $listen = [
    StudentRegistered::class => [
        SendWelcomeEmail::class,
        LogActivity::class,
    ],
    JournalSubmitted::class => [
        NotifyMentor::class,
        UpdateProgress::class,
    ],
];
```

---

## Notification Standards

### Notification Structure

```php
// ✅ Notification class
class WelcomeNotification extends Notification
{
    public function __construct(
        private readonly string $password
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('student.emails.welcome.subject'))
            ->greeting(__('student.emails.welcome.greeting', ['name' => $notifiable->name]))
            ->line(__('student.emails.welcome.line1'))
            ->action(__('student.emails.welcome.action'), url('/login'))
            ->line(__('student.emails.welcome.password', ['password' => $this->password]));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => __('student.notifications.welcome.title'),
            'message' => __('student.notifications.welcome.message'),
        ];
    }
}
```

---

## File Upload Standards

### Validation & Storage

```php
// ✅ File upload validation
class UploadJournalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120', // 5MB
            ],
            'journal_id' => ['required', 'exists:journals,id'],
        ];
    }
}

// ✅ Secure file storage
$path = $request->file('journal')->store(
    "journals/{$journal->id}",
    'private' // Use 'private' disk for sensitive files
);

// ✅ Generate secure download URL
return response()->download(storage_path("app/private/{$path}"));
```

### Allowed File Types

| Type | Extensions | Max Size | Storage |
|------|-----------|----------|---------|
| Documents | pdf, doc, docx | 5MB | private |
| Images | jpg, png, jpeg | 2MB | public |
| Archives | zip, rar | 10MB | private |

---

## Audit Trail Standards

### Activity Logging

```php
// ✅ Log important actions using spatie/laravel-activitylog
activity()
    ->causedBy(auth()->user())
    ->performedOn($student)
    ->withProperties([
        'attributes' => $student->getAttributes(),
        'old' => $student->getOriginal(),
    ])
    ->log('updated');

// ✅ Custom activity description
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'email', 'nis'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

### What to Log

- [x] User login/logout
- [x] Student create/update/delete
- [x] Journal submissions
- [x] Grade changes
- [x] Setting changes
- [x] Permission changes
- [x] File uploads/deletions

---

## Environment Configuration

### .env Standards

```bash
# ✅ Use descriptive prefixes
INTERNARA_APP_NAME="Internara"
INTERNARA_APP_URL="${APP_URL}"

# Module-specific config
STUDENT_MAX_JOURNALS_PER_DAY=3
STUDENT_ALLOWED_FILE_TYPES="pdf,doc,docx"
STUDENT_MAX_FILE_SIZE=5120

# External services
MAIL_MAILER=smtp
MAIL_HOST=...
```

### Config File Structure

```php
// modules/Student/Config/config.php
return [
    'max_journals_per_day' => env('STUDENT_MAX_JOURNALS_PER_DAY', 3),
    'allowed_file_types' => env('STUDENT_ALLOWED_FILE_TYPES', 'pdf,doc,docx'),
    'max_file_size' => env('STUDENT_MAX_FILE_SIZE', 5120),
    
    // Don't expose sensitive data in config
    // Use .env for secrets
];
```

---

## Performance Standards

### Query Optimization

```php
// ❌ N+1 problem
foreach ($students as $student) {
    echo $student->department->name; // 1 + N queries
}

// ✅ Eager loading
$students = Student::with('department')->get();
foreach ($students as $student) {
    echo $student->department->name; // 2 queries total
}

// ✅ Specific columns
$students = Student::with('department:id,name')->get(['id', 'name', 'department_id']);
```

### Caching Strategy

```php
// ✅ Cache dropdowns (rarely change)
$departments = Cache::remember(
    'dropdowns:departments',
    now()->addDay(),
    fn() => Department::all(['id', 'name'])
);

// ✅ Clear cache on updates
public function updated(Department $department): void
{
    Cache::forget('dropdowns:departments');
}

// ✅ Cache expensive computations
$statistics = Cache::remember(
    "student:{$student->id}:statistics",
    now()->addHour(),
    fn() => $this->calculateStatistics($student)
);
```

---

## Testing Standards

### Test Structure (Pest PHP)

```php
// ✅ Clear, behavior-focused tests
describe('StudentService', function () {
    describe('register', function () {
        it('creates student with valid data', function () {
            $data = Student::factory()->make()->toArray();
            
            $student = app(StudentService::class)->register($data);
            
            expect($student)->toBeInstanceOf(Student::class)
                ->and($student->email)->toBe($data['email']);
        });
        
        it('throws exception for invalid NIS', function () {
            $data = Student::factory()->make(['nis' => 'invalid'])->toArray();
            
            expect(fn() => app(StudentService::class)->register($data))
                ->toThrow(InvalidNISException::class);
        });
    });
});
```

### Test Coverage Requirements

| Type | Coverage Target | Notes |
|------|----------------|-------|
| Unit Tests | 80% | Domain logic, services |
| Feature Tests | 70% | HTTP endpoints, Livewire |
| Critical Paths | 100% | Registration, payment, submission |

---

## Further Reading

- [Philosophy Guide](philosophy.md) — 3S Doctrine principles
- [Architecture Guide](architecture.md) — Design patterns
- [Testing Guide](testing.md) — Testing standards and practices

---

_Consistency builds quality._ 📋
