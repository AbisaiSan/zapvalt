<script setup lang="ts">
import { computed } from 'vue';
import { extColor, fileSize } from '../../lib/format';
import type { Segment } from '../../lib/search';
import type { Attachment } from '../../types';
import HighlightText from '../HighlightText.vue';
import Icon from '../Icon.vue';

const props = defineProps<{ attachment: Attachment; mine: boolean; nameSegments: Segment[] }>();
defineEmits<{ open: [] }>();

const ext = computed(() => props.attachment.ext.toUpperCase());
const meta = computed(() =>
    [props.attachment.pages ? `${props.attachment.pages} ${props.attachment.pages === 1 ? 'página' : 'páginas'}` : null, fileSize(props.attachment.size_bytes), ext.value]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <div
        class="box-border flex w-[290px] max-w-full cursor-pointer items-center gap-2.5 rounded-md bg-inset py-2.5 pr-2.5 pl-3"
        role="button"
        tabindex="0"
        title="Abrir pré-visualização"
        @click="$emit('open')"
        @keydown.enter="$emit('open')"
    >
        <div
            class="box-border flex h-10 w-8 flex-none items-end justify-center rounded pb-[5px] text-[9.5px] font-bold tracking-[.03em] text-white"
            :style="{ background: extColor(ext) }"
            aria-hidden="true"
        >
            {{ ext }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="truncate text-[14px] text-tx"><HighlightText :segments="nameSegments" /></div>
            <div class="mt-0.5 text-[12px]" :class="mine ? 'text-tx2-out' : 'text-tx2'">{{ meta }}</div>
        </div>
        <a
            :href="`${attachment.url}?download=1`"
            download
            title="Baixar"
            :aria-label="`Baixar ${attachment.name}`"
            class="flex h-8 w-8 flex-none items-center justify-center rounded-full border-[1.5px] border-div2 text-tx2 hover:text-tx2"
            @click.stop
        >
            <Icon name="download" :size="16" />
        </a>
    </div>
    <div class="h-4" />
</template>
