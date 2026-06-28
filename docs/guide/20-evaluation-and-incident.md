# Chapter 20: Evaluation & Incident

> **Last updated:** 2026-06-16
> **Changes:** sync — initial metadata sync with new format

## Description
This chapter covers two separate features: **Evaluation** (feedback forms for collecting stakeholder
opinions) and **Incident** (workplace issue reporting and resolution tracking).

---


## 20.1 Evaluation Overview

Evaluation is Internara's feedback collection system — similar to Google Forms but built in.
It collects subjective feedback from all PKL stakeholders: students evaluating mentors, teachers
evaluating programs, companies evaluating the program, and overall satisfaction surveys.

Unlike **Assessment** (which scores competencies via rubrics), Evaluation gathers opinions and
perceptions. Results are used for program quality improvement, not for student grading.

| Role | What They Can Do |
|------|------------------|
| **Admin** | Create and manage evaluation forms, view response results |
| **Teacher / Supervisor / Student** | Fill in and submit evaluation forms targeted at them |

---

## 20.2 Evaluation Form Structure

Each evaluation form follows a hierarchical structure:

```
Evaluation Form
├── Name & Description
├── Target Type (Mentor / Program / Company / Overall)
├── Sections (optional groupings)
│   ├── Section Title
│   └── Section Description
├── Questions (can be inside sections or at form level)
│   ├── Question Text
│   ├── Question Type (rating, yes/no, multiple choice, text)
│   ├── Weight (for score calculation)
│   └── Required / Optional
└── Responses
    └── Answers (one per question)
```

### 20.2.1 Question Types

| Type | Input | Score Calculation |
|------|-------|-------------------|
| **Rating 1–5** | Star / number selection | `(value / 5) × 100` |
| **Rating 1–10** | Number selection | `(value / 10) × 100` |
| **Yes / No** | Toggle | 100 or 0 |
| **Multiple Choice** | Select one option | Configurable per option |
| **Agreement (Likert)** | Strongly Disagree – Strongly Agree | Same as rating 1–5 |
| **Text** | Free text input | No score (qualitative only) |

### 20.2.2 Score Calculation

The overall score for a response is auto-calculated from weighted question scores:

```
overall_score = Σ(question_score × weight) / Σ(weight)
```

Scores are normalised to a 0–100 scale for consistency across question types.

---

## 20.3 Managing Evaluation Forms (Admin)

### 20.3.1 Creating a Form

1. Navigate to the Evaluation section
2. Click **New Form**
3. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Form display name | Mentor Evaluation 2025/2026 |
| **Description** | Purpose of this form | Evaluate your industry supervisor's performance |
| **Target Type** | Who or what is being evaluated | Mentor / Program / Company / Overall |
| **Active** | Whether the form is accepting responses | Yes |

4. Click **Save**

### 20.3.2 Adding Sections (Optional)

Sections group related questions together:

1. Open the form and click **Add Section**
2. Enter a title and description
3. Click **Save**

### 20.3.3 Adding Questions

1. Open the form (or a section) and click **Add Question**
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Question Text** | The question to ask | How satisfied are you with the mentor's guidance? |
| **Question Type** | Input type | Rating 1–5 |
| **Weight** | Importance weight for scoring | 2 |
| **Required** | Whether an answer is mandatory | Yes |
| **Options** | Choices (for multiple choice only) | Very Satisfied, Satisfied, Neutral, Dissatisfied |
| **Order** | Display order | 1 |

3. Click **Save**

### 20.3.4 Viewing Results

Once responses have been submitted, admins can:

- View individual response details
- See the overall score for each response
- Filter responses by date or evaluator
- Export results for analysis

---

## 20.4 Submitting an Evaluation (User)

When an evaluation form is targeted at you (e.g., you are a mentor being evaluated), you will
receive a notification or see the form in your dashboard.

1. Open the pending evaluation form
2. Answer each question:

| Question Type | How to Answer |
|---------------|---------------|
| Rating 1–5 | Select a value from 1 to 5 |
| Rating 1–10 | Select a value from 1 to 10 |
| Yes / No | Toggle yes or no |
| Multiple Choice | Select one option |
| Agreement | Select from Strongly Disagree to Strongly Agree |
| Text | Type your response |

