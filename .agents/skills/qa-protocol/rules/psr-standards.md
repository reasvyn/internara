# Rules: PSR — PHP Standards Recommendations

> Source: https://www.php-fig.org/psr/
> Versions: PSR-1 (2012), PSR-4 (2013/2019), PSR-12 (2019)
> Applicability: All PHP projects

## PSR-1: Basic Coding Standard

**Source:** https://www.php-fig.org/psr/psr-1/

### Files

1. **PHP Files MUST contain only PHP code** — no closing `?>` tag
2. **PHP Files MUST use only UTF-8** without BOM
3. **Side Effects** — A file should either declare symbols (classes, functions, constants)
   OR have side effects, but NOT both

```php
<?php
// BAD — declares a class AND has side effects
class Foo { }
echo "Hello";  // Side effect!

// GOOD — separate files
// Foo.php
class Foo { }

// index.php (entry point)
require 'Foo.php';
echo "Hello";
```

### Namespace and Class Names

1. **Classes MUST be declared in `PascalCase`** (StudlyCaps)
2. **Class constants MUST be declared in `UPPER_SNAKE_CASE`**

```php
// BAD
class myClass { }
class my_class { }

// GOOD
class MyClass { }
```

### Methods

1. **Method names MUST be declared in `camelCase`**

```php
// BAD
public function get_user_data() { }

// GOOD
public function getUserData() { }
```

## PSR-4: Autoloading

**Source:** https://www.php-fig.org/psr/psr-4/

### Mapping

- The terminating class name MUST be a class name (not a suffix or prefix)
- The file MUST end with `.php`
- The file MUST contain only ONE class/interface/trait
- Namespace and directory separators MUST use `\`

### Convention

```
App\ → app/
App\Core\ → app/Core/
App\Auth\Login\ → app/Auth/Login/
```

### Detection

```bash
# Check PSR-4 compliance
composer dump-autoload --optimize
# No errors = compliant

# Manual check: verify namespace matches directory
grep -rn "^namespace " app/ | head -20
# Each should map to its directory path
```

## PSR-12: Extended Coding Style

**Source:** https://www.php-fig.org/psr/psr-12/

### Key Rules

#### File Structure
```php
<?php  // Opening tag on first line
declare(strict_types=1);  // On next line after opening tag

namespace App\Module;

use Some\Other\Class;

class MyClass
{
    // Class body
}
```

#### Visibility
```php
// BAD
var $property;
function method() { }

// GOOD
private string $property;
public function method(): void { }
```

#### Method and Function Declarations
```php
// GOOD — visibility, return type, no space between method name and parentheses
public function methodName(): string
{
    // Method body
}

// GOOD — one argument per line if signature exceeds 80 chars
public function veryLongMethodName(
    string $firstArgument,
    int $secondArgument,
    array $thirdArgument
): ReturnType {
    // Method body
}
```

#### Control Structures
```php
// GOOD — opening brace on same line, space before opening paren
if ($condition) {
    // Body
} elseif ($otherCondition) {
    // Body
} else {
    // Body
}

// GOOD — keywords followed by space
if ($condition) {  // not if($condition)
for ($i = 0; $i < 10; $i++) {  // not for($i...
```

#### `declare(strict_types=1)`
```php
<?php
declare(strict_types=1);  // MUST be present in every PHP file

// Exception: Laravel migration files and config files
// (framework convention)
```

### Detection

```bash
# Check with PHP-CS-Fixer or Laravel Pint
vendor/bin/pint --test

# Check specific file
vendor/bin/pint --test path/to/file.php
```

## Summary Checklist

| Rule | PSR | Check |
|------|-----|-------|
| No closing `?>` tag | PSR-1 | `grep -rn "?>$\|?> " app/` |
| UTF-8 encoding | PSR-1 | `file -i *.php` |
| No side effects in class files | PSR-1 | Manual review |
| PascalCase class names | PSR-1 | `grep -rn "^class [a-z]" app/` |
| camelCase method names | PSR-1 | `grep -rn "function [a-z_]" app/` |
| UPPER_SNAKE_CASE constants | PSR-1 | `grep -rn "const [a-z]" app/` |
| Namespace matches directory | PSR-4 | `composer dump-autoload` |
| `declare(strict_types=1)` | PSR-12 | `grep -rLn "declare(strict_types" app/` |
| No `var` keyword | PSR-12 | `grep -rn "var \$" app/` |
| Visibility on properties/methods | PSR-12 | `grep -rn "function [a-z].*{" app/` |
| Space after control keywords | PSR-12 | `grep -rn "if(\|for(\|while(" app/` |
| Opening brace on same line | PSR-12 | Manual review |
