<script setup lang="ts">
withDefaults(defineProps<{ rows?: number }>(), { rows: 9 });
</script>

<template>
    <div aria-hidden="true">
        <div v-for="k in rows" :key="k" class="flex h-[72px] items-center gap-[13px] pr-4 pl-[13px]">
            <div class="h-12 w-12 flex-none animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-full bg-skel" />
            <div class="flex flex-1 flex-col gap-[9px]">
                <div class="h-3 w-[52%] animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-md bg-skel" />
                <div class="h-2.5 w-[82%] animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-[5px] bg-skel" />
            </div>
        </div>
    </div>
</template>
