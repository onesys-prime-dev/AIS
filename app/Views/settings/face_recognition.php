<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="face-settings-page">
    <div class="tabs">
        <a href="<?= site_url('settings/customization') ?>" class="tab">Customization</a>
        <a href="<?= site_url('settings/face-recognition') ?>" class="tab active">Face Recognition</a>
    </div>

    <div class="face-grid">
        <section class="card face-card">
            <div id="face-recognition-health" class="health-banner <?= !empty($faceRecognitionHealth['ok']) ? 'ok' : 'error' ?>">
                <?= esc((string) ($faceRecognitionHealth['message'] ?? 'Checking face recognition service status...')) ?>
            </div>
            <div>
                <h2>Enroll Your Face</h2>
                <p class="helper-text">
                    Center your face in the frame so the login face verification can recognize your account.
                </p>
            </div>

            <div class="camera-shell">
                <div class="camera-frame" id="camera-frame">
                    <video id="face-video" autoplay playsinline muted></video>
                    <canvas id="face-canvas" hidden></canvas>

                    <div class="camera-guide">
                        <svg viewBox="0 0 64 80" fill="none">
                            <path d="M32 4C18 4 10 16 10 32c0 20 10 34 22 34s22-14 22-34C54 16 46 4 32 4Z"
                                  stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <svg class="scan-ring" viewBox="0 0 200 200">
                        <circle class="scan-ring-track" cx="100" cy="100" r="94"/>
                        <circle class="scan-ring-progress" id="scan-ring-progress" cx="100" cy="100" r="94"/>
                    </svg>

                    <div class="capture-result" id="capture-result">
                        <svg class="result-icon success-icon" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg class="result-icon error-icon" viewBox="0 0 24 24">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <p id="face-status" class="status-text">Camera is starting. Allow browser access if prompted.</p>

            <div class="actions">
                <button id="enroll-face-btn" type="button" class="primary-btn">Save Face</button>
                <button id="replace-face-btn" type="button" class="secondary-btn" hidden>Replace Face</button>
            </div>
        </section>

        <aside class="card tips-card">
            <h3>Tips for a strong match</h3>
            <ul>
                <li>Face the camera directly and keep your head centered.</li>
                <li>Use even lighting and avoid strong backlight behind you.</li>
                <li>Remove masks, sunglasses, or anything hiding your face.</li>
                <li>Re-enroll if your stored photo is outdated or unclear.</li>
            </ul>

            <div class="callout">
                Your enrolled face is linked to your current account ID and used during the second login step.
            </div>
        </aside>

        <aside class="card status-card" id="face-status-card">
            <h3>Registered Face</h3>
            <div id="enrollment-summary" class="enrollment-summary" hidden>
                <div class="enrollment-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="enrollment-text">
                    <p class="enrollment-headline">Face registered</p>
                    <p class="enrollment-detail" id="enrollment-count-detail">—</p>
                    <p class="enrollment-detail" id="enrollment-date-detail">—</p>
                </div>
            </div>

            <div id="enrollment-empty" class="enrollment-empty" hidden>
                <p class="enrollment-headline muted">No face registered yet</p>
                <p class="helper-text">Capture your face above to enable face login for your account.</p>
            </div>

            <button id="remove-face-btn" type="button" class="text-btn danger" hidden>Remove enrolled face</button>
        </aside>
    </div>
</div>

