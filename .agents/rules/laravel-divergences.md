# Laravel Divergences — Internara Conventions over Stock Laravel

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive divergences rule

Internara deliberately differs from stock Laravel in these ways:

| Stock Laravel | Internara |
|---------------|-----------|
| `app/Models/` for all models | Models live in `app/{Module}/{SubModule}/Models/` |
| `app/Http/Livewire/` for all components | Components live in `app/{Module}/{SubModule}/Livewire/` |
| `app/Policies/` for all policies | Policies live in `app/{Module}/{SubModule}/Policies/` |
| Services for business logic | Actions (Command/Read/Process) for business logic |
| FormRequest classes for validation | Livewire Form Objects (`Livewire\Form`) for validation |
| Array parameters | `BaseData` DTO for 3+ params |
| `$fillable` / `$guarded` | `#[Fillable]` attribute on every Model |
| `Storage::put()` for file uploads | Spatie MediaLibrary only |

**When in doubt, follow Internara conventions, not stock Laravel.**

**Why this matters:** Tutorials, generated code, and AI training data default to stock-Laravel layout
and patterns. Copying them here produces off-convention modules that fail `scan_class_contracts.py`
and confuse module colocation. The internara convention is the architecture contract.

**How to apply:** When implementing or reviewing, check the Internara column first. If a well-known
stock pattern conflicts (e.g., FormRequest vs Form Object), Internara wins. When unsure, load the
module's conceptual/reference doc and the pattern doc.

**Pitfalls to avoid:**
- Creating a model in `app/Models/` "just for simplicity".
- Reaching for a `Service` class when the task is an Action's job.
- Using FormRequest validation in a Livewire feature.