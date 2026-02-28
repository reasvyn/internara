# Blueprint: Temporal Presence (BP-OPR-F401)

**Blueprint ID**: `BP-OPR-F401` | **Requirement ID**: `SYRS-F-401` | **Scope**: Monitoring & Vocational Telemetry

---

## 1. Context & Strategic Intent

This blueprint defines the real-time presence tracking mechanism. It ensures that student attendance is accurately captured via a geofenced Check-In/Check-Out workflow.

---

## 2. Technical Implementation

### 2.1 Temporal Integrity (S1 - Secure)
- **Range Guard**: Check-ins are restricted to the internship's `start_date` and `end_date`.
- **Zero-Trust Geofencing**: GPS coordinates MUST be validated against the placement's recorded location with a configurable radius.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Range Audit**: Verify check-in failure when current date is outside registration period.
    - **Geofence Audit**: Simulate check-in with coordinates > 1km from placement center (Must return 422).

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Precision Audit**: Verify `DateTime` immutable logic for overlapping attendance logs.
- **Architectural (`arch/`)**:
    - **Namespace Compliance**: Ensure `AttendanceLog` uses property hooks for computed metadata.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Ensure `Attendance` module does not depend on UI-specific geolocation scripts in its core service.
- **Feature (`Feature/`)**:
    - **N+1 Audit**: Verify that loading attendance history for a class uses optimized eager loading.

---

## 4. Documentation Strategy
- **User Guide**: Update `docs/wiki/daily-monitoring.md` to explain the real-time check-in/check-out workflow and geofencing requirements.
- **Developer Guide**: Update `modules/Attendance/README.md` to document the geofence validation algorithm and temporal integrity guards.
- **Troubleshooting**: Update `docs/wiki/daily-monitoring.md` with common check-in failure scenarios (GPS errors, radius issues).
