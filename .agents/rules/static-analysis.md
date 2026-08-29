# Rules: Static Analysis — PHPStan & Larastan

> Source: https://phpstan.org/, https://larastan.io/
> Versions: PHPStan 2.x, Larastan 3.x
> Applicability: All PHP/Laravel projects

## Overview

Static analysis finds bugs without running the code. PHPStan is the industry standard for
PHP; Larastan extends PHPStan with Laravel-specific rules.

## PHPStan Levels

| Level | Description | False Positives |
|-------|-------------|-----------------|
| 0 | Basic checks (unknown classes, functions, methods) | Very few |
| 1 | Possibly undefined variables, unknown magic methods | Few |
| 2 | Unknown methods on `$this`, return types | Moderate |
| 3 | Basic dead code, always true/false checks | Moderate |
| 4 | Return types, dead else branches | Moderate |
| 5 | Checking types of arguments passed to methods | Higher |
| 6 | Reporting missing type hints | Higher |
| 7 | Reporting partially wrong union types | High |
| 8 | Report nullable types that aren't nullable | High |
| 9 | Dead code analysis (unreachable code) | Very high |
| max | All rules (currently = level 9 + bleeding edge) | Highest |

**Recommended:** Start at level 5, increase to 8+ as codebase improves.

## Key Checks

### Type Safety

```php
// BAD — mixed types
function process($data) {
    return $data->something(); // PHPStan can't verify $data has something()
}

// GOOD — typed
function process(string $data): string {
    return strtoupper($data);
}
```

### Null Safety

```php
// BAD — no null check
function getUser(int $id): User {
    return User::find($id); // find() can return null!
}

// GOOD — null handled
function getUser(int $id): ?User {
    return User::find($id);
}

// GOOD — or throw
function getUser(int $id): User {
    return User::findOrFail($id); // throws if null
}
```

### Dead Code Detection

```php
// BAD — unreachable code
function process(int $x): int {
    return $x * 2;
    $y = $x + 1; // Never reached
}

// BAD — unused private method
class Foo {
    private function unused(): void { } // Dead code
}
```

## Larastan-Specific Checks

Larastan adds Laravel-specific rules:

### Eloquent

- [ ] Model property types match migration column types
- [ ] Relationship return types are correct
- [ ] `$fillable`, `$casts`, `$dates` are properly typed
- [ ] Query builder methods receive correct argument types

### Service Container

- [ ] Bindings resolve to correct types
- [ ] No unresolvable dependencies
- [ ] `app()` calls with correct class names

### Routes

- [ ] Route parameters match controller method signatures
- [ ] Middleware class names are valid
- [ ] Controller method exists

### Views

- [ ] Blade variables passed from controller match what view expects
- [ ] Component properties are typed

## Running

```bash
# Standard analysis
vendor/bin/phpstan analyse --no-progress

# With specific level
vendor/bin/phpstan analyse --level=8 --no-progress

# Single file
vendor/bin/phpstan analyse app/Module/Action.php

# With config
vendor/bin/phpstan analyse -c phpstan.neon

# Larastan (uses phpstan.neon which should include Larastan extension)
vendor/bin/phpstan analyse --no-progress
```

## Interpreting Results

### Error Types

| Error Type | Severity | Action |
|-----------|----------|--------|
| **Error** | High | Must fix — indicates bug or type mismatch |
| **Warning** | Medium | Should fix — likely issue |
| **Tip** | Low | Consider fixing — improvement suggestion |

### Common Laravel False Positives

| Pattern | Why False Positive | Resolution |
|---------|-------------------|------------|
| `Undefined variable $errors` | Blade shared variable | Add `@var` or ignore |
| `Cannot call method on mixed` | Dynamic Eloquent attributes | Use `@method` annotation |
| `Parameter #N of method expects array, array\|false given` | `plode()` can return false | Check return value |

## Configuration Best Practices

```neon
// phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app
    ignoreErrors:
        # Laravel dynamic properties
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::[a-zA-Z]+#'
    checkMissingIterableValueType: false  # Avoid noise from collections
```

## Severity Mapping

| PHPStan Level | Equivalent QA Severity |
|--------------|----------------------|
| Level 0-2 errors | High (basic type safety) |
| Level 3-5 errors | Medium (logic issues) |
| Level 6-8 errors | Low (type completeness) |
| Level 9/max errors | Informational (dead code) |
