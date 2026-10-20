<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSearch } from '../composables/useSearch';
import { fmt, dayOf, plural } from '../lib/format';
import { ranges, segs, snippet } from '../lib/search';
import type { MessageResult } from '../types';
import ConversationItem from './ConversationItem.vue';
import HighlightText from './HighlightText.vue';
import Icon from './Icon.vue';

const props = defineProps<{ activeConversationId: number | null; today: string }>();

const { search, pick } = useSearch();

const q = computed(() => search.term);
const none = computed(() => !search.conversationTotal && !search.messageTotal);
const msgCount = computed(() => (search.messageCapped ? '10.000+' : search.messageTotal.toLocaleString('pt-BR')));

const summary = computed(() => {
    const msgs = search.messageCapped ? '10.000+ mensagens' : plural(search.messageTotal, 'mensagem', 'mensagens');
    return `${msgs} em ${plural(search.messageConversations, 'conversa', 'conversas')}`;
});

function prefix(r: MessageResult): string {
    const kind = r.field === 'file' ? 'Documento: ' : r.field === 'caption' ? (r.kind === 'video' ? 'Vídeo: ' : 'Foto: ') : '';
    return (r.who ? `${r.who}: ` : '') + kind;
}

function selected(r: MessageResult): boolean {
    return search.lastResultId === r.message_id && props.activeConversationId === r.conversation_id;
}

const tabClass = (tab: 'chats' | 'msgs') =>
    search.tab === tab ? 'text-accent-tx border-accent' : 'text-tx2 border-transparent';
</script>

<template>
    <div>
        <div class="sticky top-0 z-[1] flex border-b border-div bg-panel" role="tablist" aria-label="Resultados da busca">
            <button
                type="button"
                role="tab"
                :aria-selected="search.tab === 'chats'"
                class="flex h-[46px] flex-1 items-center justify-center gap-2 border-b-2 text-[14px] font-medium"
                :class="tabClass('chats')"
                @click="pick('chats')"
            >
                Conversas<span class="rounded-[10px] bg-head px-2 py-px text-[12px] text-tx2">{{ search.loading ? '…' : search.conversationTotal }}</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="search.tab === 'msgs'"
                class="flex h-[46px] flex-1 items-center justify-center gap-2 border-b-2 text-[14px] font-medium"
                :class="tabClass('msgs')"
                @click="pick('msgs')"
            >
                Mensagens<span class="rounded-[10px] bg-head px-2 py-px text-[12px] text-tx2">{{ search.loading ? '…' : msgCount }}</span>
            </button>
        </div>

        <div v-if="search.loading" aria-hidden="true">
            <div v-for="k in 4" :key="k" class="flex flex-col gap-[9px] border-b border-div px-4 py-[14px]">
                <div class="h-3 w-[45%] animate-[zvPulse_1.2s_ease-in-out_infinite] rounded-md bg-skel" />
                <div class="h-2.5 w-[88%] animate-[zvPulse_1.2s_ease-in-out_infinite] rounded-[5px] bg-skel" />
            </div>
        </div>

        <div v-else-if="search.error" class="px-7 py-12 text-center text-[13px] text-err-tx" role="alert">{{ search.error }}</div>

        <div v-else-if="search.tab === 'chats'" role="tabpanel">
            <ConversationItem
                v-for="c in search.conversations"
                :key="c.id"
                :item="c"
                :today="today"
                :active="activeConversationId === c.id"
                :name-segments="segs(c.name, ranges(c.name, q))"
            />
            <div v-if="!search.conversationTotal" class="flex flex-col items-center gap-2 px-7 py-12 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-head text-tx2"><Icon name="search" :size="24" /></div>
                <div class="mt-1.5 text-[15px] text-pretty text-tx">{{ none ? `Nenhum resultado para “${q}”` : `Nenhuma conversa com “${q}” no nome` }}</div>
                <div class="max-w-[260px] text-[13px] leading-[18px] text-pretty text-tx2">
                    {{
                        none
                            ? 'Confira a grafia ou tente outra palavra. A busca ignora maiúsculas e acentos.'
                            : `Veja a aba Mensagens: ${search.messageCapped ? '10.000+ mensagens contêm' : plural(search.messageTotal, 'mensagem contém', 'mensagens contêm')} esse termo.`
                    }}
                </div>
            </div>
        </div>

        <div v-else role="tabpanel">
            <div v-if="search.messageTotal" class="px-4 pt-3 pb-1.5 text-[12px] tracking-[.02em] text-tx2" role="status">{{ summary }}</div>
            <Link
                v-for="r in search.messages"
                :key="r.message_id"
                :href="`/c/${r.conversation_id}?m=${r.message_id}`"
                class="block cursor-pointer border-b border-div px-4 pt-[11px] pb-3 text-inherit no-underline outline-offset-[-2px] hover:bg-hover"
                :class="selected(r) ? 'bg-sel' : ''"
                @click="search.lastResultId = r.message_id"
            >
                <div class="mb-[3px] flex items-baseline gap-2">
                    <span class="min-w-0 flex-1 truncate text-[15px] text-tx">{{ r.conversation_name }}</span>
                    <span class="flex-none text-[12px] text-tx2">{{ fmt(dayOf(r.sent_at)) }}</span>
                </div>
                <div class="max-h-[38px] overflow-hidden text-[14px] leading-[19px] [overflow-wrap:anywhere] text-tx2">
                    {{ prefix(r) }}<HighlightText :segments="snippet(r.text, q)" />
                </div>
            </Link>
            <div v-if="search.loadingMore" class="py-3 text-center text-[12.5px] text-tx2">Carregando mais resultados…</div>
            <div v-if="!search.messageTotal" class="flex flex-col items-center gap-2 px-7 py-12 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-head text-tx2"><Icon name="search" :size="24" /></div>
                <div class="mt-1.5 text-[15px] text-pretty text-tx">{{ none ? `Nenhum resultado para “${q}”` : `Nenhuma mensagem com “${q}”` }}</div>
                <div class="max-w-[260px] text-[13px] leading-[18px] text-pretty text-tx2">
                    {{
                        none
                            ? 'Confira a grafia ou tente outra palavra. A busca ignora maiúsculas e acentos.'
                            : `O termo aparece no nome de ${plural(search.conversationTotal, 'conversa', 'conversas')}. Veja a aba Conversas.`
                    }}
                </div>
            </div>
        </div>
    </div>
</template>
