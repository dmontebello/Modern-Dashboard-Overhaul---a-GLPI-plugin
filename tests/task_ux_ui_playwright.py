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
    card=page.locator('.mdo-tasks-card[data-can-move="1"][data-id="3"]')
    assert card.count()==1, 'editable card is present'
    for i,stage in enumerate(['done','doing','todo'],start=1):
        card.locator('[data-stage-choice]').select_option(stage)
        page.wait_for_function("""(info) => window.__calls.length === info.i &&
            !!document.querySelector('[data-mdo-column="' + info.stage + '"] .mdo-tasks-card[data-can-move="1"]')""",
            arg={'stage':stage,'i':i},timeout=8000)
        assert page.locator('[data-mdo-board]').get_attribute('data-token')=='rotated-'+str(i)
        expected={'todo':int(stage=='todo'), 'doing':2+int(stage=='doing'), 'done':int(stage=='done')}
        counts={key:int(page.locator(f'[data-mdo-column="{key}"] > header > span').inner_text()) for key in expected}
        assert counts == expected, f'live counters after {stage}: {counts} vs {expected}'
    calls=page.evaluate('window.__calls')
    assert [c['stage'] for c in calls]==['done','doing','todo'],calls
    assert calls[0]['_glpi_csrf_token'] != calls[1]['_glpi_csrf_token'], calls
    assert calls[1]['_glpi_csrf_token'] != calls[2]['_glpi_csrf_token'], calls
    card.evaluate("""card => {
      const transfer = new DataTransfer();
      transfer.setDragImage = (element) => { window.__ghostCaptured = !!element.classList.contains('mdo-drag-preview'); };
      card.dispatchEvent(new DragEvent('dragstart',{bubbles:true,dataTransfer:transfer}));
    }""")
    assert page.evaluate('window.__ghostCaptured === true'), 'drag ghost captured in viewport'
    page.locator('[data-mdo-board] .mdo-tasks-card[data-id="3"]').evaluate("""card => card.dispatchEvent(new DragEvent('dragend',{bubbles:true}))""")
    page.wait_for_function("document.querySelectorAll('.mdo-drag-preview').length === 0",timeout=3000)
    assert page.locator('.mdo-drag-preview').count()==0, 'drag ghost cleaned'
    # Column counters reflect actual DOM card movement without a page reload.
    counts=page.locator('[data-mdo-column] > header > span').all_text_contents()
    assert all(x.isdigit() for x in counts), f'non-numeric live counters: {counts}'
    # Unified view with both itemtypes: each drag must route to its source model.
    mixed=subprocess.check_output([php,str(ROOT/'tests/render_tasks.php'),json.dumps({
      'count':2,'independent_count':2,'query':{'view':'board','source':'all','group':'state'}})],text=True)
    page.set_content('<html><head><style>'+css+'</style></head><body>'+mixed+'</body></html>')
    page.evaluate("""() => { window.__calls=[]; window.fetch=async (_url,opts)=>{
      const q=new URLSearchParams(opts.body); window.__calls.push(Object.fromEntries(q));
      return {ok:true,json:async()=>({ok:true,stage:q.get('stage'),version:'v'+window.__calls.length,token:'r'+window.__calls.length})};
    }; }""")
    page.add_script_tag(content=script)
    assert page.locator('[data-mdo-column]').count()==3, 'All must always show three columns'
    project=page.locator('.mdo-tasks-card[data-source="project"]').first
    independent=page.locator('.mdo-tasks-card[data-source="standalone"]').first
    project.locator('[data-stage-choice]').select_option('done')
    page.wait_for_function('window.__calls.length===1')
    independent.locator('[data-stage-choice]').select_option('done')
    page.wait_for_function('window.__calls.length===2')
    assert [c['source'] for c in page.evaluate('window.__calls')]==['project','standalone'], 'All routes POST by source'
    assert int(page.locator('[data-mdo-column="done"] > header > span').inner_text())==2, 'mixed done column increments instantly'
    # Exercise actual dragstart + drop event handlers, not only the select control.
    page.evaluate("""() => {
      const card=document.querySelector('[data-mdo-column="done"] .mdo-tasks-card[data-source="project"]');
      const destination=document.querySelector('[data-mdo-column="todo"]');
      const transfer=new DataTransfer();
      card.dispatchEvent(new DragEvent('dragstart',{bubbles:true,dataTransfer:transfer}));
      destination.dispatchEvent(new DragEvent('drop',{bubbles:true,dataTransfer:transfer}));
    }""")
    page.wait_for_function('window.__calls.length===3')
    page.wait_for_function("""() => !!document.querySelector('[data-mdo-column="todo"] .mdo-tasks-card[data-source="project"]')""")
    assert page.evaluate('window.__calls[2].source')=='project', 'All drag-and-drop routes ProjectTask'
    page.evaluate("""() => {
      const card=document.querySelector('[data-mdo-column="done"] .mdo-tasks-card[data-source="standalone"]');
      const destination=document.querySelector('[data-mdo-column="doing"]');
      const transfer=new DataTransfer();
      card.dispatchEvent(new DragEvent('dragstart',{bubbles:true,dataTransfer:transfer}));
      destination.dispatchEvent(new DragEvent('drop',{bubbles:true,dataTransfer:transfer}));
    }""")
    page.wait_for_function('window.__calls.length===4')
    page.wait_for_function("""() => !!document.querySelector('[data-mdo-column="doing"] .mdo-tasks-card[data-source="standalone"]')""")
    assert page.evaluate('window.__calls[3].source')=='standalone', 'All drag-and-drop routes IndependentTask'
    assert int(page.locator('[data-mdo-column="done"] > header > span').inner_text())==0, 'mixed source header count recalculated'
    # Native ProjectState view exposes empty state columns and supports state moves.
    native=subprocess.check_output([php,str(ROOT/'tests/render_tasks.php'),json.dumps({
      'count':2,'query':{'view':'board','source':'project','group':'state'}})],text=True)
    page.set_content('<html><head><style>'+css+'</style></head><body>'+native+'</body></html>')
    page.evaluate("""() => { window.__calls=[];window.fetch=async (_url,opts)=>{
       const q=new URLSearchParams(opts.body);window.__calls.push(Object.fromEntries(q));
       return {ok:true,json:async()=>({ok:true,stage:q.get('stage'),version:'v'+window.__calls.length,token:'r'+window.__calls.length,percent_done:50})};
    }; }""")
    page.add_script_tag(content=script)
    assert page.locator('[data-mdo-column="state-4"]').count()==1, 'empty native status visible'
    native_card=page.locator('.mdo-tasks-card[data-source="project"]').first
    old=native_card.locator('xpath=ancestor::section[@data-mdo-column]').get_attribute('data-mdo-column')
    native_card.locator('[data-stage-choice]').select_option('state-4')
    page.wait_for_function('window.__calls.length===1')
    assert page.locator('[data-mdo-column="state-4"] > header > span').inner_text()=='1', 'native state count updates'
    assert page.locator(f'[data-mdo-column="{old}"] > header > span').inner_text()=='0', 'source native count updates'
    browser.close()
print('Task UX repeated Kanban and drag ghost tests PASSED (Chromium fixture)')
