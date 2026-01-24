# Custom Module Scaffolding: Enforcing Standards

To maintain structural integrity across our Modular Monolith, Internara utilizes custom generation
logic. This ensures that every new module starts with the correct directory tree, naming
conventions, and strict-typing boilerplate.

> **Spec Alignment:** Scaffolding logic is designed to automatically fulfill the structural
> requirements of the **[Internara Specs](../../internara-specs.md)**, such as UUID identity and
> i11n file placement.

---

## 1. Strategy 1: Configuration & Stubs (Standard)

This is our primary method. We leverage the native capabilities of `nwidart/laravel-modules` and
customize it via the `config/modules.php` file.

### 1.1 Custom Path Mapping

We override the default generator paths to align with our package-style structure:

- **Models:** `src/Models` (Enforces `HasUuid`).
- **Services:** `src/Services` (Enforces **Contract-First** design).
- **Exceptions:** `lang/{locale}/exceptions.php` (Enforces standardized error handling).

### 1.2 The `src` Convention

By setting `'app_folder' => 'src/'`, we ensure that all domain logic is isolated from the module's
root assets.

---

## 2. Strategy 2: Custom Stubs (Visual Standards)

We have published and modified the default stubs to enforce our **PHPDoc** and **Strict Typing**
requirements.

- **Location**: `stubs/modules/*.stub`
- **Impact**: Any class generated via `php artisan module:make-*` will automatically include:
    - `declare(strict_types=1);`
    - Professional English PHPDocs.
    - Standardized localization calls (`__('module::exceptions.key')`).

---

## 3. Strategy 3: The Base Module Pattern (Advanced)

For scenarios where a module requires a non-standard starting point, we use the **Base Module
Pattern**.

### 3.1 The Concept

Instead of generating from stubs, we maintain a "Template" module that includes the **Mobile-First**
UI wrappers and baseline authorization policies.

### 3.2 Implementation Steps

1.  **Draft**: Create a template module in `stubs/base-module`.
2.  **Placeholder**: Use `{Module}` and `{module}` placeholders.
3.  **Command**: Run a custom Artisan command for cloning and analytical renaming.

---

_Custom scaffolding prevents "Configuration Drift," ensuring that our architecture remains pure as
the project grows._
