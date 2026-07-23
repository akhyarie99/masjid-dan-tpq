import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const appName = import.meta.env.VITE_APP_NAME || 'SiMasjid';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#16a34a',
    },
});

// App Android (WebView) mengirim user agent khusus — lihat webview_screen.dart.
// Jangan pakai service worker/cache PWA di dalam WebView: app native sudah jadi
// "wadah"-nya sendiri, dan cache-first untuk build assets di sw.js justru bikin
// halaman ketinggalan versi setelah ada deploy baru (hash file JS/CSS berubah,
// tapi entri HTML lama masih menunjuk ke cache lama).
const isInsideNativeWebview = navigator.userAgent.includes('SimasjidApp');

if ('serviceWorker' in navigator) {
    if (import.meta.env.PROD && !isInsideNativeWebview) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    } else if (isInsideNativeWebview) {
        // Bersihkan registrasi/cache lama kalau sempat terpasang sebelum fix ini.
        navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((registration) => registration.unregister());
        }).catch(() => {});

        if ('caches' in window) {
            caches.keys().then((keys) => keys.forEach((key) => caches.delete(key))).catch(() => {});
        }
    }
}
