# Documentation Standards: The Writing Guide

To maintain a "Developer-First" documentation culture, all contributors must adhere to these
principles when adding or modifying documentation in Internara.

> **Single Source of Truth (SSoT):** The authoritative reference for all documentation is
> **[Internara Specs](../internal/internara-specs.md)**. No document may contradict the specs.

---

## 1. Core Principles

1.  **Analytical Precision**: Documentation must be grounded in facts and architectural reality.
2.  **Aesthetic-Natural Principle**: Documentation should be calm and structurally minimalist. Use
    clear headers and bullet points.
3.  **Iterative Artifact Synchronization**: A feature is only "Done" when its corresponding documentation
    (READMEs, Guides, Changelog) accurately reflects the new system state.
4.  **Terminological Consistency**: Use standardized terms (e.g., `exception::messages`, `setting()`,
    `Blueprint`, `Series Code`).

---

## 2. Formatting & Language

- **Markdown Only**: Use Markdown (`.md`) exclusively.
- **English Only**: All documentation must be written in English.
- **User-Centric Release Notes**: Written in friendly, accessible language (Indonesian & English).
- **Internal Technical Docs**: Written in professional, technical English.

---

## 3. Directory Structure

- `docs/internal/`: **Internal Engineering Hub** (Architecture, Conventions, SDLC).
- `docs/versions/`: Post-release narratives (Analytical Version Notes).
- `docs/blueprints/`: Architectural planning for active development series.
- `docs/main/`: Public-facing project overview and installation guides.

---

## 4. Specific Naming Rules

- **Exception Messages:**
    - Module-specific: `module::exceptions.key`
    - General: `exception::messages.key`
- **Application Settings:**
    - Always use `setting('key')`.

---

_Clear documentation is as important as clean code. By following these standards, you ensure that
Internara remains accessible and maintainable._