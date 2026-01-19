# Package Integration - Documentation Index

Internara utilizes several best-in-class Laravel packages. This section explains how we have wrapped
and configured these dependencies to work within our Modular Monolith.

---

## 1. Foundation & Structure

- **[Packages Overview](packages-overview.md)**: A map of our core dependencies.
- **[Laravel Framework](laravel-framework.md)**: Standards for using core Laravel features.
- **[Laravel Modules](nwidart-laravel-modules.md)**: The engine driving our modularity.

## 2. Reactivity & State

- **[Laravel Livewire](laravel-livewire.md)**: Patterns for our reactive frontend.
- **[Modules Livewire](mhmiton-laravel-modules-livewire.md)**: The cross-module discovery bridge.
- **[Model Status](spatie-laravel-model-status.md)**: Standardized entity lifecycles.

## 3. Security & Assets

- **[Laravel Permission](spatie-laravel-permission.md)**: Technical details of our RBAC system.
- **[Activity Log](spatie-laravel-activitylog.md)**: Implementing modular audit trails.
- **[Media Library](spatie-laravel-medialibrary.md)**: Robust file and attachment handling.

---

_Modules should ideally rely on the abstractions provided in these guides rather than interacting
with package APIs directly._
