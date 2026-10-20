<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTheme } from '../composables/useTheme';
import Icon from './Icon.vue';
import Logo from './Logo.vue';

defineProps<{ settingsActive: boolean }>();

const { theme, toggle } = useTheme();
</script>

<template>
    <header class="flex h-[60px] flex-none items-center gap-1 bg-head pr-2 pl-[14px]">
        <Logo />
        <div class="ml-1.5 min-w-0 flex-1 truncate text-[17px] font-semibold tracking-[-.01em]">ZapVault</div>
        <Link
            href="/importar"
            title="Importar conversas"
            class="mr-0.5 flex h-[34px] flex-none items-center gap-[5px] rounded-[17px] bg-btn pr-[11px] pl-2 text-[14px] font-medium text-btn-tx no-underline hover:text-btn-tx"
        >
            <Icon name="upload" :size="18" />
            <span>Importar</span>
        </Link>
        <button
            type="button"
            :title="theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'"
            :aria-label="theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press"
            @click="toggle"
        >
            <Icon :name="theme === 'dark' ? 'sun' : 'moon'" :size="21" />
        </button>
        <Link
            href="/configuracoes"
            title="Configurações"
            aria-label="Configurações"
            class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-tx2 hover:bg-press hover:text-tx2"
            :class="settingsActive ? 'bg-press' : ''"
        >
            <Icon name="sliders" :size="21" />
        </Link>
    </header>
</template>
