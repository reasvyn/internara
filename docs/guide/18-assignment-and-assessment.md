# Chapter 18: Assignment & Assessment

> **Last updated:** 2026-06-16

This chapter covers two related features: **Assignments** (tasks and submissions with grading) and
**Assessment** (rubric-based competency evaluation and final scoring).

---

## 18.1 Assignment Overview

Assignments are tasks created by teachers and scoped to an internship program. Students submit their
work through a draft-submit workflow, and teachers grade submissions with scores and feedback.

| Role | What They Can Do |
|------|------------------|
| **Admin / Teacher** | Create, publish, edit, delete assignments; grade submissions |
| **Student** | View assignments, submit work, receive grades and feedback |

---

## 18.2 Managing Assignments (Teacher/Admin)

Navigate to **Internship → Assignments** or go directly to `/admin/assignments`.

### 18.2.1 Creating an Assignment

1. Click **New Assignment**
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Title** | Assignment name | Laporan PKL 2025/2026 |
| **Type** | Category (e.g., report, task, presentation) | Laporan Akhir |
| **Internship Program** | The program this assignment belongs to | PKL 2025/2026 |
| **Description** | Detailed instructions | |
| **Due Date** | Submission deadline | 30 June 2026 |
| **Mandatory** | Whether this assignment is required | Yes |

3. Click **Save** — the assignment is created in **Draft** status
4. To make it visible to students, click **Publish**

### 18.2.2 Assignment Status Lifecycle

```
Draft → Published → Closed
```

| Status | Meaning | Students Can Submit? |
|--------|---------|----------------------|
| **Draft** | Being set up, not visible to students | No |
| **Published** | Visible and open for submissions | Yes |
| **Closed** | No longer accepting submissions | No |

### 18.2.3 Editing an Assignment

1. Find the assignment in the table
2. Click **Edit**
3. Update the fields — changing the due date affects existing submission deadlines
4. Click **Save**

### 18.2.4 Deleting an Assignment

An assignment can be deleted only if it has **no submissions**. If students have already submitted
work, close the assignment instead of deleting it.

### 18.2.5 Bulk Operations

Select multiple assignments to perform bulk actions:
- **Delete Selected** — removes assignments without submissions

---

## 18.3 Student Submissions

### 18.3.1 Submitting Work

1. Navigate to **Student Portal → Assignments** from the sidebar
2. Browse the list of published assignments
3. Click **View Details** on an assignment
4. Write your response and optionally attach a file:

| Field | Description |
|-------|-------------|
| **Response** | Your answer or report (minimum 20 characters) |
| **File** | Optional attachment (PDF, DOC, DOCX, ZIP, PPT, PPTX — max 10 MB) |

5. Click **Submit** — the submission is sent for grading

### 18.3.2 Submission Rules

- You can only submit **once per assignment**
- Late submissions are flagged but accepted
- You must have an active internship registration to submit

### 18.3.3 Viewing Grades

After your teacher grades the submission, you can view the score and feedback on the same page.
The submission status changes to **Graded**.

---

## 18.4 Grading Submissions (Teacher)

1. Navigate to **Supervision → Submissions Grading** or **Teacher → Submissions Grading**
2. The table shows submissions awaiting grading from students assigned to you
3. Click **Grade** on a submission to open the grading panel

| Field | Description |
|-------|-------------|
| **Score** | Numeric score (0–100) |
| **Feedback** | Written feedback (minimum 10 characters) |
| **Action** | Grade (mark as complete) or Request Revision |

### 18.4.1 Grade vs Request Revision

| Action | Effect |
|--------|--------|
| **Grade** | Marks submission as Graded with score and feedback — terminal state |
| **Request Revision** | Sends submission back to student for revision — student can edit and resubmit |

### 18.4.2 Submission Status Lifecycle

```
Draft ── Submit ──> Submitted ── Grade ──> Graded
                              └── Request Revision ──> Revision Required ──> Draft
```

| Status | Meaning |
|--------|---------|
| **Draft** | Student is still writing |
| **Submitted** | Sent for grading |
| **Graded** | Teacher has assigned a score and feedback |
| **Revision Required** | Teacher requested changes |

---

## 18.5 Assessment Overview

Assessment is the competency evaluation framework for internship programs. Schools build custom
rubrics with competencies and indicators, and evaluators (teachers and supervisors) score students
against them.

### 18.5.1 Key Concepts

