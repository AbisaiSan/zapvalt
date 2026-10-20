# Decisões

Decisões pequenas tomadas durante a implementação, com o motivo.

## Tarefa 00: base do projeto

- **PHP pelo Homebrew, sem Herd.** A pedido do usuário, o PHP (8.5) e o Composer vieram do Homebrew. A especificação exige 8.3 ou superior.
- **Inertia 2, não 3.** O Inertia 3 já existe, mas a especificação pede o 2. O `inertiajs/inertia-laravel` 2.x é compatível com o Laravel 13; no frontend, `@inertiajs/vue3` 2.3.
- **TypeScript 6.** O TypeScript 7 (versão nativa) não funciona com o `vue-tsc` 3.3, que é o verificador de tipos dos componentes Vue.
- **Pasta de dados.** `ZAPVAULT_DATA_PATH` vazio usa `~/ZapVault-dados`. Caminho relativo é resolvido a partir da raiz do projeto, o que permite aos testes usarem `storage/framework/testing/zapvault-data`, sem tocar no acervo real. A pasta, as subpastas da especificação (`db`, `media`, `thumbs`, `originals`, `backups`, `uploads-tmp`) e o arquivo do banco são criados na inicialização da aplicação, com permissão 700.
- **Banco.** A conexão `sqlite` aponta para `{DATA}/db/zapvault.sqlite`, com `journal_mode=WAL`, `synchronous=NORMAL`, `busy_timeout=10000` e chaves estrangeiras. A conexão `queue` separada fica para a tarefa 01. O antigo `database/database.sqlite` criado pelo instalador do Laravel não é mais usado (está fora do Git).
- **Tema antes da página aparecer.** Como a CSP proíbe scripts inline, o tema salvo em `localStorage` (`zv-theme`) é aplicado por `public/js/theme.js`, carregado no `<head>`. A mesma regra está em `resources/js/lib/theme.ts`, usada pela aplicação.
- **Barra de progresso do Inertia desligada.** Ela injeta `<style>` inline, bloqueado pela CSP (`default-src 'self'`).
- **CSP em desenvolvimento.** Com `npm run dev` ativo, a CSP libera só o servidor local do Vite (scripts, estilos e websocket), porque o Vite injeta estilos inline. Em uso normal, com `npm run build`, vale a política da especificação sem exceções.
- **Página de erro em modo debug.** Com `APP_DEBUG=true`, respostas de erro 500 saem sem CSP, porque a página de erro detalhada do Laravel usa scripts inline. Os demais cabeçalhos continuam.
- **Arquivos estáticos.** O `php artisan serve` entrega arquivos de `public/` sem passar pelo Laravel, então os cabeçalhos de segurança valem para todas as respostas da aplicação (páginas, API, erros), mas não para `.css` e `.js` compilados.
- **`composer run zapvault`.** Usa o `concurrently` (já instalado com o projeto) para subir `php artisan serve --host=127.0.0.1 --port=8000` e `php artisan queue:work`. Antes, apaga um `public/hot` esquecido, para garantir os arquivos compilados. `composer run dev` é o `php artisan dev` do Laravel 13 (servidor em 127.0.0.1, fila e Vite).
- **Fontes do sistema.** Removida a fonte Instrument Sans (baixada da Bunny Fonts) que vinha no projeto novo. A pilha de fontes é a do protótipo.
- **`AGENTS.md` removido** na configuração inicial: trazia instruções genéricas de instalação de PHP via `curl | bash`, contrárias às regras do projeto.

## Tarefa 01: banco e busca

