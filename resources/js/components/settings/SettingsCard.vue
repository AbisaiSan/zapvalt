<script setup lang="ts">
// Cartão de uma seção de Configurações, como no protótipo. Com "row", o conteúdo fica ao lado do título.
defineProps<{ title: string; description?: string; row?: boolean }>();
</script>

<template>
    <section class="rounded-xl border border-div bg-panel px-5 py-[18px]" :class="row ? 'flex flex-wrap items-center gap-4' : 'flex flex-col gap-3'" :aria-label="title">
        <div class="flex items-center gap-4" :class="row ? 'min-w-[200px] flex-1' : ''">
            <div class="min-w-0 flex-1">
                <h2 class="m-0 text-[15px] font-medium text-tx">{{ title }}</h2>
                <div v-if="description || $slots.description" class="mt-1 text-[13px] leading-[18px] text-pretty text-tx2">
                    <slot name="description">{{ description }}</slot>
                </div>
            </div>
            <slot name="aside" />
        </div>
        <slot />
    </section>
</template>
