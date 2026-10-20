<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import ConversationItem from '../components/ConversationItem.vue';
import EmptyArchive from '../components/EmptyArchive.vue';
import ListSkeleton from '../components/ListSkeleton.vue';
import SearchField from '../components/SearchField.vue';
import SearchResults from '../components/SearchResults.vue';
import SidebarHeader from '../components/SidebarHeader.vue';
import ToastHost from '../components/ToastHost.vue';
import { useArchive } from '../composables/useArchive';
import { useEscape } from '../composables/useEscape';
import { useNarrow } from '../composables/useNarrow';
import { useSearch } from '../composables/useSearch';
import { useToday } from '../composables/useToday';
import { ESC, handleEscape } from '../lib/escape';

// Layout persistente: a lista da esquerda continua montada enquanto o painel direito troca de página.
const page = usePage();
const narrow = useNarrow();
const today = useToday();
const { archive, refresh, loadMore } = useArchive();
const { search, active: searching, clear, loadMoreMessages } = useSearch();
const searchField = ref<InstanceType<typeof SearchField> | null>(null);

provide('today', today);

const showRight = computed(() => page.component !== 'Home');
const activeConversationId = computed(() => (page.component === 'Chat' ? Number((page.props as { conversationId?: number }).conversationId) : null));

function focusSearch() {
    if (narrow.value && showRight.value) {
        router.visit('/', { onFinish: () => nextTick(() => searchField.value?.focus()) });
        return;
    }
    searchField.value?.focus();
}

function onKey(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        focusSearch();
        return;
    }
    if (event.key === 'Escape' && handleEscape()) event.preventDefault();
}

function onListScroll(event: Event) {
    const el = event.target as HTMLElement;
    if (el.scrollHeight - el.scrollTop - el.clientHeight > 400) return;
    if (searching.value) {
        if (search.tab === 'msgs') loadMoreMessages();
    } else {
        loadMore();
    }
}

useEscape(ESC.listSearch, () => {
    if (!search.q) return false;
    clear();
    return true;
});

onMounted(() => {
    window.addEventListener('keydown', onKey);
    if (archive.booting) refresh();
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="fixed inset-0 flex overflow-hidden bg-panel text-[14px] text-tx">
        <aside
            v-show="!(narrow && showRight)"
            class="relative z-[1] flex h-full flex-col border-r border-div bg-panel"
            :class="narrow ? 'min-w-0 flex-[1_1_100%]' : 'max-w-[420px] min-w-[320px] flex-[0_0_30%]'"
            aria-label="Conversas"
        >
            <SidebarHeader :settings-active="page.component === 'Settings'" />
            <SearchField ref="searchField" v-model="search.q" />
            <div class="min-h-0 flex-1 overflow-y-auto" @scroll.passive="onListScroll">
                <ListSkeleton v-if="archive.booting" />
                <div v-else-if="archive.error" class="px-7 py-12 text-center text-[13px] leading-[19px] text-err-tx" role="alert">{{ archive.error }}</div>
                <EmptyArchive v-else-if="!archive.items.length" />
                <SearchResults v-else-if="searching" :active-conversation-id="activeConversationId" :today="today" />
                <nav v-else aria-label="Lista de conversas">
                    <ConversationItem v-for="c in archive.items" :key="c.id" :item="c" :today="today" :active="activeConversationId === c.id" />
                    <ListSkeleton v-if="archive.loadingMore" :rows="2" />
                </nav>
            </div>
        </aside>
        <main v-show="!(narrow && !showRight)" class="relative flex h-full min-w-0 flex-[1_1_auto] flex-col bg-page">
            <slot />
        </main>
        <ToastHost />
    </div>
</template>
