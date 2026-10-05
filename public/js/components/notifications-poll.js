/**
 * Periodically re-fetches the current page and swaps in the freshly
 * rendered notification fragments (header bell dropdown + the dashboard's
 * Published/Unpublished panel, when present) so a scheduled announcement
 * whose publish time has just passed — or a new request notification —
 * shows up without the user manually reloading the page.
 */
(function () {
    const POLL_INTERVAL_MS = 60000;

    function swapById(doc, id) {
        const next = doc.getElementById(id);
        const current = document.getElementById(id);
        if (next && current) {
            current.innerHTML = next.innerHTML;
        }
    }

    function refreshNotifications() {
        if (document.visibilityState !== 'visible') {
            return;
        }

        fetch(window.location.href, { credentials: 'same-origin' })
            .then(function (res) { return res.ok ? res.text() : Promise.reject(res); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');

                swapById(doc, 'headerNotifList');
                swapById(doc, 'headerNotifBadgeSlot');

                const currentPanel = document.getElementById('notificationsPanel');
                const nextPanel = doc.getElementById('notificationsPanel');
                if (currentPanel && nextPanel) {
                    const activeTab = currentPanel.querySelector('.notifications-tab.active');
                    const activeGroup = activeTab && activeTab.id === 'tabUnpublished' ? 'unpublished' : 'published';
                    currentPanel.innerHTML = nextPanel.innerHTML;
                    if (typeof window.toggleNotificationsGroup === 'function') {
                        window.toggleNotificationsGroup(activeGroup);
                    }
                }
            })
            .catch(function () {
                // Transient network/server hiccup — the next poll will retry.
            });
    }

    setInterval(refreshNotifications, POLL_INTERVAL_MS);
})();
