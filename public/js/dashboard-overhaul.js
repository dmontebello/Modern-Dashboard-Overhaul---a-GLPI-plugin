/**
 * Modern Dashboard Overhaul 0.7.0 - Davide Montebello - MIT.
 * UI only. No fetch/XHR, cookies, storage, polling or prototype overrides.
 * GLPI 11 bridge: .dashboard_select + jQuery change, .night-mode/.theme-dark.
 * Native dashboard permissions, '+', edit forms, grid sizes and data stay native.
 */
(() => {
    'use strict';
    if (window.ModernDashboardOverhaul) return;
    const VERSION = window.MdoBuild?.version || 'unavailable';
    const controllers = new Map();
    const SELF = '[data-mdo-owned]';
    const isItalian = (document.documentElement.lang || navigator.language || '').toLowerCase().startsWith('it');
    const text = isItalian
        ? {dashboards: 'Dashboard disponibili', scroll: 'Scorri orizzontalmente per altre dashboard', theme: 'Alterna tema chiaro/scuro della dashboard'}
        : {dashboards: 'Available dashboards', scroll: 'Scroll horizontally for more dashboards', theme: 'Toggle dashboard light/dark mode'};
    let stopped = false;
    let discovery = null;
    let themeObserver = null;

    const feature = (dash, name) => getComputedStyle(dash).getPropertyValue(`--mdo-${name}-enabled`).trim() === '1';
    const ownRoot = (node, dash) => node && node.closest('.dashboard') === dash;
    function setClass(el, name, enabled) {
        if (el.classList.contains(name) !== enabled) el.classList.toggle(name, enabled);
    }
    function setStyle(el, name, value) {
        if (el.style.getPropertyValue(name) !== value) el.style.setProperty(name, value);
    }
    function globalDark() {
        if (document.documentElement.matches('[data-glpi-theme-dark="1"],[data-bs-theme="dark"],.theme-dark') ||
            document.body.matches('[data-glpi-theme-dark="1"],[data-bs-theme="dark"],.theme-dark')) return true;
        if (document.documentElement.matches('[data-glpi-theme-dark="0"],[data-bs-theme="light"]')) return false;
        const bodyColor = getComputedStyle(document.body).backgroundColor;
        if (bodyColor === 'transparent' || /rgba\([^)]*,\s*0\s*\)$/.test(bodyColor)) return false;
        const rgb = bodyColor.match(/^rgba?\((\d+)[, ]+(\d+)[, ]+(\d+)/);
        return !!rgb && (0.2126 * +rgb[1] + 0.7152 * +rgb[2] + 0.0722 * +rgb[3]) < 125;
    }
    function editing(dash) {
        return dash.matches('.edit-mode,.filter-mode,.editmode,.editing') ||
            !!dash.querySelector('.toolbar .edit-dashboard.active,.toolbar .filter-dashboard.active');
    }
    function visibleContent(root) {
        function visit(el, isRoot = false) {
            if (!isRoot) {
                if (el.hidden || el.matches('script,style,template,noscript,input[type="hidden"]')) return false;
                const style = getComputedStyle(el);
                if (style.display === 'none' || ['hidden','collapse'].includes(style.visibility)) return false;
                if (el.matches('select,input,textarea,button,a,[role="button"],[role="combobox"],img,svg,canvas,iframe')) return true;
                for (const pseudo of ['::before','::after']) {
                    const value = getComputedStyle(el, pseudo).content;
                    if (value && !['none','normal','""',"''"].includes(value)) return true;
                }
            }
            return [...el.childNodes].some(n => n.nodeType === Node.TEXT_NODE ? !!n.textContent.trim() :
                n.nodeType === Node.ELEMENT_NODE && visit(n));
        }
        return visit(root, true); // Ignore root display, possibly hidden by our class.
    }

    function attach(dash) {
        if (stopped || controllers.has(dash)) return;
        const flags = {
            restyle: feature(dash, 'restyle'), tabs: feature(dash, 'tabs'),
            compact: feature(dash, 'compact'), charts: feature(dash, 'chart')
        };
        let frame = 0;
        let numberTimer = 0;
        let filtersDirty = true;
        let filtersEditState = null;
        let select = null;
        let nav = null;
        let plus = null;
        let plusAttrs = null;
        let optionsSignature = '';
        let lastSelected = null;
        let refreshCount = 0;
        let chartUpdateCount = 0;
        const nativeTheme = dash.classList.contains('theme-dark');
        const nativeNight = dash.querySelector('.toolbar .night-mode');
        const initialNightActive = !!nativeNight?.classList.contains('active');
        const tracked = new Set();
        const chartStates = new WeakMap();
        const kpiStates = new WeakMap();
        const failedCharts = new WeakSet();
        const delayed = new Set();
        const delayedCharts = new WeakSet();
        const initialTheme = nativeTheme || initialNightActive || globalDark();
        dash.classList.add('mdo-ui');
        setClass(dash, 'mdo-restyle', flags.restyle);
        // Seed from the user's GLPI palette ONCE. Thereafter the native button's
        // theme-dark class is authoritative, even when the surrounding UI is dark.
        setClass(dash, 'theme-dark', initialTheme);
        if (nativeNight) setClass(nativeNight, 'active', initialTheme);

        const sizes = typeof ResizeObserver === 'function' ? new ResizeObserver(schedule) : null;
        sizes?.observe(dash);

        function schedule() {
            if (!frame && !stopped && dash.isConnected) frame = requestAnimationFrame(refresh);
        }
        function delayedRefresh() {
            // One retry batch per dashboard, not three timers per individual chart.
            if (delayed.size) return;
            [120, 500, 1400].forEach(delay => {
                const timer = setTimeout(() => { delayed.delete(timer); schedule(); }, delay);
                delayed.add(timer);
            });
        }
        function bridgeChange() { schedule(); }
        function plusKey(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                event.currentTarget.click();
            }
        }
        function releaseSelect() {
            if (select) {
                select.classList.remove('mdo-select-bridged');
                if (window.jQuery) window.jQuery(select).off('change.mdoOverhaul', bridgeChange);
                else select.removeEventListener('change', bridgeChange);
            }
            if (plus && plusAttrs) {
                plus.removeEventListener('keydown', plusKey);
                for (const [key,value] of Object.entries(plusAttrs)) {
                    if (value === null) plus.removeAttribute(key); else plus.setAttribute(key,value);
                }
            }
            nav?.remove();
            nav = null; select = null; plus = null; plusAttrs = null;
            optionsSignature = ''; lastSelected = null;
            dash.classList.remove('mdo-tabs-ready','mdo-toolbar-stacked');
            dash.style.removeProperty('--mdo-toolbar-reserve');
        }
        function chooseTab(value) {
            if (!select || select.disabled || editing(dash)) return;
            const option = [...select.options].find(o => o.value === value && !o.disabled && !o.hidden && !o.parentElement.disabled);
            if (!option || select.value === value) return;
            select.value = value;
            // GLPI registers a jQuery change handler. Trigger ONCE (not twice).
            if (window.jQuery) window.jQuery(select).trigger('change');
            else select.dispatchEvent(new Event('change', {bubbles: true}));
            schedule();
        }
        function navClick(event) {
            const button = event.target.closest('.mdo-dashboard-tab');
            if (button && button.parentElement === nav && !button.disabled) chooseTab(button.dataset.value);
        }
        function revealTab(button) {
            if (!nav || nav.clientWidth < 1) return;
            const item = button.getBoundingClientRect();
            const view = nav.getBoundingClientRect();
            if (item.left < view.left) nav.scrollLeft += item.left - view.left - 2;
            else if (item.right > view.right) nav.scrollLeft += item.right - view.right + 2;
        }
        function navKey(event) {
            const button = event.target.closest('.mdo-dashboard-tab');
            if (!button || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
            const buttons = [...nav.querySelectorAll('.mdo-dashboard-tab:not(:disabled)')];
            if (!buttons.length) return;
            const rtl = getComputedStyle(nav).direction === 'rtl';
            const step = (event.key === 'ArrowRight' ? 1 : -1) * (rtl ? -1 : 1);
            let index = buttons.indexOf(button);
            index = event.key === 'Home' ? 0 : event.key === 'End' ? buttons.length - 1 :
                (index + step + buttons.length) % buttons.length;
            event.preventDefault();
            buttons[index].focus();
            revealTab(buttons[index]);
            // Manual activation: Enter/Space use native button click.
        }
        function updateTabs() {
            if (!flags.tabs || dash.matches('.mini,.embed')) return;
            const source = dash.querySelector('.toolbar.left-toolbar select.dashboard_select');
            const holder = source?.closest('.change-dashboard');
            if (!source || !holder || !ownRoot(source, dash)) {
                if (select) releaseSelect();
                return;
            }
            if (source !== select || !nav?.isConnected) {
                releaseSelect();
                select = source;
                nav = document.createElement('div');
                nav.className = 'mdo-dashboard-tabs';
                nav.dataset.mdoOwned = 'tabs';
                nav.setAttribute('role','tablist');
                nav.setAttribute('aria-label',text.dashboards);
                nav.setAttribute('aria-orientation','horizontal');
                nav.title = text.scroll;
                nav.addEventListener('click', navClick);
                nav.addEventListener('keydown', navKey);
                holder.insertBefore(nav, holder.firstChild);
                // jQuery handles native changes too. Do not attach twice.
                if (window.jQuery) window.jQuery(select).on('change.mdoOverhaul', bridgeChange);
                else select.addEventListener('change', bridgeChange);
                plus = holder.querySelector('.add-dashboard');
                if (plus && !plus.matches('button,a,input')) {
                    plusAttrs = {role: plus.getAttribute('role'), tabindex: plus.getAttribute('tabindex')};
                    plus.setAttribute('role','button'); plus.setAttribute('tabindex','0');
                    plus.addEventListener('keydown', plusKey);
                }
            }
            const opts = [...select.options].filter(o => !o.hidden && o.value !== '').map(o => ({
                value:o.value, text:o.textContent.trim(), disabled: !!(o.disabled || o.parentElement.disabled)
            }));
            if (!opts.length) {
                select.classList.remove('mdo-select-bridged');
                nav.hidden = true;
                dash.classList.remove('mdo-tabs-ready');
                return;
            }
            nav.hidden = false;
            const signature = JSON.stringify(opts);
            if (signature !== optionsSignature) {
                const focusValue = nav.contains(document.activeElement) ? document.activeElement.dataset.value : null;
                const scrollLeft = nav.scrollLeft;
                const frag = document.createDocumentFragment();
                opts.forEach(o => {
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = 'mdo-dashboard-tab';
                    b.dataset.value = o.value;
                    b.setAttribute('role','tab'); b.title = o.text;
                    const panel = dash.querySelector('.grid-stack');
                    if (panel?.id) b.setAttribute('aria-controls',panel.id);
                    const span = document.createElement('span');
                    span.textContent = o.text; // Never HTML from a dashboard title.
                    b.append(span); frag.append(b);
                });
                nav.replaceChildren(frag);
                nav.scrollLeft = scrollLeft;
                optionsSignature = signature;
                if (focusValue !== null) [...nav.children].find(b => b.dataset.value === focusValue)?.focus();
            }
            [...nav.children].forEach((b,i) => {
                const selected = b.dataset.value === select.value;
                b.setAttribute('aria-selected',String(selected));
                b.tabIndex = selected ? 0 : -1;
                b.disabled = select.disabled || opts[i].disabled || editing(dash);
            });
            select.classList.add('mdo-select-bridged');
            dash.classList.add('mdo-tabs-ready');
            const right = dash.querySelector(':scope > .toolbar:not(.left-toolbar)');
            if (right) {
                const r = right.getBoundingClientRect();
                const box = dash.getBoundingClientRect();
                const reserve = Math.ceil(Math.max(r.width, box.right - r.left) + 14);
                setStyle(dash,'--mdo-toolbar-reserve',`${reserve}px`);
                const leftSpace = dash.classList.contains('fullscreen') ? 260 : 12;
                setClass(dash,'mdo-toolbar-stacked',box.width - reserve - leftSpace < 210);
                if (!tracked.has(right)) { tracked.add(right); sizes?.observe(right); }
            }
            if (select.value !== lastSelected) {
                lastSelected = select.value;
                const selected = [...nav.children].find(b => b.dataset.value === lastSelected);
                if (selected) revealTab(selected);
            }
        }
        function updateCards() {
            const cards = dash.querySelectorAll('.grid-stack-item-content > .card');
            for (const card of cards) {
                if (!ownRoot(card,dash)) continue;
                const kpi = flags.restyle && card.classList.contains('big-number');
                const chartCard = flags.charts && card.classList.contains('g-chart');
                // Listen for size changes only on cards that need dynamic work.
                if ((kpi || chartCard) && !tracked.has(card)) { tracked.add(card); sizes?.observe(card); }
                if (kpi) {
                    card.classList.add('mdo-native-kpi');
                    const width = card.clientWidth;
                    const height = card.clientHeight;
                    const tight = width < 150 || height < 95;
                    setClass(card,'mdo-kpi-tight',tight);
                    setClass(card,'mdo-kpi-short',height < 76);
                    // Bound the size by BOTH dimensions. No FitText monkey-patch,
                    // no edits to native values and no scrollable .content span.
                    const number = card.querySelector('.formatted-number');
                    if (number) {
                        const full = number.textContent.trim();
                        // Update the native tooltip even when the new value has the same width.
                        // It must not be skipped by the geometry fingerprint fast path.
                        if (number.title !== full) number.title = full;
                        const fingerprint = `${width}:${height}:${full.length}`;
                        if (kpiStates.get(card) === fingerprint) continue;
                        kpiStates.set(card, fingerprint);
                        const digits = Math.max(1,[...full].length);
                        const available = Math.max(24,width - (tight ? 20 : 32) - (tight ? 24 : 30) - (tight ? 7 : 10));
                        const desired = Math.min(52,Math.max(28,Math.min(width * 0.21,height * 0.34)));
                        const fontSize = Math.max(24,Math.min(desired,available / (Math.max(2,digits) * 0.62)));
                        setStyle(card,'--mdo-kpi-number-size',`${Math.round(fontSize)}px`);
                    }
                }
                if (chartCard) {
                    for (const chart of card.querySelectorAll('.chart')) {
                        if (!delayedCharts.has(chart)) { delayedCharts.add(chart); delayedRefresh(); }
                        updateChart(chart);
                    }
                }
            }
            for (const node of tracked) {
                if (!node.isConnected || !dash.contains(node)) { sizes?.unobserve(node); tracked.delete(node); }
            }
        }
        function updateChart(element) {
            // GLPI 11.0.9 uses ECharts despite retaining the historical .ct-chart class.
            // Reuse its instance. Never dispose/reinit, mutate data, replace series,
            // override palette colors, intercept clicks or send extra requests.
            const api = window.echarts;
            if (!api || typeof api.getInstanceByDom !== 'function' || element.clientWidth < 1) return;
            try {
                const chart = api.getInstanceByDom(element);
                if (!chart || chart.isDisposed?.()) return;
                // Round geometry to 32px bands: drag-resizing cannot repaint
                // the whole ECharts option on every single pixel.
                const widthBand = Math.round(element.clientWidth / 48);
                const heightBand = Math.round(element.clientHeight / 32);
                const signature = `${dash.classList.contains('theme-dark')}:${widthBand}:${heightBand}`;
                const state = chartStates.get(element);
                if (state && state.chart === chart && state.signature === signature) return;
                const css = getComputedStyle(dash);
                const ink = css.getPropertyValue('--mdo-ink').trim();
                const muted = css.getPropertyValue('--mdo-muted').trim();
                const surface = css.getPropertyValue('--mdo-surface').trim();
                const border = css.getPropertyValue('--mdo-border').trim();
                const option = chart.getOption();
                const item = element.closest('.grid-stack-item');
                let cardOptions = {};
                try { cardOptions = JSON.parse(item?.getAttribute('data-card-options') || '{}'); } catch (_) { /* Native metadata may be absent while loading. */ }
                const monthlyStatus = cardOptions.card_id === 'ticket_status' || /^ticket_status(?:_|$)/.test(item?.getAttribute('gs-id') || '');
                const match = (entry, patch) => entry && entry.id !== undefined ? {id:entry.id,...patch} : patch;
                const patch = {backgroundColor:'transparent', textStyle:{color:ink,fontFamily:'system-ui, sans-serif'}};
                if (Array.isArray(option.title) && option.title.length) {
                    patch.title = option.title.map(t => match(t,{textStyle:{color:ink,fontSize:Math.max(22,Math.min(34,Math.round(Math.min(element.clientWidth,element.clientHeight)*0.13))),fontWeight:700},subtextStyle:{color:muted}}));
                }
                // Native ECharts legend + Grid are kept: reserve one scrolling row
                // for horizontal legends on bar/line charts instead of wrapping
                // multiple legend rows over the plotted data.
                const cartesian = Array.isArray(option.series) &&
                    option.series.some(s => ['bar','line','scatter'].includes(s.type));
                const horizontalLegend = monthlyStatus && cartesian && Array.isArray(option.legend) &&
                    option.legend.some(l => l.orient !== 'vertical' && l.show !== false);
                if (Array.isArray(option.legend) && option.legend.length) {
                    patch.legend = option.legend.map(l => match(l,{
                        textStyle:{color:ink,fontSize:horizontalLegend ? 12 : 13},
                        pageTextStyle:{color:muted,fontSize:12},
                        ...(horizontalLegend && l.orient !== 'vertical' ? {
                            type:'scroll',orient:'horizontal',top:element.clientHeight < 110 ? 0 : 8,bottom:'auto',left:8,right:8,
                            itemWidth:12,itemHeight:12,itemGap:12,
                            pageIconSize:12,pageButtonItemGap:5,
                        } : {})
                    }));
                }
                if (horizontalLegend && Array.isArray(option.grid) && option.grid.length) {
                    const reserve = Math.min(56, Math.max(24, element.clientHeight - 56), 48);
                    patch.grid = option.grid.map(g => match(g,{top:reserve,
                        bottom:Math.max(20,Math.min(Number.parseFloat(g.bottom) || 28,element.clientHeight * 0.25)),containLabel:true}));
                }
                for (const key of ['xAxis','yAxis']) {
                    if (Array.isArray(option[key]) && option[key].length) {
                        patch[key] = option[key].map(t => match(t,{
                            axisLabel:{color:muted,fontSize:12}, nameTextStyle:{color:muted,fontSize:13},
                            axisLine:{lineStyle:{color:border}}, splitLine:{lineStyle:{color:border,opacity:0.55}}
                        }));
                    }
                }
                if (Array.isArray(option.tooltip) && option.tooltip.length) {
                    patch.tooltip = option.tooltip.map(t => match(t,{backgroundColor:surface,borderColor:border,textStyle:{color:ink,fontSize:13}}));
                }
                if (Array.isArray(option.series) && option.series.length) {
                    patch.series = option.series.map(t => match(t,{label:{color:ink,fontSize:13}}));
                }
                chartStates.set(element,{chart,signature}); // Before setOption, which may change DOM.
                chart.setOption(patch,{notMerge:false,lazyUpdate:true,silent:true});
                chartUpdateCount++;
            } catch (error) {
                // Unknown chart engines/options fail soft; the native chart stays in place.
                if (!failedCharts.has(element)) {
                    failedCharts.add(element);
                    console.debug('[Modern Dashboard Overhaul] Optional chart styling skipped.',error);
                }
            }
        }
        function refresh() {
            frame = 0;
            if (stopped || !dash.isConnected) return;
            refreshCount++;
            if (nativeNight?.isConnected) setClass(nativeNight,'active',dash.classList.contains('theme-dark'));
            if (flags.compact) {
                const nowEditing = editing(dash);
                if (filtersDirty || filtersEditState !== nowEditing) {
                    dash.querySelectorAll('.filters_toolbar').forEach(toolbar => {
                        if (!ownRoot(toolbar,dash)) return;
                        setClass(toolbar,'mdo-empty-filters', !nowEditing && !visibleContent(toolbar));
                    });
                    filtersDirty = false;
                    filtersEditState = nowEditing;
                }
            }
            updateTabs();
            updateCards();
        }
        const observer = new MutationObserver(records => {
            for (const record of records) {
                const el = record.target.nodeType === Node.ELEMENT_NODE ? record.target : record.target.parentElement;
                if (!el || el.closest(SELF)) continue;
                if (el.closest('.filters_toolbar') || el === dash) filtersDirty = true;
                if (flags.restyle && ['childList','characterData'].includes(record.type) && el.closest('.big-number .formatted-number')) {
                    // Native 800ms number animations may update text every frame.
                    // Measure just once after they settle, not 60 times a second.
                    clearTimeout(numberTimer);
                    numberTimer = setTimeout(() => { numberTimer = 0; schedule(); }, 120);
                    continue;
                }
                if (record.type === 'attributes' && record.attributeName === 'class') {
                    const clean = s => (s || '').split(/\s+/).filter(c => c && !c.startsWith('mdo-')).sort().join(' ');
                    if (clean(record.oldValue) === clean(el.className)) continue;
                }
                if (el === dash || el.closest('.toolbar,.filters_toolbar') ||
                    (record.type === 'childList' && (el.matches('.grid-stack-item-content,.grid-stack,.chart') ||
                    [...record.addedNodes].some(n => n.nodeType === Node.ELEMENT_NODE &&
                        (n.matches('.card,.toolbar,.filters_toolbar') || n.querySelector('.card,.toolbar,.filters_toolbar')))))) {
                    schedule(); break;
                }
            }
        });
        observer.observe(dash,{subtree:true,childList:true,characterData:true,attributes:true,attributeOldValue:true,
            attributeFilter:['class','hidden','disabled','label']});
        function toolbarClick(event) { if (event.target.closest('.toolbar,.filters_toolbar')) schedule(); }
        dash.addEventListener('click',toolbarClick);
        controllers.set(dash,{
            schedule,
            syncTheme() {
                setClass(dash,'theme-dark',globalDark());
                schedule();
            },
            debug() { return {id:dash.id,theme:dash.classList.contains('theme-dark')?'dark':'light',flags,tabCount:nav?.children.length||0,refreshCount,chartUpdateCount}; },
            destroy() {
                observer.disconnect(); sizes?.disconnect();
                dash.removeEventListener('click',toolbarClick);
                delayed.forEach(clearTimeout); delayed.clear();
                if (frame) cancelAnimationFrame(frame);
                if (numberTimer) clearTimeout(numberTimer);
                releaseSelect();
                dash.classList.remove('mdo-ui','mdo-restyle');
                dash.querySelectorAll('.mdo-empty-filters').forEach(e=>e.classList.remove('mdo-empty-filters'));
                dash.querySelectorAll('.mdo-native-kpi').forEach(e=>{
                    e.classList.remove('mdo-native-kpi','mdo-kpi-tight','mdo-kpi-short');
                    e.style.removeProperty('--mdo-kpi-number-size');
                });
                setClass(dash,'theme-dark',nativeTheme);
                if (nativeNight?.isConnected) setClass(nativeNight,'active',initialNightActive);
            }
        });
        schedule();
    }
    function start() {
        const initialDashboards = document.querySelectorAll('.dashboard');
        initialDashboards.forEach(attach);
        // Do not monitor the entire GLPI document during unrelated ticket, asset
        // or administration pages. Central/dashboard views still support AJAX.
        const dashboardPage = /(?:^|\/)(?:central|dashboard)(?:\.|\/|$)/i.test(location.pathname);
        if (!initialDashboards.length && !dashboardPage) return;
        discovery = new MutationObserver(records => {
            let removed = false;
            for (const record of records) {
                removed ||= record.removedNodes.length > 0;
                for (const node of record.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE || node.closest(SELF)) continue;
                    if (node.matches('.dashboard')) attach(node);
                    if (!node.closest('.dashboard')) node.querySelectorAll('.dashboard').forEach(attach);
                }
            }
            if (removed) controllers.forEach((controller,dash) => {
                if (!dash.isConnected) { controller.destroy(); controllers.delete(dash); }
            });
        });
        discovery.observe(document.body,{childList:true,subtree:true});
        themeObserver = new MutationObserver(records => {
            // A sidebar/collapse class change must not reset a dashboard's toggle.
            const relevant = records.some(record => record.attributeName !== 'class' ||
                (record.oldValue || '').split(/\s+/).includes('theme-dark') !== record.target.classList.contains('theme-dark'));
            if (relevant) controllers.forEach(c=>c.syncTheme());
        });
        for (const root of [document.documentElement,document.body]) {
            themeObserver.observe(root,{attributes:true,attributeOldValue:true,attributeFilter:['data-glpi-theme-dark','data-bs-theme','class']});
        }
    }
    window.ModernDashboardOverhaul = Object.freeze({
        version:VERSION,
        refresh() { controllers.forEach(c=>c.schedule()); },
        debugInfo() { return [...controllers.values()].map(c=>c.debug()); },
        restoreNativeNavigation() {
            stopped = true;
            discovery?.disconnect(); themeObserver?.disconnect();
            controllers.forEach(c=>c.destroy()); controllers.clear();
            // Temporary browser recovery. Reload re-enables configured modules.
            // Chart presentation is fully reset by reloading, not by replaying stale data.
        }
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',start,{once:true});
    else start();
})();
