# Tarefa 01: Banco e busca

**Status:** concluída

## Objetivo

Criar o banco com todas as tabelas e a busca de texto, sem ainda importar nada.

## Onde ler na especificação

- Seção "Banco de dados" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Migrations de `settings`, `import_batches`, `import_files`, `conversations`, `participants`, `messages`, `attachments`, `backups`
- [x] Tabela `messages_fts` (FTS5, trigram, tabela externa) com gatilhos de inserção, atualização e exclusão
- [x] Índices descritos na especificação e `queue.sqlite` separado para os jobs
- [x] Serviço de normalização de texto (minúsculas, sem acentos, sem caracteres invisíveis) usado em `search_text`
- [x] Fábricas com dados fictícios

## Como conferir

- [x] Testes de normalização e de FTS passam com dados criados por fábrica
- [x] `php artisan migrate:fresh` funciona no ambiente de testes

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 01").
- A consulta de busca completa (contadores com teto, cursor, abas) entra com os endpoints na tarefa 04; aqui ficaram o índice, a normalização e os auxiliares de frase FTS e de `LIKE`.
