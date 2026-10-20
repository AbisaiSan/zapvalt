// ZapVault — dados fictícios e utilitários (sem servidor)
const WD = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
const MO = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
export const AV = ['#5B6BC0', '#C0564B', '#2E8C8F', '#9C5BB0', '#A8701A', '#3F8A55', '#7A6A5A', '#3C7DBF'];
const TODAY = new Date(2026, 9, 20);
const cap = s => s.charAt(0).toUpperCase() + s.slice(1);
const parts = d => d.split('-').map(Number);
const wd = d => { const [y, m, dd] = parts(d); return new Date(y, m - 1, dd).getDay(); };
export const dayDiff = d => { const [y, m, dd] = parts(d); return Math.round((TODAY - new Date(y, m - 1, dd)) / 864e5); };
export const fmt = d => { const [y, m, dd] = d.split('-'); return `${dd}/${m}/${y}`; };
export const sepLabel = d => {
  const n = dayDiff(d);
  if (n === 0) return 'Hoje';
  if (n === 1) return 'Ontem';
  if (n <= 5) return cap(WD[wd(d)]);
  const [y, m, dd] = parts(d);
  return `${dd} de ${MO[m - 1]} de ${y}`;
};
export const weekday = d => cap(WD[wd(d)]);
export const listDate = m => {
  const n = dayDiff(m.d);
  if (n === 0) return m.t || 'Hoje';
  if (n === 1) return 'Ontem';
  if (n <= 5) return cap(WD[wd(m.d)].split('-')[0]);
  return fmt(m.d);
};
export const dur = sec => `${Math.floor(sec / 60)}:${String(Math.floor(sec % 60)).padStart(2, '0')}`;
export const initials = name => {
  const w = name.replace(/^Grupo\s+/i, '').split(/\s+/).filter(Boolean);
  return (((w[0] || '')[0] || '') + ((w[1] || '')[0] || '')).toUpperCase();
};
export const ph = h => `repeating-linear-gradient(135deg, oklch(0.80 0.05 ${h}) 0 12px, oklch(0.76 0.05 ${h}) 12px 24px)`;

// Busca: ignora maiúsculas, minúsculas e acentos, preservando os índices do texto original
export const norm = s => {
  let o = '';
  for (let i = 0; i < s.length; i++) {
    const c = s[i];
    const n = c.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    o += n.length ? n[0] : c;
  }
  return o;
};
export const ranges = (text, q) => {
  if (!text || !q) return [];
  const nt = norm(text), nq = norm(q.trim());
  if (!nq) return [];
  const out = [];
  let i = nt.indexOf(nq);
  while (i !== -1) { out.push([i, i + nq.length]); i = nt.indexOf(nq, i + nq.length); }
  return out;
};
const plain = t => ({ t, bg: 'transparent', fg: 'inherit' });
export const segs = (text, rs) => {
  if (!rs || !rs.length) return [plain(text)];
  const out = [];
  let p = 0;
  rs.forEach(([a, b, cur]) => {
    if (a > p) out.push(plain(text.slice(p, a)));
    out.push({ t: text.slice(a, b), bg: cur ? 'var(--hl-cur)' : 'var(--hl)', fg: cur ? 'var(--hl-cur-tx)' : 'var(--hl-tx)' });
    p = b;
  });
  if (p < text.length) out.push(plain(text.slice(p)));
  return out;
};
export const searchField = m => {
  if (m.w === 's' || m.k === 'deleted') return null;
  if (m.k === 'text') return 'text';
  if ((m.k === 'image' || m.k === 'video') && m.caption) return 'caption';
  if (m.k === 'doc') return 'file';
  return null;
};

export const mk = (id, name, ps, file, rows, extra) => {
  const msgs = rows.map((r, i) => {
    const [d, t, w, x, b] = r;
    const o = typeof x === 'string' ? { k: 'text', text: x } : Object.assign({}, x);
    return Object.assign(o, { id: id + '-' + i, d: '2026-' + d, t, w, batch: b || 0 });
  });
  return Object.assign({ id, name, parts: ps, file, msgs, group: ps.length > 1, av: 0 }, extra || {});
};

const E = { k: 'sys', icon: 'lock', text: 'As mensagens e ligações são protegidas com a criptografia de ponta a ponta.' };
const S = t => ({ k: 'sys', text: t });
const CALL = { k: 'call', text: 'Chamada de voz perdida' };

