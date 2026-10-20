<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { useEscape } from '../../composables/useEscape';
import { ESC } from '../../lib/escape';
import Icon from '../Icon.vue';

const emit = defineEmits<{ rename: []; remove: [] }>();

const open = ref(false);
const button = ref<HTMLButtonElement | null>(null);
const menu = ref<HTMLElement | null>(null);

function items(): HTMLElement[] {
    return menu.value ? [...menu.value.querySelectorAll<HTMLElement>('[role="menuitem"]')] : [];
}

async function toggle() {
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        items()[0]?.focus();
    }
}

function close(refocus = true) {
    open.value = false;
    if (refocus) button.value?.focus();
}

function move(delta: number) {
    const list = items();
    const i = list.indexOf(document.activeElement as HTMLElement);
    list[(i + delta + list.length) % list.length]?.focus();
}

function choose(action: 'rename' | 'remove') {
    close(false);
    if (action === 'rename') emit('rename');
    else emit('remove');
}

useEscape(ESC.menu, () => {
    if (!open.value) return false;
    close();
    return true;
});
</script>

<template>
    <div class="relative flex-none">
        <button
            ref="button"
            type="button"
            title="Mais opções"
            aria-label="Mais opções da conversa"
            aria-haspopup="menu"
            :aria-expanded="open ? 'true' : 'false'"
            class="flex h-10 w-10 items-center justify-center rounded-full text-tx2 hover:bg-press"
            :class="open ? 'bg-press' : ''"
            @click="toggle"
        >
            <Icon name="more" :size="21" />
        </button>
        <template v-if="open">
            <div class="fixed inset-0 z-[4]" aria-hidden="true" @click="close(false)" />
            <div
                ref="menu"
                role="menu"
                aria-label="Opções da conversa"
                class="absolute top-[46px] right-0 z-[5] w-[220px] rounded-[10px] bg-panel py-1.5 shadow-pop"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.tab="close(false)"
            >
                <button type="button" role="menuitem" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-[14px] text-tx hover:bg-hover focus:bg-hover" @click="choose('rename')">
                    <Icon name="edit" :size="18" /><span>Renomear conversa</span>
                </button>
                <button type="button" role="menuitem" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-[14px] text-danger hover:bg-hover focus:bg-hover" @click="choose('remove')">
                    <Icon name="trash" :size="18" /><span>Excluir conversa</span>
                </button>
            </div>
        </template>
    </div>
</template>
