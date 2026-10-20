<script setup lang="ts">
import { computed, ref } from 'vue';
import { fileSize } from '../../lib/format';
import type { ImportFileCard } from '../../types';

const props = defineProps<{ file: ImportFileCard; upload?: { sent: number; size: number } }>();
defineEmits<{ choose: [choice: 'merge' | 'separate'] }>();

const open = ref(false);
const f = computed(() => props.file);
const uploadPct = computed(() => (props.upload ? Math.round((props.upload.sent / Math.max(1, props.upload.size)) * 100) : 0));
const busy = computed(() => ['uploading', 'waiting', 'processing'].includes(f.value.status));
const done = computed(() => f.value.status === 'done' || f.value.status === 'partial');

const pill = computed(() => {
    switch (f.value.status) {
        case 'uploading': return { text: `Enviando ${uploadPct.value}%`, cls: 'bg-accent-soft text-accent-tx' };
        case 'waiting': return { text: 'Aguardando', cls: 'bg-head text-tx2' };
        case 'processing': return { text: `Processando ${f.value.percent}%`, cls: 'bg-accent-soft text-accent-tx' };
        case 'done': return { text: 'Concluído', cls: 'bg-ok-bg text-ok-tx' };
        default: return { text: 'Erro', cls: 'bg-err-bg text-err-tx' };
    }
});

const sub = computed(() => {
    switch (f.value.status) {
        case 'uploading': return 'Enviando…';
        case 'waiting': return 'Na fila';
        case 'processing': return 'Lendo mensagens e anexos…';
        case 'failed': return 'Arquivo não reconhecido';
        default: return `Conversa: ${f.value.conversation_name ?? ''}`;
    }
});

const progress = computed(() => (f.value.status === 'uploading' ? uploadPct.value : f.value.status === 'processing' ? f.value.percent : null));

const stats = computed(() => {
    const s = f.value.stats;
    if (!s) return [];
    return [
        { n: s.new, l: 'mensagens novas', err: false },
        { n: s.repeated, l: 'repetidas', err: false },
        { n: s.media_found, l: 'anexos encontrados', err: false },
        { n: s.media_missing, l: 'anexos faltantes', err: s.media_missing > 0 },
    ];
});

const chip = (selected: boolean) => (selected ? 'bg-btn text-btn-tx border-btn' : 'bg-transparent text-warn-tx border-warn-tx');
</script>

<template>
    <div class="flex flex-col gap-2.5 border-b border-div px-4 py-[14px] last:border-b-0">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-head text-[10px] font-bold text-tx2" aria-hidden="true">{{ f.ext.toUpperCase() }}</div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] text-tx">{{ f.name }}</div>
                <div class="mt-px text-[12px] text-tx2">{{ fileSize(f.size_bytes) }} · {{ sub }}</div>
            </div>
            <span class="flex-none rounded-xl px-2.5 py-[3px] text-[12px] font-medium whitespace-nowrap" :class="pill.cls" role="status">{{ pill.text }}</span>
        </div>

        <div v-if="busy && progress !== null" class="ml-12 h-1 overflow-hidden rounded-sm bg-track" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full bg-accent transition-[width] duration-150 ease-linear" :style="{ width: `${progress}%` }" />
        </div>

        <div v-if="done" class="ml-12 grid grid-cols-[repeat(auto-fit,minmax(118px,1fr))] gap-2">
            <div v-for="s in stats" :key="s.l" class="rounded-lg border border-div bg-page px-2.5 py-2">
                <div class="text-[18px]" :class="s.err ? 'text-err-tx' : 'text-tx'">{{ s.n.toLocaleString('pt-BR') }}</div>
                <div class="text-[12px] text-tx2">{{ s.l }}</div>
            </div>
        </div>

        <div v-if="done && f.existing" class="ml-12 flex flex-col gap-2.5 rounded-lg bg-warn-bg p-3 text-[13px] leading-[18px] text-warn-tx">
            <span>
                Já existe uma conversa com <b>{{ f.existing.name }}</b> no acervo. As {{ (f.stats?.repeated ?? 0).toLocaleString('pt-BR') }} mensagens repetidas não serão duplicadas.
            </span>
            <div class="flex flex-wrap gap-2" role="group" aria-label="O que fazer com a conversa existente">
                <button type="button" class="h-8 rounded-2xl border px-[14px] text-[13px] font-medium" :class="chip(f.choice === 'merge')" :aria-pressed="f.choice === 'merge'" @click="$emit('choose', 'merge')">
                    Somar à existente
                </button>
                <button type="button" class="h-8 rounded-2xl border px-[14px] text-[13px] font-medium" :class="chip(f.choice === 'separate')" :aria-pressed="f.choice === 'separate'" @click="$emit('choose', 'separate')">
                    Manter separada
                </button>
            </div>
        </div>

        <div v-if="f.status === 'partial'" class="ml-12 flex flex-col gap-2 rounded-lg bg-err-bg p-3 text-[13px] leading-[18px] text-err-tx">
            <div class="flex items-center gap-2">
                <span class="flex-1">{{ f.unrecognized_count.toLocaleString('pt-BR') }} linhas não foram reconhecidas. As demais mensagens foram importadas.</span>
                <button type="button" class="flex-none text-[13px] font-semibold text-err-tx underline" :aria-expanded="open" @click="open = !open">{{ open ? 'Ocultar linhas' : 'Ver linhas' }}</button>
            </div>
            <div v-if="open" class="flex flex-col gap-1 overflow-x-auto rounded-md bg-panel px-2.5 py-2 font-mono text-[12px] text-tx">
                <div v-for="l in f.unrecognized_lines" :key="l.line" class="flex gap-3 whitespace-pre">
                    <span class="flex-none text-tx2">linha {{ l.line }}</span><span>{{ l.text }}</span>
                </div>
                <div v-if="f.unrecognized_count > f.unrecognized_lines.length" class="text-tx2">e mais {{ (f.unrecognized_count - f.unrecognized_lines.length).toLocaleString('pt-BR') }} linhas</div>
            </div>
        </div>

        <div v-if="f.status === 'failed'" class="ml-12 rounded-lg bg-err-bg p-3 text-[13px] leading-[18px] text-err-tx" role="alert">{{ f.error_message }}</div>
    </div>
</template>
