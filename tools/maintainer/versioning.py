"""Shared SemVer/provenance rules for source, build and publication."""
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import subprocess

SEMVER = r"\d+\.\d+\.\d+(?:-dev\.[1-9]\d*)?"
GENERATED = {"SHA512SUMS", "build-info.json"}


def version(root):
    match = re.search(r"define\('PLUGIN_MDOVERHAUL_VERSION', '(" + SEMVER + r")'\)", (Path(root) / 'setup.php').read_text())
    if not match:
        raise ValueError('Missing/invalid single version in setup.php')
    return match[1]


def included(relative):
    name = relative.as_posix()
    return not (name in GENERATED or name == 'config/ui.local.php'
                or name.endswith(('.zip', '.zip.sha512', '.pyc'))
                or re.fullmatch(r'docs/releases/' + SEMVER + r'\.build\.json', name)
                or any(part in {'.git', '.github', '__pycache__', '.venv', 'node_modules'} for part in relative.parts)
                or name.startswith(('.', 'dist/', 'build/')))


def metadata(root):
    root = Path(root)
    sha = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root, text=True).strip()
    rows = subprocess.check_output(['git', 'status', '--porcelain', '--untracked-files=all'], cwd=root, text=True).splitlines()
    dirty = any(not (row[3:] in GENERATED or row[3:].endswith(('.zip', '.zip.sha512', '.pyc')) or row[3:].startswith('build/') or '__pycache__/' in row[3:] or re.fullmatch(r'docs/releases/' + SEMVER + r'\.build\.json', row[3:])) for row in rows)
    expected = os.environ.get('GITHUB_SHA')
    if expected and (sha != expected or dirty):
        raise ValueError('CI must build the exact clean tested source SHA')
    return {'version': version(root), 'channel': 'Dev' if '-dev.' in version(root) else 'Main',
            'source_commit': None if dirty else sha, 'source_state': 'dirty' if dirty else 'clean',
            'build_id': os.environ.get('GITHUB_RUN_ID', 'local'),
            'built_at': datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')}


def documentation_errors(root):
    root = Path(root)
    ver = version(root)
    if '-dev.' not in ver:
        return []
    errors = []
    for name in ['CHANGELOG.md', 'README.md', 'QA.md', 'docs/dev/STATUS.md', 'docs/dev/VERSIONING_POLICY.md', f'docs/releases/{ver}.md']:
        path = root / name
        if not path.is_file() or ver not in path.read_text():
            errors.append(f'Current version {ver} missing from {name}')
    notes = root / f'docs/releases/{ver}.md'
    if notes.is_file():
        text = notes.read_text()
        for label in ['Fix', 'File', 'Test', 'NON COLLAUDATO LIVE', 'Rollback', 'SHA512', 'build-info.json', 'Prossimi']:
            if label not in text:
                errors.append(f'Release notes missing {label}')
    return errors
