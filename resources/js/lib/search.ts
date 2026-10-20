// Busca e destaque, portados de norm, ranges e segs do protótipo (docs/prototipo/zapvault-data.js).
// A normalização preserva o tamanho do texto, para que as posições coincidam com o original.

export type Range = [start: number, end: number, current?: boolean];

export type SegmentKind = 'plain' | 'hl' | 'cur';

export interface Segment {
    t: string;
    kind: SegmentKind;
}

/** Ignora maiúsculas, minúsculas e acentos, caractere por caractere. */
export function norm(s: string): string {
    let out = '';
    for (let i = 0; i < s.length; i++) {
        const c = s[i];
        const n = c.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        out += n.length ? n[0] : c;
    }
    return out;
}

/** Posições de todas as ocorrências do termo, sem sobreposição. */
export function ranges(text: string | null | undefined, q: string | null | undefined): Range[] {
    if (!text || !q) return [];
    const nt = norm(text);
    const nq = norm(q.trim());
    if (!nq) return [];
    const out: Range[] = [];
    let i = nt.indexOf(nq);
    while (i !== -1) {
        out.push([i, i + nq.length]);
        i = nt.indexOf(nq, i + nq.length);
    }
    return out;
}

/** Divide o texto em trechos com e sem destaque. */
export function segs(text: string, rs: Range[] | null | undefined): Segment[] {
    if (!rs || !rs.length) return [{ t: text, kind: 'plain' }];
    const out: Segment[] = [];
    let p = 0;
    for (const [a, b, cur] of rs) {
        if (a > p) out.push({ t: text.slice(p, a), kind: 'plain' });
        out.push({ t: text.slice(a, b), kind: cur ? 'cur' : 'hl' });
        p = b;
    }
    if (p < text.length) out.push({ t: text.slice(p), kind: 'plain' });
    return out;
}

/**
 * Trecho de um resultado de busca: quebras de linha viram espaços e, se a primeira ocorrência
 * passa do 38º caractere, o trecho começa depois do último espaço antes de 24 caracteres atrás, com "…".
 */
export function snippet(text: string, q: string): Segment[] {
    const flat = text.replace(/\n/g, ' ');
    const rs = ranges(flat, q);
    if (!rs.length) return segs(flat, []);

    let start = 0;
    const s0 = rs[0][0];
    if (s0 > 38) {
        const sp = flat.lastIndexOf(' ', s0 - 24);
        start = sp < 0 ? s0 - 24 : sp + 1;
    }

    const shifted = rs.map(([a, b]): Range => [a - start, b - start, false]).filter((r) => r[0] >= 0);
    const out = segs(flat.slice(start), shifted);
    if (start > 0) out.unshift({ t: '…', kind: 'plain' });
    return out;
}

/** Termo pronto para buscar: sem espaços nas pontas e com pelo menos 2 caracteres. */
export function searchable(q: string): boolean {
    return q.trim().length >= 2;
}
