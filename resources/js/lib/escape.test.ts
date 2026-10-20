import { describe, expect, it } from 'vitest';
import { ESC, handleEscape, onEscape } from './escape';

describe('Esc', () => {
    it('trata pela ordem de prioridade, não pela ordem de registro', () => {
        const calls: string[] = [];
        const offPage = onEscape(ESC.page, () => (calls.push('page'), true));
        const offSearch = onEscape(ESC.listSearch, () => (calls.push('search'), true));
        const offDate = onEscape(ESC.datePopover, () => false);

        expect(handleEscape()).toBe(true);
        expect(calls).toEqual(['search']);

        offSearch();
        handleEscape();
        expect(calls).toEqual(['search', 'page']);

        offPage();
        offDate();
        expect(handleEscape()).toBe(false);
    });
});
