# Guidance Domain
> Last updated: 2026-05-26
> Changes: fix: enforce super admin integrity with SuperAdminIntegrityRules across all code paths


## Purpose

Guidance manages handbooks and guidance documents that students, teachers, and supervisors
must read and acknowledge during their internship lifecycle. Documents like the internship
handbook, code of conduct, and safety guidelines are distributed digitally — this domain tracks
who has read and acknowledged each document version.

## Boundary

**In scope:** Handbook management (create, update, delete, activate/deactivate), role-targeted
handbook assignment via `target_audience` field (all, student, teacher, supervisor), student
acknowledgement workflow (view and mark as read), immutable acknowledgement audit trail (who
acknowledged which handbook, when, and from what IP address).

**Out of scope:** Template-based document generation (Document domain), certificate issuance
(Certificate domain), internship report generation (Internship domain), file upload and storage
(media library handles raw file storage — Guidance manages metadata and acknowledgement).

## Key Concepts

**Handbooks.** A handbook is a versioned content item — currently stored as Markdown text in
the database — that users are encouraged to read and acknowledge. Each handbook has a title,
slug, content, version number, active/inactive status, and a `target_audience` field that
determines which roles can see it (all, student, teacher, or supervisor). Handbooks can be
created as drafts (inactive, not visible to non-admin users) or published (active, visible to
the target audience). Admins can edit handbooks after creation.

**Acknowledgements.** An acknowledgement records that a specific user read and acknowledged a
specific handbook at a specific time. Each acknowledgement captures: the user's identity, the
handbook reference, the timestamp of acknowledgement, and the IP address. Acknowledgements are
append-only — they can be created but not modified or deleted, providing an audit trail.

**Target Audience.** Each handbook has a `target_audience` field restricting visibility to
specific roles: `all` (visible to everyone), `student`, `teacher`, or `supervisor`. Students
see handbooks targeted at `all` or `student`; teachers see `all` or `teacher`; supervisors see
`all` or `supervisor`. Admin users see all handbooks regardless of target audience.

**Acknowledgement is not a gate.** Currently, acknowledgement is purely informational — it does
not block registration, attendance, logbook, or any other action. This can be changed in the
future by adding gating logic.

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to create and publish handbooks so that users can read guidance materials
- **Admin:** As an admin, I want to target handbooks to specific roles so that students see student-relevant content and teachers see teacher-relevant content
- **Student/Teacher/Supervisor:** As a user, I want to view handbooks relevant to my role so that I can read and understand policies
- **Student/Teacher/Supervisor:** As a user, I want to acknowledge a handbook so that my reading compliance is recorded
- Acknowledgements are immutable — they permanently record the user, handbook, timestamp, and IP address
- Handbooks can be edited after creation; previous versions are not preserved
- Only `super_admin` can delete handbooks; admins can create and edit

### Process Flow

```
Admin creates/publishes handbook → User views handbook → User acknowledges (immutable)
```

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateHandbookAction` | Creates a new handbook |
| `UpdateHandbookAction` | Updates an existing handbook |
| `DeleteHandbookAction` | Deletes a handbook |
| `AcknowledgeHandbookAction` | Records a user's acknowledgement of a handbook |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Handbook` (title, slug, content, version, is_active, target_audience), `HandbookAcknowledgement` (user_id, handbook_id, acknowledged_at, ip_address) |
| **Entity** | `HandbookPublishState` (published status check) |
| **Enums** | *(none — target_audience is a string column with `in:all,student,teacher,supervisor` validation)* |
| **Livewire** | `HandbookIndex` (admin CRUD), `StudentHandbookIndex` (user view + acknowledge) |
| **Policy** | `HandbookPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| User | User identity for acknowledgement records and role filtering |
| Core | BaseAction, BaseModel, SmartLogger |
