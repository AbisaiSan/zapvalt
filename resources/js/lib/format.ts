// Datas, nomes e tamanhos, como no protótipo. Horários chegam sem fuso ("2026-10-20T09:21:00")
// e são só formatados, nunca convertidos.

const WD = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
const MO = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

/** Cores de avatar por avatar_index (0 a 7). */
export const AVATAR_COLORS = ['#5B6BC0', '#C0564B', '#2E8C8F', '#9C5BB0', '#A8701A', '#3F8A55', '#7A6A5A', '#3C7DBF'];

/** Cor do selo de extensão dos documentos. */
export function extColor(ext: string): string {
    return ({ PDF: '#D64545', XLSX: '#1E8A4C', ZIP: '#6B7780', CSV: '#127C8C' } as Record<string, string>)[ext.toUpperCase()] ?? '#6B7780';
}

const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

/** "AAAA-MM-DD" de hoje, no relógio do computador. */
export function todayKey(now: Date = new Date()): string {
    const p = (n: number) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${p(now.getMonth() + 1)}-${p(now.getDate())}`;
}

/** Parte da data ("AAAA-MM-DD") de um horário sem fuso. */
export function dayOf(at: string): string {
    return at.slice(0, 10);
}

/** "HH:MM" de um horário sem fuso. */
export function timeOf(at: string): string {
    return at.slice(11, 16);
}

function parts(day: string): [number, number, number] {
    const [y, m, d] = day.split('-').map(Number);
    return [y, m, d];
}

/** Dias entre a data e hoje (positivo no passado). */
export function dayDiff(day: string, today: string = todayKey()): number {
    const [y, m, d] = parts(day);
    const [ty, tm, td] = parts(today);
    return Math.round((Date.UTC(ty, tm - 1, td) - Date.UTC(y, m - 1, d)) / 864e5);
}

function weekdayIndex(day: string): number {
    const [y, m, d] = parts(day);
    return new Date(Date.UTC(y, m - 1, d)).getUTCDay();
}

/** "20/10/2026". */
export function fmt(day: string): string {
    const [y, m, d] = day.split('-');
    return `${d}/${m}/${y}`;
}

/** "Segunda-feira". */
export function weekday(day: string): string {
    return cap(WD[weekdayIndex(day)]);
}

/** "janeiro" a "dezembro". */
export function monthName(month: number): string {
    return MO[month - 1];
}

/** Separador de dia na conversa: Hoje, Ontem, dia da semana até 5 dias, senão "19 de outubro de 2026". */
export function sepLabel(day: string, today: string = todayKey()): string {
    const n = dayDiff(day, today);
    if (n === 0) return 'Hoje';
    if (n === 1) return 'Ontem';
    if (n > 1 && n <= 5) return cap(WD[weekdayIndex(day)]);
    const [y, m, d] = parts(day);
    return `${d} de ${MO[m - 1]} de ${y}`;
}

/** Data da lista de conversas: hora se for hoje, Ontem, dia da semana até 5 dias, senão dd/mm/aaaa. */
export function listDate(at: string | null, today: string = todayKey()): string {
    if (!at) return '';
    const day = dayOf(at);
    const n = dayDiff(day, today);
    if (n === 0) return timeOf(at);
    if (n === 1) return 'Ontem';
    if (n > 1 && n <= 5) return cap(WD[weekdayIndex(day)].split('-')[0]);
    return fmt(day);
}

/** "0:42". */
export function dur(sec: number): string {
    return `${Math.floor(sec / 60)}:${String(Math.floor(sec % 60)).padStart(2, '0')}`;
}

/** Iniciais do avatar, ignorando o "Grupo " do começo. */
export function initials(name: string): string {
    const w = name.replace(/^Grupo\s+/i, '').split(/\s+/).filter(Boolean);
    return (((w[0] || '')[0] || '') + ((w[1] || '')[0] || '')).toUpperCase();
}

/** "1 mensagem", "2 mensagens"; números com separador de milhar. */
export function plural(n: number, one: string, many: string): string {
    return `${n.toLocaleString('pt-BR')} ${n === 1 ? one : many}`;
}

/** "240 KB", "1,2 MB". */
export function fileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    const mb = bytes / 1024 / 1024;
    if (mb < 1024) return `${mb.toLocaleString('pt-BR', { maximumFractionDigits: 1 })} MB`;
    return `${(mb / 1024).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} GB`;
}

/** Atalho de busca exibido na tela. */
export function searchShortcut(platform: string = typeof navigator === 'undefined' ? '' : navigator.platform || navigator.userAgent): string {
    return /Mac|iPhone|iPad/i.test(platform) ? '⌘K' : 'Ctrl K';
}
