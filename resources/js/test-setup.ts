import { vi } from 'vitest';
import { defineComponent, h, reactive } from 'vue';

// O Inertia não roda fora do navegador; nos testes, componentes simples no lugar.
vi.mock('@inertiajs/vue3', () => ({
    Head: defineComponent({ render: () => null }),
    Link: defineComponent({
        props: { href: { type: String, default: '' } },
        setup: (props, { slots }) => () => h('a', { href: props.href }, slots.default?.()),
    }),
    router: { visit: vi.fn() },
    usePage: () => reactive({ component: 'Home', props: {}, url: '/' }),
}));
