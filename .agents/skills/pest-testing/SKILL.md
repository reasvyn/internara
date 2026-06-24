---
name: pest-testing
description: SDLC Phase: TESTING. Test writing, editing, and fixing using Pest — feature tests, unit tests, architecture tests, Livewire component tests.
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
  - medialibrary-development
downstream:
  - feature-building
  - sync-docs
---

# Pest Testing Skill

## When to Activate

Apply this skill whenever writing, editing, or fixing tests. Activates for all testing tasks — feature tests, unit tests, and Livewire component tests using Pest.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — new code needing tests |
| | `code-refactoring` — refactored code needing characterization tests |
| | `livewire-development` — new components |
| | `medialibrary-development` — upload functionality |
| **This skill** | **TESTING** — produces test files |
| **Downstream (output)** | `feature-building` — tests integrated into feature completion |
| | `sync-docs` — documentation updated with test references |
| **Phase** | [Planning] → [Analysis] → [Design] → [Implementation] → Testing → [Maintenance] |

## Key References

- **Architecture (testing strategy)**: `docs/architecture.md#testing-strategy`
- **Testing Pattern**: `docs/architecture/testing-pattern.md`
- **Testing Guide**: `docs/infrastructure/testing.md`
- **BaseEntity**: `app/Core/Entities/BaseEntity.php`
- **BaseAction**: `app/Core/Actions/BaseAction.php`
- **BasePolicy**: `app/Core/Policies/BasePolicy.php`

## Module-First Test Structure

Tests mirror source structure exactly:

```
tests/Feature/{Module}/{SubModule}/{Name}Test.php   → Actions, Livewire (integration)
tests/Unit/{Module}/{SubModule}/{Name}Test.php      → Entities, Enums (pure unit)
tests/{Feature,Unit}/{Component}/{Name}Test.php     → Shared components (Data, Enums, Livewire)
tests/Arch/                                          → Architecture rules (expect/arch)
```

Create tests: `php artisan make:test --pest {Name}Test` (omit `Feature/` or `Unit/` prefix).

## Scope Isolation (Critical)

**Each Action, command, and component gets its own dedicated test file.** Do not combine multiple scopes into a single file (e.g., `ConsoleCommandsTest` grouping separate commands).

## Layer Testing Strategy

| Layer | Test Type | Database | Base Class |
|-------|-----------|----------|------------|
| Entity | Unit | No | Instantiate directly: `new Entity(...)` |
| Enum | Unit | No | Assert `label()`, transitions, terminals |
| DTO/Data | Unit | No | Constructor → `toArray()` |
| Policy | Unit | No | Mock user/model → assert gates |
| Command Action | Feature | Yes (`LazilyRefreshDatabase`) | Resolve from container, call `execute()` |
| Read Action | Feature | Yes | Resolve, call method, assert result |
| Process Action | Feature | Yes | Full workflow + partial failure scenarios |
| Livewire | Feature | Yes | `Livewire::test()` → interact → assert |
| Console Command | Feature | Yes | `$this->artisan()` → assert exit code |
| HTTP | Feature | Yes | `$this->get()` / `$this->post()` → assert status |

### Entity Tests (No Database)

```php
describe('Apprentice', function () {
    it('prevents login when locked', function () {
        $entity = new Apprentice(status: 'active', emailVerifiedAt: now(), setupRequired: false, lockedAt: now()->toDateTimeString());

        expect($entity->allowsLogin())->toBeFalse();
    });
});
```

### Action Tests (With Database)

```php
describe('CreateInternshipAction', function () {
    it('creates an internship with valid data', function () {
        $action = app(CreateInternshipAction::class);
        $data = new CreateInternshipData(name: 'Summer Program', ...);

        $internship = $action->execute($data);

        assertModelExists($internship);
        expect($internship->name)->toBe('Summer Program');
    });
});
```

## Performance Preferences

| Preference | Over |
|------------|------|
| `LazilyRefreshDatabase` | `RefreshDatabase` (skips replay if schema current) |
| `assertModelExists()` | `assertDatabaseHas()` (clearer intent) |
| Factory states and sequences | Manual model creation |
| Fakes **after** factory setup | Fakes before (UUID events must not be silenced) |
| `usingTestCase()` / `beforeEach()` | Repeated setup in each test |

