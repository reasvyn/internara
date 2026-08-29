# File Header Order

Every PHP class file MUST follow this exact ordering:

```php
<?php

declare(strict_types=1);

namespace App\{Module}\{SubModule}\{Type};

use App\{Dependency};

class {ClassName} extends {BaseClass}
{
    public function __construct(
        protected readonly {Type} ${param},
    ) {}

    public function execute(): {ReturnType}
    {
        // ...
    }
}
```

**Rules:**

1. `declare(strict_types=1)` — always first (except migrations/config)
2. Namespace — matches directory location
3. Use statements — one per line, sorted alphabetically
4. Class declaration — extends appropriate base class
5. Constructor — `protected readonly` promotion for injected dependencies
6. Single `execute()` method — the only public method on Actions

**Why this ordering matters:** It makes every file parse the same way: strict types are enforced
before anything is declared, autoloading matches namespace→path, imports are greppable and
diff-stable, and the constructor signature documents the dependency graph at a glance.

**Pitfalls to avoid:**
- Placing namespace/`use` above the `declare` line.
- Grouping imports by category instead of alphabetically.
- Non-promoted constructor assignments that hide injected dependencies.

**Detection:** `vendor/bin/pint --dirty --format agent` (style) and review of each new file.
