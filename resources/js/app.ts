import { createInertiaApp } from '@inertiajs/vue3';
import AuthLayout from './components/ui/layouts/AuthLayout.vue';
import MainLayout from './components/ui/layouts/MainLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'DMS';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    pages: {
        path: './pages',
        lazy: true,
        extension: '.vue',
    },
    layout: (name) => {
        return name.startsWith('Auth/') ? AuthLayout : MainLayout;
    },
    progress: {
        color: 'var(--color-primary-light)',
    },
});
