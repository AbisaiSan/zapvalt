<script setup lang="ts">
import { computed } from 'vue';
import { dayOf, fmt, plural } from '../lib/format';
import type { ConversationHeader } from '../types';
import Avatar from './Avatar.vue';
import Icon from './Icon.vue';

const props = defineProps<{ conversation: ConversationHeader; showBack: boolean; dateOpen?: boolean; searchOpen?: boolean }>();
defineEmits<{ back: []; toggleDate: []; openSearch: [] }>();

const subtitle = computed(() => {
    const c = props.conversation;
    if (!c.first_message_at || !c.last_message_at) return 'Sem mensagens';
    const first = dayOf(c.first_message_at);
    const last = dayOf(c.last_message_at);
    const period = first === last ? fmt(first) : `${fmt(first)} – ${fmt(last)}`;
    const people = c.is_group ? ` · ${c.participant_count} participantes` : '';
    return `${period} · ${plural(c.message_count, 'mensagem', 'mensagens')}${people}`;
});
</script>

<template>
    <header class="relative z-[3] flex h-[60px] flex-none items-center gap-2.5 bg-head pr-2.5 pl-[14px]">
        <button
            v-if="showBack"
            type="button"
            title="Voltar"
            aria-label="Voltar para a lista"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            @click="$emit('back')"
        >
            <Icon name="back" :size="22" />
        </button>
        <Avatar :name="conversation.name" :avatar-index="conversation.avatar_index" :is-phone="conversation.is_phone" :size="40" />
        <div class="ml-0.5 min-w-0 flex-1">
            <h1 class="m-0 truncate text-[16px] font-normal text-tx">{{ conversation.name }}</h1>
            <div class="truncate text-[13px] text-tx2">{{ subtitle }}</div>
        </div>
        <button
            type="button"
            title="Pular para data"
            aria-label="Pular para data"
            :aria-expanded="dateOpen ? 'true' : 'false'"
            class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            :class="dateOpen ? 'bg-press' : ''"
            @click="$emit('toggleDate')"
        >
            <Icon name="calendar" :size="21" />
        </button>
        <button
            type="button"
            title="Buscar na conversa"
            aria-label="Buscar na conversa"
            class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            :class="searchOpen ? 'bg-press' : ''"
            @click="$emit('openSearch')"
        >
            <Icon name="search" :size="21" />
        </button>
        <slot />
    </header>
</template>
