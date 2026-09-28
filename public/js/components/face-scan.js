/*
|--------------------------------------------------------------------------
| Shared "Face ID"-style scan component
|--------------------------------------------------------------------------
|
| Pairs with components/face_scan.php's markup: a camera circle ringed by
| N radial segments. Owns the camera, a live-detection state machine
| (no-face -> positioning -> scanning -> pass-complete -> capturing ->
| success/error), and drives which ring segments light up as the user
| moves their head. Callers (login.php, face_recognition_form.php) only
| supply an onCapture callback that POSTs the captured frame per pass and
| resolves {status, message}.
|
| Directional ring fill is a three-tier approximation (see the project's
| face-scan-v2 plan for the full rationale):
|   1. Chromium FaceDetector with landmarks -> eye/nose offset angle.
|   2. Chromium FaceDetector without landmarks (common on Windows) ->
|      bounding-box offset from frame center.
|   3. No FaceDetector at all (Firefox/Safari) -> segments fill in
|      sequential order over a fixed timer, not spatially.
*/

window.createFaceScan = function ({
    id,
    onCapture,
    scansRequired = 1,
    coverageThreshold = 0.85,
    minSegmentsPerTick = 3,
    fallbackTimerMs = 3200,
    passIntervalMs = 900,
    successHoldMs = 1500,
    errorCooldownMs = 1800,
    autoResumeAfterSuccess = true,
    onStateChange = null,
    // Background "already matched?" polling during scanning — only makes
    // sense for a read-only match/verify onCapture (login), never for an
    // enrollment onCapture (each success there WRITES a new embedding row,
    // so polling it repeatedly would silently spam duplicate samples).
    // Off by default; login.php opts in explicitly.
    earlyMatchProbe = false,
    probeIntervalMs = 1200,
    fastFillStepMs = 14,
} = {}) {

    const stage = document.getElementById(`${id}-stage`);
    const circle = document.getElementById(`${id}-circle`);
    const video = document.getElementById(`${id}-video`);
    const canvas = document.getElementById(`${id}-canvas`);
    const ring = document.getElementById(`${id}-ring`);
    const statusEl = document.getElementById(`${id}-status`);

    if (!stage || !circle || !video || !canvas || !ring) return null;

    const segmentEls = Array.from(ring.querySelectorAll('.face-scan-ring-seg'));
    const segmentCount = segmentEls.length || 1;

    let mediaStream = null;
    let faceDetector = null;
    let detectTimer = null;
    let fallbackTimer = null;
    let resumeTimer = null;
    let probeTimer = null;
    let fastFillTimer = null;
    let state = 'idle';
    let running = false;
    let paused = false;
    let probeInFlight = false;

    let passesRequired = scansRequired;
    let passesCompleted = 0;
    const touched = new Set();

    // Passive liveness signal: a rolling window of recent face position/size
    // samples from the live FaceDetector. A held-up photo or screen is
    // essentially motionless frame to frame (only detector noise), while a
    // real face — even one deliberately "held still" — drifts and changes
    // scale by a small but measurable amount as someone breathes/balances.
    // Not cryptographic-grade anti-spoofing, just enough to stop the trivial
    // "hold up a photo, get matched on the very first frame" case.
    let positionHistory = [];
    const livenessWindowMs = 2500;
    const minLivenessSamples = 4;
    const livenessMinSpread = 0.015; // fraction of frame width/height

    // True once *any* path (a background probe, or the normal
    // threshold-triggered capture) has a successful result in hand for the
    // current pass — guards against the other path also trying to finish
    // (both may be in flight at once right at the handoff moment).
    let settled = false;

    const supportsFaceDetector = () => 'FaceDetector' in window;

    function setState(next) {
        state = next;
        stage.classList.remove(
            'state-no-face', 'state-positioning', 'state-scanning',
            'state-pass-complete', 'state-capturing', 'state-success', 'state-error'
        );
        if (next !== 'idle') stage.classList.add(`state-${next}`);
        if (typeof onStateChange === 'function') onStateChange(next);
    }

    function setStatus(message, tone = '') {
        if (!statusEl) return;
        statusEl.textContent = message;
        statusEl.className = 'face-scan-status' + (tone ? ` tone-${tone}` : '');
    }

    function resetPassProgress() {
        touched.clear();
        segmentEls.forEach((el) => el.classList.remove('filled'));
        settled = false;
        positionHistory = [];
    }

    function recordMovementSample(box, vw, vh) {
        const now = performance.now();
        positionHistory.push({
            x: (box.x + box.width / 2) / vw,
            y: (box.y + box.height / 2) / vh,
            size: box.width / vw,
            t: now,
        });
        const cutoff = now - livenessWindowMs;
        while (positionHistory.length && positionHistory[0].t < cutoff) positionHistory.shift();
    }

    function hasLivenessSignal() {
        if (positionHistory.length < minLivenessSamples) return false;

        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        let minS = Infinity, maxS = -Infinity;

        for (const p of positionHistory) {
            if (p.x < minX) minX = p.x; if (p.x > maxX) maxX = p.x;
            if (p.y < minY) minY = p.y; if (p.y > maxY) maxY = p.y;
            if (p.size < minS) minS = p.size; if (p.size > maxS) maxS = p.size;
        }

        const spread = Math.max(maxX - minX, maxY - minY, maxS - minS);
        return spread >= livenessMinSpread;
    }

    function beginCycle(opts) {
        passesRequired = (opts && opts.scansRequired) || scansRequired;
        passesCompleted = 0;
        resetPassProgress();
    }

    function clearAllTimers() {
        if (detectTimer) { clearInterval(detectTimer); detectTimer = null; }
        if (fallbackTimer) { clearInterval(fallbackTimer); fallbackTimer = null; }
        if (resumeTimer) { clearTimeout(resumeTimer); resumeTimer = null; }
        if (probeTimer) { clearInterval(probeTimer); probeTimer = null; }
        if (fastFillTimer) { clearInterval(fastFillTimer); fastFillTimer = null; }
    }

    /*
    |--------------------------------------------------------------------------
    | DIRECTION ESTIMATION (tiers 1 & 2 — see file header)
    |--------------------------------------------------------------------------
    */

    function normalizeAngle(deg) {
        return ((deg % 360) + 360) % 360;
    }

    // Base (unrotated) segment points straight down from the ring's center;
    // CSS rotate() is visually clockwise. Solving rotate(rx,ry)=(dx,dy) for a
    // segment that starts at (0,1) gives theta = atan2(-dx, dy). Video is
    // mirrored for the on-screen preview (scaleX(-1)) but detection runs on
    // the raw unmirrored frame, so dx is negated once, centrally, below.
    function angleForOffset(dx, dy) {
        dx = -dx; // account for the mirrored preview
        return normalizeAngle(Math.atan2(-dx, dy) * 180 / Math.PI);
    }

    function estimateDirection(face, vw, vh) {
        const box = face.boundingBox;

        if (Array.isArray(face.landmarks) && face.landmarks.length) {
            // The Shape Detection spec doesn't pin down whether each eye is
            // its own landmark entry (two 'eye' entries, one point each) or
            // a single 'eye' entry carrying both points — flatMap handles
            // either shape the same way.
            const eyeLocations = face.landmarks
                .filter((l) => l.type === 'eye')
                .flatMap((l) => l.locations || []);
            const noseLandmark = face.landmarks.find((l) => l.type === 'nose' && l.locations && l.locations.length);
            const noseLocation = noseLandmark && noseLandmark.locations[0];

            if (eyeLocations.length >= 2 && noseLocation) {
                const eyeMidX = (eyeLocations[0].x + eyeLocations[1].x) / 2;
                const eyeMidY = (eyeLocations[0].y + eyeLocations[1].y) / 2;
                const dx = noseLocation.x - eyeMidX;
                const dy = noseLocation.y - eyeMidY;

                if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5) {
                    return angleForOffset(dx, dy);
                }
            }
        }

        // Tier 2: bounding-box offset from the frame center.
        const cx = box.x + box.width / 2;
        const cy = box.y + box.height / 2;
        const dx = cx - vw / 2;
        const dy = cy - vh / 2;

        if (Math.abs(dx) < vw * 0.02 && Math.abs(dy) < vh * 0.02) return null;

        return angleForOffset(dx, dy);
    }

    function fillSegmentsNear(angleDeg) {
        const segAngle = 360 / segmentCount;
        const centerIndex = Math.round(angleDeg / segAngle) % segmentCount;
        const spread = Math.floor(minSegmentsPerTick / 2);

        for (let k = -spread; k <= spread; k++) {
            const idx = ((centerIndex + k) % segmentCount + segmentCount) % segmentCount;
            if (!touched.has(idx)) {
                touched.add(idx);
                segmentEls[idx].classList.add('filled');
            }
        }
    }

    function isCentered(box) {
        const vw = video.videoWidth || 1;
        const vh = video.videoHeight || 1;
        const cx = box.x + box.width / 2;
        const cy = box.y + box.height / 2;
        const dxRatio = Math.abs(cx - vw / 2) / vw;
        const dyRatio = Math.abs(cy - vh / 2) / vh;
        const sizeRatio = box.width / vw;
        return dxRatio < 0.22 && dyRatio < 0.22 && sizeRatio > 0.22 && sizeRatio < 0.85;
    }

    function checkPassCompletion() {
        if (touched.size / segmentCount >= coverageThreshold) {
            reachPassThreshold();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DETECTION LOOP (tier 1/2 — live FaceDetector)
    |--------------------------------------------------------------------------
    */

    async function runDetection() {
        if (!running || paused) return;
        if (state !== 'no-face' && state !== 'positioning' && state !== 'scanning') return;
        if (video.readyState < 2) return;

        try {
            const faces = await faceDetector.detect(video);

            // Re-validate after the await: detect() can take long enough
            // for a prior tick's own in-flight call to resolve after we've
            // already moved on (e.g. into 'capturing' once the ring filled).
            // Without this, a stale tick could revert the state back to
            // 'scanning' mid-capture and even re-trigger reachPassThreshold()
            // while a capture POST is already in flight.
            if (!running || paused) return;
            if (state !== 'no-face' && state !== 'positioning' && state !== 'scanning') return;

            if (!faces || !faces.length) {
                if (state !== 'no-face') { setState('no-face'); setStatus('Position your face within the frame.'); }
                return;
            }

            const face = faces[0];

            recordMovementSample(face.boundingBox, video.videoWidth || 1, video.videoHeight || 1);

            if (!isCentered(face.boundingBox)) {
                if (state !== 'positioning') { setState('positioning'); setStatus('Keep your face centered.'); }
                return;
            }

            if (state !== 'scanning') { setState('scanning'); setStatus('Move your head slowly to complete the circle.'); }

            const angle = estimateDirection(face, video.videoWidth, video.videoHeight);
            if (angle != null) fillSegmentsNear(angle);

            checkPassCompletion();
        } catch (error) {
            console.error('Face detection error:', error);
        }
    }

    function armLiveDetection() {
        if (paused) return;

        try {
            faceDetector = faceDetector || new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
        } catch (error) {
            console.error('FaceDetector unavailable:', error);
            armFallback();
            return;
        }

        setState('no-face');
        setStatus('Position your face within the frame.');
        detectTimer = setInterval(runDetection, 220);
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACK LOOP (tier 3 — no FaceDetector support)
    |--------------------------------------------------------------------------
    */

    function armFallback() {
        if (paused) return;
        faceDetector = null;
        setState('scanning');
        setStatus('Center your face and hold still.');

        let nextIndex = 0;
        const stepMs = Math.max(40, fallbackTimerMs / segmentCount);

        fallbackTimer = setInterval(() => {
            if (!running || paused) { clearInterval(fallbackTimer); fallbackTimer = null; return; }

            if (nextIndex < segmentCount && !touched.has(nextIndex)) {
                touched.add(nextIndex);
                segmentEls[nextIndex].classList.add('filled');
            }
            nextIndex++;

            if (touched.size / segmentCount >= coverageThreshold) {
                clearInterval(fallbackTimer);
                fallbackTimer = null;
                reachPassThreshold();
            }
        }, stepMs);
    }

    function armDetectionLoop() {
        if (paused) return;
        armProbe();
        if (supportsFaceDetector()) armLiveDetection(); else armFallback();
    }

    /*
    |--------------------------------------------------------------------------
    | BACKGROUND "ALREADY MATCHED?" PROBE (earlyMatchProbe, login only)
    |--------------------------------------------------------------------------
    |
    | Fires the same onCapture the normal ring-completion path would, but on
    | a slower interval and silently — a miss just means "not yet", not an
    | error. The first probe (or the normal threshold-triggered capture,
    | whichever wins the race) to get a success result settles the pass;
    | the other is ignored via the `settled` guard. A success skips waiting
    | for the ring to fill from head movement and instead fast-fills the
    | remaining segments, since verify() consumes a one-time session flag
    | and can't safely be called again after a match.
    */

    function armProbe() {
        if (!earlyMatchProbe || typeof onCapture !== 'function') return;
        if (probeTimer) { clearInterval(probeTimer); probeTimer = null; }
        runProbe();
        probeTimer = setInterval(runProbe, probeIntervalMs);
    }

    async function runProbe() {
        if (!running || paused || settled || probeInFlight) return;
        if (state !== 'positioning' && state !== 'scanning') return;
        if (video.readyState < 2) return;
        // Require some observed natural movement before letting the
        // background probe submit a frame — otherwise a photo/screen held
        // up to the camera gets matched instantly, before the ring even
        // starts filling. See positionHistory/hasLivenessSignal above.
        if (!hasLivenessSignal()) return;

        probeInFlight = true;
        let result;
        try {
            result = await onCapture(captureFrame());
        } catch (error) {
            return;
        } finally {
            probeInFlight = false;
        }

        if (!running || paused || settled) return;
        if (!result || result.status !== 'success') return;

        settled = true;
        clearAllTimers();
        setState('pass-complete');
        fastFillRemaining(() => finishWithResult(result));
    }

    function fastFillRemaining(onDone) {
        const remaining = [];
        for (let i = 0; i < segmentCount; i++) {
            if (!touched.has(i)) remaining.push(i);
        }

        if (!remaining.length) { onDone(); return; }

        let idx = 0;
        fastFillTimer = setInterval(() => {
            const segIdx = remaining[idx];
            touched.add(segIdx);
            segmentEls[segIdx].classList.add('filled');
            idx++;

            if (idx >= remaining.length) {
                clearInterval(fastFillTimer);
                fastFillTimer = null;
                onDone();
            }
        }, fastFillStepMs);
    }

    /*
    |--------------------------------------------------------------------------
    | PASS COMPLETION -> CAPTURE
    |--------------------------------------------------------------------------
    */

    function reachPassThreshold() {
        clearAllTimers();
        setState('pass-complete');
        segmentEls.forEach((el) => el.classList.add('filled'));
        capture();
    }

    function captureFrame() {
        canvas.width = video.videoWidth || 720;
        canvas.height = video.videoHeight || 720;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', 0.92);
    }

    async function capture() {
        setState('capturing');
        setStatus('Capturing...');

        if (typeof onCapture !== 'function') return;

        let result;
        try {
            result = await onCapture(captureFrame());
        } catch (error) {
            console.error(error);
            result = { status: 'error', message: 'Something went wrong. Please try again.' };
        }

        if (!running || paused || settled) return; // a background probe already won, or we were paused/stopped, while this was in flight

        if (result && result.status === 'success') settled = true;
        finishWithResult(result);
    }

    function finishWithResult(result) {
        if (!running) return;

        if (result && result.status === 'success') {
            passesCompleted++;

            if (passesCompleted < passesRequired) {
                setStatus(result.message || (passesCompleted === 1 ? 'First scan complete' : `Scan ${passesCompleted} complete`), 'success');
                resumeTimer = setTimeout(() => {
                    resumeTimer = null;
                    if (!running || paused) return;
                    resetPassProgress();
                    armDetectionLoop();
                }, passIntervalMs);
            } else {
                setState('success');
                setStatus(result.message || 'Face scan complete', 'success');

                if (autoResumeAfterSuccess) {
                    resumeTimer = setTimeout(() => {
                        resumeTimer = null;
                        if (!running || paused) return;
                        beginCycle();
                        armDetectionLoop();
                    }, successHoldMs);
                }
            }
        } else {
            setState('error');
            setStatus((result && result.message) || 'Face capture failed. Please try again.', 'error');

            resumeTimer = setTimeout(() => {
                resumeTimer = null;
                if (!running || paused) return;
                resetPassProgress();
                armDetectionLoop();
            }, errorCooldownMs);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CAMERA LIFECYCLE
    |--------------------------------------------------------------------------
    */

    async function startCamera() {
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                audio: false
            });
            video.srcObject = mediaStream;
            await video.play();
            circle.classList.add('has-video');
            armDetectionLoop();
        } catch (error) {
            console.error(error);
            // No automatic retry path for a blocked/unavailable camera — the
            // caller's onStateChange('error') is the cue to surface a manual
            // retry affordance.
            setState('error');
            setStatus('Camera access was blocked. Please allow camera permission and try again.', 'error');
        }
    }

    function stopCamera() {
        if (!mediaStream) return;
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
        circle.classList.remove('has-video');
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC API
    |--------------------------------------------------------------------------
    */

    function start(opts) {
        if (running) return;
        running = true;
        paused = false;
        beginCycle(opts);
        setState('idle');
        startCamera();
    }

    function stop() {
        running = false;
        clearAllTimers();
        stopCamera();
        setState('idle');
        resetPassProgress();
    }

    function pause() {
        paused = true;
        clearAllTimers();
        resetPassProgress();
        setState('idle');
    }

    function resume(opts) {
        if (!running) return;
        paused = false;
        beginCycle(opts);
        if (!mediaStream) {
            startCamera();
            return;
        }
        armDetectionLoop();
    }

    function destroy() {
        stop();
    }

    return { start, stop, pause, resume, destroy, setStatus, getState: () => state };
};
