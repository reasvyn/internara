# Rules: SANS Top 25 Most Dangerous Software Errors (2024)

> Source: https://cwe.mitre.org/top25/
> Version: 2024
> Applicability: All software, with PHP/Laravel focus

## Overview

The SANS Top 25 is an annual list of the most dangerous software errors, ranked by a combined
score of frequency (how often the weakness appears in real-world vulnerabilities) and impact
(how severe the consequences are). The list combines CWE entries from the NVD (National
Vulnerability Database) with real-world CVE data.

## Combined Score Rankings (2024)

### Rank 1-5: Critical

| Rank | CWE | Name | Score | PHP/Laravel Relevance |
|------|-----|------|-------|----------------------|
| 1 | CWE-787 | Out-of-bounds Write | 66.1 | Low (memory-safe language) |
| 2 | CWE-79 | Improper Neutralization of Input During Web Page Generation (XSS) | 46.0 | **HIGH** — Blade templates |
| 3 | CWE-89 | Improper Neutralization of Special Elements used in an SQL Command (SQL Injection) | 45.0 | **HIGH** — Database queries |
| 4 | CWE-416 | Use After Free | 32.3 | Low (memory-safe language) |
| 5 | CWE-78 | Improper Neutralization of Special Elements used in an OS Command | 27.9 | Medium — exec(), system() |

### Rank 6-10: High

| Rank | CWE | Name | Score | PHP/Laravel Relevance |
|------|-----|------|-------|----------------------|
| 6 | CWE-20 | Improper Input Validation | 23.1 | **HIGH** — All user input |
| 7 | CWE-125 | Out-of-bounds Read | 19.1 | Low |
| 8 | CWE-22 | Improper Limitation of a Pathname to a Restricted Directory (Path Traversal) | 18.3 | **HIGH** — File operations |
| 9 | CWE-352 | Cross-Site Request Forgery (CSRF) | 17.2 | **HIGH** — Forms/API |
| 10 | CWE-434 | Unrestricted Upload of File with Dangerous Type | 16.7 | **HIGH** — File uploads |

### Rank 11-15: High-Medium

| Rank | CWE | Name | Score | PHP/Laravel Relevance |
|------|-----|------|-------|----------------------|
| 11 | CWE-862 | Missing Authorization | 16.3 | **HIGH** — Routes/Policies |
| 12 | CWE-863 | Incorrect Authorization | 15.8 | **HIGH** — Policy logic |
| 13 | CWE-798 | Use of Hard-coded Credentials | 14.8 | **HIGH** — Config/source |
| 14 | CWE-306 | Missing Authentication for Critical Function | 14.5 | **HIGH** — Admin endpoints |
| 15 | CWE-190 | Integer Overflow or Wraparound | 13.9 | Low (PHP handles automatically) |

### Rank 16-20: Medium

| Rank | CWE | Name | Score | PHP/Laravel Relevance |
|------|-----|------|-------|----------------------|
| 16 | CWE-502 | Deserialization of Untrusted Data | 13.2 | Medium — unserialize() |
| 17 | CWE-287 | Improper Authentication | 12.8 | **HIGH** — Login/session |
| 18 | CWE-476 | NULL Pointer Dereference | 12.5 | Low (PHP returns null) |
| 19 | CWE-732 | Incorrect Permission Assignment for Critical Resource | 12.3 | **HIGH** — File permissions |
| 20 | CWE-94 | Improper Control of Generation of Code ('Code Injection') | 11.8 | Medium — eval(), dynamic calls |

### Rank 21-25: Medium

| Rank | CWE | Name | Score | PHP/Laravel Relevance |
|------|-----|------|-------|----------------------|
| 21 | CWE-611 | Improper Restriction of XML External Entity Reference | 11.5 | Low (if no XML) |
| 22 | CWE-918 | Server-Side Request Forgery (SSRF) | 11.3 | **HIGH** — HTTP client |
| 23 | CWE-77 | Improper Neutralization of Special Elements used in a Command | 11.0 | Medium — shell commands |
| 24 | CWE-119 | Improper Restriction of Operations within the Bounds of a Memory Buffer | 10.7 | Low |
| 25 | CWE-269 | Improper Privilege Management | 10.5 | **HIGH** — RBAC |

## PHP/Laravel Focused CWEs

The following CWEs are most relevant to PHP/Laravel applications and should receive the
highest scrutiny:

### Tier 1 — Must Check (Directly Applicable)

| CWE | Check Method |
|-----|-------------|
| **CWE-79** (XSS) | Search for `{!! !!}` in Blade; check all output escaping |
| **CWE-89** (SQLi) | Search for `DB::raw()`, `select()`, `whereRaw()` with variables |
| **CWE-22** (Path Traversal) | Search for `file_get_contents()`, `Storage::get()` with user input |
| **CWE-352** (CSRF) | Verify `@csrf` on all forms; check API state-changing endpoints |
| **CWE-434** (File Upload) | Check upload validation (type, size, content) |
| **CWE-862** (Missing Authz) | Verify middleware on routes; check Policy registration |
| **CWE-798** (Hard-coded Creds) | Search for `password`, `secret`, `key`, `token` in source |
| **CWE-306** (Missing Authn) | Check all admin/management routes require authentication |

### Tier 2 — Should Check (Common in PHP)

| CWE | Check Method |
|-----|-------------|
| **CWE-20** (Input Validation) | Check FormRequest classes; validate all inputs |
| **CWE-13** (PHP-specific) | Check for `unserialize()` with user data |
| **CWE-502** (Deserialization) | Search for `unserialize()`, `igbinary_unserialize()` |
| **CWE-732** (File Permissions) | Check file permissions on uploaded files |
| **CWE-94** (Code Injection) | Search for `eval()`, `assert()`, `call_user_func()` with user input |
| **CWE-269** (Privilege Mgmt) | Check RBAC implementation, role hierarchy |
| **CWE-918** (SSRF) | Check HTTP client usage with user URLs |

### Tier 3 — Nice to Check (Less Common)

| CWE | Check Method |
|-----|-------------|
| **CWE-611** (XXE) | Check if XML parsing exists; if so, disable external entities |
| **CWE-77** (Command Injection) | Search for `exec()`, `system()`, `passthru()`, `shell_exec()` |

## Scoring Methodology

The SANS/CWE scoring combines:

1. **Frequency (F)** — How often the weakness appears in CVE data
   - F = Number of CVEs with this CWE / Total CVEs in period
2. **Impact (I)** — Average CVSS score of CVEs with this CWE
   - I = Mean(CVSS scores) for all CVEs with this CWE
3. **Combined Score** = F × I × 100

A higher combined score means the weakness is both common AND impactful.

## Application to Code Review

When reviewing code, prioritize findings by SANS rank:

1. Check Tier 1 CWEs first (they have the highest combined score)
2. Any finding in CWE-79, CWE-89, CWE-22 is automatically High/Critical
3. CWE-862/863 (Authorization) findings are common in Laravel apps — check every route
4. CWE-798 (Hard-coded Credentials) is easy to check with grep — do it first
