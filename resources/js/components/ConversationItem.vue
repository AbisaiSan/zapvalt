<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { listDate } from '../lib/format';
import type { Segment } from '../lib/search';
import type { ConversationItem } from '../types';
import Avatar from './Avatar.vue';
import HighlightText from './HighlightText.vue';

defineProps<{ item: ConversationItem; active?: boolean; nameSegments?: Segment[]; today: string }>();
</script>

<template>
    <Link
        :href="`/c/${item.id}`"
        class="flex h-[72px] cursor-pointer items-center gap-[13px] pl-[13px] text-inherit no-underline outline-offset-[-2px] hover:bg-hover"
        :class="active ? 'bg-sel' : ''"
        :aria-current="active ? 'page' : undefined"
    >
        <Avatar :name="item.name" :avatar-index="item.avatar_index" :is-phone="item.is_phone" />
        <div class="flex h-full min-w-0 flex-1 flex-col justify-center gap-[3px] border-b border-div pr-[15px]">
            <div class="flex items-baseline gap-2">
                <span class="min-w-0 flex-1 truncate text-[16px] text-tx">
                    <HighlightText v-if="nameSegments" :segments="nameSegments" />
                    <template v-else>{{ item.name }}</template>
                </span>
                <span class="flex-none text-[12px] text-tx2">{{ listDate(item.last_message_at, today) }}</span>
            </div>
            <div class="truncate text-[14px] text-tx2">{{ item.preview }}</div>
        </div>
    </Link>
</template>
