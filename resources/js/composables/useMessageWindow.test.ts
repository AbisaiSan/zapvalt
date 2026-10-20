import { afterEach, describe, expect, it, vi } from 'vitest';
import type { Message, MessageWindow } from '../types';
import { MAX_IN_DOM, PAGE, useMessageWindow } from './useMessageWindow';

function messages(from: number, n: number): Message[] {
    return Array.from({ length: n }, (_, i) => ({
        id: from + i, sent_at: `2026-01-01T00:00:${String((from + i) % 60).padStart(2, '0')}`, kind: 'text' as const,
        sender: null, text: 'x', caption: null, system_icon: null, attachment: null,
    }));
}

function respond(w: Partial<MessageWindow>) {
    return Promise.resolve(new Response(JSON.stringify({ has_older: true, has_newer: false, older_cursor: null, newer_cursor: null, ...w }), { status: 200 }));
}

afterEach(() => vi.unstubAllGlobals());

describe('useMessageWindow', () => {
    it('carrega antigas em lotes e nunca passa de 300 mensagens, descartando as mais novas', async () => {
        let next = 100_000;
        vi.stubGlobal('fetch', vi.fn((url: string) => {
            next -= PAGE;
            return respond({ messages: messages(next, PAGE), has_older: true, has_newer: !url.includes('before') ? false : true });
        }));

        const { win, latest, older } = useMessageWindow();
        await latest(1);
        expect(win.messages).toHaveLength(PAGE);
        expect(win.hasNewer).toBe(false);

        for (let i = 0; i < 4; i++) await older();

        expect(win.messages).toHaveLength(MAX_IN_DOM);
        expect(win.hasNewer).toBe(true);
        expect(win.messages[0].id).toBe(next);
        expect(String(vi.mocked(fetch).mock.calls.at(-1)?.[0])).toContain('before=');
    });

    it('carrega novas e descarta as mais antigas acima do limite', async () => {
        let next = 0;
        vi.stubGlobal('fetch', vi.fn(() => {
            const page = respond({ messages: messages(next, PAGE), has_older: next > 0, has_newer: true });
            next += PAGE;
            return page;
        }));

        const { win, day, newer } = useMessageWindow();
        await day(1, '2026-01-01');
        for (let i = 0; i < 4; i++) await newer();

        expect(win.messages).toHaveLength(MAX_IN_DOM);
        expect(win.hasOlder).toBe(true);
        expect(win.messages.at(-1)?.id).toBe(next - 1);
    });

    it('around devolve a mensagem de foco', async () => {
        vi.stubGlobal('fetch', vi.fn(() => respond({ messages: messages(10, 5), focus_id: 12 })));

        const { around } = useMessageWindow();

        expect(await around(1, 12)).toBe(12);
        expect(String(vi.mocked(fetch).mock.calls[0][0])).toContain('around=12');
    });
});
