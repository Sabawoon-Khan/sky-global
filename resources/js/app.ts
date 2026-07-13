import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeLocale } from '@/composables/useLocale';
import { initializeThemeColors } from '@/lib/theme-colors';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Sun Sky Global Security';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name.startsWith('errors/'):
            case name === 'mis/hr/Payroll/Print':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#dc2626',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
initializeThemeColors();
initializeLocale();
