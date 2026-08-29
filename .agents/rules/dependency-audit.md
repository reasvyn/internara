# Rules: Dependency Audit

> Source: Composer (PHP), npm (Node.js)
> Applicability: All projects with dependencies

## Overview

Dependency auditing checks for known vulnerabilities (CVEs) in third-party packages,
outdated packages, and dependency health.

## PHP Dependencies (Composer)

### Commands

```bash
# Check for known vulnerabilities
composer audit

# Check for outdated packages
composer outdated

# Validate composer.json structure
composer validate

# Show installed packages with versions
composer show
```

### What to Record

| Field | Description |
|-------|-------------|
| Package | Name of the vulnerable package |
| Version | Installed version |
| Advisory | CVE number or GitHub advisory ID |
| Severity | critical / high / medium / low |
| Title | Brief description of vulnerability |
| Fix | Available fixed version (if any) |

### Severity Classification

| Source | Severity | Action |
|--------|----------|--------|
| **CVE with CVSS ≥ 9.0** | Critical | Flag immediately; may need emergency patch |
| **CVE with CVSS 7.0-8.9** | High | Schedule fix within 1 week |
| **CVE with CVSS 4.0-6.9** | Medium | Include in next update cycle |
| **CVE with CVSS < 4.0** | Low | Accept or defer |
| **No CVE but abandoned** | Medium | Evaluate replacement |

### Package Health Indicators

```bash
# Check if package is abandoned
composer show --format=json | jq '.[] | select(.abandoned != null)'

# Check last update date
composer show --format=json | jq '.[] | {name: .name, updated: .updated}'
```

**Red Flags:**
- Package abandoned with no replacement
- Last commit > 2 years ago
- Open security issues without maintainer response
- < 1 maintainer
- Very few downloads relative to alternatives

## JavaScript Dependencies (npm)

### Commands

```bash
# Check for known vulnerabilities
npm audit

# Check for outdated packages
npm outdated

# Validate package.json
npm pkg validate
```

### What to Record

Same fields as PHP. Additional npm-specific concerns:

| Concern | Check |
|---------|-------|
| Dev vs production | `npm audit --omit=dev` for production-only |
| Transitive dependencies | Most npm vulnerabilities are in transitive deps |
| Fix available | `npm audit fix` or manual update |

## Dependency Pinning Strategy

| Strategy | Pros | Cons |
|----------|------|------|
| Exact version (`1.2.3`) | Deterministic builds | Miss security patches |
| Caret (`^1.2.3`) | Security patches auto-included | Breaking changes possible |
| Tilde (`~1.2.3`) | Patch-level safety | May miss minor fixes |
| Range (`>=1.0 <2.0`) | Maximum flexibility | Unpredictable |

**Recommendation:** Use caret (`^`) for most dependencies; exact pin for critical infrastructure.

## License Compliance

```bash
# Check licenses
composer show --format=json | jq '.[].license'
```

| License | Risk |
|---------|------|
| MIT, BSD, Apache 2.0 | Low (permissive) |
| LGPL | Medium (copyleft on modifications) |
| GPL | High (copyleft on entire work) |
| AGPL | Very High (network copyleft) |
| Proprietary/Commercial | High (usage restrictions) |

## Vulnerability Sources

| Source | URL | Coverage |
|--------|-----|----------|
| GitHub Advisories | https://github.com/advisories | Comprehensive |
| Packagist Advisory | https://packagist.org/security/advisories | PHP-specific |
| NVD (NIST) | https://nvd.nist.gov/ | Universal |
| npm Advisory | https://github.com/advisories?ecosystem=npm | JavaScript |
| Snyk | https://snyk.io/vuln/ | Cross-ecosystem |

## Output Format

```json
{
  "php_dependencies": {
    "total": 25,
    "outdated": 3,
    "vulnerable": 1,
    "abandoned": 0,
    "findings": [
      {
        "package": "vendor/package",
        "installed": "1.2.3",
        "latest": "1.2.5",
        "advisory": "CVE-2024-XXXXX",
        "severity": "high",
        "title": "Remote code execution via...",
        "fix": "1.2.5"
      }
    ]
  },
  "js_dependencies": {
    "total": 15,
    "outdated": 5,
    "vulnerable": 0,
    "findings": []
  }
}
```