## TDD Workflow

Follow the architecture's bottom-up dependency order:

1. **Enum** — define state machine, transitions (unit test)
2. **Entity** — define business rules (unit test, no DB)
3. **Command Action** — persistence, transactions (feature test)
4. **Read Action** — complex queries (feature test)
5. **Process Action** — multi-step orchestration (feature test)
6. **Livewire** — UI interactions (feature test)
7. **Policy** — authorization gates (unit test)
8. **Console Command** — CLI interactions (feature test)

---

## Livewire Component Testing

Use `Livewire::test('component.alias')` or `Livewire::test(Full\Class::class)`.

```php
use Livewire\Livewire;

describe('ProfileEditor', function () {
    it('updates the user name', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('user.profile.profile-editor')
            ->set('name', 'New Name')
            ->call('save')
            ->assertSet('name', 'New Name')
            ->assertDispatched('profile-updated')
            ->assertRedirect('/profile');
    });

    it('shows validation errors for empty name', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('user.profile.profile-editor')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    it('handles file upload', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        Livewire::actingAs($user)
            ->test('user.profile.profile-editor')
            ->set('photo', $file)
            ->call('upload')
            ->assertFileUploaded('photo');
    });
});
```

### Common Livewire Assertions

| Method | Purpose |
|--------|---------|
| `->assertSet('prop', $value)` | Component property equals value |
| `->assertNotSet('prop', $value)` | Component property differs |
| `->assertCount('items', 3)` | Array property has N items |
| `->assertSee('Hello')` | Rendered output contains text |
| `->assertDontSee('Goodbye')` | Rendered output excludes text |
| `->assertSeeHtml('<div>')` | Rendered output contains raw HTML |
| `->assertDispatched('event-name')` | Event was dispatched |
| `->assertNotDispatched('event-name')` | Event was not dispatched |
| `->assertDispatchedTo('channel', ...)` | Event dispatched to channel |
| `->assertHasErrors(['field' => ['rule']])` | Validation errors on property |
| `->assertNoRedirect()` | No redirect triggered |
| `->assertRedirect($uri)` | Redirected to URI |
| `->assertRedirectToRoute('name')` | Redirected to named route |
| `->assertUnauthorized()` | Authorization failed |
| `->assertForbidden()` | Gate `forbidden` response |
| `->assertFileUploaded('prop')` | File was uploaded to prop |
| `->assertReturn()` | Method returned the given value |
| `->assertViewIs('path.to.view')` | Specific view rendered |
| `->assertViewHas('key', $value)` | View data has key/value |

### File Upload Helpers

```php
UploadedFile::fake()->image('photo.jpg', 100, 100);
UploadedFile::fake()->create('document.pdf', 1024);
UploadedFile::fake()->create('data.csv', 512);
```

---

## Console Command Testing

Use `$this->artisan('command:name', ['arg' => 'val'])` and chain assertions.

```php
describe('SystemHealthCommand', function () {
    it('exits successfully when healthy', function () {
        $this->artisan('system:health')
            ->assertExitCode(0)
            ->expectsOutput('All systems operational');
    });

    it('accepts an argument and outputs it', function () {
        $this->artisan('system:health', ['--verbose' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('memory usage');
    });

    it('prompts for confirmation', function () {
        $this->artisan('system:cleanup')
            ->expectsQuestion('Confirm database cleanup?', 'yes')
            ->assertExitCode(0);
    });

    it('hides sensitive output when silent flag is set', function () {
        $this->artisan('admin:recover', ['--silent' => true])
            ->doesntExpectOutput('Super Admin Token')
            ->assertSuccessful();
    });
});
```

### Console Assertion Methods

| Method | Purpose |
|--------|---------|
| `->assertExitCode(int)` | Exact exit code |
| `->assertSuccessful()` | Exit code 0 |
| `->assertFailed()` | Non-zero exit code |
| `->expectsOutput(string)` | Output contains string |
| `->expectsOutputToContain(string)` | Output contains substring |
| `->doesntExpectOutput(string)` | Output does NOT contain string |
| `->expectsQuestion(ask, answer)` | Answer interactive question |
| `->expectsConfirmation(ask, default)` | Confirm yes/no |
| `->expectsChoice(ask, answer, options)` | Choose from list |
| `->expectsTable(headers, rows)` | Table rendered in output |

