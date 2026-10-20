import { describe, expect, it } from 'vitest';
import cases from '../../../tests/Fixtures/normalization.json';
import { norm, ranges, searchable, segs, snippet } from './search';

const text = (s: ReturnType<typeof segs>) => s.map((x) => x.t).join('');
const hits = (s: ReturnType<typeof segs>) => s.filter((x) => x.kind !== 'plain').map((x) => x.t);

describe('norm', () => {
    it.each(cases)('normaliza "$input" como o backend', ({ input, expected }) => {
        expect(norm(input)).toBe(expected);
    });

    it('preserva o tamanho do texto', () => {
        for (const { input } of cases) expect(norm(input)).toHaveLength(input.length);
    });
});

describe('ranges', () => {
    it('acha o termo ignorando caixa e acentos', () => {
        expect(ranges('PROPOSTA aprovada', 'proposta')).toEqual([[0, 8]]);
        expect(ranges('Proposta', 'PROPOSTA')).toEqual([[0, 8]]);
        expect(ranges('ação', 'acao')).toEqual([[0, 4]]);
        expect(ranges('acao', 'ação')).toEqual([[0, 4]]);
    });

    it('acha no início, no meio e no fim, várias vezes, sem sobreposição', () => {
        expect(ranges('ok, tudo ok e ok', 'ok')).toEqual([[0, 2], [9, 11], [14, 16]]);
        expect(ranges('aaaa', 'aa')).toEqual([[0, 2], [2, 4]]);
        expect(ranges('fim do texto', 'texto')).toEqual([[7, 12]]);
    });

    it('funciona com ç, ñ, ü, emojis e aspas curvas', () => {
        expect(ranges('Señor Müller', 'muller')).toEqual([[6, 12]]);
        expect(ranges('Fechado 🎉 hoje', 'hoje')).toEqual([[11, 15]]);
        expect(ranges('ela disse “sim”', '“sim”')).toEqual([[10, 15]]);
    });

    it('ignora termo vazio e texto vazio', () => {
        expect(ranges('abc', '  ')).toEqual([]);
        expect(ranges('', 'a')).toEqual([]);
        expect(ranges(null, 'a')).toEqual([]);
    });
});

describe('segs', () => {
    it('separa os trechos com destaque e marca o atual', () => {
        const out = segs('Bom dia, proposta e PROPOSTA', [[9, 17], [20, 28, true]]);

        expect(out).toEqual([
            { t: 'Bom dia, ', kind: 'plain' },
            { t: 'proposta', kind: 'hl' },
            { t: ' e ', kind: 'plain' },
            { t: 'PROPOSTA', kind: 'cur' },
        ]);
    });

    it('sem destaque devolve o texto inteiro', () => {
        expect(segs('texto', [])).toEqual([{ t: 'texto', kind: 'plain' }]);
    });
});

describe('snippet', () => {
    it('mantém textos curtos inteiros e troca quebras de linha por espaços', () => {
        const out = snippet('Segue o PDF\ncom os valores', 'valores');

        expect(text(out)).toBe('Segue o PDF com os valores');
        expect(hits(out)).toEqual(['valores']);
    });

    it('corta o começo com "…" quando o termo aparece depois do 38º caractere', () => {
        const long = 'Pessoal, criei o grupo para centralizar o projeto Alfa e a proposta final.';
        const out = snippet(long, 'proposta');

        expect(out[0]).toEqual({ t: '…', kind: 'plain' });
        expect(text(out)).toBe('…centralizar o projeto Alfa e a proposta final.');
        expect(hits(out)).toEqual(['proposta']);
    });
});

describe('searchable', () => {
    it('exige 2 caracteres depois de tirar os espaços', () => {
        expect(searchable(' a ')).toBe(false);
        expect(searchable('ok')).toBe(true);
    });
});
