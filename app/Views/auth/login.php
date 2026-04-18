<?= $this->extend('layouts/auth_layout') ?>
<?= $this->section('content') ?>

<?php
    $showFaceModal = (bool) ($showFaceVerification ?? false);
    $verificationUrl = $verificationUrl ?? site_url('/face-verify');
    $postLoginRedirect = $postLoginRedirect ?? site_url('/client_portal/dashboard');
?>

<div class="relative flex min-h-screen items-center justify-center m-auto w-full px-6 py-12 bg-[#FAF9F6]">
    <div class="flex w-full max-w-6xl h-[80vh] rounded-2xl overflow-hidden shadow-lg">
        <div class="w-2/3 flex items-center justify-center bg-white px-12 lg:px-24">
            <div class="w-full max-w-md">
                <div class="mb-10">
                    <div class="text-gray-400 font-bold text-lg mb-1">Accounting System</div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">Welcome Back</h1>
                    <p class="text-gray-500 text-lg">
                        Enter your email and password to access your account.
                    </p>
                    <p class="mt-2 text-sm text-gray-400">
                        A face verification step will be required after sign in.
                    </p>
                </div>

                <?php if (session('error')): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <?= esc(session('error')) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= url_to('login') ?>" method="post" class="space-y-6">
                    <?= csrf_field() ?>

                    <div>
                        <input
                            type="text"
                            name="email"
                            value="<?= old('email') ?>"
                            placeholder="Email or username"
                            class="w-full px-5 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400"
                            required
                        >
                    </div>

                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Password" class="w-full px-5 py-4 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400" required>
                        <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                            <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-3 text-gray-600 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                            >
                            <span>Stay logged in?</span>
                        </label>

                        <a href="#" class="text-purple-600 hover:text-purple-800 hover:underline font-medium">
                            Forgot password?
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-black text-white py-4 rounded-full font-semibold text-lg hover:bg-gray-900 transition duration-200 shadow-md"
                    >
                        Login
                    </button>
                </form>

                <p class="mt-8 text-center text-gray-500 text-sm">
                    By continuing, you agree to our
                    <span class="font-semibold text-gray-700">Terms</span> and
                    <span class="font-semibold text-gray-700">Privacy Policy</span>
                </p>

                <p class="mt-6 text-center text-gray-400 text-xs">
                    Copyright © 2026 ONESYS Primesolutions Co
                </p>
            </div>
        </div>
        <div class="hidden lg:block w-1/3 flex-shrink-0 bg-cover bg-center"
            style="background-image: url('<?= base_url('images/background/building.png') ?>');">
        </div>
    </div>

    <?php if ($showFaceModal): ?>
        <div class="absolute inset-0" style="background: rgba(3, 3, 2, 0.55); backdrop-filter: blur(3px);"></div>

        <div class="absolute inset-0 z-10 flex items-center justify-center px-4 py-10">
            <div class="w-full bg-white shadow-xl" style="max-width: 470px; min-height: 440px; border-radius: 18px; padding: 32px 34px; display: flex; flex-direction: column;">
                <div class="mb-4 flex items-center justify-between" style="margin-bottom: 14px;">
                    <div class="w-5"></div>
                    <h2 class="text-xl font-bold" style="color: #962323;">Face Recognition</h2>
                    <a href="<?= site_url('/logout') ?>" class="text-xl leading-none text-gray-400 transition hover:text-gray-600">&times;</a>
                </div>

                <div id="scan-state" style="flex: 1; display: flex; flex-direction: column;">
                    <div id="face-scan-shell" class="relative mx-auto mt-4 overflow-hidden rounded-full bg-gray-200" style="width: 164px; height: 164px; margin-top: 10px;">
                        <video id="face-video" autoplay playsinline muted style="width: 164px; height: 164px; object-fit: cover; display: block;"></video>
                        <div class="pointer-events-none absolute inset-0" style="background: rgba(229, 231, 235, 0.55);"></div>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center text-gray-700">
                            <svg viewBox="0 0 64 64" fill="none" style="width: 60px; height: 60px;">
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
                        <div id="face-scan-line" class="pointer-events-none absolute top-1/2 -translate-y-1/2 rounded-full" style="left: 22px; right: 22px; height: 4px; background: #18c6b3; box-shadow: 0 0 14px rgba(24, 198, 179, 0.65);"></div>
                    </div>

                    <canvas id="face-canvas" class="hidden"></canvas>

                    <div class="mt-6 text-center" style="margin-top: 28px;">
                        <h3 class="font-bold leading-tight" style="font-size: 32px; color: #962323;">Scanning your face</h3>
                        <p id="face-status" class="my-2 text-xs text-gray-400" style="min-height: 40px; display: flex; align-items: center; justify-content: center; padding: 0 16px; text-align: center; font-size: 13px;">
                            Scan your face to verify your identity.
                        </p>
                    </div>

                    <div style="margin-top: auto; padding-top: 14px;">
                        <button
                            id="verify-face-btn"
                            type="button"
                            class="w-full text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-gray-300"
                            style="border-radius: 8px; padding: 13px 18px; background: #18c6b3; font-size: 15px;"
                        >
                            <span id="verify-face-btn-label">Scan Now</span>
                        </button>
                    </div>
                </div>

                <div id="success-state" class="hidden" style="flex: 1; display: none; flex-direction: column;">
                    <div class="relative mx-auto mt-4 rounded-full bg-gray-200" style="width: 164px; height: 164px; overflow: visible; margin-top: 10px;">
                        <div class="absolute inset-0 overflow-hidden rounded-full">
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center text-gray-700">
                                <svg viewBox="0 0 64 64" fill="none" style="width: 60px; height: 60px;">
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
                        </div>
                        <div class="face-success-badge absolute flex items-center justify-center rounded-full text-white shadow-lg" style="right: 10px; top: 10px; width: 30px; height: 30px; background: #18c6b3; z-index: 20;">
                            <svg viewBox="0 0 20 20" class="fill-current" style="width: 15px; height: 15px;">
                                <path d="M7.8 13.6 4.7 10.5l-1.4 1.4 4.5 4.5 9-9-1.4-1.4z"/>
                            </svg>
                        </div>
                        <div class="face-success-ring absolute my-2" style="inset: -10px; border-radius: 999px; border: 2px solid rgba(24, 198, 179, 0.28);"></div>
                    </div>

                    <div class="mt-6 text-center" style="margin-top: 28px;">
                        <h3 class="font-bold leading-tight" style="font-size: 36px; color: #962323;">Identity Verified!</h3>
                        <p class="text-xs text-gray-400" style="min-height: 40px; display: flex; align-items: center; justify-content: center; padding: 0 16px; text-align: center; font-size: 13px;">Redirecting you to your dashboard...</p>
                    </div>

                    <div style="margin-top: auto; display: flex; justify-content: center; padding-top: 14px;">
                        <div class="face-redirect-loader" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>

