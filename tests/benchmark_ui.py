#!/usr/bin/env python3
"""DOM/observer stress comparison 0.6.0 vs 0.7.0. Use pre-0.7 script as optional arg.
This measures scheduled JS retry timers, not total GLPI query/network performance.
"""
import argparse
import json
import re
from pathlib import Path
from playwright.sync_api import sync_playwright

p=argparse.ArgumentParser()
p.add_argument('--previous-js',type=Path,required=True)
p.add_argument('--output',type=Path,required=True)
args=p.parse_args()
root=Path(__file__).resolve().parents[1]
html=(root/'tests/ui_fixture.html').read_text('utf-8')
html=re.sub(r'<link rel="stylesheet"[^>]+>', '', html)
html=re.sub(r'<script src="[^"]+"></script>', '', html)
css=[(root/'public/css'/n).read_text('utf-8') for n in ['dashboard-theme.css','dashboard-overhaul.css','dashboard-tabs.css','compact-filters.css','chart-text.css','security-watch.css']]
with sync_playwright() as pw:
 b=pw.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-gpu'])
 results=[]
 for version, source in [('0.6.0',args.previous_js),('0.7.0',root/'public/js/dashboard-overhaul.js')]:
  pg=b.new_page(viewport={'width':1440,'height':1050})
  pg.set_content(html)
  for style in css: pg.add_style_tag(content=style)
  pg.evaluate('''()=>{const grid=document.querySelector('.grid-stack');const fragment=document.createDocumentFragment();
    for(let i=0;i<60;i++){let wrapper=document.createElement('div');wrapper.className='grid-stack-item';
      wrapper.style.cssText='grid-column:span 2;grid-row:span 2;';
      wrapper.innerHTML='<div class="grid-stack-item-content"><div class="card g-chart"><div class="chart"></div></div></div>';
      fragment.append(wrapper);}grid.append(fragment);
    window.__timers={120:0,500:0,1400:0};const old=window.setTimeout;
    window.setTimeout=function(fn,time,...args){if(Object.hasOwn(window.__timers, String(time)))window.__timers[time]++;return old.call(this,fn,time,...args);};}''')
  pg.add_script_tag(content=source.read_text('utf-8'))
  pg.wait_for_timeout(250)
  stats=pg.evaluate('''()=>({timers:window.__timers, count:ModernDashboardOverhaul.debugInfo()[0].tabCount,
      refreshes:ModernDashboardOverhaul.debugInfo()[0].refreshCount})''')
  stats['version']=version
  stats['delayed_total']=sum(stats['timers'].values())
  results.append(stats)
  pg.close()
 b.close()
assert results[1]['delayed_total'] < results[0]['delayed_total'], results
args.output.parent.mkdir(parents=True, exist_ok=True)
args.output.write_text(json.dumps(results,indent=2)+'\n',encoding='utf-8')
print('[OK] retry timer scheduling benchmark (61 charts, GLPI-like fixture)')
for r in results:print(f"  {r['version']}: {r['delayed_total']} delayed chart timers, {r['refreshes']} refreshes")
print(f"  reduction: {(1-results[1]['delayed_total']/results[0]['delayed_total'])*100:.1f}% in this stress case")
