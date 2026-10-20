import { reactive } from 'vue';
import { getJson, query } from '../lib/api';
import type { ArchiveSummary, ConversationItem } from '../types';

// Lista de conversas e resumo do acervo, compartilhados entre o painel esquerdo e as páginas.
const state = reactive({
    booting: true,
    items: [] as ConversationItem[],
    nextCursor: null as string | null,
    loadingMore: false,
    summary: null as ArchiveSummary | null,
    error: '' as string,
});

async function refresh(): Promise<void> {
    state.error = '';
    try {
        const [page, summary] = await Promise.all([
            getJson<{ items: ConversationItem[]; next_cursor: string | null }>('/api/conversations'),
            getJson<ArchiveSummary>('/api/archive/summary'),
        ]);
        state.items = page.items;
        state.nextCursor = page.next_cursor;
        state.summary = summary;
    } catch {
        state.error = 'Não foi possível abrir o acervo. Confira se o ZapVault está rodando e recarregue a página.';
    } finally {
        state.booting = false;
    }
}

async function loadMore(): Promise<void> {
    if (!state.nextCursor || state.loadingMore) return;
    state.loadingMore = true;
    try {
        const page = await getJson<{ items: ConversationItem[]; next_cursor: string | null }>('/api/conversations' + query({ cursor: state.nextCursor }));
        const known = new Set(state.items.map((c) => c.id));
        state.items.push(...page.items.filter((c) => !known.has(c.id)));
        state.nextCursor = page.next_cursor;
    } finally {
        state.loadingMore = false;
    }
}

/** Troca um item da lista (por exemplo, depois de renomear a conversa). */
function replaceItem(item: ConversationItem): void {
    const i = state.items.findIndex((c) => c.id === item.id);
    if (i >= 0) state.items[i] = item;
}

export function useArchive() {
    return { archive: state, refresh, loadMore, replaceItem };
}
