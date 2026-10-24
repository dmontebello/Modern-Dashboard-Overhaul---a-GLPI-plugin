#!/usr/bin/env python3
"""Fail before building when candidate identity or release notes are incomplete."""
from pathlib import Path
from versioning import documentation_errors, version

root = Path(__file__).resolve().parents[2]
errors = documentation_errors(root)
if errors:
    raise SystemExit('\n'.join(errors))
print('PASS: candidate documentation', version(root))
