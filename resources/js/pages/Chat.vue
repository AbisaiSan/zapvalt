<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import ChatHeader from '../components/ChatHeader.vue';
import DateJumpPopover from '../components/chat/DateJumpPopover.vue';
import InChatSearch from '../components/chat/InChatSearch.vue';
import JumpToBottom from '../components/chat/JumpToBottom.vue';
import MessageList from '../components/chat/MessageList.vue';
import ReadOnlyFooter from '../components/ReadOnlyFooter.vue';
import { useAudioPlayer } from '../composables/useAudioPlayer';
import { useLastConversation } from '../composables/useLastConversation';
import { useMessageWindow } from '../composables/useMessageWindow';
import { useNarrow } from '../composables/useNarrow';
import AppShell from '../layouts/AppShell.vue';
import { ApiError, getJson, isAbort, query } from '../lib/api';
import { locateOccurrence } from '../lib/rows';
import type { ConversationHeader, Match } from '../types';

defineOptions({ layout: AppShell });

const props = defineProps<{ conversationId: number; focusMessageId: number | null }>();

const narrow = useNarrow();
const last = useLastConversation();
const audio = useAudioPlayer();
const { win, latest, around, day, older, newer, has } = useMessageWindow();

const conversation = ref<ConversationHeader | null>(null);
const notFound = ref(false);
const list = ref<InstanceType<typeof MessageList> | null>(null);
const flashId = ref<number | null>(null);
const dateOpen = ref(false);
const jump = ref({ showJump: false, below: 0 });

let flashTimer: ReturnType<typeof setTimeout> | undefined;

function flash(id: number) {
    clearTimeout(flashTimer);
    flashId.value = id;
    flashTimer = setTimeout(() => (flashId.value = null), 2200);
}

async function open(id: number, focus: number | null) {
    notFound.value = false;
    closeSearch();
    dateOpen.value = false;
    audio.stop();

    try {
        conversation.value = await getJson<ConversationHeader>(`/api/conversations/${id}`);
        last.value = id;
    } catch (error) {
        conversation.value = null;
        notFound.value = error instanceof ApiError && error.status === 404;
        return;
    }

    const focused = focus ? await around(id, focus) : await latest(id);
    await nextTick();
    if (focused) {
        list.value?.scrollToMessage(focused);
        flash(focused);
    } else {
        list.value?.scrollBottom();
    }
    list.value?.report();
}

const loadOlder = () => list.value?.keepPosition(older);
const loadNewer = () => list.value?.keepPosition(newer);

async function jumpToBottom() {
    if (win.hasNewer) {
        await latest(props.conversationId);
        await nextTick();
        list.value?.scrollBottom();
    } else {
        list.value?.scrollBottom(true);
    }
}

async function jumpToDay(d: string) {
    dateOpen.value = false;
    await day(props.conversationId, d);
    await nextTick();
    list.value?.scrollToDay(d);
    list.value?.report();
}

// Busca dentro da conversa: as ocorrências vêm do servidor; o destaque é feito aqui.
const searchOpen = ref(false);
const searchBox = ref<InstanceType<typeof InChatSearch> | null>(null);
const inQ = ref('');
const term = ref('');
const matches = ref<Match[]>([]);
const total = computed(() => matches.value.reduce((n, m) => n + m.count, 0));
const index = ref(0);
const ready = ref(false);
const current = computed(() => (term.value && total.value ? locateOccurrence(matches.value, index.value) : null));

let searchTimer: ReturnType<typeof setTimeout> | undefined;
let searchAbort: AbortController | null = null;

function openSearch() {
    dateOpen.value = false;
    if (searchOpen.value) searchBox.value?.focus();
    searchOpen.value = true;
}

function closeSearch() {
    searchOpen.value = false;
    inQ.value = '';
}

async function goTo(i: number, smooth: boolean) {
    index.value = i;
    const target = current.value;
    if (!target) return;
    if (!has(target.messageId)) {
        await around(props.conversationId, target.messageId);
        await nextTick();
        smooth = false;
    }
    await nextTick();
    list.value?.scrollToMessage(target.messageId, smooth);
    list.value?.report();
}

