<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { buildRows } from '../../lib/rows';
import type { Message } from '../../types';
import DaySeparator from './DaySeparator.vue';
import MessageBubble from './MessageBubble.vue';
import SystemNotice from './SystemNotice.vue';

const props = defineProps<{
    messages: Message[];
    isGroup: boolean;
    hasOlder: boolean;
    hasNewer: boolean;
    loadingOlder: boolean;
    loadingNewer: boolean;
    flashId: number | null;
    term: string;
    current: { messageId: number; nth: number } | null;
}>();

const emit = defineEmits<{
    older: [];
    newer: [];
    openMedia: [messageId: number];
    openDoc: [messageId: number];
    position: [state: { showJump: boolean; below: number }];
}>();

const el = ref<HTMLElement | null>(null);
const rows = computed(() => buildRows(props.messages));

/** Mantém a mesma mensagem no mesmo lugar da tela enquanto a lista muda acima ou abaixo dela. */
async function keepPosition(change: () => Promise<unknown>) {
    const box = el.value;
    if (!box) return change();

    const nodes = [...box.querySelectorAll<HTMLElement>('[data-mid]')];
    const anchor = nodes.find((n) => n.offsetTop + n.offsetHeight > box.scrollTop) ?? nodes[0];
    const id = anchor?.dataset.mid;
    const offset = anchor ? anchor.offsetTop - box.scrollTop : 0;

    await change();
    await nextTick();

    const again = id ? box.querySelector<HTMLElement>(`[data-mid="${id}"]`) : null;
    if (again) box.scrollTop = again.offsetTop - offset;
}

function scrollBottom(smooth = false) {
    el.value?.scrollTo({ top: el.value.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
}

function scrollToMessage(id: number, smooth = false): boolean {
    const box = el.value;
    const target = box?.querySelector<HTMLElement>(`[data-mid="${id}"]`);
    if (!box || !target) return false;
    box.scrollTo({ top: Math.max(0, target.offsetTop - box.clientHeight / 2 + target.offsetHeight / 2), behavior: smooth ? 'smooth' : 'auto' });
    return true;
}

function scrollToDay(day: string) {
    const box = el.value;
    const target = box?.querySelector<HTMLElement>(`[data-day="${day}"]`);
    if (box && target) box.scrollTo({ top: Math.max(0, target.offsetTop - 8) });
}

let frame = 0;
function report() {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
        const box = el.value;
        if (!box) return;
        const show = props.hasNewer || box.scrollHeight - box.scrollTop - box.clientHeight > 320;
        let below = 0;
        if (show) {
            const limit = box.scrollTop + box.clientHeight;
            box.querySelectorAll<HTMLElement>('[data-mid]').forEach((n) => {
                if (n.offsetTop > limit) below++;
            });
        }
        emit('position', { showJump: show, below });
    });
}

function onScroll() {
    const box = el.value;
    if (!box) return;
    if (box.scrollTop < 40 && props.hasOlder && !props.loadingOlder) emit('older');
    if (box.scrollHeight - box.scrollTop - box.clientHeight < 40 && props.hasNewer && !props.loadingNewer) emit('newer');
    report();
}

onMounted(report);
onBeforeUnmount(() => cancelAnimationFrame(frame));

defineExpose({ keepPosition, scrollBottom, scrollToMessage, scrollToDay, report });
</script>

<template>
    <div
        ref="el"
        class="relative min-h-0 flex-1 overflow-y-auto px-[clamp(12px,6%,72px)] pt-2 pb-[18px]"
        role="log"
        aria-label="Mensagens"
        tabindex="0"
        @scroll.passive="onScroll"
    >
        <div v-if="hasOlder" class="flex justify-center pt-2.5 pb-1">
            <div v-if="loadingOlder" class="flex items-center gap-2 py-0.5 text-[12.5px] text-tx2" role="status">
                <span class="box-border h-3.5 w-3.5 animate-[zvSpin_.8s_linear_infinite] rounded-full border-2 border-div2 border-t-accent" />
                <span>Carregando mensagens anteriores</span>
            </div>
            <button v-else type="button" class="rounded-[14px] bg-chip px-[14px] py-1.5 text-[12.5px] text-accent-tx shadow-bubble" @click="$emit('older')">
                Carregar mensagens anteriores
            </button>
        </div>
        <div v-else-if="messages.length" class="flex items-center justify-center gap-2.5 pt-[14px] pb-0.5 text-[12px] text-tx2">
            <span class="h-px w-7 bg-div2" /><span>Início do histórico</span><span class="h-px w-7 bg-div2" />
        </div>

        <template v-for="row in rows" :key="row.key">
            <DaySeparator v-if="row.type === 'day'" :day="row.day" />
            <SystemNotice v-else-if="row.type === 'notice'" :message="row.message" />
            <MessageBubble
                v-else
                :message="row.message"
                :first="row.first"
                :is-group="isGroup"
                :flash="flashId === row.message.id"
                :term="term"
                :current-nth="current && current.messageId === row.message.id ? current.nth : -1"
                @open-media="$emit('openMedia', $event)"
                @open-doc="$emit('openDoc', $event)"
            />
        </template>

        <div v-if="loadingNewer" class="flex items-center justify-center gap-2 py-3 text-[12.5px] text-tx2" role="status">
            <span class="box-border h-3.5 w-3.5 animate-[zvSpin_.8s_linear_infinite] rounded-full border-2 border-div2 border-t-accent" />
            <span>Carregando mensagens mais recentes</span>
        </div>
    </div>
</template>
