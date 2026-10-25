#!/usr/bin/env python3
"""CRUD/navigation of actual plugin controllers using persistent doubles; NOT GLPI live."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlparse
from playwright.sync_api import sync_playwright
from tasks_ui_playwright import BASE_CSS

ROOT=Path(__file__).resolve().parents[1]
CSS=BASE_CSS+(ROOT/'public/css/task-workspace.css').read_text()


def render(**options):
    return json.loads(subprocess.check_output([os.environ.get('MDO_PHP') or shutil.which('php'),str(ROOT/'tests/render_standalone.php'),json.dumps(options)],text=True))


def page_html(body,dark=False):
    return f'<html data-bs-theme="{"dark" if dark else "light"}"><style>{CSS}</style><body><main>{body}</main></body></html>'


def run():
    checks=0
    def check(ok,label):
        nonlocal checks
        checks+=1
        assert ok,label
        print('[OK]',checks,label)

    with sync_playwright() as engine:
        browser=engine.chromium.launch(headless=True,executable_path=os.environ.get('MDO_CHROMIUM_EXECUTABLE') or shutil.which('chromium'),args=['--no-sandbox'])
        for width in [390,768,1024,1440]:
            for dark in [False,True]:
                for count in [0,100]:
                    response=render(count=count,query={'view':'board'})
                    page=browser.new_page(viewport={'width':width,'height':1000})
                    page.set_content(page_html(response['body'],dark))
                    label=f'{width}px dark={dark} count={count}'
                    check(page.evaluate('document.documentElement.scrollWidth<=innerWidth'),label+' no page overflow')
                    check(page.locator('[data-task-type="standalone"]').count()==count,label+' own task type and count')
                    check(page.locator('select[name="project"], [role="progressbar"]').count()==0,label+' no project or fake percentage')
                    page.locator('.mdo-tasks-filter-panel summary').focus()
                    page.keyboard.press('Enter')
                    check(page.locator('[name="q"]').is_visible(),label+' keyboard filters')
                    page.close()

                form=render(mode='form',query={})
                for zoom in [1,2]:
                    page=browser.new_page(viewport={'width':width,'height':1000})
                    page.set_content(page_html(form['body'],dark))
                    page.locator('body').evaluate('(body, zoom) => body.style.zoom=zoom',zoom)
                    label=f'form {width}px dark={dark} zoom={zoom}'
                    check(page.evaluate('document.documentElement.scrollWidth<=innerWidth'),label+' no page overflow')
                    page.locator('[name="name"]').focus()
                    check(page.locator('[name="name"]').evaluate('(input)=>input===document.activeElement'),label+' keyboard field access')
                    page.close()

        class Handler(BaseHTTPRequestHandler):
            state={}
            options={}
            def log_message(self,*args): pass
            def do_GET(self): self.process()
            def do_POST(self): self.process()
            def process(self):
                query={k:v[-1] for k,v in parse_qs(urlparse(self.path).query).items()}
                post={k:v[-1] for k,v in parse_qs(self.rfile.read(int(self.headers.get('Content-Length',0))).decode(),keep_blank_values=True).items()} if self.command=='POST' else {}
                response=render(rows=self.state,mode='form' if '.form.php' in self.path else 'workspace',query=query,post=post,**self.options)
                self.state.clear(); self.state.update(response['rows'] or {})
                self.send_response(303 if response['redirect'] else response['status'])
                if response['redirect']: self.send_header('Location',response['redirect'])
                self.send_header('Content-Type','text/html; charset=utf-8'); self.end_headers()
                self.wfile.write(page_html(response['body']).encode())

        server=ThreadingHTTPServer(('127.0.0.1',0),Handler)
        threading.Thread(target=server.serve_forever,daemon=True).start()
        base=f'http://127.0.0.1:{server.server_port}/glpi/plugins/mdoverhaul/front/'
        page=browser.new_page(viewport={'width':390,'height':1000})
        page.goto(base+'standalonetask.form.php')
        check(page.get_by_role('link',name='Task di progetto',exact=True).count()==0,'standalone-only creator does not require ProjectTask rights')
        page.locator('[name="name"]').fill('Browser independent')
        page.locator('[name="content"]').fill('<script>must remain text</script>')
        page.locator('[name="status"]').select_option('2')
        page.get_by_role('button',name='Save',exact=True).click()
        page.wait_for_url('**/standalonetask.form.php?id=1')
        check(page.locator('[name="name"]').input_value()=='Browser independent','create without project and native-style POST/redirect')
        check(len(Handler.state)==1 and 'projects_id' not in Handler.state['1'],'no project field or extra object persisted')
        page.goto(base+'tasks.php?source=standalone&view=board')
        page.get_by_role('link',name='Browser independent',exact=True).click()
        page.locator('[name="status"]').select_option('3')
        page.get_by_role('button',name='Save',exact=True).click()
        page.wait_for_load_state('networkidle')
        check(Handler.state['1']['status']==3,'completion saved by own model')
        page.get_by_role('button',name='Delete',exact=True).click()
        page.wait_for_url('**/standalonetask.php')
        check(Handler.state['1']['is_deleted']==1,'delete retains row in trash')
        page.goto(base+'standalonetask.form.php?id=1')
        page.get_by_role('button',name='Restore',exact=True).click()
        page.wait_for_load_state('networkidle')
        check(Handler.state['1']['is_deleted']==0,'restore preserves original independent task')
        result=page.request.post(base+'standalonetask.form.php',form={'id':'1','update':'1','name':'Forbidden'})
        check(result.status==403 and Handler.state['1']['name']=='Browser independent','missing CSRF denied before mutation (middleware double)')
        Handler.options={'user':1,'rights':1024}
        result=page.goto(base+'standalonetask.form.php?id=1')
        check(result.status==200 and page.get_by_role('button',name='Save',exact=True).count()==0,'reader sees form without save controls')
        check(page.get_by_role('button',name='Delete',exact=True).count()==0,'reader has no delete controls')
        Handler.options={'user':2,'rights':1024}
        result=page.goto(base+'standalonetask.form.php?id=1')
        check(result.status==403,'another actor cannot open independent form')
        Handler.options={'entities':[2],'rights':1|2|4|8}
        result=page.goto(base+'standalonetask.form.php?id=1')
        check(result.status==403,'READALL cannot cross active entities')
        Handler.options={'disabled':True}
        result=page.goto(base+'tasks.php?source=standalone&view=board')
        check(result.status==404 and len(Handler.state)==1,'module OFF rejects URL and retains data')
        page.close(); server.shutdown(); server.server_close(); browser.close()
    print(f'{checks} Standalone UI tests PASSED (persistent GLPI doubles, NON COLLAUDATO LIVE).')


if __name__=='__main__': run()
