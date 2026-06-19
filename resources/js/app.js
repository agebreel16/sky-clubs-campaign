// WebPush registration — only on agent portal pages
if ('serviceWorker' in navigator && document.body?.dataset?.agentUuid) {
    const agentUuid   = document.body.dataset.agentUuid;
    const vapidKey    = document.querySelector('meta[name="vapid-key"]')?.content;

    if (vapidKey) {
        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            return reg.pushManager.getSubscription().then(function (sub) {
                if (sub) return sub; // already subscribed

                return Notification.requestPermission().then(function (permission) {
                    if (permission !== 'granted') return null;

                    const applicationServerKey = urlBase64ToUint8Array(vapidKey);
                    return reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey,
                    });
                });
            });
        }).then(function (sub) {
            if (!sub) return;

            fetch(`/agent/${agentUuid}/push/subscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode(...new Uint8Array(sub.getKey('p256dh')))),
                        auth:   btoa(String.fromCharCode(...new Uint8Array(sub.getKey('auth')))),
                    },
                }),
            });
        }).catch(console.warn);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding  = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64   = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData  = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}
