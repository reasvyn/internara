# Data Contract Writing — §6 API/Data Contracts

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

§6 API/Data Contracts is the interface layer of the spec — the exact shapes that `spec-audit` Area 2
compares against real code. A contract written in prose ("the system stores student data") gives the
implementer freedom to guess and the auditor nothing to check. Contracts must be written so a class
signature either matches or it doesn't.

---

## What §6 Must Contain

- **Exact class signatures, method signatures, config arrays**
- **Data types for all properties**
- **All enum cases if applicable**
- **Reference to source files with paths** where implementation exists
- **Route definitions with middleware**

---

## Why precision is non-negotiable

`spec-audit` Area 2 (Contract Verification) does three things against §6:

1. **Class existence** — does the class exist at the referenced path?
2. **Class declaration** — is it `final readonly` (Entity/DTO)? `extends BaseCommandAction` (Action)?
3. **Method signature** — does `execute()` match the spec'd signature (param types, return type)?

A contract written as "a LoginAction with login capability" cannot answer any of those three checks.
A contract written as `execute(string $email, string $password): ActionResponse` either matches the
code or generates a `C-1`/`C-2` drift finding.

---

## How to write a verifiable contract

```markdown
## 6. API / Data Contracts

### Actions

`LoginAction extends BaseCommandAction`
- `execute(LoginData $data): ActionResponse`
  - `LoginData` (extends `BaseData`): `email: string`, `password: string`

### Entities

`StudentEntity` (final readonly)
- `fromModel(Student $student): static`
- `isEligibleForCertification(): bool`

### DTOs

`CreateStudentData` (extends `BaseData`): `name: string`, `nisn: string`, `schoolId: int`

### Enums

`StudentStatusEnum: string implements LabelEnum, StatusEnum`
- cases: `draft`, `active`, `graduated`

### Routes

| Method | URI | Controller/Livewire | Middleware |
|--------|-----|---------------------|------------|
| POST | `/login` | LoginAction (via route) | `guest`, `throttle:5,1` |

### Config

`config/attendance.php` keys: `grace_minutes` (int), `clock_out_required` (bool)
```

**Rules applied:**

- Signatures name types explicitly — `execute(LoginData $data): ActionResponse`, not
  `execute($data)`.
- Properties show types — `email: string`.
- Enum cases are enumerated so the auditor can match `app/.../Enums/`.
- Routes include method, URI, handler, and middleware (the middleware is what `spec-audit` verifies
  for S4/S5/S7).
- Config keys are named and typed so `spec-audit` can grep for their registration.

---

## Anti-patterns / Pitfalls

- **Contract as prose** ("authenticates a user and returns a token") — nothing to diff against code.
- **Copying a stock signature without the return type** — `execute(...)` without `: ActionResponse`
  fails the return-type check.
- **Missing enum cases** — the auditor compares the case list; missing cases become false drift.
- **Omitted middleware on routes** — security FRs (rate limiting, auth) can't be verified.
- **Contradicting existing code** — before writing a signature, verify against actual code or a DD
  explaining the deliberate divergence; otherwise `spec-audit` flags `C-1` (spec ahead) immediately.
- **Duplicating contracts across two specs** — cross-reference instead (Clean Code/Dedup doctrine);
  a duplicated contract can be edited in one spec and silently drift from the other.

---

## Verification / Detection

After writing §6:

- Every referenced class name resolves to a real file under `app/` (or is marked as new with a DD).
- Every signature has typed params and a return type.
- Every DTO/Entity matches its architectural contract (final readonly, BaseData, fromModel, purity).
- Every route lists middleware; every config key is named and typed.
- `spec-audit` Area 2 runs clean on the contracts; any `C-*` finding means the contract or the code
  drifted and must be resolved per the decision matrix.