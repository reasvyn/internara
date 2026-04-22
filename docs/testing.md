# 🧪 Testing Guide

Comprehensive guide to Internara's **Test-Driven Development (TDD)** practices, testing framework (Pest), and quality assurance.

---

## Overview

Internara requires **90%+ code coverage** and follows a **TDD-first approach**. Tests are not written after code—they drive implementation.

```
Test-Driven Development (TDD) Cycle

1. RED     Write test (fails)
2. GREEN   Write minimal code (passes)
3. REFACTOR Improve code quality
4. REPEAT  Until feature complete
```

---

## Testing Framework: Pest

**Pest** is a modern testing framework for PHP, built on top of PHPUnit. It's more readable and enjoyable than traditional PHPUnit.

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

Internara follows the **test pyramid** principle: many unit tests, fewer feature tests, minimal UI tests.

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
    
    expect(fn () => $service->create([]))
        ->toThrow(ValidationException::class);
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
        $browser->loginAs($user)
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
            
            expect(fn () => $service->create([
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ]))->toThrow(ValidationException::class);
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
    expect(fn () => app(StudentService::class)->create([]))
        ->toThrow(ValidationException::class);
});

it('handles null input', function () {
    expect(fn () => app(StudentService::class)->findByEmail(null))
        ->toThrow(TypeError::class);
});

it('handles very long strings', function () {
    $longName = str_repeat('a', 1000);
    
    expect(fn () => app(StudentService::class)->create([
        'name' => $longName,
        'email' => 'test@example.com',
    ]))->toThrow(ValidationException::class);
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
        'email' => 'invalid-email',  // Invalid format
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

## Quick Reference

| Task | Command |
| :--- | :--- |
| Run all tests | `composer test` |
| Run unit tests only | `composer test -- --testsuite=Unit` |
| Run feature tests | `composer test -- --testsuite=Feature` |
| Run architecture tests | `composer test -- --testsuite=Arch` |
| Run specific test file | `composer test -- tests/Unit/StudentServiceTest.php` |
| Run with coverage | `composer test -- --coverage-html coverage/` |
| Watch mode | `composer test -- --watch` |
| Stop on failure | `composer test -- --stop-on-failure` |

---

## Further Reading

- [Philosophy Guide](philosophy.md) — Test-driven development principles
- [Standards Guide](standards.md) — Code quality standards
- [Pest Documentation](https://pestphp.com) — Official Pest docs
- [PHPUnit Documentation](https://phpunit.de) — PHPUnit reference

---

*Testing is how we build confidence.* 🧪
