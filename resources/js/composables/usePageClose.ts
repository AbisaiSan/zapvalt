import { router } from '@inertiajs/vue3';
import { ESC } from '../lib/escape';
import { useEscape } from './useEscape';
import { useLastConversation } from './useLastConversation';

/** Fechar Importar ou Configurações: volta para a última conversa aberta, ou para o início. */
export function usePageClose() {
    const last = useLastConversation();
    const close = () => router.visit(last.value ? `/c/${last.value}` : '/');

    useEscape(ESC.page, () => {
        close();
        return true;
    });

    return close;
}
