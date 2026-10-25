#!/usr/bin/env python3
"""Responsive tests of the actual Tasks PHP output with isolated GLPI doubles.

No GLPI runtime, database or Vue: native ACL/AJAX/DnD still need staging tests.
"""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlparse

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
TESTS = 0
BASE_CSS = """+:root {--tblr-body-color:#182433;--tblr-bg-surface:#fff;--tblr-card-bg:#fff;
 --tblr-border-color:#d5dce5;--tblr-secondary-color:#4b5f73;--tblr-primary:#206bc4;--tblr-danger:#b42318;}
[data-bs-theme=dark] {--tblr-body-color:#edf2f7;--tblr-bg-surface:#182433;--tblr-card-bg:#1d2d40;
 --tblr-border-color:#46586d;--tblr-secondary-color:#b0bfd1;--tblr-primary:#9bc5ff;--tblr-danger:#ffb4ab;}
* {box-sizing:border-box;} body {margin:0;font:14px/1.5 system-ui,sans-serif;
 color:var(--tblr-body-color);background:var(--tblr-bg-surface);}
main {max-width:1248px;margin:auto;padding:24px;min-width:0;}
a {color:var(--tblr-primary);text-decoration:none;} .btn {display:inline-flex;gap:4px;
 align-items:center;justify-content:center;min-height:40px;padding:8px 12px;border:1px solid var(--tblr-border-color);
 border-radius:6px;font:inherit;color:var(--tblr-primary);background:var(--tblr-bg-surface);}
.btn-primary {background:#206bc4;color:#fff;border-color:#206bc4;}
input,select {display:block;width:100%;padding:8px;border:1px solid var(--tblr-border-color);
 border-radius:6px;background:var(--tblr-bg-surface);color:var(--tblr-body-color);font:inherit;min-height:40px;}
.text-secondary {color:var(--tblr-secondary-color);} .alert {border:1px solid var(--tblr-border-color);padding:16px;}
"""


def check(valid, message):
    global TESTS
    TESTS += 1
    if not valid:
        raise AssertionError(message)
    print(f"[OK] {TESTS} {message}")


def markup(count=100, theme="light", query=None, error=False):
    php = os.environ.get("MDO_PHP") or shutil.which("php")
    if not php:
        raise RuntimeError("PHP CLI required (PATH or MDO_PHP)")
    result = subprocess.run(
        [php, str(ROOT / "tests/render_tasks.php"), json.dumps({
            "count": count, "query": query or {"view": "board"}, "error": error,
        })], capture_output=True, text=True, check=True,
    )
    css = BASE_CSS + (ROOT / "public/css/task-workspace.css").read_text()
    return f'<!doctype html><html lang="it" data-bs-theme="{theme}"><head><meta charset="utf-8"><style>{css}</style></head><body><main>{result.stdout}</main></body></html>'


