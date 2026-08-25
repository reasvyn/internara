# Data Flow & Lineage

> **Last updated:** 2026-08-25 **Changes:** new skill — flow & lineage for data-architect

## Intent

Data movement is explicit: from request → DTO → Action → Entity → Model → DB on write, and DB → Model → Entity → DTO → Livewire → Blade on read. Cache and events are part of the flow, not afterthoughts. Every hop has a single owner and a clear lineage.

## What it enforces

- **CQRS-inspired Action triad:** `Command` (write, `transaction()` + `log()`), `Read` (read-only, no side effects), `Process` (read+write orchestration). No Model mutation in Livewire (C1).
- **ERD-first for new relations:** Sketch ERD (tables, FK, cardinality) before migrating; keep it in module doc or ADR when cross-module.
- **Lineage:** Each field has a source (form/CSV/external) and a sink (DB, export, PDF). Untracked lineage is a spec gap.
- **Cache flow:** Keys registered in `config/cache-keys.php` (C4); writes invalidate via event → listener → `Cache::forget()` — never inline `Cache::forget()` in the Action without a dispatched event.
- **Event lineage:** Side effects go via `dispatchEvent()` (queued, after commit); never inline mail/notify/cache logic in the Command body.

## How to apply

```
Request (Livewire/FormRequest) → DTO (BaseData) → Command::execute(DTO)
  → Entity::fromModel() enforces invariants
  → Model::create/update(values from DTO)
  → $this->log() + $this->dispatchEvent() → Listener → Cache::forget() / Notify
```

For reads: `ReadAction` returns DTO/Entity; Livewire renders from DTO — no raw Model in Blade (pass via Entity/DTO).

## Pitfalls to avoid

- `Model::create($request->all())` in Livewire or controller (C1 + D5) — bypasses DTO/validation.
- `Cache::put('inline_key', ...)` without registration (C4).
- Inline `Mail::send()` inside Command without an event — breaks lineage and testability.
- ERD that lives only in a chat message — move it to `docs/modules/{module}.md` or an ADR.

## Verification

- `python3 scripts/scan_violations.py` — C1, C4 clean.
- `python3 scripts/scan_class_contracts.py` — Action/Entity/DTO contracts pass.
- `python3 scripts/scan_doc_links.py` — ERD/doc links resolve.
- Cache keys used in code exist in `config/cache-keys.php`.
