#!/usr/bin/env python3
"""Audit ope-pg-* scaffolding: PHP scopes vs CSS brutalism vs GBE overrides."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
css = (ROOT / "docs/themes/ope.css").read_text(encoding="utf-8")
tail = css[int(len(css) * 0.55) :]

scopes_in_css = set(re.findall(r"body\.(ope-pg-[a-z0-9-]+)", css))
scopes_in_php = set()
php_files = {}
for p in ROOT.glob("*.php"):
    t = p.read_text(encoding="utf-8", errors="ignore")
    # Robust class extraction that removes PHP tags to prevent "ope-pg-ficha<?php" issues
    for m in re.finditer(r'class="([^"]*ope-pg-[^"]*)"', t):
        classes_str = m.group(1)
        classes_clean = re.sub(r'<\?php.*?\?>', ' ', classes_str)
        for c in classes_clean.split():
            if c.startswith("ope-pg-"):
                scopes_in_php.add(c)
                php_files.setdefault(c, []).append(p.name)

def has_ope_tail(scope: str) -> bool:
    idx = tail.find(f"body.{scope}")
    if idx < 0:
        if f"body.{scope}," in tail or f"body.{scope} " in tail:
            return "--ope-" in tail[tail.find(f"body.{scope}") : tail.find(f"body.{scope}") + 2000]
        return False
    return "--ope-" in tail[idx : idx + 2000]

def brutal_count(scope: str) -> int:
    return len(re.findall(rf"body\.{re.escape(scope)}[^\n]*#000", css))

print("=== PHP scopes missing from ope.css ===")
for s in sorted(scopes_in_php - scopes_in_css):
    print(f"  {s}  ({', '.join(php_files.get(s, []))})")

print("\n=== Scaffolding audit (PHP pages) ===")
print(f"{'SCOPE':<32} {'GBE tail':<9} {'brutal':<8} {'status':<8} PHP files")
for s in sorted(scopes_in_php):
    gbe = has_ope_tail(s)
    brutal = brutal_count(s)
    files = ", ".join(php_files.get(s, [])[:3])
    if len(php_files.get(s, [])) > 3:
        files += "…"
    status = "OK" if gbe and brutal < 5 else ("PARTIAL" if gbe else "OP")
    print(f"{s:<32} {str(gbe):<9} {brutal:<8} {status:<8} {files}")
