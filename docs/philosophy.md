# Project Philosophy — Guiding Principles, Values & Vision

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format
## Description

Guiding principles, values, and vision that shape Internara — beyond architecture, beyond code.

## 1. Education First, Technology Second

Internara exists to serve education, not the other way around. Every feature, every workflow, every decision is measured against one question: *Does this make it easier for schools to run better internship programs?*

Technology is never the goal. It is the enabler. We prioritize workflows that mirror real-world school operations over technically elegant abstractions. If a feature requires users to change how they work, we have failed.

## 2. Every School Deserves Great Tools

Indonesian SMA/SMK schools face a wide gap in resources, infrastructure, and technical capability. A school in a major city with dedicated IT staff and a school in a remote district with a single shared computer should both be able to use Internara effectively.

This means:
- **Zero external services by default.** SQLite, file cache, sync queue — run on a shared hosting plan for $5/month.
- **Progressive enhancement.** Start simple, add Redis, MySQL, workers, S3 only when you need them. No feature is locked behind infrastructure.
- **Offline-resilient operations.** Core workflows (attendance, logbooks) degrade gracefully when connectivity is intermittent.
- **No vendor lock-in.** Self-hosted, single-tenant. Your data stays on your infrastructure.

## 3. Simplicity Over Complexity, Always

Complexity is the enemy of reliability, security, and maintainability. Every feature, every abstraction, every pattern must justify its existence.

- **Actions over Services.** A single-responsibility class with one `execute()` method is simpler to understand, test, and change than a multi-method Service class.
- **Module colocation over flat layering.** Keeping everything related to "Enrollment" in one place is simpler than scattering it across eight directories.
- **Direct imports over elaborate decoupling.** Import a class from another module directly. Add events, contracts, or service providers only when you have a proven need.
- **Good enough today over perfect next week.** Build the simple version, ship it, iterate. Avoid speculative generality.

## 4. Maintainability Is a Feature

The codebase will outlive the original developers. Schools run internship programs year after year. The software must be maintainable by a fresh team with minimal ramp-up.

This means:
- **Consistent conventions.** One way to write an Action, one way to define an Enum, one way to structure a module. No surprises.
- **Self-documenting code.** Type hints, descriptive naming, colocated structure. The code reads like a specification.
- **Comprehensive documentation.** Every module has overview and reference docs. Every architecture decision has an ADR. Documentation is authoritative — code implements what docs describe.
- **Framework stability.** We stay current with Laravel LTS releases, but we pin versions and test upgrades thoroughly.

## 5. Data Sovereignty and Trust

Schools entrust Internara with sensitive student data — grades, attendance records, incident reports, personal information. This trust is non-negotiable.

- **Data resides on school infrastructure.** No telemetry, no usage reporting, no external API calls for core functionality.
- **Privacy by design.** PII masking, configurable retention policies, GDPR-compliant deletion workflows.
- **Transparent compliance.** Cross-role proxy, audit trails, immutable activity logs — every action is traceable and accountable.
- **No dark patterns.** No upsells, no data collection, no analytics pings.

## 6. Built for Indonesian Education, Designed for Adaptation

Internara is purpose-built for Indonesian SMA/SMK PKL (*Praktik Kerja Lapangan*) programs, with their specific requirements:
- Dual-role mentoring (teacher + industry supervisor)
- Competency-based assessment with rubrics
- Multi-company placement per student cohort
- Certificate issuance with QR verification
- Compliance with Indonesian education regulations (5-year record retention, NPSN integration)

But the architecture — Action-based MVC, module colocation, progressive infrastructure — is designed to adapt. The patterns are universal, even if the initial domain is specific.

## 7. Progress, Not Perfection

We ship continuously. Every iteration makes the system better for schools, students, teachers, and supervisors.

- **Pragmatic over dogmatic.** Framework dependencies in entities? Allowed when practical. Direct cross-module imports? Encouraged. Perfect purity is less important than developer velocity.
- **Gradual migration.** Every pattern has a clear migration path from simple to sophisticated. Never let the perfect be the enemy of the good.
- **Tested, not theoretical.** Every change is backed by tests. If it isn't tested, it doesn't work. If it doesn't work, it doesn't ship.
- **Open contribution.** The project welcomes contributions that align with these principles. Documentation is as important as code. Tests are as important as features.

---

## Core Values

| Value | In Practice |
|-------|-------------|
| **Accessibility** | Runs on $5/month shared hosting, SQLite default, no external services required |
| **Simplicity** | Single-responsibility Actions, colocated modules, direct imports |
| **Maintainability** | Consistent conventions, comprehensive docs, self-documenting code |
| **Trust** | Self-hosted, data sovereignty, PII masking, immutable audit trails |
| **Pragmatism** | Framework deps in entities allowed, gradual migration paths, ship continuously |
| **Quality** | Test-backed, documented-first, code-reviewed |

---

## What We Do Not Do

- We do not offer a multi-tenant SaaS version.
- We do not collect telemetry or usage data.
- We do not upsell features or lock functionality behind tiers.
- We do not sacrifice reliability for architectural purity.
- We do not ship untested code.
- We do not deprecate patterns without a documented migration path.
