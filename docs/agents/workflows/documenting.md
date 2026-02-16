# Comprehensive Engineering Protocol: Documentation & Synchronization

This document establishes the authoritative **Documentation & Synchronization Protocol** for the
Internara project, adhering to **ISO/IEC 12207** and **ISO/IEC 11179** (Metadata registries).

---

## ⚖️ Core Mandates & Prohibitions (The Record Laws)

The Agent must adhere to these invariants to ensure the absolute integrity of the "Engineering
Record."

### 1. Document Integrity & Depth (The Absolute Law)

- **Non-Truncation Policy (CRITICAL)**: It is **STRICTLY PROHIBITED** to simplify, truncate, or
  reduce the technical depth of any documentation. Architectural details, rigorous definitions, and
  complex rationales MUST be preserved in their entirety. Any edit that results in a loss of
  technical precision or depth is a **Critical Quality Violation**.
- **English Invariant**: All technical documentation, PHPDoc, and commit messages MUST be authored
  in professional English.
- **Traceability Mandate**: Every technical document must trace its intent back to a **[SYRS-ID]**
  or **[STRS-ID]**.

### 2. Semantic & Metadata Laws

- **App vs Brand Identity**: Maintain the distinction between Product Identity (`app_info.json`) and
  Instance Identity (`brand_name`).
- **Doc-as-Code**: Every code modification MUST trigger a corresponding update to technical
  documentation. Documentation is an executable behavioral specification.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Ongoing Synchronization**: Ensuring the code state (PHPDocs, logic) matches the technical
  documentation (specs, architecture).
- **Docs-as-Code**: Managing all Markdown files in `docs/` and root (README, SECURITY, etc.) during
  active development.
- **Agentic Documentations**: Managing and synchronizing AI agent guidelines, protocols, and
  operational instructions within the `docs/agents/` directory.
- **Traceability Maintenance**: Updating module-level READMEs and SyRS links in code.
- **Distinction**: This protocol focuses on **active development sync**, whereas
  **[Publicating](./publicating.md)** focuses on **release baseline finalization**.

### 2. Authorized Actions

- **Metadata Patching**: Updating version and series codes.
- **Commit Execution**: Staging and committing documentation changes using **Conventional Commits**
  (`docs(...)`).
- **Relocation & Renaming**: Moving documentation to correct hierarchies (requires user approval).

---

## 📦 Artefact Synchronizations (Root Documentation)

The following root-level artifacts MUST be synchronized during the "System-Wide Realignment"
(Phase 4) to ensure project-wide consistency:

- **README.md**: Central project identity, versioning, and installation entry point.
- **CONTRIBUTING.md**: Guidelines for community participation and development standards.
- **CODE_OF_CONDUCT.md**: Ethical standards and behavioral expectations for the ecosystem.
- **SECURITY.md**: Vulnerability reporting protocols and security-by-design baselines.

---

## Phase 0: Contextual Immersion & Traceability

- **Requirement Lookup**: Identify the specific SyRS requirement affected.
- **Discovery Audit**: Scan the repository for any new or unindexed documentation artifacts
  (Markdown files) that are not yet referenced in the Technical Index or Module Catalog.
- **Previous Record Review**: Read existing blueprints and ADRs to understand the evolution history.

## Phase 1: Conceptual Documentation (The Blueprint)

- **Blueprinting**: Construct the narrative blueprint in `docs/developers/blueprints/`.
- **User Approval**: Present the conceptual approach for authorization.

## Phase 2: Construction Documentation (The Technical Record)

- **Analytical PHPDoc**: Document the "Why" and "What" of every public method.
- **i18n Extraction**: Synchronize `lang/` files; ensure zero hard-coded strings.
- **Exception Mapping**: Document custom exceptions in module-level READMEs.
- **Documentation Commits**: Use conventional commits (e.g.,
  `docs(user): update service contract definitions`).

## Phase 3: Module-Level Identity Synchronization

- **Module README Audit**: Update domain scope and service contracts.
- **Registry Sync**: Update `module.json` and `composer.json` metadata.

## Phase 4: System-Wide Realignment (The Sweep)

- **README & License Audit**: Update version badges and credits.
- **Security & Ethics Audit**: Verify `SECURITY.md` and `CODE_OF_CONDUCT.md`.
- **Agentic Sync**: Audit and synchronize AI agent instructions in `docs/agents/` to reflect any new
  protocols, architectural changes, or operational mandates.

## Phase 5: Publication Record Synchronization

- **Publication Notes Construction**: Draft the friendly publication notes in `docs/pubs/releases/`.
- **Handover Preparation**: Prepare the final metadata for the user-led publication.

## Phase 6: Final Verification & Keypoints

- **Traceability Audit**: Ensure all tests and code match the updated records.
- **PR Alignment**: Verify that the documentation updates are included in the relevant **Pull
  Request**.
- **Keypoints Summary**: Final report of Actions, Rationales, and Outcomes.

---

_Documentation is not an after-thought; it is the definitive proof of engineering excellence._
