import { onBeforeUnmount } from 'vue';
import { onEscape } from '../lib/escape';

/** Registra um tratamento de Esc enquanto o componente existir. */
export function useEscape(priority: number, handler: () => boolean): void {
    const off = onEscape(priority, handler);
    onBeforeUnmount(off);
}
