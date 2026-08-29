# Domain Boundary Rule — One Business Domain, One Domain

Defines when a business domain warrants its own Domain instead of living inside a parent Module's
catch-all. This complements §1.6 (SRP & Modularity) in `docs/guides/arch/modular-pattern.md`; read
that section for the layer responsibilities table, read this file for the decomposition decision.

---

## Rule

Every distinct business domain MUST be implemented as exactly one Domain under its owning Module
(`app/Modules/{Module}/Domain/{Domain}/`), owning its full stack — Actions, Entities, Models, Livewire, Policies,
routes, translations, and tests. A domain must not be split across multiple Domains, nor collapsed
into a sibling's catch-all once it has reached Domain-scale cohesion.

## Signs a domain should become its own Domain

These are cohesion **signals**, not an exhaustive hard gate — two of the clearest:

1. **CRUD mass — 3 of 4 standard operations.** If the domain owns three or more of the four standard
   CRUD operations (Create, Read, Update, Delete) against its own entity, it has a full lifecycle and
   warrants its own Domain. Two or fewer operations may temporarily live inside a parent
   Domain's `Actions/` until a third appears, then extract.
2. **Role-scoped business operation.** If a meaningful business operation is intended for a specific
   user role (e.g., student-only submission, supervisor-only verification, admin-only grading), the
   domain it serves is cohesive enough to stand as a Domain. Role scoping is a strong cohesion
   signal, not a side detail — it also earns the domain its own Policy surface.

> These are *two of* the signs. Other signals include: a vocabulary/entity set distinct from the
> parent, an independent lifecycle or state machine, dedicated UI screens, or a need for its own
> authorization surface. When in doubt, bias toward extraction (see §1.6 "Extraction bias").

## Why

- **Cohesion (SRP):** a domain with 3+ CRUD ops or role-specific operations has several reasons to
  change and its own ubiquitous language; colocating it gives one reason to change per Domain.
- **Discoverability:** `find app/Modules/{Module}/Domain/{Domain}/Actions` lists the domain's surface in one place
  instead of scattering it across a parent's misc `Actions/`.
- **Authorization clarity:** role-scoped operations get their own Policy instead of being buried in a
  shared Policy with cross-cutting `if` branches.

## Anti-patterns

- A domain with 3+ CRUD operations hidden inside a parent Domain's `Actions/` folder.
- Splitting one domain's operations across two Domains (e.g., Create/Update in one, Read/Delete in
  another) — that fragments the single responsibility.
- A "Misc"/"Other" Domain used as a dumping ground for domains that already reached
  Domain-scale cohesion.

## Enforcement & Next Step

- Surface during `spec-audit` and code review when a Domain's `Actions/` shows 3+ CRUD ops or
  role-scoped operations that belong to a separate domain.
- When extraction is needed, follow §21 Workflow Patterns (Action/Entity Extraction) in
  `docs/guides/arch/modular-pattern.md`.
