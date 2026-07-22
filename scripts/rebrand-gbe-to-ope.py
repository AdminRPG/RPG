#!/usr/bin/env python3
"""
Rebrand mecánico OPE/GBE → OPE (One Piece: Eternal).

Orden:
  1) Sustituciones de contenido en archivos de texto
  2) Renombrado de archivos/carpetas

Uso:
  py scripts/rebrand-ope-to-ope.py --dry-run
  py scripts/rebrand-ope-to-ope.py
"""

from __future__ import annotations

import argparse
import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

SKIP_DIR_NAMES = {
    ".git",
    "vendor",
    "node_modules",
    "graphify-out",
    "cache",
    "uploads",
    "backups",
    "__pycache__",
}

# Extensiones donde aplicamos sustitución de contenido.
TEXT_EXTS = {
    ".php",
    ".css",
    ".xml",
    ".md",
    ".js",
    ".mjs",
    ".ts",
    ".tsx",
    ".json",
    ".txt",
    ".html",
    ".htm",
    ".mdc",
    ".yml",
    ".yaml",
    ".sql",
    ".py",
    ".sh",
    ".ps1",
}

# Reemplazos de contenido: orden importa (más específicos primero).
CONTENT_REPLACEMENTS: list[tuple[str, str]] = [
    # Prefijos de función / archivo / constantes
    ("ope_rol_", "ope_rol_"),
    ("OPE_ROL_", "OPE_ROL_"),
    ("ope_pp_", "ope_pp_"),
    ("OPE_PP_", "OPE_PP_"),
    ("ope_viaje_", "ope_viaje_"),
    ("ope_oraculo_", "ope_oraculo_"),
    ("ope_combat_", "ope_combat_"),
    ("ope_system_", "ope_system_"),
    ("ope_region_", "ope_region_"),
    ("ope_skydom_", "ope_skydom_"),
    ("ope_render_", "ope_render_"),
    ("ope_bootstrap_", "ope_bootstrap_"),
    ("ope_child_", "ope_child_"),
    ("ope_db_", "ope_db_"),
    ("ope_resolve_", "ope_resolve_"),
    ("ope_read_", "ope_read_"),
    ("ope_load_", "ope_load_"),
    ("ope_user_", "ope_user_"),
    ("ope_nav_", "ope_nav_"),
    ("ope_staff_", "ope_staff_"),
    ("ope_active_", "ope_active_"),
    # Constantes / defines restantes
    ("OPE_THEME_ROOT", "OPE_THEME_ROOT"),
    ("OPE_CSS_FILE", "OPE_CSS_FILE"),
    ("OPE_CHILD_XML", "OPE_CHILD_XML"),
    ("OPE_CHILD_BUNDLE_XML", "OPE_CHILD_BUNDLE_XML"),
    ("OPE_TEMPLATE_XML_FILES", "OPE_TEMPLATE_XML_FILES"),
    ("OPE_", "OPE_"),
    # Catch-all de funciones ope_* restantes (después de los específicos)
    ("ope_", "ope_"),
    # Clases / scopes CSS / plantillas
    ("ope-pg-", "ope-pg-"),
    ("ope-index", "ope-index"),
    ("ope-panel", "ope-panel"),
    ("ope-section", "ope-section"),
    ("ope-top", "ope-top"),
    ("ope-child-theme", "ope-child-theme"),
    ("ope-shared", "ope-shared"),
    ("ope-forms", "ope-forms"),
    ("ope-showthread", "ope-showthread"),
    ("ope-forumdisplay", "ope-forumdisplay"),
    ("ope.css", "ope.css"),
    ("ope-", "ope-"),
    # Copy de marca (contenido)
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("One Piece: Eternal Rol", "One Piece: Eternal Rol"),
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("One Piece: Eternal", "One Piece: Eternal"),
    ("OPE Eternal", "OPE Eternal"),
    ("OPE", "OPE"),
]

