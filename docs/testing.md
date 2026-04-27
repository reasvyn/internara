# 🧪 Testing Guide

Comprehensive guide to Internara's **Domain-Driven Design (DDD) Modular** testing practices,
framework (Pest), and quality assurance.

---

## Overview

Internara requires **90%+ code coverage** and follows a **DDD-centric approach**. Tests are used to
validate the integrity of the domain model and ensure business invariants are strictly enforced.

```
Domain-Driven Design (DDD) Testing Workflow

1. DISCOVER  Identify domain concepts and invariants
2. MODEL     Define Entities, Value Objects, and Aggregates
3. VALIDATE  Write tests to enforce domain invariants
4. IMPLEMENT Realize the domain model in code
```

---

## Testing Framework: Pest

**Pest** is a modern testing framework for PHP, built on top of PHPUnit. It's more readable and
enjoyable than traditional PHPUnit.

### Why Pest?

```php
// ❌ PHPUnit (verbose)
class StudentServiceTest extends TestCase
{
    public function test_it_can_find_a_student_by_email()
    {
        $student = Student::factory()->create(['email' => 'john@example.com']);
        $found = $this->app->make(StudentService::class)->findByEmail('john@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($found->id, $student->id);
    }
}

// ✅ Pest (fluent)
it('finds a student by email', function () {
    $student = Student::factory()->create(['email' => 'john@example.com']);
    $found = app(StudentService::class)->findByEmail('john@example.com');
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($student->id);
});
```

### Benefits

- Cleaner, more readable syntax
- Built-in expectations (no assertion boilerplate)
- Better error messages
- Runs on PHPUnit (compatibility)
- Half as many lines of code

---

## Test Pyramid

Internara follows the **test pyramid** principle: many unit tests, fewer feature tests, minimal UI
tests.

```
       /\
      /  \  Browser Tests (5%)
     /    \ UI interactions (Dusk)
    /──────\
   /        \  Feature Tests (30%)
  /          \ Business workflows
 /────────────\
/              \ Unit Tests (65%)
/ ─ ─ ─ ─ ─ ─ ─ \ Component logic, services, models
```

### Test Pyramid Explained

**Unit Tests (Bottom) — Fast, many**

- Test individual functions/methods
- No database, no external calls
- 1-5ms per test
- Example: `StudentServiceTest`

**Feature Tests (Middle) — Moderate**

- Test business workflows
- Use in-memory SQLite database
- Include HTTP requests and job queues
- Example: `StudentRegistrationTest`

**Browser Tests (Top) — Slow, few**

- Test user interactions
- Use headless Chrome (Dusk)
- Slow (0.5-2 seconds per test)
- Example: `StudentDashboardTest`

---

## Test Suite Structure

Internara has four test suites defined in `phpunit.xml`:

### 1. Unit Tests

```xml
<testsuite name="Unit">
    <directory>tests/Unit</directory>
    <directory>modules/*/tests/Unit</directory>
</testsuite>
```

**Purpose**: Test business logic in isolation

**Example**:

```php
// tests/Unit/Services/StudentServiceTest.php
it('validates required fields', function () {
    $service = app(StudentService::class);

    expect(fn() => $service->create([]))->toThrow(ValidationException::class);
});

it('generates UUID on creation', function () {
    $service = app(StudentService::class);
    $student = $service->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($student->id)->toBeUuid();
});
```

### 2. Feature Tests

```xml
<testsuite name="Feature">
    <directory>tests/Feature</directory>
    <directory>modules/*/tests/Feature</directory>
</testsuite>
```

**Purpose**: Test complete workflows, including database and APIs

**Example**:

```php
// tests/Feature/StudentRegistrationTest.php
it('allows students to register for internship', function () {
    $school = School::factory()->create();
    $internship = Internship::factory()->for($school)->create();

    $response = $this->post(route('internship.register'), [
        'internship_id' => $internship->id,
        'email' => 'student@example.com',
        'name' => 'John Doe',
    ]);

    expect($response)->toRedirect();
    expect(Registration::where('internship_id', $internship->id)->count())->toBe(1);
});

it('prevents duplicate registrations', function () {
    $registration = Registration::factory()->create();

    $response = $this->post(route('internship.register'), [
        'internship_id' => $registration->internship_id,
        'email' => $registration->user->email,
        'name' => $registration->user->profile->name,
    ]);

    expect($response)->toHaveValidationErrors('email');
});
```

