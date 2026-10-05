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

function _mpSetViewMode(mode) {
    _mpViewMode = mode;
    document.getElementById('mpViewCutoffBtn').classList.toggle('active', mode === 'cutoff');
    document.getElementById('mpViewDayBtn').classList.toggle('active', mode === 'day');
    _mpRenderCharts();
}

function openMyPerformanceModal() {
    document.getElementById('myPerformanceModal').style.display = 'flex';
    loadMyPerformance();
}

function closeMyPerformanceModal() {
    document.getElementById('myPerformanceModal').style.display = 'none';
}

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
    _mpViewMode = 'cutoff';
    document.getElementById('mpViewCutoffBtn').classList.add('active');
    document.getElementById('mpViewDayBtn').classList.remove('active');

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
        _mpRenderCharts();
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
    if (_mpHoursChartInstance) _mpHoursChartInstance.destroy();

    if (_mpViewMode === 'day') {
        _mpRenderDayCharts();
    } else {
        _mpRenderCutoffCharts();
    }
}

function _mpRenderCutoffCharts() {
    document.getElementById('mpChart1Title').textContent = 'Attendance & Punctuality Trend';
    document.getElementById('mpChart2Title').textContent = 'Avg Hours & OT Days Trend';

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

    const hoursCtx = document.getElementById('mpHoursChart').getContext('2d');
    _mpHoursChartInstance = new Chart(hoursCtx, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Avg Hours/Day',
                    data: trend.map(t => t.avg_hours_per_day),
                    backgroundColor: 'rgba(59,130,246,.5)',
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'OT Days',
                    data: trend.map(t => t.ot_days),
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
                y1: { type: 'linear', position: 'right', min: 0, grid: { drawOnChartArea: false }, title: { display: true, text: 'OT Days' } },
            },
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function _mpRenderDayCharts() {
    document.getElementById('mpChart1Title').textContent = 'Late Minutes Per Day (this cutoff)';
    document.getElementById('mpChart2Title').textContent = 'Hours & OT Per Day (this cutoff)';

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
