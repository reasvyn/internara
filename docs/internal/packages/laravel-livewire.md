# Laravel Livewire: Reactive Modular UI

Livewire is the primary framework for building interactive user interfaces in Internara. By
leveraging the TALL stack, we maintain a simplified, full-stack development experience while
achieving SPA-like reactivity.

---

## 1. Architectural Role in Modules

In our layered architecture, Livewire acts as the **Presentation Layer**.

### 1.1 Strict Gating (Thin Components)

Livewire components **must not** contain business logic. Their responsibilities are:

- Capturing user input.
- Managing local UI state (e.g., toggling modals).
- Delegating data processing to **Service** classes.
- Rendering views using **MaryUI** and **DaisyUI** components.

### 1.2 Modular Discovery

We use the `module::component` syntax. This is made possible by the
`mhmiton/laravel-modules-livewire` bridge.

- **Example**: `@livewire('user::profile-form')` points to
  `modules/User/src/Livewire/ProfileForm.php`.

---

## 2. Dependency Injection: The `boot()` Rule

A critical convention in Internara: **Never use the constructor for Dependency Injection in Livewire
components.**

### Why?

Livewire's hydration cycle recreates the component instance on every request. Injecting dependencies
in the constructor can lead to stale objects or hydration failures.

### The Correct Way

Use the `boot()` method to inject your **Contracts**.

```php
protected UserServiceInterface $userService;

public function boot(UserServiceInterface $userService): void
{
    $this->userService = $userService;
}
```

---

## 3. Inter-Module Communication

To maintain modular isolation, Livewire components should never reference other components from
different modules directly. Use these approved patterns:

### 3.1 Browser Events (Client-Side)

The preferred method for dynamic updates.

- **Trigger**: `$this->dispatch('user-updated')`.
- **Listen**: `#[On('user-updated')]` in another module's component.

### 3.2 UI Slots (Server-Side)

For static or layout-level injection.

- **Usage**: Modules register their components to a named slot (e.g., `navbar.actions`).
- **Render**: The `UI` module renders these slots via `@slotRender`.

---

_Livewire is our most powerful UI tool. Following these conventions ensures that your interfaces
remain fast, secure, and easy to test._
