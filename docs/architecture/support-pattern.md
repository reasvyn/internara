# Support Pattern — Static Utilities, Purity Rules & Boundaries

> **Last updated:** 2026-06-27
> **Changes:** rewrite — Support is now purely static utilities with no constructor injection; instance+framework classes moved to Service pattern

## Description
Defines the Support utility layer — purely static helper classes with minimal or no framework
dependencies.

---


## 1. What Support Is

Support utilities are **`public static` methods only**. They serve one purpose: stateless
transformations and helpers with zero side effects.

**Rules:**
- MUST use only `public static` methods — no `public function` instance methods
- MUST NOT have constructor injection — no `__construct()` parameters
- MAY use static framework calls (`config()`, `__()`, `trans()`) but SHOULD prefer pure PHP where
  possible
- MUST NOT write to the database
- MUST NOT dispatch events
- MUST NOT call Actions
- MUST NOT manage transactions

### What Support Is NOT

| If your class needs... | It belongs in |
|-----------------------|---------------|
| Constructor injection | **Service** (see [Service Pattern](service-pattern.md)) |
| Instance methods (`public function` without `static`) | **Service** |
| Framework facades in a constructor | **Service** |
| To extend a framework class (`extends Translator`, `extends BaseSpotlight`) | **Service** |

---

## 2. Where Support Lives

Support classes at their owning scope:

| Scope | Path | Example |
|-------|------|---------|
| Cross-module | `app/Core/Support/` | `app/Core/Support/PiiMasker.php` |
| Module-level | `app/{Module}/Support/` | `app/User/Support/UserIdentifierGenerator.php` |
| Submodule-level | `app/{Module}/{SubModule}/Support/` | None currently |

**Placement rules:**
- Pure utility without framework calls → `app/Core/Support/`
- Module-specific utility → `app/{Module}/Support/`
- If it needs constructor injection → `app/{Module}/{SubModule}/Services/` instead

---

## 3. Existing Support Classes — Correct vs Incorrect

| Class | Location | Static only? | Framework deps? | Verdict |
|-------|----------|-------------|----------------|---------|
| `Color` | `Core/Support/` | ✅ Static only | ❌ Pure PHP | ✅ Correct |
| `PiiMasker` | `Core/Support/` | ✅ Static only | ❌ Pure PHP | ✅ Correct |
| `PasswordRules` | `Core/Support/` | ✅ Static only | ✅ Minimal (`Password` rule object) | ✅ Correct |
| `helpers.php` | `Core/Support/` | ✅ Static functions | ✅ `app_info()` via `Cache`/`Config` | ✅ Correct (file, not class) |
| `Environment` | `Core/Support/` | ✅ Static only | ✅ `config()`, `app()` | ✅ Correct (static, no injection) |
| `UserIdentifierGenerator` | `User/Support/` | ✅ Static only | ✅ DB collision check | ✅ Correct (static read-only) |
| `AppIntegrity` | `Core/Support/` | ✅ Static only | ❌ Pure PHP (`RuntimeException`) | ✅ Correct |
| `helpers.php` (Settings) | `Settings/Support/` | ✅ Static functions | ✅ `setting()`, `brand()` via Cache | ✅ Correct |
| `SmartLogger` | `Core/Support/` | ❌ Instance methods | ✅ Facades, DB, events | ❌ **Should be Service** |
| `CsvHandler` | `Core/Support/` | ❌ Instance methods | ✅ `StreamedResponse`, `Collection` | ❌ **Should be Service** |
| `Settings` | `Settings/Support/` | ❌ Instance methods | ✅ `Cache`, `Config` | ❌ **Should be Service** |
| `Brand` | `Settings/Support/` | ❌ Instance methods | ✅ `Cache`, `Config` | ❌ **Should be Service** |
| `Theme` | `Settings/Theme/Support/` | ❌ Instance methods | ✅ `Cache`, `SettingsStore` | ❌ **Should be Service** |
| `Locale` | `Settings/Locale/Support/` | ❌ Instance methods | ✅ `App`, `Cookie` | ❌ **Should be Service** |
| `DocumentRenderer` | `Document/Support/` | ❌ Constructor injection | ✅ `Pdf`, `Blade`, `Storage` | ❌ **Should be Service** |
| `CertificateRenderer` | `Certification/Certificate/Support/` | ❌ Constructor injection | ✅ `Pdf`, `Blade`, `Storage` | ❌ **Should be Service** |
| `BackupRunner` | `SysAdmin/Backups/Support/` | ❌ Instance methods | ✅ `storage_path()`, shell exec | ❌ **Should be Service** |
| `SystemProvisioner` | `Setup/Installation/Support/` | ❌ Instance methods | ✅ `Artisan`, `File` | ❌ **Should be Service** |
| `AppInfo` | `Core/Support/` | ✅ Static methods | ✅ `Cache`, `Config`, `File` facades | ⚠️ Borderline: static but framework-aware. Could stay with note. |
| `LangChecker` | `Core/Support/` | ❌ Instance + `extends Translator` | ✅ Framework class | ❌ **Should be Service** |
| `Spotlight` | `Core/Support/` | ❌ Instance + `extends BaseSpotlight` | ✅ maryUI component | ❌ **Should be Service** |

