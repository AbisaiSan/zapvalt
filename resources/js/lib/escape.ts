// Esc fecha, nesta ordem: visualizador, pré-visualização, seletor de data, busca na conversa,
// busca da lista e, por fim, as páginas Importar e Configurações.

export const ESC = { viewer: 60, preview: 50, datePopover: 40, inChatSearch: 30, listSearch: 20, page: 10 } as const;

type Handler = () => boolean;

const handlers = new Map<symbol, { priority: number; handler: Handler }>();

/** Registra quem quer tratar o Esc; devolve a função para remover. Handler devolve true se tratou. */
export function onEscape(priority: number, handler: Handler): () => void {
    const key = Symbol();
    handlers.set(key, { priority, handler });
    return () => handlers.delete(key);
}

export function handleEscape(): boolean {
    const ordered = [...handlers.values()].sort((a, b) => b.priority - a.priority);
    return ordered.some(({ handler }) => handler());
}