| Term | Definition | Example |
|------|------------|---------|
| **Rubric** | Evaluation template with weighted competencies | PKL Assessment 2025/2026 |
| **Competency** | A skill or attribute being evaluated | Work Attitude, Technical Skills |
| **Indicator** | A measurable aspect within a competency | Discipline, Teamwork, Punctuality |
| **Evaluator Role** | Who scores this competency | Teacher, Supervisor, or both |

---

## 18.6 Building Rubrics (Admin/Teacher)

Navigate to **Assessment → Rubrics** or go directly to `/admin/assessments/rubrics`.

### 18.6.1 Creating a Rubric

1. Click **Add Rubric**
2. Enter a name and description
3. Click **Save**

### 18.6.2 Adding Competencies

A rubric contains one or more competencies. Each competency has:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Competency name | Work Attitude |
| **Weight** | Percentage of total rubric score | 40% |
| **Evaluator Role** | Who should score this | Teacher, Supervisor, or System |
| **Order** | Display order | 1 |

Competency weights must add up to 100% across the rubric.

### 18.6.3 Adding Indicators

Each competency contains one or more indicators:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Indicator name | Discipline |
| **Max Score** | Maximum possible score | 100 |
| **Weight** | Weight within this competency | 50% |
| **Order** | Display order | 1 |

### 18.6.4 Editing Rubric Structure

You can add, edit, or remove competencies and indicators at any time. Changes do not affect
already-finalized assessments because scores are stored as snapshots.

---

## 18.7 Grading Assessments (Evaluator)

1. Navigate to **Assessment → Grade** from the admin sidebar, or use the direct grading link
   for a specific student registration
2. The grading interface shows competencies and indicators that you are authorized to score

### 18.7.1 Scoring Indicators

Enter a numeric score for each indicator you are responsible for. The system validates:
- Score must be between 0 and the indicator's max score
- You can only score competencies assigned to your role

### 18.7.2 Auto-Import Scores

Click **Auto-Import** to pull in scores from:
- Assignment submissions (average score)
- Logbook completion rate

These are suggestions — you can override them manually.

### 18.7.3 Finalizing an Assessment

Once all scores are entered, click **Finalize**. The system:
- Calculates the weighted total score across all scored competencies
- Normalizes weights if some competencies were not scored (e.g., supervisor unavailable)
- Records the final score and locks the assessment

A finalized assessment **cannot be edited**. Corrections require a new assessment round.

### 18.7.4 Cross-Role Proxy

The assessment supports the Cross-Role Proxy model (see [ADR-014](../adr/adr-cross-role-proxy.md)):

- **Teacher** scores competencies assigned to `teacher` role
- **Supervisor** scores competencies assigned to `supervisor` role
- If the supervisor is unavailable, the teacher can **proxy** as supervisor — scoring their
  competencies, providing feedback, and performing any supervisor action. The action is logged
  with `proxy_role = 'supervisor'` in the audit trail.
- If no proxy acts and the supervisor has not scored, their weight is redistributed to the
  teacher's components
- **Admin** can proxy for both teacher and supervisor roles
- System-scored competencies are auto-calculated (e.g., attendance rate)

---

## 18.8 Viewing Assessment Results

### 18.8.1 For Students

1. Navigate to **Student Portal → My Assessments** from the sidebar
2. View finalized assessment results with scores per competency and overall score

### 18.8.2 For Teachers/Admin

Navigate to **Assessment → View Assessments** to browse all assessment records.

---

## 18.9 Troubleshooting

### Assignment "AssignmentType" error

If you see an error about `AssignmentType` not found, this means the assignment types have not
been set up. Contact your system administrator.

### Cannot publish an assignment

Only **Draft** assignments can be published. If the status is something else, create a new
assignment or edit the existing one.

### Student says they cannot see an assignment

Check that:
- The assignment is **Published** (not Draft)
- The student has an active internship registration in the same program
- The assignment belongs to the student's program

### No rubric appears when grading

A rubric must be created and assigned to the internship program. Navigate to **Assessment →
Rubrics** to create one. If a rubric exists but is not appearing, check that it is set to
**Active**.

### Cannot finalize an assessment

Common reasons:
- No competencies have been scored
- The assessment has already been finalized
- There is no rubric assigned

### Assessment score seems incorrect

Finalized assessments cannot be edited. If the score is wrong, create a new assessment round with
corrected scores. The original assessment remains as a historical record.

---

**← Previous: [Chapter 17: Monitoring Visit & Supervision Log](17-monitoring-visit-and-supervision-log.md)**
**Next: [Chapter 19: Student Report & Certification](19-student-report-and-certification.md)**
