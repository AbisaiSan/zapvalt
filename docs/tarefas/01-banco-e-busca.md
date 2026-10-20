# Tarefa 01: Banco e busca

**Status:** pendente

## Objetivo

Criar o banco com todas as tabelas e a busca de texto, sem ainda importar nada.

## Onde ler na especificação

- Seção "Banco de dados" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Migrations de `settings`, `import_batches`, `import_files`, `conversations`, `participants`, `messages`, `attachments`, `backups`
- [ ] Tabela `messages_fts` (FTS5, trigram, tabela externa) com gatilhos de inserção, atualização e exclusão
- [ ] Índices descritos na especificação e `queue.sqlite` separado para os jobs
- [ ] Serviço de normalização de texto (minúsculas, sem acentos, sem caracteres invisíveis) usado em `search_text`
- [ ] Fábricas com dados fictícios

## Como conferir

- [ ] Testes de normalização e de FTS passam com dados criados por fábrica
- [ ] `php artisan migrate:fresh` funciona no ambiente de testes

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
