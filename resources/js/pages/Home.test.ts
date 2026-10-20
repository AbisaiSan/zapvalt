import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { useArchive } from '../composables/useArchive';
import Home from './Home.vue';


describe('Home', () => {
    const { archive } = useArchive();

    beforeEach(() => {
        archive.booting = false;
        archive.summary = null;
    });

    it('mostra "Abrindo seu acervo…" enquanto carrega', () => {
        archive.booting = true;
        expect(mount(Home).text()).toContain('Abrindo seu acervo…');
    });

    it('mostra o acervo vazio com o botão de importar', () => {
        archive.summary = { conversations: 0, messages: 0, first_message_at: null, last_message_at: null, attachments: 0, bytes_used: 0 };
        const text = mount(Home).text();

        expect(text).toContain('Seu acervo está vazio');
        expect(text).toContain('Importar conversas');
    });

    it('mostra o resumo do acervo e o período', () => {
        archive.summary = {
            conversations: 12, messages: 12345, first_message_at: '2026-09-02T11:00:00', last_message_at: '2026-10-20T09:21:00', attachments: 22, bytes_used: 1,
        };
        const text = mount(Home).text();

        expect(text).toContain('Selecione uma conversa');
        expect(text).toContain('12.345');
        expect(text).toContain('Mensagens de 02/09/2026 a 20/10/2026');
        expect(text).toContain('Acervo local e somente leitura. Nada é enviado.');
    });
});
