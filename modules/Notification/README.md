# Notification Module

The `Notification` module provides a unified system for sending alerts and messages to users.

> **Spec Alignment:** This module supports the **Administrative Management** and **Communication**
> goals defined in the **[Internara Specs](../../docs/internal/internara-specs.md)**.

## Purpose

- **Communication:** Keeps Instructors, Students, and Supervisors informed about critical events.
- **Centralization:** Standardizes multi-channel alerting (Database, Email).
- **i18n:** All notification templates MUST be fully localized (ID/EN).

## Key Features

- **Global UI Bridge:** Powers the `mary-toast` notifications for real-time feedback.
- **Role-Targeted Alerts:** Notifies specific user roles based on system events (e.g., Log Approval,
  Registration Submission).
- **Email Integration:** Support for SMTP/Mailgun as mandated in Specs 10.5.

---

_The Notification module ensures that Internara remains a proactive and reactive platform._
