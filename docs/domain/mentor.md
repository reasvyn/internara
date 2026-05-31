# Mentor Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 24 files in [reference](mentor-reference.md) exist

## Purpose

Mentor handles supervision — teachers and company supervisors who guide students during
their placement. Includes supervision logs and mentor profile management.

---

## Design Principles

### 1. Dual Mentorship

Every student has two mentors: a school teacher (academic) and a company supervisor
(industry). Both resolve to the MENTOR functional role via `Role::resolvesTo()`. The
teacher mentor guides from the school's perspective — monitoring progress, grading
assignments, and conducting site visits. The supervisor mentor guides on-site daily —
verifying attendance, reviewing journals, and providing workplace feedback.

### 2. Supervision Logs

Private notes recording observations, concerns, and action items. Both teacher and
supervisor mentors maintain logs, but with different focus areas: teachers track academic
progress and placement compliance, while supervisors track workplace performance and
professional behavior.

### 3. Site Visits by Teacher Mentors

School teachers conduct formal site visits to placement companies. These visits are
scheduled, conducted, and documented as separate records from supervision logs. Each
visit captures observations of the workplace environment, student performance assessment,
and follow-up action items. Industry supervisors do not perform site visits — they are
already on-site at the company daily.

---

## Domain Boundary

The Mentor domain owns the supervision toolkit — the tools and records that teachers and company supervisors use to guide students through their placement. It manages dual mentorship assignments: every student receives both a school teacher (academic mentor) and a company supervisor (industry mentor). The teacher mentor monitors academic progress, grades assignments, and conducts formal site visits to placement companies. The supervisor mentor provides daily on-site guidance — verifying attendance, reviewing journal entries, and giving workplace feedback. Both roles maintain private supervision logs recording observations, concerns, and action items, though their focus areas differ.

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
- Schedule site visits to placement companies with date, time, and purpose, visible on the teacher's personal calendar.
- Log site visit results with workplace observations, student performance notes, photos, and follow-up action items.
