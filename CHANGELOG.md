# Changelog

All notable changes to Internara are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased] — 0.2.0

### Added
- User guide components to 20 admin pages
- Technical support email field with notification integration
- Brand name and site title auto-set from school name on setup finalize
- Loading overlay, step transitions, success animation for setup wizard
- `SystemCacheWarmCommand` for pre-warming caches after deployment

### Changed
- Extended 9 full-page form components from `BaseFormView`

### Fixed
- `Brand::name()` didn't check `brand_name` setting
- `Brand::title()` didn't check `site_title` setting
- Login button redirects back to `/setup` after setup finalize
- Translation keys nested incorrectly in `lang/*/validation.php`
- Stale namespace references in `AppInfo`/`AppIntegrity`
- Conditional support email in credential change warning notification

---

## [0.1.0] — 2026-06-10

### Added
- Complete 19-module architecture with 4-layer MVC + Action Triad
- Foundation modules: Core, Auth, User, SysAdmin, Setup, Settings
- Academic modules: Academics, Program, Enrollment
- Assessment modules: Assessment, Assignment, Evaluation
- Tracking modules: Journals, Incident
- Supporting modules: Partners, Certification, Reports, Document
- Setup wizard: 6-step guided installation with environment audit
- Flat RBAC with 5 roles + 2 functional roles (mentor, mentee)
- UUID v7 primary keys on all domain models
- SmartLogger dual-channel fluent logger with PII masking
- Dynamic theming system with light/dark mode
- Bilingual support (en/id)
- Content Security Policy enforcement via global middleware
- Cross-Role Proxy mechanism (teacher proxies supervisor, admin proxies both)
- QR cryptographic verification for certificates
- Laravel Pulse monitoring integration
- 15 Architecture Decision Records (ADRs)
- Comprehensive documentation: architecture, conventions, 19 module docs, user guide (22 chapters)

### Security
- CSP headers enforced globally
- Rate limiting on auth endpoints, recovery flows, setup token validation
- PII isolation in separate tables
- No raw SQL without parameterized binding

---

## Template

When adding a new release, copy the following section and fill it in:

```markdown
## [X.Y.Z] — YYYY-MM-DD

### Added
- 

### Changed
- 

### Deprecated
- 

### Removed
- 

### Fixed
- 

### Security
- 
```
