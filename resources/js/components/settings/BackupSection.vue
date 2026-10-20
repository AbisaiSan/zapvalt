<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { ApiError, getJson, sendJson } from '../../lib/api';
import { dayOf, fileSize, fmt, timeOf } from '../../lib/format';
import type { BackupState, Settings } from '../../types';
import Icon from '../Icon.vue';
import SettingsCard from './SettingsCard.vue';

const props = defineProps<{ lastBackup: Settings['last_backup']; current: BackupState | null; dataPath: string }>();
const emit = defineEmits<{ saved: [] }>();

const backup = ref<BackupState | null>(props.current);
const error = ref('');
let timer: ReturnType<typeof setTimeout> | undefined;

const running = computed(() => backup.value?.status === 'waiting' || backup.value?.status === 'running');
const done = computed(() => backup.value?.status === 'done');
const failed = computed(() => backup.value?.status === 'failed' || !!error.value);

const last = computed(() => (props.lastBackup ? `${fmt(dayOf(props.lastBackup.at))} às ${timeOf(props.lastBackup.at)}` : 'nenhum ainda'));
const doneText = computed(() => {
    const b = backup.value;
    return b?.file_name ? `${b.file_name} (${fileSize(b.size_bytes ?? 0)})` : '';
});

async function poll() {
    if (!backup.value) return;
    try {
        backup.value = await getJson<BackupState>(`/api/backups/${backup.value.id}`);
    } catch {
        // Uma falha de rede pontual não interrompe o acompanhamento.
    }
    if (running.value) {
        timer = setTimeout(poll, 700);
    } else if (backup.value.status === 'done') {
        emit('saved');
    }
}

async function start() {
    clearTimeout(timer);
    error.value = '';
    try {
        backup.value = await sendJson<BackupState>('POST', '/api/backups');
        timer = setTimeout(poll, 300);
    } catch (e) {
        backup.value = null;
        error.value = e instanceof ApiError ? e.message : 'Não foi possível começar o backup. Tente de novo.';
    }
}

if (running.value) timer = setTimeout(poll, 300);
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <SettingsCard title="Backup" description="Gera um arquivo .zip com todo o acervo para guardar em outro lugar.">
        <div v-if="running" class="flex flex-col gap-2" role="status">
            <div class="flex justify-between text-[13px] text-tx2">
                <span>{{ backup?.status === 'waiting' ? 'Aguardando a vez na fila…' : 'Gerando backup…' }}</span><span>{{ backup?.percent ?? 0 }}%</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-[3px] bg-track" role="progressbar" aria-label="Progresso do backup" :aria-valuenow="backup?.percent ?? 0" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full bg-accent transition-[width] duration-100 ease-linear" :style="{ width: `${backup?.percent ?? 0}%` }" />
            </div>
        </div>

        <template v-else-if="done">
            <div class="flex flex-wrap items-center gap-2.5 rounded-lg bg-ok-bg px-3 py-2.5 text-[13px] leading-[18px] text-ok-tx" role="status">
                <Icon name="check" :size="18" />
                <span class="min-w-[180px] flex-1 break-all">Backup concluído: {{ doneText }}</span>
                <button type="button" class="text-[13px] font-semibold text-ok-tx underline" @click="start">Fazer outro</button>
            </div>
            <div class="text-[12px] leading-[17px] text-tx2">
                Ele fica em <span class="font-mono break-all">{{ dataPath }}/backups</span>. Copie para um disco externo, de preferência criptografado.
            </div>
        </template>

        <div v-else-if="failed" class="flex flex-wrap items-center gap-2.5 rounded-lg bg-err-bg px-3 py-2.5 text-[13px] leading-[18px] text-err-tx" role="alert">
            <Icon name="alert" :size="18" />
            <span class="min-w-[180px] flex-1">{{ error || backup?.error || 'Não foi possível gerar o backup.' }}</span>
            <button type="button" class="text-[13px] font-semibold text-err-tx underline" @click="start">Tentar de novo</button>
        </div>

        <div v-else class="flex flex-wrap items-center gap-3">
            <span class="min-w-[180px] flex-1 text-[13px] text-tx2">Último backup: {{ last }}</span>
            <button type="button" class="flex h-[38px] items-center gap-[7px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx" @click="start">
                <Icon name="download" :size="17" />Fazer backup agora
            </button>
        </div>
    </SettingsCard>
</template>
