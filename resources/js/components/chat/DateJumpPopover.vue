<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { useEscape } from '../../composables/useEscape';
import { getJson } from '../../lib/api';
import { ESC } from '../../lib/escape';
import { fmt, monthName, plural, weekday } from '../../lib/format';
import type { DayCount } from '../../types';

const props = defineProps<{ conversationId: number }>();
const emit = defineEmits<{ pick: [day: string]; close: [] }>();

const days = ref<DayCount[] | null>(null);
const failed = ref(false);
const list = ref<HTMLElement | null>(null);

// Agrupado por ano e mês, para conversas longas.
const groups = computed(() => {
    const out: { key: string; label: string; days: DayCount[] }[] = [];
    for (const d of days.value ?? []) {
        const key = d.day.slice(0, 7);
        if (out[out.length - 1]?.key !== key) {
            const [y, m] = key.split('-').map(Number);
            out.push({ key, label: `${monthName(m)} de ${y}`, days: [] });
        }
        out[out.length - 1].days.push(d);
    }
    return out;
});

useEscape(ESC.datePopover, () => {
    emit('close');
    return true;
});

onMounted(async () => {
    try {
        days.value = (await getJson<{ days: DayCount[] }>(`/api/conversations/${props.conversationId}/days`)).days;
        await nextTick();
        // Começa nas datas mais recentes.
        if (list.value) list.value.scrollTop = list.value.scrollHeight;
    } catch {
        failed.value = true;
    }
});
</script>

<template>
    <div class="fixed inset-0 z-[4]" aria-hidden="true" @click="$emit('close')" />
    <div ref="list" class="absolute top-[54px] right-[54px] z-[5] max-h-[380px] w-[250px] overflow-y-auto rounded-[10px] bg-panel py-1.5 shadow-pop" role="dialog" aria-label="Pular para data">
        <div class="px-4 pt-2 pb-1.5 text-[12px] text-tx2">Pular para data</div>
        <div v-if="failed" class="px-4 py-2 text-[13px] text-err-tx">Não foi possível carregar as datas.</div>
        <div v-else-if="!days" class="px-4 py-2 text-[13px] text-tx2">Carregando…</div>
        <template v-for="g in groups" :key="g.key">
            <div v-if="groups.length > 1" class="sticky top-0 bg-panel px-4 pt-2 pb-1 text-[11.5px] font-semibold tracking-[.02em] text-tx2 uppercase">{{ g.label }}</div>
            <button
                v-for="d in g.days"
                :key="d.day"
                type="button"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left hover:bg-hover"
                @click="$emit('pick', d.day)"
            >
                <span class="min-w-0 flex-1">
                    <span class="block text-[14px] text-tx">{{ fmt(d.day) }}</span>
                    <span class="block text-[12px] text-tx2">{{ weekday(d.day) }}</span>
                </span>
                <span class="text-[12px] text-tx2">{{ plural(d.count, 'mensagem', 'mensagens') }}</span>
            </button>
        </template>
    </div>
</template>
