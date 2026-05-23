# Document Domain

## Purpose

Document is the rendering engine and document storage layer for the entire application. It 
provides the pipeline for generating formatted output files — PDFs, spreadsheets, and other 
document types — from templates and domain-provided data. Critically, Document does not know 
what the content means. It provides the infrastructure (template management, rendering pipeline 
orchestration, output storage and retrieval) while each domain provides its own renderers that 
know how to produce domain-specific content. Certificates Certificate domain, registration forms 
Registration domain, internship reports Internship domain, and placement letters Placement domain 
all flow through Document's pipeline but are rendered by their respective domain renderers. This 
separation means the infrastructure evolves independently of the content.

## Boundary

**In scope:** Document model and persisted storage metadata (each generated document gets a 
record with type, format, size, generation timestamp, linked entity, and template version), 
template management (upload template files, categorize by document type, version templates, 
assign templates to renderers), rendering pipeline orchestration (resolve the correct template, 
call the registered domain renderer to gather data, invoke the output driver, store the result, 
create the Document record), output format resolution (PDF via headless browser or PDF library, 
XLSX/ODS via spreadsheet processor, DOCX, HTML — all extensible via a driver contract), 
download endpoints with authorization checks, document type registry (maps document type 
identifier strings to their renderer classes, populated by each domain at registration time).

**Out of scope:** Domain-specific content logic (each domain provides its own renderer 
implementation — Document only knows the renderer contract), certificate-specific rendering 
logic (Certificate domain provides the renderer), report content and structure decisions 
(Internship domain defines what goes in reports), document approval workflows (Registration 
domain owns application document upload workflows), raw file uploads that are not generated 
documents (spatie/laravel-medialibrary handles raw file uploads directly), template content 
authoring (templates are designed externally and uploaded as files).

## Key Concepts

**Documents.** A Document record represents one generated output file. It stores: the document 
type identifier (e.g., "certificate.completion", "registration.acceptance_letter"), the output 
format (pdf, xlsx, html), the file size in bytes, the generation timestamp, the exact template 
version used to generate it, a polymorphic link to the source entity that this document 
represents (e.g., which registration this certificate belongs to), and a media library reference 
to the stored file. Documents are completely immutable — once generated, neither the record 
metadata nor the file content can be modified. If a document needs to be regenerated (e.g., a 
certificate template was updated and the institution wants new certificates printed), a new 
Document record is created with a version number increment. The old document remains available in 
the audit trail.

**Templates.** Templates define the visual structure and styling of generated documents. They are 
uploaded as files and can take different forms depending on the output driver: Blade views (for 
server-side rendered PDFs converted via a headless browser), CSS stylesheets (for styling 
HTML-to-PDF conversion), XLSX template files (for spreadsheet generation with pre-defined layout 
and formulas), or DOCX template files (for Word document generation with merge fields). Each 
template is versioned — uploading a new version creates a new version record rather than 
overwriting the existing one. Templates are assigned to one or more document types (a template 
could serve both mid-term and final report types, for example). Template versioning ensures that 
every generated document can be traced back to the exact template that produced it, which is 
critical for legal and audit purposes.

**Rendering Pipeline.** When a document generation request arrives, the pipeline executes a 
deterministic sequence of steps. Step 1: resolve the correct template by querying the document 
type registry with the requested type identifier and locale, selecting the latest active template 
version. Step 2: discover the registered renderer for this document type — the renderer is a 
class provided by the owning domain that implements a simple Renderer contract (typically a 
method like `render(Entity $entity): array` that returns the structured data needed for the 
template). Step 3: call the renderer, which gathers and returns the required data from its 
domain, performing any necessary authorization or validation. Step 4: inject the returned data 
into the resolved template and invoke the appropriate output driver (PDF engine, spreadsheet 
builder, etc.). Step 5: store the rendered file via the media library with appropriate 
conversions and optimizations. Step 6: create a Document record with all metadata linking the 
stored file, template version, and source entity. If any step fails, the entire pipeline is 
considered failed and no partial document is stored.

**Document Type Registry.** A registry that maps document type identifier strings (like 
"certificate.completion", "registration.form", "internship.progress_report") to their renderer 
classes. When a new domain wants to generate documents, it registers its renderer with the 
registry during its service provider boot. The registry maintains exactly one renderer per 
document type — ambiguous mappings are rejected at registration time. Renderers are lazily 
resolved (instantiated only when a document of that type is requested), keeping the system 
memory-efficient when many document types are registered but few are in active use.

**Output Drivers.** The pipeline supports multiple output formats through a driver pattern. The 
PDF driver renders HTML templates into PDF files using either a headless browser (for complex 
layouts) or a PDF library (for simpler, faster rendering). The spreadsheet driver processes XLSX 
template files, injecting data into named ranges or cells. The HTML driver produces inline 
preview output for browser display without file download. The driver contract defines methods for 
rendering, saving, and stream/download responses. New drivers can be added by any domain that 
needs a new output format — just implement the driver contract and register it.

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to manage document templates so that generated documents have the correct layout and branding
- **Admin:** As an admin, I want to generate a document (certificate, report, letter) from a template so that output is consistent and auditable
- **Admin:** As an admin, I want to configure output formats (PDF, XLSX, HTML) per document type so that each use case gets the right format
- **User:** As a user, I want to download a generated document so that I can print or share it
- **Developer:** As a developer, I want to register a domain-specific renderer so that my domain can produce documents through the pipeline
- Documents are immutable after creation — regeneration creates a new version record; the 
original is preserved for audit.
- Template version changes do not retroactively alter already-generated documents — each 
document records the exact template version used.
- Each document type must have exactly one registered renderer — ambiguous mappings are 
rejected at service provider registration.
- Templates are versioned; document generation always uses a specific template version (defaults 
to latest active, can be pinned to a specific version).
- Generated files are stored exclusively via the media library — never on the local filesystem 
outside the storage directory.
- Document downloads require authorization — the caller must have view permission on the source 
entity linked to the document.
- The rendering pipeline is synchronous by default but supports queuing for long-running 
generations (configurable per document type).
- Template deletion is blocked if any Document record references that template version.

### Rendering Pipeline Flow

```
1. Resolve template (by type + locale)
2. Discover registered domain renderer
3. Renderer gathers domain data
4. Inject data into template
5. Invoke output driver (PDF/XLSX/HTML)
6. Store file via media library
7. Create Document record with metadata
```

### Key Operations

| Action | Description |
|--------|-------------|
| `SaveDocumentTemplateAction` | Uploads and saves a new document template version |
| `RenderDocumentAction` | Executes the rendering pipeline for a document type |
| `GenerateReportAction` | Generates a specific report document |
| `DeleteReportAction` | Removes a generated report |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Document` (generated document metadata with media library reference) |
| **Enums** | `DocumentCategory` — `APPLICATION`, `PERMIT`, `CERTIFICATE`, `REPORT`, `LETTER` |
| **Livewire** | `TemplateManager`, `ReportsManager` |
| **Support** | `DocumentRenderer` (rendering pipeline orchestration) |
| **Controllers** | `DocumentRenderController` (download endpoint) |

## Dependencies

| Dependency | Reason |
|---|---|
| Core | BaseModel for Document persistence, BaseAction for operations, SmartLogger for render 
audit trail |
| Media Library | Storing and serving generated document files via spatie/laravel-medialibrary |