---

## Event Assertion Patterns

```php
use App\Program\Events\InternshipPublished;
use Illuminate\Support\Facades\Event;

describe('PublishInternshipAction', function () {
    it('dispatches InternshipPublished on success', function () {
        $internship = Internship::factory()->create(['status' => 'draft']);
        $action = app(PublishInternshipAction::class);

        Event::fake([InternshipPublished::class]);

        $action->execute($internship);

        Event::assertDispatched(InternshipPublished::class);
        Event::assertDispatchedTimes(InternshipPublished::class, 1);
    });

    it('dispatches with correct internship ID', function () {
        $internship = Internship::factory()->create();
        $action = app(PublishInternshipAction::class);

        Event::fake();

        $action->execute($internship);

        Event::assertDispatched(InternshipPublished::class, function ($event) use ($internship) {
            return $event->internship->is($internship);
        });
    });

    it('does not dispatch on failure', function () {
        $internship = Internship::factory()->create(['status' => 'published']);
        $action = app(PublishInternshipAction::class);

        Event::fake([InternshipPublished::class]);

        try {
            $action->execute($internship);
        } catch (\Throwable) {}

        Event::assertNotDispatched(InternshipPublished::class);
    });
});
```

### Event Assertion Methods

| Method | Purpose |
|--------|---------|
| `Event::assertDispatched(Class)` | Event was dispatched |
| `Event::assertDispatchedTimes(Class, n)` | Dispatched exactly N times |
| `Event::assertNotDispatched(Class)` | Event was NOT dispatched |
| `Event::assertDispatched(fn ($e) => ...)` | Callback asserts payload |
| `Event::assertNothingDispatched()` | No events dispatched at all |

**Always position `Event::fake()` after factory setup** to avoid silencing UUID-creation events.

---

## Notification Assertion Patterns

```php
use App\User\Notifications\InternshipAssigned;
use Illuminate\Support\Facades\Notification;

describe('NotifyStudentAction', function () {
    it('sends notification to the student', function () {
        $student = User::factory()->create();
        $action = app(NotifyStudentAction::class);

        Notification::fake();

        $action->execute($student);

        Notification::assertSentTo(
            [$student], InternshipAssigned::class
        );
    });

    it('sends exactly one notification per student', function () {
        $student = User::factory()->create();

        Notification::fake();

        // trigger the action

        Notification::assertSentTo(
            [$student], InternshipAssigned::class
        );
        Notification::assertCount(1);
    });

    it('sends nothing when no eligible students', function () {
        Notification::fake();

        // trigger action with no matches

        Notification::assertNothingSent();
    });
});
```

### Notification Assertion Methods

| Method | Purpose |
|--------|---------|
| `assertSentTo($notifiables, $class)` | Sent to specific user(s) |
| `assertSentTo($notifiables, $class, callback)` | Sent with payload assertion |
| `assertNotSentTo($notifiables, $class)` | NOT sent to specific users |
| `assertCount($n)` | Exactly N notifications sent |
| `assertNothingSent()` | No notifications dispatched |
| `assertTimesSent($n, $class)` | Class sent N times total |

---

## Mail Assertion Patterns

```php
use App\Auth\Notifications\WelcomeMail;
use Illuminate\Support\Facades\Mail;

describe('SendWelcomeMailAction', function () {
    it('sends welcome email to new user', function () {
        $user = User::factory()->create();
        $action = app(SendWelcomeMailAction::class);

        Mail::fake();

        $action->execute($user);

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->user->is($user);
        });
    });

    it('queues the welcome email for delivery', function () {
        $user = User::factory()->create();

        Mail::fake();

        // trigger action

        Mail::assertQueued(WelcomeMail::class, 1);
    });

    it('does not send for unverified email', function () {
        $user = User::factory()->unverified()->create();

        Mail::fake();

        // trigger action that should skip

        Mail::assertNotSent(WelcomeMail::class);
    });
});
```

### Mail Assertion Methods

| Method | Purpose |
|--------|---------|
| `Mail::assertSent($class)` | Mail sent (any recipient) |
| `Mail::assertSent($class, callback)` | Mail sent with payload check |
| `Mail::assertNotSent($class)` | Mail NOT sent |
| `Mail::assertQueued($class, $times)` | Mail queued (not yet sent) |
| `Mail::assertNotQueued($class)` | Mail NOT queued |
| `Mail::assertNothingSent()` | No mail sent at all |
| `Mail::assertNothingQueued()` | Nothing enqueued |

