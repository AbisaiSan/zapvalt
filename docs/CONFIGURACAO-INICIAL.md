# ZapVault: prompt de configuração inicial para o Claude Code

Oct 20, 2026 · @Abisai

## Como usar

Cole o prompt abaixo no Claude Code e ele monta sozinho a base do projeto, sobe para o GitHub e avisa quando terminar. Ele não escreve nenhuma funcionalidade do aplicativo; isso fica para as tarefas.

1. **Claude Code instalado.** Se ainda não instalou, siga a [página oficial](https://docs.claude.com/en/docs/claude-code/overview).
2. **Ferramentas prontas.** Laravel Herd (PHP e Composer), Node.js 20 ou superior e Git. O prompt confere tudo e, se algo faltar, para e diz o que instalar.
3. **Escolha a pasta dos projetos.** No Terminal, vá para onde você guarda seus projetos, por exemplo `cd ~/Documents`. O projeto será criado ali dentro, em uma pasta nova chamada `zapvault`.
4. **Abra o Claude Code.** Rode `claude` nessa pasta e faça o login do primeiro uso.
5. **Cole o prompt** da seção "Prompt de configuração inicial".
6. **Aprove os comandos.** O Claude Code pede permissão para cada comando novo; leia e aprove. Nenhum passo precisa de senha de administrador. Se ele pedir, negue e me conte.
7. **Responda o que ele perguntar.** Ele pode parar para pedir seu nome e e-mail do Git, ou o login no GitHub.
8. **Espere o aviso.** No fim aparece "CONFIGURAÇÃO INICIAL CONCLUÍDA" no terminal e, no Mac, uma notificação.

## O que o prompt faz e o que fica de fora

O prompt entrega um projeto Laravel limpo, versionado e já publicado no seu GitHub, com as regras do projeto no `CLAUDE.md`. A base técnica do aplicativo (Inertia, Vue, Tailwind, Vitest, banco e comando único de execução) continua na tarefa 00.

**Ele faz**

- Confere o ambiente: PHP, Composer, Node, npm, Git, SQLite com busca FTS5 trigram, e ferramentas opcionais de mídia.
- Cria a pasta `zapvault` com Laravel, Pest e SQLite, sem starter kit.
- Ajusta o básico: nome ZapVault, idioma pt\_BR, fuso `America/Sao_Paulo`, sessão e cache em arquivo, endereço `127.0.0.1`.
- Cria a pasta de dados fora do projeto (`ZapVault-dados`, na sua pasta pessoal) e a configuração `ZAPVAULT_DATA_PATH`.
- Reforça o `.gitignore` para nunca subir conversas, `.zip`, bancos, `.env` e dados.
- Cria `docs/`, `docs/tarefas/` e `docs/prototipo/`, o `CLAUDE.md` e um `README.md` em português.
- Roda um teste de fumaça: migrations, testes, build e servidor em `127.0.0.1`.
- Liga ao repositório `https://github.com/AbisaiSan/zapvalt.git`, faz o commit e o push na `main`.
- Avisa no terminal e com uma notificação do Mac, e para.

**Ele não faz**

- Não instala programas no sistema nem usa `sudo`.
- Não instala Inertia, Vue, Tailwind nem Vitest, não cria tabelas e não escreve tela alguma.
- Não cria os documentos. Você coloca a especificação, o protótipo e as tarefas depois do aviso.
- Não usa `push --force` e não reescreve histórico.
- Não começa nenhuma tarefa por conta própria.

## Prompt de configuração inicial

Copie o bloco inteiro, do começo ao fim. O `CLAUDE.md` já vai embutido (é o da especificação, com duas linhas a mais sobre os documentos e os comandos que ainda serão criados), então não precisa criar nenhum arquivo antes.

```text
Você vai fazer a CONFIGURAÇÃO INICIAL do projeto ZapVault e depois PARAR. Não implemente nenhuma funcionalidade do aplicativo.

O ZapVault é um aplicativo que roda só no meu computador (Mac) para guardar e consultar, em modo somente leitura, conversas do WhatsApp Business exportadas em .txt e .zip. A interface é em português do Brasil e parecida com o WhatsApp Web. Backend Laravel/PHP com banco SQLite. O desenvolvimento acontece depois, tarefa por tarefa, a partir de docs/tarefas.

Regras desta sessão:
- Trabalhe somente dentro da pasta zapvault, que você vai criar dentro da pasta atual. A única exceção é a pasta de dados em ~/ZapVault-dados (passo 4).
- Não use sudo e não instale programas no sistema (Homebrew, Herd, Node e semelhantes). Se algo obrigatório faltar, pare e me diga o que instalar e como.
- Não use push --force e não reescreva o histórico do Git.
- Não crie e não me peça conversas reais.
- Se uma escolha mudar bastante o resultado, pergunte. Detalhes pequenos, escolha o mais simples e registre no relatório final.
- Escreva em português do Brasil, com vírgulas em vez de travessões.

PASSO 1. Conferir a pasta e o ambiente
- Mostre a pasta atual (pwd). Se já existir uma pasta zapvault aqui e ela não estiver vazia, pare e pergunte.
- Confira e anote as versões de: php (precisa ser 8.3 ou superior), composer, node (20 ou superior), npm, git, sqlite3 e, se existir, o instalador laravel.
- Teste com um pequeno script PHP se o PDO SQLite tem FTS5 com o tokenizer trigram: crie uma tabela virtual em memória, insira um texto e busque um trecho do meio de uma palavra. Se falhar, continue, mas registre como RISCO GRAVE no relatório, porque a busca do aplicativo depende disso.
- Verifique se existem ffmpeg, ffprobe e pdfinfo (poppler). Apenas informe; não instale.
- Confira git config user.name e user.email. Se faltar algum, me pergunte (se eu não quiser expor meu e-mail, use o endereço noreply do GitHub) e configure apenas neste repositório, sem --global.
- Se faltar PHP 8.3+, Composer, Node 20+ ou Git, pare aqui.

PASSO 2. Criar o projeto
- Veja as opções atuais com laravel new --help e crie o projeto zapvault com Pest, banco SQLite, sem starter kit e sem autenticação, sem perguntas interativas se possível, na branch main. Se o comando laravel não existir ou falhar, use composer create-project laravel/laravel zapvault e instale o Pest pelo método oficial.
- Entre em zapvault. Se não houver repositório Git, rode git init -b main.
- Confirme com php artisan --version.

PASSO 3. Configuração básica (.env e .env.example, mantendo os dois iguais em estrutura)
- APP_NAME=ZapVault, APP_URL=http://127.0.0.1:8000, APP_LOCALE=pt_BR, APP_FALLBACK_LOCALE=pt_BR, APP_FAKER_LOCALE=pt_BR.
- Fuso horário: em config/app.php use 'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo') e defina APP_TIMEZONE=America/Sao_Paulo nos dois arquivos.
- Confirme DB_CONNECTION=sqlite. Use SESSION_DRIVER=file e CACHE_STORE=file. Não mexa na fila; ela será definida na tarefa 01.
- ZAPVAULT_DATA_PATH: no .env.example deixe vazio, com um comentário explicando que é a pasta de dados fora do projeto; no .env coloque o caminho absoluto de ~/ZapVault-dados (não use o til no .env).
- Crie config/zapvault.php retornando ['data_path' => env('ZAPVAULT_DATA_PATH')].
- Confirme que o .env está no .gitignore.

PASSO 4. Pasta de dados fora do projeto
- Crie ~/ZapVault-dados com mkdir -p e permissão chmod 700. Não coloque nada dentro dela. Ela nunca vai para o Git.

PASSO 5. .gitignore
- Acrescente, sem duplicar o que já existe: .DS_Store, *.zip, *.sqlite, *.sqlite-wal, *.sqlite-shm, /exports/, /dados/, WhatsApp*, ZapVault-dados/.

PASSO 6. Estrutura de documentação e arquivos do projeto
- Crie docs/, docs/tarefas/ e docs/prototipo/, cada uma com um arquivo .gitkeep vazio. Não crie outros documentos: eu coloco a especificação, o protótipo e as tarefas depois.
- Substitua o README.md padrão do Laravel por um README.md em português curto com: o que é o ZapVault, um aviso de privacidade (os dados ficam só no computador e conversas nunca vão para o Git), como rodar por enquanto (composer install, copiar .env.example para .env, php artisan key:generate, php artisan serve --host=127.0.0.1), onde ficam os documentos (docs/) e a linha 'Status: configuração inicial'.
- Crie o CLAUDE.md na raiz com exatamente o texto entre as linhas ===== INÍCIO DO CLAUDE.md ===== e ===== FIM DO CLAUDE.md ===== (sem incluir essas duas linhas).

PASSO 7. Teste de fumaça
- Se ainda for necessário, rode composer install e npm install. Gere a chave com php artisan key:generate se APP_KEY estiver vazia.
- Rode php artisan migrate --force, composer test e npm run build.
- Suba o servidor com php artisan serve --host=127.0.0.1 --port=8000 em segundo plano, confira com curl -sI http://127.0.0.1:8000 que a resposta é 200 e encerre o servidor.
- Se algo falhar, corrija quando for configuração simples; se não for, pare e me explique.

PASSO 8. Commit e envio para o GitHub
- Antes, rode git status e liste o que será commitado. Confirme que não há .env, banco SQLite, .zip, node_modules, vendor nem dados de conversa.
- Faça um ou dois commits em português, por exemplo 'projeto Laravel inicial com Pest e SQLite' e 'configuração básica, .gitignore, estrutura de docs, CLAUDE.md e README'.
- Remoto: se origin não existir, rode git remote add origin https://github.com/AbisaiSan/zapvalt.git. Se existir com outro endereço, pare e pergunte. Depois git branch -M main e git push -u origin main.
- Se o push for rejeitado porque o repositório remoto já tem commits (README, LICENSE): não force. Rode git pull origin main --allow-unrelated-histories --no-rebase, resolva conflitos mantendo o conteúdo dos dois lados (no README, prefira o nosso), me conte o que fez e tente o push de novo.
- Se o push falhar por autenticação (permission denied, 403, pedido de senha): pare e me explique as opções, que são entrar com o GitHub CLI (gh auth login) ou usar um token de acesso pessoal. Não peça minhas credenciais aqui. Quando eu disser que resolvi, tente o push de novo.
- Confirme que git status está limpo e que git rev-parse HEAD é igual ao commit de origin/main (use git ls-remote origin main).

PASSO 9. Aviso final e parada
- Mostre exatamente este relatório, preenchido:

CONFIGURAÇÃO INICIAL CONCLUÍDA
- Pasta do projeto: (caminho completo)
- Versões: PHP, Laravel, Composer, Node
- Busca FTS5 trigram: ok ou RISCO GRAVE
- Opcionais: ffmpeg, ffprobe e pdfinfo (presentes ou ausentes)
- Repositório: endereço, branch main, último commit (hash curto) já enviado
- Pasta de dados: (caminho)
- Decisões e avisos: (o que escolhi sozinho, o que falhou, o que corrigi)
- O que fazer agora: coloque estes arquivos na pasta do projeto, depois rode git add docs, git commit -m 'documentação inicial' e git push:
  docs/PRODUTO.md (opcional), docs/ESPECIFICACAO.md, docs/prototipo/ (arquivos do protótipo) e docs/tarefas/ (README.md e as tarefas 00 a 10)

- Depois do relatório, me avise também com uma notificação do Mac, rodando osascript -e 'display notification "Configuração inicial concluída. Volte ao terminal." with title "ZapVault"' (se falhar, ignore) e emita um aviso sonoro de terminal com printf '\a'.
- Em seguida PARE. Não inicie nenhuma tarefa e não faça mais nada até eu mandar a próxima instrução.

===== INÍCIO DO CLAUDE.md =====
# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business. Roda só no computador do usuário. Interface em português do Brasil, parecida com o WhatsApp Web.

## Fontes de verdade
- docs/ESPECIFICACAO.md: especificação técnica completa (stack, banco, importador, busca, endpoints, roteiro por etapas). Leia antes de qualquer trabalho e siga.
- docs/tarefas/: uma tarefa por etapa do roteiro (00 a 10), com README, status e lista de entregas. É a fila de trabalho.
- docs/prototipo/: protótipo visual aprovado. Reproduza layout, textos, cores e comportamentos. Só é referência visual; não copie o formato de dados em memória nem o runtime dele.
- docs/PRODUTO.md: visão de produto, regras de negócio e requisitos (se existir).
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
- composer run zapvault: sobe servidor local e worker da fila (uso diário), criado na tarefa 00
- composer run dev: desenvolvimento com recarga automática
- composer test: testes do backend (Pest)
- npm test: testes do frontend (Vitest), configurado na tarefa 00
- npm run build: compila a interface
- php artisan zapvault:import {arquivo}: importa um .txt ou .zip
- php artisan zapvault:demo [--scale=large]: gera dados fictícios
- php artisan zapvault:reindex: reconstrói a busca
- php artisan zapvault:verify: confere a consistência dos dados
- php artisan zapvault:backup e zapvault:restore {arquivo}
===== FIM DO CLAUDE.md =====
```

Os comandos `zapvault:*` e o `composer run zapvault` ainda não existem; eles nascem nas tarefas 00 a 09. O `CLAUDE.md` já diz isso.

## Depois do aviso: documentos e início das tarefas

Quando o Claude Code mostrar "CONFIGURAÇÃO INICIAL CONCLUÍDA", o projeto já está no GitHub com as pastas de documentos vazias. Agora você coloca os arquivos feitos aqui, na estrutura abaixo.

```text
zapvault/
├── CLAUDE.md
├── README.md
└── docs/
    ├── PRODUTO.md            (opcional, documento de produto)
    ├── ESPECIFICACAO.md      (especificação técnica)
    ├── prototipo/
    │   ├── ZapVault.dc.html
    │   ├── zapvault-data.js
    │   ├── support.js
    │   └── uploads/
    └── tarefas/
        ├── README.md
        ├── 00-base-do-projeto.md
        ├── ...
        └── 10-acabamento-e-verificacao-final.md
```

**De onde vem cada coisa**

| Destino | Origem |
| --- | --- |
| `docs/ESPECIFICACAO.md` | [Especificação técnica](https://claude.ai/code/artifact/b38ea84d-7a1a-4891-86fc-ca4bee1c0fdf), exportada em Markdown |
| `docs/PRODUTO.md` | [Documento de produto](https://claude.ai/code/artifact/646b3d66-f4f5-4a2d-be67-d36be3e33b51), exportado em Markdown |
| `docs/prototipo/` | Arquivos do .zip do protótipo que o Claude Design gerou. `ZapVault.dc.html` precisa ficar direto nessa pasta, sem uma pasta extra no meio |
| `docs/tarefas/` | O arquivo `tarefas.zip` enviado nesta conversa, descompactado dentro de `docs` |

O brief do Claude Design não é necessário para o desenvolvimento.

Os arquivos de tarefa citam `docs/ESPECIFICACAO.md` e `docs/prototipo/`. Por isso a especificação e o protótipo ficam em `docs/`, ao lado de `docs/tarefas/`, e não dentro dela. Colocar tudo em `docs/tarefas/` também funciona, mas então todos esses caminhos precisam ser trocados.

**Enviar os documentos ao GitHub**

Na pasta do projeto, rode:

1. `unzip ~/Downloads/tarefas.zip -d docs`
2. `git status`, para conferir que só aparecem arquivos dentro de `docs/`
3. `git add docs`
4. `git commit -m "documentação inicial"`
5. `git push`

**Iniciar as tarefas**

Rode `claude` na pasta do projeto e cole o texto da seção "Prompt para o Claude Code" da [especificação técnica](https://claude.ai/code/artifact/b38ea84d-7a1a-4891-86fc-ca4bee1c0fdf). Ele lê o `CLAUDE.md`, a especificação e `docs/tarefas/README.md`, e começa pela tarefa 00. A cada tarefa ele roda os testes, faz o commit, envia ao GitHub e para esperando você conferir.

## Se algo der errado

O Claude Code explica cada falha no terminal. Estes são os casos mais prováveis.

| Sintoma | O que fazer |
| --- | --- |
| `laravel: command not found` | Feche e abra o Terminal depois de instalar o Herd. O prompt também tenta `composer create-project` como alternativa. |
| PHP abaixo de 8.3 | Atualize a versão do PHP nas configurações do Herd e rode o prompt de novo. |
| Teste de FTS5 com trigram falhou | O relatório marca RISCO GRAVE. Siga em frente, copie a mensagem e me conte, porque a busca depende disso e há como contornar. |
| Push recusado porque o repositório já tem um README | O prompt faz o `pull` com histórico não relacionado e tenta de novo. Ele nunca usa `--force`. |
| Push com `permission denied`, 403 ou pedido de senha | Entre com o GitHub CLI (`gh auth login`, instalador em cli.github.com) ou crie um token de acesso pessoal com permissão de escrita neste repositório e use-o no lugar da senha. Depois diga ao Claude Code para tentar o push de novo. |
| Pede senha de administrador | Negue. Nenhum passo precisa disso. Copie a mensagem e me conte. |
| Já existe uma pasta `zapvault` com arquivos | Escolha outra pasta de projetos ou renomeie a antiga, e rode o prompt de novo. |
| A notificação do Mac não apareceu | O relatório no terminal é o aviso principal. Se quiser a notificação, libere o Terminal em Ajustes do Sistema, Notificações. |
| O Claude Code parou sem dizer nada | Olhe o terminal: ele provavelmente está esperando sua aprovação de um comando ou uma resposta. |
