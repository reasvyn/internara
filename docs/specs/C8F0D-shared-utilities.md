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

An SMK operator once opened the about page, the certificate footer, and the health report side by side and found three different version strings, because each screen had parsed `composer.json` on its own. Reading metadata now goes through one path: code calls `AppInfo::name()`, `AppInfo::version()`, or `AppInfo::authorName()` — or the `app_info('name')` helper — and `AppInfo` reads `composer.json` once, then serves the values from a registered cache key for 24 hours. Every module sees the same strings with zero repeated file reads, a behavior owned jointly by FR-UTIL-001, FR-UTIL-002, and FR-UTIL-003.

#### UC-UTIL-002 — System Verifies Application Attribution

A vocational school in Cirebon once received a USB installer from a neighboring school with all author credits stripped and the version string edited to look like a paid fork. That is the shape `AppIntegrity::verify()` exists to catch. On startup or when an admin triggers the check on an already deployed application, the routine reads the `composer.json` author name and compares it against the expected attribution. In production a mismatch throws `RejectedException`, so the redistributed copy fails loudly instead of running silently, while in local or testing the same mismatch only logs a SmartLogger warning and lets development continue. Either path leaves unauthorized redistribution detected and reported, with the strict behavior owned by FR-UTIL-008 and the rejection semantics following the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md).

#### UC-UTIL-003 — Developer Reads Setting / Brand Values

Once the settings infrastructure is seeded, a call like `setting('key')` or `brand('key')` from `app/Modules/Settings/Support/helpers.php` travels a fixed path: the helper resolves the value through the cache-backed, group-scoped contract owned by [settings-infrastructure](YB22J-settings-infrastructure.md), never by querying the `Setting` model directly from another module. This spec asserts only that those two helpers exist and remain the single access vocabulary, so no direct `Setting` reads leak outside the settings module. The behavior itself — caching, grouping, fallbacks — is exercised by the owning spec's tests, which is why this row carries `—` and defers its code-testable consequence to [settings-infrastructure](YB22J-settings-infrastructure.md).

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
| FR-UTIL-004 | `Environment` helpers: `isDebugMode()`, `isDevelopment()`, `isLocal()`, `isTesting()`, `isCLI()`, `isStaging()`, `isMaintenance()`, `isProduction()` in `app/Modules/Core/Support/Environment.php` | P0 | U | Full |
| FR-UTIL-005 | `PasswordRules::default()` — 8+ chars, mixed case, numbers | P0 | U | Full |
| FR-UTIL-006 | `PasswordRules::strict()` — additional rules for high-security contexts | P1 | U | Full |
| FR-UTIL-007 | `Color` — `hexToRgb()`, `rgbToHex()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, `computeDarkShades()` | P0 | U | Full |
| FR-UTIL-008 | `AppIntegrity::verify()` checks the `composer.json` author name; throws in production, warns in dev/test | P0 | F | Full |
| FR-UTIL-009 | `LangChecker` extends Laravel `Translator`, logs missing translation keys with caller file/line via SmartLogger, and still returns the key fallback | P1 | F | Full |

### 4.1 App Metadata

#### FR-UTIL-001 — AppInfo reads composer.json with 24h cache

A school technician once deleted the `description` field from `composer.json` while cleaning up the deploy, and the about page white-screened because the old helper threw on a missing key. `AppInfo` refuses to repeat that: the seven accessors `name()`, `version()`, `authorName()`, `authorEmail()`, `description()`, `license()`, and `gitUrl()` in §6 each return a sane default when their optional field is absent, so a trimmed manifest still renders. Only the author-name check stays strict, because FR-UTIL-008 needs it for attribution. The feature test proves both halves by asserting cached values match `composer.json` and that no file re-read occurs within TTL, at layer `F`.

#### FR-UTIL-002 — app_info() global helper

Early modules each reached into `AppInfo` differently — one called `AppInfo::all()`, another guessed at `get()` — until the helper consolidated the vocabulary. Now `app_info()` returns the full metadata array, `app_info('name')` returns a single key, and `app_info('missing', $default)` falls back to the supplied default, all defined in `app/Modules/Core/Support/helpers.php` behind `function_exists` so package overrides never fatal. The feature test walks all three call shapes to lock that vocabulary in at layer `F`.

#### FR-UTIL-003 — Registered cache keys (C4)

Let an inline `'cache_key'` string slip into one helper and the failure arrives months later: version bumps stop invalidating because two keys spell the same cache differently, and the certificate footer shows last semester's release. The [cache pattern](../guides/arch/cache-pattern.md) forbids that drift outright — the AppInfo key lives in `config/cache-keys.php`, and any future invalidation runs listener-driven per the [gradual-migration ADR](../adr/adr-gradual-migration.md) without touching call sites. `scan_violations.py` enforces the C4 invariant at layer `A`, so a stray literal fails the gate before it ships.

### 4.2 Environment Detection

#### FR-UTIL-004 — Environment predicates

An SMK operator in Semarang once ran a production backup from artisan and the job sent test notifications to all parents, because the code had branched on `! app()->environment('production')` and CLI had slipped through the negation. The eight predicates in `app/Modules/Core/Support/Environment.php` remove that trap: `isDebugMode()`, `isDevelopment()`, `isLocal()`, `isTesting()`, `isCLI()`, plus `isStaging()`, `isMaintenance()`, and `isProduction()` for the positive checks callers actually need — an artisan command running in production is honestly both CLI and production, no negation required. (> Decision 2026-09-11: the earlier five-predicate text predated `isStaging()`/`isMaintenance()` and wrongly claimed `isProduction()` was renamed; corrected to the implemented API.) Unit tests pin each predicate at layer `U`.

### 4.3 Validation & Presentation Helpers

#### FR-UTIL-005 — Default password rules

Every password field resolves its rules through `PasswordRules::default()`, which returns `['min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']` at call time — eight characters minimum with mixed case and a digit — so validation behaves identically whether the request comes from registration, reset, or admin creation. Any deviation from that baseline needs a recorded reason, not a local copy-paste. A unit test asserts the exact rule set and `grep -r "PasswordRules" app/` shows universal adoption, holding the line at layer `U`.

#### FR-UTIL-006 — Strict password rules

Superadmin recovery is the edge that justifies a second tier: the same eight-character baseline would technically pass, yet a recovery slip photographed on a staff-room desk deserves a harder secret. `PasswordRules::strict()` extends the default set for those high-security contexts, and owning specs reference this variant instead of inventing local rules that drift on the next audit. A unit test locks the extended set at layer `U`, so the stricter path cannot silently regress to default.

#### FR-UTIL-007 — Color math

Branding started as ad-hoc hex arithmetic scattered across Blade files, with every school computing its own lighten and darken by hand. The `Color` helper ended that by fixing one deterministic vocabulary — `hexToRgb()`, `rgbToHex()`, `relativeLuminance()`, `contrastColor()`, `lighten()`, `darken()`, `computeBaseShades()`, and `computeDarkShades()` — where `contrastColor()` returns white or black across the relative-luminance threshold and the two shade builders derive the whole self-hosted palette from a single brand hex. Malformed hex is rejected rather than collapsing to silent black, while shorthand like `#fff` is normalized before conversion. Unit tests cover known conversions, the threshold boundary, and the shade-table shape at layer `U`.

