# Shared Utilities — Cross-Cutting Helpers & Services

> **Last updated:** 2026-07-24 **Changes:** feat — split from core-foundation.md; AppInfo,
> Environment, PasswordRules, Color, AppIntegrity, LangChecker

## Description

Cross-cutting utility classes used by multiple modules. These are not architectural foundations
(base classes) or infrastructure configuration (tech stack) — they are shared helpers that
multiple features depend on. SmartLogger, CsvHandler, and ModuleDiscoverService have their own
dedicated specs and are cross-referenced here only.

---

## 1. Problem Statements

### PS-1 — Duplicated Helper Logic

Without shared utilities, each module reimplements common operations: reading app metadata,
detecting environment, validating passwords, converting colors. This leads to inconsistent
behavior (one module uses 8-char passwords, another uses 12) and wasted effort.

### PS-2 — Application Integrity Verification

A self-hosted application distributed via Git must verify that the deployment matches the
expected attribution. Unauthorized redistribution (removing author credits) must be detectable
in production while allowing development flexibility.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `AppInfo` — centralized composer.json metadata with 24h cache |
| G2  | Provide `Environment` — environment detection helpers (isProduction, isTesting, etc.) |
| G3  | Provide `PasswordRules` — default password validation rules |
| G4  | Provide `Color` — hex/RGB conversion, contrast calculation, DaisyUI shade generation |
| G5  | Provide `AppIntegrity` — composer attribution verification |
| G6  | Provide `LangChecker` — missing translation key detection |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | SmartLogger, PiiMasker — see [logging-and-error-handling.md](logging-and-error-handling.md) |
| NG2  | CsvHandler — see [csv-import-export.md](csv-import-export.md) |
| NG3  | ModuleDiscoverService — see [module-discovery.md](module-discovery.md) |
| NG4  | Base classes (Action, Entity, DTO, Model) — see [base-classes.md](base-classes.md) |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Reads App Metadata

**Actor:** Developer / System
**Preconditions:** `composer.json` exists with author info
**Flow:**
1. Code calls `AppInfo::name()`, `AppInfo::version()`, `AppInfo::authorName()`
2. `AppInfo` reads `composer.json` once, caches for 24h
3. Subsequent calls return cached values
**Postconditions:** Consistent metadata across all modules, zero repeated file reads

### UC-2 — System Verifies Application Attribution

