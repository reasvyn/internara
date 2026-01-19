# Journal Module

The `Journal` module manages the daily activity tracking (Logbook) for students during their
internship. It allows students to record their work, reflect on their learning, and receive
verification from supervisors.

## Purpose

- **Activity Tracking:** Provides a formal record of student daily tasks and achievements.
- **Verification:** Enables a dual-authority approval process involving both School Teachers and
  Industry Mentors.
- **Competency Mapping:** Links daily activities to curriculum standards (Basic Competencies).
- **Reflection:** Encourages students to document learning outcomes.

## Key Features

### 1. Daily Logbook

- **Work Topics:** Categorized titles for daily tasks.
- **Detailed Descriptions:** Full log of activities performed.
- **Private Attachments:** Support for photos, documents, and proof of work stored securely.

### 2. Supervision Workflow

- **Draft & Submit:** Students can save progress as drafts before final submission.
- **Dual Approval:** Either an assigned Teacher or Mentor can approve/reject entries.
- **Strict Locking:** Once an entry is marked as `approved` or `verified`, it is permanently locked.
  Further updates or deletions are forbidden to ensure historical integrity.
- **Academic Scoping:** Every entry is automatically associated with the `active_academic_year`
  setting, ensuring logs are partitioned by academic period.

### 3. Visual Insights

- **Week at a Glance:** A sidebar widget providing quick visual status of the current week's
  completion.
- **Status Badges:** Clear indication of entry state (Draft, Submitted, Approved, Rejected).

## Architecture

- **Service Layer:** `JournalService` handles all status transitions and media orchestration.
- **Contract:** `Modules\Journal\Services\Contracts\JournalService`.
- **Media Collection:** `attachments` (stored in the `private` disk).
- **Authorization:** Managed via `JournalPolicy`, ensuring data is only accessible to the student
  and their assigned supervisors.
