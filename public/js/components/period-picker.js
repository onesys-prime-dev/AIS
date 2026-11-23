/**
 * Period picker: one button that shows the chosen period and opens a panel to change it.
 *
 *   Cutoff  - the payroll cutoffs (Current, Previous, earlier). The default.
 *   Month   - a whole calendar month (the last 12).
 *   Custom  - any From/To dates, plus quick ranges (Last 30 days, This year).
 *
 * A period is one string ("kind"):
 *   cutoff:2026-10-11|2026-10-25   month:2026-10   custom:2026-10-03|2026-10-17   30   year
 *
 *   const picker = PeriodPicker.create({
 *       mount: element,               // the button is rendered inside this element
 *       cutoffs: [{start, end}, ...], // newest first (the first is the current cutoff)
 *       value: 'cutoff:...',
 *       onChange: (kind) => {},       // fired when the person picks something
 *   });
 *   picker.setValue(kind) / picker.getValue() / picker.setCutoffs(list)
 *
 * PeriodPicker.range(kind) -> [startISO, endISO]; PeriodPicker.describe(kind, cutoffs) -> {label, dates}.
 */
(function (global) {
    'use strict';

    const pad = (n) => String(n).padStart(2, '0');
    const iso = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const parse = (s) => new Date(s + 'T00:00:00');
    const short = (s, withYear) => parse(s).toLocaleDateString(undefined, withYear ? { month: 'short', day: 'numeric', year: 'numeric' } : { month: 'short', day: 'numeric' });
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cutoffKey = (c) => 'cutoff:' + c.start + '|' + c.end;
    const monthKey = (d) => 'month:' + d.getFullYear() + '-' + pad(d.getMonth() + 1);

    function range(kind) {
        kind = String(kind);
        if (kind.indexOf('cutoff:') === 0 || kind.indexOf('custom:') === 0) return kind.slice(7).split('|');
        if (kind.indexOf('month:') === 0) {
            const p = kind.slice(6).split('-');
            return [iso(new Date(+p[0], +p[1] - 1, 1)), iso(new Date(+p[0], +p[1], 0))];
        }
        const now = new Date();
        if (kind === '30') { const s = new Date(); s.setDate(now.getDate() - 29); return [iso(s), iso(now)]; }
        if (kind === 'year') return [now.getFullYear() + '-01-01', iso(now)];
        return [iso(new Date(now.getFullYear(), now.getMonth(), 1)), iso(now)];   // legacy "month" = this month so far
    }

    function monthName(kind) {
        const p = String(kind).slice(6).split('-');
        return new Date(+p[0], +p[1] - 1, 1).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    }

    function describe(kind, cutoffs) {
        kind = String(kind);
        const r = range(kind);
        const dates = short(r[0], false) + ' – ' + short(r[1], true);
        let label;
        if (kind.indexOf('cutoff:') === 0) {
            const i = (cutoffs || []).findIndex((c) => cutoffKey(c) === kind);
            label = i === 0 ? 'Current cutoff' : i === 1 ? 'Previous cutoff' : 'Payroll cutoff';
        } else if (kind.indexOf('month:') === 0) label = monthName(kind);
        else if (kind.indexOf('custom:') === 0) label = 'Custom range';
        else if (kind === '30') label = 'Last 30 days';
        else if (kind === 'year') label = 'This year';
        else label = 'This month';
        return { label: label, dates: dates };
    }

    function tabOf(kind) {
        kind = String(kind);
        if (kind.indexOf('cutoff:') === 0) return 'cutoff';
        if (kind.indexOf('month:') === 0) return 'month';
        return 'custom';
    }

    function injectStyle() {
        if (document.getElementById('pp-style')) return;
        const css = `
.pp-trigger{display:inline-flex;align-items:center;gap:10px;border:1px solid #d1d5db;background:#fff;color:#111827;border-radius:10px;padding:7px 12px;cursor:pointer;font:inherit;text-align:left;min-width:0;max-width:100%}
.pp-trigger:hover{background:#f9fafb}
.pp-trigger[aria-expanded="true"]{border-color:#6b7280;box-shadow:0 0 0 3px rgba(107,114,128,.18)}
.pp-trigger svg{flex:none;color:#6b7280}
.pp-label{display:flex;flex-direction:column;line-height:1.2;min-width:0}
.pp-label b{font-size:13px;font-weight:600;white-space:nowrap}
.pp-label small{font-size:11px;color:#6b7280;white-space:nowrap}
.pp-panel{position:fixed;z-index:100001;width:340px;max-width:calc(100vw - 16px);background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,.2);font-size:13px;color:#111827;display:none}
.pp-panel.pp-open{display:block}
.pp-tabs{display:flex;gap:4px;padding:8px;background:#f3f4f6;border-radius:14px 14px 0 0;border-bottom:1px solid #e5e7eb}
.pp-tab{flex:1;border:0;background:transparent;border-radius:8px;padding:7px 8px;font:inherit;font-weight:600;color:#6b7280;cursor:pointer}
.pp-tab:hover{color:#111827}
.pp-tab.pp-on{background:#fff;color:#111827;box-shadow:0 1px 3px rgba(0,0,0,.12)}
.pp-body{padding:10px;max-height:340px;overflow-y:auto}
.pp-item{display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;border:1px solid transparent;background:transparent;border-radius:10px;padding:9px 10px;font:inherit;cursor:pointer;text-align:left}
.pp-item:hover{background:#f3f4f6}
.pp-item.pp-sel{background:#f3f4f6;border-color:#d1d5db}
.pp-item .pp-name{font-weight:600}
.pp-item .pp-dates{font-size:12px;color:#6b7280;white-space:nowrap}
.pp-tag{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;border-radius:999px;padding:1px 7px;background:#1f2937;color:#fff;margin-left:6px}
.pp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.pp-month{border:1px solid #e5e7eb;background:#fff;border-radius:10px;padding:9px 4px;font:inherit;cursor:pointer;text-align:center;line-height:1.2}
.pp-month small{display:block;font-size:11px;color:#9ca3af}
.pp-month:hover{background:#f3f4f6}
.pp-month.pp-sel{background:#1f2937;border-color:#1f2937;color:#fff}
.pp-month.pp-sel small{color:#d1d5db}
.pp-note{font-size:11px;color:#9ca3af;margin:8px 2px 0}
.pp-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.pp-row label{font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.03em}
.pp-row input{display:block;width:100%;margin-top:4px;border:1px solid #d1d5db;border-radius:8px;padding:8px;font:inherit;color:#111827;background:#fff}
.pp-chips{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.pp-chip{border:1px solid #d1d5db;background:#fff;border-radius:999px;padding:5px 12px;font:inherit;font-size:12px;cursor:pointer}
.pp-chip:hover{background:#f3f4f6}
.pp-chip.pp-sel{background:#1f2937;border-color:#1f2937;color:#fff}
.pp-apply{width:100%;border:0;border-radius:8px;background:#1f2937;color:#fff;font:inherit;font-weight:600;padding:9px 12px;cursor:pointer}
.pp-apply:hover{background:#111827}
.pp-err{color:#b45309;font-size:12px;margin:0 0 8px;min-height:0}
.pp-empty{padding:14px 6px;text-align:center;color:#9ca3af}`;
        const el = document.createElement('style');
        el.id = 'pp-style';
        el.textContent = css;
        document.head.appendChild(el);
    }

    function create(opts) {
        injectStyle();
        let cutoffs = (opts.cutoffs || []).slice();
        let value = opts.value || (cutoffs.length ? cutoffKey(cutoffs[0]) : monthKey(new Date()));
        let tab = tabOf(value);
        let customError = '';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'pp-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        opts.mount.appendChild(trigger);

        const panel = document.createElement('div');
        panel.className = 'pp-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Choose a period');
        document.body.appendChild(panel);

        function renderTrigger() {
            const d = describe(value, cutoffs);
            trigger.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>' +
                '<span class="pp-label"><b>' + esc(d.label) + '</b><small>' + esc(d.dates) + '</small></span>' +
                '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
        }

        function body() {
            if (tab === 'cutoff') {
                if (!cutoffs.length) return '<div class="pp-empty">Payroll cutoffs are not available. Use Month or Custom.</div>';
                return cutoffs.map(function (c, i) {
                    const k = cutoffKey(c);
                    return '<button type="button" class="pp-item' + (k === value ? ' pp-sel' : '') + '" data-pp-pick="' + esc(k) + '">' +
                        '<span class="pp-name">' + (i === 0 ? 'Current cutoff' : i === 1 ? 'Previous cutoff' : 'Cutoff') + '</span>' +
                        '<span class="pp-dates">' + esc(short(c.start, false) + ' – ' + short(c.end, true)) + '</span></button>';
                }).join('');
            }
            if (tab === 'month') {
                const now = new Date();
                let html = '<div class="pp-grid">';
                for (let i = 0; i < 12; i++) {
                    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
                    const k = monthKey(d);
                    html += '<button type="button" class="pp-month' + (k === value ? ' pp-sel' : '') + '" data-pp-pick="' + k + '">' +
                        esc(d.toLocaleDateString(undefined, { month: 'short' })) + '<small>' + (i === 0 ? 'This month' : d.getFullYear()) + '</small></button>';
                }
                return html + '</div><p class="pp-note">Totals the whole calendar month, 1st to last day.</p>';
            }
            const r = range(value);
            return '<div class="pp-chips">' +
                '<button type="button" class="pp-chip' + (value === '30' ? ' pp-sel' : '') + '" data-pp-pick="30">Last 30 days</button>' +
                '<button type="button" class="pp-chip' + (value === 'year' ? ' pp-sel' : '') + '" data-pp-pick="year">This year</button></div>' +
                '<div class="pp-row"><div><label>From</label><input type="date" data-pp-from value="' + esc(r[0]) + '"></div><div><label>To</label><input type="date" data-pp-to value="' + esc(r[1]) + '"></div></div>' +
                '<p class="pp-err" data-pp-err>' + esc(customError) + '</p>' +
                '<button type="button" class="pp-apply" data-pp-apply>Apply dates</button>';
        }

        function renderPanel() {
            const tabs = [['cutoff', 'Cutoff'], ['month', 'Month'], ['custom', 'Custom']];
            panel.innerHTML = '<div class="pp-tabs" role="tablist">' + tabs.map(function (t) {
                return '<button type="button" role="tab" class="pp-tab' + (tab === t[0] ? ' pp-on' : '') + '" data-pp-tab="' + t[0] + '" aria-selected="' + (tab === t[0]) + '">' + t[1] + '</button>';
            }).join('') + '</div><div class="pp-body">' + body() + '</div>';
        }

        function place() {
            const r = trigger.getBoundingClientRect();
            const w = Math.min(340, window.innerWidth - 16);
            let left = (opts.align === 'left') ? r.left : r.right - w;
            left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
            let top = r.bottom + 6;
            const h = panel.offsetHeight;
            if (top + h > window.innerHeight - 8 && r.top - 6 - h > 8) top = r.top - 6 - h;   // flip above when there is no room below
            panel.style.left = left + 'px';
            panel.style.top = Math.max(8, top) + 'px';
        }

        function open() {
            tab = tabOf(value);
            customError = '';
            renderPanel();
            panel.classList.add('pp-open');
            trigger.setAttribute('aria-expanded', 'true');
            place();
        }
        function close() {
            panel.classList.remove('pp-open');
            trigger.setAttribute('aria-expanded', 'false');
        }
        function choose(kind) {
            value = kind;
            renderTrigger();
            close();
            if (opts.onChange) opts.onChange(kind);
        }

        trigger.addEventListener('click', function () { panel.classList.contains('pp-open') ? close() : open(); });
        panel.addEventListener('click', function (e) {
            const t = e.target.closest('[data-pp-tab]');
            if (t) { tab = t.dataset.ppTab; customError = ''; renderPanel(); place(); return; }
            const pick = e.target.closest('[data-pp-pick]');
            if (pick) { choose(pick.dataset.ppPick); return; }
            if (e.target.closest('[data-pp-apply]')) {
                const f = panel.querySelector('[data-pp-from]').value;
                const to = panel.querySelector('[data-pp-to]').value;
                let err = '';
                if (!f || !to) err = 'Choose both dates.';
                else if (f > to) err = 'The start date must be on or before the end date.';
                else if ((parse(to) - parse(f)) / 86400000 > 366) err = 'Choose a range of one year or less.';
                if (err) { customError = err; panel.querySelector('[data-pp-err]').textContent = err; return; }
                choose('custom:' + f + '|' + to);
            }
        });
        // Enter in a date box applies the dates.
        panel.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.matches('[data-pp-from],[data-pp-to]')) { e.preventDefault(); panel.querySelector('[data-pp-apply]').click(); }
        });
        document.addEventListener('mousedown', function (e) {
            if (!panel.classList.contains('pp-open')) return;
            if (panel.contains(e.target) || trigger.contains(e.target)) return;
            close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.classList.contains('pp-open')) { close(); e.stopPropagation(); trigger.focus(); }
        }, true);
        window.addEventListener('resize', function () { if (panel.classList.contains('pp-open')) place(); });
        // Scrolling the page or a card moves the button: follow it, and close if it scrolled out of sight or its tab was hidden.
        window.addEventListener('scroll', function () {
            if (!panel.classList.contains('pp-open')) return;
            if (trigger.offsetParent === null) { close(); return; }
            const r = trigger.getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight) { close(); return; }
            place();
        }, true);

        renderTrigger();
        return {
            getValue: function () { return value; },
            setValue: function (kind) { value = kind; renderTrigger(); },
            setCutoffs: function (list) { cutoffs = (list || []).slice(); renderTrigger(); if (panel.classList.contains('pp-open')) { renderPanel(); place(); } },
            close: close,
            destroy: function () { panel.remove(); trigger.remove(); },
        };
    }

    global.PeriodPicker = { create: create, range: range, describe: describe, cutoffKey: cutoffKey, monthKey: monthKey };
})(window);
