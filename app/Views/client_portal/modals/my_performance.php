<style>
#myPerformanceModal .modal {
    max-width: 720px;
}

.mp-scorecards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

@media (min-width: 560px) {
    .mp-scorecards {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.mp-card {
    background: #f9fafb;
    border-radius: 14px;
    padding: 14px;
    text-align: center;
}

.mp-card-value {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
}

.mp-card-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.mp-chart-title {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin: 18px 0 8px;
}

.mp-empty {
    text-align: center;
    color: #9ca3af;
    padding: 30px 10px;
}

.mp-daily-toggle-row {
    margin-top: 18px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.mp-toggle-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
}

.mp-daily-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 12px;
}

.mp-daily-table th,
.mp-daily-table td {
    padding: 6px 8px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.mp-daily-table th {
    color: #6b7280;
    font-weight: 600;
    background: #f9fafb;
}

.mp-daily-table td.mp-late {
    color: #dc2626;
}

.mp-streak { background: #f9fafb; border-radius: 14px; padding: 16px; }
.mp-streak-hero { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
@keyframes mpFlicker { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
.mp-streak-ic svg { animation: mpFlicker 2.4s ease-in-out infinite; }
.mp-streak-ic.off svg { animation: none; }
.mp-streak-ic { display:flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:12px; background:#1f2937; color:#fbbf24; }
.mp-streak-ic.off { background:#e5e7eb; color:#9ca3af; }
.mp-streak-num { font-size: 44px; font-weight: 800; color: #1f2937; line-height: 1; }
.mp-streak-sub { font-size: 12px; color: #6b7280; margin-top: 4px; }
.mp-streak-stats { display: flex; gap: 18px; margin-left: auto; flex-wrap: wrap; }
.mp-streak-stats div { text-align: center; }
.mp-streak-stats b { display: block; font-size: 18px; color: #374151; }
.mp-streak-stats span { font-size: 11px; color: #9ca3af; }
.mp-streak-msg { margin-top: 12px; font-size: 13px; color: #374151; background: #fff; border-radius: 10px; padding: 9px 12px; border-left: 3px solid #fbbf24; }
.mp-prog { margin-top: 12px; }
.mp-prog-bar { height: 8px; background: #e5e7eb; border-radius: 99px; overflow: hidden; }
.mp-prog-fill { height: 100%; background: linear-gradient(90deg, #fbbf24, #f97316); border-radius: 99px; transition: width .6s ease; }
.mp-prog-lbl { font-size: 11px; color: #6b7280; margin-top: 4px; }
.mp-badges { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
.mp-badge { width: 62px; text-align: center; padding: 8px 0 7px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; color: #9ca3af; }
.mp-badge svg { display:block; margin: 0 auto 3px; }
.mp-badge b { display:block; font-size: 13px; font-weight: 700; line-height: 1.1; }
.mp-badge small { font-size: 10px; }
.mp-badge.earned { color: #b45309; border-color: #fbbf24; background: #fffbeb; }
.mp-badge.earned b { color: #1f2937; }
.mp-cal { display: flex; gap: 4px; margin-top: 14px; overflow-x: auto; padding-bottom: 2px; }
.mp-cal-col { display: flex; flex-direction: column; gap: 4px; }
.mp-cal-cell { display: inline-block; width: 14px; height: 14px; border-radius: 3px; }
.mp-cal-on { background: #22c55e; }
.mp-cal-late { background: #ef4444; }
.mp-cal-off { background: #e5e7eb; }
.mp-cal-future { background: transparent; }
.mp-cal-today { outline: 2px solid #374151; outline-offset: 1px; }
.mp-cal-legend { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #9ca3af; margin-top: 8px; }

.mp-view-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.mp-view-toggle button {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    cursor: pointer;
    transition: .15s ease;
}

.mp-view-toggle button.active {
    background: #8b5cf6;
    border-color: #8b5cf6;
    color: #fff;
}
</style>

<div class="modal-header">
    <span>My Performance</span>
    <button type="button" onclick="closeMyPerformanceModal()">✕</button>
</div>

<div class="modal-body">
    <div id="mpLoading" class="mp-empty">Loading your performance...</div>

    <div id="mpContent" style="display:none;">
        <div class="mp-scorecards">
            <div class="mp-card">
                <div class="mp-card-value" id="mpAttendance">—</div>
                <div class="mp-card-label">Attendance (this cutoff)</div>
            </div>
            <div class="mp-card">
                <div class="mp-card-value" id="mpPunctuality">—</div>
                <div class="mp-card-label">Punctuality (this cutoff)</div>
            </div>
            <div class="mp-card">
                <div class="mp-card-value" id="mpAvgHours">—</div>
                <div class="mp-card-label">Avg Hours / Day</div>
            </div>
            <div class="mp-card">
                <div class="mp-card-value" id="mpOtDays">—</div>
                <div class="mp-card-label">OT Days</div>
            </div>
        </div>

        <div class="mp-view-toggle">
            <span>View:</span>
            <button type="button" id="mpViewCutoffBtn" class="active" onclick="_mpSetViewMode('cutoff')">Per Cutoff</button>
            <button type="button" id="mpViewDayBtn" onclick="_mpSetViewMode('day')">Per Day (this cutoff)</button>
        </div>

        <div class="mp-chart-title" id="mpChart1Title">Attendance &amp; Punctuality Trend</div>
        <canvas id="mpRateChart" height="120"></canvas>

        <div class="mp-chart-title" id="mpChart2Title">Avg Hours &amp; OT Days Trend</div>
        <canvas id="mpHoursChart" height="120"></canvas>
        <div id="mpStreak" style="display:none;"></div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="cancel-btn" onclick="closeMyPerformanceModal()">Close</button>
</div>

<script>
let _mpRateChartInstance = null;
let _mpHoursChartInstance = null;
let _mpTrendData = [];
let _mpDailyData = [];
let _mpViewMode = 'cutoff';
let _mpStreak = null;

// Remember whether the modal was open, which view it was on and how far it
// was scrolled, so a browser refresh puts the person back in the same spot.
const _MP_KEY = 'mp_state';
let _mpRestore = null;
function _mpSave(patch) {
    try {
        const cur = JSON.parse(sessionStorage.getItem(_MP_KEY) || '{}');
        sessionStorage.setItem(_MP_KEY, JSON.stringify(Object.assign(cur, patch)));
    } catch (e) {}
}

function _mpSetViewMode(mode) {
    _mpViewMode = mode;
    _mpSave({ mode: mode });
    document.getElementById('mpViewCutoffBtn').classList.toggle('active', mode === 'cutoff');
    document.getElementById('mpViewDayBtn').classList.toggle('active', mode === 'day');
    _mpRenderCharts();
}

function openMyPerformanceModal(restore) {
    document.getElementById('myPerformanceModal').style.display = 'flex';
    if (restore) {
        _mpRestore = restore;
    } else {
        _mpRestore = null;
        _mpSave({ open: true, mode: 'cutoff', scroll: 0 });
    }
    loadMyPerformance();
}

function closeMyPerformanceModal() {
    document.getElementById('myPerformanceModal').style.display = 'none';
    try { sessionStorage.removeItem(_MP_KEY); } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function () {
    const body = document.querySelector('#myPerformanceModal .modal-body');
    let t = null;
    if (body) body.addEventListener('scroll', function () {
        clearTimeout(t);
        t = setTimeout(function () { _mpSave({ scroll: body.scrollTop }); }, 150);
    });
    try {
        const st = JSON.parse(sessionStorage.getItem(_MP_KEY) || 'null');
        if (st && st.open) openMyPerformanceModal(st);
    } catch (e) {}
});

function _mpEnsureChartJs(callback) {
    if (window.Chart) { callback(); return; }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
    script.onload = callback;
    script.onerror = function () {
        document.getElementById('mpLoading').textContent = 'Could not load the chart library. Check your internet connection.';
        document.getElementById('mpLoading').style.display = 'block';
        document.getElementById('mpContent').style.display = 'none';
    };
    document.head.appendChild(script);
}

async function loadMyPerformance() {
    const loadingEl = document.getElementById('mpLoading');
    const contentEl = document.getElementById('mpContent');
    loadingEl.style.display = 'block';
    loadingEl.textContent = 'Loading your performance...';
    contentEl.style.display = 'none';
    _mpViewMode = (_mpRestore && _mpRestore.mode === 'day') ? 'day' : 'cutoff';
    document.getElementById('mpViewCutoffBtn').classList.toggle('active', _mpViewMode === 'cutoff');
    document.getElementById('mpViewDayBtn').classList.toggle('active', _mpViewMode === 'day');

    let data;
    try {
        const resp = await fetch('<?= site_url('client_portal/performance/my-summary') ?>');
        data = await resp.json();
        if (data.error) throw new Error(data.error);
    } catch (err) {
        loadingEl.textContent = 'Could not load your performance data.';
        return;
    }

    _mpEnsureChartJs(function () {
        // Show the container BEFORE creating the charts — Chart.js measures
        // the canvas at construction time, and a display:none parent gives
        // it a 0x0 size, producing a blank/broken chart that never recovers
        // even after the parent becomes visible.
        loadingEl.style.display = 'none';
        contentEl.style.display = 'block';
        _mpRenderScorecards(data.latest);
        _mpTrendData = data.trend || [];
        _mpDailyData = data.daily || [];
        _mpStreak = data.streak || null;
        _mpRenderCharts();
        if (_mpRestore) {
            const body = document.querySelector('#myPerformanceModal .modal-body');
            if (body) body.scrollTop = _mpRestore.scroll || 0;
            _mpRestore = null;
        }
    });
}

function _mpRenderScorecards(latest) {
    if (!latest) return;
    document.getElementById('mpAttendance').textContent = latest.attendance_rate + '%';
    document.getElementById('mpPunctuality').textContent = (latest.punctuality_rate === null ? '—' : latest.punctuality_rate + '%');
    document.getElementById('mpAvgHours').textContent = latest.avg_hours_per_day + ' hrs';
    document.getElementById('mpOtDays').textContent = latest.ot_days;
}

function _mpRenderCharts() {
    if (_mpRateChartInstance) _mpRateChartInstance.destroy();
    if (_mpHoursChartInstance) { _mpHoursChartInstance.destroy(); _mpHoursChartInstance = null; }

    if (_mpViewMode === 'day') {
        _mpRenderDayCharts();
    } else {
        _mpRenderCutoffCharts();
    }
}

function _mpRenderCutoffCharts() {
    document.getElementById('mpChart1Title').textContent = 'Attendance & Punctuality Trend';
    document.getElementById('mpChart2Title').textContent = 'On-Time Streak';
    document.getElementById('mpHoursChart').style.display = 'none';
    _mpRenderStreak();

    const trend = _mpTrendData;
    const labels = trend.map(t => t.label);

    const rateCtx = document.getElementById('mpRateChart').getContext('2d');
    _mpRateChartInstance = new Chart(rateCtx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Attendance %',
                    data: trend.map(t => t.attendance_rate),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,.15)',
                    tension: .3,
                    fill: true,
                },
                {
                    label: 'Punctuality %',
                    data: trend.map(t => t.punctuality_rate),
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,.15)',
                    tension: .3,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } },
            plugins: { legend: { position: 'bottom' } },
        },
    });

}

const _mpIcons = {
    flame: '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c.5 3-1.5 4.5-3 6.5C7.5 11.5 7 13 7 14.5a5 5 0 0 0 10 0c0-1.7-.8-3-1.8-4.2-.3 1-.9 1.7-1.7 2 .3-3.2-.5-6.8-1.5-9.3z"/></svg>',
    check: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="5.5"/><path d="M9.5 9l1.8 1.8L14.7 7.4"/><path d="M8.5 13.8L7 21l5-2.6 5 2.6-1.5-7.2"/></svg>',
    lock:  '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>'
};

function _mpRenderStreak() {
    const box = document.getElementById('mpStreak');
    const st = _mpStreak;
    box.style.display = 'block';
    if (!st || !st.days) {
        box.innerHTML = '<div class="mp-empty">No attendance recorded yet.</div>';
        return;
    }

    const cur = st.current, best = st.best;
    const milestones = [3, 7, 14, 30, 60, 100];
    const next = milestones.find(m => m > cur) || null;
    const prev = [...milestones].reverse().find(m => m <= cur) || 0;
    const pct = next ? Math.round((cur - prev) / (next - prev) * 100) : 100;

    let msg;
    if (cur === 0) msg = st.last_late
        ? 'Your last attendance was late. Your next on-time day starts a fresh streak.'
        : 'Clock in on time to start your streak.';
    else if (cur >= best && cur > 1) msg = 'Your longest streak yet. Keep it going.';
    else if (cur === 1) msg = 'Streak started. Clock in on time again tomorrow to build it.';
    else msg = (best - cur + 1) + ' more on-time day' + (best - cur + 1 === 1 ? '' : 's') + ' to beat your all-time best of ' + best + '.';

    // GitHub-style calendar: weeks as columns (Mon..Sun rows), last 17 weeks.
    const WEEKS = 17;
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const fmt = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const dow = (today.getDay() + 6) % 7;            // Mon=0
    const start = new Date(today); start.setDate(today.getDate() - dow - (WEEKS - 1) * 7);
    let cols = '';
    for (let w = 0; w < WEEKS; w++) {
        let col = '';
        for (let d = 0; d < 7; d++) {
            const day = new Date(start); day.setDate(start.getDate() + w * 7 + d);
            if (day > today) { col += '<span class="mp-cal-cell mp-cal-future"></span>'; continue; }
            const key = fmt(day);
            const state = (key in st.map) ? (st.map[key] ? 'late' : 'on') : 'off';
            const tip = key + (state === 'on' ? ' - on time' : state === 'late' ? ' - late' : ' - no attendance');
            col += '<span class="mp-cal-cell mp-cal-' + state + (day.getTime() === today.getTime() ? ' mp-cal-today' : '') + '" title="' + tip + '"></span>';
        }
        cols += '<div class="mp-cal-col">' + col + '</div>';
    }

    const badges = milestones.map(m =>
        '<div class="mp-badge' + (best >= m ? ' earned' : '') + '" title="' + (best >= m ? 'Reached' : 'Not yet reached') + ': ' + m + ' days on time in a row">' + (best >= m ? _mpIcons.check : _mpIcons.lock) + '<b>' + m + '</b><small>days</small></div>').join('');

    box.innerHTML =
        '<div class="mp-streak">' +
            '<div class="mp-streak-hero">' +
                '<div class="mp-streak-ic' + (cur === 0 ? ' off' : '') + '">' + _mpIcons.flame + '</div>' +
                '<div><div class="mp-streak-num">' + cur + '</div>' +
                '<div class="mp-streak-sub">day' + (cur === 1 ? '' : 's') + ' on time in a row</div></div>' +
                '<div class="mp-streak-stats">' +
                    '<div><b>' + best + '</b><span>All-time best</span></div>' +
                    '<div><b>' + (st.on_time_rate === null ? '-' : st.on_time_rate + '%') + '</b><span>On time (last 30)</span></div>' +
                    '<div><b>' + st.total_on_time + '</b><span>On-time days</span></div>' +
                '</div>' +
            '</div>' +
            '<div class="mp-streak-msg">' + msg + '</div>' +
            (next ? '<div class="mp-prog"><div class="mp-prog-bar"><div class="mp-prog-fill" style="width:' + pct + '%"></div></div>' +
                '<div class="mp-prog-lbl">' + cur + ' / ' + next + ' days to the next milestone</div></div>'
                : '<div class="mp-prog-lbl">All milestones reached. Excellent consistency.</div>') +
            '<div class="mp-badges">' + badges + '</div>' +
            '<div class="mp-cal">' + cols + '</div>' +
            '<div class="mp-cal-legend"><span class="mp-cal-cell mp-cal-off"></span> none <span class="mp-cal-cell mp-cal-on"></span> on time <span class="mp-cal-cell mp-cal-late"></span> late</div>' +
        '</div>';
}

function _mpRenderDayCharts() {
    document.getElementById('mpChart1Title').textContent = 'Late Minutes Per Day (this cutoff)';
    document.getElementById('mpChart2Title').textContent = 'Hours & OT Per Day (this cutoff)';
    document.getElementById('mpHoursChart').style.display = '';
    document.getElementById('mpStreak').style.display = 'none';

    const daily = _mpDailyData;
    const labels = daily.map(d => d.date.slice(5)); // MM-DD, cutoff is already scoped to one period

    const rateCtx = document.getElementById('mpRateChart').getContext('2d');
    _mpRateChartInstance = new Chart(rateCtx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Late Minutes',
                    data: daily.map(d => d.late_minutes),
                    backgroundColor: daily.map(d => d.late_minutes > 0 ? 'rgba(220,38,38,.6)' : 'rgba(34,197,94,.5)'),
                },
            ],
        },
        options: {
            responsive: true,
            scales: { y: { min: 0, title: { display: true, text: 'Minutes' } } },
            plugins: { legend: { display: false } },
        },
    });

    const hoursCtx = document.getElementById('mpHoursChart').getContext('2d');
    _mpHoursChartInstance = new Chart(hoursCtx, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Hours Worked',
                    data: daily.map(d => d.hours),
                    backgroundColor: 'rgba(59,130,246,.5)',
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'OT Hours',
                    data: daily.map(d => d.ot_hours),
                    borderColor: '#f97316',
                    yAxisID: 'y1',
                    tension: .3,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                y:  { type: 'linear', position: 'left', min: 0, title: { display: true, text: 'Hours' } },
                y1: { type: 'linear', position: 'right', min: 0, grid: { drawOnChartArea: false }, title: { display: true, text: 'OT Hours' } },
            },
            plugins: { legend: { position: 'bottom' } },
        },
    });
}
</script>
