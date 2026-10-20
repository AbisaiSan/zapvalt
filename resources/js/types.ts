// Formatos da API (docs/ESPECIFICACAO.md, seção "Endpoints e contratos").

export interface ConversationItem {
    id: number;
    name: string;
    is_group: boolean;
    is_phone: boolean;
    avatar_index: number;
    preview: string;
    last_message_at: string | null;
}

export interface Participant {
    id: number;
    name: string;
    is_me: boolean;
    message_count: number;
    color_slot: number | null;
}

export interface ConversationHeader extends Omit<ConversationItem, 'preview'> {
    first_message_at: string | null;
    message_count: number;
    participant_count: number;
    source_file_name: string | null;
    participants: Participant[];
}

export type MessageKind = 'text' | 'image' | 'video' | 'audio' | 'doc' | 'sticker' | 'system' | 'call' | 'deleted' | 'missing';

export interface Attachment {
    id: number;
    name: string;
    ext: string;
    size_bytes: number;
    width: number | null;
    height: number | null;
    duration: number | null;
    pages: number | null;
    url: string;
    thumb_url: string | null;
}

export interface Message {
    id: number;
    sent_at: string;
    kind: MessageKind;
    sender: { participant_id: number; name: string; is_me: boolean; color_slot: number | null } | null;
    text: string | null;
    caption: string | null;
    system_icon: string | null;
    attachment: Attachment | null;
}

export interface MessageWindow {
    messages: Message[];
    has_older: boolean;
    has_newer: boolean;
    older_cursor: string | null;
    newer_cursor: string | null;
    focus_id?: number;
}

export interface MessageResult {
    message_id: number;
    conversation_id: number;
    conversation_name: string;
    sent_at: string;
    who: string | null;
    field: 'text' | 'caption' | 'file';
    kind: MessageKind;
    text: string;
}

export interface MessageSearchPage {
    results: MessageResult[];
    next_cursor: string | null;
    total?: number;
    total_capped?: boolean;
    conversations?: number;
}

export interface ArchiveSummary {
    conversations: number;
    messages: number;
    first_message_at: string | null;
    last_message_at: string | null;
    attachments: number;
    bytes_used: number;
}

export interface DayCount {
    day: string;
    count: number;
}

export interface Match {
    message_id: number;
    sent_at: string;
    count: number;
}

export type ImportStatus = 'uploading' | 'waiting' | 'processing' | 'done' | 'partial' | 'failed';

export interface ImportFileCard {
    id: number;
    name: string;
    ext: string;
    size_bytes: number;
    status: ImportStatus;
    percent: number;
    conversation_name: string | null;
    stats: { new: number; repeated: number; media_found: number; media_missing: number; lines_read: number } | null;
    unrecognized_count: number;
    unrecognized_lines: { line: number; text: string }[];
    error_message: string | null;
    existing: { id: number; name: string | null } | null;
    choice: 'merge' | 'separate' | null;
    needs_choice: boolean;
}

export interface ImportState {
    batch_id: number | null;
    files: ImportFileCard[];
    name_question: { ask: boolean; candidates: { name: string; hint: string; suggested: boolean }[] } | null;
    can_commit: boolean;
    hint: string;
}
