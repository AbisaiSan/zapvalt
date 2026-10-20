import { onBeforeUnmount, onMounted, reactive } from 'vue';
import { ApiError, getJson, sendJson } from '../lib/api';
import type { ImportState } from '../types';

/** Envios em andamento neste navegador: bytes enviados por upload_id. */
const uploads = reactive<Record<number, { sent: number; size: number }>>({});
const rejected = reactive<{ name: string; message: string }[]>([]);
const state = reactive<{ data: ImportState | null; offline: boolean }>({ data: null, offline: false });
let queue: Promise<void> = Promise.resolve();

function xsrf(): string {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

async function refresh() {
    try {
        state.data = await getJson<ImportState>('/api/imports');
        state.offline = false;
    } catch (e) {
        state.offline = true;
        throw e;
    }
}

/** Envia um arquivo em pedaços (8 MB), um arquivo por vez, na ordem em que foram escolhidos. */
async function upload(file: File) {
    const start = await sendJson<{ upload_id: number; chunk_size: number }>('POST', '/api/imports/uploads', { name: file.name, size: file.size });
    uploads[start.upload_id] = { sent: 0, size: file.size };
    await refresh();

    for (let index = 0, offset = 0; offset < file.size; index++, offset += start.chunk_size) {
        const response = await fetch(`/api/imports/uploads/${start.upload_id}?index=${index}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/octet-stream', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            body: file.slice(offset, offset + start.chunk_size),
        });
        if (!response.ok) throw new ApiError(response.status, 'O envio foi interrompido. Tente de novo.');
        uploads[start.upload_id].sent = Math.min(file.size, offset + start.chunk_size);
    }

    await sendJson('POST', `/api/imports/uploads/${start.upload_id}/complete`);
    delete uploads[start.upload_id];
}

export function addFiles(files: File[]) {
    for (const file of files) {
        if (!/\.(txt|zip)$/i.test(file.name)) {
            rejected.push({ name: file.name, message: 'Só é possível importar arquivos .txt ou .zip.' });
            continue;
        }
        queue = queue
            .then(() => upload(file))
            .catch((e) => {
                rejected.push({ name: file.name, message: e instanceof ApiError ? e.message : 'O envio foi interrompido. Tente de novo.' });
            })
            .finally(refresh);
    }
}

export function useImport() {
    let timer: ReturnType<typeof setInterval> | undefined;

    onMounted(() => {
        refresh().catch(() => {});
        // A tela consulta o andamento a cada 700 ms.
        timer = setInterval(() => refresh().catch(() => {}), 700);
    });
    onBeforeUnmount(() => clearInterval(timer));

    return {
        imp: state,
        uploads,
        rejected,
        refresh,
        choose: (id: number, choice: 'merge' | 'separate') => sendJson('PATCH', `/api/imports/files/${id}`, { choice }).then(refresh),
        commit: (nameChoice: string | null) => sendJson<{ new: number; updated: number }>('POST', '/api/imports/commit', { name_choice: nameChoice }),
        cancel: () => {
            rejected.splice(0);
            return sendJson('POST', '/api/imports/cancel').then(refresh);
        },
    };
}
