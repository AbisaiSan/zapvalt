import { ref } from 'vue';

const message = ref<{ text: string; id: number } | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

/** Aviso temporário no rodapé da tela. */
export function useToast() {
    return {
        toast: message,
        show(text: string) {
            clearTimeout(timer);
            message.value = { text, id: Date.now() };
            timer = setTimeout(() => (message.value = null), 2600);
        },
    };
}
