# Documentation Split — Human vs AI Engineering

## Description

Documentation is split by audience and the split is directional: human-engineering docs live in
`docs/`, AI-engineering assets live in `.agents/`, with a strict reference direction between them.

---

Documentation is split by audience, and the split is directional:

| Tier | Location | Audience | Content |
|------|----------|----------|---------|
| **Human engineering** | `docs/` + root community files (`README.md`, `CONTRIBUTING.md`, `SECURITY.md`) | Developers, operators, contributors | Architecture, specs, guides, dependency references |
| **AI engineering** | `.agents/` | AI agents | Skills, agent memory (`context/`), plans |

**Reference direction (non-symmetric):**

| From | To | Allowed? |
|------|----|----------|
| `docs/` | `.agents/` | **No** — single exception: content written *for agents* (e.g., `## AI Agent Guides` sections, entries explicitly marked agent-oriented) |
| `.agents/` | `docs/` | **Yes** — skills and contexts cite `docs/` as SSOT instead of restating it |

Rules:

- Human docs stay **self-contained**: content serving both audiences is authored in `docs/`;
  `.agents/` references it rather than duplicating it.
- The exception is **content-based, not path-based**: a reference into `.agents/` is permitted only
  when the surrounding content itself serves AI agents. Human-facing sections never link
  `.agents/skills/**`, `.agents/plans/**`, or any other agent asset.
- When writing in `docs/`, agent assets may be *named* (e.g., "see the `spec-writing` skill") but
  never linked or path-referenced.
- `scan_doc_links.py` does not enforce this policy — check manually on every doc change.
