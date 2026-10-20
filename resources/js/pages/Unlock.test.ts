import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Unlock from './Unlock.vue';

function respond(status: number, body?: unknown) {
    return Promise.resolve(new Response(body === undefined ? null : JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }));
}

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe('Unlock', () => {
    it('avisa a senha incorreta', async () => {
        vi.stubGlobal('fetch', vi.fn(() => respond(422, { message: 'Senha incorreta.', retry_after: 0 })));
        const w = mount(Unlock);

        await w.get('input').setValue('errada1');
        await w.get('form').trigger('submit');
        await flushPromises();

        expect(w.text()).toContain('Senha incorreta.');
        expect((w.get('input').element as HTMLInputElement).value).toBe('');
    });

    it('depois de muitas tentativas, espera com contagem regressiva', async () => {
        vi.useFakeTimers();
        vi.stubGlobal('fetch', vi.fn(() => respond(422, { message: 'Muitas tentativas erradas.', retry_after: 5 })));
        const w = mount(Unlock);

        await w.get('input').setValue('errada1');
        await w.get('form').trigger('submit');
        await flushPromises();

        expect(w.text()).toContain('Tente de novo em 5 s.');
        expect(w.get('button').attributes('disabled')).toBeDefined();

        await vi.advanceTimersByTimeAsync(5000);
        expect(w.text()).not.toContain('Tente de novo em');
        expect(w.get('button').attributes('disabled')).toBeUndefined();
    });

    it('com a senha certa, abre o acervo', async () => {
        const assign = vi.fn();
        vi.stubGlobal('location', { ...window.location, assign });
        vi.stubGlobal('fetch', vi.fn(() => respond(204)));
        const w = mount(Unlock);

        await w.get('input').setValue('segredo1');
        await w.get('form').trigger('submit');
        await flushPromises();

        expect(assign).toHaveBeenCalledWith('/');
    });
});
