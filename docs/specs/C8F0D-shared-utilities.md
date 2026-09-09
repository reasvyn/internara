# Shared Utilities — Cross-Cutting Helpers & Services

> **Spec ID:** C8F0D

## Description

Cross-cutting utility classes used by multiple modules: app metadata (`AppInfo`), environment
detection (`Environment`), password validation (`PasswordRules`), color math (`Color`),
attribution verification (`AppIntegrity`), and missing-translation detection (`LangChecker`).
These are shared helpers, not architectural foundations or infrastructure configuration.

SmartLogger, CsvHandler, ModuleService, and ModuleManager have their own dedicated specs and are
cross-referenced here only. The `setting()` / `brand()` global helpers are contracted in
[settings-infrastructure](YB22J-settings-infrastructure.md); the `app_info()` helper is
contracted here. Base classes live in [base-classes](SE5Q9-base-classes.md).

---

## 1. Problem Statements

### PS-1 — Duplicated Helper Logic

Without shared utilities, each module reimplements common operations: reading app metadata,
detecting environment, validating passwords, converting colors. This leads to inconsistent
behavior (one module uses 8-char passwords, another uses 12) and wasted effort.
**→ Requirement:** FR-UTIL-001/005/007 (AppInfo, PasswordRules, Color), FR-UTIL-004 (Environment).

### PS-2 — Application Integrity Verification

A self-hosted application distributed via Git must verify that the deployment matches the
expected attribution. Unauthorized redistribution (removing author credits) must be detectable
in production while allowing development flexibility.
**→ Requirement:** FR-UTIL-008 (AppIntegrity), DD-UTIL-002.

### PS-3 — Silent Localization Gaps

Missing translation keys resolve to the raw key with no signal, so Indonesian-locale gaps ship
unnoticed. A development-time detector that logs the missing key with its caller location keeps
both locales complete without changing resolution behavior.
**→ Requirement:** FR-UTIL-009 (LangChecker).

---

## 2. Goals & Non-Goals

### Goals

- **Provide `AppInfo` plus the `app_info()` helper** — centralized `composer.json` metadata with a 24h cache and a global accessor. *Why:* every module needs consistent version/attribution data with zero repeated file reads.
- **Provide `Environment` detection helpers** — `isDebugMode`, `isDevelopment`, `isLocal`, `isTesting`, `isCLI`. *Why:* one predicate vocabulary keeps environment branching consistent instead of scattered `app()->environment()` string checks.
- **Provide `PasswordRules` defaults** — one 8+ char mixed-case-numeric baseline plus a strict variant. *Why:* password policy must be identical everywhere it is enforced.
- **Provide `Color` math** — hex/RGB conversion, luminance, contrast, lighten/darken, and self-hosted palette shade generation. *Why:* branding and theme code needs deterministic color derivation, not ad-hoc hex arithmetic.
- **Provide `AppIntegrity` attribution verification** — throws in production, warns elsewhere. *Why:* redistribution detection must not block legitimate development workflows.
- **Provide `LangChecker` missing-key detection** — logs the missing key with caller file/line, still returns the key fallback. *Why:* gaps become visible in logs without breaking the request that hit them.

### Non-Goals

- **SmartLogger and PiiMasker**. *Why:* specified in [logging-and-error-handling](89SRA-logging-and-error-handling.md); this spec only consumes them.
- **CsvHandler**. *Why:* specified in [csv-import-export](O2KCR-csv-import-export.md).
- **ModuleService and ModuleManager**. *Why:* specified in [module-discovery](I1BCV-module-discovery.md) and [module-manager](B114U-module-manager.md).
- **Base classes (Action, Entity, DTO, Model)**. *Why:* specified in [base-classes](SE5Q9-base-classes.md).
- **`setting()` and `brand()` helper contracts**. *Why:* specified in [settings-infrastructure](YB22J-settings-infrastructure.md) — see UC-UTIL-003 for the cross-reference row.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-UTIL-001 | Developer reads app metadata (version, attribution) with zero repeated file reads | P0 | F | Full |
| UC-UTIL-002 | System verifies application attribution on startup or admin trigger | P0 | F | Full |
| UC-UTIL-003 | Developer reads a setting or brand value via the `setting()` / `brand()` helpers contracted in settings-infrastructure | P1 | — | — |

### 3.1 Metadata, Integrity & Settings Access

#### UC-UTIL-001 — Developer Reads App Metadata

