<script setup lang="ts">
import Icon from '../Icon.vue';

defineProps<{ below: number; more: boolean }>();
defineEmits<{ jump: [] }>();
</script>

<template>
    <button
        type="button"
        title="Ir para o fim"
        aria-label="Ir para a mensagem mais recente"
        class="absolute right-[22px] bottom-[58px] z-[2] flex h-[42px] w-[42px] items-center justify-center rounded-full bg-panel text-tx2 shadow-pop"
        @click="$emit('jump')"
    >
        <Icon name="down" :size="22" />
        <span
            v-if="below > 0 || more"
            class="absolute -top-1.5 -right-1 box-border flex h-5 min-w-5 items-center justify-center rounded-[10px] bg-btn px-[5px] text-[11px] font-semibold text-btn-tx"
        >
            {{ below > 99 ? '99+' : more ? `${below}+` : below }}
        </span>
    </button>
</template>
