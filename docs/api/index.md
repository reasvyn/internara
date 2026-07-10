# API Reference — HTTP Endpoints & Integration

> **Last updated:** 2026-07-10 **Changes:** initial — comprehensive API endpoint reference

## Description

Complete HTTP API reference for Internara. All routes are web-based (session-authenticated) unless
marked as public. Internara does not expose a RESTful JSON API for external consumption — it is a
monolithic web application. Internal JSON responses are used only for Livewire interactions and
the certificate verification endpoint.

---

## Authentication

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/login` | Guest | Login form |
| POST | `/login` | Guest | Authenticate (email/username + password) |
| POST | `/logout` | Auth | Terminate session |
| GET | `/forgot-password` | Guest | Password reset request form |
| POST | `/forgot-password` | Guest | Send reset link |
| GET | `/reset-password/{token}` | Guest | Password reset form |
| POST | `/reset-password` | Guest | Execute password reset |
| GET | `/recover-account` | Guest | Account recovery form |
| POST | `/recover-account` | Guest | Redeem recovery code |
| GET | `/confirm-password` | Auth | Confirm password form |
| POST | `/confirm-password` | Auth | Re-authenticate |

---

## Dashboard

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/dashboard` | Auth | Role-based dashboard redirect |
| GET | `/admin/dashboard` | Admin | Admin dashboard |
| GET | `/teacher/dashboard` | Teacher | Teacher dashboard |
| GET | `/supervisor/dashboard` | Supervisor | Supervisor dashboard |
| GET | `/student/dashboard` | Student | Student dashboard |

---

## User & Profile

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/user/profile` | Auth | Profile editor |
| PUT | `/user/profile` | Auth | Update profile |
| POST | `/user/profile/avatar` | Auth | Upload avatar |
| GET | `/user/notifications` | Auth | Notification center |
| POST | `/user/notifications/{id}/read` | Auth | Mark notification read |
| POST | `/user/notifications/read-all` | Auth | Mark all notifications read |

---

## SysAdmin — User Management

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/sysadmin/users` | Admin | User index |
| POST | `/sysadmin/users` | Admin | Create user |
| GET | `/sysadmin/users/{user}` | Admin | Show user |
| PUT | `/sysadmin/users/{user}` | Admin | Update user |
| POST | `/sysadmin/users/{user}/lock` | Admin | Lock user account |
| POST | `/sysadmin/users/{user}/unlock` | Admin | Unlock user account |
| POST | `/sysadmin/users/{user}/mark-alumni` | Admin | Mark student as alumni |
| POST | `/sysadmin/users/bulk` | Admin | Bulk create users |
| GET | `/sysadmin/admins` | Super Admin | Admin account index |
| POST | `/sysadmin/admins` | Super Admin | Create admin account |
| GET | `/sysadmin/students` | Admin | Student manager |
| GET | `/sysadmin/teachers` | Admin | Teacher manager |
| GET | `/sysadmin/supervisors` | Admin | Supervisor manager |

### Announcements

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/sysadmin/announcements` | Admin | Announcement index |
| POST | `/sysadmin/announcements` | Admin | Create announcement |
| GET | `/sysadmin/announcements/{announcement}` | Admin | Show announcement |
| PUT | `/sysadmin/announcements/{announcement}` | Admin | Update announcement |
| POST | `/sysadmin/announcements/{announcement}/publish` | Admin | Publish announcement |

### Audit Log

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/sysadmin/audit-log` | Admin | Audit log index |

---

## Academics

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/academics/school` | Admin | School profile editor |
| PUT | `/academics/school` | Admin | Update school profile |
| GET | `/academics/departments` | Admin | Department index |
| POST | `/academics/departments` | Admin | Create department |
| PUT | `/academics/departments/{department}` | Admin | Update department |
| DELETE | `/academics/departments/{department}` | Admin | Delete department (guarded) |
| GET | `/academics/academic-years` | Admin | Academic year index |
| POST | `/academics/academic-years` | Admin | Create academic year |
| PUT | `/academics/academic-years/{academicYear}` | Admin | Update academic year |

---

## Partners

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/partners/companies` | Admin | Company index |
| POST | `/partners/companies` | Admin | Create company |
| GET | `/partners/companies/{company}` | Admin | Show company |
| PUT | `/partners/companies/{company}` | Admin | Update company |
| GET | `/partners/partnerships` | Admin | Partnership index |
| POST | `/partners/partnerships` | Admin | Create partnership |
| PUT | `/partners/partnerships/{partnership}` | Admin | Update partnership |

---

## Program

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/program/internships` | Admin | Program index |
| POST | `/program/internships` | Admin | Create program |
| GET | `/program/internships/{internship}` | Admin | Show program |
| PUT | `/program/internships/{internship}` | Admin | Update program |
| POST | `/program/internships/{internship}/publish` | Admin | Publish program |
| POST | `/program/internships/{internship}/cancel` | Admin | Cancel program |
| GET | `/program/internships/{internship}/closure-check` | Admin | Closure readiness report |
| GET | `/program/groups` | Admin | Group index |
| POST | `/program/groups` | Admin | Create group |
| GET | `/program/groups/{group}` | Admin | Show group |
| PUT | `/program/groups/{group}` | Admin | Update group |

---

## Enrollment

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/enrollment/register` | Guest | Application form |
| POST | `/enrollment/register` | Guest | Submit application |
| GET | `/enrollment/registrations` | Auth | Registration center |
| POST | `/enrollment/registrations` | Student | Start registration wizard |
| GET | `/enrollment/registrations/{registration}` | Auth | Show registration |
| POST | `/enrollment/registrations/{registration}/documents` | Student | Upload required document |
| POST | `/enrollment/registrations/{registration}/activate` | Admin | Activate registration |
| GET | `/enrollment/placements` | Admin | Placement index |
| POST | `/enrollment/placements` | Admin | Create placement slot |
| PUT | `/enrollment/placements/{placement}` | Admin | Update placement |
| POST | `/enrollment/placements/{placement}/assign` | Admin | Assign student to slot |
| POST | `/enrollment/placement-change-requests` | Student | Request slot change |
| GET | `/enrollment/placement-change-requests` | Admin | Pending change requests |

