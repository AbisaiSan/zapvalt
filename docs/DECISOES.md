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

## Tarefa 03: dados de demonstração

- **A demonstração passa pelo importador.** O `zapvault:demo` monta, para cada conversa do protótipo, um export no formato Android com mídia de mentira dentro de um `.zip` e importa pelo mesmo caminho dos arquivos reais. Assim as chaves de duplicidade, a busca e as contagens são idênticas às de uma importação de verdade, e rodar de novo não duplica nada.
- **Datas deslocadas.** O protótipo fixa "hoje" em 20/10/2026; as datas são deslocadas para que esse dia vire a data real, e os rótulos "Hoje" e "Ontem" batem com o protótipo.
- **Nome do usuário.** "Moura Consultoria" (o nome em Configurações no protótipo) é adicionado aos nomes do usuário.
- **Mídia de mentira.** Imagens JPEG coloridas com faixas e o nome do arquivo; figurinhas WebP; áudio WAV silencioso com a duração do protótipo; PDFs com o número de páginas e o tamanho do protótipo; outros documentos com o tamanho do protótipo. Vídeo só é de verdade se o `ffmpeg` existir; sem ele, o arquivo não abre e a tela mostra o estado de erro. A imagem marcada como quebrada no protótipo (`IMG-20261016-WA0004.jpg`) é gravada corrompida, para o mesmo efeito. Arquivos de origem (`.txt` ou `.zip`) e cores de avatar são os do protótipo.
- **Segurança.** Com conversas no acervo, o comando pergunta antes (padrão "não"); fora do modo interativo, só roda com `--force`. Também recusa rodar com importação em andamento.
- **Escala grande.** `--scale=large` soma 288 conversas sintéticas (uma com 100 mil mensagens, cerca de 10% grupos) às 12 do protótipo: 300 conversas e 1,5 milhão de mensagens, em cerca de 65 segundos e 770 MB de banco.
- **Correção no importador.** Um `.zip` anexado como documento dentro de uma conversa (por exemplo, `NF_setembro.zip`) era tratado como outro pacote de conversas e derrubava a importação. Agora, se o pacote já tem o texto da conversa, os `.zip` internos são anexos; os `.zip` internos só são abertos como conversas quando não há texto, e os que não abrem são ignorados.
- **Índice em `messages.participant_id`.** O SQLite não cria índice para chave estrangeira; sem ele, o `zapvault:verify` levava 53 segundos na escala grande. Com o índice, menos de 1 segundo.

## Tarefa 04: API

- **Rotas JSON no grupo `web`.** Os endpoints ficam em `routes/web.php`, sob `/api`, com sessão e proteção CSRF (o navegador envia o cabeçalho `X-XSRF-TOKEN`). Isso impede que outro site aberto no navegador faça pedidos de escrita para `127.0.0.1`.
- **Rascunhos invisíveis.** A rota `{conversation}` só resolve conversas ativas; rascunhos de importação dão 404 em todos os endpoints e na mídia.
- **Mensagem.** `text` traz o corpo em mensagens de texto, avisos e chamadas; nos demais tipos é nulo (o nome do arquivo vem em `attachment.name`). `thumb_url` existe para imagens e figurinhas (sem miniatura, devolve a própria imagem) e para vídeos com pôster. `color_slot` é nulo para você.
- **Janelas de mensagens.** Além do que a especificação pede, a resposta traz `older_cursor` e `newer_cursor` para o próximo pedido. `limit` vai até 200. `around` divide o limite ao meio (antes e depois). `day` sem mensagens a partir da data devolve as mais recentes, sem `focus_id`.
- **Busca de mensagens.** As contagens (`total`, `total_capped` e `conversations`) vêm só na primeira página. `total` para em 10.000 e `total_capped` indica o "10.000+"; `conversations` é exato, contado na mesma passada. Para ordenar, a consulta pega só os ids e depois os detalhes da página, o que derrubou a busca por um termo presente em 22% de 1,5 milhão de mensagens de 3,2 s para cerca de 0,4 s.
- **Busca de 2 letras.** O `LIKE` definido pela especificação varre a tabela: cerca de 1,1 s com 1,5 milhão de mensagens (as buscas de 3 ou mais letras ficam abaixo de 0,5 s). Para uma busca lenta não segurar as outras, o `composer run zapvault` sobe o servidor com 4 processos (`PHP_CLI_SERVER_WORKERS=4`, que exige `--no-reload`: mudanças no `.env` pedem reiniciar).
- **Busca de conversas.** Até 200 itens, com o total.
- **Mídia.** `BinaryFileResponse` com `Range` (206); intervalo que começa no fim do arquivo ou depois dele, ou com fim antes do início, responde 416 (o Symfony devolveria o arquivo inteiro). ETag com o SHA-256 e 304 quando confere. Tipos fora da lista (inclusive HTML, SVG, JS e CSV) saem como `application/octet-stream` e download.
- **Envio em partes.** O tamanho do pedaço é 8 MB (configurável em `config/zapvault.php`, o que permite testar com pedaços pequenos). Cada pedaço é gravado na sua posição, em qualquer ordem; o `complete` confere o tamanho total e, para `.zip`, o início `PK`. Cancelar apaga também envios pela metade.
- **Dicas do Concluir.** "Aguarde o processamento dos arquivos", "Escolha o que fazer com “Nome”" e "Tudo pronto para concluir", como no protótipo, e "Nenhum arquivo pôde ser importado" quando todos falharam. Texto de apoio dos nomes: "Autor de N mensagens em M arquivos" ("nos M arquivos" quando aparece em todos), no singular quando for 1.
- **Senha, bloqueio e backup.** As rotas existem e respondem 501 até a tarefa 09.
- **Validação em português.** Criado `lang/pt_BR/validation.php` com as regras usadas; sem ele, os erros apareciam como chaves (`validation.string`).

