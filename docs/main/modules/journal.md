# Journal Module

The `Journal` module manages the daily activity tracking (Logbook) for students during their internship. It allows students to record their work, reflect on their learning, and receive verification from supervisors.

## Purpose

- **Activity Tracking:** Provides a formal record of student daily tasks and achievements.
- **Verification:** Enables a dual-authority approval process involving both School Teachers and Industry Mentors.
- **Competency Mapping:** Links daily activities to curriculum standards (Basic Competencies).
- **Reflection:** Encourages students to document learning outcomes and emotional states (Mood tracking).

## Key Features

### 1. Daily Logbook
- **Work Topics:** Categorized titles for daily tasks.
- **Detailed Descriptions:** Full log of activities performed.
- **Mood Tracking:** Capture the student's emotional state during the workday.
- **Private Attachments:** Support for photos, documents, and proof of work stored securely.

### 2. Supervision Workflow
- **Draft & Submit:** Students can save progress as drafts before final submission.
- **Dual Approval:** Either an assigned Teacher or Mentor can approve/reject entries.
- **Immutability:** Once approved, journal entries cannot be edited by students to maintain data integrity.

### 3. Visual Insights
- **Week at a Glance:** A sidebar widget providing quick visual status of the current week's completion.
- **Status Badges:** Clear indication of entry state (Draft, Submitted, Approved, Rejected).

## Architecture

- **Service Layer:** `JournalService` handles all status transitions and media orchestration.
- **Contract:** `Modules\Journal\Services\Contracts\JournalService`.
- **Media Collection:** `attachments` (stored in the `private` disk).
- **Authorization:** Managed via `JournalPolicy`, ensuring data is only accessible to the student and their assigned supervisors.

---

**Navigation** [← Back to Module TOC](../table-of-contents.md)
