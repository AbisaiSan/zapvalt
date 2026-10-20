<script setup lang="ts">
import { computed } from 'vue';
import { AVATAR_COLORS, initials } from '../lib/format';
import Icon from './Icon.vue';

const props = withDefaults(defineProps<{ name: string; avatarIndex: number; isPhone?: boolean; size?: number }>(), { isPhone: false, size: 48 });

const color = computed(() => AVATAR_COLORS[((props.avatarIndex % 8) + 8) % 8]);
</script>

<template>
    <div
        class="flex flex-none items-center justify-center rounded-full font-medium text-white"
        :style="{ width: `${size}px`, height: `${size}px`, background: color, fontSize: size >= 48 ? '17px' : '15px' }"
        aria-hidden="true"
    >
        <Icon v-if="isPhone" name="person" :size="size >= 48 ? 26 : 22" />
        <template v-else>{{ initials(name) }}</template>
    </div>
</template>
