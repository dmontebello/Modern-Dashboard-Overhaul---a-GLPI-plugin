#!/usr/bin/env python3
"""Responsive unified creation form tests on GLPI doubles, NOT live GLPI."""
import os
from pathlib import Path
import shutil
import subprocess
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
CSS = (ROOT / 'public/css/task-workspace.css').read_text()
BODY = subprocess.check_output(
    [os.environ.get('MDO_PHP') or shutil.which('php'), str(ROOT / 'tests/render_task_creation.php')],
    text=True
)
tests = 0

def check(result, text):
    global tests
    tests += 1
    if not result:
        raise AssertionError(text)
    print('[OK]', tests, text)

with sync_playwright() as engine:
    browser = engine.chromium.launch(
        headless=True, executable_path=os.environ.get('MDO_CHROMIUM_EXECUTABLE') or shutil.which('chromium'),
        args=['--no-sandbox']
    )
    for width in [390, 768, 1024, 1440]:
        for theme in ['light', 'dark']:
            page = browser.new_page(viewport={'width': width, 'height': 1000})
            page.set_content('<html data-bs-theme="' + theme +
                '"><style>body{margin:0;font:14px system-ui;} .container-xl{max-width:1200px;margin:auto;} '
                'input,textarea,select{font:inherit;min-height:38px;background:transparent;color:inherit;border:1px solid #888;} '
                '.btn{display:inline-block;padding:12px;} ' + CSS + '</style><body><main>' + BODY + '</main></body></html>')
            label = f'{width}px {theme}'
            check(page.evaluate('document.documentElement.scrollWidth <= innerWidth'), label + ' no horizontal overflow')
            check(page.locator('.mdo-unified-create-form').count() == 1, label + ' one form')
            check(page.locator('[name="projects_id"]').count() == 1, label + ' optional project')
            check(page.locator('[name="name"][required]').count() == 1, label + ' accessible required name')
            check(page.locator('[name="due_date"]').count() == 1, label + ' shared due date')
            check(page.get_by_role('button', name='Save').count() == 1, label + ' one save')
            check(page.locator('[data-mdo-standalone-fields]').count() == 1, label + ' standalone fields kept separate')
            check(page.locator('.mdo-tasks-create-actions').evaluate('e => e.getBoundingClientRect().width > 0'), label + ' action row rendered')
            page.locator('[name="name"]').focus()
            check(page.evaluate('document.activeElement.name === "name"'), label + ' keyboard name focus')
            page.close()
    browser.close()
print(tests, 'unified creation browser checks PASSED (doubles, NON COLLAUDATO LIVE)')