---

## Queue Assertion Patterns

```php
use App\Jobs\ProcessCertificateGeneration;
use Illuminate\Support\Facades\Queue;

describe('RequestCertificateAction', function () {
    it('pushes generation job onto queue', function () {
        $certificate = Certificate::factory()->create();

        Queue::fake();

        $action = app(RequestCertificateAction::class);
        $action->execute($certificate);

        Queue::assertPushed(ProcessCertificateGeneration::class);
    });

    it('pushes job with correct certificate ID', function () {
        $certificate = Certificate::factory()->create();

        Queue::fake();

        $action = app(RequestCertificateAction::class);
        $action->execute($certificate);

        Queue::assertPushed(ProcessCertificateGeneration::class, function ($job) use ($certificate) {
            return $job->certificate->is($certificate);
        });
    });

    it('pushes onto the correct queue', function () {
        Queue::fake();

        // trigger action

        Queue::assertPushedOn('high', ProcessCertificateGeneration::class);
    });

    it('does not push when generation is disabled', function () {
        Queue::fake();

        // trigger action that should skip

        Queue::assertNotPushed(ProcessCertificateGeneration::class);
        Queue::assertNothingPushed();
    });
});
```

### Queue Assertion Methods

| Method | Purpose |
|--------|---------|
| `Queue::assertPushed($class)` | Job pushed (any count) |
| `Queue::assertPushed($class, callback)` | Job pushed with payload check |
| `Queue::assertPushedOn($queue, $class)` | Pushed to specific queue name |
| `Queue::assertPushedTimes($class, $n)` | Pushed exactly N times |
| `Queue::assertNotPushed($class)` | Job NOT pushed |
| `Queue::assertNothingPushed()` | No jobs pushed at all |

---

## Permission/Role Testing

Test authorization gates with `actingAs()` and permission assignment.

```php
describe('InternshipPolicy', function () {
    it('allows admin to publish any internship', function () {
        $admin = User::factory()->create()->assignRole('admin');
        $internship = Internship::factory()->create();

        $this->actingAs($admin);
        expect(app(InternshipPolicy::class)->publish($admin, $internship))->toBeTrue();
    });

    it('denies student to publish', function () {
        $student = User::factory()->create()->assignRole('student');
        $internship = Internship::factory()->create();

        expect(app(InternshipPolicy::class)->publish($student, $internship))->toBeFalse();
    });

    it('checks ownership for mentor view', function () {
        $mentor = User::factory()->create()->assignRole('mentor');
        $mentorInternship = Internship::factory()->create(['mentor_id' => $mentor->id]);
        $otherInternship = Internship::factory()->create();

        expect(app(InternshipPolicy::class)->view($mentor, $mentorInternship))->toBeTrue();
        expect(app(InternshipPolicy::class)->view($mentor, $otherInternship))->toBeFalse();
    });
});
```

### Testing Livewire Authorization

```php
it('denies unauthorized users access to admin panel', function () {
    $student = User::factory()->create()->assignRole('student');

    Livewire::actingAs($student)
        ->test('sysadmin.dashboard')
        ->assertUnauthorized();  // 403 from policy
});
```

### Permission Assertion Helpers

```php
// Assert user has a specific permission
expect($user->can('publish-internship'))->toBeTrue();

// Assert user has a role
expect($user->hasRole('admin'))->toBeTrue();

// Assert gate resolves correctly
expect(Gate::forUser($user)->allows('publish', $internship))->toBeTrue();
```

---

## Exception Testing

```php
use App\Core\Exceptions\Action\ValidationFailedException;
use App\Core\Exceptions\Module\RejectedException;

describe('ApproveReportAction', function () {
    it('throws when report is already approved', function () {
        $report = Report::factory()->approved()->create();
        $action = app(ApproveReportAction::class);

        expect(fn () => $action->execute($report))
            ->toThrow(RejectedException::class, 'Report is already approved');
    });

    it('throws validation exception for missing score', function () {
        $report = Report::factory()->create();
        $action = app(ApproveReportAction::class);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Score is required');

        $action->execute($report, new ApproveReportData(score: null));
    });
});
```

