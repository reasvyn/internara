# Spatie Model Status: Standardized Lifecycles

To manage complex entity states (e.g., `Internship`: Applied -> Active -> Completed), Internara
utilizes the `spatie/laravel-model-status` package. This allows us to track history and reasons for
status changes without polluting our domain tables with status flags.

---

## 1. Rationale: Auditability

Unlike a simple `status` column, this package stores state changes in a separate `statuses` table.

- **Audit Trail**: You can see _who_ changed a status and _why_ (via the `reason` field).
- **History**: The entire lifecycle of a record is preserved.

---

## 2. Implementation in Modules

### 2.1 Apply the HasStatuses Concern

```php
use Spatie\ModelStatus\HasStatuses;

class InternshipRegistration extends Model
{
    use HasStatuses;
}
```

### 2.2 Setting & Transitioning

```php
$registration->setStatus('approved', 'Student passed the technical test.');

// Retrieve current state
echo $registration->currentStatus; // "approved"
```

---

## 3. Querying by Status

The concern provides powerful scopes for filtering your data grids.

```php
// Get all pending applications
$pending = InternshipRegistration::whereStatus('pending')->get();

// Get all non-archived records
$active = InternshipRegistration::whereNotStatus('archived')->get();
```

---

_By standardizing on `Model Status`, we ensure that all business lifecycles in Internara are
transparent and traceable. Refer to the **[Shared Concerns](../shared-concerns.md)** guide for
details on global status helpers._