- **Tabelas padrão removidas.** O ZapVault não tem login nem usa cache ou sessão em banco, então as migrations de `users` e `cache` (e o model `User`) saíram do projeto. Uma migration remove essas tabelas de bancos que já as tinham; em banco novo ela não faz nada.
- **Filas em `db/queue.sqlite`.** A migration das filas usa a conexão `queue` (`jobs`, `job_batches` e `failed_jobs`). Nos testes, as duas conexões ficam em memória.
- **Nome do arquivo em `body`.** Em imagens, vídeos, áudios, figurinhas e documentos, `body` guarda o nome do arquivo citado no texto. Assim a busca por nome de documento usa o mesmo campo, e a chave de duplicidade não depende de o anexo existir.
- **Gatilhos do FTS só para `search_text` preenchido.** Mensagens sem texto pesquisável não entram no índice; apagar uma conversa (em cascata) também limpa o índice.
- **Normalização.** `App\Support\TextNormalizer` segue a função `norm` do protótipo (NFD, sem marcas U+0300 a U+036F, minúsculas) e também remove os caracteres invisíveis do WhatsApp. Com `intl` ausente, usa uma tabela de letras acentuadas.
- **Cópia antes de migrar.** Antes de migrar o banco real, foi feita uma cópia com `VACUUM INTO` em `{DATA}/backups/pre-migracao-tarefa01.sqlite`.

## Tarefa 02: importador e linha de comando

- **Avisos do iPhone.** No iPhone, avisos (criptografia, grupo criado, alguém saiu) e chamadas aparecem com um remetente (o nome do grupo ou do contato) e o texto começa com a marca invisível U+200E. Antes de remover os invisíveis, o leitor guarda essa marca e trata essas linhas como avisos sem remetente, como no Android.
- **Mídia não exportada no iPhone.** Além de `<Mídia oculta>`, `<Arquivo de mídia oculto>` e `<Media omitted>`, os textos marcados do iPhone ("imagem ocultada", "vídeo omitido", "image omitted" etc.) também viram `missing`. Pontos finais no fim ("Esta mensagem foi apagada.") são ignorados na comparação.
- **Tipo na chave de duplicidade.** Para anexos, a chave usa o tipo do arquivo citado (imagem, vídeo...), mesmo quando ele não veio no pacote. Assim, importar primeiro o `.txt` e depois o `.zip` com mídia não duplica: ao somar, a mensagem que estava como "mídia faltante" ganha o anexo. Limite conhecido: `<Mídia oculta>` não traz o nome do arquivo e não pode ser casado com o anexo de outro export.
- **Contagem de mensagens.** `message_count` e a última mensagem (`last_message_id`, usada na prévia) ignoram avisos de sistema e chamadas, que no protótipo são avisos. `first_message_at` e `last_message_at` consideram todas as linhas.
- **Mesmo contato duas vezes no lote.** Se dois arquivos do mesmo lote têm o mesmo nome de conversa e não havia conversa anterior, o segundo é somado ao primeiro ao Concluir, em vez de virar "Nome (2)".
- **Vários textos no mesmo .zip.** Cada texto reconhecido como conversa vira um cartão; textos que não são conversa são ignorados se houver pelo menos uma conversa no pacote.
- **Nome da conversa.** Tirado do nome do texto; se for `_chat.txt` (iPhone), do nome do `.zip`.
- **Miniaturas.** Imagens ganham miniatura JPEG de até 640 px (GD). Figurinhas usam o próprio arquivo. Vídeo só ganha pôster, e áudio e vídeo só ganham duração, se `ffmpeg`/`ffprobe` existirem; páginas de PDF, se `pdfinfo` existir.
- **Limites do .zip** (em `config/zapvault.php`, ajustáveis pelo `.env`): 20 GB descompactados, 100 mil entradas e razão de compressão de até 200 vezes para entradas acima de 1 MB.
- **`zapvault:import` pelo terminal.** Recusa rodar se houver uma importação em andamento na tela. `--commit` conclui sem perguntas (soma às conversas existentes, salvo `--separate`); sem `--commit` e fora do modo interativo, a análise é descartada e nada é gravado.
- **Fixture com nome neutro.** O `.gitignore` ignora arquivos que começam com `WhatsApp`, então o exemplo no formato AM/PM se chama `eduardo-am-pm.txt` e o nome original é passado no teste.
- **Desempenho medido.** Um `.txt` sintético de 200 mil mensagens (18 MB) foi importado em cerca de 6 segundos; reimportar levou 8,5 segundos e resultou em 0 mensagens novas.
