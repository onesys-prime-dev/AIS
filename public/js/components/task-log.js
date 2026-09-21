/**
 * task-log.js
 *
 * Shared "Task Log" modal logic — the timeline bar, task list, and add/edit
 * form used to log multiple tasks (each with its own time span) within a
 * single clocked-in session. Used on both the Timesheet page and the
 * Dashboard's "Log Task" button; each page has exactly one instance of the
 * modal, so the fixed element IDs below never collide across pages.
 *
 * Requires, defined by the host page BEFORE this script loads:
 *   - const TASKS_BASE_URL, UPDATE_TASK_URL, DELETE_TASK_URL (site_url()-based)
 *   - function escapeHtml(str)
 *   - window.showToast(message, type) — from components/toast.js
 *
 * Requires these element IDs to exist in the page's Task Log modal markup:
 *   taskLogModal, task_att_id, task_att_date, task_editing_id,
 *   task_start_time, task_end_time (hidden inputs — canonical 24h "HH:MM"
 *     values), task_start_hour/_minute/_ampm, task_end_hour/_minute/_ampm
 *     (the visible <select> dropdowns users actually interact with),
 *   task_att_client, task_att_text,
 *   tl_timeline, tl_window_label, tl_task_list, tl_empty_msg,
 *   tl_form_title, tl_cancel_edit_btn, tl_save_btn, tl_form_error,
 *   tl_duration_label (optional — duration readout for the time fields)
 */

// ── Task Log: state for the currently-open attendance session ──────────────
let taskLogWindow = null;   // { time_in, time_out }
let taskLogTasks  = [];     // tasks already logged this session
let taskLogDirty  = false;  // true once any add/edit/delete succeeds

// Last known-good (end - start) duration for the add/edit form, in ms. Kept
// in sync whenever the end time is deliberately changed (typed, chip click,
// timeline click) so that nudging the start time afterwards can shift the
// end time by the same span instead of forcing it to be re-entered.
let taskLogDurationMs = null;
let taskLogListenersBound = false;

// Same convention as presence.js: pull the token from the <meta> tag main.php
// sets, sent as a header since these are JSON fetch() calls (no form field
// for the CSRF filter to read from a plain POST body).
function taskLogCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function openTaskLogModal(attendanceId, date) {
    document.getElementById('task_att_id').value = attendanceId;
    document.getElementById('task_att_date').textContent = date || '';
    taskLogDirty = false;
    cancelTaskEdit();
    bindTaskFormListenersOnce();

    // Inline style (not class toggling) so this works regardless of which
    // page's CSS convention is in play — Tailwind's hidden/flex utility
    // classes can lose a specificity tie against a page's own bare
    // ".modal-overlay { display:none }" rule depending on stylesheet order.
    const modal = document.getElementById('taskLogModal');
    modal.style.display = 'flex';
    document.body.classList.add('overflow-hidden');

    loadTaskPresets();
    await loadTasksForSession(attendanceId);
}

// Preset task / client dropdowns. Only present on pages whose modal includes them
// (SAVED_TASKS_URL / SAVED_CLIENTS_URL are defined by the host page).
async function loadTaskPresets() {
    await Promise.all([
        fillPresetDropdown('savedTasksDropdown', typeof SAVED_TASKS_URL !== 'undefined' ? SAVED_TASKS_URL : null, 'task_performed', 'task_att_text', '-- Write your task manually --'),
        fillPresetDropdown('savedClientsDropdown', typeof SAVED_CLIENTS_URL !== 'undefined' ? SAVED_CLIENTS_URL : null, 'client', 'task_att_client', '-- Type a client manually --'),
    ]);
}

async function fillPresetDropdown(selectId, url, field, targetId, placeholder) {
    const select = document.getElementById(selectId);
    if (!select || !url) return;
    try {
        const rows = await (await fetch(url)).json();
        select.innerHTML = '';
        const first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        select.appendChild(first);
        rows.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r[field];
            opt.textContent = r[field];
            select.appendChild(opt);
        });
        select.onchange = function () {
            if (this.value) document.getElementById(targetId).value = this.value;
        };
    } catch (e) {
        console.error('Failed to load presets:', e);
    }
}

