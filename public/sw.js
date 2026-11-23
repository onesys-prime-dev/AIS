/**
 * Service worker for attendance-reminder browser push.
 *
 * Shows a reminder as a system notification even when the portal tab is closed (as long as the browser is running),
 * opens/focuses the portal when it is clicked, and closes a notification when the page says the reminder is no longer
 * due (the employee clocked in, clocked out or ended the break).
 */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (event) {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = {}; }

    event.waitUntil((async function () {
        // If the person is looking at the portal right now (its window has focus), its own pop-up already covers this.
        // A portal that is merely visible behind another window does not count: they would miss it.
        const open = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        if (open.some(function (c) { return c.focused; })) return;

        await self.registration.showNotification(data.title || 'Attendance reminder', {
            body: data.body || '',
            // the same group (day + kind of reminder) = a later step replaces the earlier notification instead of stacking
            tag: data.group || data.tag || 'attendance-reminder',
            renotify: true,
            requireInteraction: true,
            data: { url: data.url || '/' },
        });
    })());
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async function () {
        const list = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const c of list) {
            if ('focus' in c) { await c.focus(); return; }
        }
        if (self.clients.openWindow) await self.clients.openWindow(url);
    })());
});

// The page asks to close a notification once its reminder is no longer due.
self.addEventListener('message', function (event) {
    const msg = event.data || {};
    if (msg.type !== 'close' || !msg.tag) return;
    event.waitUntil(self.registration.getNotifications({ tag: msg.tag }).then(function (list) {
        list.forEach(function (n) { n.close(); });
    }));
});
