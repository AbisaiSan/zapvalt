<script setup lang="ts">
import { inject, ref, type Ref } from 'vue';
import { sepLabel, todayKey } from '../../lib/format';

defineProps<{ day: string }>();
const today = inject<Ref<string>>('today', ref(todayKey()));
</script>

<template>
    <div :data-day="day" class="mt-3 mb-1.5 flex justify-center">
        <span class="rounded-lg bg-chip px-3 py-[5px] text-[12.5px] text-tx2 shadow-bubble">{{ sepLabel(day, today) }}</span>
    </div>
</template>
