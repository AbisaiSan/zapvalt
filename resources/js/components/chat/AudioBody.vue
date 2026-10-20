<script setup lang="ts">
import { computed } from 'vue';
import { useAudioPlayer } from '../../composables/useAudioPlayer';
import { dur } from '../../lib/format';
import type { Attachment } from '../../types';
import Icon from '../Icon.vue';

const props = defineProps<{ messageId: number; attachment: Attachment; mine: boolean }>();
const { player, toggle, seek, cycleSpeed } = useAudioPlayer();

const active = computed(() => player.id === props.messageId);
const playing = computed(() => active.value && player.playing);
const total = computed(() => (active.value && player.duration ? player.duration : props.attachment.duration ?? 0));
const position = computed(() => (active.value ? player.position : 0));
const pct = computed(() => (total.value ? Math.min(100, (position.value / total.value) * 100) : 0));
const speed = computed(() => player.speeds[props.messageId] ?? 1);
const time = computed(() => {
    if (!total.value) return position.value > 0 ? dur(position.value) : '';
    return position.value > 0 ? `${dur(position.value)} / ${dur(total.value)}` : dur(total.value);
});

function onSeek(event: MouseEvent) {
    const r = (event.currentTarget as HTMLElement).getBoundingClientRect();
    seek(props.messageId, props.attachment.url, props.attachment.duration, (event.clientX - r.left) / r.width);
}

function onKey(event: KeyboardEvent) {
    if (!total.value) return;
    const step = event.key === 'ArrowRight' ? 5 : event.key === 'ArrowLeft' ? -5 : 0;
    if (!step) return;
    event.preventDefault();
    seek(props.messageId, props.attachment.url, props.attachment.duration, (position.value + step) / total.value);
}
</script>

<template>
    <div class="flex w-[270px] max-w-full items-center gap-2.5 pb-3">
        <button
            type="button"
            :title="playing ? 'Pausar' : 'Tocar'"
            :aria-label="playing ? 'Pausar áudio' : 'Tocar áudio'"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-btn text-btn-tx"
            @click="toggle(messageId, attachment.url, attachment.duration)"
        >
            <Icon :name="playing ? 'pause' : 'play'" :size="16" :class="playing ? '' : 'ml-0.5'" />
        </button>
        <div class="min-w-0 flex-1">
            <div
                class="relative flex h-4 cursor-pointer items-center"
                role="slider"
                tabindex="0"
                aria-label="Posição do áudio"
                :aria-valuemin="0"
                :aria-valuemax="Math.round(total)"
                :aria-valuenow="Math.round(position)"
                @click="onSeek"
                @keydown="onKey"
            >
                <div class="h-1 w-full rounded-sm bg-track" />
                <div class="absolute top-1.5 left-0 h-1 rounded-sm bg-accent" :style="{ width: `${pct}%` }" />
                <div class="absolute top-0.5 -ml-1.5 h-3 w-3 rounded-full bg-accent" :style="{ left: `${pct}%` }" />
            </div>
            <div v-if="player.failed === messageId" class="mt-px text-[11px] text-err-tx" role="alert">Não foi possível tocar o áudio</div>
            <div v-else class="mt-px text-[11px]" :class="mine ? 'text-tx2-out' : 'text-tx2'">{{ time }}</div>
        </div>
        <button
            type="button"
            title="Velocidade de reprodução"
            :aria-label="`Velocidade ${speed === 1.5 ? '1,5' : speed} vezes`"
            class="h-6 min-w-10 flex-none rounded-xl bg-inset px-2 text-[12px] font-semibold text-tx"
            @click="cycleSpeed(messageId)"
        >
            {{ speed === 1.5 ? '1,5x' : `${speed}x` }}
        </button>
    </div>
</template>
