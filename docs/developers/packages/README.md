# Supporting Ecosystem: Package Integration Index

This index centralizes the technical records for the **Supporting Lifecycle Processes** of the
Internara project. It defines the protocols for integrating third-party dependencies while ensuring
compliance with the **Modular Monolith** architectural invariants.

---

## 1. Foundational Orchestrators

- **[Ecosystem Overview](packages-overview.md)**: Semantic mapping of the supporting baseline.
- **[Laravel Framework](laravel-framework.md)**: Infrastructure orchestration standards.
- **[Laravel Modules](laravel-modules.md)**: Foundational modular engine protocols.

## 2. Presentation & State Baselines

- **[Laravel Livewire](laravel-livewire.md)**: Presentation layer orchestration standards.
- **[Modules Livewire](mhmiton-laravel-modules-livewire.md)**: Cross-module component discovery
  bridge.
- **[Model Status](spatie-laravel-model-status.md)**: Entity lifecycle orchestration standards.

## 3. Security, Audit & Media

- **[Laravel Permission](spatie-laravel-permission.md)**: Modular IAM orchestration standards.
- **[Activity Log](spatie-laravel-activitylog.md)**: Forensic audit orchestration standards.
- **[Media Library](spatie-laravel-medialibrary.md)**: Asset management orchestration standards.

---

_Internal artifacts must utilize the abstractions defined in these protocols to ensure architectural
purity and prevent concrete package coupling._