**Actor:** Developer / System
**Preconditions:** `composer.json` exists with author info.
**Flow:**
1. Code calls `AppInfo::name()`, `AppInfo::version()`, `AppInfo::authorName()` — or `app_info('name')`
2. `AppInfo` reads `composer.json` once, caches for 24h under a registered cache key
3. Subsequent calls return cached values
**Postconditions:** Consistent metadata across all modules, zero repeated file reads.
**Governing guidance:** FR-UTIL-001/002/003.

#### UC-UTIL-002 — System Verifies Application Attribution

**Actor:** System (startup or admin trigger)
**Preconditions:** Application deployed.
**Flow:**
1. `AppIntegrity::verify()` reads the `composer.json` author name
2. Compares against expected attribution
3. In production: throws `RejectedException` if attribution removed
4. In local/testing: logs warning via SmartLogger
**Postconditions:** Unauthorized redistribution detected and reported.
**Governing guidance:** FR-UTIL-008; [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md) for the `RejectedException` semantics.

#### UC-UTIL-003 — Developer Reads Setting / Brand Values

**Actor:** Developer
**Preconditions:** Settings infrastructure seeded.
**Flow:**
1. Code calls `setting('key')` or `brand('key')` from `app/Modules/Settings/Support/helpers.php`
2. Values resolve per the settings-infrastructure contract (cache-backed, group-scoped)
3. This spec asserts only that the helpers exist and are the single access path — behavior is verified in [settings-infrastructure](YB22J-settings-infrastructure.md)
**Postconditions:** No direct `Setting` model reads outside the settings module; one access vocabulary.
**Governing guidance:** [settings-infrastructure](YB22J-settings-infrastructure.md) (contract owner). This row carries `—` because its code-testable consequence is verified there, not here.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-UTIL-001 | `AppInfo` reads `composer.json` metadata (name, version, author, license, description, git URL) with a 24h cache | P0 | F | Full |
| FR-UTIL-002 | The `app_info()` global helper returns all metadata (`AppInfo::all()`) or a single key (`AppInfo::get($key, $default)`); required by QLHDO §7.4 | P0 | F | Full |
| FR-UTIL-003 | `AppInfo` cache entries use keys registered in `config/cache-keys.php` (C4 invariant) — no inline cache-key strings | P0 | A | Full |
| FR-UTIL-004 | `Environment` helpers: `isDebugMode()`, `isDevelopment()`, `isLocal()`, `isTesting()`, `isCLI()` in `app/Modules/Core/Support/Environment.php` | P0 | U | Full |
| FR-UTIL-005 | `PasswordRules::default()` — 8+ chars, mixed case, numbers | P0 | U | Full |
| FR-UTIL-006 | `PasswordRules::strict()` — additional rules for high-security contexts | P1 | U | Full |
| FR-UTIL-007 | `Color` — `hexToRgb()`, `rgbToHex()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, `computeDarkShades()` | P0 | U | Full |
| FR-UTIL-008 | `AppIntegrity::verify()` checks the `composer.json` author name; throws in production, warns in dev/test | P0 | F | Full |
| FR-UTIL-009 | `LangChecker` extends Laravel `Translator`, logs missing translation keys with caller file/line via SmartLogger, and still returns the key fallback | P1 | F | Full |

### 4.1 App Metadata

#### FR-UTIL-001 — AppInfo reads composer.json with 24h cache

- Accessors: `name()`, `version()`, `authorName()`, `authorEmail()`, `description()`, `license()`, `gitUrl()` — see §6.
- **Edge case:** a missing optional field returns a sane default rather than throwing; only the author-name check (FR-UTIL-008) is strict.
- **Verification:** feature test asserts cached values match `composer.json` and no file re-read occurs within TTL (layer `F`).

#### FR-UTIL-002 — app_info() global helper

- `app_info()` → full metadata array; `app_info('name')` → single key; `app_info('missing', $default)` → default. Defined in `app/Modules/Core/Support/helpers.php` behind `function_exists`.
- **Verification:** feature test covers all three call shapes (layer `F`).

#### FR-UTIL-003 — Registered cache keys (C4)

- Per the [cache pattern](../guides/arch/cache-pattern.md): inline `'cache_key'` strings are forbidden; the AppInfo key lives in `config/cache-keys.php` and invalidation (if ever needed) is listener-driven per the [gradual-migration ADR](../adr/adr-gradual-migration.md).
- **Verification:** `scan_violations.py` C4 check (layer `A`).

### 4.2 Environment Detection

#### FR-UTIL-004 — Environment predicates

- `isProduction()` was renamed to `isDevelopment()` to better describe the local/dev environment check; callers branch on the positive predicate they mean, never on negated environment strings.
- **Edge case:** CLI context (`isCLI()`) composes with the others — artisan commands running in production are production *and* CLI.
- **Verification:** unit test per predicate with environment overrides (layer `U`).

### 4.3 Validation & Presentation Helpers

#### FR-UTIL-005 — Default password rules

- `['min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']` — every password field uses this baseline; deviations require a recorded reason.
- **Verification:** unit test asserts the rule set; `grep -r "PasswordRules" app/` shows universal adoption (layer `U`).

#### FR-UTIL-006 — Strict password rules

- Extends the default set for high-security contexts (e.g., superadmin recovery); owning specs reference this variant instead of inventing local rules.
- **Verification:** unit test (layer `U`).

#### FR-UTIL-007 — Color math

- `contrastColor()` returns white or black depending on the relative-luminance threshold — light backgrounds get dark text and vice versa. `computeBaseShades()` / `computeDarkShades()` derive the self-hosted palette from one brand hex.
- **Edge case:** malformed hex input is rejected (no silent black); shorthand (`#fff`) is normalized before conversion.
- **Verification:** unit tests over known conversions, the luminance threshold boundary, and shade-table shape (layer `U`).