## Tarefa 05: estrutura da interface e lista

- **Layout persistente.** `AppShell.vue` é o layout das páginas do Inertia; a lista, a busca e a rolagem continuam montadas ao trocar de página. Os dados vêm da API JSON (não de props do Inertia), e só o id da conversa e a mensagem de foco (`?m=`) vão como props.
- **Esc em ordem fixa.** Cada parte da tela registra seu tratamento com uma prioridade (`resources/js/lib/escape.ts`), e o Esc fecha sempre na ordem da especificação, independente da ordem em que as coisas foram abertas.
- **Tema.** O botão de tema vale na hora (localStorage) e também salva em `settings.theme` pela API.
- **Altura de linha.** O Tailwind define 1,5 como padrão; o protótipo usa a do navegador. Foi definida `line-height: normal` para as medidas baterem.
- **Foco visível.** O campo de busca ganha contorno verde ao receber foco, que o protótipo não tem; é exigência de acessibilidade.
- **Normalização compartilhada.** Os casos de teste de normalização ficam em `tests/Fixtures/normalization.json` e são usados pelo Pest e pelo Vitest.
- **Testes do frontend.** O Inertia é simulado nos testes do Vitest (`resources/js/test-setup.ts`), porque não roda fora do navegador.

## Tarefa 06: tela da conversa

- **Janela de mensagens.** A conversa carrega 100 mensagens por vez e mantém no máximo 300 na tela; ao passar disso, descarta as do lado oposto ao que está sendo carregado e volta a pedi-las ao servidor se a pessoa rolar de volta. A mensagem visível fica no mesmo lugar enquanto entram ou saem mensagens. Medido na conversa de 100 mil mensagens: abre em menos de 300 ms e cada lote de antigas carrega em cerca de 25 ms.
- **Dias no "Pular para data".** Como no protótipo, avisos e chamadas não contam na quantidade do dia, mas o dia aparece. Com mais de um mês, a lista mostra o mês e o ano como cabeçalho; ela abre já nas datas mais recentes.
- **Busca na conversa.** Começa na primeira ocorrência, em ordem cronológica, como no protótipo. Ocorrência fora da janela carregada é buscada com `around` antes de rolar.
- **Botão de ir ao fim.** O contador mostra as mensagens carregadas abaixo da tela; se ainda há mensagens mais novas fora da janela, aparece com "+".
- **Áudio adiantado.** O player de áudio (tocar, pausar, barra clicável e velocidades 1x, 1,5x e 2x) já funciona, com um único `<audio>` para o aplicativo inteiro. O ajuste fino fica para a tarefa 07.
- **Imagens, vídeos e documentos.** Até a tarefa 07 (visualizador e pré-visualização), clicar abre o arquivo em outra aba. O botão de baixar do documento já funciona.
- **Vídeo sem pôster.** Sem `ffmpeg` não há miniatura do vídeo; o balão mostra um fundo escuro com o nome do arquivo, o botão de reprodução e a duração, se conhecida.
- **Demonstração.** O aviso de criptografia, que no protótipo não tem horário, passa a usar o horário da mensagem seguinte em vez de 00:00.
