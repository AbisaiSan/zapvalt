import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Tela estreita (menos de 760 px): uma coluna por vez. */
export function useNarrow() {
    const narrow = ref(typeof window !== 'undefined' && window.innerWidth < 760);
    const update = () => (narrow.value = window.innerWidth < 760);

    onMounted(() => {
        update();
        window.addEventListener('resize', update);
    });
    onBeforeUnmount(() => window.removeEventListener('resize', update));

    return narrow;
}
