# Domain Boundary Rule — One Business Domain, One Domain

Defines when a business domain warrants its own Domain instead of living inside a parent Module's
catch-all. This complements §1.6 (SRP & Modularity) in `docs/guides/arch/modular-pattern.md`; read
that section for the layer responsibilities table, read this file for the decomposition decision.

---

## Rule

A Module's **primary domain** (the domain that shares the Module's name) **MAY stay flat** at
`app/Modules/{Module}/` (`Actions/`, `Models/`, `Livewire/` ... langsung di Module) untuk menghindari kedalaman
berlebihan (`Assessment/Domain/Assessment` yang mubazir).

Setiap **domain bisnis yang berbeda** dari domain utama — yang memiliki identitas, lifecycle, atau
kepentingan role yang distinct — **MUST** diimplementasikan sebagai tepat satu Domain di
`app/Modules/{Module}/Domain/{Domain}/`, owning its full stack — Actions, Entities, Models, Livewire, Policies,
routes, translations, dan tests. Domain yang berbeda tidak boleh dipecah ke beberapa Domain, maupun
dilebur ke flat catch-all Module setelah mencapai skala Domain.

> **Intinya:** flat di level Module diizinkan agar tidak terlalu dalam; yang wajib di `Domain/` adalah
> domain yang sudah beda identitas/bounded-context dari Module.

## Signs a domain should become its own Domain

These are cohesion **signals**, not an exhaustive hard gate — two of the clearest:

1. **CRUD mass — 3 of 4 standard operations.** Jika domain yang **berbeda** dari Module memiliki tiga
   atau lebih dari empat operasi CRUD standar (Create, Read, Update, Delete) atas entitasnya sendiri,
   ia punya lifecycle penuh dan wajib Domain-nya sendiri. Domain utama Module yang flat boleh tetap
   flat; dua operasi atau kurang di Module flat dapat tinggal sementara sampai yang ketiga muncul.
2. **Operasi bisnis role-scoped.** Jika operasi bermakna ditujukan untuk role spesifik (mis. student-only
   submission, supervisor-only verification, admin-only grading) dan **berbeda bounded-context** dari
   domain utama, domain tersebut cukup kohesif untuk jadi Domain sendiri. Role scoping adalah sinyal
   kohesi kuat — juga memberinya Policy surface sendiri.

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

- Domain berbeda dengan 3+ CRUD tersembunyi di flat `Actions/` Module (seharusnya sudah `Domain/`).
- Membelah satu domain (yang sama) ke dua Domain (`Create/Update` di satu, `Read/Delete` di lain) — memecah SRP.
- `Domain` "Misc"/"Other" sebagai tempat buang domain yang sudah matang.

## Enforcement & Next Step

- Surface during `spec-audit` and code review when a Domain's `Actions/` shows 3+ CRUD ops or
  role-scoped operations that belong to a separate domain.
- When extraction is needed, follow §21 Workflow Patterns (Action/Entity Extraction) in
  `docs/guides/arch/modular-pattern.md`.
