<script setup lang="ts">
import { ref } from 'vue';
import { ApiError, sendJson } from '../../lib/api';
import Dialog from '../Dialog.vue';

const props = defineProps<{ conversationId: number; name: string }>();
const emit = defineEmits<{ close: []; deleted: [] }>();

const error = ref('');
const deleting = ref(false);

async function remove() {
    deleting.value = true;
    try {
        await sendJson('DELETE', `/api/conversations/${props.conversationId}`);
        emit('deleted');
    } catch (e) {
        error.value = e instanceof ApiError ? e.message : 'Não foi possível excluir. Tente de novo.';
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <Dialog :title="`Excluir “${name}”?`" @close="$emit('close')">
        <p class="m-0">A conversa, as mensagens e os anexos dela serão apagados do ZapVault. Os arquivos originais exportados do WhatsApp, guardados no seu computador ou no celular, não são afetados.</p>
        <p class="mt-2 mb-0">Para ter a conversa de volta, importe o arquivo de novo.</p>
        <div v-if="error" class="mt-2 text-[13px] text-err-tx" role="alert">{{ error }}</div>
        <template #actions>
            <button type="button" data-autofocus class="h-[38px] rounded-[19px] px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="$emit('close')">Cancelar</button>
            <button type="button" :disabled="deleting" class="h-[38px] rounded-[19px] bg-danger px-[18px] text-[14px] font-medium text-btn-tx disabled:opacity-60" @click="remove">
                {{ deleting ? 'Excluindo…' : 'Excluir conversa' }}
            </button>
        </template>
    </Dialog>
</template>
