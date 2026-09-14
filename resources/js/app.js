import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { installAntiInspectionGuard } from './Utils/cryptoSecurity';

const appName = import.meta.env.VITE_APP_NAME || 'SISTEM INTI AKADEMIK';

// Inisialisasi mekanisme proteksi anti-inspeksi konsol & browser memory
installAntiInspectionGuard();

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);

        // Bersihkan atribut data-page dari DOM tree untuk mencegah DOM inspection leakage
        if (el && typeof el.removeAttribute === 'function') {
            el.removeAttribute('data-page');
            delete el.dataset.page;
        }

        return vueApp;
    },
    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
});
