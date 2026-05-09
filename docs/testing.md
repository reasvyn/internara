# Testing

## Framework

Pest 4 with `LazilyRefreshDatabase` for feature tests. PHPStan level 8 for static analysis. Laravel Pint for code style.

## Structure

```
tests/
├── Arch/          # Architecture enforcement
├── Unit/          # Isolated logic (no DB by default)
│   ├── Casts/
│   ├── Entities/  # Pure PHP domain objects — no database
│   ├── Exceptions/
│   ├── Models/    # Eloquent model behavior
│   ├── Services/
│   └── Support/
└── Feature/       # End-to-end workflows
    ├── AdminRecovery/
    ├── AppInstaller/
    ├── AppSettings/
    ├── UserManager/
    └── ...        # Grouped by domain
```

### Suite Configuration (`tests/Pest.php`)

- **Feature** — `TestCase` + `LazilyRefreshDatabase` (DB refreshed per test)
- **Unit** — `TestCase` only (individual tests can opt in with `uses(RefreshDatabase::class)`)
- **Arch** — no Pest configuration, runs standalone

### Base TestCase

`tests/TestCase.php` sets up two things in `setUp()`:

1. Creates `storage/app/.installed` lock file — bypasses the setup wizard during tests
2. Registers `Gate::before` — grants all permissions to `super_admin` role users

## Commands

```bash
composer test              # Clear cache + all tests
composer test:coverage     # 80% minimum coverage enforced
composer test:arch         # Architecture tests only
composer test:feature      # Feature tests only
composer test:unit         # Unit tests only
composer quality           # lint + analyse + arch
composer quality:full      # format + strict analyse + coverage
```

## Conventions

### File Structure

- `declare(strict_types=1)` at the top of every test file
- Test files follow the pattern: `tests/{Suite}/{Domain}/{Name}Test.php`
- Feature tests are grouped by domain context (e.g. `AppSettings/`, `AdminRecovery/`, `UserManager/`)

### Architecture Tests

Use Pest's `arch()` function to enforce structural invariants:

```php
arch('all domain models must extend BaseModel')
    ->expect([Assessment::class, Assignment::class, ...])
    ->toExtend(BaseModel::class);

arch('all models must use HasUuids trait')
    ->expect([Assessment::class, ...])
    ->toUseTraits([HasUuids::class]);
```

### Unit Tests

**Model tests** — create via factory, chain assertions with `and()`:

```php
it('can be created with factory', function () {
    $user = UserFactory::new()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBeUuid();
});
```

**Entity tests** — instantiate directly, no database needed:

```php
it('detects suspended status', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->isSuspended())->toBeTrue();
});
```

**Support tests** — use `describe()` for grouping, `beforeEach` for setup:

```php
describe('resolution chain', function () {
    beforeEach(function () {
        Settings::clearOverrides();
        Cache::clear();
    });

    it('resolves runtime overrides first', function () {
        Settings::override(['theme' => 'dark']);
        expect(Settings::get('theme'))->toBe('dark');
    });
});
```

### Feature Tests

**Action tests** — resolve actions from the container:

```php
it('creates a new admin user', function () {
    $user = app(RecoverAdminAccessAction::class)->execute(
        email: 'admin@internara.test',
        password: 'secure-password',
    );

    expect($user)->toBeInstanceOf(User::class);
});
```

**Livewire tests** — use `Livewire::test(Component::class)`:

```php
it('renders the user manager page', function () {
    Livewire::test(UserManager::class)
        ->assertSuccessful()
        ->assertSet('search', '');
});

it('filters users by name', function () {
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    Livewire::test(UserManager::class)
        ->set('search', 'Alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});
```

**Behavior grouping** — use `describe()` to organize related scenarios:

```php
describe('create mode', function () {
    it('creates a new admin user', function () { ... });
    it('assigns the PROTECTED status', function () { ... });
});

describe('reset mode', function () {
    it('resets the password', function () { ... });
});
```

**Shared setup** — use `beforeEach()` for common test prerequisites:

```php
beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});
```

### Factories

Every model has a corresponding factory in `database/factories/`. Use `fake()` or `$this->faker` for test data.

**UserFactory states:**

| State | Purpose |
|---|---|
| `->requiresSetup()` | Sets `setup_required = true` |
| `->locked($reason)` | Sets `locked_at` and `locked_reason` |
| `->unverified()` | Sets `email_verified_at = null` |
| `->withPassword($pw)` | Hashes and sets a custom password |

Nested factory creation is supported (e.g. `InternshipFactory` automatically creates an `AcademicYear`).

### Seeders

`DatabaseSeeder` calls only:

1. `RolePermissionSeeder` — creates roles and permissions
2. `AppSettingSeeder` — seeds default application settings

### Assertions Quick Reference

| Pattern | Use for |
|---|---|
| `expect($x)->toBeInstanceOf()` | Type checks |
| `expect($x)->toBeTrue()/toBeFalse()` | Boolean assertions |
| `expect($x)->and($y)` | Chained assertions |
| `expect($x)->toBeUuid()` | UUID format validation |
| `expect($x)->toHaveCount()` | Collection size |
| `expect($x)->toHaveKey()` | Array key existence |
| `assertDatabaseHas()` | Database state checks |
| `Notification::assertSentTo()` | Notification delivery |
| `Notification::assertNothingSent()` | No notifications sent |
| `Livewire::test()` | Component testing |

### Test Environment

Configured in `phpunit.xml`:

| Setting | Value |
|---|---|
| Database | SQLite `:memory:` |
| Cache | `array` |
| Queue | `sync` |
| Session | `array` |
| Mail | `array` |
| Bcrypt rounds | 4 |
| Pulse/Telescope/Nightwatch | Disabled |