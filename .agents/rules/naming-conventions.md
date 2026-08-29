# Naming Convention Checks — Files, Classes, Methods, Variables

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Names are contracts: they encode module, role, and layer so code, docs, and scanners agree without
reading bodies. `scan_naming.py` enforces these conventions; a name that breaks the pattern hides the
component's role and breaks greppability across the repo. This is the enforcement-side of the naming
rules — for per-element rationale and intent, see `code-writing/rules/naming-conventions.md`.

---

## File Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `{Verb}{Entity}Action.php` | `StoreStudentAction.php` |
| Entities | `{Entity}.php` (singular) | `Student.php` |
| DTOs | `{Entity}Data.php` or `{Action}Request.php` | `StudentData.php` |
| Enums | `{Description}{Type}Enum.php` | `StudentStatusEnum.php` |
| Models | `{Entity}.php` (singular) | `Student.php` |
| Livewire | `{Action}{Entity}{Layer}.php` | `StoreStudentForm.php` |
| Policies | `{Entity}Policy.php` | `StudentPolicy.php` |
| Events | `{PastTenseVerb}{Entity}Event.php` | `StudentCreatedEvent.php` |
| Listeners | `{Event}Listener.php` | `StudentCreatedListener.php` |
| Migrations | `{timestamp}_{description}.php` | `2026_01_01_000001_create_students_table.php` |
| Tests | `{Module}/{SubModule}/{Described}Test.php` | `tests/Academics/AcademicYear/DeleteAcademicYearActionTest.php` |

**Intent:** The filename tells any reader (and any scanner) exactly which layer and role it
represents without opening the file.

**Why it matters:** Ambiguous or mis-suffixed filenames collide across modules, defeat role
recognition, and break the automation layer (`scan_*` scripts, `spec-audit` path checks) that
locates resources by name pattern.

**Anti-patterns to avoid:** `StoreStudentController.php` for a Livewire component; plural entity
files (`Students.php`); `UpdateAction.php` without the entity; tests not placed under
`tests/{Module}/{SubModule}/`.

---

## Class Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `{Verb}{Entity}Action` | `StoreStudentAction` |
| Entities | `{Entity}` (singular) | `Student` |
| DTOs | `{Entity}Data` or `{Action}Request` | `StudentData` |
| Enums | `{Description}{Type}Enum` | `StudentStatusEnum` |
| Models | `{Entity}` (singular) | `Student` |
| Livewire | `{Action}{Entity}{Layer}` | `StoreStudentForm` |
| Policies | `{Entity}Policy` | `StudentPolicy` |
| Events | `{PastTenseVerb}{Entity}Event` | `StudentCreatedEvent` |
| Listeners | `{Event}Listener` | `StudentCreatedListener` |

**Intent:** The class name is the irreducible handle used across imports, tests, factories, and docs.
It must differ from the filename only by the `.php` extension.

**Why it matters:** A class name that diverges from its filename breaks PSR-4 autoloading (the class
loads only if both match) — a silent `Class not found` waiting to happen.

**Anti-patterns to avoid:** `Student` entity class named `StudentEntity` in `app/.../Entities/`;
events named with a verb imperative (`CreateStudent`) instead of past-tense facts
(`StudentCreatedEvent`); enums not carrying the `Enum` suffix.

---

## Method Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Action execute | `execute()` (single public) | `execute(): ActionResponse` |
| Entity questions | `is{Question}(): bool` | `isEligibleForCertification(): bool` |
| Entity fromModel | `fromModel(Model $model): static` | `fromModel(Student $student): static` |
| Entity toArray | `toArray(): array` | `toArray(): array` |
| Model entity | `entity(): {Entity}Entity` | `entity(): StudentEntity` |
| LabelEnum label | `label(): string` | `label(): string` |
| StatusEnum transitions | `validTransitions(): array` | `validTransitions(): array` |
| Test methods | `describe("{SpecID}: Test description...")` grouping + `test("{SpecID}-{ReqID}: Test description...")` | `test("{SpecID}-{ReqID}: Test description...")` |

**Intent:** Method names encode the contract the runner depends on — a booleans question on an
Entity is `isX()`; a mutation entry point on an Action is `execute()`; state transitions on a
StatusEnum are `validTransitions()`.

**Why it matters:** Renaming `execute()` to `run()` breaks the Action Triad contract, every test, and
`scan_class_contracts.py`. Entity questions that don't start with `is`/`can`/`has` are unsearchable
as domain rules.

**Test method convention:** every `test()` name opens with the spec + requirement ID and is grouped
under a `describe()` of the spec ID, so the suite maps 1:1 to spec requirements (spec-driven
testing). This makes coverage mechanical to verify (`spec-audit` relies on it).

**Anti-patterns to avoid:** `handle()` instead of `execute()`; `validTransition()` (singular) vs the
plural `validTransitions()`; test names with no `{SpecID}-{ReqID}:` prefix.

---

## Variable / Property Naming

| Pattern | Convention | Example |
|---------|-----------|---------|
| Actions | `$action` | `$action = new StoreStudentAction()` |
| Entities | `$entity` | `$entity = StudentEntity::fromModel($student)` |
| DTOs | `$data` or `$request` | `$data = new StudentData(...)` |
| Models | `$model` | `$model = Student::findOrFail($id)` |
| Collections | `$items` or plural | `$students = Student::all()` |
| Boolean | `$is{State}` / `$has{Thing}` | `$isActive`, `$hasPermission` |

**Intent:** Local variables follow the type-role names so a reader can tell what a variable *is*
from a glance and does not need to trace its type.

**Why it matters:** Booleans named `$active` are ambiguous (an int flag? a timestamp? an object?); a
`$data` that is actually a Model confuses the DTO boundary. Consistent names make static analysis and
reviews cheaper.

**Anti-patterns to avoid:** `$student_data` (snake_case) — use camelCase `$studentData`;
`$results_array` — use `$results`; `$this->request` in Actions — use `$this->dto` or `$this->data`.

---

## Naming Anti-Pattern Detector

The scanner flagging a name is not noise — each replace-with is the canonical fix:

| Anti-Pattern | Replace With |
|-------------|-------------|
| `handle()` method | `execute()` |
| `$this->request` in Actions | `$this->dto` or `$this->data` |
| `App\Models\{Model}` in Entity | Import at `use` statement, keep Entity clean |
| `$student_data` (snake_case) | `$studentData` (camelCase) |
| `$results_array` | `$results` |
| `getData()` | Property access on DTO |

**Why each anti-pattern matters:**

- `handle()` is the queue/controller convention, not the Action convention — an Action with
  `handle()` has a second, non-canonical entry point.
- `$this->request` inside an Action suggests the Action reached for the HTTP request directly (D5-
  adjacent) instead of working from its DTO.
- A fully-qualified `App\Models\Model` inline inside an Entity is a C5 import leak written inside the
  method body; the fix is to import nothing (or import the allowed domain type) at the top.
- `snake_case` locals violate the camelCase variable convention and read as config keys.
- `getData()` on a `final readonly` DTO violates immutability — the property is public-read anyway.

---

## Verification

```bash
python3 tools/scan_naming.py                # file/class/method/variable naming conventions
python3 tools/scan_naming.py --module {Name}   # scope to a module
```

**Interpretation guidance:** naming findings are **MEDIUM** severity (maintainability impact) unless a
wrong name changes behavior — e.g., a filename/classname mismatch that breaks autoloading is
**HIGH**. Do not "fix" a naming finding by renaming the *scanner rule* — the rule encodes a documented
convention; align the code.