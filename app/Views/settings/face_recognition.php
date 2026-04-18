<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="face-settings-page">
    <div class="tabs">
        <a href="<?= site_url('settings/customization') ?>" class="tab">Customization</a>
        <a href="<?= site_url('settings/face-recognition') ?>" class="tab active">Face Recognition</a>
    </div>

    <div class="face-grid">
        <section class="card face-card">
            <div id="compreFace-health" class="health-banner <?= !empty($compreFaceHealth['ok']) ? 'ok' : 'error' ?>">
                <?= esc((string) ($compreFaceHealth['message'] ?? 'Checking CompreFace status...')) ?>
            </div>
            <div>
                <h2>Enroll Your Face</h2>
                <p class="helper-text">
                    Capture a clear front-facing photo so the login face verification can recognize your account.
                </p>
            </div>

            <div class="camera-shell">
                <div class="camera-frame">
                    <video id="face-video" autoplay playsinline muted></video>
                    <canvas id="face-canvas" hidden></canvas>
                    <div class="camera-mask"></div>
                    <div class="camera-guide">
                        <svg viewBox="0 0 64 64" fill="none">
                            <path d="M18 10h-6a2 2 0 0 0-2 2v6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M46 10h6a2 2 0 0 1 2 2v6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M18 54h-6a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M46 54h6a2 2 0 0 0 2-2v-6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="32" cy="32" r="12" stroke="currentColor" stroke-width="3"/>
                            <circle cx="27" cy="29" r="1.5" fill="currentColor"/>
                            <circle cx="37" cy="29" r="1.5" fill="currentColor"/>
                            <path d="M27 36c1.7 2 3.4 3 5 3s3.3-1 5-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="scan-line"></div>
                </div>
            </div>

            <p id="face-status" class="status-text">Camera is starting. Allow browser access if prompted.</p>

            <div class="actions">
                <button id="enroll-face-btn" type="button" class="primary-btn">Save Face</button>
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
    background-color: #e5e7eb;
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
    gap: 24px;
    padding: 2rem;
    align-content: start;
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
.tips-card h3 {
    margin: 0 0 12px;
    font-size: 24px;
    color: #111827;
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

.camera-frame {
    position: relative;
    width: min(100%, 360px);
    aspect-ratio: 1 / 1;
    border-radius: 999px;
    overflow: hidden;
    background: #e5e7eb;
}

.camera-frame video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.camera-mask {
    position: absolute;
    inset: 0;
    background: rgba(229, 231, 235, 0.55);
}

.camera-guide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4b5563;
}

.camera-guide svg {
    width: 76px;
    height: 76px;
}

.scan-line {
    position: absolute;
    left: 48px;
    right: 48px;
    top: 50%;
    height: 4px;
    transform: translateY(-50%);
    border-radius: 999px;
    background: #18c6b3;
    box-shadow: 0 0 14px rgba(24, 198, 179, 0.65);
}

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

@media (max-width: 1024px) {
    .face-grid {
        grid-template-columns: 1fr;
    }

    .face-card {
        min-height: auto;
    }
}
</style>

<script>
(() => {
    const video = document.getElementById('face-video');
    const canvas = document.getElementById('face-canvas');
    const status = document.getElementById('face-status');
    const enrollButton = document.getElementById('enroll-face-btn');
    const enrollUrl = <?= json_encode($verificationUrl) ?>;
    const statusUrl = <?= json_encode($statusUrl ?? '') ?>;
    const healthBanner = document.getElementById('compreFace-health');
    const csrfTokenName = <?= json_encode(function_exists('csrf_token') ? csrf_token() : null) ?>;
    const csrfHash = <?= json_encode(function_exists('csrf_hash') ? csrf_hash() : null) ?>;
    let mediaStream = null;

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
            enrollButton.disabled = false;
            setStatus('Camera ready. Capture and save your current face for login verification.');
        } catch (error) {
            enrollButton.disabled = true;
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
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            setHealth(Boolean(data.ok), data.message || 'CompreFace status updated.');
        } catch (error) {
            console.error(error);
            setHealth(false, 'Unable to refresh CompreFace status.');
        }
    };

    const captureFrame = () => {
        canvas.width = video.videoWidth || 720;
        canvas.height = video.videoHeight || 720;

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        return canvas.toDataURL('image/jpeg', 0.92);
    };

    enrollButton.addEventListener('click', async () => {
        if (!mediaStream) {
            setStatus('Camera is not ready yet. Please wait a moment and try again.', 'error');
            return;
        }

        enrollButton.disabled = true;
        setStatus('Saving your enrolled face. Hold still for a moment.');

        try {
            const payload = new URLSearchParams({
                image: captureFrame()
            });

            if (csrfTokenName && csrfHash) {
                payload.append(csrfTokenName, csrfHash);
            }

            const response = await fetch(enrollUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            const data = await response.json();

            if (data.status === 'success') {
                setStatus(data.message || 'Your face has been enrolled successfully.', 'success');
                await refreshHealth();
                return;
            }

            setStatus(data.message || 'Face enrollment failed. Please try again.', 'error');
            await refreshHealth();
        } catch (error) {
            console.error(error);
            setStatus('Unable to reach the face enrollment service right now. Please try again.', 'error');
            await refreshHealth();
        } finally {
            enrollButton.disabled = false;
        }
    });

    window.addEventListener('beforeunload', stopCamera);
    enrollButton.disabled = true;
    startCamera();
    refreshHealth();
})();
</script>

<?= $this->endSection() ?>
