<div class="face-modal-content">

    <div id="face-modal-health" class="health-banner">
        Checking face recognition service status...
    </div>

    <p class="helper-text">
        Center <span id="face-modal-employee-name" class="font-semibold text-gray-800">this employee</span>'s
        face in the circle, then slowly turn their head to complete the ring — it captures automatically.
    </p>

    <?= view('components/face_scan', ['id' => 'enroll-face', 'size' => 220]) ?>

    <div id="fr-enrollment-line" class="enrollment-line">—</div>

    <div class="actions">
        <button id="fr-capture-btn" type="button" class="primary-btn">Capture Face</button>
    </div>

    <button id="fr-remove-btn" type="button" class="text-btn danger" hidden>Remove enrolled face</button>

</div>

<style>
.face-modal-content {
    font-family: 'Segoe UI', sans-serif;
}

.face-modal-content .health-banner {
    margin-bottom: 14px;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    line-height: 1.5;
    background: #f3f4f6;
    color: #4b5563;
}

.face-modal-content .health-banner.ok {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.face-modal-content .health-banner.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.face-modal-content .helper-text {
    margin: 0 0 18px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
    text-align: center;
}

.face-modal-content .enrollment-line {
    text-align: center;
    font-size: 12px;
    color: #6b7280;
    margin: 10px 0 4px;
}

.face-modal-content .actions {
    margin-top: 8px;
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.face-modal-content .primary-btn {
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #14b8a6, #2dd4bf);
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    padding: 11px 22px;
    min-width: 150px;
    cursor: pointer;
}

.face-modal-content .primary-btn:disabled { background: #9ca3af; cursor: not-allowed; }

.face-modal-content .text-btn {
    display: block;
    margin: 14px auto 0;
    border: none;
    background: none;
    padding: 0;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.face-modal-content .text-btn.danger { color: #dc2626; }
.face-modal-content .text-btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<script>
(() => {
    const HEALTH_URL = <?= json_encode(site_url('client_portal/face-recognition/health')) ?>;
    const ENROLL_BASE_URL = <?= json_encode(site_url('client_portal/face-recognition/enroll')) ?>;
    const CLEAR_BASE_URL = <?= json_encode(site_url('client_portal/face-recognition/clear')) ?>;
    const CSRF_TOKEN_NAME = <?= json_encode(function_exists('csrf_token') ? csrf_token() : null) ?>;
    const CSRF_HASH = <?= json_encode(function_exists('csrf_hash') ? csrf_hash() : null) ?>;

    const captureButton = document.getElementById('fr-capture-btn');
    const removeButton = document.getElementById('fr-remove-btn');
    const enrollmentLine = document.getElementById('fr-enrollment-line');
    const healthBanner = document.getElementById('face-modal-health');
    const employeeNameEl = document.getElementById('face-modal-employee-name');

    let targetUserId = null;

    const setHealth = (ok, message) => {
        healthBanner.textContent = message;
        healthBanner.className = 'health-banner ' + (ok ? 'ok' : 'error');
    };

    const formatDate = (isoLike) => {
        if (!isoLike) return '';
        const d = new Date(isoLike.replace(' ', 'T'));
        if (isNaN(d.getTime())) return isoLike;
        return d.toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'
        });
    };

    function renderEnrollment(enrollment) {
        if (!enrollment) return;

        if (enrollment.enrolled) {
            removeButton.hidden = false;
            captureButton.textContent = 'Capture Another Angle';

            const count = enrollment.count ?? 1;
            const max = enrollment.max ?? count;
            const dateText = enrollment.latest_at ? ` • Last updated: ${formatDate(enrollment.latest_at)}` : '';
            enrollmentLine.textContent = `${count} of ${max} samples saved${dateText}`;
        } else {
            removeButton.hidden = true;
            captureButton.textContent = 'Capture Face';
            enrollmentLine.textContent = 'No face registered yet';
        }

        window.dispatchEvent(new CustomEvent('face-recognition:enrollment-updated', {
            detail: { userId: targetUserId, enrollment }
        }));
    }

    async function refreshHealth() {
        try {
            const response = await fetch(HEALTH_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            setHealth(Boolean(data.ok), data.message || 'Face recognition status updated.');
        } catch (error) {
            console.error(error);
            setHealth(false, 'Unable to refresh face recognition status.');
        }
    }

    // Lazily constructed: this script runs as part of the page's "modals"
    // section, which renders before the "scripts" section that loads
    // face-scan.js — so window.createFaceScan isn't defined yet at parse
    // time. By the time a click handler below actually calls getFaceScan(),
    // every script tag on the page has long since executed.
    let faceScanInstance = null;
    function getFaceScan() {
        if (faceScanInstance) return faceScanInstance;

        faceScanInstance = window.createFaceScan({
        id: 'enroll-face',
        // One capture per explicit "arm" (auto on first-time enrollment,
        // otherwise via Capture Another Angle) — freezes on success instead
        // of endlessly re-capturing while the modal sits open, since each
        // capture writes a new embedding row.
        autoResumeAfterSuccess: false,
        onStateChange: (state) => {
            const busy = state === 'positioning' || state === 'scanning' || state === 'pass-complete' || state === 'capturing';
            captureButton.disabled = busy;
        },
        onCapture: async (image) => {
            if (!targetUserId) {
                return { status: 'error', message: 'No employee selected.' };
            }

            const payload = new URLSearchParams({ image });
            if (CSRF_TOKEN_NAME && CSRF_HASH) payload.append(CSRF_TOKEN_NAME, CSRF_HASH);

            try {
                const response = await fetch(`${ENROLL_BASE_URL}/${targetUserId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                });
                const data = await response.json();
                refreshHealth();

                if (data.status === 'success') {
                    renderEnrollment(data.enrollment);
                    return { status: 'success', message: data.message || 'Face saved.' };
                }

                return { status: 'error', message: data.message || 'Face enrollment failed. Please try again.' };
            } catch (error) {
                console.error(error);
                refreshHealth();
                return { status: 'error', message: 'Unable to reach the face enrollment service right now. Please try again.' };
            }
        }
        });

        return faceScanInstance;
    }

    captureButton.addEventListener('click', () => {
        getFaceScan().resume({ scansRequired: 1 });
    });

    removeButton.addEventListener('click', async () => {
        if (!targetUserId) return;
        if (!confirm('Remove this employee\'s enrolled face? Face login will stop working for them until re-enrolled.')) return;

        removeButton.disabled = true;

        try {
            const payload = new URLSearchParams();
            if (CSRF_TOKEN_NAME && CSRF_HASH) payload.append(CSRF_TOKEN_NAME, CSRF_HASH);

            const response = await fetch(`${CLEAR_BASE_URL}/${targetUserId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
                body: payload
            });
            const data = await response.json();

            if (data.status === 'success') {
                getFaceScan().setStatus(data.message || 'Enrolled face removed.', 'success');
                renderEnrollment(data.enrollment);
            } else {
                getFaceScan().setStatus(data.message || 'Unable to remove enrolled face.', 'error');
            }
        } catch (error) {
            console.error(error);
            getFaceScan().setStatus('Unable to reach the face recognition service right now.', 'error');
        } finally {
            removeButton.disabled = false;
        }
    });

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ENTRY POINT — called by employee_main.php when an edit icon
    | is clicked, since this single modal instance is reused for every row.
    |--------------------------------------------------------------------------
    */
    window.openFaceRecognitionModal = function (employee) {
        targetUserId = employee.user_id;
        employeeNameEl.textContent = employee.name || 'this employee';

        const enrollment = {
            enrolled: !!employee.enrolled,
            count: employee.enrollment_count,
            max: 5,
            latest_at: employee.enrollment_latest_at,
        };
        renderEnrollment(enrollment);

        openModalById('faceRecognitionModal');

        const scan = getFaceScan();
        if (enrollment.enrolled) {
            // Already has a face on file — show the live feed but don't
            // silently add another sample just from opening this modal.
            scan.start();
            scan.pause();
            scan.setStatus('Face already on file. Tap "Capture Another Angle" to add a sample.');
        } else {
            // First-time enrollment: two full passes back-to-back, no
            // manual click needed (mirrors Face ID's own two-scan setup).
            scan.start({ scansRequired: 2 });
        }

        refreshHealth();
    };

    document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('.modal-close');
        const overlay = e.target.classList?.contains('modal-overlay') ? e.target : null;
        if (!closeBtn && !overlay) return;

        const modal = closeBtn ? closeBtn.closest('.modal-overlay') : overlay;
        if (modal && modal.id === 'faceRecognitionModal' && faceScanInstance) {
            faceScanInstance.stop();
        }
    });

    window.addEventListener('beforeunload', () => { if (faceScanInstance) faceScanInstance.stop(); });
})();
</script>
