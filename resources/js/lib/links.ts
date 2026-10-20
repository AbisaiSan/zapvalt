import type { Segment } from './search';

export interface LinkedSegment extends Segment {
    href?: string;
}

/** Só http e https viram link; pontuação no fim fica de fora. */
const URL = /\bhttps?:\/\/[^\s<>"']+/gi;
const TRAILING = /[.,;:!?)\]}'"»]+$/;

export function linkRanges(text: string): { start: number; end: number; href: string }[] {
    const out: { start: number; end: number; href: string }[] = [];
    for (const match of text.matchAll(URL)) {
        const raw = match[0].replace(TRAILING, '');
        try {
            const url = new globalThis.URL(raw);
            if (url.protocol === 'http:' || url.protocol === 'https:') out.push({ start: match.index!, end: match.index! + raw.length, href: url.href });
        } catch {
            // Endereço inválido: fica como texto.
        }
    }
    return out;
}

/** Junta os trechos de destaque com os links: cada pedaço sabe se é destaque e se é link. */
export function withLinks(text: string, segments: Segment[]): LinkedSegment[] {
    const links = linkRanges(text);
    if (!links.length) return segments;

    const out: LinkedSegment[] = [];
    let pos = 0;
    for (const seg of segments) {
        const start = pos;
        const end = pos + seg.t.length;
        let cut = start;
        const points = links.flatMap((l) => [l.start, l.end]).filter((p) => p > start && p < end);
        for (const p of [...points, end]) {
            const href = links.find((l) => cut >= l.start && cut < l.end)?.href;
            out.push({ t: text.slice(cut, p), kind: seg.kind, href });
            cut = p;
        }
        pos = end;
    }
    return out.filter((s) => s.t.length);
}
