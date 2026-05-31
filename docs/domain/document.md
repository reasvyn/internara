# Document Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 12 files in [reference](document-reference.md) exist

## Purpose

Document manages templates and renders output files — PDF generation for reports,
certificates, and other generated documents.

---

## Design Principles

### 1. Template-Driven Rendering

Every generated document originates from a template. No document content is hardcoded —
layouts, styling, and data mapping all flow through the template system. Changing a template
changes all future renderings without code changes.

### 2. Version Traceability

Every rendered document records the exact template version used at generation time.
If a certificate was issued with template v3, inspecting the certificate reveals that it
used v3 — even if the template has since been updated to v5.

### 3. Renderer Independence

The rendering pipeline is renderer-agnostic. Adding a new output format (e.g., DOCX in
addition to PDF and XLSX) requires only a new renderer implementation — no pipeline
changes.

---

## Domain Boundary

The Document domain owns the rendering engine that transforms templates and data into generated output files such as PDFs, spreadsheets, and other document formats. It manages document templates (Blade templates, CSS stylesheets, XLSX spreadsheets) that define the layout and styling of generated documents. The rendering pipeline follows six sequential steps: resolve the appropriate template, discover the correct renderer for the output format, gather the required data from source domains, inject data into the template, invoke the rendering driver, and store the generated result. Reports can be generated, viewed, and downloaded by authorized users. Every generated document records the exact template version used at the time of rendering for traceability. During program closure, the domain generates comprehensive archive reports containing grade summaries, attendance records, completion status, and other archival data.

Document does not own the source data used in rendering — student grades (Assessment), attendance records (Attendance), program definitions (Internship), certificate designs (Certificate), or any other business data. It owns only the templates and the rendering infrastructure. It does not own the content that fills the documents, only the mechanism that produces the final output files.

The domain depends on virtually every other domain as data sources for template rendering, but it does not own or manage that data. It provides the rendering service consumed by all domains that need generated output files, particularly Internship (for reports and archive documents) and Certificate (for credential documents).

---

## Key Features

- Upload and manage document templates including Blade templates, CSS stylesheets, and spreadsheet files.
- Render documents through a six-step pipeline that resolves templates, gathers data, renders content, and stores the output.
- Generate, view, and download program reports from the administrative interface.
- Serve authorized document downloads to authenticated users with access control.
- Record the exact template version used in every generated document for traceability.
- Generate comprehensive archive reports during program closure containing grade summaries, attendance records, and completion status.
- Preview generated PDF documents in a browser tab with a loading indicator during rendering.
- Download generated documents with a prominent download button and a progress indicator.
- Browse a report list with filters for program, report type, and generation date.
- Upload template files via drag and drop with an instant preview of the template name and type.
