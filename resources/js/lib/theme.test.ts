import { beforeEach, describe, expect, it } from 'vitest';
import { applyTheme, loadTheme, setTheme, THEME_STORAGE_KEY } from './theme';

describe('tema', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.removeAttribute('data-zv-theme');
    });

    it('usa o tema claro quando nada foi salvo', () => {
        expect(loadTheme()).toBe('light');
    });

    it('lê o tema escuro salvo em zv-theme', () => {
        localStorage.setItem(THEME_STORAGE_KEY, 'dark');
        expect(loadTheme()).toBe('dark');
    });

    it('ignora valores desconhecidos', () => {
        localStorage.setItem(THEME_STORAGE_KEY, 'roxo');
        expect(loadTheme()).toBe('light');
    });

    it('aplica o tema no atributo data-zv-theme do html', () => {
        applyTheme('dark');
        expect(document.documentElement.getAttribute('data-zv-theme')).toBe('dark');
    });

    it('salva e aplica o tema escolhido', () => {
        setTheme('dark');
        expect(localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark');
        expect(document.documentElement.getAttribute('data-zv-theme')).toBe('dark');
    });
});
