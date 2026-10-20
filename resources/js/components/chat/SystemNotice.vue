<script setup lang="ts">
import { timeOf } from '../../lib/format';
import type { Message } from '../../types';
import Icon from '../Icon.vue';

defineProps<{ message: Message }>();
</script>

<template>
    <div :data-mid="message.id" class="my-2 flex justify-center">
        <div class="flex max-w-[min(520px,88%)] items-center gap-[7px] rounded-lg bg-sys px-3 py-1.5 text-center text-[12.5px] leading-[17px] text-tx2 shadow-bubble">
            <Icon v-if="message.system_icon === 'lock'" name="lock" :size="13" />
            <Icon v-else-if="message.kind === 'call'" name="call" :size="15" />
            <span>{{ message.text }}</span>
            <span class="whitespace-nowrap">· {{ timeOf(message.sent_at) }}</span>
        </div>
    </div>
</template>
