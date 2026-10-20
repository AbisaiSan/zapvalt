<script setup lang="ts">
import { computed, ref } from 'vue';
import { dur } from '../../lib/format';
import { mediaBox } from '../../lib/rows';
import type { Attachment } from '../../types';
import Icon from '../Icon.vue';

const props = defineProps<{ kind: 'image' | 'video'; attachment: Attachment }>();
defineEmits<{ open: [] }>();

const box = computed(() => mediaBox(props.attachment.width, props.attachment.height, props.kind));
const state = ref<'loading' | 'ok' | 'error'>(props.attachment.thumb_url ? 'loading' : props.kind === 'video' ? 'ok' : 'error');
const attempt = ref(0);
const src = computed(() => {
    const url = props.attachment.thumb_url;
    return url ? (attempt.value ? `${url}?tentativa=${attempt.value}` : url) : null;
});

function retry() {
    attempt.value++;
    state.value = 'loading';
}
</script>

<template>
    <div
        class="relative max-w-full overflow-hidden rounded-md bg-skel"
        :class="state === 'ok' ? 'cursor-pointer' : ''"
        :style="{ width: `${box.width}px`, aspectRatio: box.ratio }"
        @click="state === 'ok' && $emit('open')"
    >
        <img
            v-if="src"
            :key="src"
            :src="src"
            :alt="kind === 'video' ? `Vídeo ${attachment.name}` : `Foto ${attachment.name}`"
            loading="lazy"
            class="absolute inset-0 h-full w-full object-cover"
            @load="state = 'ok'"
            @error="kind === 'video' ? (state = 'ok') : (state = 'error')"
        />
        <div v-if="kind === 'video' && !attachment.thumb_url" class="absolute inset-0 bg-[#2A3942]">
            <span class="absolute top-2 left-2 rounded bg-white/80 px-1.5 py-0.5 font-mono text-[10.5px] text-[#26343C]">{{ attachment.name }}</span>
        </div>
        <template v-if="kind === 'video' && state === 'ok'">
            <button type="button" :aria-label="`Abrir vídeo ${attachment.name}`" class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-[52px] w-[52px] items-center justify-center rounded-full bg-[rgba(11,20,26,.55)] text-white"><Icon name="play" :size="22" /></span>
            </button>
            <span v-if="attachment.duration" class="absolute bottom-[7px] left-2 flex items-center gap-1 text-[11px] text-white [text-shadow:0_1px_2px_rgba(0,0,0,.6)]">
                <Icon name="videocam" :size="14" />{{ dur(attachment.duration) }}
            </span>
        </template>
        <div v-if="state === 'loading'" class="absolute inset-0 flex animate-[zvPulse_1.2s_ease-in-out_infinite] items-center justify-center bg-skel" aria-hidden="true">
            <span class="box-border h-7 w-7 animate-[zvSpin_.8s_linear_infinite] rounded-full border-[3px] border-div2 border-t-accent" />
        </div>
        <div v-if="state === 'error'" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-head p-3 text-center text-[13px] leading-[17px] text-tx2" role="alert">
            <Icon name="alert" :size="26" />
            <span>{{ kind === 'video' ? 'Não foi possível abrir o vídeo' : 'Não foi possível abrir a imagem' }}</span>
            <button type="button" class="rounded-[14px] border border-div2 px-3 py-1 text-[13px] font-medium text-accent-tx" @click.stop="retry">Tentar de novo</button>
        </div>
    </div>
</template>
