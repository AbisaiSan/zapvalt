<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import ChatHeader from '../components/ChatHeader.vue';
import DocPreview from '../components/DocPreview.vue';
import MediaViewer from '../components/MediaViewer.vue';
import DateJumpPopover from '../components/chat/DateJumpPopover.vue';
import DeleteDialog from '../components/chat/DeleteDialog.vue';
import InChatSearch from '../components/chat/InChatSearch.vue';
import JumpToBottom from '../components/chat/JumpToBottom.vue';
import MessageList from '../components/chat/MessageList.vue';
import RenameDialog from '../components/chat/RenameDialog.vue';
import ReadOnlyFooter from '../components/ReadOnlyFooter.vue';
import { useArchive } from '../composables/useArchive';
import { useAudioPlayer } from '../composables/useAudioPlayer';
import { useLastConversation } from '../composables/useLastConversation';
import { useMessageWindow } from '../composables/useMessageWindow';
import { useNarrow } from '../composables/useNarrow';
import { useToast } from '../composables/useToast';
import AppShell from '../layouts/AppShell.vue';
import { ApiError, getJson, isAbort, query } from '../lib/api';
import { locateOccurrence } from '../lib/rows';
import type { ConversationHeader, ConversationItem, Match, Message } from '../types';

defineOptions({ layout: AppShell });

const props = defineProps<{ conversationId: number; focusMessageId: number | null }>();

const narrow = useNarrow();
const last = useLastConversation();
const audio = useAudioPlayer();
const { win, latest, around, day, older, newer, has } = useMessageWindow();

const conversation = ref<ConversationHeader | null>(null);
const notFound = ref(false);
const loadFailed = ref(false);
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
    loadFailed.value = false;
    closeSearch();
    dateOpen.value = false;
    dialog.value = null;
    viewerMessageId.value = null;
    docMessage.value = null;
    audio.stop();

    try {
        conversation.value = await getJson<ConversationHeader>(`/api/conversations/${id}`);
        last.value = id;
    } catch (error) {
        conversation.value = null;
        notFound.value = error instanceof ApiError && error.status === 404;
        loadFailed.value = !notFound.value;
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

// Visualizador de imagem e vídeo, e pré-visualização de documento.
const viewerMessageId = ref<number | null>(null);
const docMessage = ref<Message | null>(null);

function openMedia(messageId: number) {
    audio.stop();
    viewerMessageId.value = messageId;
}

function openDoc(messageId: number) {
    docMessage.value = win.messages.find((m) => m.id === messageId && m.attachment) ?? null;
}

// Menu ⋯: renomear e excluir (só do ZapVault).
const { refresh: refreshArchive, replaceItem } = useArchive();
const toast = useToast();
const dialog = ref<'rename' | 'delete' | null>(null);

function onRenamed(item: ConversationItem) {
    dialog.value = null;
    if (conversation.value) conversation.value.name = item.name;
    replaceItem(item);
    toast.show('Conversa renomeada');
}

function onDeleted() {
    dialog.value = null;
    last.value = null;
    refreshArchive();
    router.visit('/');
    toast.show('Conversa excluída do ZapVault');
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
                @rename="dialog = 'rename'"
                @remove="dialog = 'delete'"
            >
                <DateJumpPopover v-if="dateOpen && conversation.message_count" :conversation-id="conversation.id" @pick="jumpToDay" @close="dateOpen = false" />
            </ChatHeader>

            <InChatSearch v-if="searchOpen" ref="searchBox" v-model="inQ" :index="index" :total="total" :ready="ready" @step="step" @close="closeSearch" />

            <div v-if="win.error" class="flex flex-1 flex-col items-center justify-center gap-3 p-6" role="alert">
                <div class="text-[13px] text-err-tx">{{ win.error }}</div>
                <button type="button" class="rounded-[14px] bg-chip px-[14px] py-1.5 text-[12.5px] text-accent-tx shadow-bubble" @click="open(conversationId, focusMessageId)">Tentar de novo</button>
            </div>
            <div v-else-if="win.loading && !win.messages.length" class="flex flex-1 items-center justify-center gap-2 text-[12.5px] text-tx2" role="status">
                <span class="box-border h-3.5 w-3.5 animate-[zvSpin_.8s_linear_infinite] rounded-full border-2 border-div2 border-t-accent" />
                <span>Carregando mensagens</span>
            </div>
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
                @open-media="openMedia"
                @open-doc="openDoc"
                @position="jump = $event"
            />

            <JumpToBottom v-if="jump.showJump && win.messages.length" :below="jump.below" :more="win.hasNewer" @jump="jumpToBottom" />
            <ReadOnlyFooter :file-name="conversation.source_file_name" />

            <MediaViewer
                v-if="viewerMessageId"
                :conversation-id="conversation.id"
                :message-id="viewerMessageId"
                :avatar-index="conversation.avatar_index"
                :narrow="narrow"
                @close="viewerMessageId = null"
            />
            <DocPreview v-if="docMessage" :message="docMessage" @close="docMessage = null" />
            <RenameDialog v-if="dialog === 'rename'" :conversation-id="conversation.id" :name="conversation.name" @close="dialog = null" @renamed="onRenamed" />
            <DeleteDialog v-if="dialog === 'delete'" :conversation-id="conversation.id" :name="conversation.name" @close="dialog = null" @deleted="onDeleted" />
        </template>
        <div v-else-if="loadFailed" class="flex flex-1 items-center justify-center p-6" role="alert">
            <div class="max-w-[320px] rounded-[10px] bg-chip px-6 py-5 text-center shadow-bubble">
                <div class="text-[15px] text-tx">Não foi possível abrir a conversa</div>
                <div class="mt-1.5 text-[13px] leading-[18px] text-tx2">Confira se o ZapVault está rodando no Terminal.</div>
                <button type="button" class="mt-3 h-[34px] rounded-[17px] bg-btn px-4 text-[13px] font-medium text-btn-tx" @click="open(conversationId, focusMessageId)">Tentar de novo</button>
            </div>
        </div>
        <template v-else-if="!notFound">
            <div class="flex h-[60px] flex-none items-center gap-2.5 bg-head pl-[14px]" aria-hidden="true">
                <div class="h-10 w-10 animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-full bg-skel" />
                <div class="flex flex-col gap-1.5">
                    <div class="h-3 w-40 animate-[zvPulse_1.4s_ease-in-out_infinite] rounded bg-skel" />
                    <div class="h-2.5 w-56 animate-[zvPulse_1.4s_ease-in-out_infinite] rounded bg-skel" />
                </div>
            </div>
            <div class="flex flex-1 items-center justify-center gap-2 text-[12.5px] text-tx2" role="status">
                <span class="box-border h-3.5 w-3.5 animate-[zvSpin_.8s_linear_infinite] rounded-full border-2 border-div2 border-t-accent" />
                <span>Abrindo conversa</span>
            </div>
        </template>
        <div v-else class="flex flex-1 items-center justify-center p-6">
            <div class="max-w-[320px] rounded-[10px] bg-chip px-6 py-5 text-center shadow-bubble">
                <div class="text-[15px] text-tx">Conversa não encontrada</div>
                <div class="mt-1.5 text-[13px] leading-[18px] text-tx2">Ela pode ter sido excluída do ZapVault.</div>
            </div>
        </div>
    </div>
</template>
