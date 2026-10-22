#!/usr/bin/env python3
"""Refresh SHA512SUMS then build a deterministic MDO source ZIP and SHA512 sidecar."""
import argparse
import hashlib
import os
import stat
import sys
import zipfile
from pathlib import Path
sys.dont_write_bytecode = True
from check_release import check

FIXED_TIMESTAMP = (2026, 1, 1, 0, 0, 0)


def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument('folder', help='Unpacked mdoverhaul source dir')
    ap.add_argument('zip', help='Output ZIP path')
    args = ap.parse_args()
    root = Path(args.folder).resolve()
    target = Path(args.zip).resolve()
    if root.name != 'mdoverhaul' or not root.is_dir():
        sys.exit('Expected source directory named mdoverhaul')
    if target.is_relative_to(root):
        sys.exit('Output ZIP cannot be inside source directory')
    paths = []
    for p in root.rglob('*'):
        rel = p.relative_to(root).as_posix()
        if p.is_symlink():
            sys.exit('Symlink forbidden: ' + rel)
        if p.is_file():
            if rel == 'config/ui.local.php':
                sys.exit('Refusing to package private local override config/ui.local.php')
            if rel != 'SHA512SUMS':
                paths.append((rel, p))
    paths.sort(key=lambda x: x[0])
    manifest = ''.join(f'{hashlib.sha512(p.read_bytes()).hexdigest()}  {rel}\n' for rel, p in paths)
    (root / 'SHA512SUMS').write_text(manifest, encoding='utf-8')
    errors, version = check(root)
    if errors:
        for e in errors:
            print('[ERRORE]', e, file=sys.stderr)
        sys.exit(1)
    target.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(target, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel, p in paths + [('SHA512SUMS', root / 'SHA512SUMS')]:
            entry = zipfile.ZipInfo(f'mdoverhaul/{rel}', FIXED_TIMESTAMP)
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.create_system = 3
            mode = 0o755 if p.name.endswith('.sh') else 0o644
            entry.external_attr = (stat.S_IFREG | mode) << 16
            z.writestr(entry, p.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    zip_errors, _ = check(root, target)
    if zip_errors:
        target.unlink(missing_ok=True)
        sys.exit('\n'.join('[ERRORE] '+e for e in zip_errors))
    digest = hashlib.sha512(target.read_bytes()).hexdigest()
    sha_file = Path(str(target) + '.sha512')
    sha_file.write_text(f'{digest}  {target.name}\n', encoding='ascii')
    print('[OK] MDO', version, 'release package:', target)
    print('[OK] SHA-512:', sha_file)

if __name__ == '__main__':
    main()
