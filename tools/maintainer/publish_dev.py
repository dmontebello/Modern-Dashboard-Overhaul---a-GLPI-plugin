#!/usr/bin/env python3
"""Verify an exact tested CI artifact, then update Dev packages with a normal push."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import stat
from datetime import datetime
from zipfile import ZipFile

from versioning import SEMVER, included, version


def git(root, *args):
    return subprocess.check_output(['git', *args], cwd=root, text=True).strip()


def verify(root, archive, source_sha, run_id):
    ver = version(root)
    if '-dev.' not in ver or not re.fullmatch('[a-f0-9]{40}', source_sha):
        raise ValueError('Only an exact Dev source commit may be published')
    if git(root, 'rev-parse', 'HEAD') != source_sha:
        raise ValueError('Checkout is not the tested commit')
    digest = hashlib.sha512(archive.read_bytes()).hexdigest()
    if Path(str(archive) + '.sha512').read_text() != f'{digest}  {archive.name}\n':
        raise ValueError('Invalid ZIP sidecar')
    with ZipFile(archive) as z:
        names = z.namelist()
        if len(names) != len(set(names)) or any(not n.startswith('mdoverhaul/') or '..' in n.split('/') or '\\' in n for n in names):
            raise ValueError('Unsafe or duplicate ZIP paths')
        if any(stat.S_ISLNK(entry.external_attr >> 16) for entry in z.infolist()):
            raise ValueError('Symlinks are not release files')
        lines = z.read('mdoverhaul/SHA512SUMS').decode().splitlines()
        if any(not re.fullmatch(r'[a-f0-9]{128}  [a-zA-Z0-9_./-]+', line) for line in lines):
            raise ValueError('Invalid manifest entry')
        manifest = dict(line.split('  ', 1)[::-1] for line in lines)
        if len(manifest) != len(lines):
            raise ValueError('Duplicate manifest entry')
        expected = {p.relative_to(root).as_posix() for p in root.rglob('*') if p.is_file() and included(p.relative_to(root))}
        if set(manifest) != expected | {'build-info.json'} or set(names) != {'mdoverhaul/' + n for n in manifest} | {'mdoverhaul/SHA512SUMS'}:
            raise ValueError('Archive/source file sets differ')
        for name, value in manifest.items():
            data = z.read('mdoverhaul/' + name)
            if hashlib.sha512(data).hexdigest() != value:
                raise ValueError('Manifest digest mismatch: ' + name)
            if name != 'build-info.json' and (root / name).read_bytes() != data:
                raise ValueError('Artifact differs from tested checkout: ' + name)
        info = json.loads(z.read('mdoverhaul/build-info.json'))
        if info.get('version') != ver or info.get('channel') != 'Dev' or info.get('source_commit') != source_sha or info.get('source_state') != 'clean' or info.get('build_id') != run_id:
            raise ValueError('Wrong build provenance')
        if not re.fullmatch(r'\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ', info.get('built_at', '')):
            raise ValueError('Invalid UTC build date')
        datetime.strptime(info['built_at'], '%Y-%m-%dT%H:%M:%SZ')
    return info, digest, manifest


def documentation_only_since(root, previous_source_sha, current_sha):
    """Ignore publisher outputs; refuse any source or tooling changes without bump."""
    if not all(re.fullmatch(r'[a-f0-9]{40}', sha) for sha in (previous_source_sha, current_sha)):
        return False
    ancestor = subprocess.run(['git', 'merge-base', '--is-ancestor', previous_source_sha, current_sha],
                              cwd=root, capture_output=True, check=False)
    if ancestor.returncode != 0:
        return False
    changed = git(root, 'diff', '--name-only', '--no-renames', previous_source_sha, current_sha).splitlines()
    if not changed:
        return False
    def allowed(path):
        generated = (path == 'SHA512SUMS'
            or re.fullmatch(r'MDO_Dev_Latest\.zip(?:\.sha512)?', path)
            or re.fullmatch(r'Modern_Dashboard_Overhaul_' + SEMVER + r'\.zip(?:\.sha512)?', path)
            or re.fullmatch(r'docs/releases/' + SEMVER + r'\.build\.json', path))
        docs = ((path.startswith('docs/') and path.endswith('.md'))
            or path in {'AGENTS.md', 'README.md', 'CHANGELOG.md', 'QA.md', 'UPDATE.md',
                        'tools/README.md', '.github/BRANCHES.md'})
        return bool(generated or docs)
    return all(allowed(path) for path in changed)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archive', type=Path)
    parser.add_argument('--source-sha', required=True)
    parser.add_argument('--run-id', required=True)
    parser.add_argument('--verify-only', action='store_true')
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2]
    archive = args.archive.resolve()
    info, digest, manifest = verify(root, archive, args.source_sha, args.run_id)
    if args.verify_only:
        print('PASS: archive, source, manifest and build provenance verified')
        return
    if archive.name != f"Modern_Dashboard_Overhaul_{info['version']}.zip":
        raise ValueError('Versioned ZIP has an unexpected name')
    subprocess.run(['git', 'fetch', '--no-tags', 'origin', 'Dev'], cwd=root, check=True)
    if git(root, 'rev-parse', 'origin/Dev') != args.source_sha:
        print('Dev moved; stale artifact not published, previous Latest retained')
        return
    # Immutable means even a rerun must not overwrite an existing versioned package.
    tracked = subprocess.run(['git', 'cat-file', '-e', 'HEAD:' + archive.name], cwd=root, capture_output=True)
    if tracked.returncode == 0:
        old = subprocess.check_output(['git', 'show', 'HEAD:' + archive.name], cwd=root)
        if hashlib.sha512(old).hexdigest() != digest:
            record = root / f"docs/releases/{info['version']}.build.json"
            try:
                previous = json.loads(record.read_text(encoding='utf-8'))['source_commit']
            except (OSError, KeyError, ValueError, TypeError):
                previous = None
            if previous == args.source_sha:
                print('PASS: immutable ZIP already exists for the same source; no overwrite')
                return
            if isinstance(previous, str) and documentation_only_since(root, previous, args.source_sha):
                print('PASS: documentation-only update; immutable ZIP and Latest unchanged')
                return
            raise ValueError('Immutable version already exists; increment Dev version for code changes')
    target = root / archive.name
    shutil.copyfile(archive, target)
    shutil.copyfile(Path(str(archive) + '.sha512'), Path(str(target) + '.sha512'))
    alias = root / 'MDO_Dev_Latest.zip'
    shutil.copyfile(archive, alias)
    Path(str(alias) + '.sha512').write_text(f'{digest}  {alias.name}\n')
    record = root / f"docs/releases/{info['version']}.build.json"
    record.write_text(json.dumps({**info, 'zip': archive.name, 'sha512': digest,
        'actions_url': f'https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/{args.run_id}'}, indent=2) + '\n')
    (root / 'SHA512SUMS').write_text(''.join(f'{value}  {name}\n' for name, value in sorted(manifest.items()) if name != 'build-info.json'))
    files = [target.name, target.name + '.sha512', alias.name, alias.name + '.sha512', str(record.relative_to(root)), 'SHA512SUMS']
    subprocess.run(['git', 'add', '--', *files], cwd=root, check=True)
    subprocess.run(['git', 'commit', '-m', f"build(dev): publish tested {info['version']} [skip ci]"], cwd=root, check=True)
    subprocess.run(['git', 'push', 'origin', 'HEAD:refs/heads/Dev'], cwd=root, check=True)


if __name__ == '__main__':
    main()
