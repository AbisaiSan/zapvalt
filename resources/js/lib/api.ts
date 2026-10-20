// Cliente da API local. Só fala com o próprio servidor (127.0.0.1).

export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        /** Segundos de espera antes de tentar a senha de novo. */
        public retryAfter = 0,
    ) {
        super(message);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/** Com a senha ativa e a sessão bloqueada (ou expirada), o servidor responde 423. */
export const LOCKED = 423;

async function parse<T>(response: Response): Promise<T> {
    if (response.status === LOCKED && typeof window !== 'undefined') {
        window.location.assign('/desbloquear');
    }
    if (!response.ok) {
        let message = 'Algo deu errado. Tente de novo.';
        let retryAfter = 0;
        try {
            const body = await response.json();
            if (typeof body?.message === 'string' && body.message) message = body.message;
            if (typeof body?.retry_after === 'number') retryAfter = body.retry_after;
        } catch {
            // Resposta sem JSON: fica a mensagem padrão.
        }
        throw new ApiError(response.status, message, retryAfter);
    }
    return response.status === 204 ? (undefined as T) : ((await response.json()) as T);
}

export async function getJson<T>(url: string, signal?: AbortSignal): Promise<T> {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal });
    return parse<T>(response);
}

export async function sendJson<T = void>(method: 'POST' | 'PUT' | 'PATCH' | 'DELETE', url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
    return parse<T>(response);
}

export function isAbort(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

export function query(params: Record<string, string | number | null | undefined>): string {
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value !== null && value !== undefined && value !== '') search.set(key, String(value));
    }
    const s = search.toString();
    return s ? `?${s}` : '';
}