function step(delta: number) {
    if (!total.value) return;
    goTo((index.value + delta + total.value) % total.value, true);
}

watch(inQ, (q) => {
    clearTimeout(searchTimer);
    searchAbort?.abort();
    ready.value = false;
    if (q.trim().length < 2) {
        term.value = '';
        matches.value = [];
        return;
    }
    searchTimer = setTimeout(async () => {
        searchAbort = new AbortController();
        try {
            const r = await getJson<{ total: number; matches: Match[] }>(
                `/api/conversations/${props.conversationId}/matches` + query({ q: q.trim() }),
                searchAbort.signal,
            );
            term.value = q.trim();
            matches.value = r.matches;
            ready.value = true;
            if (r.matches.length) goTo(0, true);
        } catch (error) {
            if (!isAbort(error)) ready.value = true;
        }
    }, 250);
});

// Visualizador e pré-visualização chegam na tarefa 07; por enquanto o arquivo abre em outra aba.
function openAttachment(messageId: number) {
    const m = win.messages.find((x) => x.id === messageId);
    if (m?.attachment) window.open(m.attachment.url, '_blank', 'noopener,noreferrer');
}

// Por último: o watch imediato usa a busca declarada acima.
watch(() => [props.conversationId, props.focusMessageId] as const, ([id, focus]) => open(id, focus), { immediate: true });

onBeforeUnmount(() => {
    clearTimeout(flashTimer);
    clearTimeout(searchTimer);
    audio.stop();
});
</script>

<template>
    <Head :title="conversation?.name ?? 'Conversa'" />
    <div class="relative flex min-h-0 flex-1 flex-col bg-chat">
        <template v-if="conversation">
            <ChatHeader
                :conversation="conversation"
                :show-back="narrow"
                :date-open="dateOpen"
                :search-open="searchOpen"
                @back="router.visit('/')"
                @toggle-date="dateOpen = !dateOpen"
                @open-search="openSearch"
            >
                <DateJumpPopover v-if="dateOpen && conversation.message_count" :conversation-id="conversation.id" @pick="jumpToDay" @close="dateOpen = false" />
            </ChatHeader>

            <InChatSearch v-if="searchOpen" ref="searchBox" v-model="inQ" :index="index" :total="total" :ready="ready" @step="step" @close="closeSearch" />

            <div v-if="win.error" class="flex flex-1 items-center justify-center p-6 text-[13px] text-err-tx" role="alert">{{ win.error }}</div>
            <div v-else-if="!win.loading && !win.messages.length" class="flex flex-1 items-center justify-center p-6">
                <div class="max-w-[320px] rounded-[10px] bg-chip px-6 py-5 text-center shadow-bubble">
                    <div class="text-[15px] text-tx">Nenhuma mensagem nesta conversa</div>
                    <div class="mt-1.5 text-[13px] leading-[18px] text-tx2">O arquivo importado não tinha mensagens reconhecidas.</div>
                </div>
            </div>
            <MessageList
                v-else
                ref="list"
                :messages="win.messages"
                :is-group="conversation.is_group"
                :has-older="win.hasOlder"
                :has-newer="win.hasNewer"
                :loading-older="win.loadingOlder"
                :loading-newer="win.loadingNewer"
                :flash-id="flashId"
                :term="term"
                :current="current"
                @older="loadOlder"
                @newer="loadNewer"
                @open-media="openAttachment"
                @open-doc="openAttachment"
                @position="jump = $event"
            />

            <JumpToBottom v-if="jump.showJump && win.messages.length" :below="jump.below" :more="win.hasNewer" @jump="jumpToBottom" />
            <ReadOnlyFooter :file-name="conversation.source_file_name" />
        </template>
        <div v-else-if="notFound" class="flex flex-1 items-center justify-center p-6">
            <div class="max-w-[320px] rounded-[10px] bg-chip px-6 py-5 text-center shadow-bubble">
                <div class="text-[15px] text-tx">Conversa não encontrada</div>
                <div class="mt-1.5 text-[13px] leading-[18px] text-tx2">Ela pode ter sido excluída do ZapVault.</div>
            </div>
        </div>
    </div>
</template>
