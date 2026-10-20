import { dayOf } from './format';
import type { Message } from '../types';

export type Row =
    | { type: 'day'; key: string; day: string }
    | { type: 'notice'; key: string; message: Message }
    | { type: 'message'; key: string; message: Message; first: boolean };

const NOTICE = new Set(['system', 'call']);

/**
 * Linhas da conversa: separador a cada dia, avisos centralizados e balões.
 * "first" marca o primeiro balão de uma sequência do mesmo remetente (com ponta e nome).
 */
export function buildRows(messages: Message[]): Row[] {
    const rows: Row[] = [];
    let lastDay: string | null = null;
    let prev: Message | null = null;

    for (const m of messages) {
        const day = dayOf(m.sent_at);
        if (day !== lastDay) {
            rows.push({ type: 'day', key: `d${day}`, day });
            lastDay = day;
            prev = null;
        }

        if (NOTICE.has(m.kind)) {
            rows.push({ type: 'notice', key: `m${m.id}`, message: m });
            prev = null;
            continue;
        }

        const first = !prev || prev.sender?.participant_id !== m.sender?.participant_id;
        rows.push({ type: 'message', key: `m${m.id}`, message: m, first });
        prev = m;
    }

    return rows;
}

/** Texto pesquisável de cada mensagem: corpo, legenda de foto e vídeo, ou nome do documento. */
export function searchableText(m: Message): { field: 'text' | 'caption' | 'file'; text: string } | null {
    if (m.kind === 'text' && m.text) return { field: 'text', text: m.text };
    if ((m.kind === 'image' || m.kind === 'video') && m.caption) return { field: 'caption', text: m.caption };
    if (m.kind === 'doc' && m.attachment) return { field: 'file', text: m.attachment.name };
    return null;
}

/** Cursor no mesmo formato do servidor (base64 de "sent_at|id"). */
export function cursorFor(m: Message): string {
    const raw = `${m.sent_at.replace('T', ' ')}|${m.id}`;
    return btoa(raw).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/** Converte o índice global de ocorrência ("3 de 12") em mensagem e ocorrência dentro dela. */
export function locateOccurrence(matches: { message_id: number; count: number }[], index: number): { messageId: number; nth: number } | null {
    let seen = 0;
    for (const m of matches) {
        if (index < seen + m.count) return { messageId: m.message_id, nth: index - seen };
        seen += m.count;
    }
    return null;
}

/** Largura máxima da mídia pela proporção real (4/3: 300 px, 3/4: 210 px, 16/9: 330 px, 1/1: 260 px). */
export function mediaBox(width: number | null, height: number | null, kind: 'image' | 'video'): { width: number; ratio: string } {
    if (!width || !height) return kind === 'video' ? { width: 330, ratio: '16 / 9' } : { width: 300, ratio: '4 / 3' };

    const r = width / height;
    const buckets: [number, number][] = [[4 / 3, 300], [3 / 4, 210], [16 / 9, 330], [1, 260]];
    const [, max] = buckets.reduce((best, b) => (Math.abs(Math.log(b[0] / r)) < Math.abs(Math.log(best[0] / r)) ? b : best));

    return { width: max, ratio: `${width} / ${height}` };
}
