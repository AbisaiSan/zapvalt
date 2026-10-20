<script setup lang="ts">
import { ref } from 'vue';
import { ApiError, sendJson } from '../../lib/api';
import type { ConversationItem } from '../../types';
import Dialog from '../Dialog.vue';

const props = defineProps<{ conversationId: number; name: string }>();
const emit = defineEmits<{ close: []; renamed: [item: ConversationItem] }>();

const value = ref(props.name);
const error = ref('');
const saving = ref(false);

async function save() {
    const name = value.value.trim();
    if (!name) {
        error.value = 'Informe um nome para a conversa.';
        return;
    }
    if (name === props.name) {
        emit('close');
        return;
    }
    saving.value = true;
    try {
        emit('renamed', await sendJson<ConversationItem>('PATCH', `/api/conversations/${props.conversationId}`, { name }));
    } catch (e) {
        error.value = e instanceof ApiError ? e.message : 'Não foi possível renomear. Tente de novo.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog title="Renomear conversa" @close="$emit('close')">
        <form id="rename-form" class="flex flex-col gap-2" @submit.prevent="save">
            <label for="rename-input" class="text-[13px]">O novo nome vale só no ZapVault; o arquivo exportado não muda.</label>
            <input
                id="rename-input"
                v-model="value"
                data-autofocus
                maxlength="120"
                autocomplete="off"
                class="box-border h-[38px] w-full min-w-0 rounded-lg border border-div2 bg-panel px-3 text-[14px] text-tx"
                :aria-invalid="error ? 'true' : 'false'"
                aria-describedby="rename-error"
                @input="error = ''"
            />
            <div id="rename-error" class="min-h-[18px] text-[13px] text-err-tx" role="alert">{{ error }}</div>
        </form>
        <template #actions>
            <button type="button" class="h-[38px] rounded-[19px] px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="$emit('close')">Cancelar</button>
            <button type="submit" form="rename-form" :disabled="saving" class="h-[38px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx disabled:opacity-60">Salvar</button>
        </template>
    </Dialog>
</template>
