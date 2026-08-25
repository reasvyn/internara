# barryvdh/laravel-dompdf — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for barryvdh/laravel-dompdf v3.1.2 (dompdf/dompdf v3.1.6)

## Description

Conceptual reference for the PDF generation stack: **barryvdh/laravel-dompdf v3.1.2**, the Laravel
wrapper around the **DOMPDF v3.1.6** HTML-to-PDF renderer.

---

## Installed & Role

| Package | Version | Role |
|---------|---------|------|
| `barryvdh/laravel-dompdf` | `v3.1.2` (`^3.1`) | Laravel integration — facade, Blade-to-PDF loading, download/stream helpers |
| `dompdf/dompdf` | `v3.1.6` | The underlying CSS 2.1-capable HTML→PDF renderer |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Blade-to-PDF** | `Pdf::loadView('template', $data)` renders a Blade view (HTML + inline CSS) into a PDF document |
| **Stream / download** | Responses returned directly as streamed or downloaded files from controllers |
| **Asset embedding** | Images/fonts referenced via absolute paths or base64 — external URL fetching is limited by design |
| **CSS constraints** | Supports a CSS 2.1 subset plus some CSS 3 — no modern layout engines; templates must be print-oriented markup |

---

## How Internara Uses It

- Certificate rendering: `app/Certification/Certificate/Services/CertificateRenderer.php`,
  download endpoint in the Certification module controller
- Official document rendering: `app/Document/Services/DocumentRenderer.php` +
  `RenderDocumentAction`
- Global options in `config/dompdf.php`; template conventions in the `dompdf-development` skill
- Boundary rule: Reports module produces grade cards only — thesis content belongs to Assignment
  (`docs/refs/modules/reports.md`)

## Quick References

- [Wrapper docs](https://github.com/barryvdh/laravel-dompdf) — Laravel integration README
- [`docs/architecture/index.md`](../../architecture/index.md) — related rendering patterns
