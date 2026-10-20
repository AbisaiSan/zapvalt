import { onBeforeUnmount, onMounted, ref } from 'vue';
import { todayKey } from '../lib/format';

/** Data de hoje ("AAAA-MM-DD"), atualizada se o aplicativo ficar aberto na virada do dia. */
export function useToday() {
    const today = ref(todayKey());
    let timer: ReturnType<typeof setInterval> | undefined;

    onMounted(() => (timer = setInterval(() => (today.value = todayKey()), 60_000)));
    onBeforeUnmount(() => clearInterval(timer));

    return today;
}
