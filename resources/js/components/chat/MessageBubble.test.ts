import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type { Message } from '../../types';
import MessageBubble from './MessageBubble.vue';

function message(extra: Partial<Message>): Message {
    return {
        id: 1, sent_at: '2026-10-20T09:21:00', kind: 'text', text: 'oi', caption: null, system_icon: null, attachment: null,
        sender: { participant_id: 2, name: 'Maria Souza', is_me: false, color_slot: 3 },
        ...extra,
    };
}

const base = { first: true, isGroup: true, flash: false, term: '', currentNth: -1 };

describe('MessageBubble', () => {
    it('mostra o texto como texto, nunca como HTML', () => {
        const w = mount(MessageBubble, { props: { ...base, message: message({ text: '<b>oi</b><script>x()</script>' }) } });

        expect(w.find('b').exists()).toBe(false);
        expect(w.find('script').exists()).toBe(false);
        expect(w.text()).toContain('<b>oi</b><script>x()</script>');
    });

    it('torna links http e https clicáveis em nova aba, com noopener', () => {
        const w = mount(MessageBubble, { props: { ...base, message: message({ text: 'veja https://exemplo.com e javascript:alert(1)' }) } });
        const links = w.findAll('a');

        expect(links).toHaveLength(1);
        expect(links[0].attributes()).toMatchObject({ href: 'https://exemplo.com/', target: '_blank', rel: 'noopener noreferrer' });
    });

    it('mostra o nome do remetente com a cor dele em grupos', () => {
        const w = mount(MessageBubble, { props: { ...base, message: message({}) } });

        expect(w.text()).toContain('Maria Souza');
        expect(w.html()).toContain('var(--s3)');
    });

    it('destaca o termo e a ocorrência atual', () => {
        const w = mount(MessageBubble, { props: { ...base, term: 'ok', currentNth: 1, message: message({ text: 'ok e ok' }) } });
        const marks = w.findAll('mark');

        expect(marks).toHaveLength(2);
        expect(marks[0].classes()).toContain('bg-hl');
        expect(marks[1].classes()).toContain('bg-hl-cur');
    });

    it('textos de mensagem apagada e de mídia faltante', () => {
        expect(mount(MessageBubble, { props: { ...base, message: message({ kind: 'deleted', text: null }) } }).text()).toContain('Essa mensagem foi apagada');
        expect(
            mount(MessageBubble, { props: { ...base, message: message({ kind: 'deleted', text: null, sender: { participant_id: 1, name: 'Eu', is_me: true, color_slot: null } }) } }).text(),
        ).toContain('Você apagou esta mensagem');
        expect(mount(MessageBubble, { props: { ...base, message: message({ kind: 'missing', text: null }) } }).text()).toContain('Mídia não incluída na exportação');
    });

    it('documento mostra páginas, tamanho e extensão, com link para baixar', () => {
        const w = mount(MessageBubble, {
            props: {
                ...base,
                message: message({
                    kind: 'doc', text: null,
                    attachment: { id: 7, name: 'Proposta_v3.pdf', ext: 'pdf', size_bytes: 245760, width: null, height: null, duration: null, pages: 3, url: '/media/7', thumb_url: null },
                }),
            },
        });

        expect(w.text()).toContain('3 páginas · 240 KB · PDF');
        expect(w.find('a[download]').attributes('href')).toBe('/media/7?download=1');
    });
});