### 3. Architecture Tests

```xml
<testsuite name="Arch">
    <directory>tests/Arch</directory>
    <directory>modules/*/tests/Arch</directory>
</testsuite>
```

**Purpose**: Enforce architectural rules (no circular dependencies, no model imports, etc.)

**Example**:

```php
// tests/Arch/DependencyTest.php
it('has no circular dependencies', function () {
    // Scan all modules
    // Check: Module A depends on B, B depends on A
    // Fail if found
});

it('modules do not import models across module boundaries', function () {
    // Scan source files
    // Pattern: use Modules\{OtherModule}\Models\*
    // Fail if found (use Services/Contracts instead)
});

it('enforces strict types on all PHP files', function () {
    // Scan all PHP files
    // Check first line: declare(strict_types=1);
    // Fail if missing
});

it('no hardcoded strings in views', function () {
    // Scan blade files
    // Pattern: {{ 'string' }} or "string"
    // Fail if found (use __('key') instead)
});
```

### 4. Browser Tests

```xml
<testsuite name="Browser">
    <directory>tests/Browser</directory>
    <directory>modules/*/tests/Browser</directory>
</testsuite>
```

**Purpose**: Test UI interactions with actual browser (Dusk)

**Example**:

```php
// tests/Browser/StudentDashboardTest.php
it('allows student to view dashboard', function () {
    $user = User::factory()->student()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser
            ->loginAs($user)
            ->visit('/dashboard')
            ->assertSee('Welcome')
            ->assertSee('My Internship')
            ->click('@start-journal')
            ->assertPathIs('/journal/create');
    });
});
```

---

## Running Tests

### Run All Tests

```bash
composer test
```

### Run Specific Suite

```bash
# Unit tests only
composer test -- --testsuite=Unit

# Feature tests only
composer test -- --testsuite=Feature

# Architecture tests
composer test -- --testsuite=Arch

# Browser tests
composer test -- --testsuite=Browser
```

### Run Specific Test File

```bash
composer test -- tests/Unit/Services/StudentServiceTest.php
```

### Run Specific Test

```bash
composer test -- --filter testValidatesRequiredFields
```

### Watch Mode (Re-run on file change)

```bash
composer test -- --watch
```

### Generate Coverage Report

```bash
composer test -- --coverage-html coverage/
```

Then open `coverage/index.html` in browser.

---

## Writing Tests

### Test File Structure

```php
// tests/Unit/Services/StudentServiceTest.php
<?php
declare(strict_types=1);

use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService;
use Tests\TestCase;

describe('StudentService', function () {
    describe('findByEmail', function () {
        it('returns student when found', function () {
            $student = Student::factory()->create(['email' => 'john@example.com']);

            $found = app(StudentService::class)->findByEmail('john@example.com');

            expect($found)->not->toBeNull();
            expect($found->id)->toBe($student->id);
        });

        it('returns null when not found', function () {
            $found = app(StudentService::class)->findByEmail('nonexistent@example.com');

            expect($found)->toBeNull();
        });
    });

    describe('create', function () {
        it('creates student with valid data', function () {
            $service = app(StudentService::class);

            $student = $service->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'nis' => '123456789',
            ]);

            expect($student)->toBeInstanceOf(Student::class);
            expect($student->id)->toBeUuid();
            expect(Student::find($student->id))->not->toBeNull();
        });

        it('fails with invalid email', function () {
            $service = app(StudentService::class);

            expect(
                fn() => $service->create([
                    'name' => 'John Doe',
                    'email' => 'invalid-email',
                ]),
            )->toThrow(ValidationException::class);
        });
    });
});

```

### Best Practices

**1. Test Behavior, Not Implementation**

