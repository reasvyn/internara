# Module Loader Engine: Autonomous Discovery & Orchestration

This document provides the advanced technical record for the **Module Orchestration Engine** of
Internara. It explains how the system autonomously discovers, boots, and integrates domain modules
without manual registration, adhering to the **Project Genesis** architecture.

---

## 🏛️ The Discovery Invariant

Internara is designed for **Zero-Config Integration**. A developer should be able to drop a new
directory into `modules/` and have the system recognize it immediately.

### 1. The Orchestrator: `Core\Providers\ModuleServiceProvider`

The `Core` module acts as the "System Glue." Its primary provider is responsible for scanning the
file system and initializing the modular baseline.

### 2. The Loading Sequence

1.  **Scanning**: The engine scans the `modules/` directory for `module.json` or standard directory
    signatures.
2.  **State Verification**: It checks `modules_statuses.json` to determine if the module is
    administratively enabled.
3.  **Namespace Mapping**: Utilizing PSR-4 patterns, it maps the `Modules\{Name}` namespace to the
    `modules/{Name}/src` directory (Implementing **src-Omission**).
4.  **Provider Booting**: It identifies and boots the primary `ServiceProvider` defined in the
    module metadata.

---

## 🔌 Service Binding & Contract Mapping

Internara utilizes a specialized **Auto-Binder** (`app/Providers/BindServiceProvider`) to facilitate
the **Contract-First** mandate.

### 1. Automatic Contract Resolution

When a module defines a Service and a corresponding Interface (Contract) within the
`src/Services/Contracts/` directory, the engine automatically performs the following:

- **Logic**: If `User\Services\UserService` implements `User\Services\Contracts\UserService`, the
  binder automatically binds the Interface to the Implementation in the Laravel Service Container.
- **Rationale**: This eliminates manual binding code and ensures that cross-module dependency
  injection always receives the correct implementation.

---

## 📂 Namespace Omission Architecture

The **src-Omission** rule is implemented via the Composer autoloader configuration and custom module
metadata.

- **Technical Implementation**:
    ```json
    "psr-4": {
        "Modules": "modules/"
    }
    ```
- **Runtime Adjustment**: The `ModuleServiceProvider` adjusts the mapping so that the `src` folder
  is treated as the root of the namespace, preventing deep, redundant namespace segments like
  `Modules\User\Src\Models`.

---

## 🛠️ Maintenance & Troubleshooting

### 1. Discovery Cache

To optimize performance in production, module locations and service bindings are cached.

- **Clear Command**: `php artisan module:clear-cache` or `php artisan optimize:clear`.

### 2. Dependency Cycles

The engine prevents circular dependencies by loading modules in a specific order:

1.  **Shared** (Infrastructure)
2.  **Core** (Foundation)
3.  **Support/UI/Auth** (Utilities)
4.  **Domain Modules** (Business Logic)

---

_The Module Loader is the heart of Internara's portability. By protecting its logic, we ensure the
system remains scalable and easy to evolve._