<style>
body {
    background: var(--bg-color, #f5f6fa);
}

.face-settings-page {
    font-family: 'Segoe UI', sans-serif;
    height: 100%;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
    background-color: var(--bg-color, #e5e7eb);
}

.tabs {
    display: flex;
    align-items: center;
    gap: 30px;
    background: #2e2e2e;
    color: white;
    padding: 15px 25px;
    min-height: 70px;
    position: sticky;
    top: 0;
    z-index: 10;
    flex-shrink: 0;
}

.tab {
    cursor: pointer;
    opacity: 0.8;
    text-decoration: none;
}

.tab.active {
    font-weight: 600;
    opacity: 1;
    position: relative;
}

.tab.active::after {
    content: '';
    position: absolute;
    bottom: -24px;
    left: 0;
    width: 100%;
    height: 3px;
    background: white;
}

.face-grid {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
    grid-template-rows: auto auto;
    gap: 24px;
    padding: 2rem;
    align-content: start;
}

.face-card {
    grid-row: 1 / span 2;
}

.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.face-card {
    display: flex;
    flex-direction: column;
    min-height: 640px;
}

.face-card h2,
.tips-card h3,
.status-card h3 {
    margin: 0 0 12px;
    font-size: 24px;
    color: #111827;
}

.status-card h3 {
    font-size: 18px;
}

.helper-text {
    margin: 0 0 24px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}

.health-banner {
    margin-bottom: 18px;
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    line-height: 1.5;
}

.health-banner.ok {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.health-banner.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.camera-shell {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

/* ===================== APPLE-FACE-ID-STYLE CAPTURE RING ===================== */

.camera-frame {
    position: relative;
    width: min(100%, 320px);
    aspect-ratio: 1 / 1;
    border-radius: 999px;
    overflow: hidden;
    background: #111827;
    transition: transform 0.25s ease;
}

.camera-frame.state-error {
    animation: frame-shake 0.4s ease;
}

@keyframes frame-shake {
    0%, 100% { transform: translateX(0); }
    20%      { transform: translateX(-6px); }
    40%      { transform: translateX(6px); }
    60%      { transform: translateX(-4px); }
    80%      { transform: translateX(4px); }
}

.camera-frame video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1); /* mirror, like a real face-id camera preview */
}

.camera-guide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.55);
    pointer-events: none;
}

.camera-guide svg {
    width: 42%;
    height: 68%;
    animation: guide-breathe 2.6s ease-in-out infinite;
}

@keyframes guide-breathe {
    0%, 100% { opacity: 0.35; }
    50%      { opacity: 0.7; }
}

.state-scanning .camera-guide svg,
.state-success .camera-guide svg,
.state-error .camera-guide svg {
    animation: none;
    opacity: 0.85;
}

/* The scanning ring itself — an SVG circle whose stroke-dashoffset animates
   from "empty" to "full" while a capture is in flight, mirroring the
   Face ID progress-ring motif. Idle: invisible. Scanning: teal, filling.
   Success: green, complete. Error: red, brief flash. */
.scan-ring {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
    pointer-events: none;
}

.scan-ring-track,
.scan-ring-progress {
    fill: none;
    stroke-width: 6;
}

.scan-ring-track {
    stroke: rgba(255, 255, 255, 0.12);
}

.scan-ring-progress {
    stroke: #2dd4bf;
    stroke-linecap: round;
    stroke-dasharray: 590.6;
    stroke-dashoffset: 590.6;
    opacity: 0;
    transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease, stroke 0.2s ease;
}

.state-scanning .scan-ring-progress {
    stroke-dashoffset: 0;
    opacity: 1;
}

.state-success .scan-ring-progress {
    stroke-dashoffset: 0;
    opacity: 1;
    stroke: #22c55e;
    transition: stroke 0.2s ease;
}

.state-error .scan-ring-progress {
    stroke-dashoffset: 0;
    opacity: 1;
    stroke: #ef4444;
    transition: stroke-dashoffset 0.3s ease, stroke 0.2s ease, opacity 0.2s ease;
}

