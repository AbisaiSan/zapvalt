<script setup lang="ts">
import { computed, ref } from 'vue';
import { useToast } from '../../composables/useToast';
import { ApiError, sendJson } from '../../lib/api';
import Icon from '../Icon.vue';
import SettingsCard from './SettingsCard.vue';

const hasPassword = defineModel<boolean>({ required: true });
const emit = defineEmits<{ saved: [] }>();

const toast = useToast();

// closed: sem senha e sem formulário; create: nova senha; change: trocar; remove: confirmar a senha atual.
const mode = ref<'closed' | 'create' | 'change' | 'remove' | 'active'>(hasPassword.value ? 'active' : 'closed');
const pwd1 = ref('');
const pwd2 = ref('');
const current = ref('');
const error = ref('');
const busy = ref(false);

const on = computed(() => hasPassword.value || mode.value === 'create');

function reset(next: typeof mode.value) {
    mode.value = next;
    pwd1.value = '';
    pwd2.value = '';
    current.value = '';
    error.value = '';
}

function toggle() {
    if (mode.value === 'closed') reset('create');
    else if (mode.value === 'create') reset('closed');
    else if (mode.value === 'remove') reset('active');
    else reset('remove');
}

async function save() {
    if (pwd1.value.length < 6) return (error.value = 'Use pelo menos 6 caracteres.');
    if (pwd1.value !== pwd2.value) return (error.value = 'As senhas não coincidem.');
    busy.value = true;
    try {
        await sendJson('PUT', '/api/settings/password', { password: pwd1.value, password_confirmation: pwd2.value });
        const changed = hasPassword.value;
        hasPassword.value = true;
        reset('active');
        toast.show(changed ? 'Senha alterada' : 'Senha ativada');
        emit('saved');
    } catch (e) {
        error.value = e instanceof ApiError ? e.message : 'Não foi possível salvar a senha. Tente de novo.';
    } finally {
        busy.value = false;
    }
}

async function remove() {
    if (!current.value) return (error.value = 'Digite a senha atual.');
    busy.value = true;
    try {
        await sendJson('DELETE', '/api/settings/password', { password: current.value });
        hasPassword.value = false;
        reset('closed');
        toast.show('Senha desativada');
        emit('saved');
    } catch (e) {
        current.value = '';
        error.value = e instanceof ApiError ? e.message : 'Não foi possível desativar a senha. Tente de novo.';
    } finally {
        busy.value = false;
    }
}

async function lockNow() {
    try {
        await sendJson('POST', '/api/lock');
    } finally {
        // Recarrega a página: nada do acervo fica na memória do navegador.
        window.location.assign('/desbloquear');
    }
}

const inputClass = 'box-border h-[38px] min-w-0 rounded-lg border border-div2 bg-panel px-3 text-[14px] text-tx';
</script>

<template>
    <SettingsCard title="Senha (opcional)">
        <template #description>
            Pede uma senha sempre que o ZapVault for aberto. Ela protege a abertura do aplicativo, mas não criptografa as conversas nem os anexos; para isso, ative a criptografia do disco
            (FileVault, no Mac).
        </template>
        <template #aside>
            <button
                type="button"
                role="switch"
                :aria-checked="on ? 'true' : 'false'"
                aria-label="Pedir senha ao abrir"
                title="Ativar ou desativar senha"
                class="relative h-6 w-[42px] flex-none rounded-xl transition-[background] duration-150"
                :class="on ? 'bg-btn' : 'bg-div2'"
                @click="toggle"
            >
                <span class="absolute top-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,.3)] transition-[left] duration-150" :class="on ? 'left-[21px]' : 'left-[3px]'" />
            </button>
        </template>

        <form v-if="mode === 'create' || mode === 'change'" class="flex flex-col gap-3" @submit.prevent="save">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-2">
                <input v-model="pwd1" type="password" autocomplete="new-password" placeholder="Nova senha" aria-label="Nova senha" :class="inputClass" @input="error = ''" />
                <input v-model="pwd2" type="password" autocomplete="new-password" placeholder="Repita a senha" aria-label="Repita a senha" :class="inputClass" @input="error = ''" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="min-w-[160px] flex-1 text-[13px] text-err-tx" role="alert">{{ error }}</span>
                <button v-if="mode === 'change'" type="button" class="h-[38px] rounded-[19px] px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="reset('active')">Cancelar</button>
                <button type="submit" :disabled="busy" class="h-[38px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx disabled:opacity-60">
                    {{ mode === 'change' ? 'Salvar senha' : 'Ativar senha' }}
                </button>
            </div>
        </form>

        <form v-else-if="mode === 'remove'" class="flex flex-col gap-3" @submit.prevent="remove">
            <label for="current-password" class="text-[13px] text-tx2">Para desativar a senha, digite a senha atual.</label>
            <input id="current-password" v-model="current" type="password" autocomplete="current-password" placeholder="Senha atual" :class="inputClass" @input="error = ''" />
            <div class="flex flex-wrap items-center gap-3">
                <span class="min-w-[160px] flex-1 text-[13px] text-err-tx" role="alert">{{ error }}</span>
                <button type="button" class="h-[38px] rounded-[19px] px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="reset('active')">Cancelar</button>
                <button type="submit" :disabled="busy" class="h-[38px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx disabled:opacity-60">Desativar senha</button>
            </div>
        </form>

        <div v-else-if="mode === 'active'" class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg bg-ok-bg px-3 py-2.5 text-[13px] text-ok-tx">
            <Icon name="lock" :size="16" />
            <span class="flex-1">Senha ativada</span>
            <button type="button" class="text-[13px] font-semibold text-ok-tx underline" @click="lockNow">Bloquear agora</button>
            <button type="button" class="text-[13px] font-semibold text-ok-tx underline" @click="reset('change')">Alterar senha</button>
        </div>
    </SettingsCard>
</template>
