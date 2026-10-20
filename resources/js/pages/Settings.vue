<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '../components/PageHeader.vue';
import { usePageClose } from '../composables/usePageClose';
import AppShell from '../layouts/AppShell.vue';

defineOptions({ layout: AppShell });

const close = usePageClose();
</script>

<template>
    <Head title="Configurações" />
    <div class="flex min-h-0 flex-1 flex-col">
        <PageHeader title="Configurações" @close="close" />
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto max-w-[760px] px-6 pt-7 pb-8 text-[14px] leading-[21px] text-tx2">As configurações chegam em uma próxima etapa.</div>
        </div>
    </div>
</template>
