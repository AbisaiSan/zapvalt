<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ChatHeader from '../components/ChatHeader.vue';
import ReadOnlyFooter from '../components/ReadOnlyFooter.vue';
import { useLastConversation } from '../composables/useLastConversation';
import { useNarrow } from '../composables/useNarrow';
import AppShell from '../layouts/AppShell.vue';
import { ApiError, getJson } from '../lib/api';
import type { ConversationHeader } from '../types';

defineOptions({ layout: AppShell });

const props = defineProps<{ conversationId: number; focusMessageId: number | null }>();

const narrow = useNarrow();
const last = useLastConversation();
const conversation = ref<ConversationHeader | null>(null);
const notFound = ref(false);

async function load(id: number) {
    notFound.value = false;
    try {
        conversation.value = await getJson<ConversationHeader>(`/api/conversations/${id}`);
        last.value = id;
    } catch (error) {
        conversation.value = null;
        notFound.value = error instanceof ApiError && error.status === 404;
    }
}

watch(() => props.conversationId, load, { immediate: true });
</script>

<template>
    <Head :title="conversation?.name ?? 'Conversa'" />
    <div class="relative flex min-h-0 flex-1 flex-col bg-chat">
        <template v-if="conversation">
            <ChatHeader :conversation="conversation" :show-back="narrow" @back="router.visit('/')" />
            <div class="min-h-0 flex-1" />
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
