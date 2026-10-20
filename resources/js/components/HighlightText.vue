<script setup lang="ts">
import type { Segment } from '../lib/search';

// Texto sempre como texto (nunca v-html), com os trechos destacados.
defineProps<{ segments: Segment[] }>();
</script>

<template>
    <template v-for="(s, i) in segments" :key="i"
        ><span v-if="s.kind === 'plain'">{{ s.t }}</span
        ><mark v-else class="rounded-[3px]" :class="s.kind === 'cur' ? 'bg-hl-cur text-hl-cur-tx' : 'bg-hl text-hl-tx'">{{ s.t }}</mark></template
    >
</template>
