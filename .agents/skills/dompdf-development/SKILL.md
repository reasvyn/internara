---
name: dompdf-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). DOMPDF development — PDF generation, Blade-to-PDF rendering, and asset embedding. 1:1 mapping for barryvdh/laravel-dompdf."
upstream:
  - feature-building
downstream:
  - sync-docs
---

# DomPDF Development — PDF Generation

## When to Activate

Use this skill when generating PDFs from Blade views, embedding assets, or configuring DOMPDF options.

## Workflow

Follow `AGENTS.md §Agent Workflow` pipeline. This skill adds DOMPDF guidance.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Blade-to-PDF rendering (view, data, paper, options) | `.agents/rules/blade-to-pdf.md` | Any PDF generation |
| Asset embedding & fonts (images, CSS, unicode) | `.agents/rules/assets-fonts.md` | Embedding assets or custom fonts |

## References

| Topic | Doc |
|-------|-----|
| DOMPDF docs | `search-docs` with `barryvdh/laravel-dompdf` |
| Reports module | `docs/refs/modules/reports.md` |
