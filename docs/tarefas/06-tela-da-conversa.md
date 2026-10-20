# Tarefa 06: Tela da conversa

**Status:** pendente

## Objetivo

Mostrar uma conversa inteira com rolagem fluida, busca interna e salto para qualquer data.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Endpoints e contratos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Balões por tipo de mensagem, separadores de data, cores por remetente
- [ ] Rolagem com carregamento de mensagens antigas em lotes, mantendo o DOM pequeno
- [ ] "Pular para data" agrupado por ano e mês, botão de ir ao fim com contador
- [ ] Busca dentro da conversa com "3 de 12", Enter e Shift+Enter
- [ ] Abrir uma conversa a partir de um resultado de busca já na mensagem, com destaque (`?m=`)

## Como conferir

- [ ] Abrir uma conversa de 100 mil mensagens (`--scale=large`) fica fluido
- [ ] Saltar para uma data antiga e voltar ao fim funciona

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
