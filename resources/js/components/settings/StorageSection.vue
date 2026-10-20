<script setup lang="ts">
import { computed, ref } from 'vue';
import { useArchive } from '../../composables/useArchive';
import { fileSize } from '../../lib/format';
import Dialog from '../Dialog.vue';
import Icon from '../Icon.vue';
import SettingsCard from './SettingsCard.vue';

defineProps<{ dataPath: string }>();

const { archive } = useArchive();
const help = ref(false);

const info = computed(() => {
    const s = archive.summary;
    if (!s) return '';
    return `${s.messages.toLocaleString('pt-BR')} mensagens e ${s.attachments.toLocaleString('pt-BR')} anexos · ${fileSize(s.bytes_used)} usados`;
});
</script>

<template>
    <SettingsCard title="Local de armazenamento" description="Conversas e anexos ficam nesta pasta. Nada é enviado para a internet.">
        <div class="flex items-center gap-2">
            <div class="box-border flex h-[38px] min-w-0 flex-1 items-center gap-2 rounded-lg bg-head px-3 text-tx2">
                <Icon name="folder" :size="16" />
                <span class="truncate font-mono text-[12.5px] text-tx" :title="dataPath">{{ dataPath }}</span>
            </div>
            <button type="button" class="h-[38px] flex-none rounded-[19px] border border-div2 px-4 text-[14px] font-medium text-accent-tx hover:bg-press" @click="help = true">Alterar…</button>
        </div>
        <div class="text-[12px] text-tx2">{{ info }}</div>

        <Dialog v-if="help" title="Como mudar a pasta de dados" wide @close="help = false">
            <p class="m-0">O ZapVault não move os dados sozinho, para não correr o risco de perder nada no caminho. Para usar outra pasta:</p>
            <ol class="mt-2 mb-0 flex list-decimal flex-col gap-1.5 pl-5">
                <li>Pare o ZapVault: no Terminal em que ele está rodando, aperte Ctrl+C.</li>
                <li>Mova a pasta <code class="font-mono text-[12.5px] break-all text-tx">{{ dataPath }}</code> inteira para o novo lugar.</li>
                <li>No arquivo <code class="font-mono text-[12.5px] text-tx">.env</code>, na pasta do projeto, troque o valor de <code class="font-mono text-[12.5px] text-tx">ZAPVAULT_DATA_PATH</code> pelo novo caminho.</li>
                <li>Abra o ZapVault de novo com <code class="font-mono text-[12.5px] text-tx">composer run zapvault</code>.</li>
            </ol>
            <template #actions>
                <button type="button" data-autofocus class="h-[38px] rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx" @click="help = false">Entendi</button>
            </template>
        </Dialog>
    </SettingsCard>
</template>
