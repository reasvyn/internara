# Rules: Code Quality

> ISO 25010 Mapping: Maintainability (Modularity, Analysability, Modifiability, Testability)
> Applicability: All codebases

## Overview

Code quality encompasses complexity, duplication, dead code, naming, and structural
patterns that affect maintainability.

## 1. Complexity Metrics

### Cyclomatic Complexity

Each function/method should have cyclomatic complexity < 10.

```php
// HIGH complexity — deeply nested
public function process(Order $order): Result
{
    if ($order->isValid()) {
        if ($order->hasItems()) {
            foreach ($order->items as $item) {
                if ($item->isAvailable()) {
                    if ($item->stock >= $item->quantity) {
                        // Deep nesting, hard to follow
                    }
                }
            }
        }
    }
}

// LOW complexity — flat structure
public function process(Order $order): Result
{
    if (!$order->isValid()) {
        return Result::invalid();
    }
    
    if (!$order->hasItems()) {
        return Result::empty();
    }
    
    $unavailable = $order->items->reject->isAvailable();
    if ($unavailable->isNotEmpty()) {
        return Result::unavailable($unavailable);
    }
    
    return Result::success();
}
```

### Nesting Depth

Maximum nesting depth: 3 levels.

```php
// BAD — 5 levels
foreach ($orders as $order) {
    if ($order->active) {
        foreach ($order->items as $item) {
            if ($item->needsReview()) {
                if ($item->canAutoApprove()) {
                    $item->approve(); // 5 levels deep
                }
            }
        }
    }
}

// GOOD — 2 levels (early returns, extracted methods)
foreach ($orders as $order) {
    $this->processOrder($order);
}

private function processOrder(Order $order): void
{
    if (!$order->active) {
        return;
    }
    
    $order->items
        ->filter->needsReview()
        ->filter->canAutoApprove()
        ->each->approve();
}
```

### Method Length

Target: < 20 lines per method. Maximum: 50 lines.

### Class Length

Target: < 300 lines per class. Maximum: 500 lines.

## 2. Code Duplication

### Detection Patterns

```bash
# Find similar method signatures
grep -rn "function.*execute\|function.*handle" app/ --include="*.php" | sort

# Find repeated blocks
# Use tools: phpmd, phpcpd (copy-paste detector)
vendor/bin/phpmd app/ text rulesets/cleancode.xml
```

### Types of Duplication

| Type | Example | Resolution |
|------|---------|------------|
| **Exact copy-paste** | Same code in 2+ files | Extract to shared method/service |
| **Structural duplication** | Same pattern, different data | Extract to base class/trait |
| **Semantic duplication** | Same logic, different implementation | Consolidate to single implementation |
| **Boilerplate duplication** | Similar CRUD actions across modules | Create base class or generator |

## 3. Dead Code

```bash
# Find unused classes (defined but never imported)
grep -rn "^class\|^interface\|^trait\|^enum" app/ --include="*.php" | while read line; do
    classname=$(echo "$line" | sed 's/.*class \([A-Za-z]*\).*/\1/')
    count=$(grep -rn "$classname" app/ --include="*.php" | wc -l)
    if [ "$count" -le 1 ]; then
        echo "POSSIBLY UNUSED: $classname (defined in: $line)"
    fi
done

# Find TODO/FIXME/HACK comments
grep -rn "TODO\|FIXME\|HACK\|XXX\|WORKAROUND" app/ --include="*.php"

# Find empty methods
grep -rn "function.*{}$" app/ --include="*.php"
```

## 4. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Class | PascalCase | `UserService` |
| Method | camelCase | `getUserData()` |
| Variable | camelCase | `$userCount` |
| Constant | UPPER_SNAKE_CASE | `MAX_ATTEMPTS` |
| Boolean method | is/has/can/should prefix | `isActive()`, `hasPosts()` |
| Collection method | noun (plural) | `$users`, `$items` |
| Boolean variable | adjective or is/has prefix | `$isActive`, `$hasErrors` |

## 5. Magic Numbers and Strings

```php
// BAD — magic numbers
if ($user->login_attempts > 5) { ... }
sleep(3600);
return $value * 0.15;

// GOOD — named constants
private const MAX_LOGIN_ATTEMPTS = 5;
private const LOCKOUT_DURATION_SECONDS = 3600;
private const TAX_RATE = 0.15;

if ($user->login_attempts > self::MAX_LOGIN_ATTEMPTS) { ... }
```

## 6. Dependency Direction

```php
// BAD — circular dependency
// ModuleA imports ModuleB
// ModuleB imports ModuleA

// Check for circular imports:
grep -rn "^use App\\\\" app/ --include="*.php" | awk -F'use ' '{print $2}' | sort | uniq -c | sort -rn | head -20
```

## 7. Function Parameters

```php
// BAD — too many parameters
public function create(
    string $name,
    string $email,
    string $phone,
    string $address,
    string $city,
    string $state,
    string $zip,
    string $country
): User { }

// GOOD — use DTO/Value Object
public function create(UserData $data): User { }

// Threshold: > 3 parameters should use a DTO
```

## 8. Return Type Consistency

```php
// BAD — inconsistent return types
public function find(int $id)
{
    if ($id === 0) {
        return null;  // Sometimes null
    }
    return User::find($id);  // Sometimes User
}

// GOOD — consistent return type
public function find(int $id): ?User
{
    return User::find($id);
}
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| Cyclomatic complexity > 20 | High |
| Method > 100 lines | Medium |
| Class > 500 lines | Medium |
| Dead code (unused private methods) | Low |
| Magic numbers in business logic | Medium |
| Code duplication > 10 lines | Medium |
| TODO/FIXME older than 6 months | Low |
| > 3 parameters on public method | Low |
| Inconsistent return types | Medium |
| Circular dependencies | High |
