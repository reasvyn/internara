# Evaluation Domain

## Purpose

Evaluation collects structured feedback about the placement experience from multiple
perspectives — students rate mentors, companies, facilities, and overall satisfaction.

---

## Design Principles

### 1. Multi-Perspective Feedback

Evaluation gathers feedback from all angles of the placement experience — students rate
mentors, companies, and facilities; teachers rate programs; and the system aggregates across perspectives.
No single viewpoint defines quality.

### 2. Anonymous Where Appropriate

Student evaluations of mentors and companies are anonymous by default — the recipient
sees aggregate scores, not individual respondent identities. This encourages honest
feedback without fear of repercussion.

### 3. Score Band Standardization

All evaluation scores map to consistent bands: excellent (85–100), good (70–84),
satisfactory (55–69), needs improvement (40–54), and poor (0–39). These bands are
shared across all evaluation types for comparability.

---

## Domain Boundary

The Evaluation domain owns structured feedback collection — the multi-perspective assessment of placement quality gathered throughout and at the end of the program lifecycle. Students evaluate their mentors on communication, responsiveness, and guidance quality. They evaluate their host companies on workplace safety, task relevance, and mentoring effectiveness. They evaluate workplace facilities on infrastructure, cleanliness, and accessibility. They also submit an independent overall satisfaction rating. The system maps numeric scores to five performance bands: excellent, good, satisfactory, needs improvement, and poor. Administrators can view all evaluations filtered by type with aggregate scores and trend analysis. A separate program quality evaluation — completed by administrators and teachers during program closure — assesses curriculum alignment, completion rates, partner satisfaction, and areas for improvement. The system automatically aggregates scores and generates trend reports across all evaluation types.

Evaluation does not own student identity data (User/Mentee), program definitions (Internship), mentor assignment records (Mentor), rubric definitions (Assessment), or certificate issuance (Certificate). It collects feedback about those domains' performance but does not manage their data or workflows. It does not own the entities being evaluated — it owns only the evaluation responses and their aggregation.

The domain depends on User for respondent identity, on Internship for program context, on Mentor for mentor identity in mentor evaluations, and on Partnership for company identity in company evaluations. Its aggregated results are consumed by the Internship domain during program closure for quality analysis and archival.

---

## Key Features

- Allow students to rate their mentor's communication, responsiveness, and guidance quality.
- Allow students to rate their host company's workplace safety, task relevance, and mentoring effectiveness.
- Submit an independent overall satisfaction rating separate from mentor and company evaluations.
- Map numeric scores to five performance bands from excellent through poor with defined score ranges.
- View all evaluations filtered by type with aggregate scores and trend analysis in the administrative dashboard.
- Conduct program quality evaluations during closure to assess curriculum alignment, completion rates, and partner satisfaction.
- Automatically aggregate scores and generate trend reports across all evaluation types.
- Rate mentors using a star-rating widget with clear labels for each evaluation dimension.
- Rate companies using a star-rating widget for workplace safety, task relevance, and mentoring effectiveness.
- Submit evaluations via a multi-step form with progress indicators separating mentor, company, and satisfaction sections.
- View aggregate evaluation results in the admin dashboard with bar charts and trend lines across programs.
