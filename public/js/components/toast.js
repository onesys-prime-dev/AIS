/**
 * Shared toast notification component (styles in css/toast.css).
 * Usage: window.showToast('Message text', 'success' | 'error' | 'warning' | 'info');
 */
(function () {
    const ICONS = {
        success: `<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#16a34a"/><path d="M6.5 12.5l4 4 7-8" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
        error:   `<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#dc2626"/><path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>`,
        warning: `<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><path d="M12 2L22 20H2L12 2z" fill="#d97706"/><rect x="11" y="9.5" width="2" height="6" rx="1" fill="#fff"/><circle cx="12" cy="17.5" r="1.1" fill="#fff"/></svg>`,
        info:    `<svg class="toast-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#7b5cff"/><rect x="11" y="10.5" width="2" height="7" rx="1" fill="#fff"/><circle cx="12" cy="7.5" r="1.2" fill="#fff"/></svg>`,
    };
    const TITLES   = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Info' };
    const DURATION = { success: 4500, error: 7000, warning: 5500, info: 4500 };

    function getContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    let lastToast = { key: '', at: 0 };

    window.showToast = function (message, type = 'info') {
        // The same message twice in a moment (e.g. the global access-denied
        // toast below plus a page's own error handler) shows once.
        const key = type + '|' + message;
        const now = Date.now();
        if (lastToast.key === key && now - lastToast.at < 1500) return;
        lastToast = { key, at: now };

        const container = getContainer();
        const t = type in ICONS ? type : 'info';

        const el = document.createElement('div');
        el.className = `toast toast-${t}`;
        el.innerHTML = `${ICONS[t]}
            <div class="toast-body">
                <div class="toast-title">${TITLES[t]}</div>
                <div class="toast-msg"></div>
            </div>
            <button class="toast-close" aria-label="Close">&times;</button>
            <div class="toast-progress" style="width:100%"></div>`;
        el.querySelector('.toast-msg').textContent = message;

        container.appendChild(el);
        requestAnimationFrame(() => { el.classList.add('show'); });

        const dur = DURATION[t];
        const prog = el.querySelector('.toast-progress');
        if (prog) {
            prog.style.transition = `width ${dur}ms linear`;
            requestAnimationFrame(() => { requestAnimationFrame(() => { prog.style.width = '0%'; }); });
        }

        const dismiss = () => {
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => el.remove(), 240);
        };
        const timer = setTimeout(dismiss, dur);
        el.querySelector('.toast-close').addEventListener('click', (e) => { e.stopPropagation(); clearTimeout(timer); dismiss(); });
        el.addEventListener('click', () => { clearTimeout(timer); dismiss(); });
    };

    // Landing here after a denied page/action (see PortalAccessFilter): show
    // the toast, then drop the flag from the address bar so a refresh or a
    // copied link doesn't repeat it.
    (function showAccessDenied() {
        let url;
        try { url = new URL(window.location.href); } catch (e) { return; }
        const flag = url.searchParams.get('access_denied');
        if (!flag) return;
        url.searchParams.delete('access_denied');
        try { window.history.replaceState(null, '', url.pathname + url.search + url.hash); } catch (e) {}
        const text = flag === 'action' ? "You don't have permission to do that." : "You don't have access to that page.";
        const show = () => window.showToast(text, 'error');
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', show);
        else show();
    })();

    // Any background request refused with 403 + a JSON message (the portal's
    // "no permission" reply) gets a toast, even where the page's own code
    // ignores failed responses. The response itself is passed through
    // untouched (we only read a clone).
    if (typeof window.fetch === 'function') {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = function () {
            return nativeFetch.apply(null, arguments).then(function (res) {
                if (res.status === 403 && (res.headers.get('content-type') || '').includes('json')) {
                    res.clone().json().then(function (body) {
                        const msg = body && (body.message || body.error);
                        if (msg && body.success === false) window.showToast(String(msg), 'error');
                    }).catch(function () {});
                }
                return res;
            });
        };
    }
})();
