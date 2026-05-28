# Assignment Domain

## Purpose

Assignment manages task-based learning — teachers create tasks, students submit work,
teachers grade submissions.

---

## Models

| Model | Key Fields |
|---|---|
| `Assignment` | title, description, due_date, internship_id, assignment_type_id, is_mandatory |
| `Submission` | assignment_id, registration_id, content, status, score |
| `AssignmentType` | name, slug, group |

## Actions

| Action | Type |
|---|---|
| `CreateAssignmentAction` | Command |
| `UpdateAssignmentAction` | Command |
| `DeleteAssignmentAction` | Command |
| `PublishAssignmentAction` | Command |
| `SubmitAssignmentAction` | Command |
| `GradeSubmissionAction` | Command |
| `VerifySubmissionAction` | Command |

## Where to Find It

- `app/Domain/Assignment/Models/`
- `app/Domain/Assignment/Actions/`
