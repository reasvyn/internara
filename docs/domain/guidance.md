# Guidance Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** (11 files) + ⏳ **Planned enhancements** (see [reference](guidance-reference.md))

## Purpose

Guidance manages handbooks and documents that users must read and acknowledge —
versioned guides, procedure manuals, policies, and internship implementation
guidelines. Each handbook pairs Markdown content with an optional downloadable PDF
for more detailed information.

---

## Design Principles

### 1. Versioned Documents

Every handbook update creates a new version. Previous versions remain accessible.

### 2. Acknowledgement Tracking

User acknowledgements are immutable — user, timestamp, and IP are recorded.

---

## Domain Boundary

The Guidance domain owns handbooks and reference documents that users must read and acknowledge during their placement program. It manages versioned handbooks with titles, URL slugs, Markdown-formatted content, optional downloadable PDF attachments, and active or inactive status. Each handbook targets a specific audience by role — all users, students only, teachers only, or supervisors only — ensuring that each group sees only the content relevant to them. The domain enforces an immutable acknowledgment system: when a user confirms they have read a handbook, the acknowledgment is permanently recorded with the user identity, exact timestamp, and IP address.

Guidance does not own user identity or profile data (User), program definitions or requirements (Internship), document templates or rendering (Document), or any operational domain data. It owns the handbook content and the record of who has acknowledged reading it. It does not manage course materials, assignment instructions, or assessment criteria — those belong to their respective operational domains.

The domain depends on User for identity in acknowledgment records and role-based audience filtering, and on Auth for role definitions used in audience targeting. For PDF storage it depends on Media Library (Spatie). It is a standalone content domain — handbooks do not reference program-specific data, and no other domain depends on guidance content for its own business logic.

---

## Key Features

- Create, update, and deactivate handbooks with a title, URL slug, Markdown content, optional PDF attachment, and version tracking.
- Upload a PDF file alongside each handbook for users who need the complete printed document.
- Display handbooks filtered by the reader's role so students, teachers, and supervisors see only their relevant documents.
- Record immutable acknowledgments when a user confirms they have read a handbook, storing the user identity, timestamp, and IP address.
- Set the target audience for each handbook to all users, students only, teachers only, or supervisors only.
- Browse a library of handbooks filtered automatically by the current user's role assignment.
- Acknowledge a handbook with a single acknowledgment button that records the timestamp permanently.
- Download the full PDF document for offline reading.
- ⏳ Read handbooks in a full-screen reading view with a table of contents sidebar generated from Markdown headings.
- ⏳ View a personal acknowledgment history listing all confirmed handbooks with acknowledgment dates.
