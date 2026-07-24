# PDF Generation — Dompdf Rendering Pipeline

> **Last updated:** 2026-07-23 **Changes:** feat — initial PDF generation specification

## Description

Defines the PDF generation infrastructure built on barryvdh/laravel-dompdf: configuration,
Blade-based template rendering, memory management, the `CertificateRenderer` and
`DocumentRenderer` services, and conventions for creating new PDF-generating features.

---

## 1. Problem Statements

### PS-1 — PDF Generation Is Memory-Intensive

Dompdf loads entire HTML documents into memory. Batch operations (issuing 50 certificates
simultaneously) can exhaust PHP memory limits and crash the worker.

### PS-2 — Inconsistent PDF Templates

Without a standardized template system, each module creates PDFs differently: some use
inline HTML, some use Blade views, some hardcode styles. This leads to visual inconsistency
and maintenance burden.

### PS-3 — No Separation Between Content and Rendering

When PDF generation logic is embedded in Actions, changing the PDF layout requires modifying
business logic. Content (data) and presentation (template) should be separated.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | All PDF generation uses Dompdf as the single rendering engine |
| G2  | PDF templates are Blade views with consistent styling |
| G3  | Memory limits are configured for large batch operations |
| G4  | Renderer services encapsulate PDF-specific logic |
| G5  | Generated PDFs are stored via MediaLibrary for lifecycle management |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | PDF form filling or interactive PDFs |
| NG2  | PDF digital signatures |
| NG3  | Real-time PDF preview in browser |
| NG4  | PDF/A compliance for archival |
| NG5  | Multi-page PDF streaming (progressive rendering) |

---

## 3. User Stories / Use Cases

### UC-1 — System Generates Student Certificate

**Actor:** System (automated)
**Preconditions:** Internship completed, assessments finalized, certificate template exists
**Flow:**
1. `IssueCertificateAction` calls `CertificateRenderer`
2. Renderer fetches student data, internship details, assessment scores
3. Blade template rendered with data
4. Dompdf converts HTML to PDF
5. PDF stored via MediaLibrary on certificate model
**Postconditions:** Certificate PDF available for download

### UC-2 — Admin Generates Grade Card

**Actor:** Admin
**Preconditions:** Report finalized with grade data
**Flow:**
1. Admin clicks "Generate PDF" on report page
2. `DocumentRenderer` fetches report data
3. Grade card Blade template rendered
4. Dompdf generates PDF
5. PDF returned as download response
**Postconditions:** Admin receives grade card PDF

### UC-3 — Batch Certificate Issuance

**Actor:** System (queued job)
**Preconditions:** `BatchIssueCertificatesJob` dispatched
**Flow:**
1. Job iterates over student list
2. For each student, `CertificateRenderer` generates PDF
3. PDF stored, certificate record created
4. Progress updated in cache
5. On completion, admin notified
**Postconditions:** All certificates generated, failures logged

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-PDF1 | All PDF generation MUST use barryvdh/laravel-dompdf |
| FR-PDF2 | PDF templates MUST be Blade views in `resources/views/pdf/` |
| FR-PDF3 | Templates MUST use consistent CSS styling via shared layout |
| FR-PDF4 | `CertificateRenderer` MUST handle certificate-specific rendering |
| FR-PDF5 | `DocumentRenderer` MUST handle general document rendering |
| FR-PDF6 | Generated PDFs MUST be stored via MediaLibrary for lifecycle management |
| FR-PDF7 | Batch operations MUST set `memory_limit` to 512M or use queued jobs |
| FR-PDF8 | PDF rendering MUST NOT block the HTTP response for large documents |
| FR-PDF9 | Templates MUST use `__()` for all user-facing strings (localization) |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-PDF1 | Single PDF generation MUST complete within 10 seconds |
| NFR-PDF2 | Batch PDF generation MUST process ≥ 5 certificates per minute |
| NFR-PDF3 | PDF file size MUST be < 5MB per document |
| NFR-PDF4 | Memory usage per PDF MUST NOT exceed 256MB |
| NFR-PDF5 | Generated PDFs MUST be visually identical across browsers/OS |

---

## 6. API / Data Contracts

### CertificateRenderer

```php
class CertificateRenderer
{
    public function render(Certificate $certificate): string
    {
        // Fetch student, internship, assessment data
        // Render Blade template
        // Return PDF binary content
    }

    public function renderToStorage(Certificate $certificate): Media
    {
        // render() + store via MediaLibrary
    }
}
```

### DocumentRenderer

```php
class DocumentRenderer
{
    public function render(string $template, array $data): string
    {
        // Render Blade template with data
        // Return PDF binary content
    }

    public function renderToDownload(string $template, array $data, string $filename): Response
    {
        // render() + return as download response
    }
}
```

### Blade Template Structure

```html
<!-- resources/views/pdf/certificate.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Shared PDF styles */
        body { font-family: 'DejaVu Sans', sans-serif; }
        .certificate { /* layout */ }
    </style>
</head>
<body>
    <div class="certificate">
        <h1>{{ __('certificate.title') }}</h1>
        <p>{{ $studentName }}</p>
        <!-- ... -->
    </div>
</body>
</html>
```

### Dompdf Configuration

```php
// config/dompdf.php
'isRemoteEnabled' => false,
'isHtml5ParserEnabled' => true,
'defaultFont' => 'DejaVu Sans',
'isFontSubsettingEnabled' => true,
```

---

## 7. Design Decisions

### DD-1 — Blade Templates Over Raw HTML

**Decision:** PDF templates are Blade views, not raw HTML strings.

**Rationale:** Blade provides localization (`__()`), conditional rendering, loops, and
template inheritance. This avoids duplicating data formatting logic in PHP.

**Trade-off:** Blade compilation adds ~100ms per PDF. Acceptable for the maintainability gain.

### DD-2 — Renderer Services Over Inline PDF Logic

**Decision:** PDF generation logic lives in dedicated Renderer services, not in Actions.

**Rationale:** PDF rendering is infrastructure (template + engine), not business logic. Actions
should delegate to Renderers for any PDF-specific work.

**Trade-off:** Adds two service classes. The separation keeps Actions focused on business rules.

### DD-3 — Synchronous Thumbnail, Async Batch

**Decision:** Single PDF generation is synchronous; batch generation uses queued jobs.

**Rationale:** Single PDFs (user clicks "download") must return immediately. Batch PDFs
(certificates for 50 students) should not block the HTTP worker.

**Trade-off:** Batch jobs require queue infrastructure. Already covered by job-queue-infrastructure spec.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| PDF generation success rate | 99.9% |
| Single PDF generation time | < 10s |
| Batch PDF throughput | ≥ 5/min |
| Memory-related failures | 0 |
| Visual consistency across all PDF types | 100% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [file-uploads-media.md](file-uploads-media.md) | Media storage for rendered PDF files |

### Build Guide
After implementing this spec, the system can generate PDFs via DomPDF for certificates, grade cards, and official documents. PDFs use templates from document-templates and store output via media library. The final step in the lifecycle is reports, which archive grade cards as snapshots.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [reports.md](reports.md) | Reports generate grade card snapshots using PDF generation infrastructure |

---

## Quick References

- `config/dompdf.php` — Dompdf configuration
- `app/Certification/Certificate/Services/CertificateRenderer.php` — Certificate PDF renderer
- `app/Document/Services/DocumentRenderer.php` — General document renderer
- `resources/views/pdf/` — PDF Blade templates
- `docs/specs/certification.md` — Certificate issuance workflow
- `docs/specs/document-templates.md` — Document template management
- `docs/specs/job-queue-infrastructure.md` — Async processing for batch operations
