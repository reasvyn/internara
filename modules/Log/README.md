# Log Module

The `Log` module is responsible for monitoring system events and auditing user activities. it
provides transparency and accountability across the entire application.

## Purpose

- **Auditing:** Tracks who performed what action and when.
- **Observability:** Monitors significant system events for debugging and security.
- **Accountability:** Provides a history of data changes.

## Key Features

- **Activity Logging**: Integration with `spatie/laravel-activitylog` to track model changes and
  custom events.
- **System Monitoring**: Captures critical logs for administrative review.