---

## 4. Support vs Actions

| Concern | Support | Action |
|---------|---------|--------|
| **Method style** | `public static` only | `public function execute()` |
| **Base class** | None | `BaseAction` |
| **Transaction** | Never | Required (Command/Process) |
| **Logging** | Never | Required (Command/Process) |
| **Event dispatch** | Never | Recommended (Command) |
| **Database write** | Never | Always (Command) |
| **Business rules** | Never | Primary owner |

### When to Choose Action Over Support

- The class performs a single business operation (create, update, delete, complex read).
- The class needs transaction safety or logging.
- The class dispatches events.

If any of these are true, use an Action. A Support class is never a downgrade path for an Action.

---

## 5. Support vs Services

| Concern | Support | Service |
|---------|---------|---------|
| **Method style** | `public static` only | Instance methods with constructor injection |
| **Constructor injection** | Never | Required |
| **Framework dependency** | Minimal (static `config()` OK) | Required (config, container, facades) |
| **Override/extend framework class** | Never | May extend framework classes |
| **Scope** | Module or submodule | Core, Module, or SubModule |
| **Domain business logic** | Never | Never (infrastructure logic only) |
| **Test style** | Unit (no Laravel boot needed) | Integration (needs Laravel container) |

**Quick decision:**
```
Does the class need constructor injection?
├─ Yes → Service
└─ No → Can it be written as `public static` methods only?
    ├─ Yes → Support
    └─ No → Service (instance methods + minimal/no injection is still Service)
```

---

## 6. When to Create a Support Utility

1. **Pure transformation** — color math, string masking, array manipulation, CSV generation logic
   (without StreamedResponse).
2. **Static validation rules** — password rules, validation arrays.
3. **Stateless generator** — username generation based on simple rules.
4. **Standalone helper function** — a global `app_info()` or `setting()` convenience wrapper.

### Do NOT Create a Support Utility For

- **Operations needing constructor injection** → Service instead.
- **Business operations** → Action instead.
- **Eloquent-related code** → Model scopes or traits instead.
- **Single-use inline logic** → keep it local until a second caller appears.

---

## 7. Anti-Patterns

- **Support with constructor injection** — if you need injected dependencies, it is a Service.
  Rename and move.
- **Support calling Actions** — a Support class must never call an Action.
- **Support with database writes** — persistence goes through Command Actions. Read-only Model
  queries from static methods are acceptable for simple lookups in module-level Support.
- **Support with instance methods** — `public function` (non-static) in Support is prohibited.
  Convert to static or move to Service.
- **Support extending framework classes** — `extends Translator`, `extends BaseSpotlight` — these
  are Service concerns because they depend on the framework's class hierarchy.
- **Support as dumping ground** — a `Support/` directory mixing pure static utilities with
  framework-heavy instance classes indicates missing Service boundaries.

---

## 8. Relationship Summary

```
┌───────────────────────────────────────────────────────────────────────┐
│                   BUSINESS OPERATIONS LAYER                           │
│                                                                       │
│  ┌─────────────────────────┐  ┌──────────────────────────────────┐   │
│  │        ACTION            │  │          SUPPORT                 │   │
│  │  (single operation)      │  │  (static utilities)              │   │
│  │                          │  │                                  │   │
│  │  • public function       │  │  • public static methods only    │   │
│  │    execute()             │  │  • no constructor injection      │   │
│  │  • transaction + log     │  │  • no framework deps (minimal)   │   │
│  │  • events                │  │  • pure transformations          │   │
│  │  • domain business rules │  │  • no business decisions         │   │
│  └─────────────────────────┘  └──────────────────────────────────┘   │
│              ▲                              ▲                        │
│              │                              │                        │
│              └──────── uses ────────────────┘                        │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                  SERVICE (infrastructure)                    │    │
│  │  • public function instance methods                          │    │
│  │  • constructor injection for framework deps                  │    │
│  │  • infrastructure logic (not domain business logic)          │    │
│  │  • never calls Actions, never writes DB                      │    │
│  │  • lives at Core / Module / SubModule level                  │    │
│  └──────────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────────────┘
```

Key boundaries:

- **Action** — owns domain business operations. Transactions, logging, events. Single `execute()`.
- **Support** — owns static utilities. Pure transformations, zero side effects, no constructor.
- **Service** — owns infrastructure logic at any scope. Instance methods with constructor injection.

An Action may use a Support or Service. A Support utility may use a Service. A Service must never
call an Action.

---

> For infrastructure classes that need constructor injection, see the
> [Service Pattern](service-pattern.md). For the complete catalog of all patterns, see
> [Modular Pattern Reference](modular-pattern.md).
