import { ref } from 'vue';
import { getJson } from '../lib/api';

const names = ref<string[] | null>(null);

/** Nomes do usuário (Configurações), carregados uma vez. */
export function useUserNames() {
    if (names.value === null) {
        names.value = [];
        getJson<{ user_names: string[] }>('/api/settings')
            .then((s) => (names.value = s.user_names))
            .catch(() => {});
    }
    return names;
}
