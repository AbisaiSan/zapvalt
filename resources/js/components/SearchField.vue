<script setup lang="ts">
import { ref } from 'vue';
import { searchShortcut } from '../lib/format';
import Icon from './Icon.vue';

const model = defineModel<string>({ required: true });
const input = ref<HTMLInputElement | null>(null);
const shortcut = searchShortcut();

function clear() {
    model.value = '';
    input.value?.focus();
}

function focus() {
    input.value?.focus();
    input.value?.select();
}

defineExpose({ focus });
</script>

<template>
    <div class="flex-none border-b border-div px-3 py-2">
        <div class="flex h-9 items-center gap-2.5 rounded-lg bg-head pr-2 pl-3 text-tx2 focus-within:outline-2 focus-within:outline-accent">
            <Icon name="search" :size="17" />
            <input
                ref="input"
                v-model="model"
                type="search"
                placeholder="Buscar conversas e mensagens"
                aria-label="Buscar conversas e mensagens"
                class="h-full min-w-0 flex-1 border-0 bg-transparent text-[14px] text-tx outline-none [&::-webkit-search-cancel-button]:hidden"
                autocomplete="off"
                spellcheck="false"
            />
            <span v-if="!model" class="flex-none rounded border border-div2 px-[5px] py-px text-[11px] whitespace-nowrap text-tx2">{{ shortcut }}</span>
            <button
                v-else
                type="button"
                title="Limpar busca (Esc)"
                aria-label="Limpar busca"
                class="flex h-[26px] w-[26px] flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
                @click="clear"
            >
                <Icon name="close" :size="16" />
            </button>
        </div>
        <div v-if="model.trim().length === 1" class="px-1 pt-1.5 text-[12px] text-tx2" role="status">Digite pelo menos 2 letras para buscar</div>
    </div>
</template>
