self.addEventListener('install', event => {
});

self.addEventListener('fetch', event => {
});

self.addEventListener('sync', event => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncDataWithServer());
    }
});
