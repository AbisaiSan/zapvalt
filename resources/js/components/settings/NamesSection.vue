<script setup lang="ts">
import { ref } from 'vue';
import { useArchive } from '../../composables/useArchive';
import { useUserNames } from '../../composables/useUserNames';
import { ApiError, sendJson } from '../../lib/api';
import { norm } from '../../lib/search';
import type { Settings } from '../../types';
import Icon from '../Icon.vue';
import SettingsCard from './SettingsCard.vue';

const names = defineModel<string[]>({ required: true });
const emit = defineEmits<{ saved: [] }>();

const { refresh } = useArchive();
const shared = useUserNames();
const newName = ref('');
const error = ref('');

async function save(next: string[]) {
    error.value = '';
    try {
        const s = await sendJson<Settings>('PUT', '/api/settings', { user_names: next });
        names.value = s.user_names;
        shared.value = s.user_names;
        emit('saved');
        // As prévias da lista ("Você: ...") dependem dos nomes.
        refresh();
    } catch (e) {
        error.value = e instanceof ApiError ? e.message : 'Não foi possível salvar. Tente de novo.';
    }
}

function add() {
    const name = newName.value.trim();
    newName.value = '';
    if (!name || names.value.some((n) => norm(n) === norm(name))) return;
    save([...names.value, name]);
}

function remove(name: string) {
    save(names.value.filter((n) => n !== name));
}
</script>

<template>
    <SettingsCard title="Seu nome nos arquivos" description="Mensagens enviadas com estes nomes aparecem à direita. Use mais de um se você mudou de nome ou de conta.">
        <ul class="m-0 flex list-none flex-wrap gap-2 p-0">
            <li v-for="n in names" :key="n" class="flex h-8 items-center gap-1 rounded-2xl bg-accent-soft pr-[5px] pl-3 text-[13px] font-medium text-accent-tx">
                {{ n }}
                <button type="button" title="Remover" :aria-label="`Remover ${n}`" class="flex h-[22px] w-[22px] items-center justify-center rounded-full text-accent-tx hover:bg-press" @click="remove(n)">
                    <Icon name="close" :size="13" />
                </button>
            </li>
            <li v-if="!names.length" class="py-1.5 text-[13px] text-tx2">Nenhum nome definido. Todas as mensagens vão aparecer à esquerda.</li>
        </ul>
        <form class="flex gap-2" @submit.prevent="add">
            <input
                v-model="newName"
                aria-label="Adicionar outro nome"
                placeholder="Adicionar outro nome"
                maxlength="120"
                autocomplete="off"
                class="box-border h-[38px] min-w-0 flex-1 rounded-lg border border-div2 bg-panel px-3 text-[14px] text-tx"
            />
            <button type="submit" class="h-[38px] flex-none rounded-[19px] border border-div2 px-4 text-[14px] font-medium text-accent-tx hover:bg-press">Adicionar</button>
        </form>
        <div v-if="error" class="text-[13px] text-err-tx" role="alert">{{ error }}</div>
    </SettingsCard>
</template>