### Exception Test Patterns

| Pattern | When to Use |
|---------|-------------|
| `expect(fn () => ...)->toThrow(Class)` | Simple inline exception (Pest fluent) |
| `expect(fn () => ...)->toThrow(Class, 'msg')` | Exception class + message |
| `$this->expectException(Class)` | Traditional PHPUnit style |
| `$this->expectExceptionMessage('msg')` | Exception message check |
| `$this->expectExceptionCode(422)` | Exception code check |
| `->assertThrows(fn () => ...)` | Livewire assertion |
| `->assertDoesntThrow(fn () => ...)` | Livewire no-exception assertion |

### Testing Exception in Livewire

```php
it('handles invalid data gracefully', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('enrollment.register')
        ->set('academicYearId', 'invalid')
        ->call('submit')
        ->assertHasErrors(['academicYearId']);
});
```

---

## Database Assertions

```php
describe('ApproveRegistrationAction', function () {
    it('persists the approved state', function () {
        $registration = Registration::factory()->pending()->create();
        $action = app(ApproveRegistrationAction::class);

        $result = $action->execute($registration);

        assertModelExists($result);
        expect($result->fresh()->status->value)->toBe('approved');

        assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'approved',
        ]);
    });

    it('soft deletes related placement', function () {
        $placement = Placement::factory()->create();
        $action = app(RemovePlacementAction::class);

        $action->execute($placement);

        assertSoftDeleted($placement);
        expect($placement->fresh()->trashed())->toBeTrue();
    });

    it('creates pivot records', function () {
        $internship = Internship::factory()->create();
        $student = User::factory()->create();

        // action executes

        assertDatabaseHas('internship_user', [
            'internship_id' => $internship->id,
            'user_id' => $student->id,
        ]);
    });
});
```

### Database Assertion Methods

| Method | Purpose |
|--------|---------|
| `assertModelExists($model)` | Model exists in DB (loads it) |
| `assertModelMissing($model)` | Model does NOT exist in DB |
| `assertDatabaseHas($table, $data)` | Row exists with given data |
| `assertDatabaseMissing($table, $data)` | Row does NOT exist |
| `assertSoftDeleted($model)` | Model is soft-deleted |
| `assertCount($n, $collection)` | Collection has N items |
| `expect(Model::count())->toBe(5)` | Total table row count |

**Prefer `assertModelExists()` over `assertDatabaseHas()`** — it loads the model so you can chain `fresh()` assertions.

---

## HTTP Testing

Use when testing controllers or API routes directly.

```php
describe('RegistrationController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('returns a successful response', function () {
        $this->get(route('registration.index'))
            ->assertOk()
            ->assertSee('Your Registrations');
    });

    it('stores a new registration', function () {
        $data = Registration::factory()->raw();

        $this->post(route('registration.store'), $data)
            ->assertStatus(422); // validation expected
    });

    it('redirects after successful create', function () {
        $internship = Internship::factory()->create();

        $this->post(route('registration.store'), [
            'internship_id' => $internship->id,
            'reason' => 'Interested in this program',
        ])->assertRedirect(route('registration.index'));
    });

    it('returns JSON for API requests', function () {
        $this->getJson(route('api.internships'))
            ->assertOk()
            ->assertJson([
                'data' => [],
            ])
            ->assertJsonFragment(['status' => 'published'])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'status'],
                ],
            ]);
    });

    it('validates required fields', function () {
        $this->post(route('registration.store'), [])
            ->assertSessionHasErrors(['internship_id', 'reason']);
    });
});
```

### HTTP Assertion Methods

| Method | Purpose |
|--------|---------|
| `->assertOk()` | Status 200 |
| `->assertStatus(201)` | Any specific status |
| `->assertCreated()` | Status 201 |
| `->assertNoContent(204)` | Status 204 |
| `->assertRedirect($uri)` | Redirect to URI |
| `->assertRedirectToRoute('name')` | Redirect to named route |
| `->assertSessionHas('key')` | Session has key |
| `->assertSessionHasErrors(['fields'])` | Validation errors in session |
| `->assertSessionHasNoErrors()` | No validation errors |
| `->assertJson($exact)` | Exact JSON match |
| `->assertJsonFragment(['key' => 'val'])` | Partial JSON match |
| `->assertJsonStructure(['key' => ['*' => [...]])` | JSON structure validation |
| `->assertJsonCount(3, 'data')` | JSON array count |
| `->assertJsonPath('data.0.id', $id)` | JSON path value |
| `->assertForbidden()` | Status 403 |
| `->assertUnauthorized()` | Status 401 |
| `->assertNotFound()` | Status 404 |
| `->assertValid()` | No validation errors (Form Request) |
| `->assertInvalid(['field'])` | Validation errors (Form Request) |

