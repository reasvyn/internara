# Spatie Activitylog: Modular Audit Trails

Internara utilizes the `spatie/laravel-activitylog` package to implement robust, system-wide
auditing. This integration ensures that every critical business action is tracked, providing a
detailed history of "Who did what, and when."

---

## 1. Modular Implementation

To maintain isolation, we have encapsulated the activity log logic within the `Log` module.

### 1.1 Custom Model (`Modules\Log\Models\Activity`)

We extend Spatie's base activity model to support our modular standards:

- **UUID Support**: The `id` primary key is configured as a UUID.
- **Module Attribute**: Added a `module` column to identify which domain triggered the log.
- **Casting**: The `model_id` is cast to `string` to support polymorphic relations with UUID-based
  models.

---

## 2. Enabling Logs in your Module

To track changes in your domain models, follow these steps:

### 2.1 Apply the LogsActivity Concern

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Internship extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
```

---

## 3. Manual Logging

For actions that aren't tied to a specific model (e.g., "User exported a report"), use the activity
helper:

```php
activity()
    ->performedOn($report)
    ->causedBy(auth()->user())
    ->withProperties(['format' => 'pdf'])
    ->log('exported');
```

---

_Refer to the **[Log Module README](../../../modules/Log/README.md)** for details on how to display
these audit trails in the administrative dashboard._