```php
// ❌ BAD - Testing implementation details
it('calls database query', function () {
    $mock = Mockery::mock(StudentService::class);
    $mock->shouldReceive('query')->once();
    $mock->findByEmail('john@example.com');
});

// ✅ GOOD - Testing behavior
it('returns student by email', function () {
    $student = Student::factory()->create(['email' => 'john@example.com']);

    $found = app(StudentService::class)->findByEmail('john@example.com');

    expect($found->id)->toBe($student->id);
});
```

**2. One Assertion Per Test (or Logical Group)**

```php
// ❌ BAD - Too many assertions
it('student workflow', function () {
    $student = Student::factory()->create();
    expect($student)->toExist();
    expect($student->name)->not->toBeNull();
    expect($student->email)->toContain('@');
    expect($student->profile)->toExist();
    expect($student->profile->nik)->toBeEncrypted();
});

// ✅ GOOD - Logical groups
it('creates student with profile', function () {
    $student = Student::factory()->create();
    expect($student)->toExist();
});

it('encrypts PII', function () {
    $student = Student::factory()->create();
    expect($student->profile->nik)->toBeEncrypted();
});
```

**3. Use Factories**

```php
// Define factory in database/factories
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'nis' => $this->faker->numerify('#########'),
        ];
    }
}

// Use in tests
it('lists students', function () {
    $students = Student::factory(10)->create();

    $response = $this->get(route('student.index'));

    expect($response->viewData('students')->count())->toBe(10);
});
```

**4. Test Edge Cases**

```php
it('handles empty input', function () {
    expect(fn() => app(StudentService::class)->create([]))->toThrow(ValidationException::class);
});

it('handles null input', function () {
    expect(fn() => app(StudentService::class)->findByEmail(null))->toThrow(TypeError::class);
});

it('handles very long strings', function () {
    $longName = str_repeat('a', 1000);

    expect(
        fn() => app(StudentService::class)->create([
            'name' => $longName,
            'email' => 'test@example.com',
        ]),
    )->toThrow(ValidationException::class);
});
```

**5. Mock External Dependencies**

```php
it('sends welcome email on registration', function () {
    Mail::fake();

    $student = Student::factory()->create();
    event(new StudentCreated($student));

    Mail::assertSent(WelcomeEmail::class, function (WelcomeEmail $mail) use ($student) {
        return $mail->student->id === $student->id;
    });
});
```

---

## Testing Database

### In-Memory SQLite

Tests run with in-memory SQLite for speed:

```php
// config/database.php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => ':memory:',  // In-memory
],
```

### Traits for Database Tests

```php
// Refresh database before each test
use RefreshDatabase;

describe('StudentService', function () {
    use RefreshDatabase;  // Migrates fresh database before each test

    it('creates student', function () {
        $student = Student::factory()->create();
        expect(Student::count())->toBe(1);
    });

    it('starts with clean database', function () {
        expect(Student::count())->toBe(0);  // Previous test's data is gone
    });
});
```

### Transactions Instead of Full Refresh (Faster)

```php
use DatabaseTransactions;  // Rolls back changes (faster than refresh)

describe('StudentService', function () {
    use DatabaseTransactions;

    it('creates student', function () {
        $student = Student::factory()->create();
        expect(Student::count())->toBe(1);
    });

    it('has clean database', function () {
        expect(Student::count())->toBe(0);  // Previous test rolled back
    });
});
```

---

## Testing Livewire Components

### Testing RecordManager

```php
// tests/Feature/StudentManagerTest.php
use Livewire\Livewire;
use Modules\Student\Livewire\StudentManager;

it('displays students in table', function () {
    $students = Student::factory(5)->create();

    Livewire::test(StudentManager::class)
        ->assertSee($students[0]->name)
        ->assertSee($students[4]->name);
});

it('searches students', function () {
    Student::factory(5)->create();
    $john = Student::factory()->create(['name' => 'John Doe']);

    Livewire::test(StudentManager::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee($students[0]->name);
});

it('sorts by column', function () {
    Student::factory()->create(['name' => 'Zoe']);
    Student::factory()->create(['name' => 'Alice']);
    Student::factory()->create(['name' => 'Bob']);

    Livewire::test(StudentManager::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Alice', 'Bob', 'Zoe']);
});

it('creates student from form', function () {
    Livewire::test(StudentManager::class)
        ->callAction('create')
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'nis' => '123456789',
        ])
        ->call('saveRecord')
        ->assertDispatchedBrowserEvent('record-saved');

    expect(Student::where('email', 'john@example.com')->exists())->toBeTrue();
});
```