---

## State/Sequence Testing

Use factory sequences and states to test state transitions.

```php
describe('InternshipStatusTransitions', function () {
    it('transitions from draft to published', function () {
        $internship = Internship::factory()->create(['status' => 'draft']);

        app(PublishInternshipAction::class)->execute($internship);

        expect($internship->fresh()->status->value)->toBe('published');
    });

    it('rejects invalid transition from active to draft', function () {
        $internship = Internship::factory()->create(['status' => 'active']);

        expect(fn () => app(DraftInternshipAction::class)->execute($internship))
            ->toThrow(RejectedException::class);
    });
});
```

### Factory Sequences for Chained States

```php
it('processes registrations in sequence', function () {
    $registrations = Registration::factory()
        ->count(3)
        ->sequence(
            ['status' => 'pending'],
            ['status' => 'approved'],
            ['status' => 'rejected'],
        )
        ->create();

    expect($registrations[0]->status->value)->toBe('pending');
    expect($registrations[1]->status->value)->toBe('approved');
    expect($registrations[2]->status->value)->toBe('rejected');
});

it('generates sequential dates', function () {
    $logs = Logbook::factory()
        ->count(3)
        ->sequence(fn ($sequence) => [
            'date' => now()->addDays($sequence->index),
        ])
        ->create();

    expect($logs[1]->date->diffInDays($logs[0]->date))->toBe(1);
});
```

### Factory States

```php
// Define states in the factory:
public function approved(): static
{
    return $this->state(['status' => 'approved']);
}

// Use in tests:
$report = Report::factory()->approved()->create();
```

---

## Mocking Boundaries

```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

describe('MediaUploadAction', function () {
    it('stores file in S3', function () {
        Storage::fake('s3');

        $action = app(UploadDocumentAction::class);
        $file = UploadedFile::fake()->create('doc.pdf', 1024);

        $path = $action->execute($file);

        Storage::disk('s3')->assertExists($path);
    });
});

describe('FetchCompanyDataAction', function () {
    it('fetches and parses external company data', function () {
        Http::fake([
            'api.companyregistry.com/*' => Http::response([
                'name' => 'PT Example',
                'status' => 'active',
            ], 200),
        ]);

        $action = app(FetchCompanyDataAction::class);
        $result = $action->execute('PT Example');

        expect($result['name'])->toBe('PT Example');
    });

    it('handles 404 from external API', function () {
        Http::fake([
            'api.companyregistry.com/*' => Http::response([], 404),
        ]);

        $result = app(FetchCompanyDataAction::class)->execute('Unknown');

        expect($result)->toBeNull();
    });
});

describe('BatchCertificateGeneration', function () {
    it('dispatches batch job', function () {
        Bus::fake();

        $action = app(GenerateBatchCertificatesAction::class);
        $action->execute($certificates);

        Bus::assertDispatched(GenerateCertificateBatch::class);
    });
});

describe('DateSensitiveFeature', function () {
    it('behaves correctly on a specific date', function () {
        Date::setTestNow('2026-06-01');

        // test logic dependent on current date

        expect(now()->format('Y-m-d'))->toBe('2026-06-01');
    });
});

describe('ConfigToggle', function () {
    it('skips feature when disabled in config', function () {
        Config::set('features.certificate_generation', false);

        // test that feature is skipped

        expect(Config::get('features.certificate_generation'))->toBeFalse();
    });
});
```

### Fake/Mock Quick Reference

