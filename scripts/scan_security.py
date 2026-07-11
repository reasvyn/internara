#!/usr/bin/env python3
"""
scan_security.py — Security Pattern Detection
Scans PHP/Blade for XSS, SQL injection, mass assignment, auth gaps, secrets.
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
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "security"
SCAN_VERSION = "1.0.0"

# Dangerous patterns
HARDCODED_SECRETS = re.compile(
    r"""(?:password|secret|token|api_key|apikey|api[-_]?secret)\s*=\s*['"][^'"]{8,}['"]""",
    re.IGNORECASE,
)

SQL_INJECTION_PATTERNS = [
    re.compile(r"DB::select\s*\(\s*['\"]"),
    re.compile(r"DB::statement\s*\(\s*['\"]"),
    re.compile(r"DB::insert\s*\(\s*['\"]"),
    re.compile(r"DB::update\s*\(\s*['\"]"),
    re.compile(r"DB::delete\s*\(\s*['\"]"),
    re.compile(r"->where\s*\(\s*['\"].*\.\s*\$"),
    re.compile(r"->select\s*\(\s*['\"].*\.\s*\$"),
]

MASS_ASSIGNMENT = [
    re.compile(r"Model::create\s*\(\s*\$request\s*->\s*all\s*\(\s*\)"),
    re.compile(r"::create\s*\(\s*\$request\s*->\s*all\s*\(\s*\)"),
    re.compile(r"->update\s*\(\s*\$request\s*->\s*all\s*\(\s*\)"),
    re.compile(r"Model::create\s*\(\s*\$request\s*->\s*input\s*\("),
]

UNPROTECTED_ENDPOINTS = re.compile(
    r"(?:Route::(?:get|post|put|patch|delete|any|match))\s*\("
)

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
    scan_version: str
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


# ─── XSS: Unescaped output ────────────────────────────────────────────────

RE_UNESCAPED_OUTPUT = re.compile(r"\{!!\s*\$(\w+)")


def scan_xss(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        # Skip vendor published views
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_UNESCAPED_OUTPUT.search(line):
                # Skip known-safe patterns
                if "e(" in line or "strip_tags(" in line or "purify(" in line:
                    continue
                if "!! __" in line or "!! e(" in line:
                    continue
                findings.append(Finding(
                    id=f"XSS-{len(findings)+1:03d}",
                    rule="S1",
                    severity="high",
                    category="security",
                    file=rel,
                    line=i,
                    message="Unescaped Blade output {!! !!} — potential XSS",
                    suggestion="Use {{ }} for user content, or {!! e($var) !!} to escape",
                    reference="docs/conventions.md#blade-templates",
                ))
    return findings


# ─── SQL Injection ─────────────────────────────────────────────────────────

def scan_sql_injection(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            for pattern in SQL_INJECTION_PATTERNS:
                if pattern.search(line):
                    findings.append(Finding(
                        id=f"SQLI-{len(findings)+1:03d}",
                        rule="S2",
                        severity="critical",
                        category="security",
                        file=relative_path(fp),
                        line=i,
                        message="Potential SQL injection — raw query construction",
                        suggestion="Use parameterized queries with DB::select($query, $bindings)",
                        reference="docs/conventions.md#sql-injection-prevention",
                    ))
                    break
    return findings


# ─── Mass Assignment ───────────────────────────────────────────────────────

def scan_mass_assignment(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            for pattern in MASS_ASSIGNMENT:
                if pattern.search(line):
                    findings.append(Finding(
                        id=f"MASS-{len(findings)+1:03d}",
                        rule="S3",
                        severity="critical",
                        category="security",
                        file=relative_path(fp),
                        line=i,
                        message="Mass assignment — passing raw request input to create/update",
                        suggestion="Use $request->only(['field1', 'field2']) or validated DTO",
                        reference="docs/conventions.md#input-sanitization",
                    ))
                    break
    return findings


# ─── Missing Authorization ─────────────────────────────────────────────────

RE_AUTHORIZE_CALL = re.compile(r"\$this->authorize\s*\(")
RE_CAN_DIRECTIVE = re.compile(r"@can\b")
RE_POLICY_AUTHORIZE = re.compile(r"->policy\(\)\s*->authorize\s*\(")


def scan_missing_auth(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    # Check Livewire components for missing authorization
    livewire_files = [f for f in files if "/Livewire/" in str(f)]
    for fp in livewire_files:
        content = read_file(fp)
        if not content:
            continue
        # Skip if component has #[Authorize] attribute
        if "#[Authorize" in content:
            continue
        # Check for sensitive methods without authorization
        sensitive_methods = ["store", "update", "delete", "destroy", "restore", "forceDelete"]
        for method in sensitive_methods:
            method_pattern = re.compile(
                rf"public\s+function\s+{method}\s*\(",
                re.IGNORECASE,
            )
            if method_pattern.search(content):
                # Check if method body contains $this->authorize
                method_start = content.find(f"function {method}")
                if method_start == -1:
                    method_start = content.find(f"function {method.lower()}")
                if method_start != -1:
                    method_body = content[method_start:method_start + 2000]
                    if not RE_AUTHORIZE_CALL.search(method_body):
                        findings.append(Finding(
                            id=f"AUTH-{len(findings)+1:03d}",
                            rule="S6",
                            severity="high",
                            category="security",
                            file=relative_path(fp),
                            line=content[:method_start].count("\n") + 1,
                            message=f"Livewire method {method}() missing authorization check",
                            suggestion="Add $this->authorize('{method}') or #[Authorize] attribute",
                            reference="docs/conventions.md#authentication-authorization",
                        ))
    return findings


# ─── Hardcoded Secrets ─────────────────────────────────────────────────────

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
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if HARDCODED_SECRETS.search(line):
                findings.append(Finding(
                    id=f"SECRET-{len(findings)+1:03d}",
                    rule="S8",
                    severity="critical",
                    category="security",
                    file=rel,
                    line=i,
                    message="Potential hardcoded secret/password/token",
                    suggestion="Use environment variables: config('app.key') or env('SECRET')",
                    reference="docs/conventions.md#security-best-practices",
                ))
    return findings


# ─── Missing CSRF ───────────────────────────────────────────────────────────

RE_CSRF_MISSING = re.compile(
    r"<form\s[^>]*(?!@csrf)(?!csrf_token)[^>]*>",
    re.IGNORECASE,
)


def scan_missing_csrf(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    blade_files = [f for f in files if f.name.endswith(".blade.php")]
    for fp in blade_files:
        rel = relative_path(fp)
        # Skip vendor published views
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if "<form" in line.lower():
                # Livewire forms handle CSRF automatically via layout
                if "wire:" in line:
                    continue
                # Look ahead for @csrf in next 20 lines
                form_block = "\n".join(lines[i - 1:i + 20])
                if "@csrf" not in form_block and "csrf_token" not in form_block:
                    # Skip forms with method=get (no CSRF needed)
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
                        suggestion="Add @csrf after <form> tag",
                        reference="docs/conventions.md#csrf-protection",
                    ))
    return findings


# ─── Unsafe File Uploads ───────────────────────────────────────────────────

def scan_file_upload(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            # Check for store() without validation
            if "->store(" in line or "->storeAs(" in line:
                # Look for preceding validation
                context_start = max(0, i - 20)
                context_block = "\n".join(lines[context_start:i])
                if "validate(" not in context_block and "Rule::file" not in context_block:
                    findings.append(Finding(
                        id=f"UPLOAD-{len(findings)+1:03d}",
                        rule="S9",
                        severity="high",
                        category="security",
                        file=relative_path(fp),
                        line=i,
                        message="File upload without visible validation",
                        suggestion="Validate file type, size, and scan content before storage",
                        reference="docs/conventions.md#file-uploads",
                    ))
    return findings


# ─── Missing Rate Limiting on Auth ─────────────────────────────────────────

RE_AUTH_ROUTES = re.compile(
    r"Route::(?:post|get)\s*\(\s*['\"]/(?:login|register|password|reset|forgot)",
    re.IGNORECASE,
)


def scan_auth_rate_limiting(routes_file: Path) -> list[Finding]:
    findings: list[Finding] = []
    if not routes_file.exists():
        return findings

    content = read_file(routes_file)
    if not content:
        return findings

    lines = content.split("\n")
    for i, line in enumerate(lines, 1):
        if RE_AUTH_ROUTES.search(line):
            # Look ahead for throttle middleware
            context_block = "\n".join(lines[i - 1:i + 5])
            if "throttle" not in context_block and "RateLimiter" not in context_block:
                findings.append(Finding(
                    id=f"RATE-{len(findings)+1:03d}",
                    rule="S7",
                    severity="high",
                    category="security",
                    file=relative_path(routes_file),
                    line=i,
                    message="Auth route without rate limiting",
                    suggestion="Add throttle:login middleware or use RateLimiter facade",
                    reference="docs/conventions.md#rate-limiting",
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

    return ScanResult(
        scan_version=SCAN_VERSION,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 7,
            "passed": 7 - len(set(f.rule for f in findings)),
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

    findings: list[Finding] = []
    findings.extend(scan_xss(blade_files, args.module))
    findings.extend(scan_sql_injection(php_files, args.module))
    findings.extend(scan_mass_assignment(php_files, args.module))
    findings.extend(scan_missing_auth(php_files, args.module))
    findings.extend(scan_hardcoded_secrets(php_files, args.module))
    findings.extend(scan_missing_csrf(blade_files, args.module))
    findings.extend(scan_file_upload(php_files, args.module))

    # Check auth rate limiting
    routes_web = ROOT / "routes" / "web.php"
    if routes_web.exists():
        findings.extend(scan_auth_rate_limiting(routes_web))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {
            "php_files": len(php_files),
            "blade_files": len(blade_files),
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
