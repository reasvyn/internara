# Known Issues & Limitations

> **Last updated:** 2026-06-12

This document catalogs known issues across the Internara codebase.

---

## Core Module Audit

### CRITICAL

| ID | File | Issue | Status |
|----|------|-------|--------|
| CORE-1 | `app/Core/Data/BaseData.php` | **`clearParamCache()` used reflection on a non-existent static property (local `static $cache` variable).** Would crash at runtime. | **Fixed** — converted to real class static property `$paramCache`, replaced reflection with simple `self::$paramCache = []`. |

### HIGH

| ID | File | Issue | Status |
|----|------|-------|--------|
| CORE-2 | `app/Core/Entities/BaseEntity.php` | **`fromArray()` silently produced runtime errors on missing required constructor params** (cryptic `ArgumentCountError` instead of descriptive message). | **Fixed** — added validation that throws `InvalidArgumentException` with parameter name, mirroring `BaseData::fromArray()`. |
| CORE-3 | `app/Core/Console/Commands/ModuleDiscoverCommand.php` | **Potential null provider access.** `app()->getProvider(AppServiceProvider::class)` could return `null`, causing a `TypeError` rather than a clear error message. | **Fixed** — added `if ($provider === null) throw new RuntimeException(...)` guard before usage. |

### MEDIUM

| ID | File | Issue | Status |
|----|------|-------|--------|
| CORE-4 | `app/Core/Models/BaseModel.php` + `BaseAuthenticatable.php` | **Complete code duplication of 6 scope methods** (active, inactive, recent, createdAfter, createdBefore, ordered) across both base model classes. | **Fixed** — extracted into shared `HasCommonScopes` trait at `app/Core/Models/Concerns/HasCommonScopes.php`. |
| CORE-5 | `app/Core/Livewire/BaseRecordManager.php` | **`performBulkAction()` generates N separate queries** for bulk operations with hundreds of selected records. | By Design — admin-only feature; N queries within single transaction is acceptable for admin panel scale. |
| CORE-6 | `app/Core/Support/SmartLogger.php` | **`resolveTranslations()` resolves in both `en` and `id`** regardless of locale — 2 translation lookups per log entry. | By Design — both translations are stored so log viewers in any language can understand entries. |
| CORE-7 | `app/Core/Support/SmartLogger.php` | **`resolveCauser()` falls back to `Auth::user()`.** Queue jobs without explicit `for()` call lose causer context. | By Design — queue jobs should call `->for($user)` explicitly. |

### LOW

| ID | File | Issue | Status |
|----|------|-------|--------|
| CORE-8 | `app/Core/Actions/BaseAction.php` | **Fragile namespace parsing in `moduleName()`** — relies on `explode('\\', static::class)[1]`. | **Fixed** — added guard check for `$parts[0] === 'App'` before accessing index `[1]`. |
| CORE-9 | `app/Core/Exceptions/Concerns/HasExceptionContext.php` | **`isUserFacing()`/`shouldReport()` defined in both `AppException` and trait.** Maintenance risk if behavior changes. | By Design — `AppException` overrides for framework errors; `ModuleException` uses trait defaults. |
| CORE-10 | `app/Core/Exceptions/Concerns/HasExceptionContext.php` | **`toCliOutput()` did not include the exception chain.** Previous exception (`$previous`) was never rendered. | **Fixed** — added previous exception output when available. |
| CORE-11 | `app/Core/Support/HandlesActionErrors.php` | **Redundant union types.** `RuntimeException` is parent of both `AppException` and `ModuleException`. | **Fixed** — removed redundant `RuntimeException` from catch union. |
| CORE-12 | `app/Core/Http/Requests/BaseFormRequest.php` | **`failedValidation()` throws `ValidationFailedException`** instead of Laravel's `ValidationException`. | By Design — custom exception for consistent error handling across the stack. Global handler configured accordingly. |
| CORE-13 | `app/Core/Http/Middleware/LogContext.php` | **N+1 query on every authenticated request.** `$request->user()->roles->pluck('name')` loaded ALL roles. | **Fixed** — replaced with `$request->user()->roles()->value('name')` (single DB query). |
| CORE-14 | `app/Core/Http/Middleware/LogContext.php` | **Missing null check on `$request->user()`.** | **Already handled** — `if ($request->user())` guard existed at line 24. |
| CORE-15 | `app/Core/Livewire/Concerns/WithRecordSelection.php` | **`selectAll(array $ids)` had no type constraint** on array values. | **Fixed** — added `@param array<string|int> $ids` docblock annotation. |
| CORE-16 | `app/Core/Support/Spotlight.php` | **Empty class extending maryUI's Spotlight.** No customization implemented. | **Fixed** — added docblock explaining it's a placeholder for future customization. |
| CORE-17 | `app/Core/Support/SmartLogger.php` | **`writeActivityLog()` catches ALL `\Throwable` silently.** Activity log failures masked. | By Design — intentional soft-fail to prevent activity log errors from crashing the main operation. |
| CORE-18 | `app/Core/Events/BaseEvent.php` | **`toPayload()` iterates over all properties.** Objects filtered by `!is_object()` check. | By Design — documented filtering behavior prevents non-serializable data leakage. |

### ARCHITECTURAL

| ID | Issue | Detail | Status |
|----|-------|--------|--------|
| CORE-A1 | `StatusEnum` coverage | Verify all state enums implement `StatusEnum` contract. | **Verified** — all 16 state enums across all modules properly implement `StatusEnum`. |
| CORE-A2 | `SettingsStore` read-only | Contract defines only `get()`, no `set()`. All writes bypass the contract. | By Design — settings writes go through Actions for transaction safety and logging. |

---

## Previous Resolutions

| ID | Issue | Status |
|----|-------|--------|
| CORE-1 | BaseData clearParamCache crash | Fixed |
| CORE-2 | BaseEntity fromArray silent failure | Fixed |
| CORE-3 | ModuleDiscoverCommand null provider | Fixed |
| CORE-4 | BaseModel scope duplication | Fixed |
| CORE-10 | HasExceptionContext CLI chain | Fixed |
| CORE-11 | HandlesActionErrors redundant types | Fixed |
| CORE-13 | LogContext N+1 query | Fixed |
| CORE-15 | WithRecordSelection type constraint | Fixed |
| CORE-16 | Spotlight empty class | Fixed |
| E1 | Report phantom fillable columns | Fixed |
| E5 | CertificateTemplate missing migration | Fixed |
| C1 | Read Actions extending BaseAction | Fixed |
| C2 | Actions missing execute() | Fixed |
| C8 | config/mary.php Spotlight | Fixed |
| C10 | Entity accessor methods | Fixed |
| C11 | AnnouncementStatus StatusEnum | Fixed |
| A48 | Evaluations schema redesign | Fixed |
