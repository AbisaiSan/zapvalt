<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import Logo from '../components/Logo.vue';
import { ApiError, sendJson } from '../lib/api';

// Fora do AppShell: nada do acervo é carregado antes de a senha ser aceita.
const password = ref('');
const error = ref('');
const wait = ref(0);
const busy = ref(false);
const input = ref<HTMLInputElement | null>(null);
let timer: ReturnType<typeof setInterval> | undefined;

const waiting = computed(() => wait.value > 0);
const message = computed(() => (waiting.value ? `Muitas tentativas erradas. Tente de novo em ${wait.value} s.` : error.value));

function countdown(seconds: number) {
    clearInterval(timer);
    wait.value = seconds;
    timer = setInterval(() => {
        wait.value = Math.max(0, wait.value - 1);
        if (!wait.value) {
            clearInterval(timer);
            input.value?.focus();
        }
    }, 1000);
}

async function submit() {
    if (waiting.value || busy.value) return;
    if (!password.value) {
        error.value = 'Digite a senha.';
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        await sendJson('POST', '/api/unlock', { password: password.value });
        window.location.assign('/');
    } catch (e) {
        password.value = '';
        busy.value = false;
        if (e instanceof ApiError && e.retryAfter > 0) countdown(e.retryAfter);
        else error.value = e instanceof ApiError && e.status === 422 ? 'Senha incorreta.' : 'Não foi possível conferir a senha. Tente de novo.';
        input.value?.focus();
    }
}

onMounted(() => input.value?.focus());
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <Head title="Desbloquear" />
    <div class="fixed inset-0 flex flex-col bg-page text-tx">
        <main class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 py-10 text-center">
            <Logo :size="72" :radius="20" :icon="44" />
            <h1 class="mt-6 mb-2 text-[26px] font-normal text-tx">ZapVault</h1>
            <p class="m-0 max-w-[400px] text-[14px] leading-[21px] text-tx2">Digite a senha para abrir o acervo.</p>
            <form class="mt-6 flex w-full max-w-[340px] flex-col gap-2.5" @submit.prevent="submit">
                <label for="unlock-password" class="sr-only">Senha</label>
                <div class="flex gap-2">
                    <input
                        id="unlock-password"
                        ref="input"
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Senha"
                        :disabled="waiting"
                        :aria-invalid="message ? 'true' : 'false'"
                        aria-describedby="unlock-message"
                        class="box-border h-10 min-w-0 flex-1 rounded-lg border border-div2 bg-panel px-3 text-[14px] text-tx disabled:opacity-60"
                        @input="error = ''"
                    />
                    <button type="submit" :disabled="waiting || busy" class="h-10 flex-none rounded-[20px] bg-btn px-5 text-[14px] font-medium text-btn-tx disabled:cursor-not-allowed disabled:opacity-60">Abrir</button>
                </div>
                <div id="unlock-message" class="min-h-[18px] text-[13px] text-err-tx" role="alert">{{ message }}</div>
            </form>
        </main>
        <div class="flex flex-none items-center justify-center gap-1.5 p-[14px] text-[12px] text-tx2">
            <Icon name="lock" :size="14" />
            <span>Acervo local e somente leitura. Nada é enviado.</span>
        </div>
    </div>
</template>
