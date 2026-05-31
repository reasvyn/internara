# Assignment Domain

## Purpose

Assignment manages task-based learning — teachers create tasks, students submit work,
teachers grade submissions.

---

## Design Principles

### 1. Draft Before Submit

Students can save work as drafts without submitting. Submission is an explicit action, not
an automatic side effect of saving. This allows progressive work without premature grading.

### 2. Revision Loop

Submitted work can be returned for revision. A teacher may request changes, returning the
submission to draft state. The student revises and resubmits. This loop can repeat multiple
times without losing version history.

### 3. Deadline Enforcement with Flexibility

Due dates are enforced — late submissions are flagged. However, teachers can grant
extensions without modifying the original deadline. The original deadline remains visible
alongside any granted extension.

---

## Domain Boundary

The Assignment domain owns task-based learning — the creation, distribution, submission, and grading of assignments within a placement program. Teachers create assignments with titles, detailed descriptions, due dates, resource references, point values, and grading rubrics. Students submit their work as text, uploaded files, or both, with a draft workflow that allows saving progress before final submission. Submissions move through a lifecycle from draft through submitted, verified, and graded, with an optional return-for-revision loop back to draft. Grading includes numeric scores, rubric-referenced feedback, and written commentary. The system tracks deadlines, flags late submissions, supports deadline extensions, and maintains version history — every save and submission is versioned for audit purposes.

Assignment does not own student identity data (User/Mentee), program definitions (Internship), assessment rubrics (Assessment), or course material content (Guidance). It manages the task and its submissions, but the grading criteria (rubrics) belong to the Assessment domain and the learning content belongs to other domains. Assignment owns the workflow from task creation through submission to grading, but not the evaluation framework that defines what good work looks like.

The domain depends on Internship for program context, on User for student and teacher identity, and on Assessment for rubric references used in grading. It provides the assignment infrastructure consumed by the Mentee dashboard and the Mentor supervision tools.

---

## Key Features

- Create, update, and delete assignments with title, description, due dates, resource links, point values, and grading criteria.
- Submit assignment work as text, uploaded files, or both, with a draft workflow allowing progress saving before final submission.
- Grade student submissions with numeric scores, rubric-referenced feedback, and written commentary.
- Track submission status through a lifecycle of draft, submitted, verified, and graded, with optional return-for-revision.
- Monitor submission deadlines, flag late work, and grant deadline extensions when needed.
- Preserve version history of every save and submission for audit and review.
- Browse a list of assignments with due date badges showing upcoming, due today, and overdue states.
- Save assignment work as a draft with an auto-save indicator showing the last-saved timestamp.
- Upload submission files via drag and drop with a preview of attached files before final submission.
- View submission status transitions on a timeline with color-coded badges for draft, submitted, verified, and graded.
- Grade submissions via an inline grading panel with a rubric reference, score input, and feedback text area.