// ── Start/End time entry ────────────────────────────────────────────────
// Native <input type="time"> relies on tiny per-segment spinner arrows (or
// mouse-wheel scrolling) to change the value, which is fiddly to use. The
// visible controls are instead three plain <select> dropdowns per field
// (Hour / Minute / AM-PM, minute in 5-minute steps to keep each list short)
// — click to open, click the value, done. Each field still has a hidden
// <input id="task_start_time"/"task_end_time"> holding the canonical 24h
// "HH:MM" value; every other function in this file reads/writes that hidden
// input exactly as before, via setStartTimeValue()/setEndTimeValue() below.
const TL_MINUTE_STEP = 5;

function tlPad2(n) {
    return String(n).padStart(2, '0');
}

function populateTimeSelect(prefix) {
    const hourSel = document.getElementById(prefix + '_hour');
    const minSel  = document.getElementById(prefix + '_minute');
    const ampmSel = document.getElementById(prefix + '_ampm');
    if (!hourSel || !minSel || !ampmSel) return;

    hourSel.innerHTML = '<option value=""></option>' +
        Array.from({ length: 12 }, (_, i) => i + 1).map(h => `<option value="${h}">${h}</option>`).join('');
    minSel.innerHTML = '<option value=""></option>' +
        Array.from({ length: 60 / TL_MINUTE_STEP }, (_, i) => i * TL_MINUTE_STEP)
            .map(m => `<option value="${m}">${tlPad2(m)}</option>`).join('');
    ampmSel.innerHTML = '<option value="AM">AM</option><option value="PM">PM</option>';
}

// 24h "HH:MM" -> fills the three selects for that field (minute snapped to
// the nearest 5-minute step so it always matches an available option).
function writeTimeSelects(prefix, hhmm) {
    const hourSel = document.getElementById(prefix + '_hour');
    const minSel  = document.getElementById(prefix + '_minute');
    const ampmSel = document.getElementById(prefix + '_ampm');
    if (!hourSel || !minSel || !ampmSel) return;

    if (!hhmm) { hourSel.value = ''; minSel.value = ''; ampmSel.value = 'AM'; return; }

    const [h, m] = hhmm.split(':').map(Number);
    let mSnapped = Math.round(m / TL_MINUTE_STEP) * TL_MINUTE_STEP;
    let hCarry = h;
    // e.g. :58 rounds up to the NEXT hour's :00, not this hour's :00.
    if (mSnapped >= 60) { mSnapped -= 60; hCarry = (hCarry + 1) % 24; }

    let h12 = hCarry % 12;
    if (h12 === 0) h12 = 12;

    hourSel.value = h12;
    minSel.value  = mSnapped;
    ampmSel.value = hCarry >= 12 ? 'PM' : 'AM';
}

// Reads the three selects for a field back into a 24h "HH:MM" string, or ''
// if the hour/minute hasn't been picked yet.
function readTimeSelects(prefix) {
    const hourSel = document.getElementById(prefix + '_hour');
    const minSel  = document.getElementById(prefix + '_minute');
    const ampmSel = document.getElementById(prefix + '_ampm');
    if (!hourSel || !hourSel.value || minSel.value === '') return '';

    let h = parseInt(hourSel.value, 10) % 12;
    if (ampmSel.value === 'PM') h += 12;
    return tlPad2(h) + ':' + tlPad2(parseInt(minSel.value, 10));
}

function setStartTimeValue(hhmm) {
    document.getElementById('task_start_time').value = hhmm || '';
    writeTimeSelects('task_start', hhmm);
}

function setEndTimeValue(hhmm) {
    document.getElementById('task_end_time').value = hhmm || '';
    writeTimeSelects('task_end', hhmm);
}

function onStartSelectChange() {
    const hhmm = readTimeSelects('task_start');
    document.getElementById('task_start_time').value = hhmm;

    // Nudging the start keeps the previously-set duration instead of
    // requiring the end time to be picked again from scratch.
    if (hhmm && taskLogDurationMs) {
        const start = timeOnlyDate(hhmm);
        setEndTimeValue(toTimeInputValue(new Date(start.getTime() + taskLogDurationMs)));
    }
    updateDurationLabel();
}

function onEndSelectChange() {
    document.getElementById('task_end_time').value = readTimeSelects('task_end');
    taskLogDurationMs = computeCurrentDurationMs();
    updateDurationLabel();
}

