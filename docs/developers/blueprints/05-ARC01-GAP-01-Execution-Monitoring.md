# Application Blueprint: Execution & Monitoring (ARC01-GAP-01)

**Series Code**: `ARC01-GAP` | **Scope**: `Phase 4: The HOW` | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the **Daily Operational Subsystem**. It defines 
the mechanisms for tracking student progress through high-fidelity telemetry (Attendance and 
Journals) and formalized supervisory guidance.

- **SyRS Traceability**:
    - **[SYRS-F-401]**: Forensic Journal Management (Daily Logs).
    - **[SYRS-F-402]**: Real-Time Attendance Telemetry (Geofencing ready).
    - **[SYRS-F-403]**: Supervisory Guidance & Interaction Loops.

---

## 2. User Roles & Stakeholders

- **Students**: Daily journal entry and attendance check-in.
- **Instructors/Mentors**: Reviewing logs, providing feedback, and validating activity.
- **Parents**: Read-only monitoring of participation telemetry.

---

## 3. Modular Impact Assessment

1.  **Journal**: Owners of the student's daily activity records.
2.  **Attendance**: Owners of the temporal and spatial telemetry.
3.  **Guidance**: Orchestration of feedback loops and advisory sessions.

---

## 4. Logic & Architecture (The Monitoring Engine)

### 4.1 The Forensic Journal Protocol
- **Chain of Custody**: Journals are submitted by students and MUST be verified by a 
  supervisor (Teacher or Mentor) to contribute to final competency scores.
- **Integrity**: Once verified, journals are immutable.

### 4.2 Attendance Logic
- **Precision**: Capturing `check_in`, `check_out`, and `metadata` (e.g., location, network).
- **Grace Periods**: Configurable via the `Setting` module to determine "Late" status.

---

## 5. Contract & Interface Definition

### 5.1 `Modules\Journal\Services\Contracts\JournalService`
- `submit(string $registrationUuid, array $data): Journal`: Captures daily activity.
- `verify(string $journalUuid, string $supervisorUuid): void`: Formal validation.

### 5.2 `Modules\Attendance\Services\Contracts\AttendanceService`
- `record(string $registrationUuid, string $type): Attendance`: Atomic telemetry log.
- `calculateCompliance(string $registrationUuid): float`: Returns participation percentage.

---

## 6. Data Persistence Strategy

### 6.1 Performance Invariant (Large Datasets)
- Journals and Attendance will generate the highest volume of data.
- **Mandate**: Use of `chunk()` or `cursor()` for all reporting and aggregation tasks. 
- **Partitioning**: Database schema must be designed to support future table partitioning 
  by `Academic Year`.

### 6.2 Multimedia Support
- Journals must support multiple attachments (Photos/Documents) via the `Media` module to 
  serve as forensic evidence.

---

## 7. Verification Plan (V&V View)

### 7.1 Telemetry Testing
- **Test 1**: Verify that attendance cannot be recorded outside of program start/end dates.
- **Test 2**: Verify that only the assigned supervisor can verify a journal entry.

### 7.2 Static Quality
- Ensure cyclomatic complexity < 10 for the compliance calculation algorithms.

---

_This blueprint constitutes the authoritative engineering record for Execution & Monitoring. 
Operational telemetry is the foundation of institutional trust._
