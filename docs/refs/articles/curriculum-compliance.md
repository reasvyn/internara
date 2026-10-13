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
| **Curriculum Alignment**       | PKL operational stages in the system follow the regulation's standard PKL phases (planning → implementation → reporting) | Spec `[7C5WM]` (#477) + `[IT0OE]` (#478) (`label:spec`) |
| **Learning Outcomes (CP)**     | Logbook captures daily activities that map to CP/competency skill profile                                                  | Spec `[1KSWL]` (#485)                                                           |
| **Mentoring Reports**          | Supervision logs + monitoring visits provide the mentoring evidence trail                                                 | Spec `[2EHSE]` (#486)                                                                 |
| **DUDI Work-Role Mapping**     | Placement assigns each student to a DUDI work-role matching the student's competency profile                              | Spec `[J9GBH]` (#480)                                                                     |
| **Competency Configuration**   | Each school configures its own competency units (hard + soft skills) independently                                       | Spec `[ARDA6]` (#488)                                                                   |
| **Rubric Customization**       | Per-school rubric customization for assessment criteria                                                                  | Spec `[ARDA6]` (#488)                                                                   |
| **Score Weighting**            | Per-program weighting scheme for DUDI vs. school score combination                                                        | Spec `[ARDA6]` (#488)                                                                   |
| **Final Grade Computation**    | Dynamic weighting schemes compute valid final grade predicates                                                            | Spec `[R6BMW]` (#499)                                                                         |
| **Application Workflow**       | Registration wizard follows the school's PKL implementation SOP                                                            | Spec `[MBB5R]` (#479)                                                               |
| **Administrative Instruments** | Digital assignment letters, approval sheets, certificates, MoU                                                            | Spec `[PKYX6]` (#493) + `[J0M04]` (#495) |
| **Attendance Verification**    | Geotagged attendance with daily presence monitoring at the internship site                                               | Spec `[1KSWL]` (#485)                                                           |
| **Mentoring Evidence**         | Activity log + immutable submissions = authentic record of the educational process                                        | Spec `[89SRA]` (#450) + `[1KSWL]` (#485) |
| **Reporting Format**           | Final report recapitulation conforms to school administrative accountability standards                                    | Spec `[R6BMW]` (#499)                                                                         |
| **Digital Approval**           | Document approval mechanism conforms to school administrative legality principles                                          | Spec `[7H5D6]` (#500) + `[PKYX6]` (#493) |
| **Terminology**                | Vocational education terms used accurately across the system (spec-validated bilingual dictionary)                        | Translation review process — no single spec owns this; cross-cutting concern                                       |

## When to Update This Document

- A new regulation or SOP version is published.
- A new alignment gap is identified in a user story or feature spec.
- A spec referenced in the table above is added, removed, or renamed.
