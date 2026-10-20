# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business (.txt e .zip). Roda só no seu computador, com interface em português do Brasil parecida com o WhatsApp Web.

## Privacidade

Os dados ficam apenas no seu computador, na pasta definida em `ZAPVAULT_DATA_PATH` (fora do projeto). Conversas, arquivos .zip, bancos de dados e o `.env` nunca vão para o Git. O aplicativo escuta só em `127.0.0.1` e não faz chamadas para a internet.

## Requisitos

- PHP 8.3 ou superior, com as extensões `pdo_sqlite` (com FTS5), `intl`, `zip`, `gd` e `fileinfo`
- Composer
- Node.js 20 ou superior e npm

No Mac, com o Homebrew: `brew install php composer node`.

Opcionais, para duração de áudio e vídeo, pôster de vídeo e contagem de páginas de PDF: `brew install ffmpeg poppler`.

## Instalar

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Abra o `.env` e preencha `ZAPVAULT_DATA_PATH` com o caminho absoluto da pasta de dados, por exemplo `/Users/seu-usuario/ZapVault-dados`. Se ficar vazio, o aplicativo usa `~/ZapVault-dados`. A pasta e o banco são criados automaticamente, com acesso restrito ao seu usuário.

Depois:

```bash
php artisan migrate --force
npm run build
```

## Rodar

Uso diário (servidor em `http://127.0.0.1:8000` e o processador de importações):

```bash
composer run zapvault
```

Para desenvolver, com recarga automática da interface:

```bash
composer run dev
```

Para parar, use Ctrl+C no terminal.

## Importar conversas pelo terminal

Exporte a conversa no WhatsApp (com ou sem mídia) e rode:

```bash
php artisan zapvault:import "Conversa do WhatsApp com Fulano.zip"
```

O comando analisa os arquivos (aceita vários de uma vez, `.txt` ou `.zip`, inclusive `.zip` com outros `.zip` dentro), mostra o resumo de cada um (mensagens novas, repetidas, anexos encontrados e faltantes) e pergunta se deve concluir. Importar o mesmo arquivo de novo não duplica mensagens.

Opções:

- `--commit`: conclui sem perguntas
- `--merge` (padrão) ou `--separate`: o que fazer quando já existe conversa com o mesmo nome
- `--me="Seu Nome"`: informa seu nome nas mensagens

Sem `--commit`, fora do modo interativo, nada é gravado.

Outros comandos:

- `php artisan zapvault:verify`: confere contagens, índice de busca e arquivos de mídia (`--hashes` confere também o conteúdo de cada arquivo)
- `php artisan zapvault:reindex`: reconstrói o índice de busca

## Testes

```bash
composer test   # backend (Pest)
npm test        # frontend (Vitest)
```

## Documentação

A especificação, o protótipo e as tarefas ficam em `docs/`. As decisões tomadas durante o desenvolvimento ficam em `docs/DECISOES.md`.

Status: em desenvolvimento (tarefas 00 a 02 concluídas)
