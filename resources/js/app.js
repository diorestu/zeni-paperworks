import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Paperwork';

import { ZiggyVue } from '../../vendor/tightenco/ziggy/src/js';

import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin)
            .use(ZiggyVue)
            .use(Toast, {
                position: 'bottom-center',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: 'button',
                icon: true,
                rtl: false
            })
            .mount(el);
    },
    progress: {
        color: '#07304a',
    },
});
