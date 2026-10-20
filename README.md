# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business (.txt e .zip). Roda só no seu computador, com interface em português do Brasil parecida com o WhatsApp Web. Não envia mensagens e não escreve nada no WhatsApp.

## Privacidade

Os dados ficam apenas no seu computador, na pasta definida em `ZAPVAULT_DATA_PATH` (fora do projeto). Conversas, arquivos .zip, bancos de dados e o `.env` nunca vão para o Git. O aplicativo escuta só em `127.0.0.1`, não usa CDN, fontes nem serviços da internet, e funciona igual com a internet desligada.

## Requisitos

- PHP 8.3 ou superior, com as extensões `pdo_sqlite` (com FTS5), `intl`, `zip`, `gd` e `fileinfo`
- Composer
- Node.js 20 ou superior e npm

No Mac, com o Homebrew: `brew install php composer node`.

Opcionais, para duração de áudio e vídeo, pôster de vídeo e contagem de páginas de PDF: `brew install ffmpeg poppler`. Sem eles, tudo funciona, só sem essas informações.

## Instalar (uma vez)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Abra o `.env` e preencha `ZAPVAULT_DATA_PATH` com o caminho absoluto da pasta de dados, por exemplo `/Users/seu-usuario/ZapVault-dados`. Se ficar vazio, o aplicativo usa `~/ZapVault-dados`. A pasta e o banco são criados automaticamente, com acesso restrito ao seu usuário.

Para o uso diário, deixe também `APP_ENV=production` e `APP_DEBUG=false` no `.env`.

Depois:

```bash
php artisan zapvault:migrate
npm run build
```

## Uso diário

No Terminal, dentro da pasta do projeto:

```bash
composer run zapvault
```

Abra `http://127.0.0.1:8000` no navegador. O mesmo comando sobe o processador de importações e backups; deixe o Terminal aberto enquanto usa. Para parar, aperte Ctrl+C.

- **Buscar:** Ctrl+K (⌘K no Mac) vai para a busca. Ela ignora maiúsculas e acentos e procura no nome das conversas e no conteúdo das mensagens, legendas e nomes de documentos. Clicar em um resultado abre a conversa na mensagem, com o trecho destacado.
- **Na conversa:** a lupa busca dentro dela ("3 de 12", Enter e Shift+Enter), o calendário pula para uma data e o menu ⋯ renomeia ou exclui a conversa (só do ZapVault; os arquivos exportados não são tocados).
- **Esc** fecha, em ordem, o que estiver aberto por cima.

## Importar conversas

No celular, abra a conversa e use Mais opções › Exportar conversa (com mídia, para trazer fotos, áudios e documentos). Copie os arquivos para o computador.

Pela tela: botão "Importar", arraste os arquivos `.txt` ou `.zip` (ou use "Escolher arquivos"). Cada arquivo mostra o progresso e, ao terminar, quantas mensagens são novas ou repetidas e quantos anexos vieram. Se já existir uma conversa com o mesmo nome, escolha "Somar à existente" ou "Manter separada". Clique em "Concluir" para publicar ou em "Cancelar" para desfazer tudo. Importar o mesmo arquivo de novo não duplica mensagens.

Pelo terminal:

```bash
php artisan zapvault:import "Conversa do WhatsApp com Fulano.zip"
```

O comando aceita vários arquivos de uma vez (inclusive `.zip` com outros `.zip` dentro), mostra o resumo de cada um e pergunta se deve concluir. Opções:

- `--commit`: conclui sem perguntas
- `--merge` (padrão) ou `--separate`: o que fazer quando já existe conversa com o mesmo nome
- `--me="Seu Nome"`: informa seu nome nas mensagens

Sem `--commit`, fora do modo interativo, nada é gravado.

Seu nome nas mensagens (para elas aparecerem à direita) fica em Configurações, "Seu nome nos arquivos".

## Backup

Pela tela: Configurações, Backup, "Fazer backup agora". Pelo terminal:

```bash
php artisan zapvault:backup
```

O backup é um `.zip` em `{pasta de dados}/backups/ZapVault-backup-AAAA-MM-DD.zip`, com uma cópia consistente do banco, a mídia e as miniaturas. Ele pode ser feito com o aplicativo aberto; importações ainda não concluídas ficam de fora.

Uma cópia dentro da própria pasta de dados não protege contra a perda do computador: copie o arquivo para um disco externo, de preferência criptografado (no Mac, formate o disco como "APFS (Criptografado)" no Utilitário de Disco).

## Restaurar um backup

1. Pare o ZapVault (Ctrl+C no Terminal).
2. No `.env`, aponte `ZAPVAULT_DATA_PATH` para uma pasta nova (vazia ou que ainda não existe).
3. Rode:

    ```bash
    php artisan zapvault:restore /caminho/para/ZapVault-backup-AAAA-MM-DD.zip
    ```

4. Abra de novo com `composer run zapvault`.

O comando recusa restaurar por cima de uma pasta que já tem conversas ou anexos. No fim, ele roda o `zapvault:verify --hashes` para conferir contagens e o conteúdo de cada arquivo. A senha, se estava ativa, volta junto.

## Senha

Em Configurações, "Senha (opcional)" pede uma senha de pelo menos 6 caracteres sempre que o ZapVault for aberto; "Bloquear agora" fecha na hora. O desbloqueio dura até o navegador ser fechado ou ficar 2 horas sem uso, e no máximo 12 horas. Depois de 5 senhas erradas, cada nova tentativa espera mais (até 60 segundos).

A senha protege a abertura do aplicativo, mas não criptografa o banco nem os anexos. Para proteger os dados de verdade, ative a criptografia do disco (FileVault, no Mac). Se esquecer a senha, pare o aplicativo e rode `php artisan tinker --execute "App\Support\AppLock::removePassword()"` na pasta do projeto.

## Mudar a pasta de dados

Pare o ZapVault, mova a pasta inteira para o novo lugar, troque `ZAPVAULT_DATA_PATH` no `.env` e abra de novo. O aplicativo não move os dados sozinho.

## Atualizar para uma nova versão

```bash
git pull
composer install
npm install
npm run build
php artisan zapvault:migrate
```

O `zapvault:migrate` guarda uma cópia do banco em `{pasta de dados}/backups/antes-de-atualizar-*.sqlite` antes de aplicar qualquer mudança. Nunca use `migrate:fresh` com dados reais: ele apaga tudo.

## Outros comandos

- `php artisan zapvault:verify`: confere contagens, índice de busca e arquivos de mídia (`--hashes` confere também o conteúdo de cada arquivo)
- `php artisan zapvault:reindex`: reconstrói o índice de busca
- `php artisan zapvault:demo`: cria 12 conversas fictícias com mídia de exemplo (`--scale=large` gera cerca de 300 conversas e 1,5 milhão de mensagens; com conversas no acervo, pergunta antes ou exige `--force`)
- `composer run dev`: modo de desenvolvimento, com recarga automática da interface

## Testes

```bash
composer test                          # backend (Pest)
npm test                               # frontend (Vitest)
php artisan test --group=performance   # metas de tempo com 1,5 milhão de mensagens (cerca de 1 minuto)
```

## Documentação

A especificação, o protótipo e as tarefas ficam em `docs/`. As decisões tomadas durante o desenvolvimento ficam em `docs/DECISOES.md`.
