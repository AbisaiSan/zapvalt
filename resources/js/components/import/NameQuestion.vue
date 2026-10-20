<script setup lang="ts">
defineProps<{ candidates: { name: string; hint: string; suggested: boolean }[] }>();
const model = defineModel<string | null>({ required: true });
</script>

<template>
    <fieldset class="m-0 flex flex-col gap-2.5 rounded-xl border border-div bg-panel px-5 py-[18px]">
        <legend class="sr-only">Este é o seu nome nas mensagens?</legend>
        <div>
            <div class="text-[16px] font-medium text-tx" aria-hidden="true">Este é o seu nome nas mensagens?</div>
            <div class="mt-1 text-[13px] leading-[18px] text-tx2">As mensagens com esse nome aparecem à direita, como enviadas por você.</div>
        </div>
        <label
            v-for="o in [...candidates.map((c) => ({ value: c.name, label: c.name, hint: c.hint, tag: c.suggested ? 'Sugerido' : '' })), { value: null, label: 'Nenhum destes', hint: 'Defino depois em Configurações', tag: '' }]"
            :key="o.label"
            class="flex w-full cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-left"
            :class="model === o.value ? 'border-accent bg-accent-soft' : 'border-div'"
        >
            <input v-model="model" type="radio" name="import-name" :value="o.value" class="sr-only" />
            <span class="box-border flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full border-2" :class="model === o.value ? 'border-accent' : 'border-tx2'">
                <span class="h-2 w-2 rounded-full" :class="model === o.value ? 'bg-accent' : ''" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[14px] text-tx">{{ o.label }}</span>
                <span class="block text-[12px] text-tx2">{{ o.hint }}</span>
            </span>
            <span v-if="o.tag" class="flex-none rounded-[10px] bg-accent-soft px-2 py-0.5 text-[11px] font-semibold text-accent-tx">{{ o.tag }}</span>
        </label>
    </fieldset>
</template>
