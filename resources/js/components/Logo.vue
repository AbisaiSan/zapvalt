<script setup lang="ts">
withDefaults(defineProps<{ size?: number; radius?: number; icon?: number }>(), { size: 32, radius: 9, icon: 20 });
</script>

<template>
    <div class="flex flex-none items-center justify-center bg-[#00A884]" :style="{ width: `${size}px`, height: `${size}px`, borderRadius: `${radius}px` }" aria-hidden="true">
        <svg :width="icon" :height="icon" viewBox="0 0 24 24">
            <path d="M5 3.5h14a2.5 2.5 0 0 1 2.5 2.5v9a2.5 2.5 0 0 1-2.5 2.5h-7.5L7 21v-3.5H5A2.5 2.5 0 0 1 2.5 15V6A2.5 2.5 0 0 1 5 3.5z" fill="#fff" />
            <rect x="8.6" y="9.6" width="6.8" height="5" rx="1.2" fill="#00A884" />
            <path d="M10 9.6V8.4a2 2 0 0 1 4 0v1.2" fill="none" stroke="#00A884" stroke-width="1.5" />
        </svg>
    </div>
</template>
