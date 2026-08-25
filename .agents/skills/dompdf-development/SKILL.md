---
name: dompdf-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). DOMPDF development — PDF generation, Blade-to-PDF rendering, and asset embedding. 1:1 mapping for barryvdh/laravel-dompdf."
upstream:
  - feature-building
downstream:
  - sync-docs
---

# DomPDF Development — PDF Generation

> **Last updated:** 2026-08-25 **Changes:** new skill — 1:1 mapping for barryvdh/laravel-dompdf

## When to Activate

Use this skill when generating PDFs from Blade views, embedding assets, or configuring DOMPDF options.

## Workflow

Follow `agent-workflow` pipeline. This skill adds DOMPDF guidance.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Blade-to-PDF rendering (view, data, paper, options) | `rules/blade-to-pdf.md` | Any PDF generation |
| Asset embedding & fonts (images, CSS, unicode) | `rules/assets-fonts.md` | Embedding assets or custom fonts |

## References

| Topic | Doc |
|-------|-----|
| DOMPDF docs | `search-docs` with `barryvdh/laravel-dompdf` |
| Reports module | `docs/refs/modules/reports.md` |
