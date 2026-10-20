# Tarefa 05: Estrutura da interface e lista de conversas

**Status:** concluída

## Objetivo

Montar a casca da interface igual à do protótipo, com lista de conversas e busca.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Layout de duas colunas e de uma coluna em tela estreita (menos de 760 px)
- [x] Cabeçalho, lista de conversas, painel vazio, tema claro e escuro
- [x] Busca com mínimo de 2 caracteres, abas Conversas e Mensagens com contagens, trecho com destaque, espera de 250 ms e cancelamento de busca anterior
- [x] Porta para TypeScript de `norm`, `ranges` e `segs` (`search.ts`) com testes Vitest
- [x] Rotas `/`, `/c/{conversa}`, `/importar`, `/configuracoes` com navegação

## Como conferir

- [x] Com os dados de demonstração, as telas conferem com o protótipo (claro, escuro e tela estreita)
- [x] `npm test` passa

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 05").
- Conferido com capturas lado a lado do protótipo (Chrome sem janela): lista, busca com as duas abas, tema escuro e tela estreita.
- Importar e Configurações têm só o cabeçalho e a navegação; o conteúdo vem nas tarefas 08 e 09.
