import { ref } from 'vue';

// Última conversa aberta: fechar Importar ou Configurações volta para ela.
const lastConversationId = ref<number | null>(null);

export function useLastConversation() {
    return lastConversationId;
}