def run():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--screenshots", type=Path)
    args = parser.parse_args()
    if args.screenshots:
        args.screenshots.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as engine:
        executable = os.environ.get("MDO_CHROMIUM_EXECUTABLE") or shutil.which("chromium") or shutil.which("chromium-browser")
        browser = engine.chromium.launch(headless=True, executable_path=executable, args=["--no-sandbox", "--disable-gpu"])
        for width in [1440, 1024, 768, 390]:
            for theme in ["light", "dark"]:
                for count in [0, 100]:
                    label = f"{width}px {theme} {count} tasks"
                    page = browser.new_page(viewport={"width": width, "height": 1000})
                    page.set_content(markup(count, theme))
                    check(page.evaluate("document.documentElement.scrollWidth <= innerWidth"), label + ": no page overflow")
                    check(page.locator('.mdo-tasks-view-tabs:not(.mdo-tasks-source-tabs) [aria-current="page"]').inner_text() == "Kanban", label + ": active view")
                    check(page.locator(".mdo-tasks-card").count() == count, label + ": real PHP card count")
                    check(page.locator(".mdo-tasks-filters label").count() == 7, label + ": labelled filters")
                    panel = page.locator(".mdo-tasks-filter-panel summary")
                    panel.focus()
                    page.keyboard.press("Enter")
                    check(page.locator('[name="q"]').is_visible() and page.evaluate("document.documentElement.scrollWidth <= innerWidth"), label + ": keyboard opens filters without overflow")
                    page.keyboard.press("Enter")
                    if count:
                        board = page.locator(".mdo-tasks-board")
                        check(board.evaluate("e => e.scrollWidth >= e.clientWidth"), label + ": board owns horizontal scroll")
                        check(page.locator(".mdo-tasks-column").first.evaluate("e => e.getBoundingClientRect().width >= 270"), label + ": readable columns")
                        check(page.locator(".mdo-tasks-card-title").evaluate_all("es => es.every(e => e.scrollWidth <= e.clientWidth + 1)"), label + ": long titles wrap")
                        check(page.locator('.mdo-tasks-card:has([aria-valuenow="100"]) .mdo-tasks-overdue').count() == 0, label + ": completed cards never overdue")
                        board.focus()
                        page.keyboard.press("ArrowRight")
                        page.wait_for_timeout(120)
                        check(board.evaluate("e => e.scrollLeft > 0"), label + ": keyboard scroll")
                        page.keyboard.press("Tab")
                        check(page.evaluate('document.activeElement.matches(".mdo-tasks-card-title")'), label + ": keyboard reaches task link")
                        check(page.evaluate('getComputedStyle(document.activeElement).outlineStyle === "solid"'), label + ": visible focus")
                        check(page.locator(".mdo-tasks-card").first.evaluate("e => getComputedStyle(e).backgroundColor") == ("rgb(255, 255, 255)" if theme == "light" else "rgb(24, 36, 51)"), label + ": native theme tokens")
                    else:
                        check(page.locator(".mdo-tasks-empty").inner_text().startswith("Nessuna attività visibile"), label + ": empty state")
                    if args.screenshots and width in [1440, 390]:
                        page.evaluate('document.activeElement.blur(); const board = document.querySelector(".mdo-tasks-board"); if(board) board.scrollLeft = 0;')
                        page.screenshot(path=str(args.screenshots / f"tasks-{width}-{theme}-{count}.png"), full_page=True)
                    page.close()

        page = browser.new_page(viewport={"width": 1024, "height": 1000})
        page.set_content(markup(query={"view": "board", "group": "progress"}))
        check(page.locator(".mdo-tasks-column > header h2").all_text_contents() == ["0%", "1–99%", "100%"], "progress grouping uses percentages")
        check(page.get_by_text("Raggruppamento per avanzamento:", exact=False).count() == 1, "progress is explicitly separate from manual Stage")
        page.set_content(markup(error=True))
        check(page.locator('[role="alert"]').count() == 1 and page.locator(".mdo-tasks-card").count() == 0, "safe error state")

        def serve(route):
            parsed = {key: values[-1] for key, values in parse_qs(urlparse(route.request.url).query).items()}
            if urlparse(route.request.url).path.endswith('projecttask.form.php'):
                route.fulfill(status=200, content_type='text/html', body='<h1>Native form destination (double)</h1>')
                return
            if parsed.get('new') == '1':
                response = json.loads(subprocess.check_output([os.environ.get('MDO_PHP') or shutil.which('php'), str(ROOT/'tests/render_tasks.php'), json.dumps({'response':True,'query':parsed})], text=True))
                if response['redirect']:
                    route.fulfill(status=303, headers={'Location':response['redirect']})
                    return
            route.fulfill(status=200, content_type="text/html", body=markup(query=parsed))

        page.route("http://mdo.test/**", serve)
        page.goto("http://mdo.test/glpi/plugins/mdoverhaul/front/tasks.php?view=board")
        page.locator(".mdo-tasks-filter-panel summary").click()
        page.locator('[name="q"]').fill("Attività 20")
        page.locator('[name="filter"]').select_option("done")
        page.locator('[name="project"]').select_option("1")
        page.get_by_role("button", name="Filtra", exact=True).click()
        page.wait_for_url("**q=Attivit**")
        check(page.locator(".mdo-tasks-card").count() == 1, "GET filters combine on the actual controller")
        page.get_by_role("link", name="Reimposta", exact=True).click()
        check(page.locator(".mdo-tasks-card").count() == 100, "reset restores the visible dataset")
        page.get_by_role("link", name="Elenco", exact=True).click()
        check(page.locator('.mdo-tasks-view-tabs:not(.mdo-tasks-source-tabs) [aria-current="page"]').inner_text() == "Elenco", "list navigation")
        page.get_by_role("link", name="Kanban", exact=True).click()
        check(page.locator(".mdo-tasks-card").count() == 100, "return to board")
        class CreationHandler(BaseHTTPRequestHandler):
            def log_message(self, *args):
                pass

            def do_GET(self):
                parsed = {key: values[-1] for key, values in parse_qs(urlparse(self.path).query).items()}
                if urlparse(self.path).path.endswith('projecttask.form.php'):
                    body = '<h1>Native form destination (double)</h1>'
                else:
                    response = json.loads(subprocess.check_output([os.environ.get('MDO_PHP') or shutil.which('php'), str(ROOT/'tests/render_tasks.php'), json.dumps({'response':True,'query':parsed})], text=True))
                    if response['redirect']:
                        self.send_response(303)
                        self.send_header('Location', response['redirect'])
                        self.end_headers()
                        return
                    body = markup(query=parsed)
                self.send_response(200)
                self.send_header('Content-Type', 'text/html; charset=utf-8')
                self.end_headers()
                self.wfile.write(body.encode())

        server = ThreadingHTTPServer(('127.0.0.1', 0), CreationHandler)
        threading.Thread(target=server.serve_forever, daemon=True).start()
        for width in [390, 1440]:
            page.set_viewport_size({'width':width,'height':1000})
            page.goto(f'http://127.0.0.1:{server.server_port}/glpi/plugins/mdoverhaul/front/tasks.php?new=1&kind=project')
            check(page.evaluate('document.documentElement.scrollWidth<=innerWidth'), f'{width}px project creation has no overflow')
            check(page.locator('.mdo-tasks-create').evaluate('e=>e.getBoundingClientRect().width<=800'), f'{width}px bounded project picker')
            check(page.locator('.mdo-tasks-create-actions').evaluate('e=>parseFloat(getComputedStyle(e).gap)>=12'), f'{width}px spaced actions')
            page.locator('select[name="project"]').select_option('1')
            page.get_by_role('button',name='Continua',exact=True).click()
            page.wait_for_url('**/front/projecttask.form.php?projects_id=1')
            check(page.get_by_role('heading').inner_text() == 'Native form destination (double)', f'{width}px actual GET form reaches native form URL (double)')
        server.shutdown()
        server.server_close()

        # Static DOM contract from GLPI 11 Column.vue. Does not execute Vue/DnD.
        native = '<div class="kanban"><div class="kanban-columns">'
        for collapsed in [False, True, False]:
            native += f'<section class="kanban-column {"collapsed" if collapsed else ""}"><div class="kanban-column-header"><div class="kanban-column-header-content"><div class="content-left"><h2 class="kanban-column-title">Stato lungo del progetto</h2><span class="kanban_nb">10</span></div></div></div><div class="kanban-body"><article class="kanban-item" draggable="true">Task nativa</article></div></section>'
        native += '</div></div>'
        for width in [1440, 390]:
            page.set_viewport_size({"width": width, "height": 1000})
            page.set_content(f'<style>{BASE_CSS}.kanban-columns{{display:flex;overflow-x:auto;}}.kanban-column{{width:96px;flex-shrink:1;}}</style><div id="kanban-app">{native}</div><aside>{native}</aside>')
            ordinary = page.locator("aside .kanban-column").first.evaluate("e => e.getBoundingClientRect().width")
            page.add_style_tag(content=(ROOT / "public/css/task-kanban.css").read_text())
            check(page.locator("#kanban-app .kanban-column:not(.collapsed)").first.evaluate("e => e.getBoundingClientRect().width >= 270"), f"{width}px native skin: readable columns")
            check(page.locator("#kanban-app .collapsed .kanban-column-title").evaluate('e => getComputedStyle(e).writingMode === "horizontal-tb"'), f"{width}px native skin: horizontal collapsed label")
            check(page.locator("aside .kanban-column").first.evaluate("e => e.getBoundingClientRect().width") == ordinary, f"{width}px native skin: scoped selectors")
            check(page.locator('#kanban-app [draggable="true"]').count() == 3, f"{width}px native skin: native drag attributes preserved")
        browser.close()
    print(f"\n{TESTS} Tasks UI tests PASSED (real controller output, isolated GLPI doubles).")


if __name__ == "__main__":
    run()