---

## Testing Policies

### Authorization Testing

```php
// tests/Unit/Policies/StudentPolicyTest.php
it('student can view own profile', function () {
    $student = Student::factory()->create();
    $policy = new StudentPolicy();

    expect($policy->view($student, $student))->toBeTrue();
});

it('student cannot view other profiles', function () {
    $student = Student::factory()->create();
    $other = Student::factory()->create();
    $policy = new StudentPolicy();

    expect($policy->view($student, $other))->toBeFalse();
});

it('admin can view all profiles', function () {
    $admin = User::factory()->admin()->create();
    $student = Student::factory()->create();
    $policy = new StudentPolicy();

    expect($policy->view($admin, $student))->toBeTrue();
});
```

---

## Testing HTTP Requests

### Feature Test Example

```php
// tests/Feature/StudentApiTest.php
it('returns student list', function () {
    $students = Student::factory(3)->create();

    $response = $this->get(route('api.students.index'));

    expect($response->status())->toBe(200);
    expect($response->json('data'))->toHaveCount(3);
});

it('requires authentication', function () {
    $response = $this->post(route('student.create'));

    expect($response->status())->toBe(401);
});

it('validates email format', function () {
    $response = $this->post(route('student.create'), [
        'name' => 'John',
        'email' => 'invalid-email', // Invalid format
    ]);

    expect($response)->toHaveValidationErrors('email');
});
```

---

## Coverage Requirements

**90%+ code coverage** is required. Check coverage:

```bash
composer test -- --coverage-html coverage/
```

### What Gets Counted?

- ✅ Classes and functions (both public and private)
- ✅ Conditional branches (if/else)
- ✅ Exception paths (throw statements)
- ✅ Edge cases

### What Doesn't Count?

- ❌ Configuration files
- ❌ Test files themselves
- ❌ Generated code (migrations, etc.)

### Improving Coverage

```php
// If coverage is low, add tests for:
- Edge cases (null, empty, very large)
- Error conditions (exceptions, validation)
- Different permission levels
- Different user roles
- Boundary conditions
```

---

## Continuous Integration (CI)

Tests run automatically on GitHub Actions:

```yaml
# .github/workflows/tests.yml
- name: Run tests
  run: composer test

- name: Upload coverage
  run: codecov
```

All PRs must pass tests to merge.

---

## Debugging Tests

### Print Debug Info

```php
it('creates student', function () {
    ray('Creating student...');
    $student = Student::factory()->create();
    ray($student->toArray());

    expect($student->id)->toBeUuid();
});
```

Run with output:

```bash
composer test -- --display-deprecations
```

### Run Test in Isolation

```bash
composer test -- --filter testCreatesStudent
```

### Stop on First Failure

```bash
composer test -- --stop-on-failure
```

---

## AppTest: Memory-Optimized Testing for Large Suites

### The Problem: Memory Leaks in Modular Testing

When testing large, modular systems with 29+ modules, traditional test runners accumulate memory:

```
Traditional Test Runner (PHPUnit/Pest)
┌─────────────────────────────────┐
│ Load Module 1                   │ 45 MB
│ + Load Module 2                 │ 45 MB
│ + Load Module 3                 │ 45 MB
│ ... (accumulated, never freed)  │
│                                 │
│ After 20 modules: 900 MB        │
│ After 29 modules: 1.3+ GB ❌    │ Memory exhausted!
└─────────────────────────────────┘
```

This causes:

- Memory exhaustion (fatal errors)
- Slow test execution (garbage collection thrashing)
- CI/CD failures on resource-constrained environments
- Timeout issues

### The Solution: AppTest (Isolated Process Testing)

**AppTest** (`php artisan app:test`) is an advanced test orchestrator that runs test segments in
**isolated processes**, preventing memory accumulation.

