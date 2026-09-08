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

    window.showToast = function (message, type = 'info') {
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
})();
