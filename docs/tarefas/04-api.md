# Tarefa 04: API

**Status:** pendente

## Objetivo

Entregar todos os endpoints JSON que a interface vai usar, já testados.

## Onde ler na especificação

- Seção "Endpoints e contratos" em `docs/ESPECIFICACAO.md`
- Seção "Busca" em `docs/ESPECIFICACAO.md`
- Seção "Mídia e arquivos" em `docs/ESPECIFICACAO.md`
- Telas e comportamentos: `docs/prototipo/`

## Entregas

- [ ] Conversas: lista, detalhe, renomear, excluir
- [ ] Mensagens por cursor (`before`, `after`, `around`, `day`, com `focus_id`)
- [ ] Busca de conversas e de mensagens (FTS para 3 ou mais caracteres, `LIKE` para 2), contagens limitadas a 10.000, busca dentro da conversa e calendário de dias
- [ ] Mídia com `Range` (206, 416), download e tipos seguros para exibição
- [ ] Configurações, estatísticas e endpoints de importação e backup (podem responder com a estrutura final e completar nas tarefas 08 e 09)
- [ ] Testes de endpoint para cada contrato

## Como conferir

- [ ] Testes de endpoint passam
- [ ] Chamadas manuais com `curl` devolvem exatamente o formato descrito na especificação

## Regras desta tarefa

- Escreva os testes junto com o código.
- Nada de dados reais de conversa em código, testes ou logs.
- Ao terminar: testes passando, commit, atualizar o status e parar para o usuário conferir.

## Anotações

(Espaço para o Claude Code registrar o que foi decidido ou ficou pendente.)
