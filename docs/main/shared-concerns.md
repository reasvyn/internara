# Shared Model Concerns: Engineering Consistency

Internara utilizes a set of shared Eloquent **Concerns** (Traits) located in the `Shared` module.
These concerns enforce system-wide standards for identity, state management, and academic scoping,
ensuring that all modules behave predictably.

---

## 1. Identity: `HasUuid`

We have standardized on **UUID v4** for all primary and foreign keys across the application.

### Why UUIDs?

- **Security**: Prevents ID enumeration attacks (e.g., guessing `/student/1` -> `/student/2`).
- **Decoupling**: Allows modules to generate IDs before persistence, crucial for distributed or
  event-driven systems.
- **Portability**: Makes merging data from different environments (e.g., local to production)
  risk-free.

### Implementation

Simply apply the concern to your model. It automatically handles ID generation and disables
incrementing integers.

```php
use Modules\Shared\Models\Concerns\HasUuid;

class Internship extends Model
{
    use HasUuid;
}
```

---

## 2. State Management: `HasStatuses`

Most domain entities in Internara have a lifecycle (e.g., `Registration`: Pending -> Approved ->
Active -> Finished). Instead of hardcoding boolean flags, we use a flexible status system.

### Rationale

- **Audit Trail**: The system tracks _when_ a status changed and _who_ changed it.
- **Extensibility**: You can add new states without modifying the database schema.
- **Consistency**: Centralizes state logic (validation, transitions) in one place.

### Usage Example

```php
$registration->setStatus('approved', 'Student met all entry requirements.');

// Retrieve records by status
$pendingOnes = InternshipRegistration::currentStatus('pending')->get();
```

---

## 3. Academic Scoping: `HasAcademicYear`

Data integrity across academic cycles is critical. The `HasAcademicYear` concern ensures that
operational data (like Journals and Attendance) is always filtered by the active year.

### How it works

- **Auto-Injection**: Automatically populates the `academic_year` column from the global application
  settings during model creation.
- **Global Scope**: (Optional) Can be used to restrict queries to only show data for the current
  session.

```php
use Modules\Shared\Models\Concerns\HasAcademicYear;

class JournalEntry extends Model
{
    use HasAcademicYear;
}
```

---

_By leveraging these concerns, you ensure that your module remains aligned with Internara's core
engineering principles. Always check `modules/Shared/src/Models/Concerns` for new additions._
