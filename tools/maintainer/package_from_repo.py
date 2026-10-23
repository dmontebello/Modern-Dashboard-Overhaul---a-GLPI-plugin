#!/usr/bin/env python3
"""Build a complete installable MDO zip directly from the repository checkout."""
from __future__ import annotations
import argparse
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
EXCLUDE_DIRS = {".git", ".github", "__pycache__", ".venv", "node_modules"}
EXCLUDE_FILES = {"SHA512SUMS", "config/ui.local.php"}

def included(relative: Path) -> bool:
    name = relative.as_posix()
    if any(part in EXCLUDE_DIRS for part in relative.parts):
        return False
    if name in EXCLUDE_FILES or name.endswith((".zip", ".zip.sha512", ".pyc")):
        return False
    if name.startswith(".") or name.startswith("dist/") or name.startswith("build/"):
        return False
    return True

def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, help="Output zip path (default: checkout root)")
    options = parser.parse_args()
    source = (ROOT / "setup.php").read_text(encoding="utf-8")
    match = re.search(r"define\('PLUGIN_MDOVERHAUL_VERSION', '(\d+\.\d+\.\d+)'\)", source)
    if not match:
        raise SystemExit("No version constant in setup.php")
    version = match.group(1)
    archive = (options.output or ROOT / f"Modern_Dashboard_Overhaul_{version}_Pilot.zip").resolve()
    if archive.suffix != ".zip":
        raise SystemExit("Output must be a zip archive")
    with tempfile.TemporaryDirectory(prefix="mdo_release_") as temp:
        stage = Path(temp) / "mdoverhaul"
        stage.mkdir()
        for path in ROOT.rglob("*"):
            relative = path.relative_to(ROOT)
            if not included(relative):
                continue
            if path.is_symlink():
                raise SystemExit("Symlink forbidden: " + relative.as_posix())
            if not path.is_file():
                continue
            target = stage / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(path, target)
        subprocess.run([
            sys.executable, "-B", str(stage / "tools/maintainer/package_mdo.py"),
            str(stage), str(archive),
        ], check=True)
        shutil.copyfile(stage / "SHA512SUMS", ROOT / "SHA512SUMS")
    print("[OK] Complete MDO", version, archive)
    print("[OK] Synchronized source SHA512SUMS")

if __name__ == "__main__":
    main()
