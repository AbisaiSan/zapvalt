# Tarefa 00: Base do projeto

**Status:** pendente

## Objetivo

Deixar o projeto Laravel rodando no computador, com toda a base técnica pronta para as próximas tarefas.

## Onde ler na especificação

- Seção "Protótipo e decisões de stack" em `docs/ESPECIFICACAO.md`
- Seção "Segurança e execução local" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Laravel com SQLite configurado (WAL, `busy_timeout`) e `APP_TIMEZONE=America/Sao_Paulo`
- [ ] Inertia 2, Vue 3 (Composition API, TypeScript) e Tailwind, sem starter kit de autenticação
- [ ] Pest e Vitest configurados, com um teste de exemplo em cada
- [ ] Tokens de cor claro e escuro do protótipo (`data-zv-theme`, `localStorage` `zv-theme`) e fontes do sistema
- [ ] `.env.example` com `ZAPVAULT_DATA_PATH` e criação automática da pasta de dados fora do projeto
- [ ] Comandos `composer run zapvault` (servidor em 127.0.0.1 mais worker da fila) e `composer run dev`
- [ ] Cabeçalhos de segurança em todas as respostas
- [ ] `.gitignore` cobrindo `.env`, pasta de dados, `*.zip` e exports de conversas
- [ ] README inicial com instalar e rodar

## Como conferir

- [ ] `composer run zapvault` abre uma página em branco com o tema correto em `http://127.0.0.1:8000`
- [ ] `composer test` e `npm test` passam
- [ ] `git status` não mostra nenhum arquivo de dados ou de conversa

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
