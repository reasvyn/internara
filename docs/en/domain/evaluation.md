# Evaluation Domain

## Purpose

Evaluation collects structured qualitative feedback from mentors about their assigned students at 
defined intervals during the internship. It sits between the formal, rubric-based scoring of the 
Assessment domain and the private, informal supervision notes of the Mentor domain. Evaluations 
are periodic check-ins — the mentor fills out a structured form with questions about the 
student's progress, professional behavior, technical skills, communication ability, and overall 
performance. Unlike assessment rubrics (which produce numeric scores), evaluations capture the 
mentor's holistic judgment in both quantitative ratings and qualitative written observations. 
This is a deliberately simple domain: it provides a repeatable mechanism for capturing mentor 
perspectives at the right moments.

## Boundary

**In scope:** Evaluation form definition (questions with rating scales, open text fields, section 
grouping), evaluation form assignment to programs and evaluation cycles, evaluation instance 
creation (matching a form to a specific mentor-student pair for a specific cycle), evaluation 
submission by mentors (complete ratings and qualitative feedback), evaluation review by students 
(read-only view of completed evaluations), evaluation cycle management (mid-term, final, custom 
intervals with deadlines), evaluation completion tracking and reminder notifications, aggregate 
evaluation summaries (average ratings per question across a cohort, rating trends over time).

**Out of scope:** Rubric-based competency scoring and criteria definition (Assessment domain owns 
rubrics and competency assessments), daily task grading (Assignment domain), attendance tracking 
and absence management (Attendance domain), logbook journaling (Logbook domain), certificate 
eligibility decisions (Certificate domain), incident reporting (Incident domain), supervision 
logs (Mentor domain owns mentor-private notes that are never shared with students).

## Key Concepts

**Evaluation Forms.** A form defines the complete structure of an evaluation session. Forms are 
organized into sections, each covering a specific aspect of the student's performance: 
Professionalism (punctuality, dress code, workplace behavior), Technical Skills (domain-specific 
competency, tool proficiency, learning speed), Communication (written and verbal, reporting, team 
interaction), Problem-Solving (analytical thinking, initiative, resourcefulness), and Overall 
Assessment. Each section contains multiple questions using different response types: numeric 
ratings (1-5 or 1-10 scale with labeled endpoints), Likert scales (Strongly Disagree to Strongly 
Agree), Yes/No with required explanation, and open text fields for qualitative observations. 
Forms are versioned — once an evaluation cycle starts, the form version is frozen for the 
duration of that cycle.

**Evaluation Cycles.** Evaluations are grouped into cycles that align with the internship 
calendar. A typical internship has two standard cycles: mid-term (halfway evaluation) and final 
(end-of-internship evaluation). Programs can define custom cycles for different assessment needs. 
Each cycle specifies: the evaluation period (start date and end date for submission), the form to 
be used, which mentor-student pairs are required to participate, and whether the evaluation is 
mandatory or optional. When a cycle opens, evaluation instances are created for all eligible 
mentor-student pairs. The system tracks completion rates — which instances are submitted, which 
are pending, which are overdue — and sends automatic reminders to mentors with outstanding 
evaluations.

**Evaluation Instances.** An evaluation instance is the realization of a form for a specific 
mentor-student pair within a specific cycle. It captures the mentor's responses to every question 
on the form at a specific point in time. Instances have a status: PENDING (created but not yet 
started by the mentor), IN_PROGRESS (mentor has opened it and started filling responses, saves 
are incremental), SUBMITTED (mentor has completed and submitted the evaluation — it is now 
locked). Once SUBMITTED, the instance is immutable; the mentor cannot edit their responses, and 
the form version is permanently tied to the instance. Submitted instances are immediately visible 
to the student in read-only mode.

**Student Review.** Students can view their completed evaluations in the student dashboard. The 
view shows the mentor's ratings for each question, any qualitative comments, and the date of 
submission. The view is strictly read-only — students cannot respond, dispute, or annotate 
evaluations through this domain. If a student wishes to discuss or challenge an evaluation, they 
do so through normal mentor-student communication channels or, if the situation warrants formal 
escalation, through the Incident domain. Evaluation data is part of the student's permanent 
record for the internship.

**Aggregated Insights.** Beyond individual evaluations, the system compiles aggregate data across 
a cohort. Program coordinators can view: average ratings per question across all students in a 
program, distribution of ratings (how many students received each score level), rating trends 
across multiple cycles (are scores improving over time), and comparative views (how this cohort 
compares to previous cohorts). Aggregate data is anonymized — individual evaluator identities 
and student identities are not exposed in summaries. Raw mentor identities are replaced with role 
labels (e.g., "mentor", "supervisor") in aggregate reports.

## Dependencies

| Dependency | Reason |
|---|---|
| Mentor | Mentor-student assignments define which pairs are eligible for which evaluation 
instances |
| Registration | Student program enrollment determines which evaluation cycle applies and whether 
the student is active |
| Internship | Program definitions influence which evaluation forms to use, how many cycles 
exist, and their timing |
| Core | BaseAction, BaseModel, SmartLogger |

## Important Rules

- Evaluations are confidential between mentor and student until the evaluation cycle closes (all 
evaluations submitted or the deadline passes). Before cycle close, only the mentor sees their 
in-progress and submitted evaluations.
- Once an evaluation cycle closes, all submitted evaluations become permanently immutable — no 
edits, retractions, or deletions.
- Each mentor-student pair can produce at most one evaluation instance per evaluation cycle — 
no duplicate evaluations.
- Students have read-only access to their own evaluations — no editing, no deletion, no 
response mechanism through this domain.
- Evaluation forms cannot be modified while referenced by an active (not yet closed) evaluation 
cycle — modifications must wait until the cycle closes.
- Late evaluations (not submitted by the cycle deadline) are flagged on the mentor's dashboard 
but are not auto-submitted.
- Aggregate reports must anonymize individual mentor identities — raw names are replaced with 
role labels.
