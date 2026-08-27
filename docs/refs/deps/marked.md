# Marked & DOMPurify — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for marked ^18.0.7 + dompurify ^3.4.14

## Description

Conceptual reference for the markdown rendering pipeline: **Marked 18** (`marked ^18.0.7`)
converts markdown to HTML, and **DOMPurify 3** (`dompurify ^3.4.14`) sanitizes the output before
it touches the DOM. The pair is treated as one unit because they are only ever used together.

---

## Installed & Role

| Package | Version | Role |
|---------|---------|------|
| `marked` | `^18.0.7` | Markdown → HTML parser (CommonMark-flavored, fast, extensible) |
| `dompurify` | `^3.4.14` | XSS sanitizer allowing a whitelist of HTML while stripping scripts/event handlers |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Parser output is untrusted** | Marked does not sanitize by design — raw HTML embedded in markdown passes straight through to its output |
| **Sanitizer contract** | DOMPurify parses the HTML and removes anything outside its allowlist (script tags, inline JS handlers, `javascript:` URLs) |
| **Mandatory pairing** | Rendering user markdown without sanitization is an XSS vector; the pipeline is always marked → DOMPurify → insert |

---

## How Internara Uses It

- Wired together in `resources/js/app.js` and the markdown editor component
  (`resources/views/ui/components/markdown-editor.blade.php`) — reflective logbook entries and other
  rich-text fields render through this pipeline
- Security rule of thumb repo-wide: never render user-supplied HTML without this pair (see
  `docs/conventions.md` §Security Conventions, XSS prevention)

## Quick References

- [Marked docs](https://marked.js.org) — parser API and extensions
- [DOMPurify docs](https://github.com/cure53/DOMPurify) — allowlist configuration