### 4.4 Integrity & i18n Support

#### FR-UTIL-008 — Attribution verification

If the attribution check failed open in production, a stripped redistribution would boot cleanly and the project would never know; if it failed closed in development, every forked pilot would crash on first boot. The rule splits the consequence: in production a removed attribution throws `RejectedException` as a business-rule violation per the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md), while local and testing only log a SmartLogger warning and continue. `verify()` even catches its own infrastructure exceptions and degrades gracefully outside production per NFR-UTIL-002, so a broken check never takes down a dev boot. Feature tests walk both the production-throw and dev-warn paths at layer `F`.

#### FR-UTIL-009 — Missing-translation detection

During a demo at an SMK in Yogyakarta, the attendance page rendered a raw `attendance.check_in` key in front of parents because the Indonesian translation had never been written and nothing had complained. `LangChecker` makes that silence impossible: it intercepts `missing()` calls, logs the absent key with its caller file and line through SmartLogger, and still returns the key as fallback so the request that hit the gap keeps working. The feature test asserts both halves — the log entry appears and the fallback returns — at layer `F`.

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

At runtime `AppInfo` reads `composer.json` once and serves every later call from cache for 86400 seconds, a full 24 hours, because version and attribution never change between deploys. The AppInfo feature test asserts that TTL directly, and since metadata rarely changes the hit rate is effectively total — the file read happens once per day, not once per request.

#### NFR-UTIL-002 — Graceful integrity degradation

Picture a corrupted `composer.json` on a developer laptop the morning of a school pilot — the integrity check itself throws while trying to read the author name. Outside production that must never take down the boot, so `verify()` swallows its own infrastructure exceptions and degrades, holding the target of zero dev-boot failures caused by the check. The feature test forces an internal exception and asserts boot continues, proving the guard never becomes the outage.

#### NFR-UTIL-003 — Strict types everywhere

Shared helpers are called from everywhere, so a silent string-to-int coercion in `Color::darken()` would surface three modules away as a wrong shade nobody can trace. The D1 invariant answers that history: every utility file declares `strict_types=1`, all one hundred percent of them, and `scan_conventions.py` proves it at layer `A`.

#### NFR-UTIL-004 — Documented public API

Undocumented helpers rot into tribal knowledge — the next developer reimplements `contrastColor()` because nobody knew what the threshold meant. Requiring PHPDoc on one hundred percent of public methods keeps each helper self-describing, and the review gate plus `scan_conventions.py` at layer `A` makes a missing block a visible failure instead of a quiet gap.

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

An SMK in Bandung once showed version `1.4.0` in `.env` while `composer.json` said `1.3.2`, and nobody knew which screen to believe during a bug report. That drift is why `AppInfo` reads from `composer.json` alone, never from `.env` or the database — the manifest is always present, version-controlled, and authoritative for package metadata. The cost is that individual fields cannot be overridden without editing `composer.json`, which is acceptable because display overrides belong in settings as `brand_name` and `site_title`, not in app metadata.

#### DD-UTIL-002 — Graceful Degradation for Integrity Checks

At runtime `AppIntegrity::verify()` takes two different exits from the same comparison: in production a stripped attribution throws and stops the boot, while in development or testing it logs a warning and continues, because forking and rebranding are legitimate workflows that must not be blocked. Production alone enforces attribution. The gap is that a stripped staging copy slips through uncaught, which is acceptable because staging is never distributed.

#### DD-UTIL-003 — Registry-Owned Cache Keys

Consider the second cached utility after `AppInfo`: its author invents a key inline, and six months later nobody can answer who owns that cache or what invalidates it. Registering every key up front in `config/cache-keys.php` per FR-UTIL-003 prevents that orphan, following the Start → Stabilize → Final invalidation path in the [gradual-migration ADR](../adr/adr-gradual-migration.md). One registry keeps ownership auditable and lets invalidation graduate to listener-driven without touching call sites, at the small price that each new cached utility registers its key before first use.

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
