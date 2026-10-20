<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useEscape } from '../composables/useEscape';
import { useUserNames } from '../composables/useUserNames';
import { getJson } from '../lib/api';
import { ESC } from '../lib/escape';
import { AVATAR_COLORS, dayOf, fmt, initials, timeOf } from '../lib/format';
import Icon from './Icon.vue';

interface Item {
    message_id: number;
    attachment_id: number;
    kind: 'image' | 'video';
    sender: { name: string; is_me: boolean } | null;
    sent_at: string;
    caption: string | null;
    name: string;
    width: number | null;
    height: number | null;
    url: string;
}

const props = defineProps<{ conversationId: number; messageId: number; avatarIndex: number; narrow: boolean }>();
const emit = defineEmits<{ close: [] }>();

const names = useUserNames();
const items = ref<Item[]>([]);
const index = ref(0);
const failed = ref(false);
const loaded = ref(false);

const item = computed(() => items.value[index.value] ?? null);
const who = computed(() => (item.value?.sender?.is_me ? 'Você' : item.value?.sender?.name ?? ''));
const avatar = computed(() => {
    const s = item.value?.sender;
    if (s?.is_me) return { text: initials(names.value?.[0] || 'Você'), bg: '#008069' };
    return { text: initials(s?.name ?? '?'), bg: AVATAR_COLORS[props.avatarIndex % 8] };
});
const frame = computed(() => {
    const i = item.value;
    const ratio = i?.width && i.height ? i.width / i.height : 4 / 3;
    return {
        width: `min(calc(100vw - ${props.narrow ? 32 : 200}px), calc((100vh - 220px) * ${ratio.toFixed(3)}))`,
        aspectRatio: i?.width && i.height ? `${i.width} / ${i.height}` : undefined,
    };
});

function step(delta: number) {
    const next = index.value + delta;
    if (next >= 0 && next < items.value.length) index.value = next;
}

function onKey(event: KeyboardEvent) {
    if (event.key === 'ArrowLeft') step(-1);
    if (event.key === 'ArrowRight') step(1);
}

watch(index, () => (failed.value = false));

useEscape(ESC.viewer, () => {
    emit('close');
    return true;
});

onMounted(async () => {
    window.addEventListener('keydown', onKey);
    try {
        items.value = (await getJson<{ items: Item[] }>(`/api/conversations/${props.conversationId}/media`)).items;
        index.value = Math.max(0, items.value.findIndex((i) => i.message_id === props.messageId));
    } finally {
        loaded.value = true;
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex flex-col bg-viewer text-tx" role="dialog" aria-modal="true" :aria-label="item ? `${item.kind === 'video' ? 'Vídeo' : 'Foto'} ${item.name}` : 'Visualizador'">
            <div class="flex h-[60px] flex-none items-center gap-3 pr-3 pl-4">
                <div class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-[15px] font-medium text-white" :style="{ background: avatar.bg }" aria-hidden="true">{{ avatar.text }}</div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[15px]">{{ who }}</div>
                    <div v-if="item" class="truncate text-[13px] text-tx2">{{ fmt(dayOf(item.sent_at)) }} às {{ timeOf(item.sent_at) }} · {{ item.name }}</div>
                </div>
                <a
                    v-if="item"
                    :href="`${item.url}?download=1`"
                    download
                    title="Baixar"
                    class="flex h-[38px] flex-none items-center gap-1.5 rounded-[19px] pr-[14px] pl-2.5 text-[14px] text-tx no-underline hover:bg-press hover:text-tx"
                >
                    <Icon name="download" /><span>Baixar</span>
                </a>
                <button type="button" title="Fechar (Esc)" aria-label="Fechar visualizador" class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-tx hover:bg-press" @click="$emit('close')">
                    <Icon name="close" :size="22" />
                </button>
            </div>

            <div class="relative flex min-h-0 flex-1 items-center justify-center px-4">
                <button
                    type="button"
                    title="Anterior (←)"
                    aria-label="Anterior"
                    :disabled="index === 0"
                    class="absolute top-1/2 left-5 z-[2] flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-panel text-tx shadow-pop disabled:cursor-default disabled:opacity-30"
                    @click="step(-1)"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>

                <div v-if="item" class="relative overflow-hidden rounded bg-skel shadow-[0_8px_40px_rgba(0,0,0,.25)]" :style="frame">
                    <video v-if="item.kind === 'video'" :key="item.url" :src="item.url" controls autoplay preload="metadata" class="block h-full w-full bg-black" @error="failed = true" />
                    <img v-else :key="item.url" :src="item.url" :alt="item.caption || item.name" class="block h-full w-full object-contain" @error="failed = true" />
                    <div v-if="failed" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-head p-4 text-center text-[13px] text-tx2" role="alert">
                        <Icon name="alert" :size="26" />
                        <span>{{ item.kind === 'video' ? 'Não foi possível abrir o vídeo' : 'Não foi possível abrir a imagem' }}</span>
                    </div>
                </div>
                <div v-else-if="loaded" class="text-[14px] text-tx2">Nenhuma imagem ou vídeo nesta conversa.</div>

                <button
                    type="button"
                    title="Próxima (→)"
                    aria-label="Próxima"
                    :disabled="index >= items.length - 1"
                    class="absolute top-1/2 right-5 z-[2] flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-panel text-tx shadow-pop disabled:cursor-default disabled:opacity-30"
                    @click="step(1)"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
            </div>

            <div class="flex flex-none flex-col items-center gap-1 px-4 pt-[14px] pb-5 text-center">
                <div v-if="item?.caption" class="max-w-[600px] text-[14px] whitespace-pre-wrap text-tx">{{ item.caption }}</div>
                <div v-if="items.length" class="text-[12px] text-tx2">{{ index + 1 }} de {{ items.length }} · setas do teclado trocam a imagem · Esc fecha</div>
            </div>
        </div>
    </Teleport>
</template>
