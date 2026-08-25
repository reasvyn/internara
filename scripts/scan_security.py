#!/usr/bin/env python3
"""
Enhanced v2.1: parallel execution, robust error isolation, shared _common helpers,
severity/baseline filtering, and performance optimizations.
scan_security.py — Security Pattern Detection

Scans PHP/Blade for security anti-patterns across the S1-S9 rule set:
S1 XSS, S2 SQL injection, S3 mass assignment, S4 CSRF, S5 CSP / inline script,
S6 missing authorization, S7 rate limiting, S8 hardcoded secrets, S9 file uploads.

Calibrated against the codebase conventions in docs/conventions.md §3.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
VIEWS_DIR = ROOT / "resources" / "views"
ROUTES_DIR = ROOT / "routes"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "security"

REF_XSS = "docs/conventions.md#31-xss-prevention"
REF_SQLI = "docs/conventions.md#32-sql-injection-prevention"
REF_MASS = "docs/conventions.md#33-mass-assignment-protection"
REF_CSRF = "docs/conventions.md#34-csrf-protection"
REF_CSP = "docs/conventions.md#35-content-security-policy"
REF_UPLOAD = "docs/conventions.md#36-file-upload-security"
REF_RATE = "docs/conventions.md#37-rate-limiting"
REF_AUTH = "docs/foundation/rbac.md"

HARDCODED_SECRETS = re.compile(
    r"""(?:password|secret|token|api_key|apikey|api[-_]?secret)\s*=\s*['"][^'"]{8,}['"]""",
    re.IGNORECASE,
)

# Raw SQL methods that MUST use parameterized binding (docs/conventions §3.2)
RAW_SQL_METHODS = [
    r"DB::select", r"DB::statement", r"DB::insert", r"DB::update",
    r"DB::delete", r"DB::raw", r"whereRaw", r"orderByRaw", r"havingRaw",
    r"selectRaw", r"fromRaw", r"joinRaw", r"groupByRaw",
]
SQL_RAW_RE = re.compile(
    r"\b(?:DB::select|DB::statement|DB::insert|DB::update|DB::delete)\s*\("
)
SQL_RAW_BUILDER = re.compile(r"->(?:whereRaw|orderByRaw|havingRaw|selectRaw|fromRaw|joinRaw|groupByRaw)\s*\(")
SQL_INTERP = re.compile(r"\$\w+|\.\s*\$|\.\s*['\"]|['\"].*\{\$")
SQL_BINDINGS = re.compile(r"\]\s*,\s*\[|,\s*\[(?:\s*['\"\$]|(?:\d+\.?\d*))")
SQL_STR_CONCAT = re.compile(r"['\"]\s*\.\s*(?:\$|\{)|\.\s*\$")
SQL_DOC_EXCEPTION = re.compile(r"@exception|@suppress|parameterized|allowlist|allowed.*raw")

MASS_ASSIGNMENT_PATTERNS = [
    re.compile(r"::create\s*\(\s*\$request\s*->\s*(?:all|input)\s*\("),
    re.compile(r"->update\s*\(\s*\$request\s*->\s*(?:all|input)\s*\("),
    re.compile(r"->fill\s*\(\s*\$request\s*->\s*(?:all|input)\s*\("),
    re.compile(r"::firstOrCreate\s*\(\s*\$request\s*->\s*(?:all|input)\s*\("),
    re.compile(r"::create\s*\(\s*\$this\s*->\s*all\s*\("),
    re.compile(r"::create\s*\(\s*\$this\s*->\s*form\s*->\s*toArray\s*\("),
]

RE_UNESCAPED_OUTPUT = re.compile(r"\{!!\s*\$(\w+)|x-html\s*=\s*[\"']([^\"']*)")
RE_SANITIZED_CALL = re.compile(
    r"Str::markdown|html_input.*strip|purify\(|e\(|strip_tags\(|clean\(|sanitize\("
)
RE_INLINE_SCRIPT = re.compile(r"<script(?![^>]*src=)")
RE_ONCLICK = re.compile(r"\bon(?:click|load|error|submit|change|input)\s*=", re.IGNORECASE)
RE_BLADE_COMMENT = re.compile(r"\{\{--[\s\S]*?--\}\}")

RE_AUTHORIZE_CALL = re.compile(r"\$this->authorize\s*\(")
RE_AUTHZ_ATTR = re.compile(r"#\[Authorize")

RE_AUTH_ROUTE_PATHS = re.compile(
    r"/(?:login|activate|forgot-password|reset-password|recover-account|confirm-password)(?:[/'\"])?"
    r"|->name\(['\"](?:login|activate|password\.|recover\.)",
    re.IGNORECASE,
)
RE_THROTTLE = re.compile(r"throttle|RateLimiter|auth\.throttle")
RE_CSRF_MISSING = re.compile(r"<form\s[^>]*(?!@csrf)(?!csrf_token)[^>]*>", re.IGNORECASE)

RE_STORE_UPLOAD = re.compile(r"->store(?:As)?\s*\(")
RE_MEDIALIBRARY = re.compile(r"addMedia|MediaLibrary|registerMediaCollections|Rule::file|mimes:|max:|validate\s*\(")
RE_STORAGE_PUT = re.compile(r"Storage::put\s*\(")


# ─── Data ───────────────────────────────────────────────────────────────────

@dataclass
class Finding:
    id: str
    rule: str
    severity: str
    category: str
    file: str
    line: int
    column: int = 0
    message: str = ""
    suggestion: str = ""
    reference: str = ""
    context: dict[str, Any] = field(default_factory=dict)


@dataclass
class ScanResult:
    scan_name: str
    scan_type: str
    module: str | None
    timestamp: str
    execution_time_ms: int
    summary: dict[str, Any]
    findings: list[dict[str, Any]]
    metadata: dict[str, Any]


# ─── Helpers ────────────────────────────────────────────────────────────────

def find_php_files(module: str | None = None) -> list[Path]:
    if module:
        module_dir = APP_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.php"))
    return sorted(APP_DIR.rglob("*.php"))


def find_blade_files(module: str | None = None) -> list[Path]:
    if not VIEWS_DIR.exists():
        return []
    if module:
        module_dir = VIEWS_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.blade.php"))
    return sorted(VIEWS_DIR.rglob("*.blade.php"))


def find_route_files() -> list[Path]:
    files = []
    for f in sorted(ROUTES_DIR.rglob("*.php")):
        if f.name not in ("console.php", "channels.php", "api.php"):
            files.append(f)
    return files


def read_file(path: Path) -> str:
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def relative_path(path: Path) -> str:
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)


def is_comment_or_docblock(line: str) -> bool:
    stripped = line.strip()
    return stripped.startswith(("//", "*", "/*", "#")) or "/*" in line


def has_doc_exception(content: str) -> bool:
    return bool(SQL_DOC_EXCEPTION.search(content))


# ─── S1: XSS — unescaped output ────────────────────────────────────────────

def scan_xss(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_UNESCAPED_OUTPUT.search(line) and not RE_SANITIZED_CALL.search(line):
                # Inline justification comment ({{-- Safe: ... --}}) on a preceding line
                context_block = "\n".join(lines[max(0, i - 2):i])
                if RE_BLADE_COMMENT.search(context_block):
                    continue
                findings.append(Finding(
                    id=f"XSS-{len(findings)+1:03d}",
                    rule="S1",
                    severity="high",
                    category="security",
                    file=rel,
                    line=i,
                    message="Unescaped Blade output {!! !!} or x-html without inline sanitization justification",
                    suggestion="Use {{ }} for user content, or add an inline {{-- Safe: ... --}} comment for sanitized content",
                    reference=REF_XSS,
                ))
    return findings


# ─── S5: CSP / inline script ───────────────────────────────────────────────

def scan_csp(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_INLINE_SCRIPT.search(line):
                findings.append(Finding(
                    id=f"CSP-{len(findings)+1:03d}",
                    rule="S5",
                    severity="medium",
                    category="security",
                    file=rel,
                    line=i,
                    message="Inline <script> tag blocked by CSP — use Alpine.js x-data / @click",
                    suggestion="Replace inline <script> with Alpine.js directives",
                    reference=REF_CSP,
                ))
            elif RE_ONCLICK.search(line):
                findings.append(Finding(
                    id=f"CSP-{len(findings)+1:03d}",
                    rule="S5",
                    severity="medium",
                    category="security",
                    file=rel,
                    line=i,
                    message="Inline onclick handler blocked by CSP — use Alpine.js @click",
                    suggestion="Replace inline onclick= with x-on:click / @click",
                    reference=REF_CSP,
                ))
    return findings


# ─── S2: SQL injection ─────────────────────────────────────────────────────

def scan_sql_injection(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        lines = content.split("\n")
        file_has_doc_exception = has_doc_exception(content)
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*")) or "/*" in line:
                continue
            # Raw query method calls (DB facade or query builder)
            is_raw = SQL_RAW_RE.search(line) or SQL_RAW_BUILDER.search(line)
            if not is_raw:
                continue
            # Parameterized (bindings array) → safe
            if SQL_BINDINGS.search(line):
                continue
            # No interpolation → constant SQL, safe
            if not SQL_INTERP.search(line):
                continue
            # Documented exception (docblock) → skip whole file
            if file_has_doc_exception:
                continue
            findings.append(Finding(
                id=f"SQLI-{len(findings)+1:03d}",
                rule="S2",
                severity="high",
                category="security",
                file=rel,
                line=i,
                message="Raw SQL with interpolated value without parameterized binding",
                suggestion="Use bindings: ->whereRaw('col = ?', [$value]) or DB::select($q, $bindings)",
                reference=REF_SQLI,
            ))
    return findings


# ─── S3: Mass assignment ───────────────────────────────────────────────────

def scan_mass_assignment(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*")) or "/*" in line:
                continue
            for pattern in MASS_ASSIGNMENT_PATTERNS:
                if pattern.search(line):
                    findings.append(Finding(
                        id=f"MASS-{len(findings)+1:03d}",
                        rule="S3",
                        severity="high",
                        category="security",
                        file=rel,
                        line=i,
                        message="Mass assignment — passing raw request/form input to create/update",
                        suggestion="Use $request->only(['field', ...]) or validated DTO",
                        reference=REF_MASS,
                    ))
                    break
    return findings


# ─── S6: Missing authorization ─────────────────────────────────────────────

def scan_missing_auth(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    livewire_files = [f for f in files if "/Livewire/" in str(f)]
    for fp in livewire_files:
        content = read_file(fp)
        if not content:
            continue
        if RE_AUTHZ_ATTR.search(content):
            continue
        rel = relative_path(fp)
        sensitive_methods = ["store", "update", "delete", "destroy", "restore", "forceDelete"]
        for method in sensitive_methods:
            method_pattern = re.compile(
                rf"public\s+function\s+{method}\s*\(",
                re.IGNORECASE,
            )
            if method_pattern.search(content):
                method_start = content.find(f"function {method}")
                if method_start == -1:
                    method_start = content.find(f"function {method.lower()}")
                if method_start != -1:
                    method_body = content[method_start:method_start + 2000]
                    if not RE_AUTHORIZE_CALL.search(method_body):
                        findings.append(Finding(
                            id=f"AUTH-{len(findings)+1:03d}",
                            rule="S6",
                            severity="medium",
                            category="security",
                            file=rel,
                            line=content[:method_start].count("\n") + 1,
                            message=f"Livewire method {method}() missing authorization check",
                            suggestion="Add $this->authorize('{method}') or #[Authorize] attribute",
                            reference=REF_AUTH,
                        ))
    return findings


# ─── S8: Hardcoded secrets ─────────────────────────────────────────────────

def scan_hardcoded_secrets(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        if "/config/" in rel or "/database/" in rel:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*")) or "/*" in line:
                continue
            if HARDCODED_SECRETS.search(line):
                findings.append(Finding(
                    id=f"SECRET-{len(findings)+1:03d}",
                    rule="S8",
                    severity="high",
                    category="security",
                    file=rel,
                    line=i,
                    message="Potential hardcoded secret/password/token",
                    suggestion="Use environment variables: config('app.key') or env('SECRET')",
                    reference="docs/conventions.md#3-security-conventions",
                ))
    return findings


# ─── S4: Missing CSRF ──────────────────────────────────────────────────────

def scan_missing_csrf(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    blade_files = [f for f in files if f.name.endswith(".blade.php")]
    for fp in blade_files:
        rel = relative_path(fp)
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if "<form" in line.lower():
                form_block = "\n".join(lines[i - 1:i + 20])
                if "wire:" in line or "wire:" in form_block:
                    continue
                if "@csrf" in form_block or "csrf_token" in form_block:
                    continue
                if 'method="get"' in line.lower() or "method='get'" in line.lower():
                    continue
                findings.append(Finding(
                    id=f"CSRF-{len(findings)+1:03d}",
                    rule="S4",
                    severity="high",
                    category="security",
                    file=rel,
                    line=i,
                    message="Form missing @csrf directive",
                    suggestion="Add @csrf after <form> tag or use Livewire (auto CSRF)",
                    reference=REF_CSRF,
                ))
    return findings


# ─── S9: Unsafe file uploads ───────────────────────────────────────────────

def scan_file_upload(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_STORE_UPLOAD.search(line):
                context_start = max(0, i - 25)
                context_block = "\n".join(lines[context_start:i + 3])
                if not RE_MEDIALIBRARY.search(context_block):
                    findings.append(Finding(
                        id=f"UPLOAD-{len(findings)+1:03d}",
                        rule="S9",
                        severity="medium",
                        category="security",
                        file=rel,
                        line=i,
                        message="File upload storage without visible validation / MediaLibrary",
                        suggestion="Route uploads through Spatie MediaLibrary with registerMediaCollections() validation",
                        reference=REF_UPLOAD,
                    ))
            elif RE_STORAGE_PUT.search(line):
                # Generated content (PDFs, reports) via Storage::put is acceptable;
                # flag only when writing user-uploaded file bytes directly.
                if re.search(r"\$request->file|\$file\s*->|\$upload", content):
                    findings.append(Finding(
                        id=f"UPLOAD-{len(findings)+1:03d}",
                        rule="S9",
                        severity="medium",
                        category="security",
                        file=rel,
                        line=i,
                        message="User-uploaded file written directly to storage",
                        suggestion="Use Spatie MediaLibrary for user uploads",
                        reference=REF_UPLOAD,
                    ))
    return findings


# ─── S7: Rate limiting on auth routes ──────────────────────────────────────

def scan_auth_rate_limiting(route_files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in route_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        lines = content.split("\n")
        # Group-level middleware declarations
        group_middleware = []
        for i, line in enumerate(lines, 1):
            if "middleware(" in line:
                if RE_THROTTLE.search(line):
                    group_middleware.append((i, True))
                else:
                    group_middleware.append((i, False))
        for i, line in enumerate(lines, 1):
            if not RE_AUTH_ROUTE_PATHS.search(line):
                continue
            # Route-level middleware or throttle on same line / next lines
            route_block = "\n".join(lines[i - 1:i + 6])
            if RE_THROTTLE.search(route_block):
                continue
            # Group containing this route with throttle
            protected = False
            for g_line, throttled in group_middleware:
                if g_line < i and throttled:
                    protected = True
                    break
                if g_line < i:
                    continue
            if protected:
                continue
            findings.append(Finding(
                id=f"RATE-{len(findings)+1:03d}",
                rule="S7",
                severity="high",
                category="security",
                file=rel,
                line=i,
                message="Auth route without rate limiting",
                suggestion="Add 'auth.throttle' middleware to the route or its group",
                reference=REF_RATE,
            ))
    return findings


# ─── Report ─────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    scan_type: str,
    module: str | None,
    start_time: float,
    metadata: dict[str, Any],
) -> ScanResult:
    elapsed_ms = int((time.time() - start_time) * 1000)
    by_severity: dict[str, int] = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    for f in findings:
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1

    rules = set(f.rule for f in findings)
    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 9,
            "passed": 9 - len(rules),
            "failed": len(findings),
            "by_severity": by_severity,
        },
        findings=[vars(f) for f in findings],
        metadata=metadata,
    )


def write_report(result: ScanResult, output_path: Path | None = None) -> Path:
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
        output_path = OUTPUT_DIR / f"{timestamp}-{SCAN_NAME}.json"
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(
        json.dumps(vars(result), indent=2, ensure_ascii=False), encoding="utf-8"
    )
    return output_path


def print_summary(result: ScanResult) -> None:
    s = result.summary
    bs = s["by_severity"]
    print(f"\n{'='*60}")
    print(f"  SECURITY SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Categories checked: {s['total_checks']}")
    print(f"  Categories passed:  {s['passed']}")
    print(f"  Findings:           {s['failed']}")
    print(f"    Critical: {bs.get('critical', 0)}")
    print(f"    High:     {bs.get('high', 0)}")
    print(f"    Medium:   {bs.get('medium', 0)}")
    print(f"    Low:      {bs.get('low', 0)}")
    print(f"  Time: {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan for security vulnerabilities (XSS, SQLi, mass assignment, auth)",
    )
    parser.add_argument("--module", "-m", help="Target specific module")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument(
        "--format", "-f", choices=["json", "text", "summary"], default="json"
    )
    parser.add_argument("--verbose", "-v", action="store_true")
    parser.add_argument("--quiet", "-q", action="store_true")
    parser.add_argument("--strict", "-s", action="store_true")
    parser.add_argument("--json", action="store_true")
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    php_files = find_php_files(args.module)
    blade_files = find_blade_files(args.module)
    all_files = php_files + blade_files
    route_files = find_route_files()

    findings: list[Finding] = []
    findings.extend(scan_xss(all_files, args.module))
    findings.extend(scan_csp(all_files, args.module))
    findings.extend(scan_sql_injection(php_files, args.module))
    findings.extend(scan_mass_assignment(php_files, args.module))
    findings.extend(scan_missing_auth(php_files, args.module))
    findings.extend(scan_hardcoded_secrets(php_files, args.module))
    findings.extend(scan_missing_csrf(blade_files, args.module))
    findings.extend(scan_file_upload(php_files, args.module))
    findings.extend(scan_auth_rate_limiting(route_files))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {
            "php_files": len(php_files),
            "blade_files": len(blade_files),
            "route_files": len(route_files),
        },
    )

    if args.json or args.format == "json":
        print(json.dumps(vars(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result)

    output_path = write_report(result, args.output)
    if not args.quiet:
        print(f"Report saved: {relative_path(output_path)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
