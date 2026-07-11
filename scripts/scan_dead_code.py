#!/usr/bin/env python3
"""Detect dead code — unregistered observers, unused DTOs, events without listeners."""

from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"


def find_observers() -> list[str]:
    return [f.stem.replace("Observer", "") for f in APP_DIR.rglob("*Observer.php")]


def is_registered(observer_name: str) -> bool:
    for f in (APP_DIR / "Providers").glob("*.php"):
        try:
            if observer_name in f.read_text(encoding="utf-8", errors="replace"):
                return True
        except OSError:
            continue
    bootstrap = ROOT / "bootstrap" / "providers.php"
    if bootstrap.exists():
        try:
            return observer_name in bootstrap.read_text(encoding="utf-8", errors="replace")
        except OSError:
            pass
    return False


def find_events() -> list[str]:
    events = []
    for f in APP_DIR.rglob("*Event.php"):
        name = f.stem
        if not name.startswith("Base") and name != "Event":
            events.append(name)
    return events


def find_listeners_content() -> str:
    parts = []
    for f in APP_DIR.rglob("*Listener.php"):
        try:
            parts.append(f.read_text(encoding="utf-8", errors="replace"))
        except OSError:
            continue
    return "\n".join(parts)


def find_dtos() -> list[str]:
    return [f.stem for f in APP_DIR.rglob("*Data.php") if f.stem != "BaseData"]


def is_dto_used(dto_name: str) -> bool:
    for f in APP_DIR.rglob("Actions/*.php"):
        try:
            if dto_name in f.read_text(encoding="utf-8", errors="replace"):
                return True
        except OSError:
            continue
    return False


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print("Scanning dead code...")

    observers = find_observers()
    unregistered = [o for o in observers if not is_registered(o)]
    print(f"  Unregistered observers: {len(unregistered)}")

    events = find_events()
    listeners_text = find_listeners_content()
    orphan_events = [e for e in events if e not in listeners_text]
    print(f"  Events without listeners: {len(orphan_events)}")

    dtos = find_dtos()
    unused = [d for d in dtos if not is_dto_used(d)]
    print(f"  Unused DTOs: {len(unused)}")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "unregistered_observers": unregistered,
        "events_without_listeners": orphan_events,
        "unused_dtos": unused,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-dead-code.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
