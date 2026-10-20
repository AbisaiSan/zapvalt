<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icon from '../components/Icon.vue';
import ImportFileCard from '../components/import/ImportFileCard.vue';
import NameQuestion from '../components/import/NameQuestion.vue';
import PageHeader from '../components/PageHeader.vue';
import { useArchive } from '../composables/useArchive';
import { addFiles, useImport } from '../composables/useImport';
import { usePageClose } from '../composables/usePageClose';
import { useToast } from '../composables/useToast';
import AppShell from '../layouts/AppShell.vue';
import { ApiError } from '../lib/api';

defineOptions({ layout: AppShell });

const close = usePageClose();
const { imp, uploads, rejected, choose, commit, cancel } = useImport();
const { refresh: refreshArchive } = useArchive();
const { show } = useToast();

const dragging = ref(false);
const picker = ref<HTMLInputElement | null>(null);
const nameChoice = ref<string | null>(null);
const working = ref(false);
const error = ref('');

const files = computed(() => imp.data?.files ?? []);
const processed = computed(() => files.value.filter((f) => ['done', 'partial', 'failed'].includes(f.status)).length);
const question = computed(() => imp.data?.name_question);
const canCommit = computed(() => !!imp.data?.can_commit && !Object.keys(uploads).length && !working.value);

// Começa com o nome sugerido marcado, como no protótipo.
watch(question, (q, old) => {
    if (q?.ask && !old?.ask) nameChoice.value = q.candidates.find((c) => c.suggested)?.name ?? null;
});

function onDrop(event: DragEvent) {
    dragging.value = false;
    addFiles([...(event.dataTransfer?.files ?? [])]);
}

function onLeave(event: DragEvent) {
    if (!(event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) dragging.value = false;
}

function onPick(event: Event) {
    const input = event.target as HTMLInputElement;
    addFiles([...(input.files ?? [])]);
    input.value = '';
}

async function finish() {
    if (!canCommit.value) return;
    working.value = true;
    error.value = '';
    try {
        const r = await commit(question.value?.ask ? nameChoice.value : null);
        await refreshArchive();
        const parts = [`${r.new} ${r.new === 1 ? 'conversa nova' : 'conversas novas'}`];
        if (r.updated) parts.push(`${r.updated} ${r.updated === 1 ? 'atualizada' : 'atualizadas'}`);
        show(`Importação concluída: ${parts.join(' e ')}`);
        router.visit('/');
    } catch (e) {
        error.value = e instanceof ApiError ? e.message : 'Não foi possível concluir. Tente de novo.';
    } finally {
        working.value = false;
    }
}

async function abort() {
    working.value = true;
    try {
        await cancel();
        close();
    } finally {
        working.value = false;
    }
}
</script>

<template>
    <Head title="Importar conversas" />
    <div class="flex min-h-0 flex-1 flex-col">
        <PageHeader title="Importar conversas" @close="close">
            <span v-if="files.length" class="text-[12px] whitespace-nowrap text-tx2">{{ processed }} de {{ files.length }} arquivos processados</span>
        </PageHeader>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto flex max-w-[760px] flex-col gap-5 px-6 pt-7 pb-8">
                <div>
                    <h2 class="m-0 mb-1.5 text-[20px] font-medium text-tx">Traga conversas exportadas do celular</h2>
                    <p class="m-0 text-[14px] leading-[21px] text-pretty text-tx2">
                        No aplicativo, abra a conversa e use Mais opções › Exportar conversa. Arquivos .txt trazem só o texto; .zip trazem também fotos, áudios e documentos.
                    </p>
                </div>

                <div
                    class="flex flex-col items-center gap-2.5 rounded-xl border-2 border-dashed px-6 py-9 text-center transition-[background,border-color] duration-150"
                    :class="dragging ? 'border-accent bg-accent-soft' : 'border-div2 bg-panel'"
                    @dragover.prevent="dragging = true"
                    @dragleave="onLeave"
                    @drop.prevent="onDrop"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-full" :class="dragging ? 'bg-btn text-btn-tx' : 'bg-head text-tx2'"><Icon name="upload" :size="26" /></div>
                    <template v-if="dragging">
                        <div class="text-[18px] font-medium text-accent-tx">Solte aqui</div>
                        <div class="text-[13px] text-tx2">Os arquivos são lidos neste computador</div>
                    </template>
                    <template v-else>
                        <div class="text-[16px] text-tx">Arraste arquivos .txt ou .zip para cá</div>
                        <div class="text-[13px] text-tx2">ou</div>
                        <button type="button" class="h-[38px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx" @click="picker?.click()">Escolher arquivos</button>
                        <input ref="picker" type="file" multiple accept=".txt,.zip,text/plain,application/zip" class="hidden" @change="onPick" />
                    </template>
                </div>

                <div v-if="imp.offline" class="rounded-lg bg-warn-bg p-3 text-[13px] leading-[18px] text-warn-tx" role="alert">
                    Sem resposta do ZapVault. Confira se ele continua rodando no Terminal; a tela tenta de novo sozinha.
                </div>

                <div v-for="r in rejected" :key="r.name + r.message" class="rounded-lg bg-err-bg p-3 text-[13px] leading-[18px] text-err-tx" role="alert">
                    <b>{{ r.name }}</b>: {{ r.message }}
                </div>

                <div v-if="files.length" class="overflow-hidden rounded-xl border border-div bg-panel">
                    <ImportFileCard v-for="f in files" :key="f.id" :file="f" :upload="uploads[f.id]" @choose="choose(f.id, $event)" />
                </div>

                <NameQuestion v-if="question?.ask" v-model="nameChoice" :candidates="question.candidates" />
            </div>
        </div>

        <footer v-if="files.length" class="flex flex-none flex-wrap items-center gap-2.5 border-t border-div bg-panel px-6 py-3">
            <span class="min-w-40 flex-1 text-[13px]" :class="error ? 'text-err-tx' : 'text-tx2'" role="status">{{ error || imp.data?.hint }}</span>
            <button type="button" class="h-[38px] rounded-[19px] px-4 text-[14px] font-medium text-accent-tx hover:bg-press" :disabled="working" @click="abort">Cancelar</button>
            <button
                type="button"
                class="h-[38px] rounded-[19px] px-5 text-[14px] font-medium"
                :class="canCommit ? 'cursor-pointer bg-btn text-btn-tx' : 'cursor-not-allowed bg-head text-tx2'"
                :aria-disabled="!canCommit"
                @click="finish"
            >
                Concluir
            </button>
        </footer>
    </div>
</template>
