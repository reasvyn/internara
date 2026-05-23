# Incident Domain

## Purpose

Incident provides a structured, auditable workflow for reporting, investigating, and resolving 
issues that occur during internships. Incidents range from minor policy infractions (dress code 
violations, missed mandatory sessions without excuse) to serious safety concerns (workplace 
accidents, harassment reports, safety hazards) to operational issues (equipment damage, data 
breaches). This domain exists because unstructured issue handling is a liability: without 
documented reporting, investigation, and resolution, problems can be ignored, mishandled, or 
become legally indefensible. The incident workflow ensures every reported issue is tracked 
through a complete lifecycle with a full audit trail.

## Boundary

**In scope:** Incident report submission (by any user — student, mentor, teacher, admin, or 
anonymous reporter), incident categorization (safety, behavioral, policy violation, operational, 
harassment, other), severity classification (low, medium, high, critical), investigator 
assignment workflow with time-bound expectations, investigation documentation (structured notes, 
evidence file uploads, interview records, timeline entries), resolution and closure with 
documented outcome classification, incident timeline (chronological log of every action taken on 
the incident), notification chains at each workflow stage, incident statistics and trend 
reporting, incident status lifecycle management.

**Out of scope:** Attendance tracking and absence management (Attendance domain), evaluation and 
performance feedback (Evaluation domain), external disciplinary or legal case management (the 
system tracks incidents but does not interface with external disciplinary boards or legal 
systems), insurance claims processing, direct grade consequences of incidents (grade impacts are 
handled by other domains consuming incident data as supporting evidence), supervision notes 
(Mentor domain owns mentor-private observations that are not incident reports).

## Key Concepts

**Incident Reports.** Any user can submit an incident report. The report captures: the date and 
time of occurrence, the location (physical location or virtual platform), a detailed description 
of what happened, the people involved (reporter, subject of the report, witnesses, victims, 
affected parties), the category of incident, and an initial severity assessment. Reports can 
include supporting evidence — photos, documents, screenshots, or other files uploaded through 
the media library. Crucially, the reporter can choose to submit ANONYMOUSLY: in this case, the 
reporter's identity is hidden from the investigator and all other parties unless the reporter 
later opts to reveal it. Anonymous reports preserve the reporter's identity in the system (for 
audit purposes only — accessible only to senior admins) while shielding it from the 
investigation workflow.

**Severity Classification.** Each incident receives a severity level, which can be adjusted 
during investigation as more facts emerge. LOW: minor policy violation or operational issue with 
no safety impact or legal implications — handle informally but document. MEDIUM: notable issue 
requiring formal investigation — policy violation, moderate behavioral issue, or operational 
disruption. HIGH: serious incident — significant policy violation, safety concern requiring 
intervention, or potential legal implications — requires immediate investigation and management 
notification. CRITICAL: imminent danger to health or safety, active legal requirement (e.g., data 
breach notification), or major reputation risk — triggers immediate, out-of-band notification 
to all administrators and program coordinators, and may require external reporting.

**Investigation Workflow.** After a report is filed, an investigator is assigned — typically an 
admin, teacher, or designated safety officer. The investigation proceeds through defined phases: 
ASSIGNMENT (investigator designated, acknowledgment expected within a configurable time window), 
INFORMATION_GATHERING (collecting evidence, interviewing involved parties, reviewing relevant 
records, documenting findings), FINDINGS_DOCUMENTATION (structured write-up of what was 
discovered, including a factual chronology and an analysis), and OUTCOME_DETERMINATION 
(conclusion and recommended action). The investigator can adjust severity, update the category, 
add or remove involved parties, and request additional information from the reporter 
(anonymously, through the system). Every action during investigation is recorded on the incident 
timeline with timestamp, actor, action type, and details.

**Resolution and Closure.** An incident is resolved with one of four documented outcomes. 
CONFIRMED_ACTION_TAKEN: the incident occurred as reported, and corrective or disciplinary action 
was implemented. CONFIRMED_NO_ACTION: the incident occurred but, after investigation, no further 
action is warranted (minor, unavoidable, or already resolved). UNFOUNDED: investigation found no 
credible evidence that the incident occurred as described. REFERRED: the incident has been 
referred to an external authority — legal, police, regulatory body, or institutional review 
board — and the internal investigation is closed pending external outcome. Resolution always 
includes a written summary explaining the outcome and, if applicable, an action plan describing 
what was done or will be done. Once closed, the incident is permanently read-only.

