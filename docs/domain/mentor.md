# Mentor Domain

## Purpose

Mentor handles supervision — teachers and company supervisors who guide students during
their placement. Includes supervision logs and mentor profile management.

---

## Design Principles

### 1. Dual Mentorship

Every student has two mentors: a school teacher (academic) and a company supervisor
(industry). Both resolve to the MENTOR functional role via `Role::resolvesTo()`.

### 2. Supervision Logs

Private notes recording observations, concerns, and action items that guide student
development throughout the placement.

### 3. Site Visits

Formal visits to placement locations are scheduled, conducted, and documented. Each visit
produces a record of observations, findings, and follow-up actions separate from private
supervision notes.

---

## Domain Boundary

The Mentor domain owns the supervision toolkit — the tools and records that teachers and company supervisors use to guide students through their placement. It manages mentorship assignments where every student receives dual supervision from both a school teacher (academic mentor) and a company supervisor (industry mentor), both resolving to the mentor functional role regardless of their underlying user role. It handles supervision logs — private narrative entries where mentors record observations about student performance, behavior concerns, and action items. Mentors can review student-submitted reports, evaluate student performance against program competencies, and grade student assignment submissions.

Mentor does not own student identity data (User), program definitions (Internship), attendance records (Attendance), logbook entries (Logbook), assignment definitions (Assignment), assessment rubrics (Assessment), or evaluation forms (Evaluation). It provides the supervision interface and the private mentoring records but does not own the student activity data being supervised.

The domain depends on Auth for mentor functional role resolution (teachers and supervisors both resolve to mentor), on User for student identity, on Internship for program context, and on the operational domains whose content mentors review. It owns only the mentorship relationship and supervision logs, not the work being supervised.

---

## Key Features

- Write private supervision log entries recording observations, concerns, and action items about individual students.
- View and filter a personal history of all supervision logs with search capabilities.
- Review reports submitted by mentees and provide feedback as part of the revision workflow.
- Evaluate student performance against program-defined competencies with scored assessments.
- Grade student assignment submissions with numeric scores and written feedback.
- Search and filter the assigned student list by name, program, or group assignment.
- Write supervision log entries in a rich text editor with a private visibility badge.
- View a personal dashboard showing pending reviews, ungraded submissions, and recent log entries at a glance.
- Grade assignments via an inline grading panel with score input and comment box accessible from the submission view.
- Schedule site visits to placement locations with date, time, and purpose, visible on a personal calendar.
- Log site visit results with observations, photos, and follow-up action items after each visit.
