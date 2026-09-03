# Curriculum & Regulatory Compliance — Research Input

> **Status:** Research input. Not a spec. Intentionally outside the spec system because Indonesian
> PKL regulations evolve and are outside engineering control.
> **Marker:** Non-testable (`*` per spec-template convention).
> **Owner:** Documentation specialist (research context, not implementation).

## Description

Background on how Internara maps to the applicable Indonesian vocational education regulations,
curriculum structure, and PKL implementation standards (SOP) at the school. The list below is
informational — feature implementations align with the listed concerns but the regulation text
itself is not reproduced here.

## Why This Is Outside the Spec System

- Regulations change on a government/ministry cycle (years, not sprints).
- The system must adapt to school-specific SOP variation.
- A "spec" implies a verifiable, testable requirement; a regulation is a constraint to
  *consider*, not to mechanically satisfy.
- Tracking it in `docs/refs/` (with this disclaimer) makes the relationship explicit without
  polluting the spec/test pipeline.

## High-Level Alignment

| Category                       | How the system supports it                                                                                                | Relevant spec(s)                                                                                                |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| **Curriculum Alignment**       | PKL operational stages in the system follow the regulation's standard PKL phases (planning → implementation → reporting) | [7C5WM-internship-lifecycle](specs/7C5WM-internship-lifecycle.md), [IT0OE-internship-groups](specs/IT0OE-internship-groups.md) |
| **Learning Outcomes (CP)**     | Logbook captures daily activities that map to CP/competency skill profile                                                  | [1KSWL-daily-activity](specs/1KSWL-daily-activity.md)                                                           |
| **Mentoring Reports**          | Supervision logs + monitoring visits provide the mentoring evidence trail                                                 | [2EHSE-supervision](specs/2EHSE-supervision.md)                                                                 |
| **DUDI Work-Role Mapping**     | Placement assigns each student to a DUDI work-role matching the student's competency profile                              | [J9GBH-placement](specs/J9GBH-placement.md)                                                                     |
| **Competency Configuration**   | Each school configures its own competency units (hard + soft skills) independently                                       | [ARDA6-assessment](specs/ARDA6-assessment.md)                                                                   |
| **Rubric Customization**       | Per-school rubric customization for assessment criteria                                                                  | [ARDA6-assessment](specs/ARDA6-assessment.md)                                                                   |
| **Score Weighting**            | Per-program weighting scheme for DUDI vs. school score combination                                                        | [ARDA6-assessment](specs/ARDA6-assessment.md)                                                                   |
| **Final Grade Computation**    | Dynamic weighting schemes compute valid final grade predicates                                                            | [R6BMW-reports](specs/R6BMW-reports.md)                                                                         |
| **Application Workflow**       | Registration wizard follows the school's PKL implementation SOP                                                            | [MBB5R-registration](specs/MBB5R-registration.md)                                                               |
| **Administrative Instruments** | Digital assignment letters, approval sheets, certificates, MoU                                                            | [PKYX6-document-templates](specs/PKYX6-document-templates.md), [J0M04-certification](specs/J0M04-certification.md) |
| **Attendance Verification**    | Geotagged attendance with daily presence monitoring at the internship site                                               | [1KSWL-daily-activity](specs/1KSWL-daily-activity.md)                                                           |
| **Mentoring Evidence**         | Activity log + immutable submissions = authentic record of the educational process                                        | [89SRA-logging-and-error-handling](specs/89SRA-logging-and-error-handling.md), [1KSWL-daily-activity](specs/1KSWL-daily-activity.md) |
| **Reporting Format**           | Final report recapitulation conforms to school administrative accountability standards                                    | [R6BMW-reports](specs/R6BMW-reports.md)                                                                         |
| **Digital Approval**           | Document approval mechanism conforms to school administrative legality principles                                          | [7H5D6-official-documents](specs/7H5D6-official-documents.md), [PKYX6-document-templates](specs/PKYX6-document-templates.md) |
| **Terminology**                | Vocational education terms used accurately across the system (spec-validated bilingual dictionary)                        | Translation review process — no single spec owns this; cross-cutting concern                                       |

## When to Update This Document

- A new regulation or SOP version is published.
- A new alignment gap is identified in a user story or feature spec.
- A spec referenced in the table above is added, removed, or renamed.
