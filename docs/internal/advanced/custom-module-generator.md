# Custom Module Scaffolding: Enforcing Standards

To maintain structural integrity across our Modular Monolith, Internara utilizes custom generation
logic. This ensures that every new module starts with the correct directory tree, naming
conventions, and strict-typing boilerplate.

---

## 1. Strategy 1: Configuration & Stubs (Standard)

This is our primary method. We leverage the native capabilities of `nwidart/laravel-modules` and
customize it via the `config/modules.php` file.

### 1.1 Custom Path Mapping

We override the default generator paths to align with our package-style structure:

```php
// config/modules.php
'generator' => [
    'model' => ['path' => 'src/Models', 'generate' => true],
    'service' => ['path' => 'src/Services', 'generate' => true],
    'contract' => ['path' => 'src/Services/Contracts', 'generate' => true],
],
```

### 1.2 The `src` Convention

By setting `'app_folder' => 'src/'`, we ensure that all domain logic is isolated from the module's
root assets (like `composer.json` or `vite.config.js`).

---

## 2. Strategy 2: Custom Stubs (Visual Standards)

We have published and modified the default stubs to enforce our **PHPDoc** and **Strict Typing**
requirements.

- **Location**: `stubs/modules/*.stub`
- **Impact**: Any class generated via `php artisan module:make-*` will automatically include
  Internara's standard header comments and type hints.

---

## 3. Strategy 3: The Base Module Pattern (Advanced)

For scenarios where a module requires a non-standard starting point (e.g., pre-integrated
third-party APIs), we use the **Base Module Pattern**.

### 3.1 The Concept

Instead of generating from stubs, we maintain a "Template" module. Creating a new feature becomes a
process of **Cloning** and **Analytical Renaming**.

### 3.2 Implementation Steps

1.  **Draft**: Create a perfect module in `stubs/base-module`.
2.  **Placeholder**: Use `{Module}` and `{module}` placeholders in filenames and content.
3.  **Command**: Run a custom Artisan command that clones the folder and performs a recursive
    search-and-replace on the placeholders.

---

_Custom scaffolding prevents "Configuration Drift." By automating the start of a module's life, we
ensure that our architecture remains pure as the project grows._
