import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { applyTheme, loadTheme } from './lib/theme';

applyTheme(loadTheme());

createInertiaApp({
    title: (title) => (title ? `${title} · ZapVault` : 'ZapVault'),
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: true });
        return pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    // A barra de progresso do Inertia injeta <style> inline, bloqueado pela CSP.
    progress: false,
});
