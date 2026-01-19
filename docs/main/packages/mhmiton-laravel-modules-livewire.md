# Modules Livewire: The Component Bridge

The `mhmiton/laravel-modules-livewire` package is a critical piece of Internara's modular
infrastructure. It allows Livewire components located within a module's `src/Livewire` directory to
be discovered and rendered using a clean, semantic syntax.

---

## 1. Modular Syntax

Without this bridge, Livewire would only look in the `app/` directory. This package enables the
`module::` prefix.

- **Convention**: Always use the `@livewire` directive for interactive components.
- **Example**: `@livewire('internship::application-form')`
- **Path**: `modules/Internship/src/Livewire/ApplicationForm.php`

---

## 2. Component Discovery

The package is configured to scan the `src/Livewire` folder of every **Enabled** module. If a module
is disabled in `modules_statuses.json`, its components will not be discoverable.

### Event Namespacing

When dispatching events between modules, prefix the event name with the module alias to prevent
collisions.

- **Correct**: `$this->dispatch('user::updated')`
- **Incorrect**: `$this->dispatch('updated')`

---

_This bridge ensures that our UI remains as modular as our backend logic. Refer to the
**[Livewire Integration Guide](laravel-livewire.md)** for general component best practices._
