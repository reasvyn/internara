# Guidance Domain

## Purpose

Guidance manages handbooks and documents that users must read and acknowledge —
versioned guides, procedure manuals, and policies.

---

## Design Principles

### 1. Versioned Documents

Every handbook update creates a new version. Previous versions remain accessible.

### 2. Acknowledgement Tracking

User acknowledgements are immutable — user, timestamp, and IP are recorded.

---

## Models

| Model | Key Fields |
|---|---|
| `Handbook` | title, slug, content, version, is_active, target_audience |
| `HandbookAcknowledgement` | user_id, handbook_id, acknowledged_at, ip_address |

## Actions

| Action | Type |
|---|---|
| `CreateHandbookAction` | Command |
| `UpdateHandbookAction` | Command |
| `DeleteHandbookAction` | Command |
| `AcknowledgeHandbookAction` | Command |

## Where to Find It

- `app/Domain/Guidance/Models/`
- `app/Domain/Guidance/Actions/`
