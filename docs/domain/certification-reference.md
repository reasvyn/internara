# Certification — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Certification domain

This reference defines the structured aggregates and code layout within the **Certification** domain.

---

## 1. Certificate Aggregate
Orchestrates student credentials generation, verification codes signing, and secure downloads streaming.

- **Eloquent Models**:
  - `Certificate` (`app/Domain/Certification/Models/Certificate.php`)
- **Policies**:
  - `CertificatePolicy` (`app/Domain/Certification/Policies/CertificatePolicy.php`)
- **HTTP Controllers**:
  - `CertificateDownloadController` (`app/Domain/Certification/Http/Controllers/CertificateDownloadController.php`)
- **Command Actions**:
  - `IssueCertificateAction` (`app/Domain/Certification/Actions/IssueCertificateAction.php`)
  - `BatchIssueCertificateAction` (`app/Domain/Certification/Actions/BatchIssueCertificateAction.php`)
  - `RevokeCertificateAction` (`app/Domain/Certification/Actions/RevokeCertificateAction.php`)
- **Livewire UI Components**:
  - `CertificateList` (`app/Domain/Certification/Livewire/CertificateList.php`)
  - `StudentCertificates` (`app/Domain/Certification/Livewire/StudentCertificates.php`)
- **Support Layout Renderers**:
  - `CertificateRenderer` (`app/Domain/Certification/Support/CertificateRenderer.php`)
- **Enums**:
  - `CertificateStatus` (`app/Domain/Certification/Enums/CertificateStatus.php`)

---

## 2. CertificateTemplate Aggregate
Manages credentials layout custom HTML, template updates, and version counters.

- **Eloquent Models**:
  - `CertificateTemplate` (`app/Domain/Certification/Models/CertificateTemplate.php`)
- **Policies**:
  - `CertificateTemplatePolicy` (`app/Domain/Certification/Policies/CertificateTemplatePolicy.php`)
- **Command Actions**:
  - `CreateCertificateTemplateAction` (`app/Domain/Certification/Actions/CreateCertificateTemplateAction.php`)
- **Livewire UI Components**:
  - `CertificateTemplateManager` (`app/Domain/Certification/Livewire/CertificateTemplateManager.php`)

---

## 3. Document Aggregate
Compiles program landscape report files, lists analytics sheets, and handles file deletions.

- **Eloquent Models**:
  - `Document` (`app/Domain/Certification/Models/Document.php`)
- **Policies**:
  - `DocumentPolicy` (`app/Domain/Certification/Policies/DocumentPolicy.php`)
- **HTTP Controllers**:
  - `DocumentRenderController` (`app/Domain/Certification/Http/Controllers/DocumentRenderController.php`)
- **Command Actions**:
  - `GenerateReportAction` (`app/Domain/Certification/Actions/GenerateReportAction.php`)
  - `RenderDocumentAction` (`app/Domain/Certification/Actions/RenderDocumentAction.php`)
  - `DeleteReportAction` (`app/Domain/Certification/Actions/DeleteReportAction.php`)
- **Livewire UI Components**:
  - `ReportsManager` (`app/Domain/Certification/Livewire/ReportsManager.php`)
- **Support Layout Renderers**:
  - `DocumentRenderer` (`app/Domain/Certification/Support/DocumentRenderer.php`)
- **Enums**:
  - `DocumentCategory` (`app/Domain/Certification/Enums/DocumentCategory.php`)

---

## 4. DocumentTemplate Aggregate
Orchestrates HTML structures customizing for generated system reports and documents.

- **Command Actions**:
  - `SaveDocumentTemplateAction` (`app/Domain/Certification/Actions/SaveDocumentTemplateAction.php`)
- **Livewire UI Components**:
  - `TemplateManager` (`app/Domain/Certification/Livewire/TemplateManager.php`)
