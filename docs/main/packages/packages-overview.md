# Package Integration: The Internara Ecosystem

Internara is built on the shoulders of giants. We leverage several high-quality Laravel packages to
achieve our **Modular Monolith** architecture and reactive UI. This directory documents how we
configure and wrap these dependencies to maintain our strict architectural standards.

---

## 1. Core Architectural Drivers

### 1.1 [nwidart/laravel-modules](nwidart-laravel-modules.md)

The foundational engine for our modularity. It provides the directory structure and autoloader logic
that allows us to treat each folder in `modules/` as a mini-application.

### 1.2 [mhmiton/laravel-modules-livewire](mhmiton-laravel-modules-livewire.md)

A critical bridge that enables Livewire component discovery across modular boundaries. This allows
for the `module::component` syntax in our views.

---

## 2. Security & Identity

### 2.1 [spatie/laravel-permission](spatie-laravel-permission.md)

The engine behind our **RBAC** system. We have wrapped this package into a portable `Permission`
module, adding full **UUID** support and cross-module synchronization logic.

### 2.2 [spatie/laravel-activitylog](spatie-laravel-activitylog.md)

Powers our audit trails. It is integrated into the `Log` module to ensure all critical business
actions are tracked and attributed to specific users.

---

## 3. UI & State Management

### 3.1 [Livewire/livewire](laravel-livewire.md)

The primary driver for our interactive frontend. It allows us to manage complex state and reactive
updates using pure PHP logic.

### 3.2 [spatie/laravel-model-status](spatie-laravel-model-status.md)

Powers our **HasStatuses** concern. It provides a standardized way to manage entity lifecycles
(e.g., Pending -> Approved) with a built-in audit trail.

### 3.3 [spatie/laravel-medialibrary](spatie-laravel-medialibrary.md)

Handles all file attachments and image processing. It is integrated with our `UI` file component to
provide seamless uploads and previews.

---

## 4. Our "Zero-Leak" Philosophy

We do not use these packages in a standard way. Our configuration rules ensure:

- **Isolation**: Package configuration is injected at runtime by the module, preventing leakage into
  the global `config/` directory.
- **Portability**: Modules remain plug-and-play. Disabling a module also disables its corresponding
  package integrations.
- **Consistency**: All packages are configured to respect our **Namespace Convention** (omitting the
  `src` segment).

---

_Refer to the individual guide for each package to understand its specific implementation patterns
and best practices within Internara._