export const seed = () => [
  mk('maria', 'Maria Souza', ['Maria Souza'], 'Conversa com Maria Souza.zip', [
    ['10-19', null, 's', E],
    ['10-19', '09:14', 0, 'Bom dia! Consegue me mandar a proposta?'],
    ['10-19', '09:20', 'me', 'Claro, te envio ainda hoje.'],
    ['10-19', '09:22', 0, 'Ótimo. O evento é no dia 14 de novembro, umas 120 pessoas.'],
    ['10-19', '09:24', 'me', 'Você tem fotos do espaço?'],
    ['10-19', '09:30', 0, { k: 'image', file: 'IMG-20261019-WA0007.jpg', caption: 'Esse é o espaço', hue: 60, ratio: '4/3' }],
    ['10-19', '09:31', 0, { k: 'image', file: 'IMG-20261019-WA0008.jpg', hue: 220, ratio: '3/4' }],
    ['10-19', '09:41', 0, { k: 'audio', dur: 42 }],
    ['10-19', '09:50', 'me', 'Ouvi, vou ajustar o prazo.'],
    ['10-19', '09:52', 'me', 'Só preciso confirmar o som com o fornecedor.'],
    ['10-19', '10:02', 0, { k: 'deleted' }],
    ['10-19', '10:15', 's', CALL],
    ['10-19', '10:16', 0, 'Te liguei, mas pode ser por aqui mesmo.'],
    ['10-19', '10:18', 0, 'Precisamos de:\n• 120 cadeiras\n• uma mesa para o coffee break\n• projetor e telão'],
    ['10-19', '10:25', 'me', 'Anotado, vai tudo na proposta.'],
    ['10-19', '14:03', 0, { k: 'sticker', file: 'STK-20261019-WA0003.webp', hue: 330 }],
    ['10-19', '17:40', 'me', 'Proposta enviada por e-mail. Qualquer dúvida, me chama.'],
    ['10-19', '17:52', 0, 'Recebi! Vou passar para o financeiro.'],
    ['10-20', '08:12', 0, { k: 'image', file: 'IMG-20261020-WA0001.jpg', caption: 'Fachada, para o material de divulgação', hue: 160, ratio: '16/9' }],
    ['10-20', '08:55', 0, 'PROPOSTA aprovada pelo financeiro!'],
    ['10-20', '08:55', 0, 'Só pediram para atualizar os valores do coffee break.'],
    ['10-20', '08:57', 'me', 'Ótima notícia! Vou preparar o contrato.'],
    ['10-20', '08:58', 'me', 'E já te mando a versão com os valores novos.'],
    ['10-20', '09:02', 0, { k: 'video', file: 'VID-20261020-WA0002.mp4', dur: 18, caption: 'Montagem do ano passado', hue: 280, ratio: '16/9' }],
    ['10-20', '09:05', 'me', 'Ficou muito bom, dá para repetir.'],
    ['10-20', '09:08', 'me', { k: 'missing' }],
    ['10-20', '09:09', 'me', 'Era a planta baixa. Te reenvio depois.'],
    ['10-20', '09:12', 0, 'Sem pressa.'],
    ['10-20', '09:21', 'me', { k: 'doc', file: 'Proposta_v3.pdf', size: '240 KB', pages: 3, ext: 'PDF' }],
    ['10-20', '09:21', 'me', 'Segue o PDF\ncom os valores atualizados.'],
  ], { av: 0 }),
  mk('alfa', 'Grupo Projeto Alfa', ['Carlos Lima', 'Ana Ribeiro', 'Luciana Prado'], 'Conversa com Grupo Projeto Alfa.zip', [
    ['10-09', null, 's', E, 2],
    ['10-09', '10:00', 's', S('Carlos Lima criou o grupo “Projeto Alfa”'), 2],
    ['10-09', '10:00', 's', S('Carlos Lima adicionou você, Ana Ribeiro e Luciana Prado'), 2],
    ['10-09', '10:02', 0, 'Pessoal, criei o grupo para centralizar o projeto Alfa.', 2],
    ['10-09', '10:05', 1, 'Boa! Já coloco o cronograma aqui.', 2],
    ['10-09', '10:11', 1, { k: 'doc', file: 'Cronograma_Alfa.xlsx', size: '86 KB', ext: 'XLSX' }, 2],
    ['10-09', '10:20', 'me', 'Obrigado, Ana. Reviso até amanhã.', 2],
    ['10-09', '10:31', 2, 'Entrei agora. Qual é o prazo da primeira entrega?', 2],
    ['10-09', '10:33', 0, 'Dia 30, se tudo correr bem.', 2],
    ['10-13', '14:10', 0, 'Atualizei a planilha com as horas da semana.', 1],
    ['10-13', '14:12', 2, { k: 'image', file: 'IMG-20261013-WA0011.jpg', caption: 'Quadro da sala de reunião', hue: 100, ratio: '4/3' }, 1],
    ['10-13', '14:15', 'me', 'Fechou. Faltam só os testes da integração.', 1],
    ['10-13', '14:20', 1, { k: 'audio', dur: 75 }, 1],
    ['10-13', '14:31', 2, 'Concordo com a Ana, melhor adiar a demo uma semana.', 1],
    ['10-13', '14:40', 0, 'Ok, vou avisar o cliente.', 1],
    ['10-16', '09:03', 1, 'Bom dia! Subi a versão 2 do layout.'],
    ['10-16', '09:04', 1, { k: 'image', file: 'IMG-20261016-WA0004.jpg', hue: 20, ratio: '16/9', broken: true }],
    ['10-16', '09:10', 2, 'Não abriu aqui, pode reenviar?'],
    ['10-16', '09:12', 1, { k: 'image', file: 'IMG-20261016-WA0005.jpg', caption: 'Agora vai', hue: 250, ratio: '16/9' }],
    ['10-16', '09:20', 0, 'Ficou ótimo. Só aumentaria o contraste do botão.'],
    ['10-16', '09:25', 'me', 'Ajusto hoje à tarde.'],
    ['10-16', '15:47', 'me', { k: 'doc', file: 'Layout_v2_ajustes.pdf', size: '1,2 MB', pages: 8, ext: 'PDF' }],
    ['10-16', '15:50', 2, { k: 'sticker', file: 'STK-20261016-WA0001.webp', hue: 40 }],
    ['10-19', '16:20', 2, 'Alguém confirma a reunião com o cliente?'],
    ['10-19', '16:31', 1, 'Eu consigo na quinta à tarde.'],
    ['10-19', '16:33', 'me', 'Quinta às 15h para mim também.'],
    ['10-19', '16:50', 0, { k: 'video', file: 'VID-20261019-WA0001.mp4', dur: 34, caption: 'Gravação da demo', hue: 200, ratio: '16/9' }],
    ['10-19', '17:05', 0, 'reunião confirmada para quinta'],
  ], { av: 2 }),
  mk('joao', 'João Pereira', ['João Pereira'], 'Conversa com João Pereira.txt', [
    ['10-15', '10:12', 0, 'Oi! Você tem o contato do fornecedor de som?'],
    ['10-15', '10:30', 'me', 'Tenho sim, é o Sérgio: (98) 98877-1020.'],
    ['10-19', '16:02', 0, 'Falei com ele, fechamos para o dia 14.'],
    ['10-19', '16:05', 0, { k: 'audio', dur: 23 }],
    ['10-19', '16:10', 'me', 'Que bom! Me avisa se precisar de algo.'],
    ['10-19', '16:11', 0, 'Perfeito, obrigado!'],
  ], { av: 7 }),
  mk('grafica', 'Gráfica Rápida', ['Gráfica Rápida'], 'Conversa com Gráfica Rápida.zip', [
    ['10-14', '11:00', 'me', 'Bom dia! Preciso de 500 folders A5, frente e verso.'],
    ['10-14', '11:05', 'me', 'É para incluir na proposta de um evento.'],
    ['10-14', '11:40', 0, 'Bom dia! Qual papel e acabamento?'],
    ['10-14', '11:46', 'me', 'Couché 150 g, sem verniz.'],
    ['10-16', '11:30', 0, { k: 'doc', file: 'Orcamento_4471.pdf', size: '112 KB', pages: 1, ext: 'PDF' }],
    ['10-16', '11:30', 0, 'Orçamento da proposta em anexo'],
  ], { av: 4 }),
  mk('fin', 'Grupo Financeiro', ['Ana Ribeiro', 'Marcos Teixeira'], 'Conversa com Grupo Financeiro.zip', [
    ['10-15', '17:00', 1, 'As notas fiscais de setembro estão na pasta compartilhada.'],
    ['10-15', '17:05', 1, { k: 'doc', file: 'NF_setembro.zip', size: '3,4 MB', ext: 'ZIP' }],
    ['10-15', '17:30', 'me', 'Valeu, Marcos. Falta o extrato do dia 28.'],
    ['10-16', '10:12', 0, { k: 'image', file: 'IMG-20261016-WA0009.jpg', caption: 'Extrato do dia 28', hue: 80, ratio: '3/4' }],
    ['10-16', '10:40', 1, 'Bateu tudo?'],
    ['10-16', '11:02', 0, 'conciliação fechada'],
  ], { av: 5 }),
  mk('fernanda', 'Fernanda Lima', ['Fernanda Lima'], 'Conversa com Fernanda Lima.txt', [
    ['10-13', '18:20', 0, 'Oi, tudo bem? Recebi o contrato.'],
    ['10-13', '18:22', 0, 'Tenho duas dúvidas sobre a cláusula de cancelamento.'],
    ['10-13', '19:05', 'me', 'Oi, Fernanda! Claro, podemos conversar.'],
    ['10-14', '08:10', 's', CALL],
    ['10-14', '08:31', 0, 'Pode me ligar amanhã cedo?'],
  ], { av: 3 }),
  mk('transp', 'Transportadora Norte', ['Transportadora Norte'], 'Conversa com Transportadora Norte.txt', [
    ['10-10', '14:00', 'me', 'Boa tarde! Qual a previsão do pedido 1842?'],
    ['10-10', '14:22', 0, 'Boa tarde! Previsão de saída na segunda.'],
    ['10-12', '07:48', 0, 'Pedido 1842 saiu para entrega'],
  ], { av: 6 }),
  mk('ricardo', 'Ricardo Alves', ['Ricardo Alves'], 'Conversa com Ricardo Alves.zip', [
    ['10-06', '10:00', 'me', { k: 'doc', file: 'Proposta_Alves_Eventos.pdf', size: '310 KB', pages: 4, ext: 'PDF' }],
    ['10-06', '10:01', 'me', 'Ricardo, segue a proposta para o lançamento.'],
    ['10-07', '16:20', 0, 'Recebi, vou apresentar para a diretoria amanhã.'],
    ['10-08', '11:45', 0, 'Aceitamos a Proposta'],
  ], { av: 1 }),
  mk('clinica', 'Clínica Vida', ['Clínica Vida'], 'Conversa com Clínica Vida.txt', [
    ['10-01', '16:40', 'me', 'Boa tarde! Gostaria de remarcar a consulta de amanhã para mais tarde.'],
    ['10-01', '16:55', 0, 'Boa tarde! Temos às 15h ou às 17h30.'],
    ['10-01', '17:02', 'me', 'Pode ser às 15h.'],
    ['10-02', '08:05', 0, 'Confirmo o horário das 15h'],
  ], { av: 5 }),
  mk('parceiros', 'Grupo Parceiros', ['Luciana Prado', 'Diego Martins', 'Helena Castro'], 'Conversa com Grupo Parceiros.zip', [
    ['09-22', '09:00', 's', S('Diego Martins adicionou você')],
    ['09-22', '09:10', 1, 'Bem-vindos! Aqui vamos alinhar as indicações de clientes.'],
    ['09-22', '09:30', 2, 'Ótimo. Mando a lista de contatos ainda hoje.'],
    ['09-25', '15:00', 2, { k: 'doc', file: 'Contatos_parceiros.csv', size: '18 KB', ext: 'CSV' }],
    ['09-25', '15:12', 'me', 'Obrigado, Helena!'],
    ['09-28', '10:40', 0, { k: 'doc', file: 'Proposta_Parceria_2027.pdf', size: '520 KB', pages: 6, ext: 'PDF' }],
    ['09-28', '10:41', 0, 'nova PROPOSTA enviada'],
  ], { av: 3 }),
  mk('paulo', 'Paulo Mendes', ['Paulo Mendes'], 'Conversa com Paulo Mendes.txt', [
    ['09-02', '11:00', 0, 'Oi! Obrigado pela indicação.'],
    ['09-02', '11:20', 'me', 'Imagina, Paulo. Boa sorte com o projeto.'],
    ['09-15', '08:02', 0, 'Bom dia, tudo bem?'],
  ], { av: 7 }),
  mk('num', '+55 98 91234-5678', ['+55 98 91234-5678'], 'Conversa com +55 98 91234-5678.zip', [
    ['09-03', '19:14', 0, { k: 'missing' }],
    ['09-03', '19:15', 0, 'Olá, vi o anúncio'],
  ], { av: 6 }),
];

