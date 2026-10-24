#!/usr/bin/env python3
"""Build a complete installable MDO zip directly from the repository checkout."""
from __future__ import annotations
import argparse
import re
import shutil
import subprocess
import sys
import tempfile
import hashlib
import json
from pathlib import Path
from versioning import included, metadata, version

ROOT = Path(__file__).resolve().parents[2]

def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, help="Output zip path (default: checkout root)")
    options = parser.parse_args()
    ver = version(ROOT)
    info = metadata(ROOT)
    archive = (options.output or ROOT / f"Modern_Dashboard_Overhaul_{ver}.zip").resolve()
    if archive.suffix != ".zip":
        raise SystemExit("Output must be a zip archive")
    with tempfile.TemporaryDirectory(prefix="mdo_release_") as temp:
        stage = Path(temp) / "mdoverhaul"
        stage.mkdir()
        sources = []
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
            sources.append((relative.as_posix(), path))
        (stage / 'build-info.json').write_text(json.dumps(info, indent=2) + '\n')
        subprocess.run([
            sys.executable, "-B", str(stage / "tools/maintainer/package_mdo.py"),
            str(stage), str(archive),
        ], check=True)
        # The checkout manifest covers sources; the ZIP manifest also hashes generated metadata.
        (ROOT / 'SHA512SUMS').write_text(''.join(f'{hashlib.sha512(path.read_bytes()).hexdigest()}  {name}\n' for name, path in sorted(sources)))
    print("[OK] Complete MDO", ver, archive)
    print("[OK] Synchronized source SHA512SUMS")

if __name__ == "__main__":
    main()