// Wires the Start/End time selects and the timeline bar once per page load —
// the modal's elements never get torn down between opens, only their
// innerHTML content changes, so binding here (rather than in the render
// functions) avoids stacking up duplicate listeners on repeated opens.
function bindTaskFormListenersOnce() {
    if (taskLogListenersBound) return;
    taskLogListenersBound = true;

    populateTimeSelect('task_start');
    populateTimeSelect('task_end');

    ['task_start_hour', 'task_start_minute', 'task_start_ampm'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', onStartSelectChange);
    });
    ['task_end_hour', 'task_end_minute', 'task_end_ampm'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', onEndSelectChange);
    });

    const timeline = document.getElementById('tl_timeline');
    if (timeline) timeline.addEventListener('click', handleTimelineClick);
}

function closeTaskLogModal() {
    const modal = document.getElementById('taskLogModal');
    modal.style.display = 'none';
    document.body.classList.remove('overflow-hidden');

    // Any change made while the modal was open needs the underlying page
    // (Task/Client columns, totals, etc.) refreshed — simplest correct way
    // is a reload, deferred to here so multiple tasks can be added in one
    // sitting without the modal closing/reloading after every single save.
    if (taskLogDirty) location.reload();
}

async function loadTasksForSession(attendanceId) {
    const timeline = document.getElementById('tl_timeline');
    timeline.innerHTML = '<div class="text-xs text-gray-400 flex items-center justify-center h-full">Loading…</div>';
    document.getElementById('tl_task_list').innerHTML = '';
    document.getElementById('tl_empty_msg').classList.add('hidden');

    try {
        const res  = await fetch(`${TASKS_BASE_URL}/${attendanceId}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load tasks');

        taskLogWindow = data.window;
        taskLogTasks  = data.tasks || [];

        renderTimeline();
        renderTaskListUI();
        if (!document.getElementById('task_editing_id').value) prefillSuggestedSlot();
    } catch (e) {
        timeline.innerHTML = `<div class="text-xs text-red-500 flex items-center justify-center h-full px-2 text-center">${escapeHtml(e.message)}</div>`;
    }
}

// Bare "HH:MM:SS"/"HH:MM" -> a Date anchored to an arbitrary common day, for
// formatting only (never compared across the day boundary).
function timeOnlyDate(timeStr) {
    return new Date(`2000-01-01T${timeStr.length === 5 ? timeStr + ':00' : timeStr}`);
}

function fmtTime(timeStr) {
    return timeOnlyDate(timeStr).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function toTimeInputValue(d) {
    return d.toTimeString().slice(0, 5);
}

// Window/task boundaries as real Date objects, anchored to the session's own
// clock-in date, with a same-pattern +1 day roll for spans crossing midnight
// — mirrors the validation logic in TimesheetsService::validateTaskWindow().
function windowDates() {
    const start = new Date(taskLogWindow.time_in.replace(' ', 'T'));
    const end   = taskLogWindow.time_out ? new Date(taskLogWindow.time_out.replace(' ', 'T')) : new Date();
    return { start, end };
}

function taskDates(baseDateStr, task) {
    let start = new Date(`${baseDateStr}T${task.start_time}`);
    let end   = new Date(`${baseDateStr}T${task.end_time}`);
    if (end <= start) end = new Date(end.getTime() + 86400000);

    // Same "belongs to the day after clock-in" adjustment as the server's
    // validateTaskWindow() — only applies when the session itself crosses
    // midnight, so an early-morning time (e.g. 05:00) on a 10pm–6am shift
    // anchors to the right calendar day instead of appearing before it starts.
    const { start: winStart } = windowDates();
    const windowCrossesMidnight = taskLogWindow.time_out
        && taskLogWindow.time_out.split(' ')[0] !== taskLogWindow.time_in.split(' ')[0];
    if (windowCrossesMidnight && end <= winStart) {
        start = new Date(start.getTime() + 86400000);
        end   = new Date(end.getTime() + 86400000);
    }

    return { start, end };
}

function renderTimeline() {
    const el = document.getElementById('tl_timeline');
    const { start: winStart, end: winEnd } = windowDates();
    const totalMs = winEnd - winStart;

    const startLabel = winStart.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const endLabel = taskLogWindow.time_out ? winEnd.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Now';

    document.getElementById('tl_window_label').textContent = `${startLabel} – ${endLabel}`;

    if (totalMs <= 0) {
        el.innerHTML = '<div class="text-xs text-gray-400 flex items-center justify-center h-full">No time window available</div>';
        return;
    }

    const baseDateStr = taskLogWindow.time_in.split(' ')[0];

    // Logged-task blocks — inset top/bottom and rounded so back-to-back tasks
    // stay visually distinct instead of blending into one solid strip.
    // NOTE: sizing/spacing here is deliberately all inline `style` rather than
    // Tailwind utility classes — this HTML is injected at runtime, so any
    // class not already present in the *compiled* public/css/main.css (a
    // static build, not a JIT/CDN Tailwind) silently has no effect.
    const blocks = taskLogTasks.map((t, i) => {
        const { start, end } = taskDates(baseDateStr, t);
        const leftPct  = Math.max(0, Math.min(100, ((start - winStart) / totalMs) * 100));
        const widthPct = Math.max(1.5, Math.min(100 - leftPct, ((end - start) / totalMs) * 100));
        const hue = (i * 47) % 360;
        return `<div class="absolute" style="left:calc(${leftPct}% + 1px);width:calc(${widthPct}% - 2px);top:3px;bottom:3px;border-radius:3px;background:hsl(${hue},70%,55%);" title="${escapeHtml(t.task_performed)} (${fmtTime(t.start_time)}–${fmtTime(t.end_time)})"></div>`;
    }).join('');

    // Start/end time badges anchored to the bar itself (not just the small
    // summary line above it) so the clocked-in span reads clearly at a glance.
    const badge = (text, side) => `<span class="absolute pointer-events-none" style="top:50%;transform:translateY(-50%);${side}:3px;font-size:10px;font-weight:700;color:#4b5563;background:rgba(255,255,255,0.9);padding:2px 6px;border-radius:4px;box-shadow:0 1px 2px rgba(0,0,0,0.1);">${text}</span>`;

    el.innerHTML = blocks + badge(startLabel, 'left') + badge(endLabel, 'right');
}

function renderTaskListUI() {
    const list = document.getElementById('tl_task_list');
    const emptyMsg = document.getElementById('tl_empty_msg');

    if (!taskLogTasks.length) {
        list.innerHTML = '';
        emptyMsg.classList.remove('hidden');
        return;
    }
    emptyMsg.classList.add('hidden');

    list.innerHTML = taskLogTasks.map(t => `
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2" style="margin-bottom:6px;">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-gray-600">${fmtTime(t.start_time)} – ${fmtTime(t.end_time)}${t.client ? ' · ' + escapeHtml(t.client) : ''}</div>
                <div class="text-sm text-gray-700 truncate">${escapeHtml(t.task_performed)}</div>
            </div>
            <button type="button" onclick="startEditTask(${t.id})" class="text-gray-400 hover:text-purple-600 transition p-1" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button type="button" onclick="deleteTaskEntry(${t.id})" class="text-gray-400 hover:text-red-600 transition p-1" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/></svg>
            </button>
        </div>`).join('');
}

// Finds the first gap (>= 15 min) between the window start and the next
// logged task (or the window end), so the Add form doesn't start empty.
function suggestNextFreeSlot() {
    if (!taskLogWindow) return null;
    const { start: winStart, end: winEnd } = windowDates();
    const baseDateStr = taskLogWindow.time_in.split(' ')[0];
    const MIN_GAP_MS = 15 * 60 * 1000;
    const DEFAULT_SPAN_MS = 60 * 60 * 1000;

    const busy = taskLogTasks.map(t => taskDates(baseDateStr, t)).sort((a, b) => a.start - b.start);

    let cursor = winStart;
    for (const b of busy) {
        if (b.start - cursor >= MIN_GAP_MS) {
            const end = new Date(Math.min(b.start.getTime(), cursor.getTime() + DEFAULT_SPAN_MS));
            return { start: cursor, end };
        }
        if (b.end > cursor) cursor = b.end;
    }
    if (winEnd - cursor >= MIN_GAP_MS) {
        const end = new Date(Math.min(winEnd.getTime(), cursor.getTime() + DEFAULT_SPAN_MS));
        return { start: cursor, end };
    }
    return null;
}

function prefillSuggestedSlot() {
    const slot = suggestNextFreeSlot();
    setStartTimeValue(slot ? toTimeInputValue(slot.start) : '');
    setEndTimeValue(slot ? toTimeInputValue(slot.end) : '');
    taskLogDurationMs = computeCurrentDurationMs();
    updateDurationLabel();
}

// Reads the current Start/End field values and returns the span between them
// in ms, or null if either is blank. Spans that wrap past midnight (end <=
// start) are treated as running into the next day, matching the same
// convention used by taskDates()/the server's validateTaskWindow().
function computeCurrentDurationMs() {
    const s = document.getElementById('task_start_time').value;
    const e = document.getElementById('task_end_time').value;
    if (!s || !e) return null;
    const start = timeOnlyDate(s);
    let end = timeOnlyDate(e);
    if (end <= start) end = new Date(end.getTime() + 86400000);
    return end - start;
}

function updateDurationLabel() {
    const label = document.getElementById('tl_duration_label');
    if (!label) return;
    const ms = computeCurrentDurationMs();
    if (ms === null) { label.textContent = ''; return; }
    const totalMin = Math.round(ms / 60000);
    const h = Math.floor(totalMin / 60);
    const m = totalMin % 60;
    label.textContent = 'Duration: ' + (h ? h + 'h ' : '') + (m || !h ? m + 'm' : '');
}

// Quick-pick chip handler — applies a fixed span starting from whatever's in
// the Start field (falling back to the next free slot if it's still empty),
// so filling in a task doesn't require hand-typing both times.
function applyDurationPreset(minutes) {
    let startValue = document.getElementById('task_start_time').value;

    if (!startValue) {
        const slot = suggestNextFreeSlot();
        if (slot) {
            startValue = toTimeInputValue(slot.start);
        } else if (taskLogWindow) {
            startValue = toTimeInputValue(windowDates().start);
        } else {
            return;
        }
        setStartTimeValue(startValue);
    }

    const start = timeOnlyDate(startValue);
    setEndTimeValue(toTimeInputValue(new Date(start.getTime() + minutes * 60000)));
    taskLogDurationMs = minutes * 60000;
    updateDurationLabel();
    document.getElementById('tl_form_error').classList.add('hidden');
}

// Clicking a spot on the timeline sets the Start time to that point (snapped
// to 5 minutes), reusing the last-used duration (or defaulting to an hour)
// for the End time, capped so it doesn't run past the window end or into the
// next already-logged task.
function handleTimelineClick(e) {
    if (!taskLogWindow) return;

    const timeline = document.getElementById('tl_timeline');
    const rect = timeline.getBoundingClientRect();
    if (rect.width <= 0) return;

    const { start: winStart, end: winEnd } = windowDates();
    const totalMs = winEnd - winStart;
    if (totalMs <= 0) return;

    const STEP_MS = 5 * 60 * 1000;
    const frac = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    let clicked = new Date(winStart.getTime() + Math.round((frac * totalMs) / STEP_MS) * STEP_MS);
    if (clicked >= winEnd) clicked = new Date(winEnd.getTime() - STEP_MS);
    if (clicked < winStart) clicked = winStart;

    const baseDateStr = taskLogWindow.time_in.split(' ')[0];
    const upcoming = taskLogTasks
        .map(t => taskDates(baseDateStr, t))
        .filter(d => d.start > clicked)
        .sort((a, b) => a.start - b.start);
    const nextBusyStart = upcoming.length ? upcoming[0].start : winEnd;

    const desiredMs = taskLogDurationMs || (60 * 60 * 1000);
    let end = new Date(Math.min(clicked.getTime() + desiredMs, nextBusyStart.getTime(), winEnd.getTime()));
    if (end <= clicked) end = new Date(Math.min(clicked.getTime() + STEP_MS, winEnd.getTime()));

    setStartTimeValue(toTimeInputValue(clicked));
    setEndTimeValue(toTimeInputValue(end));
    taskLogDurationMs = computeCurrentDurationMs();
    updateDurationLabel();
    document.getElementById('tl_form_error').classList.add('hidden');
}

function startEditTask(taskId) {
    const task = taskLogTasks.find(t => t.id === taskId);
    if (!task) return;

    document.getElementById('task_editing_id').value = taskId;
    setStartTimeValue(task.start_time.slice(0, 5));
    setEndTimeValue(task.end_time.slice(0, 5));
    document.getElementById('task_att_client').value = task.client || '';
    document.getElementById('task_att_text').value   = task.task_performed || '';
    document.getElementById('tl_form_title').textContent = 'Edit Task';
    document.getElementById('tl_cancel_edit_btn').classList.remove('hidden');
    document.getElementById('tl_save_btn').textContent = 'Update Task';
    document.getElementById('tl_form_error').classList.add('hidden');
    taskLogDurationMs = computeCurrentDurationMs();
    updateDurationLabel();
}

function cancelTaskEdit() {
    document.getElementById('task_editing_id').value = '';
    document.getElementById('task_att_client').value = '';
    document.getElementById('task_att_text').value = '';
    document.getElementById('tl_form_title').textContent = 'Add a Task';
    document.getElementById('tl_cancel_edit_btn').classList.add('hidden');
    document.getElementById('tl_save_btn').textContent = 'Add Task';
    document.getElementById('tl_form_error').classList.add('hidden');
    if (taskLogWindow) prefillSuggestedSlot();
}

function showTaskFormError(msg) {
    const el = document.getElementById('tl_form_error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function saveTask() {
    const attendanceId  = document.getElementById('task_att_id').value;
    const editingId      = document.getElementById('task_editing_id').value;
    const startTime      = document.getElementById('task_start_time').value;
    const endTime        = document.getElementById('task_end_time').value;
    const client          = document.getElementById('task_att_client').value.trim();
    const taskPerformed  = document.getElementById('task_att_text').value.trim();
    const saveTaskToggle  = document.getElementById('saveTaskToggle');
    const saveClientToggle = document.getElementById('saveClientToggle');

    document.getElementById('tl_form_error').classList.add('hidden');

    if (!taskPerformed) { showTaskFormError('Task description is required.'); return; }
    if (!startTime || !endTime) { showTaskFormError('Start and end time are required.'); return; }

    const url = editingId
        ? `${UPDATE_TASK_URL}/${editingId}`
        : `${TASKS_BASE_URL}/${attendanceId}/create`;

    const saveBtn = document.getElementById('tl_save_btn');
    saveBtn.disabled = true;

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': taskLogCsrfToken(),
            },
            body: JSON.stringify({
                task_performed: taskPerformed,
                client: client,
                start_time: startTime + ':00',
                end_time: endTime + ':00',
                // Only relevant on create — the Dashboard's modal has this
                // "save as preset" checkbox, the Timesheet page's doesn't.
                save_task: !editingId && !!(saveTaskToggle && saveTaskToggle.checked),
                save_client: !editingId && !!(saveClientToggle && saveClientToggle.checked),
            }),
        });
        const data = await res.json();
        if (!data.success) { showTaskFormError(data.message || 'Unable to save task.'); return; }

        taskLogDirty = true;
        if (saveTaskToggle) saveTaskToggle.checked = false;
        if (saveClientToggle) saveClientToggle.checked = false;
        cancelTaskEdit();
        await loadTasksForSession(attendanceId);
        if (typeof showToast === 'function') showToast(editingId ? 'Task updated.' : 'Task added.', 'success');
    } catch (e) {
        showTaskFormError('Network error. Please try again.');
    } finally {
        saveBtn.disabled = false;
    }
}

async function deleteTaskEntry(taskId) {
    if (!confirm('Delete this task entry?')) return;

    try {
        const res  = await fetch(`${DELETE_TASK_URL}/${taskId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': taskLogCsrfToken() },
        });
        const data = await res.json();
        if (!data.success) {
            if (typeof showToast === 'function') showToast(data.message || 'Unable to delete task.', 'error');
            return;
        }

        taskLogDirty = true;
        await loadTasksForSession(document.getElementById('task_att_id').value);
        if (typeof showToast === 'function') showToast('Task deleted.', 'success');
    } catch (e) {
        if (typeof showToast === 'function') showToast('Network error. Please try again.', 'error');
    }
}
