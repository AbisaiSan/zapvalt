import { computed, reactive, watch } from 'vue';
import { getJson, isAbort, query } from '../lib/api';
import { searchable } from '../lib/search';
import type { ConversationItem, MessageResult, MessageSearchPage } from '../types';

export type SearchTab = 'chats' | 'msgs';

// Estado da busca da lista, compartilhado com a conversa (resultado clicado fica destacado).
const state = reactive({
    q: '',
    term: '',
    loading: false,
    tab: 'chats' as SearchTab,
    pinned: false,
    conversations: [] as ConversationItem[],
    conversationTotal: 0,
    messages: [] as MessageResult[],
    messageTotal: 0,
    messageCapped: false,
    messageConversations: 0,
    nextCursor: null as string | null,
    loadingMore: false,
    error: '',
    lastResultId: null as number | null,
});

let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;
let started = false;

async function run(term: string) {
    controller?.abort();
    controller = new AbortController();
    const signal = controller.signal;

    try {
        const [convs, msgs] = await Promise.all([
            getJson<{ items: ConversationItem[]; total: number }>('/api/search/conversations' + query({ q: term }), signal),
            getJson<MessageSearchPage>('/api/search/messages' + query({ q: term }), signal),
        ]);

        state.term = term;
        state.conversations = convs.items;
        state.conversationTotal = convs.total;
        state.messages = msgs.results;
        state.messageTotal = msgs.total ?? 0;
        state.messageCapped = msgs.total_capped ?? false;
        state.messageConversations = msgs.conversations ?? 0;
        state.nextCursor = msgs.next_cursor;
        state.error = '';
        if (!state.pinned) state.tab = convs.total > 0 ? 'chats' : 'msgs';
        state.loading = false;
    } catch (error) {
        if (isAbort(error)) return;
        state.error = 'Não foi possível buscar agora. Tente de novo.';
        state.loading = false;
    }
}

function start() {
    if (started) return;
    started = true;

    // Espera 250 ms depois da última tecla e cancela a busca anterior.
    watch(
        () => state.q,
        (q) => {
            clearTimeout(timer);
            if (!q) state.pinned = false;

            if (!searchable(q)) {
                controller?.abort();
                state.loading = false;
                return;
            }

            state.loading = true;
            timer = setTimeout(() => run(q.trim()), 250);
        },
    );
}

async function loadMoreMessages() {
    if (!state.nextCursor || state.loadingMore || state.loading) return;
    state.loadingMore = true;
    try {
        const page = await getJson<MessageSearchPage>('/api/search/messages' + query({ q: state.term, cursor: state.nextCursor }));
        state.messages.push(...page.results);
        state.nextCursor = page.next_cursor;
    } finally {
        state.loadingMore = false;
    }
}

export function useSearch() {
    start();

    return {
        search: state,
        active: computed(() => searchable(state.q)),
        pick: (tab: SearchTab) => {
            state.tab = tab;
            state.pinned = true;
        },
        clear: () => {
            state.q = '';
            state.pinned = false;
        },
        loadMoreMessages,
    };
}
