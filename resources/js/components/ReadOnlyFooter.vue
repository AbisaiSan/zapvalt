<script setup lang="ts">
import Icon from './Icon.vue';

defineProps<{ fileName: string | null }>();
</script>

<template>
    <footer class="flex h-10 min-w-0 flex-none items-center justify-center gap-2 bg-head px-4 text-[13px] text-tx2">
        <Icon name="lock" :size="14" />
        <span class="whitespace-nowrap">Arquivo somente leitura</span>
        <template v-if="fileName">
            <span class="opacity-60" aria-hidden="true">·</span>
            <span class="min-w-0 truncate text-[12px]">{{ fileName }}</span>
        </template>
    </footer>
</template>