| Boundary | Fake Method | Key Assertion |
|----------|-------------|---------------|
| Storage | `Storage::fake('disk')` | `assertExists()`, `assertMissing()` |
| HTTP | `Http::fake([...])` | `assertSent()`, `assertNotSent()` |
| Queue | `Queue::fake()` | `assertPushed()`, `assertNotPushed()` |
| Bus | `Bus::fake()` | `assertDispatched()`, `assertNotDispatched()` |
| Mail | `Mail::fake()` | `assertSent()`, `assertQueued()` |
| Notification | `Notification::fake()` | `assertSentTo()`, `assertNothingSent()` |
| Event | `Event::fake([classes])` | `assertDispatched()`, `assertNotDispatched()` |
| Date | `Date::setTestNow($date)` | Assert time-dependent behavior |
| Config | `Config::set('key', $val)` | Assert config-dependent behavior |
| Cache | `Cache::shouldReceive()` | Mock cache expectations |

---

## Data Providers / Datasets

```php
// Labeled dataset
$statuses = ['pending', 'approved', 'rejected', 'cancelled'];

test('status has a readable label')
    ->with($statuses)
    ->expect(fn ($status) => InternshipStatus::tryFrom($status))
    ->each->toHaveProperty('label');

// Named datasets with Pest
dataset('transition_pairs', [
    'draft to published' => ['draft', 'published', true],
    'published to active' => ['published', 'active', true],
    'active to draft' => ['active', 'draft', false],
]);

it('validates transition rules', function (string $from, string $to, bool $expected) {
    $fromStatus = InternshipStatus::tryFrom($from);
    $toStatus = InternshipStatus::tryFrom($to);

    expect($fromStatus->canTransitionTo($toStatus))->toBe($expected);
})->with('transition_pairs');

// Inline with() for simple inputs
test('rejects invalid email formats')
    ->with(['not-an-email', '', '@domain.com', 'user@'])
    ->expect(fn ($email) => app(ValidationService::class)->validateEmail($email))
    ->toBeFalse();
```

### Dataset Tips

- Use `dataset()` to define reusable named datasets
- Use `->with()` to pass arrays/lists of inputs directly
- Labeled datasets (`'name' => ['arg1', 'arg2']`) produce clear test output
- Use `->with('dataset_name')` to reference a named dataset
- Nest datasets: `->with('dates', 'statuses')` for Cartesian product

---

## Architecture Testing

Structural rules enforced via Pest's `arch()` expectations.

```php
describe('Architecture', function () {
    it('all actions extend BaseCommandAction or BaseReadAction or BaseProcessAction', function () {
        expect('App\*\Actions')
            ->not->toExtendNothing()
            ->and('App\*\Actions')
            ->toExtend('App\Core\Actions\BaseAction')
            ->orToExtend('App\Core\Actions\BaseCommandAction')
            ->orToExtend('App\Core\Actions\BaseReadAction')
            ->orToExtend('App\Core\Actions\BaseProcessAction');
    });

    it('all entities are final readonly classes', function () {
        expect('App\*\Entities')
            ->toBeFinal()
            ->toBeReadonly();
    });

    it('no debug calls in application code', function () {
        expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die'])
            ->not->toBeUsed()
            ->ignoring('tests');
    });

    it('policies extend BasePolicy', function () {
        expect('App\*\Policies')
            ->toExtend('App\Core\Policies\BasePolicy');
    });

    it('commands extend BaseCommandAction and are in correct directory', function () {
        expect('App\*\Actions\*Command*')
            ->toExtend('App\Core\Actions\BaseCommandAction');
    });

    it('Livewire components do not call Eloquent create/update/delete directly', function () {
        expect('App\*\Livewire')
            ->not->toUse('Illuminate\Database\Eloquent\Model')
            ->ignoring('App\Core\Livewire');
    });
});
```

### Architecture Test Methods

| Method | Purpose |
|--------|---------|
| `->toExtend('Class')` | Class extends given base class |
| `->not->toExtendNothing()` | Class must extend something |
| `->toBeFinal()` | Class is final |
| `->toBeReadonly()` | Class is readonly |
| `->toImplement('Interface')` | Class implements interface |
| `->toUse('Trait')` | Class uses given trait |
| `->not->toBeUsed()` | No code uses this thing |
| `->ignoring('path')` | Exclude path from assertion |
| `->toHaveMethod('methodName')` | Class has a method |
| `->toHaveProperty('prop')` | Class has a property |

---

## Coverage Analysis