<?php if ($showFaceModal): ?>
<style>
@keyframes faceSuccessPulse {
    0% { transform: scale(0.92); opacity: 0.45; }
    70% { transform: scale(1.08); opacity: 0; }
    100% { transform: scale(1.08); opacity: 0; }
}

@keyframes faceSuccessBadge {
    0% { transform: scale(0.6); opacity: 0; }
    65% { transform: scale(1.08); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes faceRedirectSpin {
    to { transform: rotate(360deg); }
}

@keyframes faceScanSweep {
    0% { transform: translateY(-22px); opacity: 0.55; }
    50% { transform: translateY(22px); opacity: 1; }
    100% { transform: translateY(-22px); opacity: 0.55; }
}

@keyframes faceScanShellPulse {
    0% { box-shadow: 0 0 0 0 rgba(24, 198, 179, 0); }
    50% { box-shadow: 0 0 0 8px rgba(24, 198, 179, 0.14); }
    100% { box-shadow: 0 0 0 0 rgba(24, 198, 179, 0); }
}

.face-success-ring {
    animation: faceSuccessPulse 1.5s ease-out infinite;
}

.face-success-badge {
    animation: faceSuccessBadge 360ms ease-out both;
}

.face-redirect-loader {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    border: 3px solid rgba(24, 198, 179, 0.2);
    border-top-color: #18c6b3;
    animation: faceRedirectSpin 0.8s linear infinite;
}

.face-scan-shell-verifying {
    animation: faceScanShellPulse 1.1s ease-in-out infinite;
}

.face-scan-line-verifying {
    animation: faceScanSweep 1.2s ease-in-out infinite;
}
</style>
<script>
(() => {
    const video = document.getElementById('face-video');
    const canvas = document.getElementById('face-canvas');
    const scanShell = document.getElementById('face-scan-shell');
    const scanLine = document.getElementById('face-scan-line');
    const verifyButton = document.getElementById('verify-face-btn');
    const verifyButtonLabel = document.getElementById('verify-face-btn-label');
    const status = document.getElementById('face-status');
    const scanState = document.getElementById('scan-state');
    const successState = document.getElementById('success-state');
    const verifyUrl = <?= json_encode($verificationUrl) ?>;
    const redirectUrlFallback = <?= json_encode($postLoginRedirect) ?>;
    const csrfTokenName = <?= json_encode(function_exists('csrf_token') ? csrf_token() : null) ?>;
    const csrfHash = <?= json_encode(function_exists('csrf_hash') ? csrf_hash() : null) ?>;
    let mediaStream = null;
    let redirectUrl = redirectUrlFallback;
    let redirectTimer = null;

    const setStatus = (message, isError = false) => {
        status.textContent = message;
        status.className = isError ? 'my-2 text-xs text-red-500' : 'my-2 text-xs text-gray-400';
    };

    const setVerifyingState = (isVerifying) => {
        if (scanShell) {
            scanShell.classList.toggle('face-scan-shell-verifying', isVerifying);
        }

        if (scanLine) {
            scanLine.classList.toggle('face-scan-line-verifying', isVerifying);
        }

        if (verifyButtonLabel) {
            verifyButtonLabel.textContent = isVerifying ? 'Verifying Face...' : 'Scan Now';
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
            verifyButton.disabled = false;
            setStatus('Camera ready. Click scan now to verify your identity.');
        } catch (error) {
            verifyButton.disabled = true;
            setStatus('Camera access was blocked. Please allow camera permission and refresh the page.', true);
            console.error(error);
        }
    };

    const captureFrame = () => {
        canvas.width = video.videoWidth || 720;
        canvas.height = video.videoHeight || 720;

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        return canvas.toDataURL('image/jpeg', 0.92);
    };

    verifyButton.addEventListener('click', async () => {
        if (!mediaStream) {
            setStatus('Camera is not ready yet. Please wait a moment and try again.', true);
            return;
        }

        verifyButton.disabled = true;
        setVerifyingState(true);
        setStatus('Verifying your face. Hold still for a moment.');

        try {
            const payload = new URLSearchParams({
                image: captureFrame()
            });

            if (csrfTokenName && csrfHash) {
                payload.append(csrfTokenName, csrfHash);
            }

            const response = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            const data = await response.json();

            if (data.status === 'success') {
                redirectUrl = data.redirect || redirectUrlFallback;
                scanState.classList.add('hidden');
                scanState.style.display = 'none';
                successState.classList.remove('hidden');
                successState.style.display = 'flex';
                setStatus(data.message || 'Verification successful.');
                stopCamera();
                if (redirectTimer) {
                    window.clearTimeout(redirectTimer);
                }
                redirectTimer = window.setTimeout(() => {
                    window.location.assign(redirectUrl);
                }, 1400);
                return;
            }

            setStatus(data.message || 'Face verification failed. Please try again.', true);
        } catch (error) {
            console.error(error);
            setStatus('Unable to reach the verification service right now. Please try again.', true);
        } finally {
            setVerifyingState(false);
            verifyButton.disabled = false;
        }
    });

    window.addEventListener('beforeunload', () => {
        stopCamera();
        if (redirectTimer) {
            window.clearTimeout(redirectTimer);
        }
    });
    verifyButton.disabled = true;
    startCamera();
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