# Renombres de archivo/carpeta (relativos a ROOT). Orden: archivos primero, dirs después.
FILE_RENAMES: list[tuple[str, str]] = [
    ("inc/plugins/ope_rol.php", "inc/plugins/ope_rol.php"),
    ("inc/ope_rol_data.php", "inc/ope_rol_data.php"),
    ("inc/ope_rol_catalogos.php", "inc/ope_rol_catalogos.php"),
    ("inc/ope_rol_system.php", "inc/ope_rol_system.php"),
    ("inc/ope_rol_mundo.php", "inc/ope_rol_mundo.php"),
    ("inc/ope_rol_oraculo.php", "inc/ope_rol_oraculo.php"),
    ("inc/ope_rol_oraculo_post.php", "inc/ope_rol_oraculo_post.php"),
    ("inc/ope_rol_viajes.php", "inc/ope_rol_viajes.php"),
    ("inc/ope_rol_rachas.php", "inc/ope_rol_rachas.php"),
    ("inc/ope_rol_enlace.php", "inc/ope_rol_enlace.php"),
    ("inc/ope_rol_renombre.php", "inc/ope_rol_renombre.php"),
    ("inc/ope_rol_pl.php", "inc/ope_rol_pl.php"),
    ("inc/ope_functions.php", "inc/ope_functions.php"),
    ("inc/ope_user_init.php", "inc/ope_user_init.php"),
    ("docs/themes/ope.css", "docs/themes/ope.css"),
    ("docs/themes/ope-shared.xml", "docs/themes/ope-shared.xml"),
    ("docs/themes/ope-forms.xml", "docs/themes/ope-forms.xml"),
    ("docs/themes/ope-showthread.xml", "docs/themes/ope-showthread.xml"),
    ("docs/themes/ope-forumdisplay.xml", "docs/themes/ope-forumdisplay.xml"),
    ("docs/themes/ope-index.xml", "docs/themes/ope-index.xml"),
    ("docs/themes/ope-child-theme.xml", "docs/themes/ope-child-theme.xml"),
    ("docs/DESIGN-GRANBLUE-ETERNAL.md", "docs/DESIGN-ONE-PIECE-ETERNAL.md"),
    ("docs/PLAN-MAESTRO-GRANBLUE-ETERNAL.md", "docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md"),
    (".cursor/rules/visual-port-gbe.mdc", ".cursor/rules/visual-port-ope.mdc"),
]


def should_skip_dir(name: str) -> bool:
    return name in SKIP_DIR_NAMES


def iter_text_files(root: Path):
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if not should_skip_dir(d)]
        for name in filenames:
            path = Path(dirpath) / name
            if path.suffix.lower() in TEXT_EXTS or name in {"AGENTS.md", "PRODUCT.md"}:
                yield path


def apply_content(text: str) -> tuple[str, int]:
    total = 0
    for old, new in CONTENT_REPLACEMENTS:
        if old in text:
            count = text.count(old)
            text = text.replace(old, new)
            total += count
    return text, total


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    content_files = 0
    content_hits = 0
    for path in iter_text_files(ROOT):
        # No reescribir este propio script en dry-run/apply de forma que se auto-rompa
        # antes de terminar: se aplica igual, pero al final.
        try:
            raw = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            try:
                raw = path.read_text(encoding="latin-1")
            except Exception:
                continue
        new, hits = apply_content(raw)
        if hits:
            content_files += 1
            content_hits += hits
            rel = path.relative_to(ROOT)
            print(f"[content] {rel}: {hits} hits")
            if not args.dry_run:
                path.write_text(new, encoding="utf-8", newline="\n")

    renamed = 0
    for src_rel, dst_rel in FILE_RENAMES:
        src = ROOT / src_rel
        dst = ROOT / dst_rel
        if not src.exists():
            print(f"[skip-rename] missing {src_rel}")
            continue
        print(f"[rename] {src_rel} -> {dst_rel}")
        renamed += 1
        if not args.dry_run:
            dst.parent.mkdir(parents=True, exist_ok=True)
            if dst.exists():
                print(f"  WARN: destino ya existe, omitiendo {dst_rel}")
                continue
            src.rename(dst)

    # Carpeta images/gbe → images/ope (si existe)
    img_src = ROOT / "images" / "gbe"
    img_dst = ROOT / "images" / "ope"
    if img_src.exists() and not img_dst.exists():
        print(f"[rename-dir] images/gbe -> images/ope")
        renamed += 1
        if not args.dry_run:
            img_src.rename(img_dst)

    print("---")
    print(f"content files touched: {content_files}")
    print(f"content replacement hits: {content_hits}")
    print(f"renames: {renamed}")
    if args.dry_run:
        print("DRY RUN — no changes written")
    return 0


if __name__ == "__main__":
    sys.exit(main())