```bash
# Run full coverage
composer run test:coverage          # full suite
composer run coverage               # pcov-based report

# Run coverage for specific tier
composer run coverage -- tests/Unit           # unit tests only
composer run coverage -- tests/Feature        # feature tests only

# Run coverage for a specific module
composer run coverage -- tests/Feature/User

# Run coverage for a specific test
composer run coverage -- --filter=CreateInternshipAction

# HTML report location
open storage/coverage/html/index.html
```

### Coverage Configuration (`phpunit.coverage.xml`)

- Minimum coverage threshold: **80%** (enforced in CI)
- paths: `app/` (all source), `tests/` (test files excluded)
- Uses `pcov` driver for fast loop coverage
- HTML + Clover output formats

### Writing Tests for Uncovered Lines

1. Run coverage and open HTML report
2. Look for red (uncovered) lines or methods
3. Write a test that exercises that path
4. Confirm coverage increases

```bash
# Check per-file coverage
composer run coverage -- --coverage-php=/tmp/coverage.cov
php coverage-checker.php --min=80 /tmp/coverage.cov
```

---

## Common Testing Mistakes

| Mistake | Why It's Wrong | Correct Approach |
|---------|----------------|------------------|
| `RefreshDatabase` instead of `LazilyRefreshDatabase` | Slows down entire suite, even for tests that don't touch DB | Use `LazilyRefreshDatabase` on all feature tests |
| `Event::fake()` before factories | Silences UUID-creation events, breaks model creation | Move `Event::fake()` **after** factory setup |
| `assertDatabaseHas()` over `assertModelExists()` | String-based assertion, can't inspect loaded model | Use `assertModelExists()` + `->fresh()` |
| Grouping multiple scopes in one file | Violates scope isolation, makes tests harder to maintain | Each Action/command/component gets its own file |
| Mocking Eloquent models | Fragile tests that break on schema changes | Use real DB + factories in feature tests |
| Testing framework internals (UUIDs, pagination) | Wastes time testing Laravel's own functionality | Only test **your** business logic |
| Using `dd/dump/ray` in tests | Debug calls break CI and confuse output | Remove before committing — check with Pint |
| Action tests calling Livewire methods | Introduces UI dependency to business logic tests | Test Action in isolation; test Livewire separately |
| Skipping edge cases / validation errors | Leaves code paths unverified | Test every validation constraint, not just happy path |
| `$this->actingAs()` before `$this->get()` | Must actingAs before making the request | `actingAs($user)->get('/route')` is correct |
| Missing state transition coverage | State machines are a common source of bugs | Test every valid + invalid transition pair |
| `php artisan test` without `--compact` | Too much output in dev | Use `--compact` for focused feedback |
| Not using datasets for repetitive tests | Duplicated test code | Extract to `dataset()` or `->with()` |

---

## Verification

- [ ] Tests in correct directory (Feature vs Unit, right Module/SubModule)?
- [ ] Entity tests avoid `RefreshDatabase` entirely?
- [ ] Action tests use `LazilyRefreshDatabase`?
- [ ] `assertModelExists()` preferred over `assertDatabaseHas()`?
- [ ] `Event::fake()` and `Http::fake()` positioned **after** factory setup?
- [ ] Each Action/component has its own dedicated test file?
- [ ] Action triad type correct (Command, Read, Process)?
- [ ] Livewire tests use `Livewire::test()` with `actingAs()` where needed?
- [ ] Console commands test exit codes AND output expectations?
- [ ] Events asserted with `assertDispatched()` + payload callback?
- [ ] Notifications and mail faked with `assertSentTo()` / `assertSent()`?
- [ ] Queue jobs tested with `assertPushed()` / `assertPushedOn()`?
- [ ] Policy tests cover both allowed and denied scenarios?
- [ ] Exception tests cover `ValidationFailedException`, `RejectedException`, domain errors?
- [ ] HTTP tests assert status, redirect, session, and JSON structure?
- [ ] Factory sequences used for state transition tests?
- [ ] `Storage::fake()`, `Http::fake()`, `Date::setTestNow()` used instead of real I/O?
- [ ] Datasets used for repetitive data-driven tests?
- [ ] Architecture tests enforce base class extensions and naming rules?
- [ ] Coverage at or above 80% threshold?
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`) in tests?
- [ ] `declare(strict_types=1)` present at top of test file?
- [ ] Test passes: `php artisan test --compact --filter={TestName}`?
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`?