**Incident Timeline.** Every single action taken on an incident is recorded as a timeline entry: 
report submission, status changes, severity changes, investigator assignment, evidence uploads, 
notes added, interviews conducted, findings documented, and resolution recorded. Each timeline 
entry captures: the timestamp, the acting user, the action type, a description, and any 
associated data (e.g., uploaded evidence references). The timeline is immutable — no entries 
can be edited or deleted. It provides a complete, court-defensible history of how the incident 
was handled from first report to final closure.

**Notifications.** At each significant workflow stage, notifications are sent to relevant 
parties. Report submission: mentor notified (if the subject is their mentee). Investigation 
assignment: investigator notified with case details. Status changes: reporter notified 
(anonymously if they chose anonymous reporting). Resolution: all involved parties notified of the 
outcome. CRITICAL incidents additionally notify all admins, the department head, and the program 
coordinator. Notifications are sent through multiple channels (in-app notification plus email) to 
ensure attention.

## Requirements

### User Stories & Rules

- **Any User:** As any user, I want to report an incident so that issues are formally documented
- **Any User:** As any user, I want to report an incident anonymously so that I can raise concerns without fear
- **Admin:** As an admin, I want to be assigned as investigator so that I can examine reported incidents
- **Admin:** As an admin, I want to document investigation findings so that the incident has a complete record
- **Admin:** As an admin, I want to resolve and close incidents with documented outcomes so that the case is properly concluded
- **Student:** As a student, I want to be notified of incidents involving me so that I am aware of the process
- **Manager:** As a manager, I want to view incident statistics so that I can identify trends and improve safety
- Incidents can never be deleted from the database — they can only be closed. Hard delete is 
blocked at the database and application levels.
- Reporter identity is anonymous to the investigator by default; the reporter must explicitly opt 
in to reveal their identity.
- CRITICAL severity incidents trigger immediate out-of-band notifications to all administrators 
— not just in-app notifications.
- Once an incident is closed, it cannot be reopened. If new information emerges, a new incident 
must be filed with a reference to the original.
- Every incident timeline entry is immutable — no edits, no deletions, no retroactive 
modifications to the incident history.
- Resolution requires a substantive written findings summary — simply changing the status field 
is not sufficient for closure.
- All evidence uploads are immutable once attached to an incident; re-uploading creates a new 
version rather than replacing the old.
- Investigation assignments are time-bound; if no progress is made within a configurable period 
(default 48 hours for HIGH/CRITICAL), the assignment auto-escalates.

### Process Flow

```
Incident Lifecycle:

REPORTED ──→ INVESTIGATING ──→ RESOLVED ──→ CLOSED (immutable)
```

- **REPORTED**: Incident submitted, awaiting investigator assignment
- **INVESTIGATING**: Investigator assigned, actively gathering information
- **RESOLVED**: Investigation complete, outcome determined and documented
- **CLOSED**: Permanently immutable — no reopening possible
- Every action during investigation is recorded on an immutable timeline
- CRITICAL severity triggers immediate out-of-band notifications to all administrators

### Key Operations

| Action | Description |
|--------|-------------|
| `ReportIncidentAction` | Submits a new incident report (supports anonymous reporting) |
| `UpdateIncidentAction` | Updates incident details during investigation |
| `ResolveIncidentAction` | Resolves an incident with outcome classification and written summary |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `IncidentReport` |
| **Enums** | `IncidentSeverity` — `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`; `IncidentStatus` — `REPORTED`, `INVESTIGATING`, `RESOLVED`, `CLOSED`; `IncidentType` — `ACCIDENT`, `SAFETY_VIOLATION`, `HARASSMENT`, `DISCIPLINARY`, `OTHER` |
| **Livewire** | `IncidentForm`, `IncidentManager` |
| **Notifications** | `IncidentReportedNotification` |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | Links the incident to the student and internship program context for reporting 
and resolution |
| User | Reporter, investigator, involved party, and witness identity for the incident record and 
notifications |
| Core | BaseAction, BaseModel, SmartLogger |


