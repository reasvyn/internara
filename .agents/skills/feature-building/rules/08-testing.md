# Testing

## Framework

Pest v4. Feature tests use `LazilyRefreshDatabase`. PHPStan level 8 for static analysis. Laravel Pint for code style.

## File Structure

```
tests/{Suite}/{Domain}/{Name}Test.php
```

- `tests/Feature/{Domain}/{Name}Test.php` — integration tests
- `tests/Unit/{Layer}/{Domain}/{Name}Test.php` — isolated logic
- `tests/Arch/{Name}ArchTest.php` — structure enforcement

## Test Patterns

```php
declare(strict_types=1);

use function Pest\Laravel\artisan;

describe('CreateUserAction', function () {
    it('creates a user with minimal data', function () {
        $user = app(CreateUserAction::class)->execute(
            name: 'John',
            email: 'john@example.com',
        );

        expect($user)->toBeInstanceOf(User::class);
    });
});
```

## Entity Testing (No DB)

```php
it('suspended user cannot log in', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
    );

    expect($entity->isSuspended())->toBeTrue();
});
```

## Livewire Testing

```php
Livewire::test(UserManager::class)
    ->assertSuccessful()
    ->set('search', 'Alice')
    ->assertSee('Alice')
    ->assertDontSee('Bob');
```

## Commands

```bash
composer test              # Clear cache + run all tests
composer test:feature      # Feature tests only
composer test:unit         # Unit tests only
composer test:arch         # Architecture tests only
```

## Base TestCase

`tests/TestCase.php` creates a Setup record with `is_installed = true` and registers `Gate::before` for super_admin bypass.

## Conventions

- `declare(strict_types=1)` at the top of every test file
- Factories for model creation with custom states
- Feature tests grouped by domain context
- Architecture tests enforce layer separation rules
