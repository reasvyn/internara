# Branding Orchestration: Product vs. Institutional Identity

This document formalizes the protocols for managing system identity within Internara, 
adhering to **[SYRS-C-004]**. It defines the relationship between the authoritative 
**Product Identity** and the flexible **Institutional Branding**.

---

## 🏛️ The Dual-Identity Invariant

To ensure clear attribution and institutional ownership, Internara operates under a 
hierarchical identity model.

### 1. Product Identity (`app_name`)
- **Authority**: Defined in the root `app_info.json`.
- **Value**: "Internara".
- **Usage**: Technical metadata, versioning, system error logs, and developer-facing 
  documentation.
- **SSoT**: Accessed via `Modules\Core\Services\Contracts\MetadataService`.

### 2. Institutional Branding (`brand_name`)
- **Authority**: Database-managed via the `Setting` module.
- **Value**: e.g., "SMK Negeri 1 Jakarta".
- **Usage**: Public-facing UI, student reports, email headers, and dashboards.
- **Fallback**: If no `brand_name` is configured, the system MUST revert to `app_name`.

---

## 🛠️ Implementation Protocols

### 1. UI Resolution
UI components must never hard-code "Internara". Instead, they must use the `setting()` 
helper with a fallback.

```blade
<!-- Correct Pattern -->
<title>{{ setting('brand_name', setting('app_name')) }}</title>

<!-- Incorrect Pattern -->
<title>Internara | Dashboard</title>
```

### 2. Logo Orchestration
- **System Logo**: The default Internara brand assets reside in `public/brand/`.
- **Instance Logo**: Uploaded assets reside in the `Media` module and are prioritized 
  over the system logo if the "Custom Branding" toggle is enabled.

---

## 🧠 Metadata Sovereignty

### 2.1 The `MetadataService`
The `Core` module provides the authoritative service for retrieving technical metadata.

```php
interface MetadataService {
    public function getVersion(): string;
    public function getSeriesCode(): string;
    public function getProductAttributes(): array;
}
```

### 2.2 Versioning Transparency
Every page footer MUST display the current system version retrieved from the 
`MetadataService` to facilitate forensic bug reporting and support.

---

## ⚖️ Legal & Attribution Invariant

While institutional branding is encouraged, the system's attribution metadata (Author, 
License, Series Code) remains immutable. Attempting to redact "Internara" from technical 
headers or root configuration files is considered a violation of the **Attribution & 
Integrity Protection** protocol.

---

_By maintaining this separation, Internara provides a white-label experience for 
institutions while preserving the integrity of its engineering lineage._
