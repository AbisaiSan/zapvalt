<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '../components/PageHeader.vue';
import { usePageClose } from '../composables/usePageClose';
import AppShell from '../layouts/AppShell.vue';

defineOptions({ layout: AppShell });

const close = usePageClose();
</script>

<template>
    <Head title="Importar conversas" />
    <div class="flex min-h-0 flex-1 flex-col">
        <PageHeader title="Importar conversas" @close="close" />
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto flex max-w-[760px] flex-col gap-5 px-6 pt-7 pb-8">
                <div>
                    <h2 class="m-0 mb-1.5 text-[20px] font-medium text-tx">Traga conversas exportadas do celular</h2>
                    <p class="m-0 text-[14px] leading-[21px] text-pretty text-tx2">
                        A importação pela tela chega na próxima etapa. Por enquanto, use o terminal:
                        <code class="rounded bg-head px-1.5 py-0.5 font-mono text-[12.5px]">php artisan zapvault:import arquivo.zip</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
