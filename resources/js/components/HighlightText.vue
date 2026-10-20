<script setup lang="ts">
import type { LinkedSegment } from '../lib/links';

// Texto sempre como texto (nunca v-html), com os trechos destacados e, quando houver, links.
defineProps<{ segments: LinkedSegment[] }>();
</script>

<template>
    <template v-for="(s, i) in segments" :key="i"
        ><a v-if="s.href" :href="s.href" target="_blank" rel="noopener noreferrer" class="underline [overflow-wrap:anywhere]" @click.stop
            ><span v-if="s.kind === 'plain'">{{ s.t }}</span
            ><mark v-else class="rounded-[3px]" :class="s.kind === 'cur' ? 'bg-hl-cur text-hl-cur-tx' : 'bg-hl text-hl-tx'">{{ s.t }}</mark></a
        ><span v-else-if="s.kind === 'plain'">{{ s.t }}</span
        ><mark v-else class="rounded-[3px]" :class="s.kind === 'cur' ? 'bg-hl-cur text-hl-cur-tx' : 'bg-hl text-hl-tx'">{{ s.t }}</mark></template
    >
</template>