```
AppTest with Isolated Processes
┌──────────────────────────────────────────┐
│ Segment 1 (Module 1: Arch)               │
│ ├─ Start fresh process: 50 MB            │
│ ├─ Run tests                             │
│ └─ Exit process (memory freed) ✓        │
└──────────────────────────────────────────┘
│ Segment 2 (Module 1: Unit)               │
│ ├─ Start fresh process: 50 MB            │
│ ├─ Run tests                             │
│ └─ Exit process (memory freed) ✓        │
└──────────────────────────────────────────┘
│ Segment 3 (Module 2: Arch)               │
│ ├─ Start fresh process: 50 MB            │
│ ├─ Run tests                             │
│ └─ Exit process (memory freed) ✓        │
└──────────────────────────────────────────┘
...
Result: Constant memory usage (50 MB) ✓
```

### How AppTest Works

1. **Target Discovery** — Scans modules for test directories
2. **Segment Division** — Breaks tests into per-module, per-suite segments
3. **Isolated Execution** — Runs each segment in its own PHP process
4. **Progress Tracking** — Records results for resumption
5. **Comprehensive Reporting** — Displays matrix of results with timing

### Basic Usage

```bash
# Run all tests across all modules (isolated)
php artisan app:test

# Run tests for specific modules
php artisan app:test Student Internship Journal

# List identified test segments without running
php artisan app:test --list

# Display results from current/latest session
php artisan app:test --report
```

### Advanced Options

```bash
# Skip specific test suites
php artisan app:test --no-arch        # Skip architecture tests
php artisan app:test --no-unit        # Skip unit tests
php artisan app:test --no-feature     # Skip feature tests
php artisan app:test --no-browser     # Skip browser tests (default)

# Run only specific test suites
php artisan app:test --arch-only      # Only architecture tests
php artisan app:test --unit-only      # Only unit tests
php artisan app:test --feature-only   # Only feature tests
php artisan app:test --browser-only   # Only browser tests

# Include browser tests (Dusk)
php artisan app:test --with-browser   # Include UI tests

# Parallel execution within each module
php artisan app:test --parallel       # Run segments concurrently

# Stop on first failure
php artisan app:test --stop-on-failure

# Generate coverage report (enables PCOV automatically)
php artisan app:test --coverage       # Creates coverage/index.html

# Run only modules with uncommitted changes
php artisan app:test --dirty          # Git-aware testing

# Filter tests by name
php artisan app:test --filter="StudentService"

# Resume previous test session (skip passed segments)
php artisan app:test --continue
php artisan app:test --continue --report

# Use custom session ID
php artisan app:test --session=my_test_run

# Clear all persistent session data
php artisan app:test --clear-sessions
```

### Performance Example

**Before AppTest** (Traditional approach):

```
Running all tests: 6 minutes 45 seconds
Memory usage: Peak 1.2 GB (failures due to exhaustion)
```

**After AppTest** (Isolated processes):

```
Running all tests: 4 minutes 12 seconds
Memory usage: Constant 70-80 MB (no accumulation)
```

**Improvement**: ~38% faster, 93% less peak memory usage ✓

### Key Features

**1. Memory Isolation**

- Each test segment runs in its own process
- Memory freed when process exits
- No memory leaks or accumulation

**2. Intelligent Segmentation**

```
Modules: 29+
Test Suites per Module: 4 (Arch, Unit, Feature, Browser)
Total Segments: 116 (typical)
Parallel Capable: Yes
```

**3. Resumable Sessions**

```bash
# Session interrupted at segment 47/116?
php artisan app:test --continue

# Only runs segments 48-116, skips passed segments
# Results combined from session file
```

**4. Automatic PCOV/JIT Management**

```bash
php artisan app:test --coverage

# Automatically:
# 1. Disables JIT (incompatible with PCOV)
# 2. Enables PCOV extension
# 3. Generates coverage HTML
# 4. Re-enables JIT after coverage run
```

**5. Progress Tracking**

```
Segment (47/116): Student > Unit
  ✓ PASS (12.34s)

Segment (48/116): Student > Feature
  ✓ PASS (18.56s)

Segment (49/116): Internship > Arch
  ✓ PASS (8.92s)
```

### Real-World Examples

