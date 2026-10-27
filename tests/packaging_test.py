#!/usr/bin/env python3
"""Exercise provenance, archive/source tampering and prerelease documentation gates."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from zipfile import ZipFile

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools/maintainer'))
from publish_dev import verify, documentation_only_since
from versioning import documentation_errors, version, included, VERSION_DOCS


class PackagingTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / 'repo'
        self.root.mkdir()
        (self.root / 'setup.php').write_text("<?php define('PLUGIN_MDOVERHAUL_VERSION', '0.8.1-dev.1');")
        (self.root / 'file.txt').write_text('candidate source')
        for args in [['init','-q'], ['config','user.name','MDO tests'], ['config','user.email','tests@example.invalid'], ['add','.'], ['commit','-qm','test fixture']]:
            subprocess.run(['git',*args], cwd=self.root, check=True)
        self.sha = subprocess.check_output(['git','rev-parse','HEAD'], cwd=self.root, text=True).strip()
        self.archive = Path(self.temp.name) / 'Modern_Dashboard_Overhaul_0.8.1-dev.1.zip'

    def build(self, **changes):
        info = {'version':version(self.root),'source_commit':self.sha,'source_state':'clean','build_id':'123','channel':'Dev','built_at':'2026-10-24T12:00:00Z'}
        info.update(changes)
        files = {name:(self.root/name).read_bytes() for name in ['setup.php','file.txt']}
        files['build-info.json'] = json.dumps(info).encode()
        manifest = ''.join(f'{hashlib.sha512(data).hexdigest()}  {name}\n' for name,data in files.items())
        with ZipFile(self.archive, 'w') as z:
            for name,data in files.items(): z.writestr('mdoverhaul/'+name,data)
            z.writestr('mdoverhaul/SHA512SUMS',manifest)
        digest = hashlib.sha512(self.archive.read_bytes()).hexdigest()
        Path(str(self.archive)+'.sha512').write_text(f'{digest}  {self.archive.name}\n')

    def test_exact_tested_source(self):
        self.build()
        self.assertEqual(verify(self.root,self.archive,self.sha,'123')[0]['version'],'0.8.1-dev.1')

    def test_wrong_source_sha(self):
        self.build(source_commit='a'*40)
        with self.assertRaises(ValueError): verify(self.root,self.archive,self.sha,'123')

    def test_wrong_run(self):
        self.build(build_id='999')
        with self.assertRaises(ValueError): verify(self.root,self.archive,self.sha,'123')

    def test_dirty_build(self):
        self.build(source_state='dirty')
        with self.assertRaises(ValueError): verify(self.root,self.archive,self.sha,'123')

    def test_source_tampering(self):
        self.build()
        (self.root/'file.txt').write_text('different source')
        with self.assertRaises(ValueError): verify(self.root,self.archive,self.sha,'123')

    def test_archive_tampering(self):
        self.build()
        self.archive.write_bytes(self.archive.read_bytes()+b'tamper')
        with self.assertRaises(ValueError): verify(self.root,self.archive,self.sha,'123')

    def test_documentation_only_update_does_not_require_new_version(self):
        (self.root / 'docs').mkdir()
        (self.root / 'docs' / 'STATUS.md').write_text('Handoff revision')
        (self.root / 'MDO_Dev_Latest.zip').write_bytes(b'old archive')
        (self.root / 'SHA512SUMS').write_text('old manifest')
        subprocess.run(['git', 'add', '.'], cwd=self.root, check=True)
        subprocess.run(['git', 'commit', '-qm', 'docs: handoff'], cwd=self.root, check=True)
        docs_sha = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=self.root, text=True).strip()
        self.assertTrue(documentation_only_since(self.root, self.sha, docs_sha))
        (self.root / 'file.txt').write_text('code actually changed')
        subprocess.run(['git', 'add', 'file.txt'], cwd=self.root, check=True)
        subprocess.run(['git', 'commit', '-qm', 'fix: source change'], cwd=self.root, check=True)
        code_sha = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=self.root, text=True).strip()
        self.assertFalse(documentation_only_since(self.root, self.sha, code_sha))
        self.assertFalse(documentation_only_since(self.root, code_sha, code_sha))

    def test_missing_notes(self):
        self.assertTrue(documentation_errors(self.root))

    def test_early_gate_matches_packager_required_version_docs(self):
        # A manual omitted from a new prerelease must fail before Playwright.
        self.assertIn('docs/ARCHITETTURA.md', VERSION_DOCS)
        (self.root / 'docs').mkdir()
        manual = self.root / 'docs' / 'ARCHITETTURA.md'
        manual.write_text('historical 0.8.1-dev.10 manual')
        errors = documentation_errors(self.root)
        self.assertIn('Current version 0.8.1-dev.1 missing from docs/ARCHITETTURA.md', errors)
        manual.write_text('current 0.8.1-dev.1 architecture')
        self.assertNotIn('Current version 0.8.1-dev.1 missing from docs/ARCHITETTURA.md',
                         documentation_errors(self.root))

    def test_generated_and_legacy_archives_not_nested(self):
        self.assertFalse(included(Path('MDO_Dev_Latest.zip')))
        self.assertFalse(included(Path('docs/releases/0.8.1-dev.1.build.json')))
        self.assertTrue(included(Path('docs/releases/0.8.1-dev.1.md')))


if __name__ == '__main__': unittest.main()
