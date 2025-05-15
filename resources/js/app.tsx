import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import * as Sentry from '@sentry/react';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

Sentry.init({
    dsn: import.meta.env.VITE_SENTRY_DSN || null,
    integrations: [Sentry.browserTracingIntegration()],
    tracesSampleRate: import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE || 0.0,
    tracePropagationTargets: [/^\//, /^ + import.meta.env.VITE_APP_URL + /],
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el, {
            onUncaughtError: Sentry.reactErrorHandler((error, errorInfo) => {
                console.warn('Uncaught error', error, errorInfo.componentStack);
            }),

            onCaughtError: Sentry.reactErrorHandler(),

            onRecoverableError: Sentry.reactErrorHandler(),
        });

        root.render(
            <LaravelReactI18nProvider locale={'pt_BR'} fallbackLocale={'en'} files={import.meta.glob('/lang/*.json')}>
                <App {...props} />
            </LaravelReactI18nProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
