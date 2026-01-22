# Shared Model Concerns: Engineering Consistency

Internara utilizes a set of shared Eloquent **Concerns** (Traits) located in the `Shared` module.
These concerns enforce system-wide standards for identity, state management, and academic scoping.

> **Spec Alignment:** These concerns implement the technical foundations for document management,
> student monitoring, and data integrity mandated by the **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. Identity: `HasUuid`

We have standardized on **UUID v4** for all primary and foreign keys across the application.

- **Security (from Specs):** Prevents ID enumeration and unauthorized data guessing.
- **Portability:** Critical for our Modular Monolith, allowing ID generation before persistence.
- **Constraint:** **No physical foreign keys** between modules. Use simple indexed UUID columns.

```php
use Modules\Shared\Models\Concerns\HasUuid;

class Internship extends Model
{
    use HasUuid;
}
```

---

## 2. State Management: `HasStatuses`

Entities follow a lifecycle (e.g., `Pending` -> `Approved` -> `Finished`).

- **Rationale:** Centralizes state transitions and validation logic.
- **Audit:** Tracks "who" and "when" for every status change, supporting the **Monitoring** goals of the specs.

```php
$registration->setStatus('approved', 'Student met all entry requirements.');
```

---

## 3. Scoping: `HasAcademicYear`

Data integrity across academic cycles is critical. The `HasAcademicYear` concern ensures that
operational data is always filtered by the active year.

- **Automatic Scoping:** Populates the `academic_year` column from `setting('active_academic_year')`.
- **Integrity:** Prevents data leak between different academic periods.

```php
use Modules\Shared\Models\Concerns\HasAcademicYear;

class JournalEntry extends Model
{
    use HasAcademicYear;
}
```

---

## 4. Multi-Language Content: `HasTranslations`

(If applicable) Used for entities requiring localized content storage (e.g., Department names, Assessment criteria).

- **Standard:** Must support both Indonesian and English as mandated by specs.

---

_By leveraging these concerns, you ensure that your module remains aligned with Internara's core
engineering principles._