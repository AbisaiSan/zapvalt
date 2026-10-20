import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import BackupSection from './BackupSection.vue';
import PasswordSection from './PasswordSection.vue';

function respond(status: number, body?: unknown) {
    return Promise.resolve(new Response(body === undefined ? null : JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }));
}

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe('PasswordSection', () => {
    it('pede pelo menos 6 caracteres e senhas iguais antes de enviar', async () => {
        const fetch = vi.fn(() => respond(204));
        vi.stubGlobal('fetch', fetch);
        const w = mount(PasswordSection, { props: { modelValue: false } });

        await w.get('[role="switch"]').trigger('click');
        const [a, b] = w.findAll('input[type="password"]');
        await a.setValue('12345');
        await b.setValue('12345');
        await w.get('form').trigger('submit');
        expect(w.text()).toContain('Use pelo menos 6 caracteres.');

        await a.setValue('123456');
        await b.setValue('654321');
        await w.get('form').trigger('submit');
        expect(w.text()).toContain('As senhas não coincidem.');
        expect(fetch).not.toHaveBeenCalled();

        await b.setValue('123456');
        await w.get('form').trigger('submit');
        await flushPromises();
        expect(fetch).toHaveBeenCalledWith('/api/settings/password', expect.objectContaining({ method: 'PUT' }));
        expect(w.emitted('update:modelValue')?.[0]).toEqual([true]);
    });

    it('com a senha ativa, mostra Bloquear agora e explica que não criptografa', async () => {
        const w = mount(PasswordSection, { props: { modelValue: true } });

        expect(w.text()).toContain('Senha ativada');
        expect(w.text()).toContain('Bloquear agora');
        expect(w.text()).toContain('não criptografa');
        expect(w.get('[role="switch"]').attributes('aria-checked')).toBe('true');
    });

    it('desativar pede a senha atual e mostra o erro do servidor', async () => {
        vi.stubGlobal('fetch', vi.fn(() => respond(422, { message: 'Senha incorreta.', retry_after: 0 })));
        const w = mount(PasswordSection, { props: { modelValue: true } });

        await w.get('[role="switch"]').trigger('click');
        expect(w.text()).toContain('digite a senha atual');
        await w.get('#current-password').setValue('errada1');
        await w.get('form').trigger('submit');
        await flushPromises();

        expect(w.text()).toContain('Senha incorreta.');
        expect(w.emitted('update:modelValue')).toBeUndefined();
    });
});

describe('BackupSection', () => {
    const props = { lastBackup: { at: '2026-10-12T18:04:00', file_name: 'ZapVault-backup-2026-10-12.zip', size_bytes: 1 }, current: null, dataPath: '/Users/ana/ZapVault-dados' };

    it('mostra o último backup', () => {
        expect(mount(BackupSection, { props }).text()).toContain('Último backup: 12/10/2026 às 18:04');
        expect(mount(BackupSection, { props: { ...props, lastBackup: null } }).text()).toContain('Último backup: nenhum ainda');
    });

    it('acompanha o progresso até o fim', async () => {
        vi.useFakeTimers();
        const states = [
            { id: 3, status: 'running', percent: 0, file_name: null, size_bytes: null, error: null },
            { id: 3, status: 'running', percent: 47, file_name: null, size_bytes: null, error: null },
            { id: 3, status: 'done', percent: 100, file_name: 'ZapVault-backup-2026-10-20.zip', size_bytes: 1288490189, error: null },
        ];
        vi.stubGlobal('fetch', vi.fn(() => respond(200, states.shift())));
        const w = mount(BackupSection, { props });

        await w.get('button').trigger('click');
        await flushPromises();
        expect(w.text()).toContain('Gerando backup…');

        await vi.advanceTimersByTimeAsync(300);
        expect(w.text()).toContain('47%');

        await vi.advanceTimersByTimeAsync(700);
        expect(w.text()).toContain('Backup concluído: ZapVault-backup-2026-10-20.zip (1,2 GB)');
        expect(w.text()).toContain('/Users/ana/ZapVault-dados/backups');
        expect(w.emitted('saved')).toHaveLength(1);
    });

    it('mostra a falha com Tentar de novo', async () => {
        vi.stubGlobal('fetch', vi.fn(() => respond(200, { id: 4, status: 'failed', percent: 2, file_name: null, size_bytes: null, error: 'Não há espaço livre suficiente no disco para gerar o backup.' })));
        const w = mount(BackupSection, { props });

        await w.get('button').trigger('click');
        await flushPromises();

        expect(w.text()).toContain('Não há espaço livre suficiente');
        expect(w.text()).toContain('Tentar de novo');
    });
});
