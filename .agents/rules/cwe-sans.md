# Rules: CWE/SANS — Common Weakness Enumeration

> Source: https://cwe.mitre.org/
> Version: CWE 4.x
> Applicability: All software

## Overview

CWE is a community-developed list of common software and hardware weakness types. Each CWE
entry defines a specific type of weakness with related detection methods, consequences, and
mitigations. CWE is more granular than OWASP or SANS Top 25 — it defines the specific
weakness types that lead to vulnerabilities.

## CWE Categories Relevant to PHP/Laravel

### Category 1: Input Validation Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-20 | Improper Input Validation | Missing validation before processing user input | High |
| CWE-22 | Path Traversal | `file_get_contents($input)`, `Storage::get($input)` | Critical |
| CWE-79 | Improper Neutralization of Input During Web Page Generation (XSS) | `{!! !!}` without sanitization | High |
| CWE-89 | SQL Injection | `DB::raw()` with variables, string concatenation in queries | Critical |
| CWE-78 | OS Command Injection | `exec()`, `system()`, `passthru()` with user input | Critical |
| CWE-94 | Code Injection | `eval()`, `assert()` with user input | Critical |
| CWE-116 | Improper Encoding or Escaping of Output | Missing `htmlspecialchars()` / `{{ }}` | High |

### Category 2: Authentication Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-287 | Improper Authentication | Missing auth checks, session fixation | High |
| CWE-288 | Authentication Bypass | Bypass possible via direct URL access | Critical |
| CWE-306 | Missing Authentication for Critical Function | Admin endpoints without auth middleware | High |
| CWE-521 | Weak Password Requirements | < 8 chars, no complexity | Medium |
| CWE-798 | Use of Hard-coded Credentials | Password/key in source code | Critical |
| CWE-613 | Insufficient Session Expiration | Sessions don't expire | Medium |

### Category 3: Authorization Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-862 | Missing Authorization | No Policy/middleware on routes | High |
| CWE-863 | Incorrect Authorization | Policy logic flawed | High |
| CWE-269 | Improper Privilege Management | Role hierarchy incorrect | High |
| CWE-275 | Permission Issues | File permissions too open | Medium |
| CWE-276 | Incorrect Default Permissions | File created with world-readable perms | Medium |
| CWE-668 | Exposure of Resource to Wrong Sphere | Cross-tenant data access | High |

### Category 4: Cryptographic Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-327 | Broken/Risky Crypto Algorithm | MD5/SHA1 for passwords | High |
| CWE-328 | Use of Weak Hash | md5(), sha1() for security | High |
| CWE-330 | Insufficiently Random Values | mt_rand() for security tokens | High |
| CWE-338 | Use of Cryptographically Weak PRNG | rand(), mt_rand() for crypto | High |
| CWE-347 | Improper Verification of Crypto Signature | Unsigned updates | High |
| CWE-798 | Hard-coded Credentials | Secret keys in source | Critical |

### Category 5: Error Handling Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-209 | Generation of Error Message Containing Sensitive Info | Stack traces to users | Medium |
| CWE-215 | Insertion of Sensitive Information Into Debug Code | dd(), dump() in production | Medium |
| CWE-396 | Catch Generic Exception | `catch (\Exception $e)` too broad | Low |
| CWE-391 | Unchecked Error Condition | Return value of critical function ignored | Medium |

### Category 6: Code Quality Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-489 | Active Debug Code | dd(), dump(), ray() in production | Medium |
| CWE-502 | Deserialization of Untrusted Data | unserialize() with user data | High |
| CWE-704 | Incorrect Type Conversion | Unsafe type casting | Medium |
| CWE-754 | Improper Check for Exceptional Conditions | Missing try/catch on I/O | Medium |

### Category 7: File and Resource Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-434 | Unrestricted Upload of File with Dangerous Type | No file type validation | High |
| CWE-552 | Files Accessible to External Parties | Uploaded files in web root | Medium |
| CWE-732 | Incorrect Permission Assignment | chmod 777, world-writable | Medium |

### Category 8: SSRF and Network Weaknesses

| CWE ID | Name | Detection Pattern | Severity |
|--------|------|-------------------|----------|
| CWE-918 | Server-Side Request Forgery | Http::get($userInput) | High |
| CWE-601 | Open Redirect | Redirect with user-controlled URL | Medium |

## Detection Methods

### Static Analysis Patterns

```bash
# SQL Injection (CWE-89)
grep -rn "DB::raw\|->whereRaw\|->selectRaw\|->orderByRaw" app/

# XSS (CWE-79)
grep -rn "{!!" resources/views/

# Command Injection (CWE-78)
grep -rn "exec\|system\|passthru\|shell_exec\|proc_open\|popen" app/

# Code Injection (CWE-94)
grep -rn "eval\|assert\|call_user_func\|call_user_func_array" app/

# Path Traversal (CWE-22)
grep -rn "file_get_contents\|file_put_contents\|fopen\|fread" app/

# Deserialization (CWE-502)
grep -rn "unserialize\|igbinary_unserialize" app/

# Hard-coded Credentials (CWE-798)
grep -rn "password.*=.*['\"]" app/ config/

# Debug Code (CWE-489)
grep -rn "dd(\|dump(\|ray(\|var_dump(\|print_r(" app/

# Weak Randomness (CWE-338)
grep -rn "mt_rand\|rand(" app/

# SSRF (CWE-918)
grep -rn "Http::get\|Http::post\|file_get_contents\|curl_" app/
```

### Runtime Analysis Patterns

- Trigger error conditions (invalid input, missing resources)
- Verify error messages don't leak sensitive information
- Test authentication bypass (access protected routes unauthenticated)
- Test authorization bypass (access other users' resources)
- Test rate limiting (rapid-fire requests)

## Severity Mapping

Use this mapping for PHP/Laravel applications:

| CWE | Base Severity | Adjustment Factors |
|-----|--------------|-------------------|
| CWE-89 | Critical | Always Critical — SQL injection is devastating |
| CWE-79 | High (stored), Medium (reflected) | Stored XSS in admin panel = Critical |
| CWE-78 | Critical | OS command injection = full server compromise |
| CWE-22 | High (sensitive files), Medium (limited) | If /etc/passwd readable = Critical |
| CWE-287 | High | Authentication bypass = Critical |
| CWE-862 | High | Missing authorization on mutations = Critical |
| CWE-798 | Critical (in source), Medium (in config) | Depends on who can see the source |
| CWE-502 | Critical | Deserialization = full compromise |
| CWE-489 | Medium | Debug code = information disclosure |
| CWE-918 | High | SSRF from web app = internal network access |
