import { reactive } from 'vue';

// Um único elemento <audio> para o aplicativo inteiro: só um áudio toca por vez.
const state = reactive({
    id: null as number | null,
    playing: false,
    position: 0,
    duration: 0,
    speeds: {} as Record<number, number>,
    /** Áudio que não pôde ser aberto (arquivo ausente ou ilegível). */
    failed: null as number | null,
});

let audio: HTMLAudioElement | null = null;

function element(): HTMLAudioElement {
    if (audio) return audio;
    audio = new Audio();
    audio.preload = 'metadata';
    audio.addEventListener('timeupdate', () => (state.position = audio!.currentTime));
    audio.addEventListener('loadedmetadata', () => {
        if (Number.isFinite(audio!.duration)) state.duration = audio!.duration;
    });
    audio.addEventListener('play', () => (state.playing = true));
    audio.addEventListener('pause', () => (state.playing = false));
    audio.addEventListener('error', () => {
        state.failed = state.id;
        state.playing = false;
    });
    audio.addEventListener('ended', () => {
        state.playing = false;
        state.position = 0;
    });
    return audio;
}

function load(id: number, url: string, duration: number | null) {
    const a = element();
    if (state.id === id) return a;
    a.pause();
    a.src = url;
    state.id = id;
    state.failed = null;
    state.position = 0;
    state.duration = duration ?? 0;
    a.playbackRate = state.speeds[id] ?? 1;
    return a;
}

export function useAudioPlayer() {
    return {
        player: state,
        toggle(id: number, url: string, duration: number | null) {
            const a = load(id, url, duration);
            if (state.playing) a.pause();
            else a.play().catch(() => (state.playing = false));
        },
        seek(id: number, url: string, duration: number | null, fraction: number) {
            const a = load(id, url, duration);
            const total = state.duration || duration || 0;
            a.currentTime = Math.max(0, Math.min(1, fraction)) * total;
            state.position = a.currentTime;
        },
        cycleSpeed(id: number) {
            const next = ({ 1: 1.5, 1.5: 2, 2: 1 } as Record<number, number>)[state.speeds[id] ?? 1] ?? 1;
            state.speeds[id] = next;
            if (state.id === id && audio) audio.playbackRate = next;
        },
        stop() {
            audio?.pause();
            state.id = null;
            state.playing = false;
        },
    };
}
