<script setup lang="ts">
import Icon from './Icon.vue';

defineProps<{ title: string }>();
defineEmits<{ close: [] }>();
</script>

<template>
    <header class="flex h-[60px] flex-none items-center gap-2.5 bg-head pr-4 pl-2.5">
        <button
            type="button"
            title="Fechar (Esc)"
            aria-label="Fechar"
            class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            @click="$emit('close')"
        >
            <Icon name="back" :size="22" />
        </button>
        <h1 class="m-0 min-w-0 flex-1 text-[16px] font-normal text-tx">{{ title }}</h1>
        <slot />
    </header>
</template>
