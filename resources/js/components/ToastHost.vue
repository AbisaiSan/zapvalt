<script setup lang="ts">
import { useToast } from '../composables/useToast';

const { toast } = useToast();
</script>

<template>
    <div aria-live="polite" class="pointer-events-none fixed inset-x-0 bottom-7 z-[60] flex justify-center px-4">
        <div v-if="toast" :key="toast.id" class="max-w-[calc(100vw-32px)] animate-[zvToast_.2s_ease-out] rounded-lg bg-tx px-[18px] py-2.5 text-center text-[14px] text-panel shadow-pop">
            {{ toast.text }}
        </div>
    </div>
</template>
