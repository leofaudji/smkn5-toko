importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");

// Import PWA Service Worker to merge functionalities and avoid conflicts
try {
    importScripts("service-worker.js");
} catch (e) {
    console.warn("Failed to import service-worker.js", e);
}