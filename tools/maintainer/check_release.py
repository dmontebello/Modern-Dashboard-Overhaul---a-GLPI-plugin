#!/usr/bin/env python3
"""Static checks for a Modern Dashboard Overhaul release folder/ZIP.

This does not execute PHP/JavaScript nor claim a GLPI live test. Standard library only.
"""
import argparse
import hashlib
import os
import re
import stat
import sys
import zipfile
from pathlib import Path
from versioning import SEMVER, VERSION_DOCS, documentation_errors

REQUIRED = [
    'README.md', 'CHANGELOG.md', 'UPDATE.md', 'QA.md', 'LICENSE',
    'docs/ARCHITETTURA.md', 'docs/INSTALLAZIONE.md', 'docs/CONFIGURAZIONE.md',
    'docs/MAPPA_CARTELLE.md', 'docs/TEST_MATRIX.md', 'docs/RELEASE_PROCESS.md',
    'docs/SECURITY.md', 'docs/INTEGRATION.md', 'docs/RELEASE_NOTES_IT.md',
    'tools/README.md', 'tools/maintainer/check_release.py', 'tools/maintainer/package_mdo.py',
]
VERSIONS = VERSION_DOCS
REQUIRED_MARKDOWN = [p for p in REQUIRED if p.endswith('.md')]
FORBIDDEN_PATHS = { 'config/ui.local.php' }
PRIVATE_STRINGS = [r'ADCON2019', r'\bhfe\.local\b', r'\baren-ep\.com\b', r'\b192\.168\.8\.',
                   r'\\\\[^/\s]+\\backup_apparati']


def check(root: Path, zip_path=None):
    errors = []
    root = root.resolve()
    if root.name != 'mdoverhaul' or not root.is_dir():
        errors.append('Source must be directory named mdoverhaul')
        return errors, None
    source = root / 'setup.php'
    if not source.is_file():
        errors.append('setup.php missing')
        return errors, None
    match = re.search(r"define\(\s*['\"]PLUGIN_MDOVERHAUL_VERSION['\"]\s*,\s*['\"](" + SEMVER + r")[ '\"]", source.read_text('utf-8'))
    if not match:
        errors.append('Cannot read version from setup.php')
        return errors, None
    version = match.group(1)
    errors.extend(documentation_errors(root))
    for r in REQUIRED:
        f = root / r
        if not f.is_file() or f.is_symlink():
            errors.append('Missing/non-file documentation: ' + r)
        elif f.stat().st_size < 200:
            errors.append('Suspiciously empty documentation: ' + r)
    for r in VERSIONS:
        f = root / r
        if f.exists() and version not in f.read_text('utf-8', errors='replace'):
            errors.append(f'Version {version} absent in {r}')
    for r in REQUIRED_MARKDOWN:
        f = root / r
        if not f.is_file():
            continue
        content = f.read_text('utf-8', errors='replace')
        for regex in PRIVATE_STRINGS:
            if re.search(regex, content, flags=re.IGNORECASE):
                errors.append(f'Potential private infrastructure string in {r}: {regex}')
        for link in re.findall(r'\]\(([^)]+)\)', content):
            path = link.split('#')[0].strip().split(' ', 1)[0]
            if not path or re.match(r'^(?:https?://|mailto:|#)', path, flags=re.IGNORECASE):
                continue
            # relative docs links only; ignore imagery if intentionally not packaged? check all
            target = (f.parent / path).resolve()
            if not target.is_relative_to(root) or not target.exists():
                errors.append(f'Broken Markdown relative link: {r} -> {path}')
    all_files = {}
    for f in root.rglob('*'):
        rel = f.relative_to(root).as_posix()
        if f.is_symlink():
            errors.append('Symlink forbidden: ' + rel)
            continue
        if f.is_file():
            all_files[rel] = f
    for forbidden in FORBIDDEN_PATHS:
        if forbidden in all_files:
            errors.append('Local private override must not be packaged: ' + forbidden)
    manifest = root / 'SHA512SUMS'
    files_to_hash = {n for n in all_files if n != 'SHA512SUMS'}
    if not manifest.is_file():
        errors.append('SHA512SUMS missing')
    else:
        listed = {}
        for num, line in enumerate(manifest.read_text('utf-8').splitlines(), 1):
            if not line:
                continue
            m = re.fullmatch(r'([a-fA-F0-9]{128})  ([a-zA-Z0-9_.][a-zA-Z0-9_./-]*)', line)
            if not m:
                errors.append(f'Malformed manifest line {num}')
                continue
            digest, rel = m.groups()
            if rel in listed or rel.startswith('/') or '..' in rel.split('/') or rel == 'SHA512SUMS':
                errors.append(f'Invalid/duplicate manifest path {rel}')
                continue
            listed[rel] = digest.lower()
            if rel not in files_to_hash:
                errors.append('Manifest lists non-distributed file ' + rel)
            elif hashlib.sha512(all_files[rel].read_bytes()).hexdigest() != digest.lower():
                errors.append('SHA512 mismatch for ' + rel)
        if set(listed) != files_to_hash:
            for n in sorted(files_to_hash - set(listed)):
                errors.append('Unlisted file ' + n)
    if zip_path:
        zp = Path(zip_path)
        if not zp.is_file():
            errors.append('ZIP not found: ' + str(zp))
        else:
            with zipfile.ZipFile(zp) as z:
                seen = set()
                for info in z.infolist():
                    path = info.filename
                    pure = path.rstrip('/').split('/')
                    if path.startswith('/') or '\\' in path or '..' in pure or not pure or pure[0] != 'mdoverhaul':
                        errors.append('Unsafe ZIP path ' + path)
                        continue
                    if (info.external_attr >> 16) & 0o170000 == stat.S_IFLNK:
                        errors.append('Symlink inside ZIP ' + path)
                    if info.is_dir():
                        continue
                    rel = '/'.join(pure[1:])
                    if rel in seen:
                        errors.append('Duplicate ZIP file ' + rel)
                    seen.add(rel)
                    if rel not in all_files:
                        errors.append('Unknown ZIP file ' + rel)
                    elif z.read(info) != all_files[rel].read_bytes():
                        errors.append('ZIP bytes differ from source ' + rel)
                if seen != set(all_files):
                    for n in sorted(set(all_files) - seen):
                        errors.append('File missing from ZIP ' + n)
    return errors, version


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('folder', help='Unpacked mdoverhaul directory')
    p.add_argument('--zip', dest='archive', help='Optional release ZIP to compare')
    args = p.parse_args()
    failures, version = check(Path(args.folder), args.archive)
    if failures:
        for error in failures:
            print('\033[31m[ERRORE]\033[0m', error, file=sys.stderr)
        sys.exit(1)
    print('\033[32m[OK]\033[0m', f'MDO {version}: required documentation, versions, relative links and SHA512SUMS consistent' + ('; ZIP matches source' if args.archive else ''))

if __name__ == '__main__':
    main()
