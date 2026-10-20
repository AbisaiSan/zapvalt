<script setup lang="ts">
// Ícones do protótipo. Decorativos (aria-hidden); o rótulo fica no botão.
defineProps<{ name: IconName; size?: number }>();
</script>

<script lang="ts">
export type IconName =
    | 'upload' | 'moon' | 'sun' | 'sliders' | 'search' | 'close' | 'person' | 'back' | 'calendar' | 'lock'
    | 'call' | 'up' | 'down' | 'play' | 'pause' | 'videocam' | 'alert' | 'download' | 'no-media' | 'deleted';
</script>

<template>
    <svg :width="size ?? 20" :height="size ?? 20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="flex-none">
        <g v-if="name === 'upload'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 15V4.5M7.5 9L12 4.5 16.5 9" /><path d="M4.5 14.5v3.5a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-3.5" />
        </g>
        <path v-else-if="name === 'moon'" d="M19.5 14.2A7.5 7.5 0 0 1 9.8 4.5a7.5 7.5 0 1 0 9.7 9.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        <g v-else-if="name === 'sun'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4" /><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4" />
        </g>
        <g v-else-if="name === 'sliders'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 7h9M17 7h3M4 17h3M11 17h9" /><circle cx="15" cy="7" r="2" /><circle cx="9" cy="17" r="2" />
        </g>
        <g v-else-if="name === 'search'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4.2-4.2" />
        </g>
        <path v-else-if="name === 'close'" d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <g v-else-if="name === 'person'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <circle cx="12" cy="9" r="3.6" /><path d="M5 20a7 7 0 0 1 14 0" />
        </g>
        <path v-else-if="name === 'back'" d="M19 12H5M11 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <g v-else-if="name === 'calendar'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17M8 3v4M16 3v4" />
        </g>
        <g v-else-if="name === 'lock'" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <rect x="5" y="10.5" width="14" height="9.5" rx="2" /><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
        </g>
        <path v-else-if="name === 'call'" d="M6.5 3.5h3l1.5 4.5-2 1.2a11 11 0 0 0 5.8 5.8l1.2-2 4.5 1.5v3a2 2 0 0 1-2 2A16.5 16.5 0 0 1 4.5 5.5a2 2 0 0 1 2-2z" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linejoin="round" />
        <path v-else-if="name === 'up'" d="M6 15l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <path v-else-if="name === 'down'" d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <path v-else-if="name === 'play'" d="M8 5.5v13l10.5-6.5z" fill="currentColor" />
        <path v-else-if="name === 'pause'" d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor" />
        <g v-else-if="name === 'videocam'" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
            <rect x="3" y="6" width="12.5" height="12" rx="2" /><path d="M15.5 10.5l5-3v9l-5-3" />
        </g>
        <g v-else-if="name === 'alert'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5v5M12 16v.5" />
        </g>
        <g v-else-if="name === 'download'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 4v11M7.5 10.5L12 15l4.5-4.5" /><path d="M5 20h14" />
        </g>
        <g v-else-if="name === 'no-media'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" /><path d="M4 5l16 14" />
        </g>
        <g v-else-if="name === 'deleted'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <circle cx="12" cy="12" r="8" /><path d="M6.5 6.5l11 11" />
        </g>
    </svg>
</template>
