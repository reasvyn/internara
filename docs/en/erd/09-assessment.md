# 09 — Assessment & Grading

> **Lifecycle:** Rubric creation → Competency definition → Indicator mapping → Student assessment → Final presentation
> **Domains:** `Assessment`
> **Tables:** 6 (`rubrics`, `competencies`, `indicators`, `assessments`, `presentations`, `presentation_examiners`)

---

## Purpose

Manages competency-based evaluation. Rubrics define evaluation frameworks with weighted competencies, each containing measurable indicators. Assessments apply rubrics to individual student registrations. Presentations handle final seminar scheduling and scoring with multiple examiners.

**Scoring model:**
```
Internship
├── report_weight (50%)
└── presentation_weight (50%)
        │
Rubric
├── competency_1 (weight: 40%)
│   ├── indicator_1a (weight: 50%, max_score: 100)
│   └── indicator_1b (weight: 50%, max_score: 100)
├── competency_2 (weight: 30%)
└── competency_3 (weight: 30%)
```

---

## Tables

### rubrics

Assessment framework linked to an internship program.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| internship_id | varchar(36) | FK → internships(id), CAS | Program this rubric belongs to |
| name | varchar(255) | NOT NULL | Rubric name (e.g., "Competency Assessment - 2025") |
| description | text | NULLABLE | | |
| is_active | boolean | DEFAULT true | Toggle without deleting |
| created_by | varchar(36) | FK → users(id), NUL | Creator |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**One-to-many:** One rubric has many competencies. A rubric is optionally linked to an internship (can be reused across programs).

### competencies

Skill/competency areas within a rubric.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| rubric_id | varchar(36) | FK → rubrics(id), CAS | Parent rubric |
| name | varchar(255) | NOT NULL | Competency name (e.g., "Technical Skills") |
| description | text | NULLABLE | | |
| weight | integer | DEFAULT 0 | % contribution to total rubric score |
| evaluator_role | varchar(255) | NOT NULL | Who evaluates: 'school_mentor', 'industry_mentor', 'both' |
| order | integer | DEFAULT 0 | Display ordering |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** Sum of all `weight` values in a rubric should equal 100 (enforced in application).

**Evaluator roles:**
- `school_mentor` — Teacher evaluates
- `industry_mentor` — Company supervisor evaluates
- `both` — Both evaluate (scores averaged)

### indicators

Measurable criteria within a competency.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| competency_id | varchar(36) | FK → competencies(id), CAS | Parent competency |
| name | varchar(255) | NOT NULL | Indicator name |
| description | text | NULLABLE | Detailed evaluation criteria |
| max_score | numeric | DEFAULT 100 | Maximum possible score |
| weight | integer | DEFAULT 0 | % contribution to competency score |
| order | integer | DEFAULT 0 | Display ordering |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Scoring formula:** `indicator_score = actual / max_score * weight` (normalized to competency weight).

### assessments

Actual evaluation results applied to a student registration.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student being assessed |
| academic_year_id | varchar(36) | FK → academic_years(id), SNU | Evaluation period |
| rubric_id | varchar(36) | FK → rubrics(id), NUL | Rubric used |
| evaluator_id | varchar(36) | FK → users(id), CAS | Who evaluated |
| type | varchar(20) | DEFAULT 'final' | 'mid', 'final', 'progress' |
| score | float | NULLABLE | Computed total score |
| content | json | NULLABLE | Detailed scores per criteria/competency |
| feedback | text | NULLABLE | Overall feedback |
| finalized_at | timestamp | NULLABLE | When evaluation was locked |
| deleted_at | timestamp | NULLABLE | Soft delete |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** Composite on `[registration_id, type]`, `[registration_id, academic_year_id]`, `[evaluator_id, type]`.

**Soft deletes:** This is the ONLY table in the schema using `softDeletes()`. Assessments can be retracted.

**Assessment types:**
- `mid` — Mid-program evaluation
- `final` — End-of-program evaluation
- `progress` — Ongoing progress check

### presentations

Final seminar/presentation scheduling and scoring.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student presenting |
| scheduled_at | datetime | NOT NULL | Presentation date/time |
| location | varchar(255) | NULLABLE | Room or virtual link |
| status | varchar(255) | DEFAULT 'scheduled' | 'scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled' |
| presentation_score | float | NULLABLE | Average score from examiners |
| report_score | float | NULLABLE | Report evaluation score |
| final_score | float | NULLABLE | Weighted combination |
| notes | text | NULLABLE | | |
| completed_at | datetime | NULLABLE | When presentation finished |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
scheduled ──► in_progress ──► completed
    │              │
    └── cancelled  └── rescheduled
```

**Scoring:** `final_score = (presentation_score * internship.presentation_weight + report_score * internship.report_weight) / 100`

### presentation_examiners

Individual examiner scores for a presentation.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| presentation_id | varchar(36) | FK → presentations(id), CAS | |
| examiner_id | varchar(36) | FK → users(id) | Teacher/examiner |
| score | float | NULLABLE | Individual score given |
| feedback | text | NULLABLE | Personal notes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

## Key Queries

### Full rubric with competencies and indicators:

```sql
SELECT r.name AS rubric,
       c.name AS competency, c.weight AS comp_weight, c.evaluator_role,
       i.name AS indicator, i.max_score, i.weight AS ind_weight
FROM rubrics r
JOIN competencies c ON c.rubric_id = r.id
JOIN indicators i ON i.competency_id = c.id
WHERE r.id = ?
ORDER BY c.order, i.order;
```

### Student's assessment summary:

```sql
SELECT a.type, a.score, a.feedback, a.finalized_at,
       u.name AS evaluator
FROM assessments a
JOIN users u ON u.id = a.evaluator_id
WHERE a.registration_id = ?
ORDER BY a.created_at DESC;
```

### Upcoming presentations:

```sql
SELECT p.scheduled_at, p.location, p.status,
       u.name AS student, i.name AS program
FROM presentations p
JOIN registrations r ON r.id = p.registration_id
JOIN mentees m ON m.id = r.mentee_id
JOIN users u ON u.id = m.user_id
JOIN internships i ON i.id = r.internship_id
WHERE p.status IN ('scheduled', 'rescheduled')
  AND p.scheduled_at >= NOW()
ORDER BY p.scheduled_at;
```

---

## Scoring Flow

```
1. Admin creates Rubric with weighted Competencies and Indicators
2. Examiner creates Assessment for a Registration
   → Stores detailed scores in `content` JSON column
   → Computed `score` is the weighted sum
3. If program requires presentation:
   → Presentation is scheduled
   → Examiners submit individual scores
   → `presentation_score` = average of examiner scores
4. Final score calculation:
   final = assessment.score (if no presentation)
   final = (presentation_score × presentation_weight + report_score × report_weight) / 100 (if presentation)
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `rubrics.internship_id` | `internships.id` | 04-internship |
| `rubrics.created_by` | `users.id` | 01-auth |
| `competencies.rubric_id` | `rubrics.id` | 09-assessment |
| `indicators.competency_id` | `competencies.id` | 09-assessment |
| `assessments.registration_id` | `registrations.id` | 05-registration |
| `assessments.academic_year_id` | `academic_years.id` | 02-institution |
| `assessments.evaluator_id` | `users.id` | 01-auth |
| `presentations.registration_id` | `registrations.id` | 05-registration |
| `presentation_examiners.examiner_id` | `users.id` | 01-auth |
