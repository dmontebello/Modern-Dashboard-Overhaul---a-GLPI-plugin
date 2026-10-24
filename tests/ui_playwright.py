#!/usr/bin/env python3
"""Isolated UI regression tests using locally installed Python Playwright + Chromium.
Not a GLPI integration test: tests dashboard DOM/class contract against fixture.
Usage: python tests/ui_playwright.py [--screenshots <folder>]
"""
import argparse
import json
import os
import re
import shutil
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright

TESTS = 0

def ok(cond, description):
    global TESTS
    TESTS += 1
    if not cond:
        raise AssertionError(f"Test {TESTS}: {description}")
    print(f'[OK] {TESTS:02d} {description}')

def run():
    p = argparse.ArgumentParser()
    p.add_argument('--screenshots', type=Path)
    args = p.parse_args()
    fixture = Path(__file__).with_name('ui_fixture.html').resolve()
    if args.screenshots: args.screenshots.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as engine:
        executable = os.environ.get('MDO_CHROMIUM_EXECUTABLE') or shutil.which('chromium') or shutil.which('chromium-browser')
        browser = engine.chromium.launch(headless=True, executable_path=executable, args=['--no-sandbox', '--disable-gpu'])
        for viewport in [(1440, 1050), (1024, 900), (780, 900), (390, 880)]:
            w, h = viewport
            page = browser.new_page(viewport={'width': w, 'height': h}, device_scale_factor=1)
            errors=[]
            page.on('pageerror',lambda e: errors.append(str(e)))
            markup = re.sub(r'<link rel=\"stylesheet\"[^>]+>', '', fixture.read_text('utf-8'))
            markup = re.sub(r'<script src=\"[^\"]+\"></script>', '', markup)
            page.set_content(markup)
            for f in ['dashboard-theme.css','dashboard-overhaul.css','dashboard-tabs.css','compact-filters.css','chart-text.css','security-watch.css']:
                page.add_style_tag(content=(fixture.parent.parent/'public/css'/f).read_text('utf-8'))
            page.add_script_tag(content=(fixture.parent.parent/'public/js/dashboard-overhaul.js').read_text('utf-8'))
            page.wait_for_function('window.ModernDashboardOverhaul?.debugInfo()?.length === 1')
            page.wait_for_timeout(520)
            debug=page.evaluate('ModernDashboardOverhaul.debugInfo()[0]')
            ok(debug['flags']['restyle'] and debug['flags']['tabs'] and debug['flags']['charts'],f'{w}px: flag moduli attivi')
            ok(debug['tabCount']==42,f'{w}px: 42 tab dashboard')
            ok(page.locator('.dashboard.mdo-tabs-ready').count()==1,f'{w}px: navigazione attiva')
            ok(page.locator('.add-dashboard').count()==1,f'{w}px: unico pulsante + nativo')
            ok(page.locator('.mdo-dashboard-tabs').evaluate('(n) => n.scrollWidth >= n.clientWidth'),f'{w}px: navigazione orizzontale disponibile')
            ok(page.locator('.dashboard .filters_toolbar.mdo-empty-filters').count()==1,f'{w}px: filtro vuoto compattato')
            ok(page.locator('.big-number.mdo-native-kpi').count()==10,f'{w}px: widget contatori adattati')
            dims=page.locator('.big-number').evaluate_all('''nodes=>nodes.map(n=>{
              const num=n.querySelector('.formatted-number'), label=n.querySelector(':scope > .label');
              const a=n.getBoundingClientRect(), b=num.getBoundingClientRect(), c=label.getBoundingClientRect();
              const middle=(b.top+c.bottom)/2, ideal=(a.top+a.bottom)/2;
              return {label:label.textContent, cardH:a.height, numberFont:parseFloat(getComputedStyle(num).fontSize),
                      labelFont:parseFloat(getComputedStyle(label).fontSize), offset:Math.abs(middle-ideal),
                      overflowY:getComputedStyle(num).overflowY, scrollHeight:num.scrollHeight, clientHeight:num.clientHeight,
                      color:getComputedStyle(num).color};
            })''')
            ok(all(d['numberFont']>=24 for d in dims),f'{w}px: numeri sempre >=24px')
            ok(all(d['labelFont']>=13 for d in dims),f'{w}px: etichette sempre >=13px')
            ok(all(d['overflowY']=='hidden' for d in dims),f'{w}px: nessuna scrollbar interna ai numeri')
            ok(all(d['offset'] < max(25, d['cardH']*.29) for d in dims),f'{w}px: centratura verticale contatori')
            ok(len(errors)==0,f'{w}px: nessuna eccezione JavaScript ({errors})')
            toolbar_geometry=page.locator('.dashboard').evaluate('''e=>{const a=e.querySelector('.toolbar.left-toolbar').getBoundingClientRect(),b=e.querySelector(':scope > .toolbar:not(.left-toolbar)').getBoundingClientRect(),p=e.querySelector('.add-dashboard').getBoundingClientRect(), d=e.getBoundingClientRect();return {overlap:Math.max(0,Math.min(a.right,b.right)-Math.max(a.left,b.left))*Math.max(0,Math.min(a.bottom,b.bottom)-Math.max(a.top,b.top)), plusWithin:p.left>=d.left-1 && p.right<=d.right+1};}''')
            ok(toolbar_geometry['overlap']==0,f'{w}px: toolbar sinistra/destra non sovrapposte')
            ok(toolbar_geometry['plusWithin'],f'{w}px: pulsante + sempre dentro dashboard')
            bar_margin=page.locator('.dashboard').evaluate('''e=>e.querySelector('.grid-stack-item').getBoundingClientRect().top - e.querySelector('.mdo-dashboard-tabs').getBoundingClientRect().bottom''')
            ok(bar_margin>=5,f'{w}px: tab non coprono i widget ({bar_margin:.1f}px di spazio)')
            if w in (780,390) and args.screenshots: page.screenshot(path=str(args.screenshots/f'dashboard-070-{w}px.png'),full_page=True)
            if w==1440:
                ok(page.locator('.dashboard.theme-dark').count()==1,'desktop: dark mode iniziale')
                page.locator('.toolbar .night-mode').click()
                page.wait_for_timeout(70)
                ok(page.locator('.dashboard:not(.theme-dark)').count()==1,'desktop: toggle light funzionante')
                ok(page.locator('.card.asw-feed').evaluate('(c)=>getComputedStyle(c).color') == 'rgb(22, 40, 63)','desktop: colori RSS light coerenti')
                if args.screenshots: page.screenshot(path=str(args.screenshots/'dashboard-070-light.png'),full_page=True)
                page.locator('.toolbar .night-mode').click()
                page.wait_for_timeout(70)
                ok(page.locator('.dashboard.theme-dark').count()==1,'desktop: ritorno a dark')
                ok(page.locator('.card.asw-feed').evaluate('(c)=>getComputedStyle(c).color') == 'rgb(242, 246, 252)','desktop: colori RSS dark coerenti')
                if args.screenshots: page.screenshot(path=str(args.screenshots/'dashboard-070-dark.png'),full_page=True)
                nav=page.locator('.mdo-dashboard-tabs')
                ok(nav.evaluate('n=>n.scrollWidth > n.clientWidth'), 'desktop: 42 tab producono overflow')
                page.locator('.mdo-dashboard-tab').nth(24).evaluate('n=>n.click()')
                page.wait_for_timeout(70)
                ok(page.locator('#selection-output').inner_text()=='dash24','desktop: click inoltrato al selettore GLPI')
                ok(page.locator('.mdo-dashboard-tab[aria-selected=true]').count()==1,'desktop: un solo tab selezionato')
                page.locator('.add-dashboard').click()
                page.wait_for_timeout(150)
                ok(page.locator('.mdo-dashboard-tab').count()==43,'desktop: + nativo aggiunge dashboard')
                ok(page.locator('#selection-output').inner_text()=='dash42','desktop: + seleziona la nuova dashboard')
                page.locator('.edit-dashboard').click()
                page.wait_for_timeout(80)
                ok(page.locator('.dashboard.edit-mode').count()==1,'desktop: edit mode nativo preservato')
                page.locator('.edit-dashboard').click()
                page.wait_for_timeout(80)
                ok(page.locator('.dashboard.edit-mode').count()==0,'desktop: uscita edit mode')
                page.wait_for_timeout(1700)
                ok(page.locator('.g-chart .chart').first.evaluate('(e)=>e.__mock?.hits > 0'),'desktop: grafici ECharts inizializzati asincronamente stilizzati')
                ok(page.locator('.g-chart .chart').first.evaluate('(e)=>e.__mock?.lastPatch.series[0].data === undefined'),'desktop: serie/dati ECharts invariati')
                ok(page.locator('.g-chart .chart').nth(1).evaluate('(e)=>e.__mock?.hits > 0'),'desktop: ECharts native bar chart styled asynchronously')
                ok(page.locator('.g-chart .chart').nth(1).evaluate('''e=>e.__mock.lastPatch.legend[0].type === "scroll" &&
                    e.__mock.lastPatch.grid[0].top >= 48 &&
                    e.__mock.lastPatch.series.every(s=>s.data===undefined)'''),
                    'desktop: single-row scrollable legend, reserved plotting space, native series intact')
                tooltop=page.locator('.dashboard').evaluate('''e=>{
                  const p=e.getBoundingClientRect(),tb=e.querySelector(':scope > .toolbar:not(.left-toolbar)').getBoundingClientRect();
                  return tb.top - p.top; }''')
                ok(tooltop >= 9,'desktop: dashboard actions spaced away from upper border')
                before=page.evaluate('ModernDashboardOverhaul.debugInfo()[0].refreshCount')
                page.evaluate('''()=>{const e=document.querySelector('.big-number .formatted-number .number');for(let i=0;i<250;i++)e.textContent=String(i); }''')
                page.wait_for_timeout(440)
                after=page.evaluate('ModernDashboardOverhaul.debugInfo()[0].refreshCount')
                ok(after-before<=5,f'desktop: ricalcoli DOM coalescenti ({after-before} refresh per 250 mutazioni)')
                # A same-length counter update must refresh its accessible tooltip.
                page.evaluate('''()=>{const n=document.querySelector('.big-number .formatted-number');n.querySelector('.number').textContent='7';}''')
                page.wait_for_timeout(250)
                ok(page.locator('.big-number .formatted-number').first.get_attribute('title')=='7',
                   'desktop: tooltip aggiornata anche per nuovi valori della stessa lunghezza')
                page.evaluate('ModernDashboardOverhaul.restoreNativeNavigation()')
                ok(page.locator('.mdo-dashboard-tabs').count()==0,'desktop: rollback JS ripristina dropdown')
                ok(page.locator('.dashboard_select.mdo-select-bridged').count()==0,'desktop: select GLPI riabilitato')
            page.close()
        # A non-dashboard page must not allocate body-wide observers.
        page=browser.new_page()
        page.goto('about:blank')
        probe=page.evaluate('''() => {const orig=window.MutationObserver;let count=0;
          window.MutationObserver=class extends orig{constructor(fn){super(fn);count++;}};
          return true; }''')
        page.add_script_tag(content=(fixture.parent.parent/'public/js/dashboard-overhaul.js').read_text('utf-8'))
        page.wait_for_timeout(70)
        ok(page.evaluate('ModernDashboardOverhaul.debugInfo().length')==0,'non-dashboard: nessun controller attivo')
        ok(page.evaluate('ModernDashboardOverhaul.version')=='0.7.0','versione JS coerente')
        page.close()
        # GLPI 11 native mini dashboard has a -45px bottom margin. Simulate the
        # native search wrapper and a tall 2-row KPI grid (no GLPI server).
        mini=browser.new_page(viewport={'width': 1200, 'height': 700})
        mini.route('http://mdo.test/**', lambda route: route.fulfill(status=200,content_type='text/html',body='''<!doctype html><html><head><style>
            body{margin:20px;background:#182433}
            .dashboard-card{border:2px solid #56697f;width:970px;border-radius:10px;background:#1d2c40}
            .card-body{padding:8px}
            .dashboard.mini{position:relative;margin-bottom:-45px!important;box-sizing:content-box}
            .grid-stack{position:relative;height:70px;width:100%}
            .grid-stack-item{position:absolute;left:0;top:0;height:140px;width:90%}
            .grid-stack-item-content{height:100%}
            .grid-stack-item-content>.card{height:100%;background:#eaf0f7}
            </style></head><body><div class="dashboard-card"><div class="card-body">
            <div class="dashboard mini"><div class="grid-stack">
            <div class="grid-stack-item"><div class="grid-stack-item-content"><div class="card big-number">6 chiamate</div></div></div>
            </div></div></div></div></body></html>'''))
        mini.goto('http://mdo.test/front/ticket.php')
        mini.add_style_tag(content=(fixture.parent.parent/'public/css/ticket-mini-dashboard.css').read_text('utf-8'))
        mini.add_script_tag(content=(fixture.parent.parent/'public/js/ticket-mini-dashboard.js').read_text('utf-8'))
        mini.wait_for_function('''() => {
          const grid=document.querySelector('.grid-stack');
          return grid && grid.getBoundingClientRect().height >= 140;
        }''')
        bounds=mini.locator('.dashboard-card').evaluate('''e=>{
          const outer=e.getBoundingClientRect(),tile=e.querySelector('.grid-stack-item .card').getBoundingClientRect(),
                dash=e.querySelector('.dashboard.mini');
          return {within:tile.bottom<=outer.bottom-2,
            margin:getComputedStyle(dash).marginBottom};
        }''')
        ok(bounds['within'] and bounds['margin']=='0px',
           'mini Ticket: grid and KPI contained in border; GLPI negative margin neutralized')
        mini.close()
        browser.close()
    print(f'\n{TESTS} UI tests PASSED (Chromium headless, isolated GLPI-like DOM).')

if __name__=='__main__':
    try:run()
    except Exception as e:
        print('[ERRORE]',repr(e),file=sys.stderr)
        sys.exit(1)
