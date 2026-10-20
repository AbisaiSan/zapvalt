<script setup lang="ts">
import { computed } from 'vue';
import { useEscape } from '../composables/useEscape';
import { ESC } from '../lib/escape';
import { dayOf, extColor, fileSize, fmt, timeOf } from '../lib/format';
import type { Message } from '../types';
import Icon from './Icon.vue';

const props = defineProps<{ message: Message }>();
const emit = defineEmits<{ close: [] }>();

const a = computed(() => props.message.attachment!);
const ext = computed(() => a.value.ext.toUpperCase());
const isPdf = computed(() => ext.value === 'PDF');
const meta = computed(() =>
    [
        a.value.pages ? `${a.value.pages} ${a.value.pages === 1 ? 'página' : 'páginas'}` : null,
        fileSize(a.value.size_bytes),
        `${fmt(dayOf(props.message.sent_at))} às ${timeOf(props.message.sent_at)}`,
        props.message.sender ? `enviado por ${props.message.sender.is_me ? 'você' : props.message.sender.name}` : null,
    ]
        .filter(Boolean)
        .join(' · '),
);

useEscape(ESC.preview, () => {
    emit('close');
    return true;
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex flex-col bg-viewer text-tx" role="dialog" aria-modal="true" :aria-label="`Documento ${a.name}`">
            <div class="flex h-[60px] flex-none items-center gap-3 pr-3 pl-4">
                <div
                    class="box-border flex h-[38px] w-[30px] flex-none items-end justify-center rounded pb-[5px] text-[9px] font-bold text-white"
                    :style="{ background: extColor(ext) }"
                    aria-hidden="true"
                >
                    {{ ext }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[15px]">{{ a.name }}</div>
                    <div class="truncate text-[13px] text-tx2">{{ meta }}</div>
                </div>
                <a
                    :href="`${a.url}?download=1`"
                    download
                    title="Baixar"
                    class="flex h-[38px] flex-none items-center gap-1.5 rounded-[19px] pr-[14px] pl-2.5 text-[14px] text-tx no-underline hover:bg-press hover:text-tx"
                >
                    <Icon name="download" /><span>Baixar</span>
                </a>
                <button type="button" title="Fechar (Esc)" aria-label="Fechar pré-visualização" class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-tx hover:bg-press" @click="$emit('close')">
                    <Icon name="close" :size="22" />
                </button>
            </div>

            <div class="flex min-h-0 flex-1 flex-col items-center px-4 pt-2 pb-4">
                <iframe v-if="isPdf" :src="a.url" :title="`Pré-visualização de ${a.name}`" class="h-full w-full max-w-[900px] flex-1 rounded-[3px] border-0 bg-white shadow-[0_4px_24px_rgba(0,0,0,.18)]" />
                <div v-else class="mt-[12vh] flex max-w-[320px] flex-col items-center gap-2.5 text-center">
                    <div class="box-border flex h-20 w-16 items-end justify-center rounded-md pb-2.5 text-[13px] font-bold text-white" :style="{ background: extColor(ext) }" aria-hidden="true">{{ ext }}</div>
                    <div class="mt-1.5 text-[15px]">Pré-visualização indisponível</div>
                    <div class="text-[13px] leading-[18px] text-tx2">Arquivos .{{ a.ext.toLowerCase() }} não podem ser exibidos aqui. Baixe para abrir no programa adequado.</div>
                    <a
                        :href="`${a.url}?download=1`"
                        download
                        class="mt-1.5 inline-flex h-[38px] items-center rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx no-underline hover:text-btn-tx"
                    >
                        Baixar arquivo
                    </a>
                </div>
            </div>
        </div>
    </Teleport>
</template>