---

## Journals

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/journals/logbook` | Auth | Logbook index |
| POST | `/journals/logbook` | Student | Create logbook entry |
| GET | `/journals/logbook/{logbook}` | Auth | Show logbook entry |
| PUT | `/journals/logbook/{logbook}` | Student | Update draft entry |
| POST | `/journals/logbook/{logbook}/submit` | Student | Submit for verification |
| POST | `/journals/logbook/{logbook}/verify` | Mentor/Supervisor | Verify logbook entry |
| GET | `/journals/attendance` | Auth | Attendance index |
| POST | `/journals/attendance/clock-in` | Student | Clock in |
| POST | `/journals/attendance/clock-out` | Student | Clock out |
| GET | `/journals/absences` | Auth | Absence requests |
| POST | `/journals/absences` | Student | Submit absence request |
| POST | `/journals/absences/{id}/approve` | Mentor/Admin | Approve absence |

---

## Guidance

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/guidance/supervision-logs` | Mentor | Supervision log index |
| POST | `/guidance/supervision-logs` | Mentor | Create supervision log |
| GET | `/guidance/supervision-logs/{log}` | Mentor | Show supervision log |
| GET | `/guidance/handbooks` | Auth | Handbook list |
| GET | `/guidance/handbooks/{handbook}` | Auth | View handbook |
| POST | `/guidance/handbooks/{handbook}/acknowledge` | Auth | Acknowledge handbook |

---

## Incident

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/incident/reports` | Auth | Incident index |
| POST | `/incident/reports` | Auth | Create incident report |
| GET | `/incident/reports/{incidentReport}` | Auth | Show incident |
| PATCH | `/incident/reports/{incidentReport}/status` | Admin | Update incident status |

---

## Assessment

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/assessment/rubrics` | Admin | Rubric index |
| POST | `/assessment/rubrics` | Admin | Create rubric |
| GET | `/assessment/rubrics/{rubric}` | Admin | Show rubric |
| PUT | `/assessment/rubrics/{rubric}` | Admin | Update rubric |
| POST | `/assessment/assessments` | Teacher/Supervisor | Submit assessment |
| GET | `/assessment/assessments/{assessment}` | Auth | View assessment |

---

## Assignment

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/assignment/assignments` | Auth | Assignment index |
| POST | `/assignment/assignments` | Admin | Create assignment |
| GET | `/assignment/assignments/{assignment}` | Auth | Show assignment |
| PUT | `/assignment/assignments/{assignment}` | Admin | Update assignment |
| POST | `/assignment/submissions` | Student | Submit assignment |
| GET | `/assignment/submissions/{submission}` | Auth | View submission |
| POST | `/assignment/submissions/{submission}/grade` | Teacher | Grade submission |

---

## Evaluation

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/evaluation/forms` | Admin | Form index |
| POST | `/evaluation/forms` | Admin | Create form |
| GET | `/evaluation/forms/{evaluationForm}` | Auth | Show form |
| PUT | `/evaluation/forms/{evaluationForm}` | Admin | Update form |
| POST | `/evaluation/forms/{evaluationForm}/submit` | Auth | Submit response |
| GET | `/evaluation/forms/{evaluationForm}/results` | Admin | View results |

---

## Reports

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/reports` | Admin | Grade card index |
| GET | `/reports/{report}` | Admin | Show grade card |
| POST | `/reports/{report}/finalize` | Admin | Finalize grade card |

---

## Certification

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/certificates` | Admin | Certificate index |
| POST | `/certificates/issue` | Admin | Issue certificate |
| POST | `/certificates/batch` | Admin | Batch issue |
| GET | `/certificates/{cert}` | Auth | Show certificate |
| POST | `/certificates/{cert}/revoke` | Admin | Revoke certificate |
| GET | `/verify/{hash}` | Public | Verify certificate |

---

## Document

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/documents/templates` | Admin | Template index |
| POST | `/documents/templates` | Admin | Create template |
| GET | `/documents/templates/{id}` | Admin | Show template |
| PUT | `/documents/templates/{id}` | Admin | Update template |
| GET | `/documents/templates/{id}/render/{registration}` | Admin | Render PDF |

---

## Setup & System

| Method | URI | Auth | Description |
| ------ | --- | ---- | ----------- |
| GET | `/setup/{token}` | Guest | Setup wizard |
| POST | `/setup/{token}` | Guest | Submit setup step |
| GET | `/system/health` | Admin | System health check |

---

## Error Response Format

All error responses follow a consistent JSON envelope:

### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### General Error (4xx/5xx)
```json
{
    "message": "Human-readable error description"
}
```

### Status Codes

| Code | Meaning |
| ---- | ------- |
| 200 | Success |
| 201 | Created |
| 204 | No Content (successful deletion) |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |
