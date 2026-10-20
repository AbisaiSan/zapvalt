import { reactive } from 'vue';
import { getJson, query } from '../lib/api';
import { cursorFor } from '../lib/rows';
import type { Message, MessageWindow } from '../types';

/** Mensagens pedidas por vez e o máximo mantido na tela (o resto é descartado e pedido de novo se preciso). */
export const PAGE = 100;
export const MAX_IN_DOM = 300;

/**
 * Janela de mensagens de uma conversa, carregada aos poucos por cursor.
 * Nunca mantém a conversa inteira no navegador.
 */
export function useMessageWindow() {
    const state = reactive({
        conversationId: 0,
        messages: [] as Message[],
        hasOlder: false,
        hasNewer: false,
        loading: false,
        loadingOlder: false,
        loadingNewer: false,
        error: '',
        version: 0,
    });

    function replace(conversationId: number, w: MessageWindow) {
        state.conversationId = conversationId;
        state.messages = w.messages;
        state.hasOlder = w.has_older;
        state.hasNewer = w.has_newer;
        state.version++;
    }

    async function open(conversationId: number, params: Record<string, string | number | null> = {}): Promise<number | null> {
        state.loading = true;
        state.error = '';
        try {
            const w = await getJson<MessageWindow>(`/api/conversations/${conversationId}/messages` + query({ limit: PAGE, ...params }));
            replace(conversationId, w);
            return w.focus_id ?? null;
        } catch {
            state.error = 'Não foi possível carregar as mensagens.';
            return null;
        } finally {
            state.loading = false;
        }
    }

    const latest = (id: number) => open(id);
    const around = (id: number, messageId: number) => open(id, { around: messageId, limit: PAGE });
    const day = (id: number, d: string) => open(id, { day: d });

    async function older(): Promise<boolean> {
        if (!state.hasOlder || state.loadingOlder || state.loading || !state.messages.length) return false;
        state.loadingOlder = true;
        const id = state.conversationId;
        try {
            const w = await getJson<MessageWindow>(`/api/conversations/${id}/messages` + query({ before: cursorFor(state.messages[0]), limit: PAGE }));
            if (id !== state.conversationId) return false;
            const merged = [...w.messages, ...state.messages];
            state.hasOlder = w.has_older;
            if (merged.length > MAX_IN_DOM) {
                merged.length = MAX_IN_DOM;
                state.hasNewer = true;
            }
            state.messages = merged;
            return true;
        } finally {
            state.loadingOlder = false;
        }
    }

    async function newer(): Promise<boolean> {
        if (!state.hasNewer || state.loadingNewer || state.loading || !state.messages.length) return false;
        state.loadingNewer = true;
        const id = state.conversationId;
        try {
            const last = state.messages[state.messages.length - 1];
            const w = await getJson<MessageWindow>(`/api/conversations/${id}/messages` + query({ after: cursorFor(last), limit: PAGE }));
            if (id !== state.conversationId) return false;
            let merged = [...state.messages, ...w.messages];
            state.hasNewer = w.has_newer;
            if (merged.length > MAX_IN_DOM) {
                merged = merged.slice(merged.length - MAX_IN_DOM);
                state.hasOlder = true;
            }
            state.messages = merged;
            return true;
        } finally {
            state.loadingNewer = false;
        }
    }

    const has = (messageId: number) => state.messages.some((m) => m.id === messageId);

    return { win: state, latest, around, day, older, newer, has };
}
