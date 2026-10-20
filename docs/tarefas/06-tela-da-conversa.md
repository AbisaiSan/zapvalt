# Tarefa 06: Tela da conversa

**Status:** concluída

## Objetivo

Mostrar uma conversa inteira com rolagem fluida, busca interna e salto para qualquer data.

## Onde ler na especificação

- Seção "Telas, rotas e componentes" em `docs/ESPECIFICACAO.md`
- Seção "Endpoints e contratos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Balões por tipo de mensagem, separadores de data, cores por remetente
- [x] Rolagem com carregamento de mensagens antigas em lotes, mantendo o DOM pequeno
- [x] "Pular para data" agrupado por ano e mês, botão de ir ao fim com contador
- [x] Busca dentro da conversa com "3 de 12", Enter e Shift+Enter
- [x] Abrir uma conversa a partir de um resultado de busca já na mensagem, com destaque (`?m=`)

## Como conferir

- [x] Abrir uma conversa de 100 mil mensagens (`--scale=large`) fica fluido
- [x] Saltar para uma data antiga e voltar ao fim funciona

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 06").
- Conferido com capturas lado a lado do protótipo e com um teste automatizado no Chrome sobre a conversa de 100 mil mensagens (`--scale=large`): abrir, carregar antigas (DOM limitado a 300), pular para a data mais antiga e voltar ao fim.
- Visualizador de mídia, pré-visualização de documento e links clicáveis ficam para a tarefa 07.
