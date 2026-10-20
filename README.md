# ZapVault

Arquivo local e somente leitura de conversas exportadas do WhatsApp Business (.txt e .zip). Roda só no seu computador, com interface em português do Brasil parecida com o WhatsApp Web.

## Privacidade

Os dados ficam apenas no seu computador, na pasta definida em `ZAPVAULT_DATA_PATH` (fora do projeto). Conversas, arquivos .zip, bancos de dados e o `.env` nunca vão para o Git.

## Como rodar (por enquanto)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1
```

Depois abra http://127.0.0.1:8000.

## Documentação

A especificação, o protótipo e as tarefas ficam em `docs/`.

Status: configuração inicial