**Development Workflow** — Run tests for modules you changed:

```bash
# Test only modules with uncommitted changes
php artisan app:test --dirty

# Or specific modules
php artisan app:test Student Internship Journal
```

**CI/CD Pipeline** — Full suite with coverage:

```bash
# Run all tests, generate coverage, stop on failure
php artisan app:test --coverage --stop-on-failure

# If it fails, resume later
php artisan app:test --continue
```

**Performance Benchmarking** — Profile module performance:

```bash
# List all test segments (see timing)
php artisan app:test --list

# After running, view detailed breakdown
php artisan app:test --report
```

**Debugging** — Run tests for one module with filters:

```bash
# Test only StudentService
php artisan app:test Student --filter="StudentService"

# Stop on first failure
php artisan app:test Student --stop-on-failure
```

### Under the Hood: How Memory Isolation Works

```php
// Each segment execution:
// modules/Support/src/Testing/Support/TestExecutor.php

$process = new Process([
    PHP_BINARY,
    'vendor/bin/pest',
    $testPath, // e.g., modules/Student/tests/Unit
    '--parallel', // Optional
]);

$process->setTimeout(1200); // 20 minute timeout
$process->run(); // Execute in isolated process

// When process exits:
// - All loaded classes unloaded
// - All database connections closed
// - All file handles released
// - All memory freed
// ← Clean slate for next segment
```

### When to Use AppTest vs. Standard Composer Test

| Scenario                  | Tool                              | Reason                     |
| :------------------------ | :-------------------------------- | :------------------------- |
| Development (1-2 modules) | `composer test`                   | Fast feedback, simple      |
| Full suite testing        | `php artisan app:test`            | Memory safe, scalable      |
| CI/CD pipelines           | `php artisan app:test`            | Reliable, no memory spikes |
| Debugging one test        | `composer test -- --filter=X`     | Direct output, easy        |
| Checking specific module  | `php artisan app:test Module`     | Isolated, memory-safe      |
| Resumable test runs       | `php artisan app:test --continue` | Session persistence        |
| Coverage generation       | `php artisan app:test --coverage` | Automatic PCOV setup       |

### Troubleshooting AppTest

**Issue**: Tests timeout on slow systems

```bash
# Increase timeout (default 1200s = 20 min)
# Edit: modules/Support/src/Testing/Support/TestExecutor.php
protected const TIMEOUT = 1800;  // 30 minutes
```

**Issue**: PCOV not enabled for coverage

```bash
# Ensure PCOV extension is installed
php -m | grep pcov

# If missing:
pecl install pcov
# or via package manager:
apt-get install php-pcov
```

**Issue**: Memory still high

```bash
# Check for open files/connections
lsof -p $(pgrep php) | wc -l

# If high, services may not be properly disconnected
# Check: Database::disconnect() in test setUp/tearDown
```

---

## Quick Reference

| Task                        | Command                                          |
| :-------------------------- | :----------------------------------------------- |
| Run all tests (memory-safe) | `php artisan app:test`                           |
| Run all tests (traditional) | `composer test`                                  |
| Run specific modules        | `php artisan app:test Student Internship`        |
| List test segments          | `php artisan app:test --list`                    |
| Run with coverage           | `php artisan app:test --coverage`                |
| Resume interrupted session  | `php artisan app:test --continue`                |
| View session report         | `php artisan app:test --report`                  |
| Clean up sessions           | `php artisan app:test --clear-sessions`          |
| Run only unit tests         | `php artisan app:test --unit-only`               |
| Stop on first failure       | `php artisan app:test --stop-on-failure`         |
| Run in parallel             | `php artisan app:test --parallel`                |
| Filter by name              | `php artisan app:test --filter="StudentService"` |
| Git-aware testing           | `php artisan app:test --dirty`                   |

---

## Further Reading

- [Philosophy Guide](philosophy.md) — Test-driven development principles
- [Standards Guide](standards.md) — Code quality standards
- [Pest Documentation](https://pestphp.com) — Official Pest docs
- [PHPUnit Documentation](https://phpunit.de) — PHPUnit reference

---

_Testing is how we build confidence._ 🧪
