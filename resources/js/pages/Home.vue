<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '../components/Icon.vue';
import Logo from '../components/Logo.vue';
import { useArchive } from '../composables/useArchive';
import AppShell from '../layouts/AppShell.vue';
import { dayOf, fmt, searchShortcut } from '../lib/format';

defineOptions({ layout: AppShell });

const { archive } = useArchive();
const shortcut = searchShortcut();

const summary = computed(() => archive.summary);
const period = computed(() => {
    const s = summary.value;
    if (!s?.first_message_at || !s.last_message_at) return '';
    return `Mensagens de ${fmt(dayOf(s.first_message_at))} a ${fmt(dayOf(s.last_message_at))}`;
});
</script>

<template>
    <Head title="Conversas" />
    <div class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 py-10 text-center">
        <Logo :size="72" :radius="20" :icon="44" />

        <template v-if="archive.booting">
            <h1 class="mt-6 mb-2 text-[26px] font-normal text-tx">Abrindo seu acervo…</h1>
            <div class="mt-6 flex gap-3" aria-hidden="true">
                <div class="h-[84px] w-[140px] animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-xl bg-skel" />
                <div class="h-[84px] w-[140px] animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-xl bg-skel" />
            </div>
        </template>

        <template v-else-if="!summary?.conversations">
            <h1 class="mt-6 mb-2 text-[26px] font-normal text-tx">Seu acervo está vazio</h1>
            <p class="m-0 max-w-[400px] text-[14px] leading-[21px] text-pretty text-tx2">Importe conversas exportadas do celular, em .txt ou .zip, para consultar quando quiser.</p>
            <Link href="/importar" class="mt-6 inline-flex h-10 items-center rounded-[20px] bg-btn px-5 text-[14px] font-medium text-btn-tx no-underline hover:text-btn-tx">Importar conversas</Link>
        </template>

        <template v-else>
            <h1 class="mt-6 mb-2 text-[26px] font-normal text-tx">Selecione uma conversa</h1>
            <p class="m-0 max-w-[400px] text-[14px] leading-[21px] text-pretty text-tx2">
                Escolha na lista ao lado ou busque por nome, palavra ou frase com
                <kbd class="rounded border border-div2 px-[5px] py-px font-sans text-[12px] whitespace-nowrap">{{ shortcut }}</kbd>
            </p>
            <div class="mt-8 flex overflow-hidden rounded-xl border border-div bg-panel">
                <div class="min-w-[110px] px-8 py-4">
                    <div class="text-[30px] font-light text-tx">{{ summary.conversations.toLocaleString('pt-BR') }}</div>
                    <div class="mt-0.5 text-[13px] text-tx2">conversas</div>
                </div>
                <div class="w-px bg-div" />
                <div class="min-w-[110px] px-8 py-4">
                    <div class="text-[30px] font-light text-tx">{{ summary.messages.toLocaleString('pt-BR') }}</div>
                    <div class="mt-0.5 text-[13px] text-tx2">mensagens</div>
                </div>
            </div>
            <div class="mt-3 text-[12px] text-tx2">{{ period }}</div>
        </template>
    </div>
    <div class="flex flex-none items-center justify-center gap-1.5 p-[14px] text-[12px] text-tx2">
        <Icon name="lock" :size="14" />
        <span>Acervo local e somente leitura. Nada é enviado.</span>
    </div>
</template>
