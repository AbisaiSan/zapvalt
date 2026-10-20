import { describe, expect, it } from 'vitest';
import { dayDiff, dur, extColor, fileSize, fmt, initials, listDate, plural, searchShortcut, sepLabel, weekday } from './format';

const TODAY = '2026-10-20'; // terça-feira

describe('datas', () => {
    it('separador: Hoje, Ontem, dia da semana até 5 dias e data por extenso', () => {
        expect(sepLabel('2026-10-20', TODAY)).toBe('Hoje');
        expect(sepLabel('2026-10-19', TODAY)).toBe('Ontem');
        expect(sepLabel('2026-10-16', TODAY)).toBe('Sexta-feira');
        expect(sepLabel('2026-10-15', TODAY)).toBe('Quinta-feira');
        expect(sepLabel('2026-10-14', TODAY)).toBe('14 de outubro de 2026');
        expect(sepLabel('2025-01-01', TODAY)).toBe('1 de janeiro de 2025');
    });

    it('lista: hora se for hoje, Ontem, dia da semana curto e dd/mm/aaaa', () => {
        expect(listDate('2026-10-20T09:21:00', TODAY)).toBe('09:21');
        expect(listDate('2026-10-19T17:05:00', TODAY)).toBe('Ontem');
        expect(listDate('2026-10-16T11:30:00', TODAY)).toBe('Sexta');
        expect(listDate('2026-10-15T17:00:00', TODAY)).toBe('Quinta');
        expect(listDate('2026-10-14T08:31:00', TODAY)).toBe('14/10/2026');
        expect(listDate(null, TODAY)).toBe('');
    });

    it('formata data e dia da semana', () => {
        expect(fmt('2026-10-20')).toBe('20/10/2026');
        expect(weekday('2026-10-20')).toBe('Terça-feira');
        expect(dayDiff('2025-12-31', '2026-01-01')).toBe(1);
    });
});

describe('nomes e números', () => {
    it('iniciais do avatar', () => {
        expect(initials('Maria Souza')).toBe('MS');
        expect(initials('Grupo Projeto Alfa')).toBe('PA');
        expect(initials('João')).toBe('J');
        expect(initials('ana   ribeiro silva')).toBe('AR');
    });

    it('duração de áudio', () => {
        expect(dur(42)).toBe('0:42');
        expect(dur(75)).toBe('1:15');
        expect(dur(3600)).toBe('60:00');
    });

    it('plural com separador de milhar', () => {
        expect(plural(1, 'mensagem', 'mensagens')).toBe('1 mensagem');
        expect(plural(12345, 'mensagem', 'mensagens')).toBe('12.345 mensagens');
    });

    it('tamanho de arquivo', () => {
        expect(fileSize(240 * 1024)).toBe('240 KB');
        expect(fileSize(1.2 * 1024 * 1024)).toBe('1,2 MB');
        expect(fileSize(500)).toBe('500 B');
    });

    it('cor do selo de documento', () => {
        expect(extColor('pdf')).toBe('#D64545');
        expect(extColor('XLSX')).toBe('#1E8A4C');
        expect(extColor('docx')).toBe('#6B7780');
    });

    it('atalho de busca no Mac e nos outros sistemas', () => {
        expect(searchShortcut('MacIntel')).toBe('⌘K');
        expect(searchShortcut('Win32')).toBe('Ctrl K');
    });
});
