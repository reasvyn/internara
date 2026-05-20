# Feature Lifecycle Context

## What It Enforces

Every feature belongs to one of 8 sequential lifecycle phases: System Setup, Foundation, Internship Planning, Registration, Operations, Assessment, Period Closing, and Archiving. Major entities follow validated state transitions defined in Enum classes. Features are routed and placed in sidebar menus according to their phase.

## Why It Matters

The lifecycle provides a mental model for where features fit in the internship management process. When building a new feature, knowing its phase helps determine:
- Which existing Entities may have reusable business rules
- Which route group it belongs to
- Which sidebar group it goes in
- Which roles have access

State machines define valid transitions explicitly. An Internship moves through DRAFT → PUBLISHED → ACTIVE → COMPLETED (or CANCELLED at any point). Transition validation is in Enum `canTransitionTo()`. Business rules around transitions (checking preconditions beyond the state) are in Entity classes.

## When It Applies

When adding any new feature, identify its lifecycle phase first. Then check existing Entities for reusable rules, create the full stack (Action → Entity → Model → Migration → Livewire → View), register in the correct route group and sidebar menu group, translate in both languages, and test at the appropriate levels.

The role context table clarifies who does what:
- `super_admin` and `admin`: all phases
- `teacher` and `supervisor`: phases 4-5 (logbook, assessment, supervision)
- `student`: phases 3-5 (registration, operations, assessment)

Exceptions: Cross-cutting features (search, notifications, system settings) span multiple phases and should be placed in the most appropriate phase for their primary concern.
