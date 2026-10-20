import { describe, expect, it } from 'vitest';
import { linkRanges, withLinks } from './links';
import { ranges, segs } from './search';

describe('links', () => {
    it('acha links http e https e deixa a pontuação do fim de fora', () => {
        expect(linkRanges('Veja https://exemplo.com.br/a?b=1, e http://x.org.')).toEqual([
            { start: 5, end: 33, href: 'https://exemplo.com.br/a?b=1' },
            { start: 37, end: 49, href: 'http://x.org/' },
        ]);
    });

    it('ignora outros esquemas', () => {
        expect(linkRanges('javascript:alert(1) ftp://x.org file:///etc')).toEqual([]);
    });

    it('combina link e destaque no mesmo texto', () => {
        const text = 'proposta em https://site.com/proposta agora';
        const out = withLinks(text, segs(text, ranges(text, 'proposta')));

        expect(out.map((s) => [s.t, s.kind, s.href ?? null])).toEqual([
            ['proposta', 'hl', null],
            [' em ', 'plain', null],
            ['https://site.com/', 'plain', 'https://site.com/proposta'],
            ['proposta', 'hl', 'https://site.com/proposta'],
            [' agora', 'plain', null],
        ]);
    });
});
