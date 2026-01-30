# Log Module

The `Log` module is responsible for monitoring system events and auditing user activities.

## Purpose

- **Auditing:** Tracks "who", "what", and "when" for all critical business actions.
- **Observability:** Supports the monitoring goals of the internship lifecycle.
- **Privacy:** Enforces **PII Masking** in all system logs.

## Key Features

- **Activity Logging:** Uses UUID-based identity for all audit trails.
- **Traceability:** Correlates logs with `user_id` and `academic_year`.
- **i18n:** Log descriptions and audit labels must support translation.

---

_The Log module provides the accountability and transparency required for academic legitimacy._
