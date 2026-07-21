# Roadmap — Development Direction

> **Last updated:** 2026-07-21 **Changes:** sync — remove Guidance module from known issues

---

## Guiding Principle

**Stabilize before expanding.** The project has 19 modules with most of the stack in place.
The priority is making what exists work correctly and reliably — not adding new features.

New features should only be considered when there is a concrete, verified need from core
stakeholders (students, teachers, school admins). "Nice to have" is not a reason to build.

---

## Current State

The architecture is sound: 4-layer model, Action Triad, Entity boundaries, DTO contracts.
Most modules have their full stack (Models, Actions, Livewire, Events, Policies, Routes).

But several modules have runtime errors from schema mismatches, missing relationships,
or incorrect method calls. Test coverage is uneven. Some modules have structural gaps
(missing Entity layers, empty Livewire directories, Actions that don't follow the triad).

The project is functional but not yet production-stable across all modules.

---

## Direction

### 1. Fix What's Broken

Runtime errors take priority over everything. A module that crashes when a user clicks
a button is not shippable, regardless of how good the architecture around it is.

Focus areas:
- Schema mismatches between migrations and code references
- Missing model relationships and undefined methods
- Blade rendering errors (multiple root elements, array casting issues)
- Actions that return raw models instead of ActionResponse

### 2. Complete What's Incomplete

Some modules have structural gaps — missing Entity layers, empty Livewire directories,
or Actions that bypass the triad pattern. These gaps make the modules fragile and
hard to maintain.

Focus areas:
- Modules that skip the Entity layer (business rules living in Actions or Models)
- Actions that don't return ActionResponse (violates C7)
- Modules with dead code: unused DTOs, unregistered observers, events without listeners
- The Evaluation module (models only, no business logic layer)

### 3. Harden What Works

Stable modules need test coverage, documentation accuracy, and convention compliance
before they can be considered production-ready.

Focus areas:
- Test coverage for domain modules (Assessment, Evaluation, Certification, Document)
- Documentation sync — ensure module docs match actual code
- Convention compliance across all modules (strict_types, `__()` for user strings, no debug calls)
- Policy correctness — ensure role-based access matches route middleware

### 4. Resist Feature Creep

The existing feature set covers the full PKL lifecycle. Before building anything new,
verify that the need is real and the existing modules can't already handle it.

Questions to ask before adding features:
- Is there a stakeholder requesting this, or is it assumed?
- Can the existing modules handle this use case with minor adjustments?
- Does this require new infrastructure, or does it fit the current architecture?
- What is the maintenance cost of this feature across 19 modules?

### 5. Polish the Experience

Once modules are stable and complete, focus on usability:

- Consistent form icons and input patterns across all modules
- Dark mode correctness (not just toggling — actual visual consistency)
- Responsive layouts that work on tablets (common in Indonesian schools)
- Localization completeness — all user-facing strings in `lang/id/`
- Meaningful error messages instead of stack traces

---

## Module Priorities

Modules are grouped by what they need, not by an arbitrary maturity score.

### Fix Runtime Errors

These modules have crashes or data corruption issues that prevent normal use:

- **Document** — non-existent columns referenced in code, SQL errors
- **Certification** — schema mismatches, missing migration columns
- **Assessment** — Blade rendering errors, relationship issues

### Complete the Structure

These modules have the foundation but are missing key architectural pieces:

- **Evaluation** — models exist but no Actions, Entities, Livewire, or Routes
- **Reports** — empty Livewire directory, no Entities, being purified from thesis concepts
- **Incident** — no Entities layer, no Events

### Harden and Verify

These modules work but need verification, testing, and cleanup:

- **Journals** — wrong user_id in attendance creation, undefined method
- **Assignment** — runtime crash, ActionResponse gaps
- **Program** — dead DTOs, documentation drift
- **Partners** — event dispatch violations, service locator usage
- **Enrollment** — broken Blade template, DTO gaps
- **Incident** — ActionResponse gaps

### Stable

These modules are reliable and well-tested:

- **Core**, **Auth**, **User**, **Settings**, **Setup**, **SysAdmin**, **Academics**

---

## What Production Readiness Looks Like

Not a milestone, but a description of the state the project should reach:

- Every module can be used end-to-end without runtime errors
- Every Action returns ActionResponse
- Every mutation path goes through an Action (no direct Model mutations in Livewire)
- Every user-facing string is translatable
- Every module has tests covering its critical paths
- Documentation matches code for every module
- PHPStan passes at level 8
- No hardcoded secrets, no debug calls in production code

When the project reaches this state consistently across all modules, it can be considered
mature. The version number at that point is an implementation detail, not a goal.

---

## Quick References

- [Project Overview](foundation/project-overview.md) — current state, module landscape
- [Architecture](architecture.md) — 4-layer model, Action Triad, conventions
- [Conventions](conventions.md) — coding standards, invariants C1-C8, D1-D6
- [Module Index](modules/index.md) — all 19 modules with links
