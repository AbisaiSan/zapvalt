<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { useEscape } from '../composables/useEscape';
import { ESC } from '../lib/escape';

// Janela de confirmação: prende o foco do teclado enquanto aberta e devolve ao fechar.
defineProps<{ title: string; wide?: boolean }>();
const emit = defineEmits<{ close: [] }>();

const box = ref<HTMLElement | null>(null);
const titleId = useId();
const previous = typeof document === 'undefined' ? null : (document.activeElement as HTMLElement | null);

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

function focusables(): HTMLElement[] {
    return box.value ? [...box.value.querySelectorAll<HTMLElement>(FOCUSABLE)] : [];
}

function onKey(event: KeyboardEvent) {
    if (event.key !== 'Tab') return;
    const items = focusables();
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

useEscape(ESC.dialog, () => {
    emit('close');
    return true;
});

onMounted(async () => {
    await nextTick();
    const auto = box.value?.querySelector<HTMLElement>('[data-autofocus]');
    (auto ?? focusables()[0])?.focus();
});
onBeforeUnmount(() => previous?.focus?.());
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-4" @mousedown.self="$emit('close')">
            <div
                ref="box"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="flex max-h-[calc(100vh-32px)] w-full flex-col gap-3 overflow-y-auto rounded-xl border border-div bg-panel px-6 pt-5 pb-4 text-tx shadow-pop"
                :class="wide ? 'max-w-[520px]' : 'max-w-[420px]'"
                @keydown="onKey"
            >
                <h2 :id="titleId" class="m-0 text-[17px] font-medium text-tx">{{ title }}</h2>
                <div class="text-[14px] leading-[20px] text-tx2">
                    <slot />
                </div>
                <div class="mt-2 flex flex-wrap justify-end gap-2">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
