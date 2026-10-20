export type Theme = 'light' | 'dark';

export const THEME_STORAGE_KEY = 'zv-theme';

/** Tema salvo no navegador; claro quando não houver ou o armazenamento falhar. */
export function loadTheme(): Theme {
    try {
        return localStorage.getItem(THEME_STORAGE_KEY) === 'dark' ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

export function applyTheme(theme: Theme): void {
    document.documentElement.setAttribute('data-zv-theme', theme);
}

export function setTheme(theme: Theme): void {
    applyTheme(theme);
    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
        // Sem armazenamento disponível, o tema vale só até recarregar.
    }
}
