#!/usr/bin/env python3
"""Actual Settings PHP output and WCAG contrast, against GLPI doubles. NOT LIVE."""
import argparse
import os
from pathlib import Path
import re
import shutil
import subprocess
from playwright.sync_api import sync_playwright
from tasks_ui_playwright import BASE_CSS

ROOT = Path(__file__).resolve().parents[1]


def luminance(color):
    values = [int(v) / 255 for v in re.findall(r'\d+', color)[:3]]
    return sum(v * w for v, w in zip([v / 12.92 if v <= .04045 else ((v + .055) / 1.055) ** 2.4 for v in values], [.2126, .7152, .0722]))


def contrast(a, b):
    values = sorted([luminance(a), luminance(b)])
    return (values[1] + .05) / (values[0] + .05)


def run():
    parser = argparse.ArgumentParser()
    parser.add_argument('--screenshots', type=Path)
    args = parser.parse_args()
    html = subprocess.check_output([os.environ.get('MDO_PHP') or shutil.which('php'), str(ROOT / 'tests/render_settings.php')], text=True)
    css = (ROOT / 'public/css/settings.css').read_text()
    tests = 0

    def check(value, label):
        nonlocal tests
        tests += 1
        assert value, label
        print('[OK]', tests, label)

    with sync_playwright() as engine:
        browser = engine.chromium.launch(headless=True, executable_path=os.environ.get('MDO_CHROMIUM_EXECUTABLE') or shutil.which('chromium'), args=['--no-sandbox'])
        for width in [390, 768, 1024, 1440]:
            for dark in [False, True]:
                for zoom in [1, 2]:
                    page = browser.new_page(viewport={'width': width, 'height': 1000})
                    page.set_content(f'<html data-glpi-theme-dark="{int(dark)}"><style>{BASE_CSS} :root{{--tblr-secondary-color:#111827}} .mdo-settings{{zoom:{zoom}}} {css}</style><body><main>{html}</main></body></html>')
                    label = f'{width}px dark={dark} zoom={zoom}'
                    check(page.evaluate('document.documentElement.scrollWidth<=innerWidth'), label + ' no overflow')
                    bg = page.locator('.mdo-settings-card').evaluate('e=>getComputedStyle(e).backgroundColor')
                    for text in page.locator('.mdo-setting-title,.mdo-setting-detail,.mdo-settings-intro,.mdo-settings-version,.mdo-build-info dt,.mdo-build-info dd').all():
                        fg = text.evaluate('e=>getComputedStyle(e).color')
                        check(contrast(fg, bg) >= 4.5, label + ' text AA')
                    for control in page.locator('.form-check-input').all():
                        border = control.evaluate('e=>getComputedStyle(e).borderTopColor')
                        check(contrast(border, bg) >= 3, label + ' control AA')
                    page.locator('.form-check-input').first.focus()
                    page.keyboard.press('Space')
                    check(page.evaluate('document.activeElement.matches(".form-check-input")'), label + ' keyboard switch')
                    check(page.locator('.mdo-build-info').count() == 1, label + ' provenance visible')
                    if args.screenshots and width in [390, 1440] and zoom == 1:
                        args.screenshots.mkdir(parents=True, exist_ok=True)
                        page.screenshot(path=str(args.screenshots / f'settings-{width}-{dark}.png'), full_page=True)
                    page.close()
        browser.close()
    print(f'{tests} Settings UI tests PASSED (isolated doubles, NON COLLAUDATO LIVE).')


if __name__ == '__main__':
    run()
