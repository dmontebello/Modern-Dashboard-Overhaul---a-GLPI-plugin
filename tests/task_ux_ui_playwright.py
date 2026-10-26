#!/usr/bin/env python3
"""Repeatable Kanban transitions and visible drag affordance in Chromium fixture."""
import json, os, shutil, subprocess
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
php=os.environ.get('MDO_PHP') or shutil.which('php')
body=subprocess.check_output([php,str(ROOT/'tests/render_tasks.php'),json.dumps({
  'count':3,'query':{'view':'board','source':'project','group':'progress'}})],text=True)
script=(ROOT/'public/js/task-workspace.js').read_text()
css=(ROOT/'public/css/task-workspace.css').read_text()
with sync_playwright() as playwright:
    browser=playwright.chromium.launch(headless=True,args=['--no-sandbox'])
    page=browser.new_page()
    page.set_content('<html><head><style>'+css+'</style></head><body>'+body+'</body></html>')
    page.evaluate("""() => {
       window.__calls = [];
       window.fetch = async (_url, options) => {
         const input=new URLSearchParams(options.body);
         window.__calls.push(Object.fromEntries(input));
         return {ok:true,json:async()=>({ok:true,stage:input.get('stage'),
             version:'version-'+window.__calls.length, token:'rotated-'+window.__calls.length})};
       };
    }""")
    page.add_script_tag(content=script)
    card=page.locator('.mdo-tasks-card[data-can-move="1"]').first
    assert card.count()==1, 'editable card is present'
    for i,stage in enumerate(['done','doing','todo'],start=1):
        card.locator('[data-stage-choice]').select_option(stage)
        page.wait_for_function('([stage,i]) => window.__calls.length === i && !!document.querySelector("[data-mdo-column="+stage+"] .mdo-tasks-card[data-can-move=\\"1\\"]")',[stage,i])
        assert page.locator('[data-mdo-board]').get_attribute('data-token')=='rotated-'+str(i)
    calls=page.evaluate('window.__calls')
    assert [c['stage'] for c in calls]==['done','doing','todo'],calls
    assert calls[0]['_glpi_csrf_token'] != calls[1]['_glpi_csrf_token'], calls
    assert calls[1]['_glpi_csrf_token'] != calls[2]['_glpi_csrf_token'], calls
    card.evaluate("""card => card.dispatchEvent(new DragEvent('dragstart',{
      bubbles:true,dataTransfer:new DataTransfer()
    }))""")
    assert page.locator('.mdo-drag-preview').count()==1, 'visible drag ghost created'
    card.evaluate("""card => card.dispatchEvent(new DragEvent('dragend',{bubbles:true}))""")
    assert page.locator('.mdo-drag-preview').count()==0, 'drag ghost cleaned'
    browser.close()
print('Task UX repeated Kanban and drag ghost tests PASSED (Chromium fixture)')
