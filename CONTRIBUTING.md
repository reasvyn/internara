# Contributing to Internara

Thank you for your interest in contributing to Internara! We welcome contributions from everyone who
shares our commitment to building high-quality, resilient software.

---

## 🚀 Setting Up Your Development Environment

```bash
git clone https://github.com/reasvyn/Internara.git
cd Internara
composer setup   # install deps, generate key, migrate DB, build assets
composer dev     # start all dev processes
```

> After `composer dev`, visit http://localhost:8000 and complete the Setup Wizard.

---

## 🔄 Contribution Workflow

1. **Fork** the repository on GitHub.
2. **Create a branch** following the naming convention:

    ```
    feature/{module}/{short-description}   # new feature
    fix/{module}/{short-description}       # bug fix
    refactor/{module}/{short-description}  # refactor without behavior change
    docs/{scope}                           # documentation only
    ```

    Examples: `feature/journal/export-pdf`, `fix/attendance/date-validation`, `docs/readme`

3. **Implement your changes** — see [Code Patterns](#-code-patterns) below.
4. **Verify** your work passes all quality gates:
    ```bash
    composer test    # full Pest test suite
    composer lint    # style check (Pint + Prettier) — no writes
    ```
5. **Format** before committing:
    ```bash
    composer format  # auto-format PHP + JS/CSS
    ```
6. **Commit** using Conventional Commits format:

    ```
    type(module): short description

    Body explaining WHY this change was made (optional but recommended).
    ```

    | Type       | When to Use                      |
    | :--------- | :------------------------------- |
    | `feat`     | New feature or behavior          |
    | `fix`      | Bug fix                          |
    | `refactor` | Refactor without behavior change |
    | `docs`     | Documentation only               |
    | `test`     | Test additions or corrections    |
    | `perf`     | Performance improvement          |
    | `chore`    | Tooling, dependencies, CI        |

    Examples:

    ```
    feat(journal): add PDF export for student journals
    fix(attendance): correct date comparison for absence requests
    perf(internship): cache dropdown queries with Cache::remember
    docs(readme): add quick start and module table
    ```

7. **Submit a Pull Request** with a clear description of what changed and why.

---

## ✅ Pull Request Checklist

Before requesting review, confirm:

- [ ] `composer test` passes with no failures
- [ ] `composer lint` passes with no style violations
- [ ] Every new PHP file begins with `declare(strict_types=1);`
- [ ] No hard-coded user-facing strings — all use `__('module::file.key')`
- [ ] New models use UUID (`HasUuid` trait) and `timestamps()`
- [ ] New services extend `EloquentQuery` or `BaseService` and implement a Contract
- [ ] New Livewire managers extend `RecordManager` and implement `initialize()` and
      `getTableHeaders()`
- [ ] No cross-module physical foreign keys in migrations
- [ ] Relevant documentation updated (especially for new modules or changed patterns)

---

## 🧩 Code Patterns

### Livewire Record Manager

All data-management Livewire components extend `RecordManager`, not `Component` directly:

```php
use Modules\UI\Livewire\RecordManager;

class ExampleManager extends RecordManager
{
    protected string $viewPermission = 'example.view';

    public function boot(ExampleService $service): void
    {
        $this->service = $service;
    }

    public function initialize(): void
    {
        // Set UI-specific state (searchable columns, per-page defaults, etc.)
        $this->searchable = ['name', 'description'];
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('example::ui.name'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
    }
}
```

### Computed Properties

Use `#[Computed]` (Livewire 3) for all computed values. Wrap shared dropdown data in
`Cache::remember()`:

```php
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

#[Computed]
public function statuses(): \Illuminate\Support\Collection
{
    return Cache::remember('dropdown:statuses', 300, fn () => Status::all(['id', 'name']));
}
```

### Service Layer

```php
// Always depend on the Contract, never the concrete class
use Modules\Example\Services\Contracts\ExampleService;

class ExampleManager extends RecordManager
{
    public function boot(ExampleService $service): void
    {
        $this->service = $service;
    }
}
```

### Method Override Type Safety

When overriding parent methods, match or widen the parameter type:

```php
// RecordManager declares: edit(mixed $id): void
// Child must use mixed, not string:
public function edit(mixed $id): void  // ✅
public function edit(string $id): void // ❌ Fatal type error
```

---

## 🔐 Security Vulnerabilities

If you discover a security vulnerability, please refer to our **[Security Policy](SECURITY.md)** for
the responsible disclosure process. **Do not** open a public issue.

---

## ⚖️ License

By contributing, you agree that your contributions will be licensed under the **MIT License**.
