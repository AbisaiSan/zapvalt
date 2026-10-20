# ZapVault: especificação técnica e prompt para o Claude Code

Oct 20, 2026 · @Abisai

## Preparar o ambiente e iniciar

Você cria o projeto, liga a pasta ao repositório do GitHub, coloca este documento, o protótipo e as tarefas dentro dele, sobe tudo para o GitHub e só então abre o Claude Code na pasta. O restante o Claude Code faz seguindo as tarefas de `docs/tarefas`, na ordem. Os passos abaixo assumem um Mac; se for outro sistema, me avise e eu adapto.

1. **Instalar as ferramentas (uma vez).** Instale o Laravel Herd (herd.laravel.com), que traz o PHP e o instalador do Laravel. Instale também o Node.js 20 ou superior e o Git, se ainda não tiver. No Terminal, confira com `php -v` (8.3 ou superior), `composer -V`, `node -v` e `git --version`.
2. **Configurar o Git e o acesso ao GitHub (uma vez).** Informe seu nome e e-mail com `git config --global user.name "Seu Nome"` e `git config --global user.email "seu@email.com"`. Para o `git push` funcionar, entre na sua conta pelo GitHub CLI (`gh auth login`) ou use um token de acesso pessoal no lugar da senha.
3. **Criar o projeto.** Rode `laravel new zapvault`. Quando o instalador perguntar, escolha sem starter kit (o projeto não terá login de usuários), testes com Pest e banco SQLite, e responda sim para inicializar o repositório Git; se as perguntas forem diferentes na sua versão, escolha o equivalente. Depois entre na pasta com `cd zapvault`.
4. **Ligar ao repositório do GitHub.** Rode `git branch -M main` e `git remote add origin https://github.com/AbisaiSan/zapvalt.git`. Confira com `git remote -v`.
5. **Colocar a documentação.** Crie `docs/prototipo` e copie para ela os arquivos do .zip do protótipo (ZapVault.dc.html, zapvault-data.js, support.js e a pasta uploads). Exporte este documento em Markdown e salve como `docs/ESPECIFICACAO.md`. Descompacte `tarefas.zip` dentro de `docs`, para existir `docs/tarefas/README.md` com as tarefas 00 a 10. Crie o `CLAUDE.md` na raiz com o texto da última seção deste documento.
6. **Conferir o que vai subir e fazer o primeiro push.** Rode `git status` e confirme que não aparece `.env`, nenhum `.zip` de conversas nem pasta de dados. Depois `git add .`, `git commit -m "configuração inicial do projeto"` e `git push -u origin main`.
7. **Instalar e abrir o Claude Code.** Siga a [página oficial](https://docs.claude.com/en/docs/claude-code/overview), que traz o comando de instalação atual para Mac. Depois, dentro da pasta `zapvault`, rode `claude` e faça o login que ele pedir no primeiro uso.
8. **Colar o prompt.** Cole o texto da seção "Prompt para o Claude Code". Ele trabalha uma tarefa por vez, na ordem de `docs/tarefas`, e para ao fim de cada uma para você conferir. Ao fim de cada tarefa ele faz o commit; envie para o GitHub com `git push` (ou peça ao Claude Code para fazer o push).

**Como conduzir**

- Confira cada etapa no navegador antes de pedir a próxima, comparando com o protótipo.
- Se algo quebrar, copie a mensagem de erro inteira e cole no Claude Code.
- Peça um commit ao fim de cada etapa; assim você pode voltar atrás sem perder nada.
- O protótipo abre no navegador por um servidor local (por exemplo, `php -S 127.0.0.1:8081 -t docs/prototipo` e depois o endereço `http://127.0.0.1:8081/ZapVault.dc.html`); ele carrega o React da internet, então precisa de conexão só para essa consulta visual.

## Prompt para o Claude Code

Depois de preparar a pasta do projeto (seção "Preparar o ambiente e iniciar"), com `docs/ESPECIFICACAO.md`, `docs/prototipo/`, `docs/tarefas/` e `CLAUDE.md` no lugar e o primeiro push feito, abra o terminal na raiz do projeto, rode `claude` e cole o texto abaixo.

```text
Você vai construir o ZapVault, um aplicativo que roda só no meu computador para guardar e consultar, em modo somente leitura, conversas do WhatsApp Business exportadas em .txt e .zip. A interface deve ser em português do Brasil e parecida com o WhatsApp Web. O backend é Laravel/PHP.

Contexto: estou migrando o WhatsApp Business de um Android para um iPhone e não posso perder o histórico. Já exportei as conversas e elas estão em arquivos que preciso importar. Os formatos, extensões e tamanhos variam. Algumas conversas são muito grandes.

A configuração inicial já foi feita por mim: o projeto Laravel existe nesta pasta, está ligado ao repositório https://github.com/AbisaiSan/zapvalt.git (branch main) e já teve o primeiro push, com a documentação em docs/. Não recrie o projeto; continue a partir dele.

Antes de escrever qualquer código:
1. Leia CLAUDE.md por inteiro.
2. Leia docs/ESPECIFICACAO.md por inteiro. Ela define a stack, o banco, o importador, a busca, as telas, os endpoints e o roteiro de etapas. Siga o que está lá.
3. Leia docs/tarefas/README.md e depois o arquivo de cada tarefa. As tarefas (00 a 10) são as etapas do roteiro e é a partir delas que vamos trabalhar.
4. Abra docs/prototipo/ e estude o protótipo visual aprovado (ZapVault.dc.html e zapvault-data.js). Ele é a referência de layout, textos, cores, estados e interações. Você pode ver o protótipo rodando com: php -S 127.0.0.1:8081 -t docs/prototipo
5. Confira o ambiente (php -v, composer -V, node -v, npm -v, sqlite3 --version, e se o PHP tem a extensão SQLite com FTS5). Se faltar algo, me diga o que instalar e como.

Depois me responda, em até 15 linhas: o que você entendeu do produto, quais pontos da especificação têm alguma dúvida ou risco, e qualquer conflito que você tenha visto entre a especificação e o protótipo. Se não houver dúvida relevante, siga direto para a tarefa 00.

Como trabalhar:
- Execute as tarefas de docs/tarefas, uma por vez, na ordem, começando pela tarefa 00. Siga o procedimento descrito em docs/tarefas/README.md, incluindo atualizar o Status da tarefa no arquivo dela e na tabela do README.
- Ao fim de cada tarefa: rode os testes (composer test e npm test), faça commits pequenos em português, envie para o GitHub com git push, e me diga em poucas linhas o que ficou pronto e como eu confiro. Depois PARE e espere eu dizer para continuar. Não comece a próxima tarefa sozinho.
- Escreva os testes junto com o código.
- Nunca use conversas reais em código, testes ou exemplos. Todos os dados de teste e de demonstração são fictícios. Nunca coloque no Git arquivos de conversa exportada, a pasta de dados ou o .env.
- Nunca carregue uma conversa inteira no navegador. Paginação por cursor e busca no servidor, como na especificação.
- O aplicativo não envia mensagens, não usa serviços externos e escuta somente em 127.0.0.1.
- Se algo da especificação não funcionar na prática ou parecer errado, explique e proponha uma alternativa antes de mudar. Decisões pequenas você mesmo registra em docs/DECISOES.md.
- Não faça nada destrutivo com a pasta de dados, com o banco real nem com o histórico do Git (sem push --force).
- Use vírgulas em vez de travessões nos textos que escrever para mim ou para a interface.

Comece agora pela leitura dos arquivos e pelo resumo, e depois a tarefa 00.
```

Ao fim de cada etapa, responda algo curto como "ok, pode seguir para a etapa 3". Se algo não ficou como no protótipo, descreva o que viu e o que esperava, de preferência com uma captura de tela, e o Claude Code corrige antes de avançar.

## Protótipo e decisões de stack

O protótipo define o visual e o comportamento; o código de produção usa Laravel com SQLite e Inertia com Vue, e nunca carrega o acervo inteiro no navegador.

**O que o protótipo enviado contém**

- `ZapVault.dc.html`: todas as telas e a lógica (escrita em React, num formato próprio do Claude Design). É a fonte da verdade para aparência, textos em português e comportamento.
- `zapvault-data.js`: 12 conversas fictícias e as funções de busca e formatação (`norm`, `ranges`, `segs`, `sepLabel`, `listDate`, `initials`, `dur`).
- `support.js`: motor interno do protótipo. Deve ser ignorado e não entra no produto.
- Tudo roda na memória do navegador: todas as mensagens ficam carregadas e a busca varre tudo. No produto isso muda, porque um acervo real pode ter centenas de milhares de mensagens.

**O que reaproveitar do protótipo**

- Os tokens de cor dos temas claro e escuro (bloco `<style>` no início do HTML), as medidas (lista com itens de 72 px, avatar de 48 px, cabeçalho de 60 px) e todos os textos.
- As regras de busca, de resumo da lista, de rótulos de data e de importação descritas nas próximas seções.

**Decisões técnicas**

| Tema | Decisão | Motivo |
| --- | --- | --- |
| Backend | Laravel na versão estável atual, PHP 8.3 ou superior | Definido por você |
| Banco de dados | SQLite em modo WAL, com FTS5 (tokenizador de trigramas) para a busca | Um usuário só, tudo local; o backup é copiar uma pasta; a busca por trecho de palavra sai pronta |
| Filas | Fila `database` em um arquivo SQLite separado, com o worker iniciado junto com o servidor | Importações longas não prendem a tela nem disputam gravação com a leitura |
| Frontend | Inertia 2 com Vue 3 (Composition API, TypeScript) e Tailwind, sem starter kit de autenticação | Rotas com link direto para cada conversa, navegação sem recarregar e componentes pequenos |
| Acesso a dados | Paginação por cursor e busca no servidor, via endpoints JSON | O navegador nunca recebe o acervo inteiro |
| Mídia | Arquivos em pasta local, nomeados pelo hash SHA-256, servidos com suporte a Range | Áudio e vídeo com avanço e nenhuma duplicata ao reimportar |
| Testes | Pest no backend e Vitest nos utilitários do frontend | Importador e busca são o coração e precisam de testes |
| Execução | Um único comando sobe o servidor em 127.0.0.1 e o worker, sem Docker | Simples para quem não é da área de infraestrutura |
| Pasta de dados | Fora do projeto, configurada no `.env` (por exemplo, `~/ZapVault-dados`) | Atualizar o código sem risco para o acervo; backup simples |

## Telas, rotas e componentes

O protótipo é uma única tela com um painel direito que muda de modo; no código, cada modo vira uma rota, e a lista da esquerda permanece montada entre elas.

**Rotas (Inertia, com layout persistente `AppShell.vue`)**

| Rota | Página | O que mostra |
| --- | --- | --- |
| `/` | `Home.vue` | Painel direito vazio: "Selecione uma conversa" e o resumo do acervo (conversas, mensagens, período) |
| `/c/{conversa}` com `?m={mensagem}` opcional | `Chat.vue` | A conversa; com `m`, abre rolada até a mensagem e a destaca por cerca de 2 segundos |
| `/importar` | `Import.vue` | Importação de arquivos |
| `/configuracoes` | `Settings.vue` | Nomes, tema, armazenamento, backup, senha |
| `/desbloquear` | `Unlock.vue` | Tela de senha (nova, fora do `AppShell`) |

**Do protótipo para componentes Vue**

| Elemento do protótipo | Componente |
| --- | --- |
| Cabeçalho da lista (ícone, nome, Importar, tema, configurações) | `SidebarHeader.vue` |
| Campo de busca com atalho, botão de limpar e aviso de 2 letras | `SearchField.vue` |
| Item da lista, avatar com iniciais (ícone de pessoa para nomes que começam com +) | `ConversationItem.vue`, `Avatar.vue` |
| Esqueletos, acervo vazio, abrindo acervo | `ListSkeleton.vue`, `EmptyArchive.vue` |
| Resultados com abas Conversas e Mensagens e contadores | `SearchResults.vue`, `ConversationResult.vue`, `MessageResult.vue` |
| Cabeçalho da conversa (voltar em tela estreita, avatar, nome, subtítulo, data, lupa, menu) | `ChatHeader.vue` |
| Barra de busca na conversa com contador "3 de 12" | `InChatSearch.vue` |
| Seletor "Pular para data" | `DateJumpPopover.vue` |
| Área de mensagens, separador de dia, aviso de sistema | `MessageList.vue`, `DaySeparator.vue`, `SystemNotice.vue` |
| Balão e seus conteúdos (texto, imagem, vídeo, áudio, documento, figurinha, mídia faltante, apagada) | `MessageBubble.vue` e `bodies/*.vue`; player de áudio em `useAudioPlayer.ts` |
| Botão "ir ao fim" com contador de novas abaixo | `JumpToBottom.vue` |
| Faixa "Arquivo somente leitura" com o nome do arquivo de origem | `ReadOnlyFooter.vue` |
| Visualizador de imagem e vídeo | `MediaViewer.vue` (Teleport) |
| Pré-visualização de documento | `DocPreview.vue` |
| Aviso temporário | `ToastHost.vue`, `useToast.ts` |
| Importar: área de soltar, cartão de arquivo, pergunta do nome | `DropZone.vue`, `ImportFileCard.vue`, `NameQuestion.vue` |
| Configurações: nomes, tema, pasta, backup, senha | `settings/NamesSection.vue`, `ThemeSection.vue`, `StorageSection.vue`, `BackupSection.vue`, `PasswordSection.vue` |

Também existem os atalhos: Ctrl+K (Cmd+K no Mac) foca a busca, e Esc fecha, nesta ordem, o visualizador, a pré-visualização, o seletor de data, a busca na conversa, a busca da lista e, por fim, as páginas Importar e Configurações. Em telas com menos de 760 px de largura, aparece uma coluna por vez, com botão de voltar.

**Adições e ajustes obrigatórios sobre o protótipo**

1. **Tela de desbloqueio**, que o protótipo não mostra: mesma identidade visual, campo de senha e botão "Abrir", com atraso crescente após tentativas erradas.
2. **Menu ⋯ no cabeçalho da conversa** com "Renomear conversa" e "Excluir conversa". A exclusão pede confirmação e explica que apaga só do ZapVault, não dos arquivos originais.
3. **Progresso real na importação**, incluindo o caso em que o arquivo inteiro não é reconhecido ("Não foi possível reconhecer este arquivo como conversa"). O estado "Erro" do protótipo, com linhas não reconhecidas, continua valendo para importações parciais.
4. **Senha com no mínimo 6 caracteres** (o protótipo diz 4), com a explicação de que ela protege a abertura do aplicativo e não criptografa os arquivos.
5. **Pasta de armazenamento:** o botão "Alterar…" abre uma explicação de como mudar (parar o aplicativo, mover a pasta, editar o `.env`), sem mover dados automaticamente.
6. **Pular para data:** agrupar os dias por ano e mês, com rolagem, para conversas longas.
7. **Links nas mensagens** clicáveis, abrindo em nova aba com `rel="noopener noreferrer"`.
8. **Dados de demonstração:** um comando que cria no banco as 12 conversas fictícias do protótipo, com mídias de mentira, para desenvolver e comparar lado a lado.
9. **Não implementar** a opção `estado` do protótipo (acervo vazio, mídia com erro etc.): ela só existe para mostrar variações visuais. Os estados em si (vazio, carregando, erro) devem existir.
10. **Data de hoje:** o protótipo fixa o dia 20/10/2026; o produto usa a data real nos rótulos "Hoje" e "Ontem".

## Banco de dados

O banco é SQLite com a mensagem no centro: cada mensagem pertence a uma conversa, tem um remetente, no máximo um anexo e uma entrada no índice de busca.

**Configuração do SQLite**

- Duas conexões: `sqlite` (acervo, em `{DATA}/db/zapvault.sqlite`) e `queue` (filas, em `{DATA}/db/queue.sqlite`). Sessões e cache usam o driver `file`, para que gravações de rotina não disputem o arquivo do acervo durante uma importação grande.
- Em cada conexão: `journal_mode=WAL`, `synchronous=NORMAL`, `busy_timeout=10000` e `foreign_keys=ON`.
- Datas e horas das mensagens são gravadas como horário local "ingênuo" (sem fuso), exatamente como vêm no arquivo exportado. Defina `APP_TIMEZONE=America/Sao_Paulo` e nunca converta fuso nessas colunas.

**Tabelas do acervo**

| Tabela | Colunas principais | Observações |
| --- | --- | --- |
| `settings` | `key` (PK), `value` (JSON), `updated_at` | Chaves: `user_names` (lista), `theme`, `password_hash`, `last_backup`, `keep_originals` |
| `import_batches` | `id`, `status` (open, committed, cancelled), `created_at`, `committed_at` | Um lote é o conjunto de arquivos da tela Importar até clicar em Concluir ou Cancelar |
| `import_files` | `id`, `batch_id`, `original_name`, `stored_path`, `ext`, `size_bytes`, `sha256`, `status` (uploading, waiting, processing, done, partial, failed), `percent`, `conversation_id` (rascunho), `existing_conversation_id`, `choice` (merge, separate), `stats` (JSON: novas, repetidas, anexos encontrados, anexos faltantes, linhas lidas), `unrecognized_lines` (JSON, até 200), `unrecognized_count`, `detected_format`, `error_message`, `started_at`, `finished_at` | Estados done, partial (linhas não reconhecidas, mostrado como "Erro" no protótipo) e failed (arquivo não reconhecido) |
| `conversations` | `id`, `name`, `name_normalized`, `is_group`, `phone`, `avatar_index` (0 a 7), `status` (draft, active), `source_file_name`, `first_message_at`, `last_message_at`, `message_count` (sem avisos de sistema), `last_message_id` | Rascunhos (`draft`) nunca aparecem em listas nem na busca. `source_file_name` aparece no rodapé da conversa |
| `participants` | `id`, `conversation_id`, `name`, `name_normalized`, `is_me`, `message_count` | Único por (`conversation_id`, `name_normalized`). `is_me` é recalculado quando os nomes em Configurações mudam |
| `messages` | `id`, `conversation_id`, `participant_id` (nulo em avisos), `sent_at`, `kind` (text, image, video, audio, doc, sticker, system, call, deleted, missing), `body`, `caption`, `system_icon`, `search_text`, `dedupe_key`, `source_line`, `import_file_id` | `search_text` guarda o texto normalizado a pesquisar; só é preenchido para text (corpo), image e video (legenda) e doc (nome do arquivo). Avisos, apagadas, áudios, figurinhas e mídia faltante não entram na busca, como no protótipo |
| `attachments` | `id`, `message_id` (único), `original_name`, `ext`, `mime`, `size_bytes`, `sha256`, `storage_path`, `thumb_path`, `width`, `height`, `duration_seconds`, `pages` | `duration_seconds` e `pages` são opcionais (preenchidos se `ffprobe` e `pdfinfo` existirem). Vários anexos podem apontar para o mesmo arquivo em disco |
| `backups` | `id`, `status`, `percent`, `file_path`, `size_bytes`, `started_at`, `finished_at`, `error` | Alimenta a seção Backup |
| `messages_fts` | tabela virtual FTS5 sobre `messages.search_text` | `content='messages'`, `content_rowid='id'`, `tokenize='trigram'`, mantida por gatilhos |

A conexão `queue` guarda apenas `jobs`, `job_batches` e `failed_jobs`.

**Índices e regras**

- `messages`: índice em (`conversation_id`, `sent_at`, `id`) para a rolagem; índice único em (`conversation_id`, `dedupe_key`) contra duplicatas; índice parcial em (`conversation_id`, `sent_at`) onde `kind` é image ou video, para o visualizador; índice em `import_file_id`.
- `conversations`: índice em (`status`, `last_message_at`) e em `name_normalized`.
- A tabela `messages_fts` e seus três gatilhos (inserir, apagar, atualizar `search_text`) são criados na migration com `DB::unprepared`, porque o construtor de schema do Laravel não cobre tabelas virtuais.
- Ao apagar uma conversa, apagam-se mensagens, participantes e anexos (cascata) e, depois, os arquivos de mídia que nenhum outro anexo usa.
- Ordem das mensagens em toda a aplicação: (`sent_at`, `id`). Esse par também é o cursor da paginação.
- Ordem de grandeza: 1 milhão de mensagens ocupa cerca de 1 a 2 GB entre tabela e índice de busca, fora a mídia.

## Importador de conversas

A importação acontece em duas fases, como a tela do protótipo sugere: primeiro os arquivos são analisados e gravados como rascunho, depois você decide e clica em Concluir; Cancelar desfaz tudo.

**Fluxo**

1. **Envio.** O navegador envia cada arquivo em pedaços de 8 MB (`POST /api/imports/uploads`, `PUT` de cada pedaço e `POST .../complete`), o que funciona para arquivos de vários GB sem mexer em limites do PHP. Um comando de terminal, `php artisan zapvault:import {arquivos}`, faz o mesmo sem navegador.
2. **Análise (job em fila, um arquivo por vez, na ordem de envio).** O job lê o arquivo, cria uma conversa em rascunho (`status=draft`), grava mensagens, participantes e anexos, e calcula as quatro contagens do cartão: mensagens novas, repetidas, anexos encontrados e anexos faltantes. Atualiza `percent` durante o processo; a tela consulta `GET /api/imports` a cada cerca de 700 ms.
3. **Decisões.** Se já existe conversa ativa com o mesmo nome (`name_normalized`), o cartão pede "Somar à existente" ou "Manter separada". Terminada a análise de todos os arquivos, a pergunta "Este é o seu nome nas mensagens?" aparece quando necessária.
4. **Concluir.** O botão só habilita com todos os arquivos analisados e nenhuma escolha pendente, com as mesmas dicas de texto do protótipo. Em uma transação por arquivo: ao somar, as mensagens do rascunho passam para a conversa existente (`UPDATE OR IGNORE`) e as que sobrarem, por já existirem, são apagadas; ao manter separada, o rascunho vira ativo (com o nome "Nome (2)" se houver colisão). Depois recalculam-se `first_message_at`, `last_message_at`, `message_count` e `last_message_id`.
5. **Cancelar.** Apaga os rascunhos (mensagens, participantes, anexos), os arquivos de mídia que nenhum outro anexo usa e os originais do lote.

**Reconhecimento do arquivo**

- Aceita `.txt` e `.zip`. Um `.zip` pode conter o texto da conversa e a mídia, ou vários `.zip` de conversas (o Drive costuma empacotar assim); pacotes aninhados são abertos até 2 níveis e cada conversa vira um cartão.
- O texto é uma conversa quando pelo menos 5 das primeiras 300 linhas (ou todas, se houver menos) casam com um dos formatos abaixo. Caso contrário, o arquivo fica com status `failed`.

**Formatos de linha (ordem de tentativa)**

| Formato | Exemplo |
| --- | --- |
| Android, 24 horas | `19/10/2026 09:14 - Maria Souza: Bom dia!` |
| Android, com vírgula e AM ou PM | `15/10/26, 7:50 PM - Eduardo Nunes: Confirmado` |
| iPhone, entre colchetes, com segundos | `[19/10/2026 09:14:05] Maria Souza: Bom dia!` |

- Ano de 2 dígitos vira 20aa. A ordem dia/mês ou mês/dia é decidida por arquivo: se algum primeiro número passa de 12, é dia primeiro; se algum segundo número passa de 12, é mês primeiro; sem evidência, dia primeiro (padrão do Brasil).
- Depois do prefixo de data e hora, o texto até o primeiro ` :  ` é o remetente e o resto é o corpo; sem ` :  `, é um aviso de sistema (sem remetente).
- Antes de analisar cada linha, remover caracteres invisíveis (U+200E, U+200F, U+202A a U+202E, U+FEFF) e trocar espaços especiais (U+202F, U+00A0) por espaço comum. Aceitar UTF-8 com ou sem BOM.
- Linha sem prefixo de data é continuação: entra na mensagem anterior, separada por quebra de linha. Linha que começa como data mas está malformada (por exemplo, `13/10/2026 17:3 - ...`) vai para a lista de linhas não reconhecidas, com número da linha e texto, e não é anexada a nenhuma mensagem. Guardar no máximo 200 linhas e o total em `unrecognized_count`.

**Classificação do corpo**

| Se o corpo for | Tipo gravado |
| --- | --- |
| `<Mídia oculta>`, `<Arquivo de mídia oculto>`, `<Media omitted>` | `missing` |
| `NOME.ext (arquivo anexado)` ou `<anexado: NOME.ext>` (e versões em inglês) | pelo tipo do arquivo: `image`, `video`, `audio`, `sticker` ou `doc`; as linhas seguintes viram a legenda |
| `Essa mensagem foi apagada`, `Você apagou esta mensagem` | `deleted` |
| Sem remetente e sobre criptografia | `system` com `system_icon=lock` |
| Sem remetente e começando por "Chamada de voz" ou "Chamada de vídeo" | `call` |
| Sem remetente, qualquer outro | `system` |
| Qualquer outro com remetente | `text` |

Tipo do arquivo anexado: imagens (jpg, jpeg, png, gif, webp), vídeos (mp4, mov, 3gp, mkv, webm), áudios (opus, ogg, m4a, mp3, aac, amr, wav) e figurinhas (nome começando por `STK-`, ou webp de figurinha). Todo o resto é `doc`. Se o arquivo citado não está no pacote, a mensagem vira `missing`, o nome fica em `body` e conta como anexo faltante.

**Duplicatas e reimportação**

- `dedupe_key` = hash de (data e hora até o minuto, remetente normalizado, tipo, texto, legenda ou nome do arquivo normalizado) mais `:n`, onde `n` conta, a partir de 0, as mensagens idênticas dentro do mesmo minuto no arquivo. Assim, duas mensagens "ok" no mesmo minuto continuam duas, e reimportar o mesmo arquivo produz as mesmas chaves e zero mensagens novas.
- A gravação usa `INSERT OR IGNORE` em lotes de 1.000 dentro de transações; o que o banco ignorar conta como repetida.

**Conversa, participantes e nome do usuário**

- Nome da conversa: tirado do nome do arquivo ("Conversa do WhatsApp com ...", "Conversa com ...", "WhatsApp Chat with ...", "WhatsApp Chat - ..."), sem a extensão; se nada casar, o nome do arquivo. Se o nome parecer um telefone, preencher também `phone`.
- `is_group`: três ou mais remetentes diferentes, ou aviso de sistema do tipo "criou o grupo" ou "adicionou".
- `is_me`: o remetente coincide (comparando o nome normalizado) com algum nome em `settings.user_names`.
- Pergunta do nome: calcular, entre todos os arquivos do lote, os remetentes ordenados por número de arquivos em que aparecem e depois por número de mensagens. Pular a pergunta se algum nome já configurado aparece como autor. O primeiro candidato leva a etiqueta "Sugerido" apenas se aparecer em pelo menos 2 arquivos e em mais da metade deles e superar o segundo em número de arquivos; o texto de apoio é "Autor de N mensagens em M arquivos". A última opção é "Nenhum destes". Escolher um nome o adiciona a `user_names` e recalcula `is_me` em todas as conversas.

**Mídia dentro do pacote**

- Extrair só as entradas citadas no texto, em fluxo, calculando o SHA-256 enquanto grava; o destino é `{DATA}/media/ab/cd/{sha256}.{ext}` e, se já existir, não regrava. Entradas não citadas são ignoradas.
- Segurança do zip: recusar entradas com `..` ou caminho absoluto, limitar o total descompactado (configurável) e o número de entradas, e recusar razões de compressão suspeitas.
- Imagens: ler largura e altura (`getimagesize`) e gerar miniatura de até 640 px. Vídeos: pôster com `ffmpeg` se existir; sem ele, usar o ícone de reprodução do protótipo. Áudio e PDF: duração e páginas só se `ffprobe` e `pdfinfo` existirem.
- Guardar os originais (o texto sempre; o `.zip` conforme `keep_originals`, padrão ligado) em `{DATA}/originals/{lote}/`.

**Normalização para busca** (`search_text`): para cada caractere, decompor (NFD), remover marcas combinantes (U+0300 a U+036F) e passar para minúsculas. Exige a extensão `intl` do PHP; sem ela, usar uma tabela de substituição para letras acentuadas do português. O frontend usa uma versão em TypeScript da função `norm` do protótipo; os dois lados devem passar nos mesmos casos de teste.

**Comandos de terminal**

- `zapvault:import {arquivos} {--commit}`: importa sem navegador.
- `zapvault:demo`: cria as 12 conversas fictícias do protótipo.
- `zapvault:reindex`: reconstrói o índice de busca.
- `zapvault:verify`: confere contagens e acha anexos ausentes no disco.
- `zapvault:backup` e `zapvault:restore {arquivo}`.

## Busca

A busca roda no servidor, ignora maiúsculas, minúsculas e acentos, e aceita trecho de palavra e frase; o destaque dos termos é desenhado no navegador, com a mesma função do protótipo.

**Regras de comportamento (todas do protótipo)**

- Começa com 2 ou mais caracteres, depois de remover espaços das pontas. Com 1 caractere, mostra "Digite pelo menos 2 letras para buscar".
- Campos pesquisados: texto das mensagens de texto, legenda de fotos e vídeos e nome de arquivo de documentos. Avisos de sistema, mensagens apagadas, áudios, figurinhas e mídia faltante ficam de fora.
- Duas abas, **Conversas** (pelo nome) e **Mensagens** (pelo conteúdo), cada uma com seu contador. A aba inicial é Conversas se houver conversa com o termo no nome, senão Mensagens; depois que a pessoa clica numa aba, a escolha fica fixa até a busca ser limpa.
- Mensagens em ordem da mais recente para a mais antiga. O resumo é "N mensagens em M conversas" (singular quando for 1).
- Cada resultado de mensagem mostra o nome da conversa, a data (dd/mm/aaaa), um prefixo ("Você: " ou o primeiro nome do remetente em grupos, mais "Documento: ", "Foto: " ou "Vídeo: " quando o trecho vier de documento ou legenda) e o trecho com o termo destacado. Quebras de linha viram espaços. Se a primeira ocorrência passa do 38º caractere, o trecho começa depois do último espaço antes da posição 24 caracteres atrás, com "…" na frente.
- Clicar em um resultado abre `/c/{conversa}?m={mensagem}`, e o resultado clicado fica com fundo de seleção enquanto a conversa estiver aberta.
- Textos de resultado vazio, iguais aos do protótipo: "Nenhum resultado para “termo”" com a dica "Confira a grafia ou tente outra palavra. A busca ignora maiúsculas e acentos."; na aba Conversas, "Nenhuma conversa com “termo” no nome" com a indicação para ver a aba Mensagens; na aba Mensagens, "Nenhuma mensagem com “termo”" com a indicação para ver a aba Conversas.

**Implementação**

- Normalizar o termo digitado com a mesma regra de `search_text` e limitá-lo a 200 caracteres.
- Mensagens com 3 ou mais caracteres: `messages_fts MATCH` com o termo entre aspas (aspas internas dobradas), que o tokenizador de trigramas trata como busca de trecho e, com espaços, de frase. Com exatamente 2 caracteres, o FTS não casa; usar `search_text LIKE '%xx%'` com escape de `%` e `_`.
- A consulta junta `messages`, `conversations` (só `status='active'`) e ordena por `sent_at DESC, id DESC`, com paginação por cursor de 50 resultados e rolagem infinita na lista.
- Contadores calculados com um teto de 10.000 (mostrar "10.000+" quando atingir) para que termos comuns não travem a tela.
- Conversas: `name_normalized LIKE '%termo%'` entre as ativas, da mais recente para a mais antiga.
- No navegador: espera de 250 ms depois da última tecla, cancelamento da requisição anterior (`AbortController`) e esqueletos de carregamento enquanto aguarda, como no protótipo.

**Busca dentro da conversa**

- `GET /api/conversations/{id}/matches?q=` devolve, em ordem cronológica, as mensagens que casam e quantas ocorrências cada uma tem (contagem sem sobreposição do termo normalizado, somando os campos pesquisados).
- O contador "3 de 12" conta ocorrências, não mensagens. Enter vai para a próxima, Shift+Enter para a anterior, com volta ao início ao chegar no fim. Sem resultados, mostra "Sem ocorrências" na cor de erro.
- Para ir a uma ocorrência fora da janela carregada, o frontend pede `messages?around={id}` e depois rola até ela. A ocorrência atual usa a cor de destaque forte (`--hl-cur`); as demais, `--hl`.

**Destaque no navegador**

- Portar `norm`, `ranges` e `segs` de `zapvault-data.js` para `resources/js/lib/search.ts`, mantendo o comportamento (a normalização preserva o tamanho do texto para que as posições coincidam com o original). O texto é desenhado em `<span>`, nunca com `v-html`.
- Criar testes (Vitest no frontend, Pest no backend) com os mesmos exemplos: PROPOSTA, proposta e Proposta; ação e acao; ç, ñ e ü; emojis; aspas curvas; termo no início, meio e fim do texto; várias ocorrências.

## Mídia e arquivos

Todos os arquivos ficam em uma única pasta de dados fora do projeto, e cada arquivo de mídia é guardado uma só vez, nomeado pelo seu hash.

```text
{DATA}/                      (ex.: ~/ZapVault-dados, definido em ZAPVAULT_DATA_PATH no .env)
  db/zapvault.sqlite         acervo
  db/queue.sqlite            filas
  media/ab/cd/<sha256>.<ext> arquivos de mídia
  thumbs/ab/cd/<sha256>.jpg  miniaturas
  originals/<lote>/          arquivos importados
  backups/                   backups gerados
  uploads-tmp/               pedaços de envio em andamento
```

**Entrega dos arquivos**

- `GET /media/{anexo}` e `GET /media/{anexo}/thumb`, protegidos pela mesma sessão do aplicativo. O caminho vem sempre do banco, nunca de parâmetros da URL.
- Resposta em fluxo, com suporte a `Range` (resposta 206), indispensável para avançar áudio e vídeo. A `BinaryFileResponse` do Laravel já faz isso.
- Cabeçalhos: `Content-Type` a partir de uma lista permitida de extensões, `ETag` com o hash, `Cache-Control: private, max-age=31536000, immutable` e `X-Content-Type-Options: nosniff`.
- Só imagens, vídeos, áudios e PDF abrem no navegador (`inline`). Qualquer outro tipo, e em especial HTML, SVG e JavaScript, é sempre entregue como download, para que um arquivo recebido por mensagem nunca execute código no aplicativo. `?download=1` força o download com o nome original.

**Comportamento na conversa (do protótipo)**

- **Imagem e vídeo:** miniatura em `loading="lazy"`, com largura máxima por proporção (4/3 com 300 px, 3/4 com 210 px, 16/9 com 330 px, 1/1 com 260 px), usando a proporção real de `width` e `height` para reservar o espaço e evitar saltos na rolagem. Enquanto carrega, mostra o esqueleto; se falhar, "Não foi possível abrir a imagem" (ou "o vídeo") com o botão "Tentar de novo", que recarrega o arquivo. Vídeo mostra o botão de reprodução e a duração sobre a miniatura quando conhecida.
- **Visualizador:** `GET /api/conversations/{id}/media` devolve a lista ordenada de imagens e vídeos da conversa (mensagem, anexo, remetente, data e hora, legenda, nome do arquivo, tipo, dimensões), para que as setas e o contador "3 de 12" funcionem mesmo com mensagens fora da janela carregada. Os vídeos tocam com `<video controls>`; Esc fecha e as setas do teclado trocam de item.
- **Áudio:** o desenho do protótipo (botão de tocar e pausar, barra de progresso clicável, tempo "posição / duração" e velocidade 1x, 1,5x, 2x em ciclo) comanda um elemento `<audio preload="metadata">` real. Só um áudio toca por vez; a duração vem do banco ou, se ausente, dos metadados do arquivo.
- **Documento:** cartão com selo da extensão e a linha "3 páginas · 240 KB · PDF" (páginas só quando conhecidas). Cores do selo: PDF `#D64545`, XLSX `#1E8A4C`, ZIP `#6B7780`, CSV `#127C8C`; qualquer outra, `#6B7780`. Clicar abre a pré-visualização: para PDF, o PDF real em um `<iframe>` (o visualizador do navegador), no lugar das páginas fictícias do protótipo; para os demais, "Pré-visualização indisponível" e o botão "Baixar arquivo". Cabeçalho, "enviado por" e botão Baixar seguem o protótipo.
- **Figurinha:** imagem sem balão. **Mídia faltante:** o aviso "Mídia não incluída na exportação".

**Configurações, armazenamento**

- O resumo do protótipo ("N mensagens e M anexos · X GB usados") vem de `GET /api/archive/summary`, com tamanho somando banco, mídia, miniaturas e originais; pode ser calculado e guardado em cache por alguns minutos.
- `ffmpeg`, `ffprobe` e `pdfinfo` são opcionais. O aplicativo detecta se existem e, se faltarem, apenas deixa de mostrar duração de áudio e vídeo, pôster de vídeo e contagem de páginas. O README deve explicar como instalá-los (no Mac, `brew install ffmpeg poppler`).

## Endpoints e contratos

O frontend conversa com o backend por 28 endpoints JSON, todos atrás da sessão do aplicativo e do bloqueio por senha quando ativado; o navegador só recebe janelas de dados, nunca o acervo inteiro.

| Endpoint | Parâmetros | Resposta |
| --- | --- | --- |
| `GET /api/archive/summary` |  | Conversas, mensagens (sem avisos), primeira e última data, anexos, bytes usados |
| `GET /api/conversations` | `cursor`, `limit` (100) | Itens da lista e `next_cursor` |
| `GET /api/conversations/{id}` |  | Cabeçalho: nome, grupo, avatar, primeira e última data, quantidade de mensagens e participantes, `source_file_name`, participantes |
| `GET /api/conversations/{id}/messages` | `limit` (100), `before`, `after`, `around`, `day` | Janela de mensagens, `has_older`, `has_newer`, cursores |
| `GET /api/conversations/{id}/days` |  | Lista de dias com a quantidade de mensagens de cada um |
| `GET /api/conversations/{id}/matches` | `q` | Mensagens que casam e ocorrências de cada uma |
| `GET /api/conversations/{id}/media` |  | Imagens e vídeos da conversa em ordem, para o visualizador |
| `PATCH /api/conversations/{id}` | `name` | Item atualizado |
| `DELETE /api/conversations/{id}` |  | 204 |
| `GET /api/search/conversations` | `q` | Itens da lista e total |
| `GET /api/search/messages` | `q`, `cursor`, `limit` (50) | Resultados, total, conversas com resultados, `next_cursor` |
| `POST /api/imports/uploads` | `name`, `size` | `upload_id`, `chunk_size` |
| `PUT /api/imports/uploads/{upload}` | `index`, corpo binário | 204 |
| `POST /api/imports/uploads/{upload}/complete` |  | Arquivo de importação criado e enfileirado |
| `GET /api/imports` |  | Lote atual: arquivos com status, porcentagem, contagens, linhas não reconhecidas; candidatos a nome; `can_commit` e a dica de texto |
| `PATCH /api/imports/files/{id}` | `choice` (merge ou separate) | Arquivo atualizado |
| `POST /api/imports/commit` | `name_choice` (texto ou nulo) | `{ new, updated }` |
| `POST /api/imports/cancel` |  | 204 |
| `GET /api/settings`, `PUT /api/settings` | `user_names`, `theme` | Nomes, tema, pasta de dados, se há senha, último backup |
| `PUT /api/settings/password` | `password`, `password_confirmation` | 204 (mínimo de 6 caracteres) |
| `DELETE /api/settings/password` | `password` | 204 |
| `POST /api/unlock`, `POST /api/lock` | `password` | 204, ou 422 com `retry_after` |
| `POST /api/backups`, `GET /api/backups/{id}` |  | Estado, porcentagem, nome e tamanho do arquivo |
| `GET /media/{anexo}`, `GET /media/{anexo}/thumb` | `download` | Arquivo em fluxo, com Range |

**Cursor.** Texto opaco (base64 de `sent_at|id`). `before` traz mensagens mais antigas que o cursor; `after`, mais novas; `around={id da mensagem}` traz cerca de 50 antes e 50 depois, incluindo a própria; `day=AAAA-MM-DD` traz uma janela que começa na primeira mensagem do dia. Sem parâmetros, devolve as 100 mais recentes. A resposta de `around` e `day` inclui `focus_id` para o frontend rolar até a mensagem.

**Resultado de busca de mensagem.** Cada item traz `message_id`, `conversation_id`, `conversation_name`, `sent_at`, `who` ("Você", o primeiro nome do remetente em grupos, ou nulo), `field` (`text`, `caption` ou `file`), `kind` e `text`, que é o conteúdo do campo pesquisado (até 4.000 caracteres). O navegador calcula o trecho e o destaque a partir desse texto.

**Item da lista de conversas**

```json
{
  "id": 7,
  "name": "Maria Souza",
  "is_group": false,
  "is_phone": false,
  "avatar_index": 0,
  "preview": "Segue o PDF com os valores atualizados.",
  "last_message_at": "2026-10-20T09:21:00"
}
```

O `preview` é montado no servidor com as regras do protótipo: resumo da última mensagem que não seja aviso (texto sem quebras de linha; "Foto" ou "Foto: legenda"; "Vídeo" ou "Vídeo: legenda"; "Áudio (0:42)"; nome do documento; "Figurinha"; "Mídia não incluída"; "Mensagem apagada"), e, em grupos, o prefixo "Você: " ou o primeiro nome do remetente. O rótulo de data da lista (hora se for hoje, "Ontem", dia da semana até 5 dias, senão dd/mm/aaaa) é calculado no navegador a partir de `last_message_at`.

**Mensagem**

```json
{
  "id": 1042,
  "sent_at": "2026-10-19T09:30:00",
  "kind": "image",
  "sender": { "participant_id": 12, "name": "Maria Souza", "is_me": false, "color_slot": 3 },
  "text": null,
  "caption": "Esse é o espaço",
  "system_icon": null,
  "attachment": {
    "id": 88, "name": "IMG-20261019-WA0007.jpg", "ext": "jpg",
    "size_bytes": 184233, "width": 1600, "height": 1200,
    "duration": null, "pages": null,
    "url": "/media/88", "thumb_url": "/media/88/thumb"
  }
}
```

`sender` é nulo em avisos e chamadas. `color_slot` segue o protótipo: `((posição do remetente entre os participantes que não são você) * 3 + avatar_index) % 8`, usado como `var(--s0)` a `var(--s7)`. Horários vêm como no arquivo, sem fuso; o navegador só formata.

## Segurança e execução local

O aplicativo só responde no próprio computador, não fala com a internet e trata cada arquivo recebido por mensagem como potencialmente hostil.

**Execução**

- Servidor em `127.0.0.1` (nunca `0.0.0.0`), com `APP_ENV=production` e `APP_DEBUG=false` no uso diário; `local` apenas para desenvolver.
- Um comando de uso diário, `composer run zapvault`, que sobe `php artisan serve --host=127.0.0.1 --port=8000` e o worker da fila (`php artisan queue:work`), usando os arquivos já compilados com `npm run build`. Para desenvolver, `composer run dev`.
- Pasta de dados com permissão restrita ao usuário (`chmod 700`).
- Antes de rodar migrations em dados reais, o script de atualização copia o arquivo do banco (`VACUUM INTO`). Nunca usar `migrate:fresh` fora do ambiente de desenvolvimento.

**Senha opcional**

- Guardar apenas o hash (`Hash::make`) em `settings.password_hash`. Um middleware exige a sessão desbloqueada em todas as rotas, exceto `/desbloquear`, `POST /api/unlock` e os arquivos estáticos.
- Depois de 5 senhas erradas, atraso crescente entre tentativas (até 60 segundos), guardado no cache de arquivo. A sessão expira ao fechar o navegador e em no máximo 12 horas.
- Em Configurações, ao lado de "Senha ativada", incluir "Bloquear agora".
- A senha protege a abertura do aplicativo; ela não criptografa o banco nem os arquivos. A proteção de verdade é a criptografia do disco (FileVault no Mac), e o texto da tela deve dizer isso.

**Navegador e conteúdo**

- Cabeçalhos em todas as respostas: `Content-Security-Policy` com `default-src 'self'`, `img-src 'self' data: blob:`, `media-src 'self' blob:`, `frame-src 'self'` e `script-src 'self'`; `X-Frame-Options: SAMEORIGIN` (o PDF abre em iframe do mesmo endereço); `Referrer-Policy: no-referrer`; `X-Content-Type-Options: nosniff`.
- Nenhuma chamada externa: sem CDN, sem fontes da internet (usar a pilha de fontes do sistema do protótipo), sem analytics.
- Texto de mensagens sempre como texto (nunca `v-html`). Links detectados só para `http` e `https`.
- Caminhos de arquivo vêm sempre do banco, e o `realpath` precisa estar dentro de `{DATA}`. O envio aceita só `.txt` e `.zip`, conferindo também o conteúdo (início `PK` para zip), e verifica espaço livre em disco (pelo menos o dobro do arquivo) antes de começar.
- Logs sem conteúdo de mensagens: erros do importador registram número de linha e tamanho, não o texto. As linhas não reconhecidas ficam só no banco.

**Backup e restauração**

- `POST /api/backups` cria um job que gera uma cópia consistente do banco (`VACUUM INTO`) e empacota banco, mídia e miniaturas em `{DATA}/backups/ZapVault-backup-AAAA-MM-DD.zip`, sem recomprimir a mídia, com progresso por bytes. Ao terminar, grava em `settings.last_backup` a data, o nome e o tamanho, que alimentam as frases do protótipo ("Último backup: 20/10/2026 às 18:04" e o nome do arquivo com o tamanho).
- `php artisan zapvault:restore {arquivo}` restaura para uma pasta de dados vazia e recusa sobrescrever dados existentes.
- Uma cópia dentro de `{DATA}/backups` não basta: o texto da tela já diz "para guardar em outro lugar", e o README deve orientar a copiar o arquivo para um disco externo criptografado.

**Repositório**

- O `.env` e qualquer pasta de dados ficam fora do Git. Testes e dados de demonstração usam somente conversas fictícias; nenhuma conversa real entra no repositório.

## Testes e critérios de aceite

Todo código novo nasce com teste. Nenhuma etapa do roteiro termina com testes falhando. Todos os dados de teste são fictícios e gerados por código (fábricas e arquivos de exemplo sintéticos), nunca conversas reais.

**Testes de backend (Pest)**

- **Parser, com arquivos de exemplo sintéticos** em `tests/Fixtures/exports/`: formato Android 24h (`20/10/2026 18:04 - Nome: texto`), Android com vírgula e AM/PM, iOS com colchetes (`[20/10/2026 18:04:12] Nome: texto`), mensagem de várias linhas, linhas de sistema (criptografia, entrou, saiu, mudou o nome do grupo), chamadas perdidas, "mensagem apagada", "(arquivo anexado)" e "\<mídia oculta>", caracteres invisíveis (LRM, RLM, U+202F), nomes com emoji, e um arquivo com linhas inválidas para conferir a lista de linhas não reconhecidas.
- **Classificação de tipo:** imagem, vídeo, áudio, documento, figurinha, anexo ausente.
- **Duplicidade:** importar o mesmo arquivo duas vezes resulta em 0 mensagens novas e todas repetidas; duas mensagens idênticas no mesmo minuto (sufixo `:n`) continuam como duas; importar um arquivo maior que contém um menor soma só o que falta.
- **Fluxo em duas fases:** Cancelar apaga os rascunhos sem sobrar linha órfã; Concluir publica; "Somar à existente" e "Manter separada" geram o resultado esperado.
- **Zip:** zip com mídia, zip dentro de zip, tentativa de caminho com `../`, arquivo grande demais ou com razão de compressão suspeita são recusados com mensagem clara.
- **Busca:** `PROPOSTA`, `proposta` e `Proposta` retornam o mesmo; `acao` encontra "ação" e o inverso; `ç`, `ñ`, `ü` e emojis funcionam; consulta de 2 caracteres usa `LIKE`; de 3 ou mais usa FTS; mensagens de sistema, apagadas, áudios, figurinhas e anexos ausentes não aparecem; a contagem para em 10.000; legenda de imagem e nome de documento são pesquisáveis.
- **Endpoints:** paginação com `before`, `after`, `around` e `day`, incluindo `focus_id`; lista de conversas ordenada pela última mensagem; mídia com `Range` (206, `Content-Range`, intervalo inválido 416); recusa de caminho fora de `{DATA}`; cabeçalhos de segurança; bloqueio sem senha digitada quando a senha está ativa.
- **Backup e restauração:** gerar, restaurar em pasta vazia e comparar contagens e hashes com `zapvault:verify`.

**Testes de frontend (Vitest)**

- `search.ts` (porta de `norm`, `ranges`, `segs`) contra o mesmo conjunto de casos do protótipo: acentos, maiúsculas, várias ocorrências, termos sobrepostos, trecho com "…" no início.
- Formatação de datas e separadores ("Hoje", "Ontem", dia da semana, data completa), iniciais do avatar, duração de áudio, atribuição de cor por remetente.

**Desempenho (meta em máquina comum)**

`php artisan zapvault:demo --scale=large` gera cerca de 300 conversas e 1,5 milhão de mensagens fictícias. Nesse volume:

- Lista de conversas abre em menos de 300 ms.
- Busca por palavra comum responde em menos de 500 ms, e por nome de conversa em menos de 150 ms.
- Abrir uma conversa de 100 mil mensagens mostra as últimas em menos de 400 ms.
- Importar um arquivo de 200 mil mensagens sem travar a interface, com progresso visível.
- O navegador nunca mantém mais que algumas centenas de mensagens no DOM por vez.

**Critérios de aceite (resumo)**

1. Importo um `.txt` e um `.zip` com mídia; as conversas aparecem com nome, prévia e data corretos.
2. Reimportar o mesmo arquivo não cria nenhuma mensagem nova.
3. Procuro um contato pelo nome e uma frase dentro de uma conversa, com ou sem acento e em qualquer caixa, e chego à mensagem com o trecho destacado.
4. Vejo imagens, vídeos, áudios (com 1x, 1,5x e 2x), PDFs e documentos, e consigo baixar qualquer anexo.
5. Não existe nenhum caminho na interface para enviar mensagem.
6. Tema claro e escuro, layout de uma coluna em tela estreita, tudo em português do Brasil.
7. Com a internet desligada, tudo funciona igual.
8. Backup gerado e restaurado em pasta vazia mantém todas as conversas e anexos.
9. As telas conferem visualmente com o protótipo em `docs/prototipo/`, exceto os ajustes listados na seção "Telas, rotas e componentes".
10. `composer test` e `npm test` passam, e `php artisan zapvault:verify` não aponta inconsistências.

## Roteiro de implementação em etapas

O Claude Code trabalha uma etapa por vez. Ao fim de cada uma ele roda os testes, faz um commit, resume em poucas linhas o que ficou pronto e como conferir, e **para** para você validar antes de seguir. Cada etapa precisa deixar o aplicativo funcionando.

| # | Etapa | O que entra | Como conferir ao fim |
| --- | --- | --- | --- |
| 0 | Base do projeto | Laravel com SQLite, Inertia 2, Vue 3 com TypeScript, Tailwind, Pest, Vitest, tokens de cor claro e escuro do protótipo, `CLAUDE.md`, pasta de dados fora do projeto (`ZAPVAULT_DATA_PATH`), comando único de execução, cabeçalhos de segurança | `composer run zapvault` abre uma página em branco com o tema correto em `127.0.0.1`; testes de exemplo passam |
| 1 | Banco e busca | Migrations de todas as tabelas, tabela FTS5 com gatilhos, configurações do SQLite (WAL, `busy_timeout`), serviço de normalização de texto | Testes de normalização e de FTS passam com dados inseridos por fábrica |
| 2 | Importador e linha de comando | Leitura de `.txt`, `.zip` e zip aninhado, detecção dos três formatos, classificação de tipo, chave de duplicidade, inserção em lotes, extração de mídia, miniaturas, `zapvault:import`, `zapvault:reindex`, `zapvault:verify` | Todos os testes do parser passam; reimportar dá 0 novas; `zapvault:verify` limpo |
| 3 | Dados de demonstração | `zapvault:demo` com as conversas fictícias do protótipo e a opção `--scale=large` | O comando popula o banco; os números batem com o esperado |
| 4 | API | Os endpoints de conversas, mensagens (cursor), busca, contagens, calendário, mídia com `Range`, configurações | Testes de endpoint passam; chamadas manuais com `curl` devolvem o formato do documento |
| 5 | Estrutura da interface e lista | Layout de duas colunas e uma coluna em tela estreita, cabeçalho, lista de conversas, painel vazio, tema, busca com abas Conversas e Mensagens e destaque | Telas conferem com o protótipo usando os dados de demonstração |
| 6 | Tela da conversa | Mensagens por tipo, separadores de data, cores de remetentes, rolagem com carregamento de mensagens antigas, "Pular para data", botão de ir ao fim com contador, busca dentro da conversa com "3 de 12" e navegação com Enter e Shift+Enter, abrir a partir de resultado da busca | Abrir uma conversa de 100 mil mensagens fica fluido; saltar para uma data antiga funciona |
| 7 | Mídia e documentos | Visualizador de imagem e vídeo, player de áudio com velocidades, PDF em iframe, download, links clicáveis | Todos os tipos de anexo abrem; vídeo avança e retrocede (Range) |
| 8 | Importação pela interface | Arrastar e soltar, envio em partes de 8 MB, fila, progresso real por arquivo, estatísticas, "já existe", erro com linhas não reconhecidas, pergunta do seu nome, Concluir e Cancelar | Importo arquivos de exemplo pela tela e o resultado fica igual ao da linha de comando |
| 9 | Configurações, senha e backup | Nomes, tema, pasta de dados (somente orientação), backup e restauração, senha opcional com tela de desbloqueio, renomear e excluir conversa pelo menu ⋯ | Backup restaurado em pasta vazia mantém tudo; senha bloqueia e libera |
| 10 | Acabamento e verificação final | Acessibilidade (foco, teclado, contraste, rótulos), estados de carregamento e erro, textos em português revisados, README com uso diário e backup, teste de desempenho em escala grande, checklist de aceite completo | Todos os critérios de aceite da seção anterior marcados |

**Regras do roteiro**

- Uma etapa por vez, na ordem. Se uma decisão da especificação não funcionar na prática, o Claude Code explica o problema e propõe a mudança, em vez de decidir sozinho.
- Depois da etapa 2, importe um export seu de verdade só na sua máquina, fora do Git, para descobrir formatos que não previmos. Linhas não reconhecidas viram novos casos de teste com texto fictício equivalente.
- Commits pequenos, com mensagem clara em português, por exemplo `etapa 2: importador de .txt e .zip`.
- Nada fora do escopo: sem envio de mensagens, sem contas de usuário, sem sincronização em nuvem, sem serviços externos.
- As ideias da fase 2 (os 2 requisitos adiados do documento de produto) só entram depois da etapa 10 e com sua aprovação.

## CLAUDE.md sugerido

Salve o texto abaixo como `CLAUDE.md` na raiz do projeto. O Claude Code lê esse arquivo no início de toda sessão, então ele guarda as regras que não podem ser esquecidas, e remete à especificação para o detalhe.

```text
# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business. Roda só no computador do usuário. Interface em português do Brasil, parecida com o WhatsApp Web.

## Fontes de verdade
- docs/ESPECIFICACAO.md: especificação técnica completa (stack, banco, importador, busca, endpoints, roteiro por etapas). Leia antes de qualquer trabalho e siga.
- docs/tarefas/: uma tarefa por etapa do roteiro (00 a 10), com README, status e lista de entregas. É a fila de trabalho.
- docs/prototipo/: protótipo visual aprovado. Reproduza layout, textos, cores e comportamentos. Só é referência visual; não copie o formato de dados em memória nem o runtime dele.
- Se algo da especificação não funcionar ou houver conflito com o protótipo, explique o problema e proponha uma mudança. Não decida sozinho.

## Repositório
- Remoto: https://github.com/AbisaiSan/zapvalt.git, branch main.
- Nunca use push --force, reset --hard em commits já enviados nem reescreva o histórico.
- Nunca faça commit de .env, pasta de dados, arquivos .zip de conversas ou qualquer texto de conversa real.

## Stack
Laravel (versão estável atual), PHP 8.3+, SQLite com FTS5, Inertia 2, Vue 3 (Composition API, TypeScript), Tailwind, Pest, Vitest. Sem starter kit de autenticação.

## Regras que nunca mudam
- O aplicativo não envia mensagens e não escreve nada no WhatsApp. É somente leitura sobre o que foi importado.
- Tudo é local: escuta apenas 127.0.0.1, sem CDN, sem fontes externas, sem analytics, sem chamadas a serviços de terceiros.
- Nunca carregue o arquivo inteiro de uma conversa no navegador. Mensagens vêm em janelas paginadas por cursor, busca e contagens são feitas no servidor.
- Texto de mensagem é sempre exibido como texto, nunca como HTML.
- Caminhos de arquivos vêm do banco e precisam ficar dentro da pasta de dados (ZAPVAULT_DATA_PATH). Valide com realpath.
- Os dados do usuário ficam fora do projeto e fora do Git. Nunca coloque conversa real em código, testes, fábricas, exemplos ou logs. Use somente dados fictícios.
- Logs não podem conter o texto das mensagens.
- Importar o mesmo arquivo duas vezes nunca duplica mensagens.
- Cancelar uma importação não deixa nada para trás.
- Nunca rode migrate:fresh, apague o banco ou a pasta de dados fora do ambiente de testes.
- Não instale pacotes sem necessidade clara. Prefira o que o Laravel já oferece.

## Idioma e estilo
- Todo texto da interface, mensagens de erro, README e mensagens de commit em português do Brasil.
- Código, nomes de classes, variáveis e colunas em inglês, como na especificação.
- Use vírgulas em vez de travessões (em dash) nos textos escritos.
- Acessibilidade: foco visível, navegação por teclado, rótulos nos botões de ícone, contraste suficiente nos dois temas.

## Como trabalhar
- Siga as tarefas de docs/tarefas, uma por vez, na ordem, conforme docs/tarefas/README.md.
- Ao começar uma tarefa, marque o Status como em andamento. Ao terminar, marque como concluída, no arquivo da tarefa e na tabela do README.
- Ao terminar uma tarefa: rode os testes, faça commits pequenos com mensagem clara, envie com git push, resuma em poucas linhas o que ficou pronto e como o usuário confere, e PARE esperando a confirmação antes de começar a próxima.
- Escreva os testes junto com o código. Tarefa com teste falhando não está terminada.
- Se faltar uma informação que muda o resultado, pergunte. Se for detalhe pequeno, escolha o mais simples e registre a decisão em docs/DECISOES.md.
- Mantenha o README com o passo a passo de instalar, rodar, importar, fazer backup e restaurar.

## Comandos
- composer run zapvault: sobe servidor local e worker da fila (uso diário)
- composer run dev: desenvolvimento com recarga automática
- composer test: testes do backend (Pest)
- npm test: testes do frontend (Vitest)
- npm run build: compila a interface
- php artisan zapvault:import {arquivo}: importa um .txt ou .zip
- php artisan zapvault:demo [--scale=large]: gera dados fictícios
- php artisan zapvault:reindex: reconstrói a busca
- php artisan zapvault:verify: confere a consistência dos dados
- php artisan zapvault:backup e zapvault:restore {arquivo}
```

Se os nomes dos comandos mudarem durante a implementação, peça ao Claude Code para atualizar o `CLAUDE.md` na mesma etapa.
