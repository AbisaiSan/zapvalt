<script setup lang="ts">
import { useTheme } from '../../composables/useTheme';
import type { Theme } from '../../lib/theme';
import Icon from '../Icon.vue';
import SettingsCard from './SettingsCard.vue';

const emit = defineEmits<{ saved: [] }>();
const { theme, apply } = useTheme();

function choose(next: Theme) {
    if (next !== theme.value) apply(next);
    emit('saved');
}
</script>

<template>
    <SettingsCard title="Tema" description="A troca é imediata e fica lembrada neste computador." row>
        <div class="flex gap-[3px] rounded-[10px] bg-head p-[3px]" role="radiogroup" aria-label="Tema">
            <button
                v-for="o in [{ value: 'light', label: 'Claro', icon: 'sun' }, { value: 'dark', label: 'Escuro', icon: 'moon' }] as const"
                :key="o.value"
                type="button"
                role="radio"
                :aria-checked="theme === o.value ? 'true' : 'false'"
                class="flex h-[34px] items-center gap-[7px] rounded-lg px-[14px] text-[14px]"
                :class="theme === o.value ? ['text-tx shadow-bubble', theme === 'dark' ? 'bg-sel' : 'bg-panel'] : 'text-tx2'"
                @click="choose(o.value)"
            >
                <Icon :name="o.icon" :size="16" />{{ o.label }}
            </button>
        </div>
    </SettingsCard>
</template>
