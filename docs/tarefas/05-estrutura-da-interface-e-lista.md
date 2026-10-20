# Tarefa 05: Estrutura da interface e lista de conversas

**Status:** pendente

## Objetivo

Montar a casca da interface igual à do protótipo, com lista de conversas e busca.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Layout de duas colunas e de uma coluna em tela estreita (menos de 760 px)
- [ ] Cabeçalho, lista de conversas, painel vazio, tema claro e escuro
- [ ] Busca com mínimo de 2 caracteres, abas Conversas e Mensagens com contagens, trecho com destaque, espera de 250 ms e cancelamento de busca anterior
- [ ] Porta para TypeScript de `norm`, `ranges` e `segs` (`search.ts`) com testes Vitest
- [ ] Rotas `/`, `/c/{conversa}`, `/importar`, `/configuracoes` com navegação

## Como conferir

- [ ] Com os dados de demonstração, as telas conferem com o protótipo (claro, escuro e tela estreita)
- [ ] `npm test` passa

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