**Actor:** System (startup or admin trigger)
**Preconditions:** Application deployed
**Flow:**
1. `AppIntegrity::verify()` reads composer.json author name
2. Compares against expected attribution
3. In production: throws `RejectedException` if attribution removed
4. In local/testing: logs warning via SmartLogger
**Postconditions:** Unauthorized redistribution detected and reported

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-SUP3 | `PasswordRules` — default password validation: 8+ chars, mixed case, numbers |
| FR-SUP4 | `AppInfo` — reads `composer.json` metadata (name, version, author, license, etc.) with 24h cache |
| FR-SUP5 | `Environment` — helpers: `isProduction()`, `isTesting()`, `isLocal()`, `isCLI()` |
| FR-SUP7 | `Color` — `hexToRgb()`, `rgbToHex()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, `computeDarkShades()` |
| FR-SUP9 | `AppIntegrity` — verifies composer.json author name; throws in production, warns in dev/test |
| FR-SUP10 | `LangChecker` — extends Laravel `Translator`, logs missing translation keys with caller file/line via SmartLogger |

### Cross-References (Dedicated Specs)

| Utility | Full Spec |
| ------- | --------- |
| SmartLogger, PiiMasker | [logging-and-error-handling.md](logging-and-error-handling.md) |
| CsvHandler | [csv-import-export.md](csv-import-export.md) |
| ModuleDiscoverService | [module-discovery.md](module-discovery.md) |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1 | `AppInfo` metadata cache TTL: 24 hours (86400s) |
| NFR-R1 | `AppIntegrity::verify()` must catch exceptions and degrade gracefully in non-production |
| NFR-M1 | All utilities must declare `strict_types=1` |
| NFR-M2 | All public methods must have PHPDoc blocks |

---

## 6. API / Data Contracts

### AppInfo

```php
// app/Core/Services/AppInfo.php
final class AppInfo
{
    public static function name(): string;        // composer.json name
    public static function version(): string;     // composer.json version
    public static function authorName(): string;  // composer.json author.name
    public static function authorEmail(): string; // composer.json author.email
    public static function description(): string; // composer.json description
    public static function license(): string;     // composer.json license
    public static function gitUrl(): string;      // composer.json homepage
    // All values cached 24h via Cache::rememberForever
}
```

### Environment

```php
// app/Core/Services/Environment.php
final class Environment
{
    public static function isProduction(): bool;
    public static function isTesting(): bool;
    public static function isLocal(): bool;
    public static function isCLI(): bool;
}
```

### PasswordRules

```php
// app/Core/Support/PasswordRules.php
final class PasswordRules
{
    public static function default(): array;   // ['min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']
    public static function strict(): array;    // additional rules for high-security contexts
}
```

### Color

```php
// app/Core/Support/Color.php
final class Color
{
    public static function hexToRgb(string $hex): array;
    public static function rgbToHex(int $r, int $g, int $b): string;
    public static function relativeLuminance(int $r, int $g, int $b): float;
    public static function contrastColor(string $hex): string;  // '#000000' or '#ffffff'
    public static function lighten(string $hex, float $percent): string;
    public static function darken(string $hex, float $percent): string;
    public static function computeBaseShades(string $hex): array;
    public static function computeDarkShades(string $hex): array;
}
```

### AppIntegrity

```php
// app/Core/Services/AppIntegrity.php
final class AppIntegrity
{
    public static function verify(): void;
    // Reads composer.json author.name
    // Production: throws RejectedException if attribution removed
    // Local/testing: logs warning via SmartLogger
}
```

### LangChecker

```php
// app/Core/Services/LangChecker.php
final class LangChecker extends Translator
{
    // Extends Laravel Translator
    // Intercepts missing() calls
    // Logs via SmartLogger with caller file:line
    // Does NOT prevent key resolution (returns key as fallback)
}
```

---

## 7. Design Decisions

### DD-1 — Composer.json as Single Source of Truth for Metadata

**Decision:** `AppInfo` reads from `composer.json`, not `.env` or database.
**Rationale:** `composer.json` is always present, version-controlled, and authoritative for
package metadata. Duplicating this in `.env` creates drift risk.
**Trade-off:** Cannot override individual fields without modifying `composer.json`. Acceptable —
overrides belong in settings (brand_name, site_title), not in app metadata.

### DD-2 — Graceful Degradation for Integrity Checks

**Decision:** `AppIntegrity::verify()` throws in production, warns in dev/test.
**Rationale:** Development workflows legitimately modify attribution (forking, rebranding).
Blocking development is counterproductive. Production deployments must enforce attribution.
**Trade-off:** Attribution removal in staging is not caught. Acceptable — staging is not distributed.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| AppInfo cache hit rate | > 99% | 24h TTL, rarely changes |
| Password rules consistency | 100% of password fields use `PasswordRules::default()` | `grep -r "PasswordRules" app/` |
| LangChecker overhead | < 1ms per missing key | SmartLogger write only on miss |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [tech-stack.md](tech-stack.md) | PHP 8.4, Laravel framework classes (Translator, Cache, Facades) |
| [base-classes.md](base-classes.md) | `BaseData`, `BaseEntity` contracts, `LabelEnum` interface |

### Build Guide
This spec provides the shared helpers every module uses: metadata, environment detection,
password validation, color manipulation, and integrity verification. These are consumed by
base classes, settings, branding, and all module features. No specific downstream depends
solely on this — these utilities are used broadly.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No specific downstream) | These utilities are consumed by all modules as needed |

---

## Quick References

- `app/Core/Services/AppInfo.php` — Composer metadata with 24h cache
- `app/Core/Services/Environment.php` — Environment detection helpers
- `app/Core/Support/PasswordRules.php` — Password validation rules
- `app/Core/Support/Color.php` — Color manipulation utilities
- `app/Core/Services/AppIntegrity.php` — Attribution verification
- `app/Core/Services/LangChecker.php` — Missing translation detection
- `composer.json` — Source of truth for app metadata
- **Related specs:** [tech-stack.md](tech-stack.md) — PHP/Laravel infrastructure
- **Related specs:** [base-classes.md](base-classes.md) — Architectural base classes
- **Related specs:** [logging-and-error-handling.md](logging-and-error-handling.md) — SmartLogger, PiiMasker
- **Related specs:** [csv-import-export.md](csv-import-export.md) — CsvHandler utility
- **Related specs:** [module-discovery.md](module-discovery.md) — ModuleDiscoverService