### 4.4 Integrity & i18n Support

#### FR-UTIL-008 — Attribution verification

- Production: throws `RejectedException` (business-rule violation per the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md)) when attribution is removed. Local/testing: logs a SmartLogger warning and continues.
- **Edge case:** `verify()` catches its own infrastructure exceptions and degrades gracefully outside production (NFR-UTIL-002) — a broken check must never take down a dev boot.
- **Verification:** feature tests for the production-throw and dev-warn paths (layer `F`).

#### FR-UTIL-009 — Missing-translation detection

- Intercepts `missing()` calls; logs via SmartLogger with caller file:line; does NOT prevent key resolution (returns key as fallback).
- **Verification:** feature test asserts the log entry and the fallback return (layer `F`).

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-UTIL-001 | `AppInfo` metadata cache TTL | 86400s (24h) | P0 | F | Full |
| NFR-UTIL-002 | `AppIntegrity::verify()` catches exceptions and degrades gracefully in non-production | 0 dev-boot failures caused by the check | P0 | F | Full |
| NFR-UTIL-003 | All utilities declare `strict_types=1` | 100% of files (D1) | P0 | A | Full |
| NFR-UTIL-004 | All public methods carry PHPDoc blocks | 100% of public methods | P1 | A | Full |

### 5.1 Performance, Resilience & Style

#### NFR-UTIL-001 — 24h metadata cache

- **Verification:** TTL assertion in the AppInfo feature test; cache-hit rate is effectively total since metadata rarely changes.

#### NFR-UTIL-002 — Graceful integrity degradation

- **Verification:** feature test forces an internal exception and asserts boot continues outside production.

#### NFR-UTIL-003 — Strict types everywhere

- **Verification:** `scan_conventions.py` D1 check (layer `A`).

#### NFR-UTIL-004 — Documented public API

- **Verification:** review gate + `scan_conventions.py` (layer `A`).

---

## 6. API / Data Contracts

### 6.1 AppInfo

```php
// app/Modules/Core/Services/AppInfo.php
final class AppInfo
{
    public static function name(): string;        // composer.json name
    public static function version(): string;     // composer.json version
    public static function authorName(): string;  // composer.json author.name
    public static function authorEmail(): string; // composer.json author.email
    public static function description(): string; // composer.json description
    public static function license(): string;     // composer.json license
    public static function gitUrl(): string;      // composer.json homepage
    // All values cached 24h under keys registered in config/cache-keys.php (FR-UTIL-003)
}
```

### 6.2 app_info() Global Helper

```php
// app/Modules/Core/Support/helpers.php
if (! function_exists('app_info')) {
    function app_info(?string $key = null, mixed $default = null): mixed;
    // app_info()          → AppInfo::all() — full metadata array
    // app_info('name')    → AppInfo::get('name') — single metadata key
}
```

> **Note:** QLHDO §7.4 lists three global helpers. `app_info()` (FR-UTIL-002) is contracted
> here; `setting()` and `brand()` live in `app/Modules/Settings/Support/helpers.php` and are
> contracted in [settings-infrastructure](YB22J-settings-infrastructure.md) — see UC-UTIL-003.

### 6.3 Environment

```php
// app/Modules/Core/Support/Environment.php
final class Environment
{
    public static function isDebugMode(): bool;
    public static function isDevelopment(): bool;
    public static function isLocal(): bool;
    public static function isTesting(): bool;
    public static function isCLI(): bool;
}
```

### 6.4 PasswordRules

