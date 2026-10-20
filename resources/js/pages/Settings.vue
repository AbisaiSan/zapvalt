<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import PageHeader from '../components/PageHeader.vue';
import BackupSection from '../components/settings/BackupSection.vue';
import NamesSection from '../components/settings/NamesSection.vue';
import PasswordSection from '../components/settings/PasswordSection.vue';
import StorageSection from '../components/settings/StorageSection.vue';
import ThemeSection from '../components/settings/ThemeSection.vue';
import { usePageClose } from '../composables/usePageClose';
import AppShell from '../layouts/AppShell.vue';
import { getJson } from '../lib/api';
import type { Settings } from '../types';

defineOptions({ layout: AppShell });

const close = usePageClose();

const settings = ref<Settings | null>(null);
const failed = ref(false);
const saved = ref(false);
let savedTimer: ReturnType<typeof setTimeout> | undefined;

// "Salvo" no cabeçalho por alguns instantes, como no protótipo.
function showSaved() {
    clearTimeout(savedTimer);
    saved.value = true;
    savedTimer = setTimeout(() => (saved.value = false), 1800);
}

async function load() {
    failed.value = false;
    try {
        settings.value = await getJson<Settings>('/api/settings');
    } catch {
        failed.value = true;
    }
}

onMounted(load);
onBeforeUnmount(() => clearTimeout(savedTimer));
</script>

<template>
    <Head title="Configurações" />
    <div class="flex min-h-0 flex-1 flex-col">
        <PageHeader title="Configurações" @close="close">
            <span v-if="saved" class="flex items-center gap-[5px] text-[13px] text-accent-tx" role="status"><Icon name="check" :size="16" />Salvo</span>
        </PageHeader>
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto box-content flex max-w-[680px] flex-col gap-4 p-6 max-[520px]:px-4">
                <template v-if="settings">
                    <NamesSection v-model="settings.user_names" @saved="showSaved" />
                    <ThemeSection @saved="showSaved" />
                    <StorageSection :data-path="settings.data_path" />
                    <BackupSection :last-backup="settings.last_backup" :current="settings.current_backup" :data-path="settings.data_path" @saved="showSaved" />
                    <PasswordSection v-model="settings.has_password" @saved="showSaved" />
                </template>
                <div v-else-if="failed" class="flex flex-col items-center gap-3 py-12 text-center" role="alert">
                    <div class="text-[14px] text-err-tx">Não foi possível carregar as configurações.</div>
                    <button type="button" class="h-[38px] rounded-[19px] border border-div2 px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="load">Tentar de novo</button>
                </div>
                <template v-else>
                    <div v-for="n in 4" :key="n" class="h-[120px] animate-[zvPulse_1.4s_ease-in-out_infinite] rounded-xl bg-skel" aria-hidden="true" />
                </template>
            </div>
        </div>
    </div>
</template>
