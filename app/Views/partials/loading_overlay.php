<?php
    // Same source as the sidebar logo and favicon (Settings > Customization);
    // falls back to the default favicon mark when no logo has been uploaded yet.
    $overlayLogoUrl = ! empty($activeCompanyBranding['logo_url'] ?? null)
        ? $activeCompanyBranding['logo_url']
        : base_url('favicon.ico');
?>
<style>
    /*
     * Fully self-contained: every rule needed to lay this out lives here.
     * Do NOT rely on Tailwind utility classes (w-36, h-96, gap-0.5, etc.) --
     * main.css/tailwind.css in this project are pre-purged builds that only
     * keep classes already used elsewhere, so an unused utility silently
     * resolves to nothing (e.g. missing height collapses a container) and
     * the overlay renders differently depending on what else happens to be
     * on the page.
     */
    @keyframes loadingRingSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes loadingLogoPulse {
        0%, 100% {
            transform: scale(1);
            filter: drop-shadow(0 4px 12px rgba(14, 165, 233, 0.15));
        }
        50% {
            transform: scale(1.035);
            filter: drop-shadow(0 8px 24px rgba(37, 99, 235, 0.28));
        }
    }

    @keyframes loadingDotBlink {
        0%, 20% { opacity: 0; transform: translateY(0); }
        50% { opacity: 1; transform: translateY(-2px); }
        80%, 100% { opacity: 0; transform: translateY(0); }
    }

    #app-loading-overlay {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #FDFDFE;
        transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #app-loading-overlay.is-hidden {
        opacity: 0;
        transform: scale(1.015);
        pointer-events: none;
    }

    .app-loading-glow {
        position: absolute;
        width: 384px;
        height: 384px;
        border-radius: 9999px;
        pointer-events: none;
        background: radial-gradient(circle, rgba(56,189,248,0.08), rgba(132,204,22,0.08));
        filter: blur(64px);
    }

    .app-loading-inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0 16px;
    }

    .app-loading-ring-box {
        position: relative;
        width: 144px;
        height: 144px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }

    .app-loading-ring {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        animation: loadingRingSpin 1.6s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite;
    }

    .app-loading-logo {
        position: relative;
        z-index: 1;
        width: 80px;
        height: 80px;
        border-radius: 9999px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: loadingLogoPulse 2.2s ease-in-out infinite;
    }

    .app-loading-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 8px;
    }

    .app-loading-text {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #334155;
        font-weight: 500;
        font-size: 14px;
        letter-spacing: 0.02em;
    }

    .app-loading-dots {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-left: 2px;
    }

    .app-loading-dot {
        width: 4px;
        height: 4px;
        border-radius: 9999px;
        background: #475569;
        display: inline-block;
    }

    .app-loading-dot:nth-child(1) { animation: loadingDotBlink 1.4s infinite 0.15s; }
    .app-loading-dot:nth-child(2) { animation: loadingDotBlink 1.4s infinite 0.3s; }
    .app-loading-dot:nth-child(3) { animation: loadingDotBlink 1.4s infinite 0.45s; }
</style>

<div id="app-loading-overlay">
    <div class="app-loading-glow"></div>

    <div class="app-loading-inner">
        <div class="app-loading-ring-box">
            <svg class="app-loading-ring" fill="none" viewBox="0 0 144 144" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="appLoadingRingGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#0284C7" stop-opacity="1"></stop>
                        <stop offset="50%" stop-color="#38BDF8" stop-opacity="1"></stop>
                        <stop offset="100%" stop-color="#84CC16" stop-opacity="1"></stop>
                    </linearGradient>
                    <linearGradient id="appLoadingTrackBg" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#E2E8F0" stop-opacity="0.6"></stop>
                        <stop offset="100%" stop-color="#F1F5F9" stop-opacity="0.3"></stop>
                    </linearGradient>
                </defs>
                <circle cx="72" cy="72" r="64" stroke="url(#appLoadingTrackBg)" stroke-width="2.5"></circle>
                <circle cx="72" cy="72" r="64" stroke="url(#appLoadingRingGradient)" stroke-dasharray="140 260" stroke-linecap="round" stroke-width="2.5"></circle>
                <circle cx="72" cy="8" r="2.5" fill="#84CC16"></circle>
            </svg>

            <!-- Company logo: dynamic, swaps per company's uploaded branding -->
            <div class="app-loading-logo">
                <img src="<?= esc($overlayLogoUrl, 'attr') ?>" alt="Company logo">
            </div>
        </div>

        <div class="app-loading-text">
            <span>Loading</span>
            <div class="app-loading-dots">
                <span class="app-loading-dot"></span>
                <span class="app-loading-dot"></span>
                <span class="app-loading-dot"></span>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // Recorded as early as possible (this script runs right after <body> opens,
        // before the rest of the page/assets), so it reflects real time-on-screen.
        var overlayShownAt = Date.now();
        var MIN_VISIBLE_MS = 900;

        function hideLoadingOverlay() {
            var overlay = document.getElementById('app-loading-overlay');
            if (!overlay) return;
            overlay.classList.add('is-hidden');
            window.setTimeout(function () {
                overlay.style.display = 'none';
            }, 600);
        }

        function onFullyLoaded() {
            var elapsed = Date.now() - overlayShownAt;
            var remaining = MIN_VISIBLE_MS - elapsed;
            if (remaining > 0) {
                window.setTimeout(hideLoadingOverlay, remaining);
            } else {
                hideLoadingOverlay();
            }
        }

        if (document.readyState === 'complete') {
            onFullyLoaded();
        } else {
            window.addEventListener('load', onFullyLoaded);
        }
    })();
</script>