export const importRows = key => {
  if (key === 'beatriz') return [
    ['10-17', null, 's', E],
    ['10-17', '10:00', 0, 'Oi! Aqui é a Beatriz, da Rocha Decor.'],
    ['10-17', '10:02', 0, 'Vi o seu trabalho no evento da Maria e adorei.'],
    ['10-17', '10:15', 'me', 'Oi, Beatriz! Obrigado. Como posso ajudar?'],
    ['10-17', '10:20', 0, { k: 'image', file: 'IMG-20261017-WA0002.jpg', caption: 'Inspiração para a festa de fim de ano', hue: 15, ratio: '4/3' }],
    ['10-17', '10:21', 0, { k: 'missing' }],
    ['10-17', '10:30', 'me', 'Dá para fazer, sim. Qual a data?'],
    ['10-18', '09:00', 0, { k: 'audio', dur: 31 }],
    ['10-18', '09:05', 0, 'Me manda um orçamento quando puder?'],
  ];
  if (key === 'obras') return [
    ['10-11', '08:00', 's', S('Eduardo Nunes criou o grupo “Obras Sede”')],
    ['10-11', '08:02', 0, 'Começamos a pintura na segunda.'],
    ['10-11', '08:10', 1, 'Precisa liberar o acesso pelo portão 2.'],
    ['10-11', '08:12', 'me', 'Já pedi para a portaria.'],
    ['10-13', '17:30', 0, { k: 'missing' }],
    ['10-13', '17:35', 1, 'Ficou bom!'],
    ['10-15', '07:50', 0, 'Hoje tem entrega de material às 10h.'],
    ['10-15', '07:52', 'me', 'Ok, vou estar lá.'],
  ];
  return [
    ['10-17', '08:29', 0, { k: 'image', file: 'IMG-20261017-WA0010.jpg', caption: 'Prova do folder', hue: 190, ratio: '3/4' }],
    ['10-17', '08:30', 0, 'Arte aprovada? Podemos imprimir hoje.'],
    ['10-17', '08:45', 'me', 'Aprovada, pode imprimir!'],
  ];
};

