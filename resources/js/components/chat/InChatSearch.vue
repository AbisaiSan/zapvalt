<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useEscape } from '../../composables/useEscape';
import { ESC } from '../../lib/escape';
import Icon from '../Icon.vue';

const props = defineProps<{ index: number; total: number; ready: boolean }>();
const emit = defineEmits<{ step: [delta: number]; close: [] }>();
const model = defineModel<string>({ required: true });
const input = ref<HTMLInputElement | null>(null);

const searching = computed(() => model.value.trim().length >= 2);
const counter = computed(() => {
    if (!searching.value || !props.ready) return '';
    return props.total ? `${props.index + 1} de ${props.total.toLocaleString('pt-BR')}` : 'Sem ocorrências';
});

function onKey(event: KeyboardEvent) {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    emit('step', event.shiftKey ? -1 : 1);
}

useEscape(ESC.inChatSearch, () => {
    emit('close');
    return true;
});

onMounted(() => input.value?.focus());
defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="relative z-[2] flex flex-none items-center gap-1 border-b border-div bg-panel py-2 pr-2.5 pl-[14px]">
        <div class="mr-1 flex h-9 min-w-0 flex-1 items-center gap-2 rounded-lg bg-head px-3 text-tx2 focus-within:outline-2 focus-within:outline-accent">
            <Icon name="search" :size="16" />
            <input
                ref="input"
                v-model="model"
                type="search"
                placeholder="Buscar nesta conversa"
                aria-label="Buscar nesta conversa"
                class="h-full min-w-0 flex-1 border-0 bg-transparent text-[14px] text-tx outline-none [&::-webkit-search-cancel-button]:hidden"
                autocomplete="off"
                spellcheck="false"
                @keydown="onKey"
            />
            <span class="flex-none text-[12px] whitespace-nowrap" :class="searching && ready && !total ? 'text-err-tx' : 'text-tx2'" role="status">{{ counter }}</span>
        </div>
        <button
            type="button"
            title="Anterior (Shift+Enter)"
            aria-label="Ocorrência anterior"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            :class="total ? '' : 'opacity-35'"
            :disabled="!total"
            @click="$emit('step', -1)"
        >
            <Icon name="up" />
        </button>
        <button
            type="button"
            title="Próxima (Enter)"
            aria-label="Próxima ocorrência"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            :class="total ? '' : 'opacity-35'"
            :disabled="!total"
            @click="$emit('step', 1)"
        >
            <Icon name="down" />
        </button>
        <button
            type="button"
            title="Fechar busca (Esc)"
            aria-label="Fechar busca na conversa"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            @click="$emit('close')"
        >
            <Icon name="close" :size="18" />
        </button>
    </div>
</template>