```php
// app/Modules/Core/Support/PasswordRules.php
final class PasswordRules
{
    public static function default(): array;   // ['min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']
    public static function strict(): array;    // additional rules for high-security contexts
}
```

### 6.5 Color

```php
// app/Modules/Core/Support/Color.php
final class Color
{
    public static function hexToRgb(string $hex): array;
    public static function rgbToHex(int $r, int $g, int $b): string;
    public static function relativeLuminance(int $r, int $g, int $b): float;
    public static function contrastColor(string $hex): string;  // '#1a1a1a' (light bg) or '#f0f0f0' (dark bg)
    public static function lighten(string $hex, float $percent): string;
    public static function darken(string $hex, float $percent): string;
    public static function computeBaseShades(string $hex): array;
    public static function computeDarkShades(string $hex): array;
}
```

### 6.6 AppIntegrity

```php
// app/Modules/Core/Services/AppIntegrity.php
final class AppIntegrity
{
    public static function verify(): void;
    // Reads composer.json author.name
    // Production: throws RejectedException if attribution removed
    // Local/testing: logs warning via SmartLogger
}
```

### 6.7 LangChecker

```php
// app/Modules/Core/Services/LangChecker.php
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

Design Decisions are **optional** to test, like Use Cases (§3). These are recorded decisions,
not test rows, so `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-UTIL-001 | Composer.json as single source of truth for app metadata | P0 | — | — |
| DD-UTIL-002 | Graceful degradation for integrity checks | P0 | — | — |
| DD-UTIL-003 | Utility cache entries live in the shared cache-key registry | P1 | — | — |

### 7.1 Sources of Truth

#### DD-UTIL-001 — Composer.json as Single Source of Truth for Metadata

**Decision:** `AppInfo` reads from `composer.json`, not `.env` or database.
**Rationale:** `composer.json` is always present, version-controlled, and authoritative for
package metadata. Duplicating this in `.env` creates drift risk.
**Trade-off:** Cannot override individual fields without modifying `composer.json`. Acceptable —
overrides belong in settings (brand_name, site_title), not in app metadata.

#### DD-UTIL-002 — Graceful Degradation for Integrity Checks

**Decision:** `AppIntegrity::verify()` throws in production, warns in dev/test.
**Rationale:** Development workflows legitimately modify attribution (forking, rebranding).
Blocking development is counterproductive. Production deployments must enforce attribution.
**Trade-off:** Attribution removal in staging is not caught. Acceptable — staging is not distributed.

#### DD-UTIL-003 — Registry-Owned Cache Keys

**Decision:** `AppInfo` caching uses keys registered in `config/cache-keys.php` (FR-UTIL-003),
following the Start → Stabilize → Final invalidation path in the
[gradual-migration ADR](../adr/adr-gradual-migration.md).
**Rationale:** One registry means cache ownership is auditable and invalidation can move to
listener-driven without touching call sites.
**Trade-off:** A new cached utility must register its key before use — a small, owned ceremony.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| AppInfo cache hit rate | > 99% | 24h TTL, rarely changes |
| Password rules consistency | 100% of password fields use `PasswordRules::default()` | `grep -r "PasswordRules" app/` |
| LangChecker overhead | < 1ms per missing key | SmartLogger write only on miss |
| Inline cache-key strings in utilities | 0 | `scan_violations.py` C4 check |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [tech-stack](FB792-tech-stack.md) | PHP 8.4, Laravel framework classes (Translator, Cache, Facades) |
| [base-classes](SE5Q9-base-classes.md) | `BaseData`, `BaseEntity` contracts, `LabelEnum` interface |

### Build Guide

This spec provides the shared helpers every module uses: metadata, environment detection,
password validation, color manipulation, and integrity verification. These are consumed by
base classes, settings, branding, and all module features. No specific downstream depends
solely on this — these utilities are used broadly.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | (No specific downstream) | These utilities are consumed by all modules as needed |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume `setting()` / `brand()` behavior stays contracted in [settings-infrastructure](YB22J-settings-infrastructure.md); this spec asserts only the access path, not the semantics | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — layer model and C4/D1 invariants
- [Tech stack](FB792-tech-stack.md) — PHP 8.4 and Laravel classes these helpers build on
- [Base classes](SE5Q9-base-classes.md) — contracts these utilities complement
- [Settings infrastructure](YB22J-settings-infrastructure.md) — owner of `setting()` / `brand()`
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger consumed by AppIntegrity and LangChecker
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` semantics for FR-UTIL-008
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — cache-invalidation phases behind DD-UTIL-003
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — §7.4 global helpers this spec's rows serve
