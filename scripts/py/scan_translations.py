#!/usr/bin/env python3
"""
scan_translations.py — Comprehensive Translation Key & Parity Scanner (v1.0)

Enforces localization integrity per:
  docs/conventions.md §15 (i18n & dual localization en + id)
  docs/guides/arch/ux-pattern.md §4 (localization rules)

Checks:
  1. Language Parity: Keys present in en/*.php must exist in id/*.php and vice-versa.
  2. JSON Parity: Keys present in en.json must exist in id.json and vice-versa.
  3. Codebase Coverage: Every translation key referenced via __(), trans(), @lang(),
     Lang::get(), or trans_choice() in app/, resources/, routes/, config/ must resolve
     in both en and id translation catalogues.
  4. Dynamic Prefix Resolution: Concatenated keys like __('prefix.' . $var) are validated
     against registered prefix namespaces.
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
import time
from pathlib import Path
from typing import Any

sys.path.insert(0, str(Path(__file__).parent))
from _common import (  # noqa: E402
    ROOT,
    Finding,
    ScanResult,
    build_report,
    parse_args_with_common,
    print_summary,
    relative_path,
    write_report,
)
try:
    from _output import handle_output
except ImportError:
    handle_output = None

SCAN_NAME = "translations"

# Regex for translation calls in PHP and Blade files
# Handles: __('key'), trans('key'), @lang('key'), Lang::get('key'), trans_choice('key', ...)
RE_TRANSLATION_CALL = re.compile(
    r"""(?:__|trans|trans_choice|@lang|Lang::get)\(\s*([\'\"])([^\'\"]+)\1""",
    re.MULTILINE,
)

# Regex for dynamic prefix concatenation: __('prefix.' . $var) or trans("prefix." . $var)
RE_PREFIX_CALL = re.compile(
    r"""(?:__|trans|trans_choice|@lang|Lang::get)\(\s*([\'\"])([a-zA-Z0-9_\-]+\.(?:[a-zA-Z0-9_\-]+\.)*)\1\s*\.""",
    re.MULTILINE,
)

# Exclude directories that do not contain application code
EXCLUDE_DIRS = {
    ROOT / "vendor",
    ROOT / "node_modules",
    ROOT / ".git",
    ROOT / "storage",
    ROOT / "bootstrap" / "cache",
    ROOT / "resources" / "views" / "vendor",
}


def load_all_translations() -> tuple[dict[str, Any], dict[str, Any], dict[str, Any], dict[str, Any]]:
    """
    Load all PHP and JSON translations for en and id using PHP CLI to ensure 100% accurate parsing.
    Returns: (en_php, id_php, en_json, id_json)
    """
    php_loader = """
$locales = ['en', 'id'];
$translations = ['en' => [], 'id' => []];
foreach ($locales as $locale) {
    $dir = __DIR__ . "/lang/{$locale}";
    if (is_dir($dir)) {
        foreach (glob("{$dir}/*.php") as $file) {
            $name = basename($file, '.php');
            try {
                $data = include $file;
            } catch (Throwable $e) {
                $data = [];
            }
            if (is_array($data)) {
                $flatten = function ($arr, $prefix = '') use (&$flatten) {
                    $res = [];
                    foreach ($arr as $k => $v) {
                        $key = $prefix ? "{$prefix}.{$k}" : $k;
                        if (is_array($v)) {
                            $res = array_merge($res, $flatten($v, $key));
                        } else {
                            $res[$key] = is_string($v) ? $v : '';
                        }
                    }
                    return $res;
                };
                foreach ($flatten($data, $name) as $k => $v) {
                    $translations[$locale][$k] = $v;
                }
            }
        }
    }
}
$enJson = json_decode(@file_get_contents(__DIR__ . '/lang/en.json'), true) ?: [];
$idJson = json_decode(@file_get_contents(__DIR__ . '/lang/id.json'), true) ?: [];
echo json_encode([
    'en_php' => $translations['en'],
    'id_php' => $translations['id'],
    'en_json' => $enJson,
    'id_json' => $idJson,
]);
"""
    try:
        proc = subprocess.run(
            ["php", "-r", php_loader],
            cwd=str(ROOT),
            capture_output=True,
            text=True,
            check=True,
        )
        data = json.loads(proc.stdout)
        return data["en_php"], data["id_php"], data["en_json"], data["id_json"]
    except Exception as e:
        sys.stderr.write(f"Failed to load translations via PHP: {e}\n")
        return {}, {}, {}, {}


def check_language_parity(
    en_php: dict[str, Any],
    id_php: dict[str, Any],
    en_json: dict[str, Any],
    id_json: dict[str, Any],
) -> list[Finding]:
    """Identify keys present in one language but missing in another."""
    findings: list[Finding] = []
    finding_idx = 1

    # Check PHP parity: EN -> ID
    en_keys = set(en_php.keys())
    id_keys = set(id_php.keys())

    missing_in_id = sorted(en_keys - id_keys)
    for k in missing_in_id:
        file_part = k.split(".")[0]
        findings.append(
            Finding(
                id=f"TR-PARITY-{finding_idx:04d}",
                rule="TRANSLATION_PARITY_ID_MISSING",
                severity="medium",
                category="convention",
                file=f"lang/id/{file_part}.php",
                line=1,
                message=f"Translation key '{k}' exists in lang/en/{file_part}.php but is missing in lang/id/{file_part}.php",
                suggestion=f"Add key '{k}' to lang/id/{file_part}.php with appropriate Indonesian translation.",
                reference="docs/conventions.md §15 Dual Localization",
                context={"key": k, "source_locale": "en", "target_locale": "id"},
            )
        )
        finding_idx += 1

    # Check PHP parity: ID -> EN
    missing_in_en = sorted(id_keys - en_keys)
    for k in missing_in_en:
        file_part = k.split(".")[0]
        findings.append(
            Finding(
                id=f"TR-PARITY-{finding_idx:04d}",
                rule="TRANSLATION_PARITY_EN_MISSING",
                severity="medium",
                category="convention",
                file=f"lang/en/{file_part}.php",
                line=1,
                message=f"Translation key '{k}' exists in lang/id/{file_part}.php but is missing in lang/en/{file_part}.php",
                suggestion=f"Add key '{k}' to lang/en/{file_part}.php with appropriate English translation.",
                reference="docs/conventions.md §15 Dual Localization",
                context={"key": k, "source_locale": "id", "target_locale": "en"},
            )
        )
        finding_idx += 1

    # Check JSON parity: EN -> ID
    en_json_keys = set(en_json.keys())
    id_json_keys = set(id_json.keys())

    for k in sorted(en_json_keys - id_json_keys):
        findings.append(
            Finding(
                id=f"TR-PARITY-{finding_idx:04d}",
                rule="TRANSLATION_JSON_ID_MISSING",
                severity="low",
                category="convention",
                file="lang/id.json",
                line=1,
                message=f"JSON string '{k[:60]}' exists in lang/en.json but is missing in lang/id.json",
                suggestion=f"Add translation for '{k[:60]}' in lang/id.json.",
                reference="docs/conventions.md §15 Dual Localization",
                context={"key": k},
            )
        )
        finding_idx += 1

    for k in sorted(id_json_keys - en_json_keys):
        findings.append(
            Finding(
                id=f"TR-PARITY-{finding_idx:04d}",
                rule="TRANSLATION_JSON_EN_MISSING",
                severity="low",
                category="convention",
                file="lang/en.json",
                line=1,
                message=f"JSON string '{k[:60]}' exists in lang/id.json but is missing in lang/en.json",
                suggestion=f"Add translation for '{k[:60]}' in lang/en.json.",
                reference="docs/conventions.md §15 Dual Localization",
                context={"key": k},
            )
        )
        finding_idx += 1

    return findings


def should_scan_file(path: Path) -> bool:
    """Filter out non-source files and excluded directories."""
    for exc in EXCLUDE_DIRS:
        try:
            path.relative_to(exc)
            return False
        except ValueError:
            pass

    if path.suffix not in {".php", ".blade.php"}:
        return False

    rel = relative_path(path)
    # Ignore translation files themselves and tests
    if rel.startswith("lang/") or rel.startswith("tests/"):
        return False

    return True


def scan_codebase_usages(
    en_keys: set[str],
    id_keys: set[str],
    all_json_keys: set[str],
    module_filter: str | None = None,
) -> list[Finding]:
    """Scan all application files for translation key calls and verify existence."""
    findings: list[Finding] = []
    finding_idx = 1000

    # Build known prefixes for dynamic calls (e.g. 'assignment.types.')
    en_prefixes = {k.rsplit(".", 1)[0] + "." for k in en_keys if "." in k}
    id_prefixes = {k.rsplit(".", 1)[0] + "." for k in id_keys if "." in k}

    scan_roots = [
        ROOT / "app",
        ROOT / "resources" / "views",
        ROOT / "routes",
        ROOT / "config",
    ]

    for s_root in scan_roots:
        if not s_root.exists():
            continue
        for p in s_root.rglob("*"):
            if not p.is_file() or not should_scan_file(p):
                continue

            rel = relative_path(p)
            if module_filter and f"Modules/{module_filter}" not in rel and f"views/{module_filter.lower()}" not in rel:
                continue

            try:
                content = p.read_text(encoding="utf-8")
            except Exception:
                continue

            lines = content.splitlines()

            # 1. Check string translation calls
            for match in RE_TRANSLATION_CALL.finditer(content):
                raw_key = match.group(2).strip()

                # If followed immediately by concatenation dot '.' (e.g. __('prefix.' . $var)), treat as dynamic prefix
                trailing = content[match.end():match.end() + 10].lstrip()
                is_concatenated = trailing.startswith(".")

                # If string contains interpolation syntax {$var} or $var or is concatenated
                if "{" in raw_key or "$" in raw_key or is_concatenated:
                    prefix = re.split(r"[\{\$]", raw_key)[0] if ("{" in raw_key or "$" in raw_key) else raw_key
                    if prefix and ("." in prefix or prefix.endswith("_")):
                        has_en = any(k.startswith(prefix) for k in en_keys)
                        has_id = any(k.startswith(prefix) for k in id_keys)
                        if not has_en or not has_id:
                            start_pos = match.start()
                            line_no = content[:start_pos].count("\n") + 1
                            file_part = prefix.split(".")[0]
                            findings.append(
                                Finding(
                                    id=f"TR-PREFIX-{finding_idx:04d}",
                                    rule="TRANSLATION_PREFIX_UNRESOLVED",
                                    severity="medium",
                                    category="convention",
                                    file=rel,
                                    line=line_no,
                                    message=f"Dynamic translation prefix '{prefix}' (from '{raw_key}') has no matching keys in lang/en/{file_part}.php or lang/id/{file_part}.php",
                                    suggestion=f"Define keys starting with '{prefix}' in lang/en/{file_part}.php and lang/id/{file_part}.php.",
                                    reference="docs/conventions.md §15 Dual Localization",
                                    context={"prefix": prefix, "raw_key": raw_key},
                                )
                            )
                            finding_idx += 1
                    continue

                key = raw_key

                # Ignore pure natural language sentences that belong to JSON or have spaces
                if " " in key:
                    if key not in all_json_keys:
                        # Natural language string not in JSON catalogue
                        start_pos = match.start()
                        line_no = content[:start_pos].count("\n") + 1
                        findings.append(
                            Finding(
                                id=f"TR-USAGE-{finding_idx:04d}",
                                rule="TRANSLATION_NATURAL_STRING_MISSING",
                                severity="low",
                                category="convention",
                                file=rel,
                                line=line_no,
                                message=f"Natural language string '{key[:60]}' called via __() is not cataloged in lang/*.json",
                                suggestion=f"Add '{key[:60]}' entry to lang/en.json and lang/id.json or use dot-notation key.",
                                reference="docs/conventions.md §15",
                                context={"key": key},
                            )
                        )
                        finding_idx += 1
                    continue

                # If key looks like a dot-notation key (e.g. 'common.actions.save' or 'auth.failed')
                if "." in key:
                    # Trailing dot means prefix call handled below
                    if key.endswith("."):
                        continue

                    # Check if key is missing in EN or ID
                    missing_locales = []
                    if key not in en_keys:
                        missing_locales.append("en")
                    if key not in id_keys:
                        missing_locales.append("id")

                    if missing_locales:
                        start_pos = match.start()
                        line_no = content[:start_pos].count("\n") + 1
                        file_part = key.split(".")[0]
                        loc_str = " and ".join(missing_locales)
                        findings.append(
                            Finding(
                                id=f"TR-USAGE-{finding_idx:04d}",
                                rule="TRANSLATION_KEY_MISSING",
                                severity="high",
                                category="convention",
                                file=rel,
                                line=line_no,
                                message=f"Translation key '{key}' called in code is missing from lang/{loc_str}/{file_part}.php",
                                suggestion=f"Define '{key}' in lang/en/{file_part}.php and lang/id/{file_part}.php.",
                                reference="docs/conventions.md §15 Dual Localization",
                                context={"key": key, "missing_locales": missing_locales},
                            )
                        )
                        finding_idx += 1

            # 2. Check dynamic prefix calls: __('prefix.' . $var)
            for match in RE_PREFIX_CALL.finditer(content):
                prefix = match.group(2)
                # Ensure at least one translation key exists with this prefix
                has_en = any(k.startswith(prefix) for k in en_keys)
                has_id = any(k.startswith(prefix) for k in id_keys)

                if not has_en or not has_id:
                    start_pos = match.start()
                    line_no = content[:start_pos].count("\n") + 1
                    file_part = prefix.split(".")[0]
                    findings.append(
                        Finding(
                            id=f"TR-PREFIX-{finding_idx:04d}",
                            rule="TRANSLATION_PREFIX_UNRESOLVED",
                            severity="medium",
                            category="convention",
                            file=rel,
                            line=line_no,
                            message=f"Dynamic translation prefix '{prefix}' has no registered keys under lang/en/{file_part}.php or lang/id/{file_part}.php",
                            suggestion=f"Define case keys under prefix '{prefix}' in lang/en/{file_part}.php and lang/id/{file_part}.php.",
                            reference="docs/conventions.md §15 Dual Localization",
                            context={"prefix": prefix},
                        )
                    )
                    finding_idx += 1

    return findings


def main() -> None:
    args = parse_args_with_common(SCAN_NAME)
    start_time = time.time()

    # Load all translations
    en_php, id_php, en_json, id_json = load_all_translations()

    en_keys = set(en_php.keys())
    id_keys = set(id_php.keys())
    all_json_keys = set(en_json.keys()) | set(id_json.keys())

    findings: list[Finding] = []

    # 1. Check language parity
    parity_findings = check_language_parity(en_php, id_php, en_json, id_json)
    findings.extend(parity_findings)

    # 2. Scan usages in code
    usage_findings = scan_codebase_usages(en_keys, id_keys, all_json_keys, args.module)
    findings.extend(usage_findings)

    duration_ms = int((time.time() - start_time) * 1000)

    # Metadata
    metadata = {
        "total_en_php_keys": len(en_keys),
        "total_id_php_keys": len(id_keys),
        "total_en_json_keys": len(en_json),
        "total_id_json_keys": len(id_json),
        "parity_findings": len(parity_findings),
        "usage_findings": len(usage_findings),
    }

    report = build_report(
        findings=findings,
        scan_name=SCAN_NAME,
        scan_type="module" if args.module else "full",
        module=args.module,
        start_time=start_time,
        metadata=metadata,
    )

    if handle_output:
        handle_output(report, args)
    else:
        write_report(SCAN_NAME, report)
        print_summary(report)

    if args.strict and findings:
        sys.exit(1)


if __name__ == "__main__":
    main()
