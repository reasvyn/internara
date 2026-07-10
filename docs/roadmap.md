# Roadmap — Feature Plans & Implementation Status

> **Last updated:** 2026-07-10 **Changes:** expand — add broader module roadmap, milestones, and v1.0 target

---

## Release Timeline

| Version | Target Date | Focus | Status |
| ------- | ----------- | ----- | ------ |
| v0.1.0 | 2026-06-10 | Foundation + Core modules | ✅ Released |
| v0.2.0 | 2026-Q3 | UI polish, test coverage, bug fixes | 🔄 In Progress |
| v0.3.0 | 2026-Q4 | Advanced features, reporting, integrations | 📋 Planned |
| v1.0.0 | 2027-Q1 | Production readiness, security audit, documentation freeze | 🎯 Target |

---

## Module Maturity Overview

| Module | Maturity | Test Coverage | Docs | Notes |
| ------ | -------- | ------------- | ---- | ----- |
| Core | ✅ Stable | ≥ 90% | ✅ Complete | Foundation complete |
| Auth | ✅ Stable | ≥ 90% | ✅ Complete | Login, RBAC, recovery |
| User | ✅ Stable | ≥ 85% | ✅ Complete | Profile, notifications, dashboards |
| SysAdmin | ✅ Stable | ≥ 85% | ✅ Complete | User CRUD, announcements, audit |
| Setup | ✅ Stable | ≥ 90% | ✅ Complete | Installation wizard |
| Settings | ✅ Stable | ≥ 85% | ✅ Complete | System config, branding |
| Academics | ✅ Stable | ≥ 85% | ✅ Complete | School, departments, academic years |
| Program | 🟡 Stable | ≥ 80% | ✅ Complete | Internship lifecycle, groups |
| Partners | 🟡 Stable | ≥ 80% | ✅ Complete | Companies, partnerships |
| Enrollment | 🟡 Stable | ≥ 80% | ✅ Complete | Registration, placement, changes |
| Journals | 🟡 Stable | ≥ 75% | ✅ Complete | Logbook, attendance, absences |
| Guidance | 🟡 Stable | ≥ 75% | ✅ Complete | Supervision logs, handbooks |
| Assessment | 🟡 Stable | ≥ 80% | ✅ Complete | Rubrics, grading |
| Evaluation | 🟡 Stable | ≥ 75% | ✅ Complete | Feedback forms, auto-scoring |
| Assignment | 🟡 Stable | ≥ 75% | ✅ Complete | Tasks, submissions, grading |
| Incident | 🟡 Stable | ≥ 70% | ✅ Complete | Issue reporting, workflows |
| Certification | 🟡 Stable | ≥ 75% | ✅ Complete | Certificates, QR, verification |
| Reports | 🔄 Refactoring | ≥ 70% | 🔄 Updating | Grade card purification in progress |
| Document | 🟡 Stable | ≥ 70% | ✅ Complete | Templates, rendering, handbooks |

**Legend:** ✅ Complete / 🟡 Stable / 🔄 In Progress / 📋 Planned

---

## Current Sprint: v0.2.0 — Reports Module Overhaul

### Active: Grade Card Purification

The Reports module is being purified to remove all student thesis/written-report concepts and
simplify to a pure final grade card with DRAFT → FINALIZED workflow.

**Status:** 🔄 In Progress
**Issues:** #235–#245

See [Reports Overhaul Detail](#) for the full implementation plan.

#### Remaining Work

| Priority | Task | Issue | Status |
| -------- | ---- | ----- | ------ |
| P0 | Strip written-report infrastructure | #244 | 🔄 In Progress |
| P1 | Create DTO for CreateReportAction | #237 | 📋 Planned |
| P2 | Update documentation (remove thesis refs) | #245 | 📋 Planned |

---

## Upcoming: v0.3.0 Features

### 1. Advanced Reporting & Analytics

| Feature | Description | Priority |
| ------- | ----------- | -------- |
| Program completion dashboards | Cross-module aggregation of completion stats | High |
| Export engine | CSV/Excel export for all data tables | High |
| Advanced filtering | Multi-criteria search across enrollments | Medium |
| Trend analysis | Historical comparison of evaluation scores | Medium |

### 2. Integration & APIs

| Feature | Description | Priority |
| ------- | ----------- | -------- |
| Calendar sync | Export internship schedules to iCal/ICS | Medium |
| Webhook system | Event-driven webhooks for external integration | Medium |
| Bulk import | CSV import for students, companies, placements | Medium |
| API token auth | API authentication for external tool integration | Low |

### 3. User Experience

| Feature | Description | Priority |
| ------- | ----------- | -------- |
| Email notifications for all workflows | Missing notification events for state transitions | High |
| In-app help tooltips | Context-sensitive guidance throughout UI | Medium |
| Mobile-responsive improvements | Better tablet and phone experience | Medium |
| Bulk operations for grade cards | Batch finalize, print, export grade cards | Medium |

---

## v1.0.0 Release Criteria

| Criterion | Target | Status |
| --------- | ------ | ------ |
| All 19 modules ≥ 85% test coverage | 85% | 🔄 75-90% |
| No P0/P1 bugs | 0 open | ✅ Achieved |
| PHPStan level 8 clean | 0 errors | ✅ Achieved |
| Documentation complete | All docs current | 🔄 In Progress |
| Security audit | No critical findings | 📋 Planned |
| Performance benchmarks met | See [Scaling Guide](infrastructure/scaling.md) | 📋 Planned |
| Production deployment guide validated | 3 tiers tested | ✅ Achieved |

---

## Completed Roadmaps

| Initiative | Issues | Status |
| ---------- | ------ | ------ |
| Core module hardening | 12 issues (#208-#219) | ✅ All resolved |
| Settings module hardening | 15 issues (#220-#234) | ✅ All resolved |
| Setup wizard UX polish | Various UX fixes | ✅ Released in v0.2.0 |
| Documentation audit & improvements | Formatting, table counts, gaps | ✅ Completed 2026-07-10 |

---

## Dependency Graph

```mermaid
flowchart TD
    A[v0.1.0 Foundation] --> B[v0.2.0 Polish & Coverage]
    B --> C[v0.3.0 Features & Integration]
    C --> D[v1.0.0 Production Ready]
    
    B --> B1[Reports Purification]
    B --> B2[Test Coverage Improvement]
    B --> B3[UI/UX Polish]
    
    C --> C1[Advanced Reporting]
    C --> C2[Integrations]
    C --> C3[UX Improvements]
    
    D --> D1[Security Audit]
    D --> D2[Performance Optimization]
    D --> D3[Documentation Freeze]
```
