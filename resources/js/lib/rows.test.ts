import { describe, expect, it } from 'vitest';
import type { Message } from '../types';
import { buildRows, cursorFor, locateOccurrence, mediaBox, searchableText } from './rows';

let id = 0;
function msg(sent_at: string, kind: Message['kind'], who: number | null, extra: Partial<Message> = {}): Message {
    return {
        id: ++id, sent_at, kind, text: 'oi', caption: null, system_icon: null, attachment: null,
        sender: who === null ? null : { participant_id: who, name: `P${who}`, is_me: who === 1, color_slot: who === 1 ? null : 0 },
        ...extra,
    };
}

describe('buildRows', () => {
    it('separa os dias, agrupa balões do mesmo remetente e reinicia depois de avisos', () => {
        const rows = buildRows([
            msg('2026-10-19T09:00:00', 'system', null),
            msg('2026-10-19T09:14:00', 'text', 2),
            msg('2026-10-19T09:15:00', 'text', 2),
            msg('2026-10-19T09:20:00', 'text', 1),
            msg('2026-10-19T10:15:00', 'call', null),
            msg('2026-10-19T10:16:00', 'text', 1),
            msg('2026-10-20T08:00:00', 'text', 1),
        ]);

        expect(rows.map((r) => (r.type === 'message' ? `msg:${r.first}` : r.type))).toEqual([
            'day', 'notice', 'msg:true', 'msg:false', 'msg:true', 'notice', 'msg:true', 'day', 'msg:true',
        ]);
    });
});

describe('searchableText', () => {
    it('usa o corpo, a legenda ou o nome do documento', () => {
        expect(searchableText(msg('2026-10-19T09:00:00', 'text', 1, { text: 'olá' }))).toEqual({ field: 'text', text: 'olá' });
        expect(searchableText(msg('2026-10-19T09:00:00', 'image', 1, { text: null, caption: 'Fachada' }))).toEqual({ field: 'caption', text: 'Fachada' });
        expect(searchableText(msg('2026-10-19T09:00:00', 'image', 1, { text: null }))).toBeNull();
        expect(searchableText(msg('2026-10-19T09:00:00', 'system', null))).toBeNull();
    });
});

describe('cursorFor', () => {
    it('gera o mesmo cursor que o servidor', () => {
        expect(cursorFor(msg('2026-10-20T09:21:00', 'text', 1, { id: 30 }))).toBe('MjAyNi0xMC0yMCAwOToyMTowMHwzMA');
    });
});

describe('locateOccurrence', () => {
    it('acha a mensagem e a ocorrência dentro dela', () => {
        const matches = [{ message_id: 5, count: 2 }, { message_id: 9, count: 1 }, { message_id: 12, count: 3 }];

        expect(locateOccurrence(matches, 0)).toEqual({ messageId: 5, nth: 0 });
        expect(locateOccurrence(matches, 1)).toEqual({ messageId: 5, nth: 1 });
        expect(locateOccurrence(matches, 2)).toEqual({ messageId: 9, nth: 0 });
        expect(locateOccurrence(matches, 5)).toEqual({ messageId: 12, nth: 2 });
        expect(locateOccurrence(matches, 6)).toBeNull();
    });
});

describe('mediaBox', () => {
    it('escolhe a largura pela proporção mais próxima e reserva o espaço real', () => {
        expect(mediaBox(1200, 900, 'image')).toEqual({ width: 300, ratio: '1200 / 900' });
        expect(mediaBox(900, 1200, 'image')).toEqual({ width: 210, ratio: '900 / 1200' });
        expect(mediaBox(1280, 720, 'image')).toEqual({ width: 330, ratio: '1280 / 720' });
        expect(mediaBox(800, 800, 'image')).toEqual({ width: 260, ratio: '800 / 800' });
        expect(mediaBox(null, null, 'video')).toEqual({ width: 330, ratio: '16 / 9' });
    });
});
