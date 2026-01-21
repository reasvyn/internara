# Documentation Standards: The Writing Guide

To maintain a "Developer-First" documentation culture, all contributors must adhere to these
principles when adding or modifying documentation in Internara.

---

## 1. Core Principles

1.  **Single Source of Truth (SSOT)**: Each concept belongs to **one** authoritative document. If
    you need to mention a rule defined elsewhere, use a concise summary and a **hyperlink**. _Do not
    duplicate logic._
2.  **Aesthetic-Natural Principle**: Documentation should be calm and structurally minimalist. Use
    clear headers, bullet points, and avoid "wall of text" paragraphs.
3.  **Technical Depth & Rationale**: Don't just say _what_ to do; explain _why_ it is done that way.
    This helps other developers understand the architectural intent.
4.  **Actionable Language**: Use direct, developer-centric language. Include code snippets or CLI
    commands where they provide immediate value.
5.  **Iterative Artifact Synchronization**: Documentation is not an afterthought. A feature is only
    "Done" when its corresponding documentation accurately reflects the new system state.

---

## 2. Formatting Conventions

- **Markdown Only**: Use Markdown (`.md`) exclusively for all guides.
- **Code Blocks**: Always use language hints (e.g., ` ```php `, ` ```bash `) for syntax highlighting.
- **Table of Contents**: Required for any document exceeding 1,000 words or containing more than 4 major sections.
- **English Only**: All documentation must be written in English.

---

## 3. Directory Structure

- `docs/main/`: Public guides for users and system administrators.
- `docs/internal/`: Internal manuals for developers, including architecture, conventions, and roadmaps.
- `docs/versions/`: Post-release narratives documenting milestones.
- `docs/tests/`: Technical guides for the testing suite.

---

_Clear documentation is as important as clean code. By following these standards, you ensure that
Internara remains accessible and maintainable for years to come._