export const sampleFiles = () => [
  { id: 'f1', key: 'beatriz', name: 'Conversa com Beatriz Rocha.zip', size: '3,8 MB', kind: 'ok', conv: 'Beatriz Rocha', sum: [8, 0, 2, 1] },
  { id: 'f2', key: 'grafica', name: 'Conversa com Gráfica Rápida.zip', size: '1,1 MB', kind: 'exists', conv: 'Gráfica Rápida', sum: [3, 6, 1, 0] },
  { id: 'f3', key: 'obras', name: 'Conversa com Grupo Obras Sede.txt', size: '86 KB', kind: 'err', conv: 'Grupo Obras Sede', sum: [7, 0, 0, 1], lines: [
    { n: 'linha 14', t: '13/10/2026 17:3 - Eduardo Nunes: Segue a foto' },
    { n: 'linha 27', t: '<anexado: 00000041-PHOTO-2026-10-13.jpg' },
    { n: 'linha 31', t: '15/10/26, 7:50 PM - Eduardo Nunes: Confirmado' },
  ] },
].map(f => Object.assign(f, { status: 'wait', pct: 0, choice: null, open: false }));

export const nameOptions = [
  { label: 'Moura Consultoria', hint: 'Autor de 14 mensagens nos 3 arquivos', tag: 'Sugerido' },
  { label: 'Rafael Moura', hint: 'Autor de 2 mensagens em 1 arquivo', tag: '' },
  { label: 'Nenhum destes', hint: 'Defino depois em Configurações', tag: '' },
];
