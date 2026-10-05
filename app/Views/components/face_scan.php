<?php
/*
|--------------------------------------------------------------------------
| Shared "Face ID"-style scan widget — markup only
|--------------------------------------------------------------------------
|
| Params:
|   $id       (string) element id prefix, e.g. "login-face" / "enroll-face".
|   $size     (int, optional) camera circle diameter in px. Default 220.
|   $hint     (string, optional) initial status text.
|   $segments (int, optional) number of radial ring ticks. Default 68.
|   $ringGap  (int, optional) gap in px between the circle edge and the ring. Default 16.
|   $segLen   (int, optional) length of each ring segment in px. Default 28.
|   $segWidth (int, optional) width of each ring segment in px. Default 4.
|
| The segment count here is the single source of truth — public/js/components/
| face-scan.js reads the rendered <span> count from the DOM rather than
| taking a duplicate number, so PHP and JS can never drift out of sync.
|
| Paired with window.createFaceScan(). Used by both auth/login.php and
| client_portal/modals/face_recognition_form.php.
*/

$id = $id ?? 'face-scan';
$size = (int) ($size ?? 220);
$hint = $hint ?? 'Starting camera...';
$segments = (int) ($segments ?? 68);
$ringGap = (int) ($ringGap ?? 16);
$segLen = (int) ($segLen ?? 28);
$segWidth = (int) ($segWidth ?? 4);
?>
<div class="face-scan-wrap" id="<?= esc($id) ?>-wrap">

    <div class="face-scan-stage" id="<?= esc($id) ?>-stage" style="--fs-size: <?= $size ?>px; --fs-ring-gap: <?= $ringGap ?>px; --fs-seg-len: <?= $segLen ?>px; --fs-seg-w: <?= $segWidth ?>px;">

        <div class="face-scan-ring" id="<?= esc($id) ?>-ring">
            <?php for ($i = 0; $i < $segments; $i++): ?>
                <span class="face-scan-ring-seg" style="--fs-seg-angle: <?= round(360 / $segments * $i, 2) ?>deg;"></span>
            <?php endfor; ?>
        </div>

        <div class="face-scan-circle" id="<?= esc($id) ?>-circle">

            <video id="<?= esc($id) ?>-video" autoplay playsinline muted></video>
            <canvas id="<?= esc($id) ?>-canvas" hidden></canvas>

            <div class="face-scan-guides" aria-hidden="true">
                <span class="face-scan-guide-v"></span>
                <svg class="face-scan-guide-h" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M8,38 Q50,29 92,38" />
                </svg>
            </div>

            <div class="face-scan-icon face-scan-icon-face" id="<?= esc($id) ?>-icon-face">
                <svg viewBox="0 0 64 64" fill="none">
                    <path d="M18 10h-6a2 2 0 0 0-2 2v6" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M46 10h6a2 2 0 0 1 2 2v6" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M18 54h-6a2 2 0 0 1-2-2v-6" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M46 54h6a2 2 0 0 0 2-2v-6" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <circle cx="32" cy="32" r="12" stroke="currentColor" stroke-width="3" />
                    <circle cx="27" cy="29" r="1.5" fill="currentColor" />
                    <circle cx="37" cy="29" r="1.5" fill="currentColor" />
                    <path d="M27 36c1.7 2 3.4 3 5 3s3.3-1 5-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                </svg>
            </div>

            <div class="face-scan-icon face-scan-icon-check" id="<?= esc($id) ?>-icon-check">
                <svg viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div class="face-scan-icon face-scan-icon-error" id="<?= esc($id) ?>-icon-error">
                <svg viewBox="0 0 24 24">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                </svg>
            </div>

        </div>

    </div>

    <p class="face-scan-status" id="<?= esc($id) ?>-status"><?= esc($hint) ?></p>

</div>
