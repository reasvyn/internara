# Essential Tooling: Engineering Orchestration

This document formalizes the authoritative technical catalog for the Internara project. It provides
the essential commands required to maintain system integrity, verify behavioral specifications, and
ensure code quality.

---

## 1. Lifecycle & Environment

| Command           | Objective                                                                 |
| :---------------- | :------------------------------------------------------------------------ |
| `composer setup`  | Full environment bootstrapping (Install, Migrate, Build).                 |
| `composer dev`    | Integrated concurrent environment (Server, Queue, Logs, Vite).             |
| `php artisan app:info` | Display application identity, version, and technical stack (SSoT Audit). |

---

## 2. Quality Gates (V&V)

Mandatory verification passes prior to any repository synchronization.

| Command           | Objective                                                                 |
| :---------------- | :------------------------------------------------------------------------ |
| `composer test`   | Execute all behavioral verification suites (Pest v4).                     |
| `composer lint`   | Perform static analysis and check code style compliance (Pint & Prettier). |
| `composer format` | Automatically fix code style violations across PHP, Blade, and JS.        |
| `php artisan app:test` | Sequential module execution to minimize memory heap usage.            |

---

## 3. Scaffolding (Architectural Integrity)

Generators are engineered to respect the **src-Omission** namespace rule and **Modular DDD**
hierarchy.

- **Services**: `php artisan module:make-service {Name} {Module}`
- **Models**: `php artisan module:make-model {Name} {Module}`
- **Livewire**: `php artisan module:make-livewire {Name} {Module}`
- **Forms**: `php artisan module:make-livewire-form {Name} {Module}`
- **Contracts**: `php artisan module:make-interface {Name} {Module}`
- **Concerns**: `php artisan module:make-trait {Name} {Module}`

---

_Adherence to this tooling baseline is mandatory to ensure the structural predictability and
reliability of the Internara system._
