# Tarefa 04: API

**Status:** concluída

## Objetivo

Entregar todos os endpoints JSON que a interface vai usar, já testados.

## Onde ler na especificação

- Seção "Endpoints e contratos" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Seção "Mídia e arquivos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [x] Conversas: lista, detalhe, renomear, excluir
- [x] Mensagens por cursor (`before`, `after`, `around`, `day`, com `focus_id`)
- [x] Busca de conversas e de mensagens (FTS para 3 ou mais caracteres, `LIKE` para 2), contagens limitadas a 10.000, busca dentro da conversa e calendário de dias
- [x] Mídia com `Range` (206, 416), download e tipos seguros para exibição
- [x] Configurações, estatísticas e endpoints de importação e backup (podem responder com a estrutura final e completar nas tarefas 08 e 09)
- [x] Testes de endpoint para cada contrato

## Como conferir

- [x] Testes de endpoint passam
- [x] Chamadas manuais com `curl` devolvem exatamente o formato descrito na especificação

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

- Decisões em `docs/DECISOES.md` (seção "Tarefa 04").
- Senha, bloqueio e backup respondem 501 até a tarefa 09; a importação já está completa no backend (a tela vem na tarefa 08).
- Desempenho com 1,5 milhão de mensagens: lista em menos de 10 ms, abrir conversa de 100 mil em menos de 25 ms, busca por nome em menos de 10 ms, busca por palavra comum em cerca de 0,4 s; busca de 2 letras (`LIKE`) em cerca de 1,1 s.
