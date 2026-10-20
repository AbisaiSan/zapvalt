import { ref } from 'vue';
import { sendJson } from '../lib/api';
import { loadTheme, setTheme, type Theme } from '../lib/theme';

const theme = ref<Theme>(typeof window === 'undefined' ? 'light' : loadTheme());

/** Tema claro ou escuro: vale na hora (localStorage) e fica salvo nas configurações. */
export function useTheme() {
    function apply(next: Theme) {
        theme.value = next;
        setTheme(next);
        sendJson('PUT', '/api/settings', { theme: next }).catch(() => {
            // Sem servidor, o tema continua valendo neste navegador.
        });
    }

    return { theme, apply, toggle: () => apply(theme.value === 'dark' ? 'light' : 'dark') };
}