3. Click **Submit**

Once submitted, your response is **immutable** — it cannot be edited or retracted.
The system records the submission timestamp and your identity.

---

## 20.5 Incident Reporting Overview

Incident reports provide a formal channel for reporting workplace issues during internships.
Any authenticated user — student, teacher, supervisor, or admin — can submit an incident report.
Incidents are classified by severity and progress through an investigation workflow.

| Role | What They Can Do |
|------|------------------|
| **All Users** | Report an incident |
| **Admin** | Investigate, resolve, and close incidents |

---

## 20.6 Reporting an Incident

### 20.6.1 Submitting a Report

1. Navigate to **Student Portal → Report an Incident** from the sidebar
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Internship Registration** | Your current internship | PKL 2025/2026 — PT Teknologi Maju |
| **Date** | When the incident occurred | 16 June 2026 14:30 |
| **Type** | Category of incident | Accident, Safety Violation, Harassment, Disciplinary, Other |
| **Severity** | How serious is the incident | Low, Medium, High, Critical |
| **Location** | Where it happened | Workshop area, 2nd floor |
| **Description** | Detailed explanation (min 20 characters) | |
| **Action Taken** | Any immediate actions taken | Notified the supervisor on site |

3. Click **Submit Report**

### 20.6.2 Severity Levels

| Severity | Meaning | Notification |
|----------|---------|-------------|
| **Low** | Minor concern | Routed to assigned mentor |
| **Medium** | Notable issue | Routed to assigned supervisor |
| **High** | Serious problem | All admins notified |
| **Critical** | Immediate danger | Immediate email + in-app notification to all admins |

High and Critical severity incidents trigger automatic notifications to all admin users and the
student's assigned teacher.

---

## 20.7 Incident Investigation Workflow (Admin)

Navigate to **Admin → Incidents** or go directly to `/admin/incidents`.

Incidents follow a state progression:

```
Reported → Investigating → Resolved → Closed
```

### 20.7.1 Incident Status Lifecycle

| Status | Meaning | Who Can Act |
|--------|---------|-------------|
| **Reported** | Incident has been submitted | Admin can begin investigation |
| **Investigating** | Under review | Admin can gather information |
| **Resolved** | Investigation complete, outcome recorded | Admin can close |
| **Closed** | Final state | No further action |

### 20.7.2 Investigating an Incident

1. Find the incident in the list
2. Review the details: type, severity, description, and any attached information
3. Click the **Resolve** button to open the resolution form

### 20.7.3 Resolving an Incident

When resolving, provide:

| Field | Description | Example |
|-------|-------------|---------|
| **Status** | Resolved or Closed | Resolved |
| **Resolution Notes** | How the incident was handled | Student was reassigned to a different workstation. Safety briefing conducted. |

4. Click **Save** — the incident status changes accordingly

### 20.7.4 Incident List

The incident manager table shows:
- Date of incident
- Student name
- Type and severity (with colour-coded badges)
- Current status
- Resolve action button (for non-terminal incidents)

Filters are available by type, severity, and status.

---

## 20.8 Troubleshooting

### Cannot submit an evaluation

- Ensure the evaluation form is **Active** — inactive forms are hidden from the submission list
- Check the **Target Type** — you can only submit forms that target your role or relationship

### Evaluation response cannot be edited

Evaluation responses are **immutable** once submitted. If a correction is needed, contact an
administrator who can create a new evaluation form for re-submission.

### Incident severity seems wrong

Severity is set by the reporter when submitting. If you believe a reported incident needs
reclassification, contact an administrator who can update the severity during investigation.

### Cannot resolve an incident

Only incidents with status **Reported** or **Investigating** can be resolved. If the incident
is already **Closed**, it is in a terminal state and cannot be changed.

### Notifications not received for critical incidents

Check that:
- Your email address is correct in your profile
- The incident severity is set to **High** or **Critical**
- The notification system is configured (queue worker running)

---

**← Previous: [Chapter 19: Student Report & Certification](19-student-report-and-certification.md)**
**Next: [Chapter 21: Announcement & Notifications](21-announcement-and-notifications.md)**
