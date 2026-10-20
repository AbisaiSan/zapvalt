<script setup lang="ts">
import { computed } from 'vue';
import { timeOf } from '../../lib/format';
import { withLinks } from '../../lib/links';
import { ranges, segs, type Range } from '../../lib/search';
import type { Message } from '../../types';
import HighlightText from '../HighlightText.vue';
import Icon from '../Icon.vue';
import AudioBody from './AudioBody.vue';
import DocBody from './DocBody.vue';
import VisualBody from './VisualBody.vue';

const props = defineProps<{
    message: Message;
    first: boolean;
    isGroup: boolean;
    flash: boolean;
    /** Termo da busca na conversa e qual ocorrência desta mensagem é a atual (-1 para nenhuma). */
    term: string;
    currentNth: number;
}>();
defineEmits<{ openMedia: [messageId: number]; openDoc: [messageId: number] }>();

const m = computed(() => props.message);
const mine = computed(() => !!m.value.sender?.is_me);
const sticker = computed(() => m.value.kind === 'sticker');
const visual = computed(() => (m.value.kind === 'image' || m.value.kind === 'video') && !!m.value.attachment);
const showSender = computed(() => props.isGroup && !mine.value && props.first && !sticker.value && !!m.value.sender);
const time = computed(() => timeOf(m.value.sent_at));

const bubbleStyle = computed(() => {
    const radius = props.first ? (mine.value ? '8px 2px 8px 8px' : '2px 8px 8px 8px') : '8px';
    let padding = '6px 8px 8px 9px';
    if (visual.value || m.value.kind === 'doc') padding = '3px';
    if (m.value.kind === 'audio') padding = '7px 8px 4px 7px';
    if (sticker.value) padding = '0';
    return {
        borderRadius: radius,
        padding,
        background: sticker.value ? 'transparent' : mine.value ? 'var(--out)' : 'var(--in)',
        boxShadow: sticker.value ? 'none' : 'var(--shadow)',
    };
});

/** Trechos com destaque para o campo pesquisável da mensagem. */
function highlighted(text: string) {
    const rs: Range[] = props.term ? ranges(text, props.term).map(([a, b], i) => [a, b, i === props.currentNth]) : [];
    return withLinks(text, segs(text, rs));
}

const textSegments = computed(() => highlighted(m.value.text ?? ''));
const captionSegments = computed(() => (m.value.caption ? highlighted(m.value.caption) : []));
const fileSegments = computed(() => highlighted(m.value.attachment?.name ?? ''));
const timeOverlay = computed(() => visual.value && !m.value.caption);
</script>

<template>
    <div
        :data-mid="m.id"
        class="-mx-2 flex rounded-lg px-2 py-px transition-[background] duration-1000"
        :class="[mine ? 'justify-end' : 'justify-start', first ? 'mt-2.5' : 'mt-0.5']"
        :style="{ background: flash ? 'var(--flash)' : 'transparent' }"
    >
        <div class="relative max-w-[65%] min-w-0 text-[14px] leading-[19px] text-tx" :style="bubbleStyle">
            <div
                v-if="showSender"
                class="text-[12.5px] leading-[18px] font-semibold"
                :style="{ color: `var(--s${m.sender?.color_slot ?? 0})`, padding: visual || m.kind === 'doc' ? '2px 5px 4px' : '0 0 1px' }"
            >
                {{ m.sender?.name }}
            </div>

            <template v-if="m.kind === 'text'">
                <span class="whitespace-pre-wrap [overflow-wrap:anywhere]"><HighlightText :segments="textSegments" /></span>
                <span class="inline-block h-px w-10" aria-hidden="true" />
            </template>

            <template v-else-if="visual && m.attachment">
                <VisualBody :kind="m.kind as 'image' | 'video'" :attachment="m.attachment" @open="$emit('openMedia', m.id)" />
                <div v-if="m.caption" class="pt-[5px] pr-[5px] pb-[3px] pl-1.5 whitespace-pre-wrap [overflow-wrap:anywhere]">
                    <HighlightText :segments="captionSegments" /><span class="inline-block h-px w-10" aria-hidden="true" />
                </div>
            </template>

            <AudioBody v-else-if="m.kind === 'audio' && m.attachment" :message-id="m.id" :attachment="m.attachment" :mine="mine" />

            <DocBody v-else-if="m.kind === 'doc' && m.attachment" :attachment="m.attachment" :mine="mine" :name-segments="fileSegments" @open="$emit('openDoc', m.id)" />

            <template v-else-if="sticker && m.attachment">
                <img :src="m.attachment.thumb_url ?? m.attachment.url" alt="Figurinha" loading="lazy" class="h-[130px] w-[130px] object-contain" />
                <div class="mt-1 flex justify-end">
                    <span class="rounded-lg bg-chip px-[7px] py-px text-[11px] text-tx2 shadow-bubble">{{ time }}</span>
                </div>
            </template>

            <div v-else-if="m.kind === 'deleted'" class="flex items-center gap-[7px] pr-[42px] italic" :class="mine ? 'text-tx2-out' : 'text-tx2'">
                <Icon name="deleted" :size="16" />
                <span>{{ mine ? 'Você apagou esta mensagem' : 'Essa mensagem foi apagada' }}</span>
            </div>

            <div v-else class="flex items-center gap-[7px] pr-[42px] italic" :class="mine ? 'text-tx2-out' : 'text-tx2'">
                <Icon name="no-media" :size="17" />
                <span>Mídia não incluída na exportação</span>
            </div>

            <span v-if="timeOverlay" class="absolute right-2.5 bottom-[7px] text-[11px] text-white [text-shadow:0_1px_2px_rgba(0,0,0,.6)]">{{ time }}</span>
            <span v-else-if="!sticker" class="absolute right-2 bottom-[3px] text-[11px] leading-[15px]" :class="mine ? 'text-tx2-out' : 'text-tx2'">{{ time }}</span>
        </div>
    </div>
</template>
