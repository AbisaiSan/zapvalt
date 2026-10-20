<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
</script>

<template>
    <div class="flex flex-col items-center gap-2.5 px-7 py-14 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-head text-tx2"><Icon name="upload" :size="28" /></div>
        <div class="mt-1 text-[16px] text-tx">Nenhuma conversa importada</div>
        <div class="max-w-[250px] text-[13px] leading-[19px] text-pretty text-tx2">Importe arquivos .txt ou .zip exportados do celular para montar seu acervo.</div>
        <Link href="/importar" class="mt-2 inline-flex h-[38px] items-center rounded-[19px] bg-btn px-[18px] text-[14px] font-medium text-btn-tx no-underline hover:text-btn-tx">Importar conversas</Link>
    </div>
</template>
