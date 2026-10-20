import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type { ImportFileCard as Card } from '../../types';
import ImportFileCard from './ImportFileCard.vue';

function card(extra: Partial<Card>): Card {
    return {
        id: 1, name: 'Conversa do WhatsApp com Maria Souza.zip', ext: 'zip', size_bytes: 3_984_588, status: 'done', percent: 100,
        conversation_name: 'Maria Souza', stats: { new: 8, repeated: 0, media_found: 2, media_missing: 1, lines_read: 20 },
        unrecognized_count: 0, unrecognized_lines: [], error_message: null, existing: null, choice: null, needs_choice: false,
        ...extra,
    };
}

describe('ImportFileCard', () => {
    it('mostra as quatro contagens quando termina', () => {
        const text = mount(ImportFileCard, { props: { file: card({}) } }).text();

        expect(text).toContain('Concluído');
        expect(text).toContain('Conversa: Maria Souza');
        expect(text).toContain('mensagens novas');
        expect(text).toContain('anexos faltantes');
        expect(text).toContain('3,8 MB');
    });

    it('mostra o progresso real enquanto envia e processa', () => {
        expect(mount(ImportFileCard, { props: { file: card({ status: 'uploading' }), upload: { sent: 50, size: 200 } } }).text()).toContain('Enviando 25%');
        expect(mount(ImportFileCard, { props: { file: card({ status: 'processing', percent: 42 }) } }).text()).toContain('Processando 42%');
        expect(mount(ImportFileCard, { props: { file: card({ status: 'waiting' }) } }).text()).toContain('Na fila');
    });

    it('pede a escolha quando a conversa já existe', async () => {
        const w = mount(ImportFileCard, { props: { file: card({ existing: { id: 3, name: 'Maria Souza' }, stats: { new: 2, repeated: 16, media_found: 0, media_missing: 0, lines_read: 20 } }) } });

        expect(w.text()).toContain('Já existe uma conversa com Maria Souza no acervo. As 16 mensagens repetidas não serão duplicadas.');
        await w.findAll('button')[1].trigger('click');
        expect(w.emitted('choose')?.[0]).toEqual(['separate']);
    });

    it('lista as linhas não reconhecidas e a falha de arquivo', async () => {
        const w = mount(ImportFileCard, { props: { file: card({ status: 'partial', unrecognized_count: 2, unrecognized_lines: [{ line: 14, text: '13/10/2026 17:3 - Eduardo' }] }) } });

        expect(w.text()).toContain('2 linhas não foram reconhecidas. As demais mensagens foram importadas.');
        await w.find('button').trigger('click');
        expect(w.text()).toContain('linha 14');

        const failed = mount(ImportFileCard, { props: { file: card({ status: 'failed', stats: null, error_message: 'Não foi possível reconhecer este arquivo como conversa.' }) } });
        expect(failed.text()).toContain('Erro');
        expect(failed.text()).toContain('Não foi possível reconhecer este arquivo como conversa.');
    });
});