.capture-result {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.result-icon {
    width: 64px;
    height: 64px;
    padding: 14px;
    border-radius: 999px;
    opacity: 0;
    transform: scale(0.6);
    transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.success-icon {
    color: #fff;
    background: rgba(34, 197, 94, 0.9);
}

.error-icon {
    color: #fff;
    background: rgba(239, 68, 68, 0.9);
}

.state-success .success-icon,
.state-error .error-icon {
    opacity: 1;
    transform: scale(1);
}

/* ============================================================================ */

.status-text {
    min-height: 44px;
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
}

.status-text.error {
    color: #dc2626;
}

.status-text.success {
    color: #059669;
}

.actions {
    margin-top: auto;
    padding-top: 24px;
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.primary-btn {
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #14b8a6, #2dd4bf);
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 24px;
    min-width: 180px;
    cursor: pointer;
}

.primary-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.secondary-btn {
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #fff;
    color: #374151;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 20px;
    cursor: pointer;
}

.secondary-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tips-card {
    align-self: start;
}

.tips-card ul {
    margin: 0;
    padding-left: 18px;
    color: #4b5563;
    line-height: 1.8;
}

.callout {
    margin-top: 24px;
    border-radius: 14px;
    padding: 16px;
    background: #f3f4f6;
    color: #374151;
    font-size: 14px;
    line-height: 1.6;
}

/* ===================== REGISTERED FACE STATUS CARD ===================== */

.status-card {
    align-self: start;
}

.enrollment-summary {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.enrollment-summary[hidden] {
    display: none;
}

.enrollment-icon {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
}

.enrollment-icon svg {
    width: 18px;
    height: 18px;
}

.enrollment-headline {
    margin: 0 0 4px;
    font-weight: 600;
    color: #111827;
    font-size: 14px;
}

.enrollment-headline.muted {
    color: #6b7280;
}

.enrollment-detail {
    margin: 0;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
}

.enrollment-empty .helper-text {
    margin: 0;
}

.text-btn {
    margin-top: 16px;
    border: none;
    background: none;
    padding: 0;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.text-btn.danger {
    color: #dc2626;
}

.text-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 1024px) {
    .face-grid {
        grid-template-columns: 1fr;
    }

    .face-card {
        min-height: auto;
        grid-row: auto;
    }
}
</style>

<script>
(() => {
    const video = document.getElementById('face-video');
    const canvas = document.getElementById('face-canvas');
    const status = document.getElementById('face-status');
    const cameraFrame = document.getElementById('camera-frame');
    const enrollButton = document.getElementById('enroll-face-btn');
    const replaceButton = document.getElementById('replace-face-btn');
    const removeButton = document.getElementById('remove-face-btn');
    const enrollmentSummary = document.getElementById('enrollment-summary');
    const enrollmentEmpty = document.getElementById('enrollment-empty');
    const enrollmentCountDetail = document.getElementById('enrollment-count-detail');
    const enrollmentDateDetail = document.getElementById('enrollment-date-detail');

    const enrollUrl = <?= json_encode($verificationUrl) ?>;
    const statusUrl = <?= json_encode($statusUrl ?? '') ?>;
    const clearUrl = <?= json_encode($clearUrl ?? '') ?>;
    const healthBanner = document.getElementById('face-recognition-health');
    const csrfTokenName = <?= json_encode(function_exists('csrf_token') ? csrf_token() : null) ?>;
    const csrfHash = <?= json_encode(function_exists('csrf_hash') ? csrf_hash() : null) ?>;
    let currentCsrfHash = csrfHash;
    let mediaStream = null;
    let busy = false;

    const initialEnrollment = <?= json_encode($faceEnrollmentStatus ?? ['enrolled' => false]) ?>;

    const setStatus = (message, type = '') => {
        status.textContent = message;
        status.className = 'status-text' + (type ? ' ' + type : '');
    };

    const setHealth = (ok, message) => {
        if (!healthBanner) {
            return;
        }

        healthBanner.textContent = message;
        healthBanner.className = 'health-banner ' + (ok ? 'ok' : 'error');
    };

    const setFrameState = (state) => {
        cameraFrame.classList.remove('state-scanning', 'state-success', 'state-error');
        if (state) {
            cameraFrame.classList.add('state-' + state);
        }
    };

    const formatDate = (isoLike) => {
        if (!isoLike) return '';
        const d = new Date(isoLike.replace(' ', 'T'));
        if (isNaN(d.getTime())) return isoLike;
        return d.toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: 'numeric', minute: '2-digit'
        });
    };

    const renderEnrollment = (enrollment) => {
        if (!enrollment) return;

        if (enrollment.enrolled) {
            enrollmentSummary.hidden = false;
            enrollmentEmpty.hidden = true;
            removeButton.hidden = false;
            replaceButton.hidden = false;
            enrollButton.textContent = 'Add Another Angle';

            const count = enrollment.count ?? 1;
            const max = enrollment.max ?? count;
            enrollmentCountDetail.textContent = `${count} of ${max} samples saved`;
            enrollmentDateDetail.textContent = enrollment.latest_at
                ? `Last updated: ${formatDate(enrollment.latest_at)}`
                : '';
        } else {
            enrollmentSummary.hidden = true;
            enrollmentEmpty.hidden = false;
            removeButton.hidden = true;
            replaceButton.hidden = true;
            enrollButton.textContent = 'Save Face';

            // Reset the (now-hidden) summary text too, so it matches a fresh
            // page load instead of leaving stale sample-count/date text sitting
            // in the DOM after a client-side removal.
            enrollmentCountDetail.textContent = '—';
            enrollmentDateDetail.textContent = '—';
        }
    };

    const stopCamera = () => {
        if (!mediaStream) {
            return;
        }

        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    };

    const startCamera = async () => {
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 720 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            video.srcObject = mediaStream;
            await video.play();
            if (!busy) {
                enrollButton.disabled = false;
                replaceButton.disabled = false;
            }
            setStatus('Camera ready. Position your face in the frame.');
        } catch (error) {
            enrollButton.disabled = true;
            replaceButton.disabled = true;
            setStatus('Camera access was blocked. Please allow camera permission and refresh the page.', 'error');
            console.error(error);
        }
    };

    const refreshHealth = async () => {
        if (!statusUrl) {
            return;
        }

        try {
            const response = await fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            setHealth(Boolean(data.ok), data.message || 'Face recognition status updated.');
        } catch (error) {
            console.error(error);
            setHealth(false, 'Unable to refresh face recognition status.');
        }
    };

    const captureFrame = () => {
        canvas.width = video.videoWidth || 720;
        canvas.height = video.videoHeight || 720;

        const context = canvas.getContext('2d');
        // Capture un-mirrored (the video preview is mirrored via CSS only,
        // for a natural "looking in a mirror" feel — the actual frame sent
        // to the server should match what a normal photo looks like).
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        return canvas.toDataURL('image/jpeg', 0.92);
    };

    const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    async function runCapture(replace) {
        if (!mediaStream || busy) {
            if (!mediaStream) {
                setStatus('Camera is not ready yet. Please wait a moment and try again.', 'error');
            }
            return;
        }

        busy = true;
        enrollButton.disabled = true;
        replaceButton.disabled = true;
        removeButton.disabled = true;

        setFrameState('scanning');
        setStatus(replace ? 'Replacing your enrolled face. Hold still...' : 'Saving your enrolled face. Hold still...');

        // Minimum on-screen scan duration so the ring animation always reads
        // as a deliberate scan rather than a flicker, even on a fast network.
        const minScanTime = wait(1200);

        try {
            const payload = new URLSearchParams({ image: captureFrame() });
            if (replace) payload.append('replace', '1');
            if (csrfTokenName && currentCsrfHash) payload.append(csrfTokenName, currentCsrfHash);

            const response = await fetch(enrollUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            const data = await response.json();
            await minScanTime;

            if (data.status === 'success') {
                setFrameState('success');
                setStatus(data.message || 'Your face has been enrolled successfully.', 'success');
                renderEnrollment(data.enrollment);
            } else {
                setFrameState('error');
                setStatus(data.message || 'Face enrollment failed. Please try again.', 'error');
            }

            await refreshHealth();
        } catch (error) {
            await minScanTime;
            console.error(error);
            setFrameState('error');
            setStatus('Unable to reach the face enrollment service right now. Please try again.', 'error');
            await refreshHealth();
        } finally {
            await wait(900);
            setFrameState(null);
            busy = false;
            enrollButton.disabled = false;
            replaceButton.disabled = false;
            removeButton.disabled = false;
        }
    }

    enrollButton.addEventListener('click', () => runCapture(false));

    replaceButton.addEventListener('click', () => {
        if (busy) return;
        const confirmed = confirm('Replace your enrolled face? This removes all previously saved samples and saves only the new capture.');
        if (!confirmed) return;
        runCapture(true);
    });

    removeButton.addEventListener('click', async () => {
        if (busy || !clearUrl) return;

        const confirmed = confirm('Remove your enrolled face? You will need to enroll again before face login will work.');
        if (!confirmed) return;

        busy = true;
        removeButton.disabled = true;
        enrollButton.disabled = true;
        replaceButton.disabled = true;

        try {
            const payload = new URLSearchParams();
            if (csrfTokenName && currentCsrfHash) payload.append(csrfTokenName, currentCsrfHash);

            const response = await fetch(clearUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            const data = await response.json();

            if (data.status === 'success') {
                setStatus(data.message || 'Your enrolled face has been removed.', 'success');
                renderEnrollment(data.enrollment);
            } else {
                setStatus(data.message || 'Unable to remove your enrolled face.', 'error');
            }
        } catch (error) {
            console.error(error);
            setStatus('Unable to reach the face recognition service right now.', 'error');
        } finally {
            busy = false;
            removeButton.disabled = false;
            enrollButton.disabled = mediaStream ? false : true;
            replaceButton.disabled = mediaStream ? false : true;
        }
    });

    window.addEventListener('beforeunload', stopCamera);
    enrollButton.disabled = true;
    replaceButton.disabled = true;
    renderEnrollment(initialEnrollment);
    startCamera();
    refreshHealth();
})();
</script>

<?= $this->endSection() ?>
