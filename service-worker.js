const CACHE_NAME = 'ksp-member-v17'; // Naikkan versi cache setelah fix Vhost
// Daftar file yang ingin di-cache agar loading lebih cepat
const urlsToCache = [
    './member/login',
    './member/dashboard',
    './assets/img/logo.png',
    './assets/img/icon-192.png',
    './assets/img/icon-512.png',
    // Cache aset eksternal agar tampilan tetap bagus saat offline
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap'
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                // Gunakan map agar jika satu gagal, yang lain tetap dicache
                return Promise.all(
                    urlsToCache.map(url => {
                        // Untuk URL eksternal, gunakan mode no-cors agar tidak kena blokir CORS saat caching
                        if (url.startsWith('http')) {
                            const request = new Request(url, { mode: 'no-cors' });
                            return fetch(request)
                                .then(response => cache.put(request, response))
                                .catch(err => console.log('Gagal cache external:', url, err));
                        }
                        
                        // Gunakan fetch manual agar tidak error fatal jika file tidak ditemukan (404)
                        return fetch(url).then(response => {
                            if (!response.ok) {
                                console.warn(`Gagal cache file (skip): ${url} [${response.status}]`);
                                return;
                            }
                            return cache.put(url, response);
                        }).catch(err => console.warn(`Gagal fetch file: ${url}`, err));
                    })
                );
            })
    );
});

self.addEventListener('fetch', event => {
    // Skip request yang bukan HTTP/HTTPS (seperti chrome-extension://)
    if (!event.request.url.startsWith('http')) return;

    // Jangan cache file OneSignal agar selalu fresh
    if (event.request.url.includes('OneSignalSDK')) return;

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Clone response untuk disimpan di cache (Dynamic Caching)
                if (response && response.status === 200 && event.request.method === 'GET') {
                    // Cek ulang skema URL sebelum put ke cache
                    if (!response.url.startsWith('http')) return response;

                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return response;
            })
            .catch(() => {
                // Jika offline, cari di cache
                return caches.match(event.request);
            })
    );
});

self.addEventListener('push', event => {
    console.log('[Service Worker] Push Diterima.');
    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
            // Cek apakah ini notifikasi dari OneSignal (memiliki properti custom.i)
            // Jika ya, biarkan SDK OneSignal yang menanganinya agar tidak muncul ganda
            if (data.custom && data.custom.i) {
                console.log('[Service Worker] Notifikasi OneSignal terdeteksi, skip handler manual.');
                return;
            }
        } catch (e) {
            data = { body: event.data.text() };
        }
    }

    const title = data.title || 'Pengumuman Baru';
    const options = {
        body: data.body || 'Ada informasi baru dari koperasi.',
        icon: data.icon || './assets/img/icon-192.png',
        badge: './assets/img/icon-192.png',
        data: { url: data.url || './member/dashboard' }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});
